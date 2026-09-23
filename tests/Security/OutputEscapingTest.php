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
 * Verifies XSS prevention in PHP and JavaScript sources.
 *
 * PHP side: user-controlled values must pass through html_escape() or __esc()
 * before being interpolated into HTML output. Direct interpolation of
 * superglobals ($_GET, $_POST, $_REQUEST, $_COOKIE) into echo/print
 * statements without escaping is flagged.
 *
 * JS side: dangerous sinks (innerHTML assignment, eval(), document.write())
 * are scanned for in plugin-owned JavaScript files.
 */

$pluginRoot = dirname(__DIR__, 2);

function npc_oe_strip_comments($src) {
	$src = preg_replace('#/\*.*?\*/#s', '', $src);
	$src = preg_replace('#//[^\n]*#', '', $src);
	return $src;
}

function npc_oe_collect_php($root) {
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

function npc_oe_collect_js($root) {
	$files = array();
	$jsDir = $root . DIRECTORY_SEPARATOR . 'js';
	if (!is_dir($jsDir)) {
		return $files;
	}
	$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($jsDir));
	foreach ($iter as $file) {
		if (!$file->isFile()) {
			continue;
		}
		$ext = strtolower($file->getExtension());
		if ($ext !== 'js') {
			continue;
		}
		// Skip minified bundles and the legacy ExtJS directory.
		$rel = ltrim(str_replace($root . DIRECTORY_SEPARATOR . 'js', '', $file->getPathname()), DIRECTORY_SEPARATOR);
		if (strpos($rel, 'ext' . DIRECTORY_SEPARATOR) === 0) {
			continue;
		}
		if (strpos($file->getFilename(), '-min.js') !== false) {
			continue;
		}
		$files[] = $file->getPathname();
	}
	sort($files);
	return $files;
}

$phpFiles = npc_oe_collect_php($pluginRoot);
$jsFiles  = npc_oe_collect_js($pluginRoot);

// ---------------------------------------------------------------------------

it('has no unescaped superglobal interpolation in echo/print statements', function () use ($phpFiles) {
	/*
	 * Pattern: echo/print containing $_GET/$_POST/$_REQUEST/$_COOKIE
	 * without passing through html_escape or __esc. This is a conservative
	 * regex that catches the most common forms; manual review covers edge cases.
	 */
	$violations = array();

	foreach ($phpFiles as $path) {
		$src = npc_oe_strip_comments(file_get_contents($path));

		// Find echo/print lines that reference a superglobal directly.
		if (preg_match_all(
			'/(echo|print)\s[^;]*\$_(GET|POST|REQUEST|COOKIE)\s*\[[^;]*/i',
			$src,
			$matches
		)) {
			// Check each match to see if it is wrapped in html_escape/__esc.
			foreach ($matches[0] as $match) {
				if (!preg_match('/html_escape\s*\(|__esc\s*\(/', $match)) {
					$violations[] = basename($path) . ': ' . trim(substr($match, 0, 80));
				}
			}
		}
	}

	expect($violations)->toBe(array(), "Unescaped superglobal output:\n" . implode("\n", $violations));
});

it('does not use innerHTML in plugin-owned JavaScript', function () use ($jsFiles) {
	$violations = array();

	foreach ($jsFiles as $path) {
		$src = npc_oe_strip_comments(file_get_contents($path));
		if (preg_match('/\.innerHTML\s*=(?!=)/', $src)) {
			$violations[] = basename($path);
		}
	}

	expect($violations)->toBe(array(), 'innerHTML assignment found in JS: ' . implode(', ', $violations));
});

it('does not use eval() in plugin-owned JavaScript', function () use ($jsFiles) {
	$violations = array();

	foreach ($jsFiles as $path) {
		$src = npc_oe_strip_comments(file_get_contents($path));
		// Avoid matching "eval" as a substring (e.g. "evaluate").
		if (preg_match('/\beval\s*\(/', $src)) {
			$violations[] = basename($path);
		}
	}

	expect($violations)->toBe(array(), 'eval() found in JS: ' . implode(', ', $violations));
});

it('does not use document.write() in plugin-owned JavaScript', function () use ($jsFiles) {
	$violations = array();

	foreach ($jsFiles as $path) {
		$src = npc_oe_strip_comments(file_get_contents($path));
		if (preg_match('/document\s*\.\s*write\s*\(/', $src)) {
			$violations[] = basename($path);
		}
	}

	expect($violations)->toBe(array(), 'document.write() found in JS: ' . implode(', ', $violations));
});

it('does not reference ExtJS Ext.* namespace in PHP output strings', function () use ($phpFiles) {
	/*
	 * After the ExtJS removal, PHP files should not generate Ext.* JavaScript
	 * calls. Inline mentions in comments are acceptable but live output is not.
	 */
	$violations = array();

	foreach ($phpFiles as $path) {
		$src = npc_oe_strip_comments(file_get_contents($path));
		// Match echo/print blocks that output Ext. references.
		if (preg_match_all('/(echo|print)\s[^;]*\bExt\.\w+/i', $src, $m)) {
			$violations[] = basename($path);
		}
	}

	expect($violations)->toBe(array(), 'Ext.* output in PHP: ' . implode(', ', $violations));
});
