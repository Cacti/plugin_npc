<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * npc.php copies a fixed set of request values onto the controller. PHP 8.2
 * deprecates creating a property that the class does not declare, so every
 * name in that set has to be declared on the base controller.
 */

beforeAll(function () {
	require_once dirname(__DIR__, 2) . '/controllers/controller.php';
});

it('declares every property npc.php assigns from the request', function () {
	preg_match("/\\\$request_properties = array\\(([^)]*)\\)/", file_get_contents(dirname(__DIR__, 2) . '/npc.php'), $match);
	preg_match_all("/'(\\w+)'/", $match[1], $names);

	expect($names[1])->not->toBe(array());

	$controller = new Controller();

	foreach ($names[1] as $name) {
		expect(property_exists($controller, $name))->toBeTrue($name . ' is not declared');
	}
});

it('treats the optional listing filters as unset until a request sets them', function () {
	$controller = new Controller();

	expect(isset($controller->hostgroup))->toBeFalse();
	expect(isset($controller->unhandled))->toBeFalse();
});
