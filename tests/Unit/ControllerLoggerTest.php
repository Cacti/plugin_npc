<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for Controller::logger(), which logs a message and hands the
 * client a generic error for the error level.
 */

beforeAll(function () {
	require_once dirname(__DIR__, 2) . '/controllers/controller.php';
});

beforeEach(function () {
	$GLOBALS['__test_config']   = array('npc_log_level' => '4');
	$GLOBALS['__test_cacti_log'] = array();
});

afterEach(function () {
	$GLOBALS['__test_config'] = array();
});

it('names the failing class and method in the error returned to the client', function () {
	$out = json_decode((new Controller())->logger('error', 'NpcSyncController', 'import', 'boom'), true);

	expect($out)->toBe(array('success' => false, 'msg' => 'An error occurred in NpcSyncController::import. See error logs for detail.'));
	expect($GLOBALS['__test_cacti_log'])->toBe(array('ERROR [NpcSyncController] (import) - boom'));
});

it('returns nothing to the client below the error level', function () {
	expect((new Controller())->logger('info', 'NpcSyncController', 'import', 'started'))->toBeNull();
});
