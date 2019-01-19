<?php

/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2007 Billy Gunn aka divagater  (billy@gunn.org)           |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License (GPLv3)     |
 | version 3 as published by the Free Software Foundation.                 |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti and Nagios are the copyright of their respective owners.          |
 +-------------------------------------------------------------------------+
*/

chdir('../../');

require_once('include/auth.php');
include(dirname(__FILE__) . '/config.php');

// Setup a timer
$starttime = microtime(true);

$params['config'] = $config;

$module = 'layout';
$action = 'drawFrame';
$format = 'json';

// Set the default module and action
if (isset_request_var('module')) {
	$module = get_request_var_request('module');
}

if (isset_request_var('action')) {
	$action = get_request_var_request('action');
}

if (isset_request_var('format')) {
	$format = get_request_var_request('format');
}

if ($action == 'csrf') {
	print csrf_get_tokens();
	exit;
}

// Include the requested controller
require_once('plugins/npc/controllers/' . $module . '.php');

$class = 'Npc' . ucfirst($module) . 'Controller';
$obj = new $class;
$obj->conn = $conn;

if (is_array($_REQUEST)) {
	foreach($_REQUEST as $key => $value) {
		if (preg_match('/^p_/', $key) || $key == 'start' || $key == 'limit') {
			$parm = preg_replace('/^p_/', '', $key);
			$obj->$parm = $value;
			$params[$parm] = get_request_var_request($key);
		} else {
			$params[$key] = get_request_var_request($key);
		}
	}
}

// Set the current page for pagination
if (get_request_var_request('start')) {
	$obj->currentPage = (($obj->start / $obj->limit) + 1);
}

if ($out = $obj->$action($params)) {
	print $out;
}

$mtime = microtime();
$mtime = explode(' ', $mtime);
$mtime = $mtime[1] + $mtime[0];
$endtime = $mtime;
$totaltime = sprintf('%01.2f', ($endtime - $starttime));

$obj->logger('debug', get_class($obj), get_request_var_request('action'), "Script execution time: $totaltime seconds");

