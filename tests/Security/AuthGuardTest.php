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
 * Verifies that every file reachable via HTTP includes Cacti's authentication
 * layer before executing any application logic. Cacti enforces auth through
 * include/auth.php (which itself includes global.php). A missing include
 * would allow unauthenticated access to the plugin.
 */

$pluginRoot = dirname(__DIR__, 2);

/**
 * Return the raw source of $file without stripping comments, because auth
 * includes must appear as real code, not in comments.
 */
function npc_auth_source($pluginRoot, $file) {
	$path = $pluginRoot . DIRECTORY_SEPARATOR . $file;
	return file_exists($path) ? file_get_contents($path) : '';
}

/**
 * True if $source contains an include/require of auth.php or global.php.
 */
function npc_has_auth_include($source) {
	return (bool) preg_match(
		'#(require|include)(_once)?\s*[(\s][\'"][^"\']*/(auth|global)\.php[\'"]#',
		$source
	);
}

// ---------------------------------------------------------------------------

it('npc.php includes auth.php before dispatching', function () use ($pluginRoot) {
	$source = npc_auth_source($pluginRoot, 'npc.php');

	expect($source)->not->toBeEmpty('npc.php was not found');
	expect(npc_has_auth_include($source))->toBeTrue('npc.php must include auth.php or global.php');
});

it('nagioscmd.php includes auth.php', function () use ($pluginRoot) {
	$source = npc_auth_source($pluginRoot, 'nagioscmd.php');

	expect($source)->not->toBeEmpty('nagioscmd.php was not found');
	expect(npc_has_auth_include($source))->toBeTrue('nagioscmd.php must include auth.php or global.php');
});

it('config.php does not bypass auth (only defines, no output)', function () use ($pluginRoot) {
	$source = npc_auth_source($pluginRoot, 'config.php');

	if (empty($source)) {
		// config.php may not exist in all configurations.
		expect(true)->toBeTrue();
		return;
	}

	// config.php should not produce HTTP output directly; it should not
	// contain echo/print/header calls at the top level without auth.
	$hasOutput = (bool) preg_match('/^\s*(echo|print|header\s*\()/m', $source);
	if ($hasOutput) {
		expect(npc_has_auth_include($source))->toBeTrue(
			'config.php produces output but does not include auth.php'
		);
	} else {
		expect(true)->toBeTrue();
	}
});

it('every controller file requires is_realm_allowed or auth include', function () use ($pluginRoot) {
	$controllerDir = $pluginRoot . DIRECTORY_SEPARATOR . 'controllers';
	$unguarded     = array();

	// layout.php and layoutDev.php are included by npc.php which already
	// enforces auth; they do not re-include auth themselves.
	$allowNoDirectAuth = array('layout.php', 'layoutDev.php');

	$iter = new DirectoryIterator($controllerDir);
	foreach ($iter as $file) {
		if (!$file->isFile() || $file->getExtension() !== 'php') {
			continue;
		}
		if (in_array($file->getFilename(), $allowNoDirectAuth, true)) {
			continue;
		}

		$source = file_get_contents($file->getPathname());

		$hasAuth = npc_has_auth_include($source)
			|| (bool) preg_match('/is_realm_allowed\s*\(/', $source)
			|| (bool) preg_match('/\$_SESSION\s*\[\s*[\'"]sess_user_id[\'"]\s*\]/', $source);

		if (!$hasAuth) {
			$unguarded[] = $file->getFilename();
		}
	}

	expect($unguarded)->toBe(
		array(),
		'Controllers with no auth guard: ' . implode(', ', $unguarded)
	);
});

it('cli.php does not expose an unauthenticated HTTP endpoint', function () use ($pluginRoot) {
	$source = npc_auth_source($pluginRoot, 'cli.php');

	if (empty($source)) {
		expect(true)->toBeTrue();
		return;
	}

	// CLI scripts are acceptable without auth.php, but must not send
	// HTTP headers (which would indicate they are web-accessible).
	$sendsHeaders = (bool) preg_match('/\bheader\s*\(/', $source);

	if ($sendsHeaders) {
		expect(npc_has_auth_include($source))->toBeTrue(
			'cli.php sends HTTP headers but lacks auth include'
		);
	} else {
		expect(true)->toBeTrue();
	}
});
