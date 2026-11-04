<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Cacti's database layer sends bound values to MariaDB as strings, and a
 * quoted LIMIT value is a syntax error. The listings therefore write the
 * paging values into the statement as integers. tests/Integration/
 * NpcDatabaseTest.php runs the same statements against MariaDB.
 */

beforeAll(function () {
	npc_test_stub_cacti_includes();
	require_once dirname(__DIR__, 2) . '/controllers/controller.php';

	foreach (array('hosts', 'services', 'comments', 'downtime', 'logentries', 'notifications', 'statehistory') as $controller) {
		npc_test_load(dirname(__DIR__, 2) . '/controllers/' . $controller . '.php');
	}
});

beforeEach(function () {
	$GLOBALS['__test_db_fetch_log'] = array();
});

it('builds the clause for the first and later pages', function () {
	$controller = new Controller();

	expect($controller->limitClause())->toBe(' LIMIT 0, 25');

	$controller->limit       = 10;
	$controller->currentPage = 3;
	expect($controller->limitClause())->toBe(' LIMIT 20, 10');
});

it('keeps the clause numeric whatever the properties hold', function () {
	$controller              = new Controller();
	$controller->limit       = '7 OR 1';
	$controller->currentPage = '2x';

	expect($controller->limitClause())->toBe(' LIMIT 7, 7');

	$controller->limit       = 0;
	$controller->currentPage = -4;
	expect($controller->limitClause())->toBe(' LIMIT 0, 1');
});

it('writes the paging values into every listing query', function (string $class, string $method) {
	$controller = new $class();
	$controller->$method();

	$selects = array_values(array_filter($GLOBALS['__test_db_fetch_log'], function ($query) {
		return strpos($query['sql'], 'COUNT(*)') === false;
	}));

	expect($selects)->not->toBe(array());
	expect($selects[0]['sql'])->toMatch('/ LIMIT 0, 25$/');
	expect($selects[0]['sql'])->not->toContain('LIMIT ?');
})->with(array(
	'hosts'         => array('NpcHostsController', 'hosts'),
	'services'      => array('NpcServicesController', 'services'),
	'comments'      => array('NpcCommentsController', 'comments'),
	'downtime'      => array('NpcDowntimeController', 'downtimeHistory'),
	'log entries'   => array('NpcLogentriesController', 'getLogs'),
	'notifications' => array('NpcNotificationsController', 'getNotifications'),
	'state history' => array('NpcStatehistoryController', 'getStateHistory'),
));
