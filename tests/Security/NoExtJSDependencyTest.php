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
 * Verifies that the ExtJS 3.x frontend library has been completely removed.
 *
 * The original NPC plugin bundled ExtJS under js/ext/ and generated
 * JavaScript that called into the Ext.* namespace. ExtJS 3.x is end-of-life,
 * carries known XSS and denial-of-service vulnerabilities, and conflicts with
 * the jQuery-based Cacti UI. The rewrite replaces it entirely.
 */

$pluginRoot = dirname(__DIR__, 2);

function npc_extjs_collect_php($root) {
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

function npc_extjs_strip_comments($src) {
	$src = preg_replace('#/\*.*?\*/#s', '', $src);
	$src = preg_replace('#//[^\n]*#', '', $src);
	return $src;
}

$phpFiles = npc_extjs_collect_php($pluginRoot);

// ---------------------------------------------------------------------------

it('js/ext/ directory does not exist', function () use ($pluginRoot) {
	$extDir = $pluginRoot . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'ext';
	expect(is_dir($extDir))->toBeFalse(
		'js/ext/ directory still present; ExtJS library files were not removed'
	);
});

it('npc-all-min.js does not exist', function () use ($pluginRoot) {
	$bundle = $pluginRoot . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'npc-all-min.js';
	expect(file_exists($bundle))->toBeFalse(
		'js/npc-all-min.js still present; legacy ExtJS bundle was not removed'
	);
});

it('no PHP file references ext-all.js', function () use ($phpFiles) {
	$violations = array();

	foreach ($phpFiles as $rel => $path) {
		$src = npc_extjs_strip_comments(file_get_contents($path));
		if (preg_match('/ext-all\.js/', $src)) {
			$violations[] = $rel;
		}
	}

	expect($violations)->toBe(
		array(),
		'ext-all.js references found in PHP files: ' . implode(', ', $violations)
	);
});

it('no PHP file outputs Ext.* namespace calls', function () use ($phpFiles) {
	/*
	 * Match live PHP output (echo/print) that generates Ext.* JavaScript.
	 * Comments referencing ExtJS for documentation purposes are excluded by
	 * the comment-stripping step.
	 */
	$violations = array();

	foreach ($phpFiles as $rel => $path) {
		$src = npc_extjs_strip_comments(file_get_contents($path));
		// Ext.onReady, Ext.apply, Ext.Panel, etc. in output contexts.
		if (preg_match('/(echo|print)\s[^;]*\bExt\.\w+/i', $src)) {
			$violations[] = $rel;
		}
	}

	expect($violations)->toBe(
		array(),
		'PHP files outputting Ext.* calls: ' . implode(', ', $violations)
	);
});

it('no PHP file loads the legacy npc-all-min.js bundle via script tag', function () use ($phpFiles) {
	$violations = array();

	foreach ($phpFiles as $rel => $path) {
		$src = npc_extjs_strip_comments(file_get_contents($path));
		if (preg_match('/npc-all-min\.js/', $src)) {
			$violations[] = $rel;
		}
	}

	expect($violations)->toBe(
		array(),
		'npc-all-min.js reference found in: ' . implode(', ', $violations)
	);
});

it('portlets-all-min.js does not exist', function () use ($pluginRoot) {
	$bundle = $pluginRoot . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'portlets-all-min.js';
	expect(file_exists($bundle))->toBeFalse(
		'js/portlets-all-min.js still present; legacy ExtJS portlet bundle was not removed'
	);
});
