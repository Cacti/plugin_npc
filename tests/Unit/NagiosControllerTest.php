<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for the command entry point of controllers/nagios.php.
 */

beforeAll(function () {
	npc_test_stub_cacti_includes();
	require_once dirname(__DIR__, 2) . '/controllers/controller.php';
	npc_test_load(dirname(__DIR__, 2) . '/controllers/nagios.php');
});

beforeEach(function () {
	$GLOBALS['__test_config']        = array();
	$GLOBALS['__test_denied_realms'] = array();
});

afterEach(function () {
	$GLOBALS['__test_config']        = array();
	$GLOBALS['__test_denied_realms'] = array();
});

it('checks the command realm before it looks at the command file', function () {
	$GLOBALS['__test_denied_realms'] = array('npc1.php');
	$GLOBALS['__test_config']        = array('npc_nagios_commands' => '1', 'npc_nagios_cmd_path' => '/nonexistent/nagios.cmd');

	$out = json_decode((new NpcNagiosController())->command(array('command' => 'ENABLE_NOTIFICATIONS')), true);

	expect($out)->toBe(array('success' => false, 'msg' => 'You do not have permission to execute this command.'));
});

it('reports a disabled feature to a user who holds the realm', function () {
	$out = json_decode((new NpcNagiosController())->command(array('command' => 'ENABLE_NOTIFICATIONS')), true);

	expect($out['success'])->toBeFalse();
	expect($out['msg'])->toContain('Remote Commands must be enabled');
});

it('writes a permitted command to the command file', function () {
	$file = sys_get_temp_dir() . '/npc-cmd-' . uniqid();
	file_put_contents($file, '');

	$GLOBALS['__test_config'] = array('npc_nagios_commands' => '1', 'npc_nagios_cmd_path' => $file);

	$out = json_decode((new NpcNagiosController())->command(array('command' => 'ENABLE_NOTIFICATIONS')), true);

	expect($out)->toBe(array('success' => true));
	expect(file_get_contents($file))->toMatch('/^\\[\\d+\\] ENABLE_NOTIFICATIONS\\n$/');
});
