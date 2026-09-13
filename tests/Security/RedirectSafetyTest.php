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
 * Verifies that every HTTP redirect is followed by an exit/die statement.
 *
 * In PHP, header('Location: ...') does not stop script execution. Any code
 * that continues after the redirect may process or output data as if the user
 * were authenticated, even though the browser will follow the redirect. This
 * is a common security mistake in legacy Cacti plugins.
 */

$pluginRoot = dirname(__DIR__, 2);

function npc_redir_collect_php($root) {
	$files = array();
	$iter  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
	foreach ($iter as $file) {
		if (!$file->isFile() || $file->getExtension() !== 'php') {
			continue;
		}
		$rel = ltrim(str_replace($root, '', $file->getPathname()), DIRECTORY_SEPARATOR);
		if (strpos($rel, 'tests' . DIRECTORY_SEPARATOR) === 0) {
			continue;
		}
		if (strpos($rel, 'vendor' . DIRECTORY_SEPARATOR) === 0) {
			continue;
		}
		$files[] = $file->getPathname();
	}
	sort($files);
	return $files;
}

function npc_redir_strip_comments($src) {
	$src = preg_replace('#/\*.*?\*/#s', '', $src);
	$src = preg_replace('#//[^\n]*#', '', $src);
	return $src;
}

$phpFiles = npc_redir_collect_php($pluginRoot);

// ---------------------------------------------------------------------------

it('every Location redirect is followed by exit or die within 3 lines', function () use ($phpFiles) {
	$violations = array();

	foreach ($phpFiles as $path) {
		$src   = npc_redir_strip_comments(file_get_contents($path));
		$lines = explode("\n", $src);
		$count = count($lines);

		for ($i = 0; $i < $count; $i++) {
			$line = $lines[$i];

			// Match: header('Location: ...' or header("Location: ...".
			if (!preg_match('/header\s*\(\s*[\'"]Location\s*:/i', $line)) {
				continue;
			}

			// Scan the next 3 lines (inclusive) for exit or die.
			$found = false;
			for ($j = $i; $j <= min($i + 3, $count - 1); $j++) {
				if (preg_match('/\b(exit|die)\s*[;(]/', $lines[$j])) {
					$found = true;
					break;
				}
			}

			if (!$found) {
				$violations[] = basename($path) . ':' . ($i + 1) . ' — redirect without exit';
			}
		}
	}

	expect($violations)->toBe(
		array(),
		"Redirects missing exit:\n" . implode("\n", $violations)
	);
});

it('has no open redirect via unsanitized superglobal in Location header', function () use ($phpFiles) {
	/*
	 * header('Location: ' . $_GET['url']) without validation allows an
	 * attacker to redirect victims to an external site. Flag any Location
	 * header that concatenates a superglobal without an allowlist check.
	 */
	$violations = array();

	foreach ($phpFiles as $path) {
		$src   = npc_redir_strip_comments(file_get_contents($path));
		$lines = explode("\n", $src);

		foreach ($lines as $no => $line) {
			if (!preg_match('/header\s*\(\s*[\'"]Location\s*:/i', $line)) {
				continue;
			}
			if (preg_match('/\$_(GET|POST|REQUEST|COOKIE)\s*\[/', $line)) {
				$violations[] = basename($path) . ':' . ($no + 1);
			}
		}
	}

	expect($violations)->toBe(
		array(),
		"Open redirect candidates:\n" . implode("\n", $violations)
	);
});
