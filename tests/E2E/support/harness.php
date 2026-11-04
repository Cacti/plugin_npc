<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Test helper: runs one of the plugin's entry scripts in a PHP subprocess
 * against a sandbox tree. Only function definitions, so it does nothing if a
 * web server is asked for it.
 */

if (!function_exists('npc_e2e_run')) {
	/**
	 * Run an entry script (npc.php by default) for one request.
	 *
	 * @param array $request method, get, post, rows (database fixtures),
	 *                       options, and for the CLI scripts script and args
	 *
	 * @return array stdout, stderr, the recorded calls and the sandbox path
	 */
	function npc_e2e_run(array $request) {
		$root   = dirname(__DIR__, 3);
		$base   = sys_get_temp_dir() . '/npc-e2e-' . uniqid();
		$npc    = $base . '/plugins/npc';
		$script = isset($request['script']) ? $request['script'] : 'npc.php';
		$args   = isset($request['args']) ? $request['args'] : array();

		mkdir($npc, 0700, true);
		mkdir($base . '/include', 0700, true);
		copy($root . '/' . $script, $npc . '/' . $script);
		copy(__DIR__ . '/front-controller-auth.php', $base . '/include/auth.php');
		copy(__DIR__ . '/front-controller-auth.php', $base . '/include/cli_check.php');

		foreach (array('controllers', 'includes', 'nagioscmd.php', 'setup.php', 'config.php', 'INFO') as $entry) {
			symlink($root . '/' . $entry, $npc . '/' . $entry);
		}

		$scenario = array_merge(array(
			'method'     => 'GET',
			'get'        => array(),
			'post'       => array(),
			'rows'       => array(),
			'options'    => array('npc_config_type' => '1'),
			'cacti_path' => $GLOBALS['config']['base_path'],
			'base_path'  => $base,
			'log'        => $base . '/calls.log',
		), $request);

		if ($scenario['method'] === 'POST') {
			$scenario['post'] = $scenario['post'] + $scenario['get'];
		}

		$environment = array('NPC_E2E' => json_encode($scenario));
		$options     = array('-d', 'display_errors=stderr');

		if (getenv('NPC_COVERAGE_DIR') !== false) {
			$environment += npc_e2e_coverage_environment();
			$options      = array_merge($options, npc_e2e_coverage_options());
		}

		$process = proc_open(
			array_merge(array(PHP_BINARY), $options, array($script), $args),
			array(1 => array('pipe', 'w'), 2 => array('file', $base . '/stderr.txt', 'w')),
			$pipes,
			$npc,
			$environment
		);

		$stdout = stream_get_contents($pipes[1]);
		proc_close($process);

		$calls = array();

		if (is_file($scenario['log'])) {
			foreach (array_filter(explode("\n", file_get_contents($scenario['log']))) as $line) {
				$calls[] = json_decode($line, true);
			}
		}

		return array('stdout' => $stdout, 'stderr' => (string) file_get_contents($base . '/stderr.txt'), 'calls' => $calls, 'base' => $base);
	}

	/**
	 * Environment that makes a subprocess record line coverage when the test
	 * process was started with NPC_COVERAGE_DIR set (see coverage-prepend.php).
	 * The label is the name of the running test.
	 */
	function npc_e2e_coverage_environment() {
		$label = 'unlabelled';

		foreach (debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 20) as $frame) {
			if (isset($frame['object']) && $frame['object'] instanceof \PHPUnit\Framework\TestCase) {
				$label = $frame['object']->name();

				break;
			}
		}

		return array('NPC_COVERAGE_DIR' => getenv('NPC_COVERAGE_DIR'), 'NPC_COVERAGE_LABEL' => $label);
	}

	function npc_e2e_coverage_options() {
		return array('-d', 'xdebug.mode=coverage', '-d', 'auto_prepend_file=' . __DIR__ . '/coverage-prepend.php');
	}

	function npc_e2e_calls(array $run, $kind) {
		return array_values(array_filter($run['calls'], function ($call) use ($kind) {
			return $call['kind'] === $kind;
		}));
	}
}
