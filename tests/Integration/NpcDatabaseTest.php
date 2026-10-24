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

/**
 * @return array|null host, port, name, user and password of the test database
 */
function npc_db_settings() {
	if (getenv('NPC_TEST_DB_HOST') !== false) {
		return array(
			'host' => getenv('NPC_TEST_DB_HOST'),
			'port' => getenv('NPC_TEST_DB_PORT') ?: '3306',
			'name' => getenv('NPC_TEST_DB_NAME') ?: 'cacti',
			'user' => getenv('NPC_TEST_DB_USER') ?: 'cactiuser',
			'pass' => getenv('NPC_TEST_DB_PASS') !== false ? getenv('NPC_TEST_DB_PASS') : 'cactiuser',
		);
	}

	$file = $GLOBALS['config']['base_path'] . '/include/config.php';

	if (!is_readable($file)) {
		return null;
	}

	$text     = (string) file_get_contents($file);
	$settings = array();

	foreach (array('host' => 'hostname', 'port' => 'port', 'name' => 'default', 'user' => 'username', 'pass' => 'password') as $key => $variable) {
		if (preg_match('/^\s*\$database_' . $variable . '\s*=\s*[\'"]([^\'"]*)[\'"]/m', $text, $match)) {
			$settings[$key] = $match[1];
		}
	}

	return isset($settings['host'], $settings['name'], $settings['user']) ? $settings + array('port' => '3306', 'pass' => '') : null;
}

function npc_db_connect() {
	$settings = npc_db_settings();

	if ($settings === null) {
		return null;
	}

	try {
		$pdo = new PDO(
			'mysql:host=' . $settings['host'] . ';port=' . $settings['port'] . ';dbname=' . $settings['name'] . ';charset=utf8',
			$settings['user'],
			$settings['pass'],
			array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 2)
		);
	} catch (PDOException $e) {
		return null;
	}

	/* Cacti runs its sessions without the strict modes. */
	$pdo->exec("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");

	return $pdo;
}

function npc_db_insert($pdo, $table, array $row) {
	$columns = '`' . implode('`, `', array_keys($row)) . '`';
	$marks   = implode(', ', array_fill(0, count($row), '?'));

	$pdo->prepare('INSERT INTO `' . $table . '` (' . $columns . ') VALUES (' . $marks . ')')->execute(array_values($row));
}

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
		npc_db_insert($pdo, 'npc_logentries', array('instance_id' => 99, 'logentry_time' => '2026-01-01 00:00:0' . $i, 'entry_time' => '2026-01-01 00:00:0' . $i, 'logentry_data' => 'entry ' . $i));
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

	npc_db_insert($pdo, 'npc_instances', array('instance_id' => 99, 'instance_name' => 'default'));
	npc_db_insert($pdo, 'npc_objects', array('object_id' => 9100, 'instance_id' => 99, 'objecttype_id' => 1, 'name1' => 'web1'));

	foreach (array(9101 => 'Load', 9102 => 'Disk') as $id => $description) {
		npc_db_insert($pdo, 'npc_objects', array('object_id' => $id, 'instance_id' => 99, 'objecttype_id' => 2, 'name1' => 'web1', 'name2' => $description));
		npc_db_insert($pdo, 'npc_services', array('instance_id' => 99, 'config_type' => 1, 'host_object_id' => 9100, 'service_object_id' => $id));
		npc_db_insert($pdo, 'npc_servicestatus', array('instance_id' => 99, 'service_object_id' => $id, 'current_state' => 0));
	}

	npc_db_insert($pdo, 'npc_hosts', array('instance_id' => 99, 'config_type' => 1, 'host_object_id' => 9100, 'alias' => 'web1'));

	foreach (array(9201 => 'web servers', 9202 => 'linux servers') as $object_id => $alias) {
		npc_db_insert($pdo, 'npc_hostgroups', array('hostgroup_id' => $object_id, 'instance_id' => 99, 'config_type' => 1, 'hostgroup_object_id' => $object_id, 'alias' => $alias));
		npc_db_insert($pdo, 'npc_hostgroup_members', array('instance_id' => 99, 'hostgroup_id' => $object_id, 'host_object_id' => 9100));
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
		npc_db_insert($pdo, 'npc_hostchecks', array('instance_id' => 99, 'host_object_id' => 9100, 'start_time' => $time, 'perfdata' => $perfdata));
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
