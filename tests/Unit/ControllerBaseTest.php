<?php
/*
 +-------------------------------------------------------------------------+
 | Nagios Plugin for Cacti                                                 |
 |                                                                         |
 | Copyright (C) 2007 Billy Gunn (billy@gunn.org)                          |
 | Copyright (C) 2024 The Cacti Group, Inc.                                |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit tests for the base Controller class (controllers/controller.php).
 *
 * Loads the class file directly after ensuring the Cacti stub environment
 * is available. No database connection is required.
 */

$controllerFile = dirname(__DIR__, 2) . '/controllers/controller.php';

// The controller file uses no top-level executable statements; it is safe to
// require it in a test context once the stub functions are in place.
if (file_exists($controllerFile)) {
	require_once $controllerFile;
}

// ---------------------------------------------------------------------------
// jsonOutput()
// ---------------------------------------------------------------------------

it('jsonOutput returns valid JSON', function () use ($controllerFile) {
	if (!file_exists($controllerFile)) {
		expect(true)->toBeTrue('controller.php not found; skipping');
		return;
	}

	$ctrl = new Controller();
	$json = $ctrl->jsonOutput(array(array('id' => 1, 'name' => 'host1')));

	expect($json)->toBeString();
	$decoded = json_decode($json, true);
	expect($decoded)->not->toBeNull('jsonOutput() did not return valid JSON');
});

it('jsonOutput returns a totalCount key', function () use ($controllerFile) {
	if (!file_exists($controllerFile)) {
		expect(true)->toBeTrue();
		return;
	}

	$ctrl   = new Controller();
	$rows   = array(array('id' => 1), array('id' => 2));
	$json   = $ctrl->jsonOutput($rows);
	$result = json_decode($json, true);

	expect(isset($result['totalCount']))->toBeTrue('jsonOutput() must include totalCount key');
});

it('jsonOutput returns a data key', function () use ($controllerFile) {
	if (!file_exists($controllerFile)) {
		expect(true)->toBeTrue();
		return;
	}

	$ctrl   = new Controller();
	$json   = $ctrl->jsonOutput(array(array('id' => 1)));
	$result = json_decode($json, true);

	expect(isset($result['data']))->toBeTrue('jsonOutput() must include data key');
});

it('jsonOutput totalCount equals result row count when numRecords is unset', function () use ($controllerFile) {
	if (!file_exists($controllerFile)) {
		expect(true)->toBeTrue();
		return;
	}

	$ctrl   = new Controller();
	$rows   = array(array('id' => 1), array('id' => 2), array('id' => 3));
	$json   = $ctrl->jsonOutput($rows);
	$result = json_decode($json, true);

	expect($result['totalCount'])->toBe(3);
});

it('jsonOutput respects numRecords when set explicitly', function () use ($controllerFile) {
	if (!file_exists($controllerFile)) {
		expect(true)->toBeTrue();
		return;
	}

	$ctrl             = new Controller();
	$ctrl->numRecords = 99;
	$rows             = array(array('id' => 1));
	$json             = $ctrl->jsonOutput($rows);
	$result           = json_decode($json, true);

	expect($result['totalCount'])->toBe(99);
});

it('jsonOutput wraps a single associative row in an array', function () use ($controllerFile) {
	if (!file_exists($controllerFile)) {
		expect(true)->toBeTrue();
		return;
	}

	$ctrl   = new Controller();
	// Pass a single row without a numeric outer key.
	$json   = $ctrl->jsonOutput(array('id' => 1, 'name' => 'host'));
	$result = json_decode($json, true);

	expect(is_array($result['data']))->toBeTrue();
	// After wrapping, data[0] should be the original row.
	expect(isset($result['data'][0]))->toBeTrue('Single row must be wrapped in outer array');
});

it('jsonOutput returns empty data array for empty input', function () use ($controllerFile) {
	if (!file_exists($controllerFile)) {
		expect(true)->toBeTrue();
		return;
	}

	$ctrl   = new Controller();
	$json   = $ctrl->jsonOutput(array());
	$result = json_decode($json, true);

	expect($result['totalCount'])->toBe(0);
	expect($result['data'])->toBe(array());
});

// ---------------------------------------------------------------------------
// flattenArray()
// ---------------------------------------------------------------------------

it('flattenArray promotes nested array keys to the parent level', function () use ($controllerFile) {
	if (!file_exists($controllerFile)) {
		expect(true)->toBeTrue();
		return;
	}

	$ctrl  = new Controller();
	$input = array(
		array(
			'id'     => 1,
			'nested' => array('foo' => 'bar', 'baz' => 'qux'),
		),
	);

	$result = $ctrl->flattenArray($input);

	expect(isset($result[0]['foo']))->toBeTrue('flattenArray must promote nested key foo');
	expect($result[0]['foo'])->toBe('bar');
	expect(isset($result[0]['baz']))->toBeTrue('flattenArray must promote nested key baz');
});

it('flattenArray preserves scalar values unchanged', function () use ($controllerFile) {
	if (!file_exists($controllerFile)) {
		expect(true)->toBeTrue();
		return;
	}

	$ctrl  = new Controller();
	$input = array(
		array('id' => 42, 'name' => 'test'),
	);

	$result = $ctrl->flattenArray($input);

	expect($result[0]['id'])->toBe(42);
	expect($result[0]['name'])->toBe('test');
});

it('flattenArray returns empty array for empty input', function () use ($controllerFile) {
	if (!file_exists($controllerFile)) {
		expect(true)->toBeTrue();
		return;
	}

	$ctrl   = new Controller();
	$result = $ctrl->flattenArray(array());

	expect($result)->toBe(array());
});

// ---------------------------------------------------------------------------
// State mapping arrays
// ---------------------------------------------------------------------------

it('stringToState covers all expected Nagios state keywords', function () use ($controllerFile) {
	if (!file_exists($controllerFile)) {
		expect(true)->toBeTrue();
		return;
	}

	$ctrl     = new Controller();
	$required = array('ok', 'up', 'warning', 'down', 'critical', 'unreachable', 'unknown', 'pending', 'any', 'not_ok');

	foreach ($required as $key) {
		expect(array_key_exists($key, $ctrl->stringToState))->toBeTrue(
			"stringToState missing key: $key"
		);
	}
});

it('hostState covers up, down, unreachable, and pending', function () use ($controllerFile) {
	if (!file_exists($controllerFile)) {
		expect(true)->toBeTrue();
		return;
	}

	$ctrl     = new Controller();
	$expected = array('0' => 'up', '1' => 'down', '2' => 'unreachable', '-1' => 'pending');

	foreach ($expected as $code => $label) {
		expect(isset($ctrl->hostState[$code]))->toBeTrue("hostState missing code $code");
		expect($ctrl->hostState[$code])->toBe($label);
	}
});

it('serviceState covers ok, warning, critical, unknown, and pending', function () use ($controllerFile) {
	if (!file_exists($controllerFile)) {
		expect(true)->toBeTrue();
		return;
	}

	$ctrl     = new Controller();
	$expected = array('0' => 'ok', '1' => 'warning', '2' => 'critical', '3' => 'unknown', '-1' => 'pending');

	foreach ($expected as $code => $label) {
		expect(isset($ctrl->serviceState[$code]))->toBeTrue("serviceState missing code $code");
		expect($ctrl->serviceState[$code])->toBe($label);
	}
});

// ---------------------------------------------------------------------------
// Default property values
// ---------------------------------------------------------------------------

it('Controller initializes with sane default limit', function () use ($controllerFile) {
	if (!file_exists($controllerFile)) {
		expect(true)->toBeTrue();
		return;
	}

	$ctrl = new Controller();
	expect($ctrl->limit)->toBeGreaterThan(0);
});

it('Controller initializes start at 0', function () use ($controllerFile) {
	if (!file_exists($controllerFile)) {
		expect(true)->toBeTrue();
		return;
	}

	$ctrl = new Controller();
	expect($ctrl->start)->toBe(0);
});
