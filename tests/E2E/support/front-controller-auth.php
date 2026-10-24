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

/*
 * Inert unless a test process started it: tests/ can end up in the web root,
 * so a request for this file must do nothing. Every path below comes from the
 * NPC_E2E environment variable the test sets, never from the request.
 */
if (PHP_SAPI !== 'cli' || getenv('NPC_E2E') === false) {
	http_response_code(404);

	exit;
}

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
 * copied unchanged into a private file in the sandbox and required from there.
 */
if (!function_exists('sanitize_search_string')) {
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

	$extracted = tempnam($scenario['base_path'], 'san');
	file_put_contents($extracted, "<?php\n" . substr($source, $start, $end - $start + 1) . "\n");
	register_shutdown_function(function () use ($extracted) {
		unlink($extracted);
	});

	require_once $extracted;
}

require_once $scenario['base_path'] . '/plugins/npc/setup.php';

$_SERVER['REQUEST_METHOD'] = $scenario['method'];
$_GET                      = $scenario['get'];
$_POST                     = $scenario['post'];
$_REQUEST                  = array_merge($_GET, $_POST);
$_SESSION['sess_user_id']  = 1;

function npc_e2e_record($kind, $detail) {
	global $scenario;

	/* Only a plain file name inside the sandbox directory may be written. */
	if (!preg_match('/^[a-z0-9._-]+$/', basename($scenario['log'])) || realpath(dirname($scenario['log'])) !== realpath($scenario['base_path'])) {
		return;
	}

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

function db_fetch_assoc($sql, $log = true, $db_conn = false) {
	return npc_e2e_rows(__FUNCTION__, $sql, array(), array());
}

function db_fetch_assoc_prepared($sql, $params = array(), $log = true, $db_conn = false) {
	return npc_e2e_rows(__FUNCTION__, $sql, $params, array());
}

function db_fetch_row_prepared($sql, $params = array(), $log = true, $db_conn = false) {
	return npc_e2e_rows(__FUNCTION__, $sql, $params, array());
}

function db_fetch_cell($sql, $col_name = '', $log = true, $db_conn = false) {
	return npc_e2e_rows(__FUNCTION__, $sql, array(), '');
}

function db_fetch_cell_prepared($sql, $params = array(), $col_name = '', $log = true, $db_conn = false) {
	return npc_e2e_rows(__FUNCTION__, $sql, $params, '');
}

function db_execute_prepared($sql, $params = array(), $log = true, $db_conn = false, $execute_name = 'Exec', $default_value = true) {
	npc_e2e_record(__FUNCTION__, array('sql' => $sql, 'params' => $params));

	return true;
}

function sql_save($array_items, $table_name, $key_cols = 'id', $autoinc = true, $db_conn = false) {
	npc_e2e_record(__FUNCTION__, array('table' => $table_name, 'row' => $array_items));

	return 1;
}

function read_config_option($config_name, $force = false) {
	global $scenario;

	return $scenario['options'][$config_name] ?? '';
}

function cacti_log($string, $output = false, $environ = 'CMDPHP', $level = '') {
	npc_e2e_record('cacti_log', array('message' => $string));
}

function raise_message($message_id, $message = '', $message_level = 0) {
	npc_e2e_record('raise_message', array('id' => $message_id));
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

function cacti_escapeshellarg($string, $quote = true) {
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

function html_start_box($title, $width, $div, $cell_padding, $align, $add_text, $add_label = false) {
}

function html_end_box($trailing_br = true, $div = false) {
}

function get_md5_include_js($path, $async = false) {
	return "<script src='" . $path . "'></script>";
}
