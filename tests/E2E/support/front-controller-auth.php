<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Stand-in for Cacti's include/auth.php, used only by the front controller
 * tests in tests/E2E.
 *
 * The request, validation and sanitizing helpers are Cacti's own, loaded from
 * the Cacti tree the plugin sits in. Only the session, the database and the
 * page chrome are replaced. The scenario comes in through the NPC_E2E
 * environment variable as JSON: request method, query and form values, the
 * rows the database should return for a statement, and the configuration
 * options. Every database call and log line is appended to a file named in
 * the same JSON so the test can inspect what the plugin did.
 */

$scenario = json_decode((string) getenv('NPC_E2E'), true);

$config = array(
	'base_path'     => $scenario['base_path'],
	'library_path'  => $scenario['cacti_path'] . '/lib',
	'url_path'      => '/cacti/',
	'cacti_version' => '1.2.30',
);

require_once $scenario['cacti_path'] . '/include/global_constants.php';
require_once $scenario['cacti_path'] . '/lib/html_utility.php';

/*
 * lib/functions.php cannot be loaded next to the stubs below, so the one
 * helper the front controller needs from it, the search string sanitizer, is
 * taken from the file source unchanged.
 */
$source = file_get_contents($scenario['cacti_path'] . '/lib/functions.php');
$start  = strpos($source, 'function sanitize_search_string');
$open   = strpos($source, '{', $start);
$depth  = 0;

for ($end = $open; $end < strlen($source); $end++) {
	if ($source[$end] === '{') {
		$depth++;
	} elseif ($source[$end] === '}' && --$depth === 0) {
		break;
	}
}

eval(substr($source, $start, $end - $start + 1));
require_once $scenario['base_path'] . '/plugins/npc/setup.php';

$_SERVER['REQUEST_METHOD'] = $scenario['method'];
$_GET                      = $scenario['get'];
$_POST                     = $scenario['post'];
$_REQUEST                  = array_merge($_GET, $_POST);
$_SESSION['sess_user_id']  = 1;

function npc_e2e_record($kind, $detail) {
	global $scenario;

	file_put_contents($scenario['log'], json_encode(array('kind' => $kind) + $detail) . "\n", FILE_APPEND);
}

function npc_e2e_rows($fn, $sql, $params, $default) {
	global $scenario;

	npc_e2e_record($fn, array('sql' => $sql, 'params' => $params));

	foreach (($scenario['rows'][$fn] ?? array()) as $match => $rows) {
		if (strpos($sql, (string) $match) !== false) {
			return $rows;
		}
	}

	return $default;
}

function db_fetch_assoc($sql) {
	return npc_e2e_rows(__FUNCTION__, $sql, array(), array());
}

function db_fetch_assoc_prepared($sql, $params = array()) {
	return npc_e2e_rows(__FUNCTION__, $sql, $params, array());
}

function db_fetch_row_prepared($sql, $params = array()) {
	return npc_e2e_rows(__FUNCTION__, $sql, $params, array());
}

function db_fetch_cell($sql) {
	return npc_e2e_rows(__FUNCTION__, $sql, array(), '');
}

function db_fetch_cell_prepared($sql, $params = array()) {
	return npc_e2e_rows(__FUNCTION__, $sql, $params, '');
}

function db_execute_prepared($sql, $params = array()) {
	npc_e2e_record(__FUNCTION__, array('sql' => $sql, 'params' => $params));

	return true;
}

function sql_save($array, $table, $key = 'id') {
	npc_e2e_record(__FUNCTION__, array('table' => $table, 'row' => $array));

	return 1;
}

function read_config_option($name, $force = false) {
	global $scenario;

	return $scenario['options'][$name] ?? '';
}

function cacti_log($message, $also_print = false, $log_type = '', $level = 0) {
	npc_e2e_record('cacti_log', array('message' => $message));
}

function raise_message($id, $text = '', $level = 0) {
	npc_e2e_record('raise_message', array('id' => $id));
}

function api_user_realm_auth($filename = '') {
	return true;
}

function api_plugin_user_realm_auth($filename = '') {
	return true;
}

function cacti_sizeof($array) {
	return is_array($array) ? count($array) : 0;
}

function cacti_count($array) {
	return is_array($array) ? count($array) : 0;
}

function cacti_escapeshellarg($string) {
	return escapeshellarg($string);
}

function html_escape($string) {
	return htmlspecialchars((string) $string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function __($text, $domain = 'cacti') {
	return $text;
}

function csrf_get_tokens() {
	return 'token';
}

function general_header() {
}

function bottom_footer() {
}

function html_start_box() {
}

function html_end_box() {
}

function get_md5_include_js($path) {
	return "<script src='" . $path . "'></script>";
}
