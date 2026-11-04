<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for Controller::perfdataOutput(), which reduces the perfdata
 * of a check to the label:value pairs a data input method reads.
 */

beforeAll(function () {
	require_once dirname(__DIR__, 2) . '/controllers/controller.php';
});

it('reduces perfdata to label and value pairs', function (?string $perfdata, string $expected) {
	expect((new Controller())->perfdataOutput($perfdata))->toBe($expected);
})->with(array(
	'one value'                  => array('rta=0.5ms;100;200;0', 'rta:0.5 '),
	'several values'             => array('load1=0.52;1;2 load5=0.4;1;2', 'load1:0.52 load5:0.4 '),
	'negative and signed values' => array('temp=-3.5C hum=+40%', 'temp:-3.5 hum:+40 '),
	'unknown value is left out'  => array('load1=U;1;2 load5=0.4', 'load5:0.4 '),
	'text without a value'       => array('OK - all fine', ''),
	'iso label is renamed'       => array('iso.1.3=7', 'output:7 '),
	'empty string'               => array('', ''),
	'null'                       => array(null, ''),
));
