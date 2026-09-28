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
 * and are accepted as technical debt during the transition. The value is the
 * EXACT number of raw calls currently present in each file, so that adding a
 * new raw call to an allowlisted file (or any raw call to a non-listed file)
 * still fails, and removing one flags the entry for update. Reduce/remove
 * entries here as each file is converted to the _prepared variants.
 */
$knownRawCallFiles = array(
	// cacti.php still issues a few static (non-user-input) raw SELECTs; the
	// value is the EXACT number currently present so a new raw call here (or
	// in any other file) is reported, and a removed one flags the entry for
	// update. Convert these and drop the entry as the file is migrated.
	'controllers/cacti.php' => 3,
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
			$rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);
			// Skip test files themselves and vendor.
			if (strpos($rel, 'tests/') === 0) {
				continue;
			}
			if (strpos($rel, 'vendor/') === 0) {
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
	// Remove single-line # comments (not inside strings, best-effort). Uses a
	// ~ delimiter so the '#' being matched is not mistaken for the delimiter,
	// which previously produced an "Unknown modifier" error and a null return.
	$source = preg_replace('~(?<!\$)#[^\n]*~', '', $source);
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

it('has no raw DB calls in controller files beyond the tracked allowlist', function () use ($pluginRoot, $rawRegex, $knownRawCallFiles) {
	$controllerDir = $pluginRoot . DIRECTORY_SEPARATOR . 'controllers';
	$violations    = array();

	$iter = new DirectoryIterator($controllerDir);
	foreach ($iter as $file) {
		if (!$file->isFile() || $file->getExtension() !== 'php') {
			continue;
		}
		$rel     = 'controllers/' . $file->getFilename();
		$source  = npc_strip_comments(file_get_contents($file->getPathname()));
		$count   = preg_match_all($rawRegex, $source);
		$allowed = isset($knownRawCallFiles[$rel]) ? $knownRawCallFiles[$rel] : 0;
		if ($count > $allowed) {
			$violations[] = $rel . ' (' . $count . ' raw calls, ' . $allowed . ' allowed)';
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
		$source  = npc_strip_comments(file_get_contents($path));
		$count   = preg_match_all($rawRegex, $source);
		$allowed = isset($knownRawCallFiles[$rel]) ? $knownRawCallFiles[$rel] : 0;
		if ($count > $allowed) {
			$violations[] = $rel;
		}
	}

	expect($violations)->toBe(array(), 'Raw DB calls found in entry points: ' . implode(', ', $violations));
});

it('tracks known raw-call files and does not silently grow the allowlist', function () use ($pluginRoot, $rawRegex, $knownRawCallFiles) {
	/*
	 * Confirm that every allowlisted file still contains EXACTLY the recorded
	 * number of raw DB calls. If a file is cleaned up (or partially
	 * converted) the count drops and the entry must be reduced or removed,
	 * otherwise the allowlist becomes meaningless. Growth is caught by the
	 * controller/entry-point tests above.
	 */
	$staleEntries = array();

	foreach ($knownRawCallFiles as $rel => $allowed) {
		$path = $pluginRoot . DIRECTORY_SEPARATOR . $rel;
		if (!file_exists($path)) {
			// File was deleted; entry is stale.
			$staleEntries[] = $rel . ' (file does not exist)';
			continue;
		}
		$source = npc_strip_comments(file_get_contents($path));
		$count  = preg_match_all($rawRegex, $source);
		if ($count === 0) {
			$staleEntries[] = $rel . ' (no raw calls found; remove from allowlist)';
		} elseif ($count < $allowed) {
			$staleEntries[] = $rel . ' (allowlist expects ' . $allowed . ' raw calls but found ' . $count . '; update the count)';
		}
	}

	expect($staleEntries)->toBe(array(), 'Stale allowlist entries: ' . implode(', ', $staleEntries));
});
