<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Runs the plugin's schema and queries against a real MariaDB or MySQL
 * server. The fetch helpers of the unit bootstrap hand each statement to PDO
 * the way Cacti's database layer does (default PDO settings, values passed to
 * execute() as an array), so a statement that Cacti would send is a statement
 * this test sends.
 *
 * The server comes from NPC_TEST_DB_HOST, NPC_TEST_DB_PORT, NPC_TEST_DB_NAME,
 * NPC_TEST_DB_USER and NPC_TEST_DB_PASS, or else from the database settings in
 * the include/config.php of the Cacti tree the plugin sits in. Without a
 * reachable server the tests are skipped, except under CI, where that is an
 * error.
 */

require_once __DIR__ . '/support/database.php';

beforeAll(function () {
	require_once dirname(__DIR__, 2) . '/setup.php';
	npc_test_stub_cacti_includes();
	require_once dirname(__DIR__, 2) . '/controllers/controller.php';

	foreach (array('hosts', 'services', 'comments', 'downtime', 'logentries', 'notifications', 'statehistory') as $controller) {
		npc_test_load(dirname(__DIR__, 2) . '/controllers/' . $controller . '.php');
	}

	$GLOBALS['npc_db'] = npc_db_connect();

	if ($GLOBALS['npc_db'] === null) {
		return;
	}

	/* Create the tables from the SQL the installer issues. */
	$stub = sys_get_temp_dir() . '/npc-test-lib-stub';

	if (!is_dir($stub)) {
		mkdir($stub, 0777, true);
	}

	file_put_contents($stub . '/database.php', "<?php\n");
	$GLOBALS['config']['library_path'] = $stub;
	$GLOBALS['__test_db_calls']        = array();
	$GLOBALS['__test_pdo']             = null;
	unset($GLOBALS['__test_pdo']);

	npc_setup_tables();

	foreach ($GLOBALS['__test_db_calls'] as $call) {
		if (strpos($call['sql'], 'CREATE TABLE `') === 0) {
			$GLOBALS['npc_db']->exec(preg_replace('/^CREATE TABLE /', 'CREATE TABLE IF NOT EXISTS ', $call['sql']));
		}
	}
});

beforeEach(function () {
	if ($GLOBALS['npc_db'] === null) {
		if (getenv('CI') === 'true') {
			throw new RuntimeException('The integration database is not reachable.');
		}

		$this->markTestSkipped('No test database is reachable.');
	}

	$GLOBALS['__test_pdo']          = $GLOBALS['npc_db'];
	$GLOBALS['__test_config']       = array('npc_config_type' => '1');
	$GLOBALS['__test_db_calls']     = array();
	$GLOBALS['__test_db_fetch_log'] = array();
	$GLOBALS['npc_db']->beginTransaction();
});

afterEach(function () {
	unset($GLOBALS['__test_pdo']);
	$GLOBALS['__test_config'] = array();

	if ($GLOBALS['npc_db'] !== null && $GLOBALS['npc_db']->inTransaction()) {
		$GLOBALS['npc_db']->rollBack();
	}
});

it('creates every table of the schema', function () {
	preg_match_all('/CREATE TABLE `(npc_\w+)`/', file_get_contents(dirname(__DIR__, 2) . '/includes/database.php'), $declared);

	$present = $GLOBALS['npc_db']->query("SHOW TABLES LIKE 'npc\\_%'")->fetchAll(PDO::FETCH_COLUMN);

	expect(count($declared[1]))->toBeGreaterThan(50);
	expect(array_diff($declared[1], $present))->toBe(array());
});

it('is rejected by the server when the paging values are bound the way Cacti binds them', function () {
	$statement = $GLOBALS['npc_db']->prepare('SELECT 1 FROM npc_logentries LIMIT ?, ?');

	expect(fn () => $statement->execute(array(0, 25)))->toThrow(PDOException::class);
});

it('runs every listing query against the server', function (string $class, string $method) {
	$controller = new $class();
	$rows       = $controller->$method();

	expect(is_array($rows) || is_string($rows))->toBeTrue();
})->with(array(
	'hosts'         => array('NpcHostsController', 'hosts'),
	'services'      => array('NpcServicesController', 'services'),
	'comments'      => array('NpcCommentsController', 'comments'),
	'downtime'      => array('NpcDowntimeController', 'downtimeHistory'),
	'log entries'   => array('NpcLogentriesController', 'getLogs'),
	'notifications' => array('NpcNotificationsController', 'getNotifications'),
	'state history' => array('NpcStatehistoryController', 'getStateHistory'),
));

it('pages through a listing', function () {
	$pdo = $GLOBALS['npc_db'];

	for ($i = 1; $i <= 7; $i++) {
		npc_db_insert($pdo, 'log_entry', array(99, '2026-01-01 00:00:0' . $i, '2026-01-01 00:00:0' . $i, 0, 'entry ' . $i));
	}

	$controller              = new NpcLogentriesController();
	$controller->limit       = 3;
	$controller->currentPage = 3;

	$page = json_decode($controller->getLogs(), true)['response']['value'];

	expect(array_column($page['items'], 'logentry_data'))->toBe(array('entry 1'));
	expect((int) $page['total_count'])->toBeGreaterThanOrEqual(7);
});

it('lists each service once however many hostgroups its host belongs to', function () {
	$pdo = $GLOBALS['npc_db'];

	npc_db_insert($pdo, 'instance', array(99, 'default'));
	npc_db_insert($pdo, 'object', array(9100, 99, 1, 'web1', ''));

	foreach (array(9101 => 'Load', 9102 => 'Disk') as $id => $description) {
		npc_db_insert($pdo, 'object', array($id, 99, 2, 'web1', $description));
		npc_db_insert($pdo, 'service', array(99, 1, 9100, $id));
		npc_db_insert($pdo, 'service_status', array(99, $id, 0));
	}

	npc_db_insert($pdo, 'host', array(99, 1, 9100, 'web1'));

	foreach (array(9201 => 'web servers', 9202 => 'linux servers') as $object_id => $alias) {
		npc_db_insert($pdo, 'hostgroup', array($object_id, 99, 1, $object_id, $alias));
		npc_db_insert($pdo, 'hostgroup_member', array(99, $object_id, 9100));
	}

	$services = new NpcServicesController();
	$rows     = $services->services();

	expect(array_column($rows, 'service_description'))->toBe(array('Disk', 'Load'));
	expect((int) $services->numRecords)->toBe(2);

	$filtered            = new NpcServicesController();
	$filtered->hostgroup = 'web servers';

	expect(array_column($filtered->services(), 'service_description'))->toBe(array('Disk', 'Load'));
	expect((int) $filtered->numRecords)->toBe(2);
});

it('returns only the newest stored check as host perfdata', function () {
	$pdo = $GLOBALS['npc_db'];

	foreach (array('2026-01-01 00:00:01' => 'old=1', '2026-01-01 00:00:02' => 'mid=2', '2026-01-01 00:00:03' => 'new=3') as $time => $perfdata) {
		npc_db_insert($pdo, 'host_check', array(99, 9100, $time, $perfdata));
	}

	$rows = (new NpcHostsController())->getPerfData(9100);

	expect($rows)->toBe(array(array('perfdata' => 'new=3')));
});

it('adds the importance column to the contacts table on upgrade', function () {
	$pdo = $GLOBALS['npc_db'];

	if ($pdo->inTransaction()) {
		$pdo->rollBack();
	}

	if (db_column_exists('npc_contacts', 'minimum_importance')) {
		$pdo->exec('ALTER TABLE `npc_contacts` DROP COLUMN `minimum_importance`');
	}

	npc_upgrade_tables();

	expect(db_column_exists('npc_contacts', 'minimum_importance'))->toBeTrue();
});

/** The stored settings of every user row, keyed by user id. */
function npc_db_settings_users($pdo) {
	return array_map('intval', $pdo->query('SELECT user_id FROM npc_settings ORDER BY user_id')->fetchAll(PDO::FETCH_COLUMN));
}

it('removes the settings of deleted users and creates those of new users', function () {
	$pdo = $GLOBALS['npc_db'];

	if ($pdo->query("SHOW TABLES LIKE 'user_auth'")->rowCount() === 0) {
		$this->markTestSkipped('Cacti user table is not available.');
	}

	npc_db_insert($pdo, 'user', array('npc_db_new_user', 'x', 0, '', '', 'on', ''));
	$new = (int) $pdo->lastInsertId();
	npc_db_insert($pdo, 'user_settings', array(987654321, 'a:0:{}'));

	$_SESSION['sess_user_id']        = 1;
	$GLOBALS['npc_default_settings'] = array('date_format' => 's:Y-m-d');
	$GLOBALS['__test_pdo']           = $pdo;

	$version = $pdo->query("SELECT version FROM plugin_config WHERE directory = 'npc'")->fetchColumn();

	npc_config_settings();

	unset($_SESSION['sess_user_id']);

	$kept = npc_db_settings_users($pdo);

	expect($kept)->toContain($new);
	expect($kept)->not->toContain(987654321);
	expect($pdo->query("SELECT version FROM plugin_config WHERE directory = 'npc'")->fetchColumn())->toBe($version);
});

it('runs the upgrade against the real tables when the stored version is old', function () {
	$pdo = $GLOBALS['npc_db'];

	if ($pdo->query("SHOW TABLES LIKE 'plugin_config'")->rowCount() === 0) {
		$this->markTestSkipped('Cacti plugin table is not available.');
	}

	/* The prune step of the upgrade works on base_path/plugins/npc, so it gets a
	   copy of the layout and never the real plugin directory. */
	$base = sys_get_temp_dir() . '/npc-upgrade-' . uniqid();
	mkdir($base . '/plugins/npc/tests', 0777, true);
	file_put_contents($base . '/plugins/npc/INFO', "[info]\nversion = 9.9.9\nname = npc\nauthor = x\n");
	file_put_contents($base . '/plugins/npc/manifest.json', json_encode(array('tombstones' => array(), 'expected' => array('INFO', 'manifest.json'), 'whitelist' => array())));
	file_put_contents($base . '/plugins/npc/tests/marker.txt', 'x');

	$realTests = dirname(__DIR__);

	$pdo->prepare("UPDATE plugin_config SET version = ? WHERE directory = 'npc'")->execute(array('0.1'));
	npc_db_insert($pdo, 'user_settings', array(987654322, 'a:0:{}'));

	$restore                        = $GLOBALS['config']['base_path'];
	$GLOBALS['config']['base_path'] = $base;
	$GLOBALS['__test_pdo']          = $pdo;
	$_SESSION['sess_user_id']       = 1;

	try {
		npc_config_settings();
	} finally {
		$GLOBALS['config']['base_path'] = $restore;
		unset($_SESSION['sess_user_id']);
	}

	expect($pdo->query("SELECT version FROM plugin_config WHERE directory = 'npc'")->fetchColumn())->toBe('9.9.9');
	expect(npc_db_settings_users($pdo))->not->toContain(987654322);
	expect(file_exists($base . '/plugins/npc/tests'))->toBeFalse();
	expect(is_dir($realTests))->toBeTrue();
	expect(is_dir(dirname(__DIR__) . '/Integration'))->toBeTrue();
});

/** Insert one host and service with both original and retained host configuration. */
function npc_algorithm_fixture($pdo, $checked = 1) {
	npc_db_insert($pdo, 'instance', [199, 'algorithm-test']);
	npc_db_insert($pdo, 'object', [19900, 199, 1, 'algorithm-host', '']);
	npc_db_insert($pdo, 'object', [19901, 199, 2, 'algorithm-host', 'Load']);
	npc_db_insert($pdo, 'host', [199, 0, 19900, 'original']);
	npc_db_insert($pdo, 'host', [199, 1, 19900, 'retained']);
	npc_db_insert($pdo, 'service', [199, 1, 19900, 19901]);
	$pdo->prepare('INSERT INTO npc_hoststatus (instance_id, host_object_id, current_state, has_been_checked) VALUES (199, 19900, 0, ?)')->execute([$checked]);
	$pdo->prepare('INSERT INTO npc_servicestatus (instance_id, service_object_id, current_state, has_been_checked) VALUES (199, 19901, 0, ?)')->execute([$checked]);
}

it('shows unchecked objects as pending in summaries and listing filters', function () {
	npc_algorithm_fixture($GLOBALS['npc_db'], 0);
	foreach ([NpcHostsController::class => ['hosts', 'up'], NpcServicesController::class => ['services', 'ok']] as $class => [$method, $healthy]) {
		$controller = new $class();
		$summary = json_decode($controller->summary(), true)['data'][0];
		expect($summary['pending'])->toBe(1);
		expect($summary[$healthy])->toBe(0);
		$controller->state = 'pending';
		$rows = $controller->$method();
		expect($rows)->toHaveCount(1);
		expect((int) $rows[0]['current_state'])->toBe(-1);
		$controller->state = $healthy;
		expect($controller->$method())->toBe([]);
	}
});

it('lists a service once with the selected host configuration', function () {
	npc_algorithm_fixture($GLOBALS['npc_db']);
	$controller = new NpcServicesController();
	$rows = $controller->services();
	expect($rows)->toHaveCount(1);
	expect((int) $controller->numRecords)->toBe(1);
	expect($rows[0]['host_alias'])->toBe('retained');
	expect((int) $rows[0]['current_state'])->toBe(0);
});

it('retains flat hostgroup and servicegroup membership and pending states', function () {
	$pdo = $GLOBALS['npc_db'];
	npc_algorithm_fixture($pdo, 0);
	npc_test_load(dirname(__DIR__, 2) . '/controllers/hostgroups.php');
	npc_test_load(dirname(__DIR__, 2) . '/controllers/servicegroups.php');
	npc_db_insert($pdo, 'object', [19902, 199, 3, 'host-group', '']);
	npc_db_insert($pdo, 'hostgroup', [19902, 199, 1, 19902, 'host-group']);
	npc_db_insert($pdo, 'hostgroup_member', [199, 19902, 19900]);
	npc_db_insert($pdo, 'object', [19903, 199, 4, 'service-group', '']);
	$pdo->exec("INSERT INTO npc_servicegroups (servicegroup_id, instance_id, config_type, servicegroup_object_id, alias) VALUES (19903, 199, 1, 19903, 'service-group')");
	$pdo->exec('INSERT INTO npc_servicegroup_members (instance_id, servicegroup_id, service_object_id) VALUES (199, 19903, 19901)');
	$hosts = new NpcHostgroupsController();
	$services = new NpcServicegroupsController();
	$pdo->exec("UPDATE npc_hosts SET display_name = 'algorithm-host' WHERE host_object_id = 19900");
	expect($services->getServicegroupMemberHoststatus('algorithm-host'))->toBe(-1);
	expect($hosts->setupResultsArray())->toHaveCount(1);
	expect($services->setupResultsArray())->toHaveCount(1);
	expect(json_decode($hosts->getHostgroupHostStatus(), true)['response']['value']['items'][0]['pending'])->toBe(1);
	expect(json_decode($hosts->getHostgroupServiceStatus(), true)['response']['value']['items'][0]['pending'])->toBe(1);
	expect(json_decode($services->getServicegroupServiceStatus(), true)['response']['value']['items'][0]['pending'])->toBe(1);
});

it('uses active Nagios downtime depth regardless of future or flexible schedules', function () {
	$pdo = $GLOBALS['npc_db'];
	npc_algorithm_fixture($pdo);
	$pdo->exec('INSERT INTO npc_scheduleddowntime (instance_id, object_id, internal_downtime_id, scheduled_start_time, scheduled_end_time) VALUES (199, 19901, 1, NOW() - INTERVAL 1 HOUR, NOW() + INTERVAL 1 HOUR), (199, 19901, 2, NOW() + INTERVAL 1 DAY, NOW() + INTERVAL 2 DAY)');
	$controller = new NpcDowntimeController();
	// An eligible flexible window has not necessarily started.
	expect($controller->inDowntime(19901))->toBe(0);
	$pdo->exec('UPDATE npc_servicestatus SET scheduled_downtime_depth = 2 WHERE service_object_id = 19901');
	expect($controller->inDowntime(19901))->toBe(1);
	$response = json_decode((new NpcServicesController())->getServices(), true);
	expect($response['response']['value']['items'][0]['in_downtime'])->toBe(1);
	$pdo->exec('UPDATE npc_hoststatus SET scheduled_downtime_depth = 1 WHERE host_object_id = 19900');
	expect($controller->inDowntime(19900))->toBe(1);
	expect($controller->inDowntime(19999))->toBe(0);
});

it('batches comment metadata without changing newest comment selection', function () {
	$pdo = $GLOBALS['npc_db'];
	npc_algorithm_fixture($pdo);
	$insert = $pdo->prepare('INSERT INTO npc_comments (instance_id, object_id, comment_type, entry_type, internal_comment_id, comment_time, entry_time, author_name, comment_data) VALUES (199, 19901, 2, ?, ?, ?, ?, ?, ?)');
	foreach ([[1, 1, '2026-01-01 00:00:00', 'old'], [1, 2, '2026-01-02 00:00:00', 'latest'], [4, 3, '2026-01-03 00:00:00', 'ack']] as [$type, $id, $date, $text]) {
		$insert->execute([$type, $id, $date, $date, 'operator', $text]);
	}
	$pdo->exec('UPDATE npc_servicestatus SET problem_has_been_acknowledged = 1 WHERE service_object_id = 19901');
	$GLOBALS['__test_db_fetch_log'] = [];
	$response = json_decode((new NpcServicesController())->getServices(), true)['response']['value'];
	expect($response['items'][0]['comment'])->toBe('operator*|*latest');
	expect($response['items'][0]['acknowledgement'])->toBe('operator*|*ack');
	expect($GLOBALS['__test_db_fetch_log'])->toHaveCount(3);
});
