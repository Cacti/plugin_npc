<?php
/*
 +-------------------------------------------------------------------------+
 | Nagios Plugin for Cacti                                                 |
 |                                                                         |
 | Copyright (C) 2007 Billy Gunn (billy@gunn.org)                          |
 | Copyright (C) 2024 The Cacti Group, Inc.                                |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 +-------------------------------------------------------------------------+
*/

/*
 * Cacti framework stubs for unit testing the NPC plugin without a live
 * Cacti installation. All DB helpers record calls in $GLOBALS['__test_db_calls']
 * so tests can assert which queries were (or were not) executed.
 *
 * PHP 7.4 style throughout: no type hints, array() literals.
 */

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------
if (!defined('CACTI_PATH_BASE')) {
	define('CACTI_PATH_BASE', dirname(__DIR__));
}
if (!defined('POLLER_VERBOSITY_NONE')) {
	define('POLLER_VERBOSITY_NONE',  0);
}
if (!defined('POLLER_VERBOSITY_LOW')) {
	define('POLLER_VERBOSITY_LOW',   1);
}
if (!defined('POLLER_VERBOSITY_MEDIUM')) {
	define('POLLER_VERBOSITY_MEDIUM', 2);
}
if (!defined('POLLER_VERBOSITY_HIGH')) {
	define('POLLER_VERBOSITY_HIGH',  3);
}
if (!defined('POLLER_VERBOSITY_DEBUG')) {
	define('POLLER_VERBOSITY_DEBUG', 4);
}
if (!defined('MESSAGE_LEVEL_ERROR')) {
	define('MESSAGE_LEVEL_ERROR', 2);
}
if (!defined('MESSAGE_LEVEL_WARN')) {
	define('MESSAGE_LEVEL_WARN', 1);
}
if (!defined('MESSAGE_LEVEL_INFO')) {
	define('MESSAGE_LEVEL_INFO', 0);
}

// ---------------------------------------------------------------------------
// DB call spy registry
// ---------------------------------------------------------------------------
$GLOBALS['__test_db_calls'] = array();

function __npc_record_db_call($fn, $sql, $params) {
	$GLOBALS['__test_db_calls'][] = array(
		'fn'     => $fn,
		'sql'    => $sql,
		'params' => $params,
	);
}

// ---------------------------------------------------------------------------
// DB fetch / execute stubs (raw, non-prepared)
// ---------------------------------------------------------------------------
if (!function_exists('db_fetch_assoc')) {
	function db_fetch_assoc($sql, $log = true) {
		__npc_record_db_call('db_fetch_assoc', $sql, array());
		return array();
	}
}

if (!function_exists('db_fetch_row')) {
	function db_fetch_row($sql, $log = true) {
		__npc_record_db_call('db_fetch_row', $sql, array());
		return array();
	}
}

if (!function_exists('db_fetch_cell')) {
	function db_fetch_cell($sql, $col = '', $log = true) {
		__npc_record_db_call('db_fetch_cell', $sql, array());
		return null;
	}
}

if (!function_exists('db_execute')) {
	function db_execute($sql, $log = true) {
		__npc_record_db_call('db_execute', $sql, array());
		return true;
	}
}

if (!function_exists('db_insert_id')) {
	function db_insert_id() {
		return 0;
	}
}

// ---------------------------------------------------------------------------
// DB fetch / execute stubs (prepared)
// ---------------------------------------------------------------------------
if (!function_exists('db_fetch_assoc_prepared')) {
	function db_fetch_assoc_prepared($sql, $params = array(), $log = true) {
		__npc_record_db_call('db_fetch_assoc_prepared', $sql, $params);
		return array();
	}
}

if (!function_exists('db_fetch_row_prepared')) {
	function db_fetch_row_prepared($sql, $params = array(), $log = true) {
		__npc_record_db_call('db_fetch_row_prepared', $sql, $params);
		return array();
	}
}

if (!function_exists('db_fetch_cell_prepared')) {
	function db_fetch_cell_prepared($sql, $params = array(), $col = '', $log = true) {
		__npc_record_db_call('db_fetch_cell_prepared', $sql, $params);
		return null;
	}
}

if (!function_exists('db_execute_prepared')) {
	function db_execute_prepared($sql, $params = array(), $log = true) {
		__npc_record_db_call('db_execute_prepared', $sql, $params);
		return true;
	}
}

// ---------------------------------------------------------------------------
// Config / settings stubs
// ---------------------------------------------------------------------------
if (!function_exists('read_config_option')) {
	function read_config_option($option, $force = false) {
		$defaults = array(
			'npc_log_level' => POLLER_VERBOSITY_MEDIUM,
		);
		return isset($defaults[$option]) ? $defaults[$option] : null;
	}
}

if (!function_exists('set_config_option')) {
	function set_config_option($option, $value) {
		// no-op in tests
	}
}

// ---------------------------------------------------------------------------
// Output / escaping stubs
// ---------------------------------------------------------------------------
if (!function_exists('html_escape')) {
	function html_escape($value) {
		return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	}
}

if (!function_exists('__')) {
	function __($text, $domain = 'cacti') {
		return $text;
	}
}

if (!function_exists('__esc')) {
	function __esc($text, $domain = 'cacti') {
		return html_escape($text);
	}
}

// ---------------------------------------------------------------------------
// Logging stub
// ---------------------------------------------------------------------------
if (!function_exists('cacti_log')) {
	function cacti_log($message, $stdout = false, $facility = 'POLLER', $severity = POLLER_VERBOSITY_MEDIUM) {
		// no-op in tests
	}
}

// ---------------------------------------------------------------------------
// Array utility stubs
// ---------------------------------------------------------------------------
if (!function_exists('cacti_sizeof')) {
	function cacti_sizeof($array) {
		return (is_array($array) || $array instanceof Countable) ? count($array) : 0;
	}
}

if (!function_exists('cacti_count')) {
	function cacti_count($array) {
		return cacti_sizeof($array);
	}
}

// ---------------------------------------------------------------------------
// Auth / realm stubs
// ---------------------------------------------------------------------------
if (!function_exists('is_realm_allowed')) {
	function is_realm_allowed($realm) {
		return true;
	}
}

// ---------------------------------------------------------------------------
// Message / form stubs
// ---------------------------------------------------------------------------
if (!function_exists('raise_message')) {
	function raise_message($message_id, $message = '', $type = MESSAGE_LEVEL_INFO) {
		// no-op in tests
	}
}

if (!function_exists('get_request_var')) {
	function get_request_var($name, $default = '') {
		return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
	}
}

if (!function_exists('get_filter_request_var')) {
	function get_filter_request_var($name, $filter = FILTER_DEFAULT, $default = '') {
		if (isset($_REQUEST[$name])) {
			return filter_var($_REQUEST[$name], $filter);
		}
		return $default;
	}
}

if (!function_exists('get_nfilter_request_var')) {
	function get_nfilter_request_var($name, $default = '') {
		return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
	}
}

if (!function_exists('isset_request_var')) {
	function isset_request_var($name) {
		return isset($_REQUEST[$name]);
	}
}

if (!function_exists('get_request_var_request')) {
	function get_request_var_request($name, $default = '') {
		return isset($_REQUEST[$name]) ? $_REQUEST[$name] : $default;
	}
}

if (!function_exists('form_input_validate')) {
	function form_input_validate($value, $name, $regex, $allow_empty, $message_id) {
		return $value;
	}
}

if (!function_exists('is_error_message')) {
	function is_error_message() {
		return false;
	}
}

if (!function_exists('sql_save')) {
	function sql_save($array_items, $table_name, $key = 'id', $autoinc = true) {
		__npc_record_db_call('sql_save', $table_name, $array_items);
		return 1;
	}
}

if (!function_exists('validate_store_request_vars')) {
	function validate_store_request_vars($array, $session_key) {
		// no-op in tests
	}
}

// ---------------------------------------------------------------------------
// HTML layout stubs
// ---------------------------------------------------------------------------
if (!function_exists('general_header')) {
	function general_header() {
		// no-op in tests
	}
}

if (!function_exists('bottom_footer')) {
	function bottom_footer() {
		// no-op in tests
	}
}

if (!function_exists('html_start_box')) {
	function html_start_box($title, $width, $background, $colspan, $align, $add_text, $resizable = true) {
		// no-op in tests
	}
}

if (!function_exists('html_end_box')) {
	function html_end_box($str = true, $no_padding = false) {
		// no-op in tests
	}
}

// ---------------------------------------------------------------------------
// Plugin API stubs (used by setup.php)
// ---------------------------------------------------------------------------
if (!function_exists('api_plugin_register_realm')) {
	function api_plugin_register_realm($plugin, $file, $description, $admin) {
		// no-op in tests
	}
}

if (!function_exists('api_plugin_register_hook')) {
	function api_plugin_register_hook($plugin, $hook, $function, $file) {
		// no-op in tests
	}
}

if (!function_exists('api_plugin_db_table_exists')) {
	function api_plugin_db_table_exists($table) {
		return false;
	}
}
