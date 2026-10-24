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
	$GLOBALS['__test_sql_save']      = array();
});

it('refuses to create a data input method without the data input realm', function () {
	$GLOBALS['__test_denied_realms'] = array('data_input.php');

	$out = json_decode((new NpcCactiController())->addDataInputMethod(array('host' => 'web1', 'object_id' => '12')), true);

	expect($out['success'])->toBeFalse();
	expect($out['msg'])->toBe('You do not have permission to create data input methods.');
	expect($GLOBALS['__test_db_calls'])->toBe(array());
});

it('reads the stored perfdata for the plain object id and adds one output field per label', function () {
	$GLOBALS['__test_db_results']['db_fetch_assoc_prepared']['FROM npc_hostchecks'] = array(array('perfdata' => 'rta=0.5ms;100;200;0;10;pl=0%'));

	$out = json_decode((new NpcCactiController())->addDataInputMethod(array('host' => 'web1', 'object_id' => '12')), true);

	expect($out['success'])->toBeTrue();

	$save = $GLOBALS['__test_sql_save'][0];
	expect($save['table'])->toBe('data_input');
	expect($save['row']['name'])->toBe('NPC - Perfdata - web1');
	expect($save['row']['input_string'])->toBe("php <path_cacti>/plugins/npc/perfdata.php --type=host --id='12' 2> /dev/null");

	expect($GLOBALS['__test_db_fetch_log'][0]['params'])->toBe(array('12'));

	$fields = array_values(array_filter($GLOBALS['__test_db_calls'], function ($call) {
		return strpos($call['sql'], 'INSERT INTO data_input_fields') !== false;
	}));

	expect(array_column(array_column($fields, 'params'), 2))->toBe(array('rta', 'pl'));
});

it('creates the data input method without fields when no check has been stored', function () {
	$out = json_decode((new NpcCactiController())->addDataInputMethod(array('host' => 'web1', 'service' => 'Load', 'object_id' => '31')), true);

	expect($out['success'])->toBeTrue();
	expect($GLOBALS['__test_sql_save'][0]['row']['name'])->toBe('NPC - Perfdata - web1: Load');
	expect($GLOBALS['__test_sql_save'][0]['row']['input_string'])->toContain('--type=service --id=');
	expect($GLOBALS['__test_db_calls'])->toBe(array());
});
