<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Test helper: runs the Cacti tree the plugin is installed in under PHP's
 * built-in web server and talks to it with real HTTP requests and real
 * sessions. Only function definitions, so it does nothing if a web server is
 * asked for it.
 *
 * The server listens on 127.0.0.1 on a free port for the length of the test
 * run. When the test process was started with NPC_COVERAGE_DIR set, the server
 * records line coverage per request (see coverage-prepend.php); each request
 * names the running test in the X-Npc-Test header, which is only ever used to
 * build a file name.
 */

if (!function_exists('npc_server_url_path')) {
	/**
	 * The $url_path of the Cacti install, which is also the path of the session
	 * cookie.
	 */
	function npc_server_url_path() {
		$file = $GLOBALS['config']['base_path'] . '/include/config.php';
		$text = is_readable($file) ? (string) file_get_contents($file) : '';

		if (preg_match('/^\s*\$url_path\s*=\s*[\'"]([^\'"]*)[\'"]/m', $text, $match)) {
			$path = trim($match[1], '/');

			return $path === '' ? '/' : '/' . $path . '/';
		}

		return '/';
	}

	/**
	 * Start the server. Returns a handle for npc_server_stop() or null when the
	 * server could not be started.
	 */
	function npc_server_start() {
		$root     = $GLOBALS['config']['base_path'];
		$prefix   = trim(npc_server_url_path(), '/');
		$docroot  = $root;
		$listener = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);

		if ($listener === false) {
			return null;
		}

		$port = (int) substr(strrchr(stream_socket_get_name($listener, false), ':'), 1);
		fclose($listener);

		if ($prefix !== '') {
			$docroot = sys_get_temp_dir() . '/npc-docroot-' . uniqid();
			mkdir($docroot, 0700, true);
			symlink($root, $docroot . '/' . $prefix);
		}

		$options = array('-d', 'display_errors=0');
		$env     = array();

		if (getenv('NPC_COVERAGE_DIR') !== false) {
			$options = array_merge($options, npc_e2e_coverage_options());
			$env     = array('NPC_COVERAGE_DIR' => getenv('NPC_COVERAGE_DIR'), 'NPC_COVERAGE_LABEL' => 'server');
		}

		$process = proc_open(
			array_merge(array(PHP_BINARY), $options, array('-S', '127.0.0.1:' . $port, '-t', $docroot)),
			array(1 => array('file', sys_get_temp_dir() . '/npc-server-' . $port . '.out', 'w'), 2 => array('file', sys_get_temp_dir() . '/npc-server-' . $port . '.err', 'w')),
			$pipes,
			$root,
			$env + array('PATH' => (string) getenv('PATH'))
		);

		for ($try = 0; $try < 50; $try++) {
			$socket = @fsockopen('127.0.0.1', $port, $errno, $error, 0.2);

			if ($socket !== false) {
				fclose($socket);

				return array('process' => $process, 'base' => '127.0.0.1:' . $port . npc_server_url_path(), 'docroot' => $docroot);
			}

			usleep(100000);
		}

		proc_terminate($process);

		return null;
	}

	function npc_server_stop($server) {
		if ($server !== null) {
			proc_terminate($server['process']);
			proc_close($server['process']);
		}
	}

	/**
	 * One HTTP request. $jar is a cookie file; the label names the running test
	 * for the coverage files.
	 *
	 * @return array status, body and the Location header
	 */
	function npc_http($server, $method, $path, array $fields, $jar, $label = 'request') {
		$url = $server['base'] . $path;

		if ($method !== 'POST' && $fields) {
			$url .= (strpos($path, '?') === false ? '?' : '&') . http_build_query($fields);
		}

		$curl = curl_init($url);

		curl_setopt_array($curl, array(
			CURLOPT_CUSTOMREQUEST  => $method,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HEADER         => true,
			CURLOPT_COOKIEJAR      => $jar,
			CURLOPT_COOKIEFILE     => $jar,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_TIMEOUT        => 30,
			CURLOPT_HTTPHEADER     => array('X-Npc-Test: ' . $label),
		));

		if ($method === 'POST') {
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
		}

		$response = (string) curl_exec($curl);
		$status   = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$split    = (int) curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		curl_close($curl);

		$head = substr($response, 0, $split);

		return array(
			'status'   => $status,
			'body'     => substr($response, $split),
			'location' => preg_match('/^Location:\s*(\S+)/mi', $head, $match) ? $match[1] : null,
		);
	}

	/**
	 * Log in through Cacti's login form and return the cookie file, or null when
	 * the login did not give a session.
	 */
	function npc_http_login($server, $username, $password, $label = 'login') {
		$jar   = sys_get_temp_dir() . '/npc-jar-' . uniqid();
		$login = npc_http($server, 'GET', 'plugins/npc/npc.php', array(), $jar, $label);

		if (!preg_match('/name=.__csrf_magic. value="([^"]+)"/', $login['body'], $token)) {
			return null;
		}

		$result = npc_http($server, 'POST', 'plugins/npc/npc.php', array('action' => 'login', 'login_username' => $username, 'login_password' => $password, '__csrf_magic' => $token[1]), $jar, $label);

		return $result['status'] === 302 ? $jar : null;
	}

	/** The CSRF token the NPC frame hands to its script. */
	function npc_http_token($server, $jar, $label = 'token') {
		$frame = npc_http($server, 'GET', 'plugins/npc/npc.php', array('module' => 'layout', 'action' => 'drawFrame'), $jar, $label);

		return preg_match('/csrfToken:\s+"([^"]+)"/', $frame['body'], $match) ? $match[1] : null;
	}
}
