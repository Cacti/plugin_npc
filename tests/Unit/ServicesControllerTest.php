<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for the service listing query in controllers/services.php.
 * The row counts are checked against MariaDB in
 * tests/Integration/NpcDatabaseTest.php.
 */

beforeAll(function () {
	npc_test_stub_cacti_includes();
	require_once dirname(__DIR__, 2) . '/controllers/controller.php';
	npc_test_load(dirname(__DIR__, 2) . '/controllers/services.php');
});

beforeEach(function () {
	$GLOBALS['__test_db_fetch_log'] = array();
});

it('does not join the hostgroup tables when no hostgroup filter is set', function () {
	(new NpcServicesController())->services();

	foreach ($GLOBALS['__test_db_fetch_log'] as $query) {
		expect($query['sql'])->not->toContain('npc_hostgroup');
	}

	expect(count($GLOBALS['__test_db_fetch_log']))->toBe(2);
});

it('joins the hostgroup tables and binds the alias when filtering by hostgroup', function () {
	$services            = new NpcServicesController();
	$services->hostgroup = 'web servers';

	$services->services();

	foreach ($GLOBALS['__test_db_fetch_log'] as $query) {
		expect($query['sql'])->toContain('LEFT JOIN npc_hostgroups hg ON hgm.hostgroup_id = hg.hostgroup_id');
		expect($query['sql'])->toContain('hg.alias = ?');
		expect($query['params'][1])->toBe('web servers');
	}
});
