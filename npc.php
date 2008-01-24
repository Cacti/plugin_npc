<?php

chdir('../../');

require_once("include/auth.php");


global $config;

$params['config'] = $config;

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

// Include the requested module
require_once("plugins/npc/modules/$module.php");

$class = 'NPC_' . $module;
$obj = new $class;
if (is_array($_REQUEST)) {
    foreach($_REQUEST as $key => $value) {
        $params[$key] = get_request_var_request($value);
        if (preg_match("/p_/", $key) || $key == "start" || $key == "limit") {
            $parm = preg_replace("/p_/", '', $key);
            $obj->$parm = $value;
        }
    }
}


// Display any results in JSON format
if ($module == 'layout' || $format == 'html') {
    $obj->$action($params);
} else {

    list ($count, $results) = $obj->$action($params);

    if (!isset($count)) {
        $count = 1;
    }

    // Setup the output array:
    $output = array('totalCount' => $count, 'data' => $results);

    // Print the JSON encoded output array:
    print json_encode($output);
}

?>

