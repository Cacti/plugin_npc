<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for the address handling of controllers/sync.php: how a host
 * name or an address is turned into canonical addresses, and how the map from
 * Cacti devices to addresses is built, stored and read.
 *
 * DNS is replaced by closures, so every case is deterministic and no test
 * sends a query.
 */

beforeAll(function () {
	npc_test_stub_cacti_includes();
	npc_test_load(dirname(__DIR__, 2) . '/controllers/sync.php');
});

beforeEach(function () {
	$GLOBALS['__test_db_results'] = array();
});

/**
 * A sync controller whose lookups answer from the given tables.
 *
 * @param array $dns    name => list of A / AAAA records
 * @param array $legacy name => address answered by the system lookup
 */
function npc_address_controller(array $dns = array(), array $legacy = array()) {
	$controller = new NpcSyncController();

	$GLOBALS['npc_address_lookups'] = array('dns' => 0, 'name' => 0);

	$controller->dnsLookup = function ($name) use ($dns) {
		$GLOBALS['npc_address_lookups']['dns']++;

		return $dns[$name] ?? false;
	};
	$controller->nameLookup = function ($name) use ($legacy) {
		$GLOBALS['npc_address_lookups']['name']++;

		return $legacy[$name] ?? $name;
	};

	return $controller;
}

/**
 * Run checkHostExists() against a temporary base_path and return the result
 * together with that path, so each test can inspect the JSON cache directory.
 */
function npc_address_cache_run($controller, $ip, $cache_id, $prepare = null) {
	$base = sys_get_temp_dir() . '/npc-address-' . uniqid();
	mkdir($base . '/cache', 0777, true);

	if ($prepare !== null) {
		$prepare($base . '/cache/npc_address_cache.json');
	}

	$restore                        = $GLOBALS['config']['base_path'];
	$GLOBALS['config']['base_path'] = $base;

	try {
		$found = $controller->checkHostExists($ip, $cache_id);
	} finally {
		$GLOBALS['config']['base_path'] = $restore;
	}

	return array($found, $base);
}

/** Records of one name, as dns_get_record() returns them. */
function npc_address_records(array $addresses) {
	return array_map(function ($address) {
		return strpos($address, ':') === false ? array('type' => 'A', 'ip' => $address) : array('type' => 'AAAA', 'ipv6' => $address);
	}, $addresses);
}

it('writes an address in one canonical form', function (?string $input, ?string $expected) {
	expect(npc_address_controller()->normalizeAddress($input))->toBe($expected);
})->with(array(
	'IPv4'                         => array('192.0.2.10', '192.0.2.10'),
	'IPv4 with spaces around it'   => array('  192.0.2.10 ', '192.0.2.10'),
	'IPv6 compressed'              => array('2001:db8::1', '2001:db8::1'),
	'IPv6 upper case'              => array('2001:DB8::1', '2001:db8::1'),
	'IPv6 expanded'                => array('2001:0db8:0000:0000:0000:0000:0000:0001', '2001:db8::1'),
	'IPv6 partly expanded'         => array('2001:db8:0:0:0:0:0:1', '2001:db8::1'),
	'IPv6 in brackets'             => array('[2001:DB8::1]', '2001:db8::1'),
	'IPv6 with a scope id'         => array('fe80::1%eth0', 'fe80::1%eth0'),
	'IPv6 loopback'                => array('0:0:0:0:0:0:0:1', '::1'),
	'IPv6 unspecified'             => array('0000:0000:0000:0000:0000:0000:0000:0000', '::'),
	'IPv4 mapped IPv6'             => array('::FFFF:192.0.2.10', '192.0.2.10'),
	'IPv4 mapped IPv6 in hex'      => array('::ffff:c000:20a', '192.0.2.10'),
	'host name'                    => array('web1.example', null),
	'empty string'                 => array('', null),
	'null'                         => array(null, null),
	'octet out of range'           => array('256.1.1.1', null),
	'IPv4 with a leading zero'     => array('192.000.002.010', null),
	'IPv6 with a bad digit'        => array('2001:db8::g', null),
	'IPv6 with too many groups'    => array('1:2:3:4:5:6:7:8:9', null),
	'brackets around a name'       => array('[web1.example]', null),
	'only brackets'                => array('[]', null),
));

it('returns an address as it is without a lookup', function (string $input, string $expected) {
	$controller = npc_address_controller();

	expect($controller->resolveAddresses($input))->toBe(array($expected));
	expect($GLOBALS['npc_address_lookups'])->toBe(array('dns' => 0, 'name' => 0));
})->with(array(
	'IPv4'            => array('192.0.2.10', '192.0.2.10'),
	'IPv6'            => array('2001:DB8::1', '2001:db8::1'),
	'bracketed IPv6'  => array('[2001:db8:0:0:0:0:0:1]', '2001:db8::1'),
));

it('returns every address of a name, IPv4 and IPv6, once', function () {
	$controller = npc_address_controller(array('web1.example' => npc_address_records(array('192.0.2.10', '2001:DB8::10', '192.0.2.11', '2001:db8:0:0:0:0:0:10', '2001:db8::11'))));

	expect($controller->resolveAddresses('web1.example'))->toBe(array('192.0.2.10', '2001:db8::10', '192.0.2.11', '2001:db8::11'));
	expect($GLOBALS['npc_address_lookups'])->toBe(array('dns' => 1, 'name' => 0));
});

it('resolves a name that only has one kind of record', function (array $records, array $expected) {
	$controller = npc_address_controller(array('web1.example' => npc_address_records($records)));

	expect($controller->resolveAddresses('web1.example'))->toBe($expected);
})->with(array(
	'IPv4 only' => array(array('192.0.2.10'), array('192.0.2.10')),
	'IPv6 only' => array(array('2001:db8::10'), array('2001:db8::10')),
));

it('ignores records that carry no usable address', function () {
	$controller = npc_address_controller(array('web1.example' => array(array('type' => 'A'), array('type' => 'AAAA', 'ipv6' => 'not an address'), array('type' => 'A', 'ip' => '192.0.2.10'))));

	expect($controller->resolveAddresses('web1.example'))->toBe(array('192.0.2.10'));
});

it('falls back to the system lookup when DNS gives no records', function (array $dns) {
	$controller = npc_address_controller($dns, array('old.example' => '192.0.2.50'));

	expect($controller->resolveAddresses('old.example'))->toBe(array('192.0.2.50'));
	expect($GLOBALS['npc_address_lookups']['name'])->toBe(1);
})->with(array(
	'the query failed'    => array(array()),
	'no records returned' => array(array('old.example' => array())),
));

it('returns nothing for a name that does not resolve', function (string $name) {
	$controller = npc_address_controller();

	expect($controller->resolveAddresses($name))->toBe(array());
})->with(array('gone.example', '', '   '));

it('does not send a query for an empty name', function () {
	$controller = npc_address_controller();

	$controller->resolveAddresses('');

	expect($GLOBALS['npc_address_lookups'])->toBe(array('dns' => 0, 'name' => 0));
});

it('passes the record types to the DNS lookup', function () {
	$seen       = array();
	$controller = new NpcSyncController();

	$controller->dnsLookup = function ($name, $type) use (&$seen) {
		$seen = array($name, $type);

		return array();
	};

	expect($controller->dnsRecords('web1.example'))->toBe(array());
	expect($seen)->toBe(array('web1.example', DNS_A | DNS_AAAA));
});

it('uses the system lookup that earlier versions used', function () {
	$controller             = new NpcSyncController();
	$controller->nameLookup = function ($name) {
		return 'looked-up:' . $name;
	};

	expect($controller->legacyLookup('web1.example'))->toBe('looked-up:web1.example');
});

/** Cacti devices as getHostnames() returns them. */
function npc_address_devices() {
	$GLOBALS['__test_db_results']['db_fetch_assoc']['FROM host'] = array(
		array('id' => '1', 'hostname' => 'web1.example'),
		array('id' => '2', 'hostname' => 'v6only.example'),
		array('id' => '3', 'hostname' => '198.51.100.7'),
		array('id' => '4', 'hostname' => '[2001:DB8:0:0:0:0:0:99]'),
		array('id' => '5', 'hostname' => 'gone.example'),
		array('id' => '6', 'hostname' => 'old.example'),
	);

	return array(
		'web1.example'   => npc_address_records(array('192.0.2.10', '2001:db8::10')),
		'v6only.example' => npc_address_records(array('2001:db8::20')),
	);
}

it('finds the Cacti device that owns an address', function (string $ip, ?string $expected) {
	$controller = npc_address_controller(npc_address_devices(), array('old.example' => '192.0.2.60'));

	list($found) = npc_address_cache_run($controller, $ip, 'k1');

	expect($found)->toBe($expected);
})->with(array(
	'IPv4 of a device with both kinds'      => array('192.0.2.10', '1'),
	'IPv6 compressed'                       => array('2001:db8::10', '1'),
	'IPv6 mixed case'                       => array('2001:DB8::10', '1'),
	'IPv6 expanded'                         => array('2001:0db8:0000:0000:0000:0000:0000:0010', '1'),
	'IPv6 in brackets'                      => array('[2001:db8::10]', '1'),
	'IPv6 of a device with only IPv6'       => array('2001:db8:0:0:0:0:0:20', '2'),
	'IPv4 literal stored as host name'      => array('198.51.100.7', '3'),
	'IPv6 literal in brackets stored'       => array('2001:db8::99', '4'),
	'device found by the system lookup'     => array('192.0.2.60', '6'),
	'a host name that resolves to a device' => array('web1.example', '1'),
	'an address no device has'              => array('192.0.2.99', null),
	'an IPv6 address no device has'         => array('2001:db8::dead', null),
	'a name that does not resolve'          => array('nobody.example', null),
	'an empty address'                      => array('', null),
));

it('rejects a shared address instead of selecting the last device', function () {
	$GLOBALS['__test_db_results']['db_fetch_assoc']['FROM host'] = array(array('id' => '1', 'hostname' => 'a.example'), array('id' => '2', 'hostname' => 'b.example'));

	$controller = npc_address_controller(array('a.example' => npc_address_records(array('192.0.2.10')), 'b.example' => npc_address_records(array('192.0.2.10'))));

	list($found) = npc_address_cache_run($controller, '192.0.2.10', 'k1');

	expect($found)->toBeFalse();
});

it('stores every address of each device in the JSON cache', function () {
	$controller = npc_address_controller(npc_address_devices());

	list($found, $base) = npc_address_cache_run($controller, '192.0.2.10', 'k1');

	$cache = json_decode(file_get_contents($base . '/cache/npc_address_cache.json'), true);

	expect($found)->toBe('1');
	expect($cache['key'])->toBe('k1');
	expect($cache['hosts']['192.0.2.10'])->toBe('1');
	expect($cache['hosts']['2001:db8::10'])->toBe('1');
	expect($cache['hosts']['2001:db8::20'])->toBe('2');
	expect($cache['hosts']['2001:db8::99'])->toBe('4');
	expect(array_keys($cache['hosts']))->not->toContain('gone.example');
});

it('reads a current map without asking Cacti or DNS again', function () {
	$controller = npc_address_controller(npc_address_devices());

	$base = sys_get_temp_dir() . '/npc-address-' . uniqid();
	mkdir($base . '/cache', 0777, true);

	$restore                        = $GLOBALS['config']['base_path'];
	$GLOBALS['config']['base_path'] = $base;

	try {
		$controller->checkHostExists('192.0.2.10', 'k1');
		$GLOBALS['__test_db_results'] = array();
		$before                       = $GLOBALS['npc_address_lookups'];

		expect($controller->checkHostExists('2001:DB8::20', 'k1'))->toBe('2');
		expect($GLOBALS['npc_address_lookups'])->toBe($before);
	} finally {
		$GLOBALS['config']['base_path'] = $restore;
	}
});

it('rebuilds the map when the cached key differs', function () {
	$controller = npc_address_controller(npc_address_devices());

	list($found, $base) = npc_address_cache_run($controller, '2001:db8::20', 'k1', function ($file) {
		file_put_contents($file, json_encode(array('version' => 2, 'key' => 'old', 'hosts' => array('192.0.2.10' => '1'))));
	});

	$cache = json_decode(file_get_contents($base . '/cache/npc_address_cache.json'), true);

	expect($found)->toBe('2');
	expect($cache['key'])->toBe('k1');
});

it('keeps identical link-local addresses on separate interfaces distinct', function () {
	$GLOBALS['__test_db_results']['db_fetch_assoc']['FROM host'] = [
		['id' => '101', 'hostname' => 'fe80::1%eth0'],
		['id' => '202', 'hostname' => 'fe80::1%eth1']
	];
	[$found] = npc_address_cache_run(npc_address_controller(), 'fe80::1%eth0', 'scope');
	expect($found)->toBe('101');
});

it('refuses ambiguous shared addresses and conflicting multihomed matches', function ($devices, $records) {
	$GLOBALS['__test_db_results']['db_fetch_assoc']['FROM host'] = $devices;
	[$found] = npc_address_cache_run(npc_address_controller(['target.example' => npc_address_records($records)]), 'target.example', 'ambiguous');
	expect($found)->toBeFalse();
})->with([
	'shared address' => [[['id' => '1', 'hostname' => '192.0.2.1'], ['id' => '2', 'hostname' => '192.0.2.1']], ['192.0.2.1']],
	'different matches' => [[['id' => '1', 'hostname' => '192.0.2.1'], ['id' => '2', 'hostname' => '192.0.2.2']], ['192.0.2.1', '192.0.2.2']]
]);

it('rebuilds a pre-scope cache even when its import key matches', function () {
	$GLOBALS['__test_db_results']['db_fetch_assoc']['FROM host'] = [['id' => '1', 'hostname' => 'fe80::1%eth0']];
	[$found] = npc_address_cache_run(npc_address_controller(), 'fe80::1%eth0', 'same', function ($file) {
		file_put_contents($file, json_encode(['key' => 'same', 'hosts' => ['fe80::1' => '2']]));
	});
	expect($found)->toBe('1');
});
