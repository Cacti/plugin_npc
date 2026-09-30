<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for npc_config_settings()'s version-drift upgrade path in
 * setup.php, including the upgrade-time manifest prune
 * (plugin_npc_prune_files()).
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	$GLOBALS['__test_db_calls'] = array();
	unset($GLOBALS['__test_db_fetch_cell']);
});

afterEach(function () {
	unset($GLOBALS['__test_db_fetch_cell'], $_SESSION['sess_user_id']);
});

it('runs the schema upgrade and prune, then updates plugin_config on a version drift', function () {
	$_SESSION['sess_user_id']        = 1;
	$GLOBALS['__test_db_fetch_cell'] = '1.0.0'; // stored (old) version: non-empty and != current

	// Sandbox base_path so plugin_npc_version() reads a temp INFO and the
	// upgrade-time prune runs against a temp tree, never the real checkout.
	$restore = $GLOBALS['config']['base_path'];
	$base    = sys_get_temp_dir() . '/npc-upg-' . uniqid();
	mkdir($base . '/plugins/npc', 0777, true);
	file_put_contents($base . '/plugins/npc/INFO', "[info]\nversion = 9.9.9\nname = npc\nauthor = x\n");
	$GLOBALS['config']['base_path'] = $base;

	try {
		npc_config_settings();
	} finally {
		$GLOBALS['config']['base_path'] = $restore;
	}

	$updates = array_values(array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return $call['fn'] === 'db_execute' && stripos($call['sql'], 'UPDATE plugin_config') !== false;
	}));

	expect($updates)->not->toBeEmpty();
});
