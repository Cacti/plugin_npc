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
	function npc_db_insert($pdo, $table, array $row) {
		$columns = '`' . implode('`, `', array_keys($row)) . '`';
		$marks   = implode(', ', array_fill(0, count($row), '?'));

		$pdo->prepare('INSERT INTO `' . $table . '` (' . $columns . ') VALUES (' . $marks . ')')->execute(array_values($row));
	}
}
