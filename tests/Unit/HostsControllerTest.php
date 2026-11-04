<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for controllers/hosts.php. The query semantics are checked
 * against MariaDB in tests/Integration/NpcDatabaseTest.php.
 */

beforeAll(function () {
	npc_test_stub_cacti_includes();
	require_once dirname(__DIR__, 2) . '/controllers/controller.php';
	npc_test_load(dirname(__DIR__, 2) . '/controllers/hosts.php');
});

beforeEach(function () {
	$GLOBALS['__test_db_results']   = array();
	$GLOBALS['__test_db_fetch_log'] = array();
});

it('asks for only the newest stored check of a host', function () {
	$GLOBALS['__test_db_results']['db_fetch_assoc_prepared']['FROM npc_hostchecks'] = array(array('perfdata' => 'rta=0.5ms'));

	$rows = (new NpcHostsController())->getPerfData(12);

	expect($rows)->toBe(array(array('perfdata' => 'rta=0.5ms')));

	$query = $GLOBALS['__test_db_fetch_log'][0];
	expect(preg_replace('/\s+/', ' ', $query['sql']))->toContain('WHERE host_object_id = ? ORDER BY hostcheck_id DESC LIMIT 1');
	expect($query['params'])->toBe(array(12));
});

it('returns no rows when a host has no stored check', function () {
	expect((new NpcHostsController())->getPerfData(12))->toBe(array());
});
