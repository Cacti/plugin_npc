<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for the Nagios host import in controllers/sync.php.
 *
 * The import shells out to cli/add_device.php. The tests point base_path at a
 * temporary tree whose add_device.php records its argument vector, so the real
 * exec() path runs without touching a Cacti install or a database.
 */

beforeAll(function () {
	npc_test_stub_cacti_includes();
	npc_test_load(dirname(__DIR__, 2) . '/controllers/sync.php');
});

beforeEach(function () {
	$GLOBALS['__test_db_calls']   = array();
	$GLOBALS['__test_db_results'] = array();
});

/**
 * Temporary Cacti-like tree with a stand-in add_device.php.
 */
function npc_sync_sandbox() {
	$base = sys_get_temp_dir() . '/npc-sync-' . uniqid();
	mkdir($base . '/cli', 0777, true);
	file_put_contents($base . '/cli/add_device.php', "<?php\nfile_put_contents(__DIR__ . '/argv.json', json_encode(array_slice(\$argv, 1)));\nprint \"Success - new device-id: (12)\\n\";\n");

	return $base;
}

function npc_sync_unmapped_controller() {
	return new class extends NpcSyncController {
		function checkHostExists($ip, $cache_id) {
			return null;
		}
	};
}

it('reports an already mapped host without running the import', function () {
	$GLOBALS['__test_db_results']['db_fetch_assoc_prepared']['WHERE npc_host_object_id'] = array(array('id' => 3));

	$out = (new NpcSyncController())->import(array(
		'config'         => array('base_path' => '/nonexistent'),
		'description'    => 'web1',
		'host_object_id' => 10,
		'ip'             => '192.0.2.10',
		'cache_id'       => 'k',
		'template_id'    => 1,
	));

	expect($out)->toBe('web1|0|0|The host already exists and is mapped');
});

it('maps a host whose address already exists in Cacti', function () {
	$sync = new class extends NpcSyncController {
		function checkHostExists($ip, $cache_id) {
			return 5;
		}
	};

	$out = $sync->import(array(
		'config'         => array('base_path' => '/nonexistent'),
		'description'    => 'web1',
		'host_object_id' => 10,
		'ip'             => '192.0.2.10',
		'cache_id'       => 'k',
		'template_id'    => 1,
	));

	expect($out)->toBe('web1|0|1|The host already existed in Cacti with device ID: 5');

	$update = end($GLOBALS['__test_db_calls']);
	expect($update['sql'])->toContain('UPDATE host');
	expect($update['params'])->toBe(array(10, 5));
});

it('imports a new host and maps the returned device id', function () {
	$base = npc_sync_sandbox();

	$out = npc_sync_unmapped_controller()->import(array(
		'config'         => array('base_path' => $base),
		'description'    => 'web1',
		'host_object_id' => 10,
		'ip'             => '192.0.2.10',
		'cache_id'       => 'k',
		'template_id'    => 7,
	));

	expect($out)->toBe('web1|1|1|Success - new device-id: (12)');

	$update = end($GLOBALS['__test_db_calls']);
	expect($update['params'])->toBe(array(10, '12'));
})->skip(trim((string) shell_exec('command -v php')) === '', 'php binary is not on PATH');

it('builds the address cache from Cacti devices when none exists', function () {
	$cache = '/tmp/npc_address_cache.php';
	if (file_exists($cache)) {
		unlink($cache);
	}

	$GLOBALS['__test_db_results']['db_fetch_assoc']['FROM host'] = array(array('id' => 4, 'hostname' => '127.0.0.1'));

	try {
		expect((new NpcSyncController())->checkHostExists('127.0.0.1', 'k1'))->toBe(4);
	} finally {
		if (file_exists($cache)) {
			unlink($cache);
		}
	}
});
