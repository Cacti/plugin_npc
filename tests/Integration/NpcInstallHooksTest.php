<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Integration coverage for plugin_npc_install(): verifies every hook and
 * both realms the plugin depends on at runtime are actually registered,
 * together with the tables it needs, in a single end-to-end pass.
 *
 * npc_setup_tables() include_once()s Cacti core's database.php via
 * $config['library_path'], so that is pointed at a throwaway empty stub
 * file for the duration of this test.
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';

	$stubLibraryPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'npc-test-lib-stub';

	if (!is_dir($stubLibraryPath)) {
		mkdir($stubLibraryPath, 0777, true);
	}

	file_put_contents($stubLibraryPath . '/database.php', "<?php\n");

	$GLOBALS['config']['library_path'] = $stubLibraryPath;
});

beforeEach(function () {
	$GLOBALS['__test_registered_hooks']  = array();
	$GLOBALS['__test_registered_realms'] = array();
	$GLOBALS['__test_db_calls']          = array();
});

it('registers every hook npc depends on, both realms, and provisions its tables', function () {
	plugin_npc_install();

	$hooks = array();
	foreach ($GLOBALS['__test_registered_hooks'] as $registered) {
		$hooks[$registered['hook']] = $registered;
	}

	foreach (array('config_arrays', 'top_header_tabs', 'top_graph_header_tabs', 'draw_navigation_text', 'config_form', 'api_device_save', 'config_settings', 'page_head') as $expected) {
		expect($hooks)->toHaveKey($expected);
		expect($hooks[$expected]['name'])->toBe('npc');
	}

	$realms = array_column($GLOBALS['__test_registered_realms'], 'file');

	expect($realms)->toContain('npc.php');
	expect($realms)->toContain('npc1.php');

	expect($GLOBALS['__test_db_calls'])->not->toBeEmpty();
});
