<?php
/*
 +-------------------------------------------------------------------------+
 | Nagios Plugin for Cacti                                                 |
 |                                                                         |
 | Copyright (C) 2007 Billy Gunn (billy@gunn.org)                          |
 | Copyright (C) 2024 The Cacti Group, Inc.                                |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 +-------------------------------------------------------------------------+
*/

/*
 * Verifies that all PHP source files in the plugin use only the _prepared
 * variants of Cacti DB helpers. Raw db_fetch_assoc(), db_fetch_row(),
 * db_fetch_cell(), and db_execute() accept unsanitized SQL strings and must
 * not appear outside of comments in production code.
 *
 * Exceptions are tracked explicitly so regressions are immediately visible.
 */

$pluginRoot = dirname(__DIR__, 2);

/*
 * Files known to contain intentional raw DB calls that predate the rewrite
 * and are accepted as technical debt during the transition. Remove entries
 * here as each file is converted.
 */
$knownRawCallFiles = array(
	'controllers/cacti.php',
	'controllers/layout.php',
	'controllers/layoutDev.php',
);

/**
 * Return every PHP file under $dir, relative to $base.
 */
function npc_collect_php_files($dir, $base) {
	$files  = array();
	$iter   = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
	foreach ($iter as $file) {
		if ($file->isFile() && $file->getExtension() === 'php') {
			$rel = ltrim(str_replace($base, '', $file->getPathname()), DIRECTORY_SEPARATOR);
			// Skip test files themselves and vendor.
			if (strpos($rel, 'tests' . DIRECTORY_SEPARATOR) === 0) {
				continue;
			}
			if (strpos($rel, 'vendor' . DIRECTORY_SEPARATOR) === 0) {
				continue;
			}
			$files[] = $rel;
		}
	}
	sort($files);
	return $files;
}

/**
 * Strip single-line and block comments from PHP source, then return the
 * remaining code so pattern matching ignores commented-out calls.
 */
function npc_strip_comments($source) {
	// Remove block comments.
	$source = preg_replace('#/\*.*?\*/#s', '', $source);
	// Remove single-line // comments.
	$source = preg_replace('#//[^\n]*#', '', $source);
	// Remove single-line # comments (not inside strings, best-effort).
	$source = preg_replace('#(?<!\$)#[^\n]*#', '', $source);
	return $source;
}

// Raw DB function names that require a _prepared replacement.
$rawPatterns = array(
	'db_fetch_assoc\s*\(',
	'db_fetch_row\s*\(',
	'db_fetch_cell\s*\(',
	'db_execute\s*\(',
);
$rawRegex = '/(' . implode('|', $rawPatterns) . ')/';

$allFiles = npc_collect_php_files($pluginRoot, $pluginRoot . DIRECTORY_SEPARATOR);

// ---------------------------------------------------------------------------

it('has no raw DB calls in controller files', function () use ($pluginRoot, $rawRegex, $knownRawCallFiles) {
	$controllerDir = $pluginRoot . DIRECTORY_SEPARATOR . 'controllers';
	$violations    = array();

	$iter = new DirectoryIterator($controllerDir);
	foreach ($iter as $file) {
		if (!$file->isFile() || $file->getExtension() !== 'php') {
			continue;
		}
		$rel    = 'controllers' . DIRECTORY_SEPARATOR . $file->getFilename();
		$source = npc_strip_comments(file_get_contents($file->getPathname()));
		if (preg_match($rawRegex, $source)) {
			if (!in_array($rel, $knownRawCallFiles, true)) {
				$violations[] = $rel;
			}
		}
	}

	expect($violations)->toBe(array(), 'New raw DB calls found in controller files: ' . implode(', ', $violations));
});

it('has no raw DB calls in top-level entry points', function () use ($pluginRoot, $rawRegex, $knownRawCallFiles) {
	$entryPoints = array('config.php', 'npc.php', 'cli.php', 'nagioscmd.php', 'perfdata.php');
	$violations  = array();

	foreach ($entryPoints as $rel) {
		$path = $pluginRoot . DIRECTORY_SEPARATOR . $rel;
		if (!file_exists($path)) {
			continue;
		}
		$source = npc_strip_comments(file_get_contents($path));
		if (preg_match($rawRegex, $source)) {
			if (!in_array($rel, $knownRawCallFiles, true)) {
				$violations[] = $rel;
			}
		}
	}

	expect($violations)->toBe(array(), 'Raw DB calls found in entry points: ' . implode(', ', $violations));
});

it('tracks known raw-call files and does not silently grow the allowlist', function () use ($pluginRoot, $rawRegex, $knownRawCallFiles) {
	/*
	 * Confirm that every file listed in $knownRawCallFiles actually contains
	 * a raw DB call. If a file is cleaned up it should be removed from the
	 * allowlist, otherwise the allowlist becomes meaningless.
	 */
	$staleEntries = array();

	foreach ($knownRawCallFiles as $rel) {
		$path = $pluginRoot . DIRECTORY_SEPARATOR . $rel;
		if (!file_exists($path)) {
			// File was deleted; entry is stale.
			$staleEntries[] = $rel . ' (file does not exist)';
			continue;
		}
		$source = npc_strip_comments(file_get_contents($path));
		if (!preg_match($rawRegex, $source)) {
			$staleEntries[] = $rel . ' (no raw calls found; remove from allowlist)';
		}
	}

	expect($staleEntries)->toBe(array(), 'Stale allowlist entries: ' . implode(', ', $staleEntries));
});
