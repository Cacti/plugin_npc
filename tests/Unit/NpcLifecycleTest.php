<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for the plugin lifecycle functions in setup.php:
 * plugin_npc_version(), plugin_npc_check_config(), and
 * plugin_npc_uninstall().
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	$GLOBALS['__test_db_calls']      = array();
	$GLOBALS['__test_removed_realms'] = array();
});

it('parses the plugin INFO file into an info array', function () {
	$info = plugin_npc_version();

	expect($info)->toBeArray();
	expect($info)->toHaveKey('name');
	expect($info)->toHaveKey('version');
	expect($info['name'])->toBe('npc');
});

it('reports the config as always valid', function () {
	expect(plugin_npc_check_config())->toBeTrue();
});

it('drops every table it owns and removes its realms on uninstall', function () {
	plugin_npc_uninstall();

	$drops = array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return $call['fn'] === 'db_execute' && stripos($call['sql'], 'DROP TABLE') !== false;
	});

	expect(count($drops))->toBeGreaterThan(50);
	expect($GLOBALS['__test_removed_realms'])->toBe(array('npc'));
});
