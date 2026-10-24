<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Test helper: connection to the test database. Only function definitions, so
 * it does nothing if a web server is asked for it.
 *
 * The server comes from NPC_TEST_DB_HOST, NPC_TEST_DB_PORT, NPC_TEST_DB_NAME,
 * NPC_TEST_DB_USER and NPC_TEST_DB_PASS, or else from the database settings in
 * the include/config.php of the Cacti tree the plugin sits in.
 */

if (!function_exists('npc_db_settings')) {
	/**
	 * @return array|null host, port, name, user and password of the test database
	 */
	function npc_db_settings() {
		if (getenv('NPC_TEST_DB_HOST') !== false) {
			return array(
				'host' => getenv('NPC_TEST_DB_HOST'),
				'port' => getenv('NPC_TEST_DB_PORT') ?: '3306',
				'name' => getenv('NPC_TEST_DB_NAME') ?: 'cacti',
				'user' => getenv('NPC_TEST_DB_USER') ?: 'cactiuser',
				'pass' => getenv('NPC_TEST_DB_PASS') !== false ? getenv('NPC_TEST_DB_PASS') : 'cactiuser',
			);
		}

		$file = $GLOBALS['config']['base_path'] . '/include/config.php';

		if (!is_readable($file)) {
			return null;
		}

		$text     = (string) file_get_contents($file);
		$settings = array();

		foreach (array('host' => 'hostname', 'port' => 'port', 'name' => 'default', 'user' => 'username', 'pass' => 'password') as $key => $variable) {
			if (preg_match('/^\s*\$database_' . $variable . '\s*=\s*[\'"]([^\'"]*)[\'"]/m', $text, $match)) {
				$settings[$key] = $match[1];
			}
		}

		return isset($settings['host'], $settings['name'], $settings['user']) ? $settings + array('port' => '3306', 'pass' => '') : null;
	}
}

if (!function_exists('npc_db_connect')) {
	function npc_db_connect() {
		$settings = npc_db_settings();

		if ($settings === null) {
			return null;
		}

		try {
			$pdo = new PDO(
				'mysql:host=' . $settings['host'] . ';port=' . $settings['port'] . ';dbname=' . $settings['name'] . ';charset=utf8',
				$settings['user'],
				$settings['pass'],
				array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 2)
			);
		} catch (PDOException $e) {
			return null;
		}

		/* Cacti runs its sessions without the strict modes. */
		$pdo->exec("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");

		return $pdo;
	}
}

if (!function_exists('npc_db_insert')) {
	/**
	 * Insert one fixture row with one of the fixed statements below.
	 *
	 * @param PDO    $pdo       connection
	 * @param string $statement key of npc_db_statements()
	 * @param array  $values    values in the order of the statement's columns
	 */
	function npc_db_insert($pdo, $statement, array $values) {
		$statements = array(
			'user'              => 'INSERT INTO user_auth (username, password, realm, must_change_password, password_change, enabled, locked) VALUES (?, ?, ?, ?, ?, ?, ?)',
			'user_realm'        => 'INSERT INTO user_auth_realm (realm_id, user_id) VALUES (?, ?)',
			'instance'          => 'INSERT INTO npc_instances (instance_id, instance_name) VALUES (?, ?)',
			'object'            => 'INSERT INTO npc_objects (object_id, instance_id, objecttype_id, name1, name2) VALUES (?, ?, ?, ?, ?)',
			'comment'           => 'INSERT INTO npc_comments (instance_id, object_id, comment_data, author_name) VALUES (?, ?, ?, ?)',
			'log_entry'         => 'INSERT INTO npc_logentries (instance_id, logentry_time, entry_time, entry_time_usec, logentry_data) VALUES (?, ?, ?, ?, ?)',
			'host'              => 'INSERT INTO npc_hosts (instance_id, config_type, host_object_id, alias) VALUES (?, ?, ?, ?)',
			'service'           => 'INSERT INTO npc_services (instance_id, config_type, host_object_id, service_object_id) VALUES (?, ?, ?, ?)',
			'service_status'    => 'INSERT INTO npc_servicestatus (instance_id, service_object_id, current_state) VALUES (?, ?, ?)',
			'hostgroup'         => 'INSERT INTO npc_hostgroups (hostgroup_id, instance_id, config_type, hostgroup_object_id, alias) VALUES (?, ?, ?, ?, ?)',
			'hostgroup_member'  => 'INSERT INTO npc_hostgroup_members (instance_id, hostgroup_id, host_object_id) VALUES (?, ?, ?)',
			'host_check'        => 'INSERT INTO npc_hostchecks (instance_id, host_object_id, start_time, perfdata) VALUES (?, ?, ?, ?)',
			'user_settings'     => 'INSERT INTO npc_settings (user_id, settings) VALUES (?, ?)',
		);

		$pdo->prepare($statements[$statement])->execute($values);
	}
}
