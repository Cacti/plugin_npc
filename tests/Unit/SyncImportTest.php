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
	$GLOBALS['__test_db_fetch_log'] = array();
	$GLOBALS['__test_denied_realms'] = array();
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

/**
 * Run checkHostExists() against a temporary base_path and return the result
 * together with that path, so each test can inspect the cache directory.
 */
function npc_sync_cache_run($ip, $cache_id, $prepare = null) {
	$base = sys_get_temp_dir() . '/npc-cache-' . uniqid();
	mkdir($base . '/cache', 0777, true);

	if ($prepare !== null) {
		$prepare($base . '/cache/npc_address_cache.json');
	}

	$restore                        = $GLOBALS['config']['base_path'];
	$GLOBALS['config']['base_path'] = $base;

	try {
		$found = (new NpcSyncController())->checkHostExists($ip, $cache_id);
	} finally {
		$GLOBALS['config']['base_path'] = $restore;
	}

	return array($found, $base);
}

it('builds the address cache as JSON from Cacti devices', function () {
	$GLOBALS['__test_db_results']['db_fetch_assoc']['FROM host'] = array(array('id' => 4, 'hostname' => '127.0.0.1'));

	list($found, $base) = npc_sync_cache_run('127.0.0.1', 'k1');
	$file               = $base . '/cache/npc_address_cache.json';

	expect($found)->toBe(4);
	expect(json_decode(file_get_contents($file), true))->toBe(array('key' => 'k1', 'hosts' => array('127.0.0.1' => 4)));
	expect(substr(sprintf('%o', fileperms($file)), -4))->toBe('0600');
	expect(glob($base . '/cache/*'))->toBe(array($file));
});

it('answers from a current cache without reading the device list', function () {
	list($found) = npc_sync_cache_run('10.0.0.1', 'k1', function ($file) {
		file_put_contents($file, json_encode(array('key' => 'k1', 'hosts' => array('10.0.0.1' => 7))));
	});

	expect($found)->toBe(7);
	expect($GLOBALS['__test_db_fetch_log'])->toBe(array());
});

it('rebuilds the cache when the key differs or the file is damaged', function (string $content) {
	$GLOBALS['__test_db_results']['db_fetch_assoc']['FROM host'] = array(array('id' => 4, 'hostname' => '127.0.0.1'));
	$GLOBALS['__test_db_fetch_log']                              = array();

	list($found) = npc_sync_cache_run('127.0.0.1', 'k2', function ($file) use ($content) {
		file_put_contents($file, $content);
	});

	expect($found)->toBe(4);
	expect($GLOBALS['__test_db_fetch_log'])->not->toBe(array());
})->with(array(
	'stale key'    => array('{"key":"k1","hosts":{"127.0.0.1":9}}'),
	'invalid JSON' => array('not json'),
	'wrong shape'  => array('{"key":"k2"}'),
));

it('does not execute a PHP cache file left in the system temp directory', function () {
	$legacy = '/tmp/npc_address_cache.php';

	if (file_exists($legacy)) {
		$this->markTestSkipped($legacy . ' already exists');
	}

	file_put_contents($legacy, "<?php\n\$GLOBALS['npc_legacy_cache_ran'] = true;\n\$cacheKey = 'k1';\n\$hosts = array('10.0.0.1' => '9');\n");
	unset($GLOBALS['npc_legacy_cache_ran']);

	try {
		list($found) = npc_sync_cache_run('10.0.0.1', 'k1');
	} finally {
		unlink($legacy);
	}

	expect($found)->toBeNull();
	expect(isset($GLOBALS['npc_legacy_cache_ran']))->toBeFalse();
});

it('still answers when the cache directory is missing', function () {
	$GLOBALS['__test_db_results']['db_fetch_assoc']['FROM host'] = array(array('id' => 4, 'hostname' => '127.0.0.1'));

	$base                           = sys_get_temp_dir() . '/npc-nocache-' . uniqid();
	$restore                        = $GLOBALS['config']['base_path'];
	$GLOBALS['config']['base_path'] = $base;

	try {
		$found = (new NpcSyncController())->checkHostExists('127.0.0.1', 'k1');
	} finally {
		$GLOBALS['config']['base_path'] = $restore;
	}

	expect($found)->toBe(4);
	expect(file_exists($base))->toBeFalse();
});

it('removes its temporary file when the cache cannot be moved into place', function () {
	$GLOBALS['__test_db_results']['db_fetch_assoc']['FROM host'] = array(array('id' => 4, 'hostname' => '127.0.0.1'));

	list($found, $base) = npc_sync_cache_run('127.0.0.1', 'k1', function ($file) {
		mkdir($file);
	});

	expect($found)->toBe(4);
	expect(glob($base . '/cache/*'))->toBe(array($base . '/cache/npc_address_cache.json'));
});

it('passes the import arguments to add_device.php as single values', function () {
	$base = npc_sync_sandbox();

	$out = npc_sync_unmapped_controller()->import(array(
		'config'         => array('base_path' => $base),
		'description'    => 'web 1 & echo extra',
		'host_object_id' => 10,
		'ip'             => '192.0.2.10',
		'cache_id'       => 'k',
		'template_id'    => 7,
	));

	expect($out)->toBe('web 1 & echo extra|1|1|Success - new device-id: (12)');
	expect(json_decode(file_get_contents($base . '/cli/argv.json'), true))->toBe(array(
		'--description=web 1 & echo extra',
		'--ip=192.0.2.10',
		'--template=7',
	));
})->skip(trim((string) shell_exec('command -v php')) === '', 'php binary is not on PATH');

it('rejects an import with a non-numeric template id or an empty address', function (array $override) {
	$base = npc_sync_sandbox();

	$out = npc_sync_unmapped_controller()->import(array_merge(array(
		'config'         => array('base_path' => $base),
		'description'    => 'web1',
		'host_object_id' => 10,
		'ip'             => '192.0.2.10',
		'cache_id'       => 'k',
		'template_id'    => 7,
	), $override));

	expect($out)->toBe(($override['description'] ?? 'web1') . '|0|0|Invalid import parameters');
	expect(file_exists($base . '/cli/argv.json'))->toBeFalse();
})->with(array(
	'template id with a suffix' => array(array('template_id' => '7; id')),
	'empty address'             => array(array('ip' => '')),
	'empty description'         => array(array('description' => '')),
));

it('refuses the import to a user without device management rights', function () {
	$GLOBALS['__test_denied_realms'] = array('host.php');

	$base = npc_sync_sandbox();

	$out = npc_sync_unmapped_controller()->import(array(
		'config'         => array('base_path' => $base),
		'description'    => 'web1',
		'host_object_id' => 10,
		'ip'             => '192.0.2.10',
		'cache_id'       => 'k',
		'template_id'    => 7,
	));

	expect($out)->toBe('web1|0|0|You do not have permission to add devices');
	expect($GLOBALS['__test_db_calls'])->toBe(array());
	expect(file_exists($base . '/cli/argv.json'))->toBeFalse();
});
