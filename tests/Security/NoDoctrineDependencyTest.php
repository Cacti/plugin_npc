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
 * Verifies that the Doctrine ORM has been completely removed.
 *
 * The original NPC plugin shipped Doctrine 1.x as a bundled dependency under
 * lib/Doctrine/. Doctrine is unmaintained for PHP 7+ and carries known
 * security issues. The rewrite replaces all Doctrine queries with Cacti's
 * prepared-statement helpers.
 *
 * These tests fail if any Doctrine artefact re-appears in the codebase.
 */

$pluginRoot = dirname(__DIR__, 2);

function npc_doctrine_collect_php($root) {
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
		$files[$rel] = $file->getPathname();
	}
	ksort($files);
	return $files;
}

function npc_doctrine_strip_comments($src) {
	$src = preg_replace('#/\*.*?\*/#s', '', $src);
	$src = preg_replace('#//[^\n]*#', '', $src);
	return $src;
}

$phpFiles = npc_doctrine_collect_php($pluginRoot);

// ---------------------------------------------------------------------------

it('lib/Doctrine/ directory does not exist', function () use ($pluginRoot) {
	$doctrineDir = $pluginRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Doctrine';
	expect(is_dir($doctrineDir))->toBeFalse(
		'lib/Doctrine/ directory still present; Doctrine was not fully removed'
	);
});

it('models/ directory does not exist', function () use ($pluginRoot) {
	$modelsDir = $pluginRoot . DIRECTORY_SEPARATOR . 'models';
	expect(is_dir($modelsDir))->toBeFalse(
		'models/ directory still present; Doctrine model files were not removed'
	);
});

it('no PHP file includes or requires Doctrine files', function () use ($phpFiles) {
	$violations = array();

	foreach ($phpFiles as $rel => $path) {
		$src = npc_doctrine_strip_comments(file_get_contents($path));
		if (preg_match('#(require|include)(_once)?\s*[(\s][\'"][^"\']*Doctrine[^"\']*\.php[\'"]#', $src)) {
			$violations[] = $rel;
		}
	}

	expect($violations)->toBe(
		array(),
		'Files still including Doctrine: ' . implode(', ', $violations)
	);
});

it('no PHP file references Doctrine_Query', function () use ($phpFiles) {
	$violations = array();

	foreach ($phpFiles as $rel => $path) {
		$src = npc_doctrine_strip_comments(file_get_contents($path));
		if (preg_match('/\bDoctrine_Query\b/', $src)) {
			$violations[] = $rel;
		}
	}

	expect($violations)->toBe(
		array(),
		'Doctrine_Query references in: ' . implode(', ', $violations)
	);
});

it('no PHP file references Doctrine_Pager', function () use ($phpFiles) {
	$violations = array();

	foreach ($phpFiles as $rel => $path) {
		$src = npc_doctrine_strip_comments(file_get_contents($path));
		if (preg_match('/\bDoctrine_Pager\b/', $src)) {
			$violations[] = $rel;
		}
	}

	expect($violations)->toBe(
		array(),
		'Doctrine_Pager references in: ' . implode(', ', $violations)
	);
});

it('no PHP file references Doctrine:: static calls', function () use ($phpFiles) {
	$violations = array();

	foreach ($phpFiles as $rel => $path) {
		$src = npc_doctrine_strip_comments(file_get_contents($path));
		if (preg_match('/\bDoctrine\s*::/', $src)) {
			$violations[] = $rel;
		}
	}

	expect($violations)->toBe(
		array(),
		'Doctrine:: static call references in: ' . implode(', ', $violations)
	);
});

it('lib/Doctrine.php loader does not exist', function () use ($pluginRoot) {
	$doctrineLoader = $pluginRoot . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Doctrine.php';
	expect(file_exists($doctrineLoader))->toBeFalse(
		'lib/Doctrine.php still present; Doctrine autoloader was not removed'
	);
});
