<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Real requests against the real Cacti the plugin is installed in: PHP's
 * built-in web server, Cacti's own login, sessions, realm checks and CSRF
 * token check, and the Cacti database.
 *
 * The tests create four users with different realms, a handful of rows in the
 * NPC tables and two settings, and remove them again afterwards. The Nagios
 * command file is a temporary file. cacti.log is cut back to its length from
 * before the run, so a warning provoked on purpose does not fail the
 * workflow's log check.
 *
 * Skipped, with the reason, when there is no database, the plugin is not
 * enabled, or the Cacti log cannot be written (the workflow's install step
 * leaves it owned by root). Under CI a missing database is an error.
 */

require_once __DIR__ . '/support/harness.php';
require_once __DIR__ . '/support/server.php';
require_once dirname(__DIR__) . '/Integration/support/database.php';

beforeAll(function () {
	$GLOBALS['npc_rs'] = array('skip' => null);
	$state             = &$GLOBALS['npc_rs'];
	$state['db']       = npc_db_connect();

	if ($state['db'] === null) {
		$state['skip'] = 'No test database is reachable.';

		if (getenv('CI') === 'true') {
			throw new RuntimeException($state['skip']);
		}

		return;
	}

	$db = $state['db'];

	if ($db->query("SHOW TABLES LIKE 'user_auth'")->rowCount() === 0 || $db->query("SELECT 1 FROM plugin_config WHERE directory = 'npc' AND status = 1")->rowCount() === 0) {
		$state['skip'] = 'Cacti is not installed with the npc plugin enabled in the test database.';

		return;
	}

	$log = $GLOBALS['config']['base_path'] . '/log/cacti.log';

	if (!extension_loaded('curl') || (file_exists($log) && !is_writable($log)) || (!file_exists($log) && !is_writable(dirname($log)))) {
		$state['skip'] = 'curl is missing or the Cacti log is not writable.';

		return;
	}

	$state['log']      = $log;
	$state['log_size'] = file_exists($log) ? filesize($log) : null;
	$state['server']   = npc_server_start();

	if ($state['server'] === null) {
		$state['skip'] = 'The built-in web server did not start.';

		return;
	}

	$realm = function ($file) use ($db) {
		$statement = $db->prepare('SELECT id + 100 FROM plugin_realms WHERE plugin = ? AND file = ?');
		$statement->execute(array('npc', $file));

		return (int) $statement->fetchColumn();
	};

	$grants = array(
		'viewer'    => array($realm('npc.php')),
		'nobody'    => array(1),
		'commander' => array($realm('npc.php'), $realm('npc1.php')),
		'admin'     => array($realm('npc.php'), $realm('npc1.php'), 3, 2),
	);

	$password          = bin2hex(random_bytes(8));
	$state['password'] = $password;
	$state['jars']   = array();
	$state['tokens'] = array();
	$state['users']  = array();

	foreach ($grants as $name => $realms) {
		$username = 'npc_e2e_' . $name;

		$db->prepare('DELETE FROM user_auth WHERE username = ?')->execute(array($username));
		npc_db_insert($db, 'user_auth', array('username' => $username, 'password' => password_hash($password, PASSWORD_DEFAULT), 'realm' => 0, 'must_change_password' => '', 'password_change' => '', 'enabled' => 'on', 'locked' => ''));

		$id = (int) $db->lastInsertId();
		$state['users'][] = $id;

		foreach ($realms as $realm_id) {
			npc_db_insert($db, 'user_auth_realm', array('realm_id' => $realm_id, 'user_id' => $id));
		}

		$state['jars'][$name] = npc_http_login($state['server'], $username, $password, 'login');

		if ($state['jars'][$name] === null) {
			$state['skip'] = 'Cacti did not accept the login of ' . $username . '.';

			return;
		}

		if (in_array($realm('npc.php'), $realms, true)) {
			$state['tokens'][$name] = npc_http_token($state['server'], $state['jars'][$name]);
		}
	}

	/* The Nagios command file and the settings that point to it. */
	$state['command_file'] = sys_get_temp_dir() . '/npc-e2e-cmd-' . uniqid();
	file_put_contents($state['command_file'], '');

	$state['settings'] = array();

	foreach (array('npc_nagios_commands' => '1', 'npc_nagios_cmd_path' => $state['command_file']) as $name => $value) {
		$old = $db->prepare('SELECT value FROM settings WHERE name = ?');
		$old->execute(array($name));
		$state['settings'][$name] = $old->fetchColumn();

		$db->prepare('REPLACE INTO settings (name, value) VALUES (?, ?)')->execute(array($name, $value));
	}

	/* Rows the listings and the bulk comment deletion read. */
	npc_db_insert($db, 'npc_objects', array('object_id' => 9300, 'instance_id' => 99, 'objecttype_id' => 1, 'name1' => 'e2ehost'));
	npc_db_insert($db, 'npc_comments', array('instance_id' => 99, 'object_id' => 9300, 'comment_data' => 'e2e comment', 'author_name' => 'e2e'));

	for ($i = 0; $i < 1005; $i++) {
		npc_db_insert($db, 'npc_logentries', array('instance_id' => 99, 'logentry_time' => '2026-01-01 00:00:00', 'entry_time' => '2026-01-01 00:00:00', 'entry_time_usec' => $i, 'logentry_data' => 'e2e entry ' . $i));
	}
});

afterAll(function () {
	$state = &$GLOBALS['npc_rs'];

	if (isset($state['server'])) {
		npc_server_stop($state['server']);
	}

	$db = $state['db'] ?? null;

	if ($db !== null) {
		foreach ($state['users'] ?? array() as $id) {
			$db->prepare('DELETE FROM user_auth_realm WHERE user_id = ?')->execute(array($id));
			$db->prepare('DELETE FROM user_auth WHERE id = ?')->execute(array($id));
		}

		foreach ($state['settings'] ?? array() as $name => $old) {
			if ($old === false) {
				$db->prepare('DELETE FROM settings WHERE name = ?')->execute(array($name));
			} else {
				$db->prepare('REPLACE INTO settings (name, value) VALUES (?, ?)')->execute(array($name, $old));
			}
		}

		$db->prepare('DELETE FROM npc_comments WHERE instance_id = ?')->execute(array(99));
		$db->prepare('DELETE FROM npc_objects WHERE object_id = ?')->execute(array(9300));
		$db->prepare('DELETE FROM npc_logentries WHERE instance_id = ?')->execute(array(99));
		$db->prepare('DELETE FROM data_input WHERE name LIKE ?')->execute(array('NPC - Perfdata - e2e%'));
		$db->prepare('DELETE FROM host WHERE description LIKE ?')->execute(array('e2e import%'));
	}

	if (isset($state['log']) && $state['log_size'] !== null && file_exists($state['log'])) {
		$handle = fopen($state['log'], 'r+');
		ftruncate($handle, $state['log_size']);
		fclose($handle);
	}
});

beforeEach(function () {
	if ($GLOBALS['npc_rs']['skip'] !== null) {
		$this->markTestSkipped($GLOBALS['npc_rs']['skip']);
	}

	file_put_contents($GLOBALS['npc_rs']['command_file'], '');
});

/** One request as the named user ('' for nobody logged in). */
function npc_rs_request($user, $method, array $fields, $token = true) {
	$state = $GLOBALS['npc_rs'];
	$jar   = $user === '' ? sys_get_temp_dir() . '/npc-jar-' . uniqid() : $state['jars'][$user];

	if ($method !== 'GET' && $token === true && isset($state['tokens'][$user])) {
		$fields['__csrf_magic'] = $state['tokens'][$user];
	}

	return npc_http($state['server'], $method, 'plugins/npc/npc.php', $fields, $jar, 'real-server');
}

/** A failed CSRF check ends the session, so those tests log in again afterwards. */
function npc_rs_relogin($user) {
	$state = &$GLOBALS['npc_rs'];

	$state['jars'][$user]   = npc_http_login($state['server'], 'npc_e2e_' . $user, $state['password'], 'login');
	$state['tokens'][$user] = npc_http_token($state['server'], $state['jars'][$user]);
}

function npc_rs_commands() {
	return array_values(array_filter(explode("\n", (string) file_get_contents($GLOBALS['npc_rs']['command_file']))));
}

function npc_rs_json($response) {
	return json_decode($response['body'], true);
}

it('asks a visitor who is not logged in to log in', function (string $method, array $fields) {
	$response = npc_rs_request('', $method, $fields, false);

	expect($response['body'])->not->toContain('currentTab');
	expect(json_decode($response['body'], true))->toBeNull();
	expect(npc_rs_commands())->toBe(array());
})->with(array(
	'frame'          => array('GET', array('module' => 'layout', 'action' => 'drawFrame')),
	'listing'        => array('GET', array('module' => 'hosts', 'action' => 'getHosts')),
	'command'        => array('POST', array('module' => 'nagios', 'action' => 'command', 'p_command' => 'ENABLE_NOTIFICATIONS')),
	'host import'    => array('POST', array('module' => 'sync', 'action' => 'import', 'p_description' => 'e2e import', 'p_ip' => '192.0.2.5', 'p_template_id' => '1')),
));

it('keeps a user without the NPC realm out', function (string $method, array $fields) {
	$response = npc_rs_request('nobody', $method, $fields, false);

	expect($response['body'])->not->toContain('currentTab');
	expect(json_decode($response['body'], true))->toBeNull();
	expect(npc_rs_commands())->toBe(array());
})->with(array(
	'frame'   => array('GET', array('module' => 'layout', 'action' => 'drawFrame')),
	'listing' => array('GET', array('module' => 'hosts', 'action' => 'getHosts')),
	'command' => array('POST', array('module' => 'nagios', 'action' => 'command', 'p_command' => 'ENABLE_NOTIFICATIONS')),
));

it('shows the frame to a user with the NPC realm', function (string $tab, string $expected) {
	$response = npc_rs_request('viewer', 'GET', array('module' => 'layout', 'action' => 'drawFrame', 'tab' => $tab));

	expect($response['status'])->toBe(200);
	expect($response['body'])->toContain("currentTab:       '" . $expected . "'");
	expect($response['body'])->not->toContain('breakout');
})->with(array(
	'known tab'               => array('hosts', 'hosts'),
	'unknown tab'             => array('nonsense', 'dashboard'),
	'markup closing a script' => array('x</script><b>breakout</b>', 'dashboard'),
	'quote and statement'     => array("x';breakout();'", 'dashboard'),
));

it('answers every listing from the database', function (string $module, string $action) {
	$response = npc_rs_request('viewer', 'GET', array('module' => $module, 'action' => $action));
	$body     = npc_rs_json($response);

	expect($response['status'])->toBe(200);
	expect($body)->toBeArray();
	expect($response['body'])->not->toContain('SQLSTATE');
})->with(array(
	'hosts'              => array('hosts', 'getHosts'),
	'host summary'       => array('hosts', 'summary'),
	'services'           => array('services', 'getServices'),
	'service summary'    => array('services', 'summary'),
	'host comments'      => array('comments', 'getHostComments'),
	'service comments'   => array('comments', 'getServiceComments'),
	'host downtime'      => array('downtime', 'getHostDowntime'),
	'service downtime'   => array('downtime', 'getServiceDowntime'),
	'log entries'        => array('logentries', 'getLogs'),
	'notifications'      => array('notifications', 'getNotifications'),
	'state history'      => array('statehistory', 'getStateHistory'),
	'process info'       => array('nagios', 'getProcessInfoGrid'),
));

it('pages through a listing within the allowed bounds', function (array $query, int $items) {
	$response = npc_rs_request('viewer', 'GET', array('module' => 'logentries', 'action' => 'getLogs') + $query);
	$page     = npc_rs_json($response)['response']['value'];

	expect(count($page['items']))->toBe($items);
	expect((int) $page['total_count'])->toBeGreaterThanOrEqual(1005);
})->with(array(
	'default page'   => array(array(), 25),
	'above the cap'  => array(array('limit' => '100000'), 1000),
	'below one'      => array(array('limit' => '-5'), 1),
	'negative start' => array(array('start' => '-50', 'limit' => '10'), 10),
	'last page'      => array(array('start' => '1000', 'limit' => '50'), 5),
	'limit as p_'    => array(array('p_limit' => '100000'), 25),
));

it('refuses a state-changing action sent with the wrong method', function (string $method, string $module, string $action) {
	$response = npc_rs_request('admin', $method, array('module' => $module, 'action' => $action, 'p_command' => 'ENABLE_NOTIFICATIONS', 'p_object_id' => '1', 'p_host' => 'e2e'), false);

	expect($response['status'])->toBe(405);
	/* Cacti's own method check answers some actions, such as save, before the plugin does. */
	expect($response['body'] === '' || npc_rs_json($response) === array('success' => false, 'msg' => 'This action requires a POST request.'))->toBeTrue();
	expect(npc_rs_commands())->toBe(array());
})->with(function () {
	$actions = array('nagios' => 'command', 'comments' => 'deleteAllHostComments', 'cacti' => 'addDataInputMethod', 'sync' => 'import', 'hosts' => 'setMappedGraph', 'services' => 'setMappedGraph', 'settings' => 'save');

	foreach (array('GET', 'PUT', 'DELETE') as $method) {
		foreach ($actions as $module => $action) {
			yield $method . ' ' . $module . '/' . $action => array($method, $module, $action);
		}
	}
});

it('rejects a POST without the CSRF token', function () {
	$response = npc_rs_request('commander', 'POST', array('module' => 'nagios', 'action' => 'command', 'p_command' => 'ENABLE_NOTIFICATIONS'), false);

	expect($response['body'])->not->toContain('"success":true');
	expect(npc_rs_commands())->toBe(array());

	npc_rs_relogin('commander');
});

it('rejects a POST with a token that is not valid', function () {
	$response = npc_rs_request('commander', 'POST', array('module' => 'nagios', 'action' => 'command', 'p_command' => 'ENABLE_NOTIFICATIONS', '__csrf_magic' => 'not-a-token'), false);

	expect($response['body'])->not->toContain('"success":true');
	expect(npc_rs_commands())->toBe(array());

	npc_rs_relogin('commander');
});

it('writes a Nagios command for a user with the command realm', function () {
	$response = npc_rs_request('commander', 'POST', array('module' => 'nagios', 'action' => 'command', 'p_command' => 'ENABLE_NOTIFICATIONS'));

	expect(npc_rs_json($response))->toBe(array('success' => true));
	expect(npc_rs_commands())->toHaveCount(1);
	expect(npc_rs_commands()[0])->toMatch('/^\[\d+\] ENABLE_NOTIFICATIONS$/');
});

it('refuses the command to a user without the command realm', function () {
	$response = npc_rs_request('viewer', 'POST', array('module' => 'nagios', 'action' => 'command', 'p_command' => 'ENABLE_NOTIFICATIONS'));

	expect(npc_rs_json($response))->toBe(array('success' => false, 'msg' => 'You do not have permission to execute this command.'));
	expect(npc_rs_commands())->toBe(array());
});

it('keeps a hostile host name inside one field of one command', function (string $host) {
	$response = npc_rs_request('commander', 'POST', array(
		'module'       => 'nagios',
		'action'       => 'command',
		'p_command'    => 'ADD_HOST_COMMENT',
		'p_host_name'  => $host,
		'p_persistent' => '0',
		'p_author'     => 'e2e',
		'p_comment'    => "first line\nsecond;line",
	));

	expect(npc_rs_json($response))->toBe(array('success' => true));

	$commands = npc_rs_commands();

	expect($commands)->toHaveCount(1);
	expect(substr_count($commands[0], ';'))->toBe(4);
})->with(array(
	'separator and new line' => array("web1;DISABLE_NOTIFICATIONS\nDISABLE_EVENT_HANDLERS"),
	'carriage return'        => array("web1\rDISABLE_NOTIFICATIONS"),
	'semicolons only'        => array(';;;'),
));

it('rejects an unknown or incomplete command', function (array $fields, string $message) {
	$response = npc_rs_request('commander', 'POST', array('module' => 'nagios', 'action' => 'command') + $fields);

	expect(npc_rs_json($response)['success'])->toBeFalse();
	expect(npc_rs_json($response)['msg'])->toContain($message);
	expect(npc_rs_commands())->toBe(array());
})->with(array(
	'unknown command'    => array(array('p_command' => 'FORMAT_DISK'), 'not a valid command'),
	'missing parameters' => array(array('p_command' => 'ADD_HOST_COMMENT', 'p_host_name' => 'web1'), 'Missing required parameter'),
));

it('deletes the comments of a host for a user with the command realm', function () {
	$response = npc_rs_request('commander', 'POST', array('module' => 'comments', 'action' => 'deleteAllHostComments'));

	expect($response['status'])->toBe(200);
	expect(preg_replace('/^\[\d+\] /', '', npc_rs_commands()[0] ?? ''))->toBe('DEL_ALL_HOST_COMMENTS;e2ehost');
});

it('deletes no comments for a user without the command realm', function () {
	npc_rs_request('viewer', 'POST', array('module' => 'comments', 'action' => 'deleteAllHostComments'));
	npc_rs_request('viewer', 'POST', array('module' => 'comments', 'action' => 'deleteAllServiceComments'));

	expect(npc_rs_commands())->toBe(array());
});

it('rejects dispatcher actions that were removed', function (string $module, string $action) {
	$response = npc_rs_request('admin', 'GET', array('module' => $module, 'action' => $action));

	expect($response['status'])->toBe(302);
	expect($response['body'])->toBe('');
})->with(array(
	'setting lookup' => array('cacti', 'getSetting'),
	'mapped check'   => array('cacti', 'isMapped'),
	'host mapping'   => array('cacti', 'mapHost'),
	'user settings'  => array('settings', 'getSettings'),
	'address check'  => array('sync', 'checkHostExists'),
));

it('creates a data input method only for a user with the data input realm', function (string $user, bool $allowed) {
	$db = $GLOBALS['npc_rs']['db'];

	$response = npc_rs_request($user, 'POST', array('module' => 'cacti', 'action' => 'addDataInputMethod', 'p_host' => 'e2e' . $user, 'p_object_id' => '12'));
	$count    = $db->prepare('SELECT COUNT(*) FROM data_input WHERE name = ?');
	$count->execute(array('NPC - Perfdata - e2e' . $user));

	expect(npc_rs_json($response)['success'])->toBe($allowed);
	expect((int) $count->fetchColumn())->toBe($allowed ? 1 : 0);
})->with(array(
	'user with the realm'    => array('admin', true),
	'user without the realm' => array('commander', false),
	'viewer'                 => array('viewer', false),
));

it('runs the host import for a user with the devices realm without running request text as a command', function () {
	$marker = sys_get_temp_dir() . '/npc-marker-' . uniqid();

	$response = npc_rs_request('admin', 'POST', array(
		'module'           => 'sync',
		'action'           => 'import',
		'p_description'    => 'e2e import & touch ' . $marker . ' &',
		'p_host_object_id' => '9301',
		'p_ip'             => '192.0.2.77',
		'p_cache_id'       => 'e2e',
		'p_template_id'    => '99999',
	));

	expect($response['status'])->toBe(200);
	expect($response['body'])->toStartWith('e2e import');
	expect(file_exists($marker))->toBeFalse();
});

it('rejects a host import with unusable parameters', function (array $override, string $message) {
	$response = npc_rs_request('admin', 'POST', array_merge(array(
		'module'           => 'sync',
		'action'           => 'import',
		'p_description'    => 'e2e import',
		'p_host_object_id' => '9301',
		'p_ip'             => '192.0.2.77',
		'p_cache_id'       => 'e2e',
		'p_template_id'    => '1',
	), $override));

	expect($response['body'])->toBe(($override['p_description'] ?? 'e2e import') . '|0|0|' . $message);
})->with(array(
	'template id with a suffix' => array(array('p_template_id' => '7 id'), 'Invalid import parameters'),
	'empty address'             => array(array('p_ip' => ''), 'Invalid import parameters'),
));

it('refuses the host import to a user without the devices realm', function (string $user) {
	$response = npc_rs_request($user, 'POST', array('module' => 'sync', 'action' => 'import', 'p_description' => 'e2e import', 'p_ip' => '192.0.2.77', 'p_template_id' => '1'));

	expect($response['body'])->toBe('e2e import|0|0|You do not have permission to add devices');
})->with(array('viewer', 'commander'));

it('lists the hostgroups selected for the import as JSON', function () {
	$response = npc_rs_request('viewer', 'GET', array('module' => 'sync', 'action' => 'getHosts', 'p_data' => '{"g1":{"alias":"e2e group","create_graphs":1,"template":2}}'));

	expect($response['status'])->toBe(200);
	expect(npc_rs_json($response))->toBe(array());
});
