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

/**
 * True if $source is a front-controller dispatch target: a pure class-
 * definition module that performs no output or application logic at file
 * scope. NPC controllers are loaded only by npc.php, which enforces auth and
 * validates the module/action allowlists before requiring the controller, so
 * requesting one directly cannot leak data. Loading sibling class files via
 * require/include is permitted; any top-level output or DB sink is not.
 */
function npc_is_pure_class_module($source) {
	if (!preg_match('/\bclass\s+\w+/', $source)) {
		return false;
	}

	$tokens = token_get_all($source);
	$depth  = 0;
	$sinks  = array(
		'printf', 'vprintf', 'print_r', 'var_dump', 'header',
		'readfile', 'fpassthru', 'fwrite', 'fputs',
		'db_execute', 'db_fetch_assoc', 'db_fetch_row', 'db_fetch_cell',
	);

	foreach ($tokens as $token) {
		if (!is_array($token)) {
			if ($token === '{') {
				$depth++;
			} elseif ($token === '}') {
				$depth--;
			}
			continue;
		}

		// String-interpolation braces open with these token ids and close with
		// a plain '}'; count them so the brace depth stays balanced.
		if ($token[0] === T_CURLY_OPEN || $token[0] === T_DOLLAR_OPEN_CURLY_BRACES) {
			$depth++;
			continue;
		}

		if ($depth > 0) {
			// Inside a class or function body: legitimate application logic.
			continue;
		}

		// At file scope, no output or execution sinks are permitted.
		if ($token[0] === T_ECHO || $token[0] === T_PRINT || $token[0] === T_INLINE_HTML
			|| $token[0] === T_OPEN_TAG_WITH_ECHO || $token[0] === T_EXIT) {
			return false;
		}

		if ($token[0] === T_STRING && in_array(strtolower($token[1]), $sinks, true)) {
			return false;
		}
	}

	return true;
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

it('every controller file is auth-guarded or a side-effect-free dispatch target', function () use ($pluginRoot) {
	$controllerDir = $pluginRoot . DIRECTORY_SEPARATOR . 'controllers';
	$unguarded     = array();

	// layout.php is included by npc.php which already enforces auth; it
	// does not re-include auth itself.
	$allowNoDirectAuth = array('layout.php');

	$iter = new DirectoryIterator($controllerDir);
	foreach ($iter as $file) {
		if (!$file->isFile() || $file->getExtension() !== 'php') {
			continue;
		}
		if (in_array($file->getFilename(), $allowNoDirectAuth, true)) {
			continue;
		}

		$source = file_get_contents($file->getPathname());

		// A controller is safe if it enforces auth directly, or is a pure
		// class-definition module dispatched exclusively by npc.php (which
		// already enforces auth and validates the module/action allowlists
		// before loading the controller class).
		$guarded = npc_has_auth_include($source)
			|| (bool) preg_match('/is_realm_allowed\s*\(/', $source)
			|| (bool) preg_match('/\$_SESSION\s*\[\s*[\'"]sess_user_id[\'"]\s*\]/', $source)
			|| npc_is_pure_class_module($source);

		if (!$guarded) {
			$unguarded[] = $file->getFilename();
		}
	}

	expect($unguarded)->toBe(
		array(),
		'Controllers with no auth guard and top-level side effects: ' . implode(', ', $unguarded)
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
