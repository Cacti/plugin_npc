<?php

chdir('../../');

require_once("include/auth.php");
include(dirname(__FILE__) . "/config.php");

$params['config'] = $config;

//$module = 'layoutDev';
$module = 'layout';
$action = 'drawFrame';
$format = 'json';

// Set the default module and action
if (isset($_REQUEST['module'])) {
    $module = get_request_var_request('module');
}

if (isset($_REQUEST['action'])) {
    $action = get_request_var_request('action');
}

if (isset($_REQUEST['format'])) {
    $format = get_request_var_request('format');
}

// Include the requested controller
require_once("plugins/npc/controllers/$module.php");

$class = 'Npc' . ucfirst($module) . 'Controller';
$obj = new $class;
$obj->conn = $conn;

if (is_array($_REQUEST)) {
    foreach($_REQUEST as $key => $value) {
        if (preg_match("/p_/", $key) || $key == "start" || $key == "limit") {
            $parm = preg_replace("/p_/", '', $key);
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





