<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * End-to-end coverage of the command line scripts Cacti data input methods
 * run to read Nagios perfdata. Each test runs the real script in a PHP
 * subprocess against the sandbox described in tests/E2E/support/harness.php.
 */

require_once __DIR__ . '/support/harness.php';

$npc_perfdata_rows = array(
	'db_fetch_cell_prepared'  => array('MAX(servicecheck_id)' => 5),
	'db_fetch_assoc_prepared' => array('FROM npc_servicechecks' => array(
		array('perfdata' => 'load1=0.52;1;2 load5=U;1;2 load15=0.4 iso.1.3=7'),
	)),
);

it('prints the numeric perfdata values of a service', function (string $script, array $args) use ($npc_perfdata_rows) {
	$run = npc_e2e_run(array('script' => $script, 'args' => $args, 'rows' => $npc_perfdata_rows));

	expect($run['stderr'])->toBe('');
	expect($run['stdout'])->toBe("load1:0.52 load15:0.4 output:7 \n");
})->with(array(
	'perfdata.php' => array('perfdata.php', array('--type=service', '--id=5')),
	'cli.php'      => array('cli.php', array('--perfdata', '--type=service', '--id=5')),
));

it('prints an empty line when no check has been stored', function (string $script, array $args) {
	$run = npc_e2e_run(array('script' => $script, 'args' => $args));

	expect($run['stderr'])->toBe('');
	expect($run['stdout'])->toBe("\n");
})->with(array(
	'perfdata.php' => array('perfdata.php', array('--type=service', '--id=5')),
	'cli.php'      => array('cli.php', array('--perfdata', '--type=host', '--id=5')),
));

it('reads the newest host check for a host', function () {
	$run = npc_e2e_run(array(
		'script' => 'perfdata.php',
		'args'   => array('--type=host', '--id=12'),
		'rows'   => array('db_fetch_assoc_prepared' => array('FROM npc_hostchecks' => array(array('perfdata' => 'rta=0.5ms;100;200;0 pl=0%')))),
	));

	expect($run['stderr'])->toBe('');
	expect($run['stdout'])->toBe("rta:0.5 pl:0 \n");
});
