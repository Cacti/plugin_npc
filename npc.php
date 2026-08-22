<?php
/*
 +-------------------------------------------------------------------------+
 | Nagios Plugin for Cacti                                                 |
 |                                                                         |
 | Copyright (C) 2007 Billy Gunn (billy@gunn.org)                          |
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 +-------------------------------------------------------------------------+
 | Cacti and Nagios are the copyright of their respective owners.          |
 +-------------------------------------------------------------------------+
*/

chdir('../../');

require_once('include/auth.php');

/* Allowlisted modules and their permitted actions. Prevents path traversal
   via the module parameter and arbitrary method invocation via action. */
$allowed_modules = array(
	'layout'        => array('drawFrame'),
	'hosts'         => array('getHosts', 'getStateInfo', 'summary', 'getPerfData', 'getMappedGraph', 'setMappedGraph'),
	'services'      => array('getServices', 'getStateInfo', 'summary', 'getPerfData', 'getPerfHistory', 'getMappedGraph', 'setMappedGraph'),
	'hostgroups'    => array('getHostgroupHostStatus', 'getHostgroupServiceStatus', 'getOverview', 'getHosts', 'getHostList', 'getHostgroups'),
	'servicegroups' => array('getHostStatusPortlet', 'getServicegroupServiceStatus', 'getOverview', 'getHostSummary', 'getServices', 'getServicegroups'),
	'comments'      => array('getAck', 'getLastComment', 'getComments', 'getHostComments', 'getServiceComments', 'deleteAllHostComments', 'deleteAllServiceComments', 'getHostIcon'),
	'downtime'      => array('getDowntime', 'getHostDowntime', 'getServiceDowntime', 'inDowntime', 'getTriggeredByCombo', 'scheduledDowntime', 'downtimeHistory'),
	'notifications' => array('getNotifications'),
	'logentries'    => array('getLogs'),
	'statehistory'  => array('getStateHistory'),
	'nagios'        => array('getProgramStatus', 'getProcessInfoGrid', 'processInfo', 'command', 'checkPerf'),
	'settings'      => array('getSettings', 'save'),
	'cacti'         => array('getSetting', 'getHostTemplates', 'isMapped', 'mapHost', 'getHostnames', 'addDataInputMethod', 'generateHash', 'getGraphList'),
	'sync'          => array('import', 'getHosts', 'listHostgroups', 'checkHostExists'),
);

$module = 'layout';
$action = 'drawFrame';

if (isset_request_var('module')) {
	$module = get_filter_request_var('module', FILTER_CALLBACK, array('options' => 'sanitize_search_string'));
}

if (isset_request_var('action')) {
	$action = get_filter_request_var('action', FILTER_CALLBACK, array('options' => 'sanitize_search_string'));
}

/* CSRF token refresh endpoint */
if ($action == 'csrf') {
	print csrf_get_tokens();
	exit;
}

/* Validate module against allowlist (prevents path traversal) */
if (!isset($allowed_modules[$module])) {
	cacti_log('SECURITY: NPC rejected unknown module: ' . $module, false, 'NPC');
	raise_message('npc_error', __('Invalid module requested.', 'npc'), MESSAGE_LEVEL_ERROR);
	header('Location: npc.php');
	exit;
}

/* Validate action against allowlist (prevents arbitrary method invocation) */
if (!in_array($action, $allowed_modules[$module], true)) {
	cacti_log('SECURITY: NPC rejected unknown action: ' . $module . '/' . $action, false, 'NPC');
	raise_message('npc_error', __('Invalid action requested.', 'npc'), MESSAGE_LEVEL_ERROR);
	header('Location: npc.php');
	exit;
}

/* Include the validated controller */
$controller_path = dirname(__FILE__) . '/controllers/' . $module . '.php';

if (!file_exists($controller_path)) {
	cacti_log('ERROR: NPC controller not found: ' . $module, false, 'NPC');
	raise_message('npc_error', __('Controller not found.', 'npc'), MESSAGE_LEVEL_ERROR);
	header('Location: npc.php');
	exit;
}

require_once($controller_path);

$class = 'Npc' . ucfirst($module) . 'Controller';

if (!class_exists($class)) {
	cacti_log('ERROR: NPC controller class not found: ' . $class, false, 'NPC');
	header('Location: npc.php');
	exit;
}

$obj = new $class;

/* Collect sanitized parameters. Only allow known parameter prefixes. */
$params = array('config' => $config);

if (is_array($_REQUEST)) {
	foreach ($_REQUEST as $key => $value) {
		/* Only accept p_ prefixed parameters, start, limit, and sort params */
		if (preg_match('/^p_[a-zA-Z0-9_]+$/', $key)) {
			$parm = preg_replace('/^p_/', '', $key);
			$obj->$parm = get_filter_request_var($key, FILTER_CALLBACK, array('options' => 'sanitize_search_string'));
			$params[$parm] = $obj->$parm;
		} elseif (in_array($key, array('start', 'limit', 'sort', 'dir'), true)) {
			if ($key == 'start' || $key == 'limit') {
				$obj->$key = get_filter_request_var($key, FILTER_VALIDATE_INT);
			} elseif ($key == 'dir') {
				$val = strtoupper(get_filter_request_var($key, FILTER_CALLBACK, array('options' => 'sanitize_search_string')));
				$obj->dir = in_array($val, array('ASC', 'DESC'), true) ? $val : 'ASC';
			} elseif ($key == 'sort') {
				$obj->sort = get_filter_request_var($key, FILTER_CALLBACK, array('options' => 'sanitize_search_string'));
			}
			$params[$key] = $obj->$key;
		}
	}
}

/* Set the current page for pagination */
if (isset($obj->start) && isset($obj->limit) && $obj->limit > 0) {
	$obj->currentPage = intval($obj->start / $obj->limit) + 1;
}

$out = $obj->$action($params);

if ($out) {
	print $out;
}
