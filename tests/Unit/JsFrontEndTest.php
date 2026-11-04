<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Runs the front end tests in tests/js with Node's built-in test runner. They
 * load js/npc.js against a small stand-in for jQuery and the DOM.
 */

it('passes the js/npc.js tests', function () {
	$output = array();
	$status = 0;

	exec('cd ' . escapeshellarg(dirname(__DIR__, 2)) . ' && node --test tests/js/npc.test.js 2>&1', $output, $status);

	$report = implode("\n", $output);

	expect($report)->toContain('# fail 0');
	expect($report)->not->toContain('# tests 0');
	expect($status)->toBe(0);
})->skip(trim((string) shell_exec('command -v node')) === '', 'node is not installed');
