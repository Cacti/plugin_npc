<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * End-to-end coverage of npc.php, the front controller every NPC request goes
 * through. Each test runs the real npc.php and the real controllers in a PHP
 * subprocess; tests/E2E/support/front-controller-auth.php replaces only the
 * session, the database and the page chrome, and Cacti's own request helpers
 * do the validation and sanitizing.
 */

require_once __DIR__ . '/support/harness.php';

it('serves the frame for a known tab', function () {
	$run = npc_e2e_run(array('get' => array('module' => 'layout', 'action' => 'drawFrame', 'tab' => 'hosts')));

	expect($run['stderr'])->toBe('');
	expect($run['stdout'])->toContain("currentTab:       'hosts'");
});

it('does not echo an unknown tab value into the frame script', function () {
	$run = npc_e2e_run(array('get' => array('module' => 'layout', 'action' => 'drawFrame', 'tab' => 'x</script><b>breakout</b>')));

	expect($run['stdout'])->toContain("currentTab:       'dashboard'");
	expect($run['stdout'])->not->toContain('breakout');
});

it('refuses state-changing actions sent as GET requests', function (string $module, string $action) {
	$run = npc_e2e_run(array('get' => array('module' => $module, 'action' => $action, 'p_command' => 'ENABLE_NOTIFICATIONS', 'p_object_id' => '12', 'p_host' => 'web1')));

	expect(json_decode($run['stdout'], true))->toBe(array('success' => false, 'msg' => 'This action requires a POST request.'));
	expect(npc_e2e_calls($run, 'db_execute_prepared'))->toBe(array());
	expect(npc_e2e_calls($run, 'sql_save'))->toBe(array());
})->with(array(
	'nagios command'          => array('nagios', 'command'),
	'delete host comments'    => array('comments', 'deleteAllHostComments'),
	'delete service comments' => array('comments', 'deleteAllServiceComments'),
	'data input method'       => array('cacti', 'addDataInputMethod'),
	'host import'             => array('sync', 'import'),
	'host graph mapping'      => array('hosts', 'setMappedGraph'),
	'service graph mapping'   => array('services', 'setMappedGraph'),
	'settings save'           => array('settings', 'save'),
));

it('accepts the same actions sent as POST', function () {
	$run = npc_e2e_run(array(
		'method' => 'POST',
		'get'    => array('module' => 'cacti', 'action' => 'addDataInputMethod'),
		'post'   => array('p_object_id' => '12', 'p_host' => 'web1'),
	));

	expect(json_decode($run['stdout'], true))->toBe(array('success' => true));
	expect(npc_e2e_calls($run, 'sql_save'))->not->toBe(array());
});

it('writes a Nagios command only for a POST request', function () {
	$file = sys_get_temp_dir() . '/npc-e2e-cmd-' . uniqid();
	file_put_contents($file, '');

	$options = array('npc_config_type' => '1', 'npc_nagios_commands' => '1', 'npc_nagios_cmd_path' => $file);
	$query   = array('module' => 'nagios', 'action' => 'command', 'p_command' => 'ENABLE_NOTIFICATIONS');

	npc_e2e_run(array('get' => $query, 'options' => $options));
	expect(file_get_contents($file))->toBe('');

	$run = npc_e2e_run(array('method' => 'POST', 'get' => $query, 'options' => $options));
	expect(json_decode($run['stdout'], true))->toBe(array('success' => true));
	expect(file_get_contents($file))->toMatch('/^\[\d+\] ENABLE_NOTIFICATIONS\n$/');
});

it('does not let a request overwrite controller properties', function () {
	$run = npc_e2e_run(array('get' => array('module' => 'hosts', 'action' => 'summary', 'p_config_type' => '99')));

	$summary = array_values(array_filter(npc_e2e_calls($run, 'db_fetch_assoc_prepared'), function ($call) {
		return strpos($call['sql'], 'h.config_type = ?') !== false;
	}));

	expect($summary[0]['params'])->toBe(array('1'));
});

it('keeps the config parameter out of the request values', function () {
	$run = npc_e2e_run(array('get' => array('module' => 'layout', 'action' => 'drawFrame', 'p_config' => 'x')));

	expect($run['stderr'])->toBe('');
	expect($run['stdout'])->toContain("currentTab:       'dashboard'");
});

it('bounds the page size and start of a listing', function (array $query, array $expected) {
	$run = npc_e2e_run(array('get' => array('module' => 'logentries', 'action' => 'getLogs') + $query));

	$select = array_values(array_filter(npc_e2e_calls($run, 'db_fetch_assoc_prepared'), function ($call) {
		return strpos($call['sql'], 'LIMIT') !== false;
	}));

	preg_match('/LIMIT (\d+), (\d+)/', $select[0]['sql'], $limit);

	expect(array((int) $limit[1], (int) $limit[2]))->toBe($expected);
})->with(array(
	'default page'     => array(array(), array(0, 25)),
	'large limit'      => array(array('limit' => '100000'), array(0, 1000)),
	'negative limit'   => array(array('limit' => '-5'), array(0, 1)),
	'negative start'   => array(array('start' => '-50', 'limit' => '10'), array(0, 10)),
	'second page'      => array(array('start' => '50', 'limit' => '50'), array(50, 50)),
	'limit as p_limit' => array(array('p_limit' => '100000'), array(0, 25)),
));

it('applies a JSON search field list to the query', function () {
	$run = npc_e2e_run(array('get' => array(
		'module'         => 'logentries',
		'action'         => 'getLogs',
		'p_searchString' => 'restart',
		'p_searchFields' => '["logentry_data","not_a_field"]',
	)));

	$count = npc_e2e_calls($run, 'db_fetch_cell_prepared');

	expect($count[0]['sql'])->toContain('l.logentry_data LIKE ?');
	expect($count[0]['sql'])->not->toContain('not_a_field');
	expect($count[0]['params'])->toBe(array('%restart%'));
});

it('reads the hostgroup selection of the import as JSON', function () {
	$run = npc_e2e_run(array(
		'get'  => array(
			'module' => 'sync',
			'action' => 'getHosts',
			'p_data' => '{"g1":{"alias":"web servers","create_graphs":1,"template":2}}',
		),
		'rows' => array('db_fetch_assoc_prepared' => array('INNER JOIN npc_hostgroup_members' => array(
			array('host_object_id' => 10, 'display_name' => 'web1', 'address' => '192.0.2.10'),
		))),
	));

	expect(json_decode($run['stdout'], true))->toBe(array(
		array('host_object_id' => 10, 'display_name' => 'web1', 'address' => '192.0.2.10', 'create_graphs' => 1, 'template' => 2),
	));

	$lookup = array_values(array_filter(npc_e2e_calls($run, 'db_fetch_assoc_prepared'), function ($call) {
		return strpos($call['sql'], 'hg.alias = ?') !== false;
	}));

	expect($lookup[0]['params'])->toBe(array('web servers'));
});

it('rejects actions that were removed from the dispatcher', function (string $module, string $action) {
	$run = npc_e2e_run(array('get' => array('module' => $module, 'action' => $action)));

	expect($run['stdout'])->toBe('');
	expect(implode("\n", array_column(npc_e2e_calls($run, 'cacti_log'), 'message')))->toContain('rejected unknown action: ' . $module . '/' . $action);
})->with(array(
	'setting lookup'       => array('cacti', 'getSetting'),
	'mapped check'         => array('cacti', 'isMapped'),
	'host mapping'         => array('cacti', 'mapHost'),
	'user settings lookup' => array('settings', 'getSettings'),
	'address lookup'       => array('sync', 'checkHostExists'),
));

it('does nothing when its stand-in auth file is requested without the test environment', function () {
	$output = shell_exec('NPC_E2E= ' . escapeshellarg(PHP_BINARY) . ' -r \'putenv("NPC_E2E"); require $argv[1];\' ' . escapeshellarg(__DIR__ . '/support/front-controller-auth.php') . ' 2>&1');

	expect((string) $output)->toBe('');
});

it('treats an array given for a JSON request value as empty', function (string $name) {
	$run = npc_e2e_run(array('get' => array('module' => 'logentries', 'action' => 'getLogs', 'p_searchString' => 'x', $name => array('a', 'b'))));

	$count = npc_e2e_calls($run, 'db_fetch_cell_prepared');

	expect($run['stderr'])->toBe('');
	expect($count[0]['sql'])->not->toContain('LIKE');
})->with(array('p_searchFields', 'p_data'));

it('reports a host import selection that is not a JSON object', function () {
	$run = npc_e2e_run(array('get' => array('module' => 'sync', 'action' => 'getHosts', 'p_data' => array('x'))));

	expect($run['stderr'])->toBe('');
	expect(json_decode($run['stdout'], true)['success'])->toBeFalse();
});

it('refuses the same state-changing action for other methods', function (string $method) {
	$run = npc_e2e_run(array('method' => $method, 'get' => array('module' => 'nagios', 'action' => 'command', 'p_command' => 'ENABLE_NOTIFICATIONS')));

	expect(json_decode($run['stdout'], true)['success'])->toBeFalse();
	expect(npc_e2e_calls($run, 'db_execute_prepared'))->toBe(array());
})->with(array('PUT', 'DELETE', 'HEAD', 'PATCH'));

it('does nothing when a test helper is run without the test environment', function (string $file) {
	$dir = sys_get_temp_dir() . '/npc-inert-' . uniqid();
	mkdir($dir, 0700);

	$command = 'cd ' . escapeshellarg($dir) . ' && env -u NPC_E2E -u NPC_COVERAGE_DIR ' . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/' . $file) . ' 2>&1';

	expect((string) shell_exec($command))->toBe('');
	expect(glob($dir . '/*'))->toBe(array());
})->with(array(
	'stand-in auth file' => array('E2E/support/front-controller-auth.php'),
	'coverage recorder'  => array('E2E/support/coverage-prepend.php'),
	'subprocess runner'  => array('E2E/support/harness.php'),
	'web server runner'  => array('E2E/support/server.php'),
	'database helper'    => array('Integration/support/database.php'),
));
