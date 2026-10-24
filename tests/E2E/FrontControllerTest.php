<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * End-to-end coverage of npc.php, the front controller every NPC request goes
 * through. Each test runs the real npc.php and the real controllers in a PHP
 * subprocess; tests/E2E/support/front-controller-auth.php replaces only the
 * session, the database and the page chrome, and Cacti's own request helpers
 * do the validation and sanitizing.
 */

/**
 * Run npc.php for one request.
 *
 * @param array $request method, get, post, rows (database fixtures), options
 *
 * @return array stdout, the recorded calls, the sandbox path
 */
function npc_e2e_run(array $request) {
	$cacti = $GLOBALS['config']['base_path'];
	$base  = sys_get_temp_dir() . '/npc-e2e-' . uniqid();
	$npc   = $base . '/plugins/npc';

	mkdir($npc, 0777, true);
	mkdir($base . '/include', 0777, true);
	copy(dirname(__DIR__, 2) . '/npc.php', $npc . '/npc.php');
	copy(__DIR__ . '/support/front-controller-auth.php', $base . '/include/auth.php');

	foreach (array('controllers', 'includes', 'nagioscmd.php', 'setup.php', 'INFO') as $entry) {
		symlink(dirname(__DIR__, 2) . '/' . $entry, $npc . '/' . $entry);
	}

	$scenario = array_merge(array(
		'method'     => 'GET',
		'get'        => array(),
		'post'       => array(),
		'rows'       => array(),
		'options'    => array('npc_config_type' => '1'),
		'cacti_path' => $cacti,
		'base_path'  => $base,
		'log'        => $base . '/calls.log',
	), $request);

	if ($scenario['method'] === 'POST') {
		$scenario['post'] = $scenario['post'] + $scenario['get'];
	}

	$process = proc_open(
		array(PHP_BINARY, '-d', 'display_errors=stderr', 'npc.php'),
		array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')),
		$pipes,
		$npc,
		array('NPC_E2E' => json_encode($scenario))
	);

	$stdout = stream_get_contents($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);
	proc_close($process);

	$calls = array();

	if (is_file($scenario['log'])) {
		foreach (array_filter(explode("\n", file_get_contents($scenario['log']))) as $line) {
			$calls[] = json_decode($line, true);
		}
	}

	return array('stdout' => $stdout, 'stderr' => $stderr, 'calls' => $calls, 'base' => $base);
}

function npc_e2e_calls(array $run, $kind) {
	return array_values(array_filter($run['calls'], function ($call) use ($kind) {
		return $call['kind'] === $kind;
	}));
}

it('serves the frame for a known tab', function () {
	$run = npc_e2e_run(array('get' => array('module' => 'layout', 'action' => 'drawFrame', 'tab' => 'hosts')));

	expect($run['stderr'])->toBe('');
	expect($run['stdout'])->toContain("currentTab:       'hosts'");
});

it('does not echo an unknown tab value into the frame script', function () {
	$run = npc_e2e_run(array('get' => array('module' => 'layout', 'action' => 'drawFrame', 'tab' => 'x</script><b>breakout</b>')));

	expect($run['stdout'])->toContain("currentTab:       'dashboard'");
	expect($run['stdout'])->not->toContain('breakout');
});
