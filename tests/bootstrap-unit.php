<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

/*
 * Test bootstrap.
 *
 * NPC's sources expect to be included by Cacti, which has already
 * defined the db_*, request-variable, and logging helpers as plain global
 * functions. Nothing here talks to a database or a network: each Cacti
 * function is declared as a stub that records the call in
 * $GLOBALS['__test_db_calls'] and hands back a safe default.
 *
 * The CI workflow checks out a pinned Cacti release next to this plugin so
 * Pest runs against Cacti's own Composer-managed vendor tree (Pest/PHPUnit)
 * instead of a vendor tree local to this plugin. The version check below
 * makes sure that checkout actually matches what tests/.cacti-version
 * expects before any plugin source is loaded.
 *
 * Guarding every declaration with function_exists() keeps this file usable
 * if a future integration suite loads real Cacti first.
 */

$cacti_root = dirname(__DIR__, 3);
$autoload   = $cacti_root . '/include/vendor/autoload.php';
$version    = $cacti_root . '/include/cacti_version';
$expected   = __DIR__ . '/.cacti-version';

if (!is_readable($autoload)) {
	throw new RuntimeException("Cacti Composer autoloader is not readable: $autoload");
}

if (!is_readable($version)) {
	throw new RuntimeException("Cacti version file is not readable: $version");
}

if (!is_readable($expected)) {
	throw new RuntimeException("Expected Cacti version file is not readable: $expected");
}

$cacti_version     = trim((string) file_get_contents($version));
$expected_version  = trim((string) file_get_contents($expected));

if ($cacti_version === '') {
	throw new RuntimeException("Cacti version file is empty: $version");
}

if ($expected_version === '') {
	throw new RuntimeException("Expected Cacti version file is empty: $expected");
}

// The CI workflow tracks a moving branch (1.2.x or develop) rather than a pinned release, so any actual version is accepted.
if (!in_array($expected_version, array('1.2.x', 'develop'), true) && $cacti_version !== $expected_version) {
	throw new RuntimeException("Expected Cacti $expected_version, found $cacti_version in $version");
}

require_once $autoload;

/*
 * base_path has to point at the Cacti root two levels above this plugin:
 * npc's source files build include paths from it at runtime.
 */
$GLOBALS['config'] = array(
	'base_path'       => $cacti_root,
	'url_path'        => '/cacti/',
	'cacti_version'   => $cacti_version,
	'cacti_server_os' => 'unix',
);

$GLOBALS['__test_db_calls'] = array();
$GLOBALS['__test_db_results'] = array();
$GLOBALS['__test_db_fetch_log'] = array();

/**
 * Result for a db_fetch_* stub.
 *
 * A test maps a substring of the SQL to the rows it wants back, per stub:
 * $GLOBALS['__test_db_results']['db_fetch_assoc']['FROM user_auth'] = array(...).
 * Every fetch is logged so a test can assert the bound parameters.
 */
function npc_test_db_result($fn, $sql, $params, $default) {
	$GLOBALS['__test_db_fetch_log'][] = array('fn' => $fn, 'sql' => $sql, 'params' => $params);

	if (isset($GLOBALS['__test_pdo'])) {
		return npc_test_pdo_fetch($GLOBALS['__test_pdo'], $fn, $sql, $params);
	}

	foreach (($GLOBALS['__test_db_results'][$fn] ?? array()) as $match => $result) {
		if (strpos($sql, (string) $match) !== false) {
			return $result;
		}
	}

	return $default;
}

/**
 * Run a fetch against a real connection, the way Cacti's database layer does:
 * the statement is prepared with PDO's defaults and the values are handed to
 * execute() as an array, so every value reaches the server as a string.
 * Integration tests set $GLOBALS['__test_pdo'] to turn this on.
 */
function npc_test_pdo_fetch($pdo, $fn, $sql, $params) {
	$statement = $pdo->prepare($sql);
	$statement->execute(array_values($params));

	if (strpos($fn, 'assoc') !== false) {
		return $statement->fetchAll(PDO::FETCH_ASSOC);
	}

	if (strpos($fn, 'row') !== false) {
		$row = $statement->fetch(PDO::FETCH_ASSOC);

		return $row === false ? array() : $row;
	}

	$value = $statement->fetchColumn();

	return $value === false ? '' : $value;
}

/**
 * Make require_once('include/auth.php') resolve to an empty stub, ahead of
 * the real Cacti file that the working directory would otherwise supply.
 */
function npc_test_stub_cacti_includes() {
	static $dir = null;

	if ($dir === null) {
		$dir = sys_get_temp_dir() . '/npc-inc-' . uniqid();
		mkdir($dir . '/include', 0777, true);
		file_put_contents($dir . '/include/auth.php', "<?php\n");
		set_include_path($dir . PATH_SEPARATOR . $GLOBALS['config']['base_path'] . PATH_SEPARATOR . get_include_path());
	}
}

if (!function_exists('get_md5_include_js')) {
	function get_md5_include_js($path, $async = false) {
		return "<script type='text/javascript' src='" . $path . "'></script>";
	}
}

if (!function_exists('get_md5_include_css')) {
	function get_md5_include_css($path) {
		return "<link href='" . $path . "' type='text/css' rel='stylesheet'>";
	}
}

if (!function_exists('db_execute')) {
	function db_execute($sql, $log = true, $db_conn = false) {
		$GLOBALS['__test_db_calls'][] = array('fn' => 'db_execute', 'sql' => $sql, 'params' => array());

		if (isset($GLOBALS['__test_pdo'])) {
			$GLOBALS['__test_pdo']->exec($sql);
		}

		return true;
	}
}

if (!function_exists('db_execute_prepared')) {
	function db_execute_prepared($sql, $params = array(), $log = true, $db_conn = false, $execute_name = 'Exec', $default_value = true) {
		$GLOBALS['__test_db_calls'][] = array('fn' => 'db_execute_prepared', 'sql' => $sql, 'params' => $params);

		if (isset($GLOBALS['__test_pdo'])) {
			$GLOBALS['__test_pdo']->prepare($sql)->execute(array_values($params));
		}

		return true;
	}
}

if (!function_exists('db_fetch_assoc')) {
	function db_fetch_assoc($sql, $log = true, $db_conn = false) {
		return npc_test_db_result('db_fetch_assoc', $sql, array(), array());
	}
}

if (!function_exists('db_fetch_assoc_prepared')) {
	function db_fetch_assoc_prepared($sql, $params = array(), $log = true, $db_conn = false) {
		return npc_test_db_result('db_fetch_assoc_prepared', $sql, $params, array());
	}
}

if (!function_exists('db_fetch_row')) {
	function db_fetch_row($sql, $log = true, $db_conn = false) {
		return npc_test_db_result('db_fetch_row', $sql, array(), array());
	}
}

if (!function_exists('db_fetch_row_prepared')) {
	function db_fetch_row_prepared($sql, $params = array(), $log = true, $db_conn = false) {
		return npc_test_db_result('db_fetch_row_prepared', $sql, $params, array());
	}
}

if (!function_exists('db_fetch_cell')) {
	function db_fetch_cell($sql, $col_name = '', $log = true, $db_conn = false) {
		return npc_test_db_result('db_fetch_cell', $sql, array(), $GLOBALS['__test_db_fetch_cell'] ?? '');
	}
}

if (!function_exists('db_fetch_cell_prepared')) {
	function db_fetch_cell_prepared($sql, $params = array(), $col_name = '', $log = true, $db_conn = false) {
		return npc_test_db_result('db_fetch_cell_prepared', $sql, $params, '');
	}
}

if (!function_exists('db_index_exists')) {
	function db_index_exists($table, $index, $log = true, $db_conn = false) {
		return false;
	}
}

if (!function_exists('db_column_exists')) {
	function db_column_exists($table, $column, $log = true, $db_conn = false) {
		if (isset($GLOBALS['__test_pdo'])) {
			return $GLOBALS['__test_pdo']->query('SHOW COLUMNS FROM `' . $table . "` LIKE '" . $column . "'")->rowCount() > 0;
		}

		return false;
	}
}

if (!function_exists('db_table_exists')) {
	function db_table_exists($table, $log = true, $db_conn = false) {
		if (isset($GLOBALS['__test_pdo'])) {
			return $GLOBALS['__test_pdo']->query("SHOW TABLES LIKE '" . $table . "'")->rowCount() > 0;
		}

		return false;
	}
}

if (!function_exists('api_plugin_db_add_column')) {
	function api_plugin_db_add_column($plugin, $table, $data) {
		return true;
	}
}

if (!function_exists('api_plugin_db_table_create')) {
	function api_plugin_db_table_create($plugin, $table, $data) {
		return true;
	}
}

$GLOBALS['__test_registered_hooks'] = array();

if (!function_exists('api_plugin_register_hook')) {
	function api_plugin_register_hook($plugin, $hook, $function, $file, $enable = false) {
		$GLOBALS['__test_registered_hooks'][] = array(
			'name'     => $plugin,
			'hook'     => $hook,
			'function' => $function,
			'file'     => $file,
		);

		return true;
	}
}

$GLOBALS['__test_registered_realms'] = array();

if (!function_exists('api_plugin_register_realm')) {
	function api_plugin_register_realm($plugin, $file, $display, $admin = true) {
		$GLOBALS['__test_registered_realms'][] = array(
			'name'        => $plugin,
			'file'        => $file,
			'description' => $display,
			'enabled'     => $admin,
		);

		return true;
	}
}

$GLOBALS['__test_removed_realms'] = array();

if (!function_exists('api_plugin_remove_realms')) {
	function api_plugin_remove_realms($plugin) {
		$GLOBALS['__test_removed_realms'][] = $plugin;

		return true;
	}
}

if (!function_exists('read_config_option')) {
	function read_config_option($config_name, $force = false) {
		return $GLOBALS['__test_config'][$config_name] ?? '';
	}
}

if (!function_exists('set_config_option')) {
	function set_config_option($config_name, $value, $remote = false) {
	}
}

if (!function_exists('html_escape')) {
	function html_escape($string) {
		return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
}

if (!function_exists('__')) {
	function __($text, $domain = '') {
		return $text;
	}
}

if (!function_exists('__esc')) {
	function __esc($text, $domain = '') {
		return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
}

if (!function_exists('cacti_log')) {
	function cacti_log($string, $output = false, $environ = 'CMDPHP', $level = '') {
		$args = func_get_args();
		$GLOBALS["__test_cacti_log"][] = isset($args[0]) ? (string) $args[0] : "";
	}
}

if (!function_exists('cacti_sizeof')) {
	function cacti_sizeof($array) {
		return is_array($array) ? count($array) : 0;
	}
}

if (!function_exists('cacti_count')) {
	function cacti_count($array) {
		return is_array($array) ? count($array) : 0;
	}
}

if (!function_exists('cacti_escapeshellarg')) {
	function cacti_escapeshellarg($string, $quote = true) {
		return escapeshellarg($string);
	}
}

if (!function_exists('api_user_realm_auth')) {
	function api_user_realm_auth($filename = '') {
		return !in_array($filename, $GLOBALS['__test_denied_realms'] ?? array(), true);
	}
}

if (!function_exists('api_plugin_user_realm_auth')) {
	function api_plugin_user_realm_auth($filename = '') {
		return !in_array($filename, $GLOBALS['__test_denied_realms'] ?? array(), true);
	}
}

if (!function_exists('is_realm_allowed')) {
	function is_realm_allowed($realm, $check_user = false) {
		return true;
	}
}

if (!function_exists('raise_message')) {
	function raise_message($message_id, $message = '', $message_level = 0) {
	}
}

/*
 * Cacti populates $_REQUEST from the live request at process start. Tests
 * instead set $_POST/$_GET directly, which PHP does not mirror into
 * $_REQUEST outside of a real HTTP request, so these stubs check all three.
 */
function npc_test_request_value($name) {
	if (array_key_exists($name, $_REQUEST)) {
		return $_REQUEST[$name];
	}
	if (array_key_exists($name, $_POST)) {
		return $_POST[$name];
	}
	if (array_key_exists($name, $_GET)) {
		return $_GET[$name];
	}

	return null;
}

if (!function_exists('get_request_var')) {
	function get_request_var($name, $default = '') {
		$value = npc_test_request_value($name);

		return $value !== null ? $value : $default;
	}
}

if (!function_exists('get_nfilter_request_var')) {
	function get_nfilter_request_var($name, $default = '') {
		$value = npc_test_request_value($name);

		return $value !== null ? $value : $default;
	}
}

if (!function_exists('get_filter_request_var')) {
	function get_filter_request_var($name, $filter = FILTER_VALIDATE_INT, $options = array()) {
		$value = npc_test_request_value($name);

		return $value !== null ? filter_var($value, $filter, $options) : '';
	}
}

if (!function_exists('isset_request_var')) {
	function isset_request_var($variable) {
		return npc_test_request_value($variable) !== null;
	}
}

if (!function_exists('form_input_validate')) {
	function form_input_validate($field_value, $field_name, $regexp_match, $allow_nulls, $custom_message = 3) {
		return $field_value;
	}
}

if (!function_exists('is_error_message')) {
	function is_error_message() {
		return false;
	}
}

if (!function_exists('sql_save')) {
	function sql_save($array_items, $table_name, $key_cols = 'id', $autoinc = true, $db_conn = false) {
		$GLOBALS['__test_sql_save'][] = array('table' => $table_name, 'row' => $array_items);

		return isset($array_items['id']) ? $array_items['id'] : 1;
	}
}

if (!defined('CACTI_PATH_BASE')) {
	define('CACTI_PATH_BASE', $GLOBALS['config']['base_path']);
}

if (!defined('POLLER_VERBOSITY_LOW')) {
	define('POLLER_VERBOSITY_LOW', 2);
}

if (!defined('POLLER_VERBOSITY_MEDIUM')) {
	define('POLLER_VERBOSITY_MEDIUM', 3);
}

if (!defined('POLLER_VERBOSITY_DEBUG')) {
	define('POLLER_VERBOSITY_DEBUG', 5);
}

if (!defined('POLLER_VERBOSITY_NONE')) {
	define('POLLER_VERBOSITY_NONE', 6);
}

if (!defined('MESSAGE_LEVEL_ERROR')) {
	define('MESSAGE_LEVEL_ERROR', 1);
}

if (!function_exists('plugin_test_read_source')) {
	function plugin_test_read_source($relative_file) {
		$path = realpath(__DIR__ . '/../' . $relative_file);
		if ($path === false) {
			throw new RuntimeException("Unable to resolve required file: {$relative_file}");
		}

		$contents = file_get_contents($path);
		if ($contents === false) {
			throw new RuntimeException("Unable to read required file: {$relative_file}");
		}

		return $contents;
	}
}

/**
 * Load a plugin source file at global scope.
 *
 * Some plugin files define data as file-scope variables that the rest of
 * the plugin reads as globals, and they read $config while doing so.
 * Requiring them from inside a method would make both halves of that
 * method-local, so the require happens here and any variable the file
 * introduced is published to $GLOBALS.
 *
 * @param string $path Absolute path to the file.
 *
 * @return void
 */
function npc_test_load($path) {
	global $config;

	$__before = get_defined_vars();

	require_once $path;

	foreach (get_defined_vars() as $__name => $__value) {
		if (!array_key_exists($__name, $__before) && strncmp($__name, '__', 2) !== 0) {
			$GLOBALS[$__name] = $__value;
		}
	}
}
