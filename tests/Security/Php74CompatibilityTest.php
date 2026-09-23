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
 * Cacti 1.2.x runs on PHP 7.4. Any PHP 8.0+ syntax in plugin code will cause
 * fatal parse errors on production hosts that have not been upgraded.
 *
 * Each test scans PHP source for a pattern that is invalid or unavailable in
 * PHP 7.4 and fails if any match is found outside of comments.
 */

$pluginRoot = dirname(__DIR__, 2);

/**
 * Collect all plugin PHP files, excluding tests/ and vendor/.
 */
function npc_php74_collect_files($root) {
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

/**
 * Strip block and line comments so patterns are not triggered by commented
 * examples.
 */
function npc_php74_strip_comments($src) {
	$src = preg_replace('#/\*.*?\*/#s', '', $src);
	$src = preg_replace('#//[^\n]*#', '', $src);
	return $src;
}

function npc_php74_scan($files, $pattern, $description) {
	$hits = array();
	foreach ($files as $path) {
		$src = npc_php74_strip_comments(file_get_contents($path));
		if (preg_match($pattern, $src)) {
			$hits[] = basename($path);
		}
	}
	return $hits;
}

$sourceFiles = npc_php74_collect_files($pluginRoot);

// ---------------------------------------------------------------------------

it('uses no str_contains() (PHP 8.0+)', function () use ($sourceFiles) {
	$hits = npc_php74_scan($sourceFiles, '/\bstr_contains\s*\(/', 'str_contains');
	expect($hits)->toBe(array(), 'PHP 8.0 str_contains() found in: ' . implode(', ', $hits));
});

it('uses no str_starts_with() (PHP 8.0+)', function () use ($sourceFiles) {
	$hits = npc_php74_scan($sourceFiles, '/\bstr_starts_with\s*\(/', 'str_starts_with');
	expect($hits)->toBe(array(), 'PHP 8.0 str_starts_with() found in: ' . implode(', ', $hits));
});

it('uses no str_ends_with() (PHP 8.0+)', function () use ($sourceFiles) {
	$hits = npc_php74_scan($sourceFiles, '/\bstr_ends_with\s*\(/', 'str_ends_with');
	expect($hits)->toBe(array(), 'PHP 8.0 str_ends_with() found in: ' . implode(', ', $hits));
});

it('uses no nullsafe operator ?-> (PHP 8.0+)', function () use ($sourceFiles) {
	$hits = npc_php74_scan($sourceFiles, '/\?->/', 'nullsafe operator');
	expect($hits)->toBe(array(), 'PHP 8.0 nullsafe operator found in: ' . implode(', ', $hits));
});

it('uses no union types in function signatures (PHP 8.0+)', function () use ($sourceFiles) {
	// Match: "function foo(int|string" or "): int|string"
	$hits = npc_php74_scan(
		$sourceFiles,
		'/function\s+\w+\s*\([^)]*\b\w+\|\w+/',
		'union types'
	);
	expect($hits)->toBe(array(), 'PHP 8.0 union types found in: ' . implode(', ', $hits));
});

it('uses no match expressions (PHP 8.0+)', function () use ($sourceFiles) {
	// "match (" or "match(" not preceded by a letter (to avoid matching "preg_match(")
	$hits = npc_php74_scan($sourceFiles, '/(?<!\w)match\s*\(/', 'match expression');
	expect($hits)->toBe(array(), 'PHP 8.0 match expression found in: ' . implode(', ', $hits));
});

it('uses no constructor property promotion (PHP 8.0+)', function () use ($sourceFiles) {
	// "public readonly $foo" or "(public $foo" inside __construct signatures
	$hits = npc_php74_scan(
		$sourceFiles,
		'/function\s+__construct\s*\([^)]*\b(public|protected|private)\s+\$/',
		'constructor property promotion'
	);
	expect($hits)->toBe(array(), 'PHP 8.0 constructor property promotion found in: ' . implode(', ', $hits));
});

it('uses no named arguments (PHP 8.0+)', function () use ($sourceFiles) {
	// "funcName(argName: value)" — identifier followed by colon inside a call.
	// Best-effort heuristic: "word: " inside parentheses. May have false
	// positives in string literals; acceptable for a security scan.
	$hits = npc_php74_scan($sourceFiles, '/\(\s*\w+\s*:\s*[^\s)]/', 'named arguments');
	expect($hits)->toBe(array(), 'PHP 8.0 named arguments found in: ' . implode(', ', $hits));
});

it('uses no readonly properties (PHP 8.1+)', function () use ($sourceFiles) {
	$hits = npc_php74_scan($sourceFiles, '/\breadonly\s+\$/', 'readonly property');
	expect($hits)->toBe(array(), 'PHP 8.1 readonly property found in: ' . implode(', ', $hits));
});

it('uses no enum declarations (PHP 8.1+)', function () use ($sourceFiles) {
	$hits = npc_php74_scan($sourceFiles, '/\benum\s+\w+/', 'enum declaration');
	expect($hits)->toBe(array(), 'PHP 8.1 enum found in: ' . implode(', ', $hits));
});
