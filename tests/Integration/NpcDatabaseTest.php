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
