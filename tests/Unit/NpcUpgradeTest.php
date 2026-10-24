<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for npc_config_settings()'s version-drift upgrade path in
 * setup.php, including the upgrade-time manifest prune
 * (npc_prune_files()).
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	$GLOBALS['__test_db_calls']   = array();
	$GLOBALS['__test_db_results'] = array();
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

it('adds the importance column to the contacts table on upgrade', function () {
	npc_upgrade_tables();

	$statements = array_column($GLOBALS['__test_db_calls'], 'sql');
	$alters     = array_values(array_filter($statements, function ($sql) {
		return strpos($sql, 'minimum_importance') !== false;
	}));

	expect($alters)->toHaveCount(1);
	expect($alters[0])->toContain('ALTER TABLE `npc_contacts` ADD COLUMN `minimum_importance`');
	expect(implode("\n", $statements))->not->toContain('npc_contracts');
});

it('creates settings for new users and removes the settings of deleted users', function () {
	$_SESSION['sess_user_id']                                         = 1;
	$GLOBALS['npc_default_settings']                                  = array('date_format' => 's:Y-m-d');
	$GLOBALS['__test_db_results']['db_fetch_cell_prepared']['status'] = '1';
	$GLOBALS['__test_db_results']['db_fetch_assoc']['FROM user_auth'] = array(array('id' => 5));
	$GLOBALS['__test_db_results']['db_fetch_assoc']['FROM npc_settings'] = array(array('user_id' => 9));

	npc_config_settings();

	$calls = $GLOBALS['__test_db_calls'];

	$insert = array_values(array_filter($calls, function ($call) {
		return $call['fn'] === 'db_execute' && strpos($call['sql'], 'INSERT INTO npc_settings VALUES(5,') === 0;
	}));
	$delete = array_values(array_filter($calls, function ($call) {
		return $call['fn'] === 'db_execute_prepared' && strpos($call['sql'], 'DELETE FROM npc_settings') === 0;
	}));

	expect($insert)->toHaveCount(1);
	expect($delete)->toHaveCount(1);
	expect($delete[0]['params'])->toBe(array(9));
});

it('keeps the settings of users that still exist', function () {
	$_SESSION['sess_user_id']                                            = 1;
	$GLOBALS['npc_default_settings']                                     = array();
	$GLOBALS['__test_db_results']['db_fetch_cell_prepared']['status']    = '1';
	$GLOBALS['__test_db_results']['db_fetch_cell_prepared']['FROM user_auth'] = 9;
	$GLOBALS['__test_db_results']['db_fetch_assoc']['FROM npc_settings'] = array(array('user_id' => 9));

	npc_config_settings();

	$deletes = array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return strpos($call['sql'], 'DELETE FROM npc_settings') === 0;
	});

	expect($deletes)->toBe(array());
});
