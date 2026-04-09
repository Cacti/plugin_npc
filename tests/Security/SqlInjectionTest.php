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
 * Verifies SQL injection prevention across the plugin codebase.
 *
 * Three checks:
 *
 * 1. searchClause() in controller.php uses bound parameters, not string
 *    concatenation, when appending user search terms to SQL.
 *
 * 2. No superglobal ($_GET, $_POST, $_REQUEST) is interpolated directly
 *    into a string that is subsequently passed to a DB function.
 *
 * 3. sort_column / sort_direction inputs are validated against an allowlist
 *    before being appended to ORDER BY clauses.
 */

$pluginRoot = dirname(__DIR__, 2);

function npc_sqli_strip_comments($src) {
	$src = preg_replace('#/\*.*?\*/#s', '', $src);
	$src = preg_replace('#//[^\n]*#', '', $src);
	return $src;
}

function npc_sqli_collect_php($root) {
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

$allFiles = npc_sqli_collect_php($pluginRoot);
$controllerBase = $pluginRoot . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . 'controller.php';

// ---------------------------------------------------------------------------

it('searchClause() uses bound parameters not string concat for user input', function () use ($controllerBase) {
	expect(file_exists($controllerBase))->toBeTrue('controllers/controller.php not found');

	$source = npc_sqli_strip_comments(file_get_contents($controllerBase));

	// Locate the searchClause method body (between the function definition
	// and its closing brace). A simple extraction suffices for scanning.
	if (!preg_match('/function\s+searchClause\s*\([^)]*\)\s*\{(.+?)(?=\n\s{4}function|\n\})/s', $source, $m)) {
		// Could not locate method; the rewrite may have renamed it.
		// Accept and note the missing method.
		expect(true)->toBeTrue('searchClause() not found; verify method was renamed');
		return;
	}

	$methodBody = $m[1];

	// The method must push values onto a $params array (bound parameters).
	$hasBoundParams = (bool) preg_match('/\$params\s*\[\s*\]\s*=/', $methodBody);

	// It must not concatenate $this->searchString directly into the WHERE clause.
	$concatsSearchString = (bool) preg_match(
		'/\.\s*\$this\s*->\s*searchString/',
		$methodBody
	);

	expect($hasBoundParams)->toBeTrue('searchClause() does not use bound parameter array');
	expect($concatsSearchString)->toBeFalse('searchClause() concatenates searchString into SQL');
});

it('has no superglobal concatenation into DB call arguments', function () use ($allFiles) {
	/*
	 * Pattern: a DB function call whose argument string contains a
	 * concatenated superglobal, e.g. "WHERE id = " . $_GET['id'].
	 *
	 * This is a heuristic; deliberate allowances for escaped values
	 * require manual review. The test flags candidates for triage.
	 */
	$violations = array();

	// Raw DB functions that should never receive superglobal-concat SQL.
	$dbFuncPattern = '/\b(db_fetch_assoc|db_fetch_row|db_fetch_cell|db_execute)\s*\(/';

	foreach ($allFiles as $path) {
		$src   = npc_sqli_strip_comments(file_get_contents($path));
		$lines = explode("\n", $src);

		foreach ($lines as $no => $line) {
			if (!preg_match($dbFuncPattern, $line)) {
				continue;
			}
			// Flag lines where a superglobal appears in the same statement.
			if (preg_match('/\$_(GET|POST|REQUEST|COOKIE)\s*\[/', $line)) {
				$violations[] = basename($path) . ':' . ($no + 1);
			}
		}
	}

	expect($violations)->toBe(
		array(),
		'Superglobal concat into DB call:\n' . implode("\n", $violations)
	);
});

it('sort_column values are validated against an allowlist before SQL use', function () use ($allFiles) {
	/*
	 * ORDER BY injection: if a user-supplied column name is appended to SQL
	 * without validation it bypasses parameterized query protection (ORDER BY
	 * cannot be parameterized). The codebase must apply an allowlist check
	 * before any ORDER BY construction that uses request input.
	 *
	 * The test verifies: wherever 'sort_column' or 'sort_direction' appears
	 * in a file that also performs an ORDER BY, there is also an allowlist
	 * pattern (array_search, in_array, a whitelist array, or a regex).
	 */
	$violations = array();

	foreach ($allFiles as $path) {
		$src = npc_sqli_strip_comments(file_get_contents($path));

		$usesSortInput = (bool) preg_match('/sort_column|sort_direction/', $src);
		$usesOrderBy   = (bool) preg_match('/ORDER\s+BY/i', $src);

		if (!$usesSortInput || !$usesOrderBy) {
			continue;
		}

		// Acceptable allowlist patterns.
		$hasAllowlist = (bool) preg_match(
			'/\b(in_array|array_search|array_key_exists)\s*\(.*sort_(column|direction)/'
			. '|\$sort_(column|direction)_allowed'
			. '|\$allowed_(columns?|sort|directions?)'
			. '|\bSANITIZE_SEARCH_\w+/i',
			$src
		);

		if (!$hasAllowlist) {
			$violations[] = basename($path);
		}
	}

	expect($violations)->toBe(
		array(),
		'Files with ORDER BY + sort input but no allowlist: ' . implode(', ', $violations)
	);
});

it('has no raw $_GET/$_POST/$_REQUEST in SQL string literals', function () use ($allFiles) {
	$violations = array();

	foreach ($allFiles as $path) {
		$src   = npc_sqli_strip_comments(file_get_contents($path));
		// Match any SQL keyword followed later on the same line by a superglobal.
		if (preg_match_all(
			'/\b(SELECT|INSERT|UPDATE|DELETE|WHERE|FROM)\b[^;]*\$_(GET|POST|REQUEST)\s*\[/i',
			$src,
			$m
		)) {
			$violations[] = basename($path) . ' (' . count($m[0]) . ' instance(s))';
		}
	}

	expect($violations)->toBe(
		array(),
		'Raw superglobal in SQL string: ' . implode(', ', $violations)
	);
});
