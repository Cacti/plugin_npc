<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for controllers/cacti.php, the bridge between NPC and Cacti
 * devices and data input methods.
 */

beforeAll(function () {
	npc_test_stub_cacti_includes();
	npc_test_load(dirname(__DIR__, 2) . '/controllers/cacti.php');
});

beforeEach(function () {
	$GLOBALS['__test_db_calls']      = array();
	$GLOBALS['__test_db_results']    = array();
	$GLOBALS['__test_db_fetch_log']  = array();
	$GLOBALS['__test_denied_realms'] = array();
});

it('refuses to create a data input method without the data input realm', function () {
	$GLOBALS['__test_denied_realms'] = array('data_input.php');

	$out = json_decode((new NpcCactiController())->addDataInputMethod(array('host' => 'web1', 'object_id' => '12')), true);

	expect($out['success'])->toBeFalse();
	expect($out['msg'])->toBe('You do not have permission to create data input methods.');
	expect($GLOBALS['__test_db_calls'])->toBe(array());
});
