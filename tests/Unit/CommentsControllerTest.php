<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for the bulk comment deletion in controllers/comments.php,
 * which hands one command per host or service to the Nagios controller.
 */

beforeAll(function () {
	npc_test_stub_cacti_includes();
	npc_test_load(dirname(__DIR__, 2) . '/controllers/comments.php');
});

beforeEach(function () {
	$GLOBALS['__test_db_results'] = array();
	$GLOBALS['__test_config']     = array();
});

afterEach(function () {
	$GLOBALS['__test_config'] = array();
});

/**
 * A named pipe stands in for the Nagios command file, which is also a pipe, so
 * every command written by the controller can be read back in order.
 */
function npc_comments_command_pipe() {
	$pipe = sys_get_temp_dir() . '/npc-cmd-' . uniqid();
	posix_mkfifo($pipe, 0600);

	$reader = fopen($pipe, 'r+');
	stream_set_blocking($reader, false);

	$GLOBALS['__test_config'] = array('npc_nagios_commands' => '1', 'npc_nagios_cmd_path' => $pipe);

	return $reader;
}

function npc_comments_commands($reader) {
	$lines = array_filter(explode("\n", (string) stream_get_contents($reader)));

	return array_values(array_map(function ($line) {
		return preg_replace('/^\\[\\d+\\] /', '', $line);
	}, $lines));
}

it('deletes the comments of every host once', function () {
	$reader = npc_comments_command_pipe();

	$GLOBALS['__test_db_results']['db_fetch_assoc_prepared']['FROM npc_comments'] = array(
		array('host_name' => 'web1', 'comment_data' => 'a'),
		array('host_name' => 'web1', 'comment_data' => 'b'),
		array('host_name' => 'db1', 'comment_data' => 'c'),
	);

	(new NpcCommentsController())->deleteAllHostComments();

	expect(npc_comments_commands($reader))->toBe(array('DEL_ALL_HOST_COMMENTS;web1', 'DEL_ALL_HOST_COMMENTS;db1'));
})->skip(!function_exists('posix_mkfifo'), 'posix extension is not loaded');

it('deletes the comments of every service once', function () {
	$reader = npc_comments_command_pipe();

	$GLOBALS['__test_db_results']['db_fetch_assoc_prepared']['FROM npc_comments'] = array(
		array('host_name' => 'web1', 'service_description' => 'Load', 'comment_data' => 'a'),
		array('host_name' => 'web1', 'service_description' => 'Load', 'comment_data' => 'b'),
		array('host_name' => 'web1', 'service_description' => 'Disk', 'comment_data' => 'c'),
	);

	(new NpcCommentsController())->deleteAllServiceComments();

	expect(npc_comments_commands($reader))->toBe(array('DEL_ALL_SVC_COMMENTS;web1;Load', 'DEL_ALL_SVC_COMMENTS;web1;Disk'));
})->skip(!function_exists('posix_mkfifo'), 'posix extension is not loaded');

it('writes nothing when there are no comments', function () {
	$reader = npc_comments_command_pipe();

	(new NpcCommentsController())->deleteAllHostComments();
	(new NpcCommentsController())->deleteAllServiceComments();

	expect(npc_comments_commands($reader))->toBe(array());
})->skip(!function_exists('posix_mkfifo'), 'posix extension is not loaded');

it('writes nothing when the user lacks the command realm', function () {
	$reader = npc_comments_command_pipe();

	$GLOBALS['__test_denied_realms']                                           = array('npc1.php');
	$GLOBALS['__test_db_results']['db_fetch_assoc_prepared']['FROM npc_comments'] = array(array('host_name' => 'web1', 'service_description' => 'Load', 'comment_data' => 'a'));

	(new NpcCommentsController())->deleteAllHostComments();
	(new NpcCommentsController())->deleteAllServiceComments();

	$GLOBALS['__test_denied_realms'] = array();

	expect(npc_comments_commands($reader))->toBe(array());
})->skip(!function_exists('posix_mkfifo'), 'posix extension is not loaded');
