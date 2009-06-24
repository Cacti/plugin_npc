#!/usr/bin/php -q
<?php

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
        die("<br><strong>This script is only meant to run at the command line.</strong>");
}

include(dirname(__FILE__)."/../../include/global.php");
include(dirname(__FILE__) . "/config.php");
require_once($config["base_path"]."/plugins/npc/controllers/hosts.php");
require_once($config["base_path"]."/plugins/npc/controllers/hostgroups.php");
require_once($config["base_path"]."/plugins/npc/controllers/services.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

$objectId = null;
$serviceName = null;
$hostname = null;

if (!sizeof($parms)) {
    display_help();
    exit(0);
} else {

    foreach($parms as $parameter) {
        @list($arg, $value) = @explode("=", $parameter);

        switch ($arg) {
            case "--type":
                $type = trim($value);
                break;

            case "--id":
                $objectId = trim($value);
                break;

            case "--service":
                $serviceName = trim($value);
                break;

            case "--host":
                $hostname = trim($value);
                break;

            case "--hostgroup":
                $hostgroup = trim($value);
                break;

            case "--ds":
                $ds = trim($value);
                break;

            case "--list-hosts":
                listHosts();
                break;

            case "--list-hostgroups":
                listHostgroups();
                break;

            case "--list-services":
                listServices();
                break;
        }
    }
}

if ($type == 'host') {
    $class = 'NpcHostsController';
} else {
    $class = 'NpcServicesController';
}

// perfdata.php --type=service --host=<hostname> --service="System: CPU"

$obj = new $class;
$results = $obj->getPerfData($objectId, $hostname, $serviceName);

$perfParts = explode(" ", $results[0]['perfdata']);

$output = '';

foreach ($perfParts as $perf) {
    if (preg_match("/=/", $perf)) {
        preg_match("/(\S+)=([-+]?[0-9]*\.?[0-9]+)/", $perf, $matches);
        if (preg_match("/^iso./", $matches[1])) {
                $matches[1] = 'output';
        }
        $output .= $matches[1] . ":" . $matches[2] . " ";
    }
}

echo $output . "\n";

function listHosts() {

    $parms = $_SERVER["argv"];
    foreach($parms as $parameter) {
        @list($arg, $value) = @explode("=", $parameter);
        switch ($arg) {
            case "--hostgroup":
                $hostgroup = trim($value);
                break;
        }
    }

    if (isset($hostgroup)) { 
        $obj = new NpcHostgroupsController;
        $results = $obj->listHostsCli($hostgroup);
    } else {
        $obj = new NpcHostsController;
        $results = $obj->listHostsCli();
    }

    echo "\nID      Name         Address\n";
    foreach($results as $result) {
        echo $result['id'] . "      " . $result['name'] .  "      " . $result['address'] . "\n";
    }

    exit;
}

function listHostgroups() {

    $obj = new NpcHostgroupsController;
    $results = $obj->listHostgroupsCli();

    echo "ID     Name\n";
    foreach($results as $result) {
        echo $result['id'] . "      " . $result['name'] .  "\n";
    }

    exit;
}

function listServices() {

    $hostname = null;

    $parms = $_SERVER["argv"];
    foreach($parms as $parameter) {
        @list($arg, $value) = @explode("=", $parameter);

        switch ($arg) {
            case "--host":
                $hostname = trim($value);
                break;
        }
    }


    $obj = new NpcServicesController;
    $results = $obj->listServicesCli($hostname);

    $x = 1;
    echo "\n" . sprintf("%-30s %-30s %-30s %-30s\n", 'Instance', 'Host', 'Service', 'Service Object ID'); 
    foreach($results as $result) {
        echo sprintf("%-30s %-30s %-30s %-30s\n", $result['instance'], $result['host'], $result['display_name'], $result['service_object_id']); 
    }

    exit;
}

function display_help() {
    echo "A simple command line utility to fetch host or service performance data from NPC\n\n";
    echo "usage: perfdata.php --type=[host|service] --id=[ID] --datasource=[DS]\n\n";
    echo "Required:\n";
    echo "    --type          Type specifies that we are querting perfdata for a host or service\n";
    echo "    --id            The host or service object ID\n";
    echo "Optional:\n";
    echo "    --ds            Return only the specified datasource. All returned by default.\n";
    echo "List Options:\n";
    echo "    --list-hosts [--hostgroup=<name>]\n";
    echo "    --list-hostgroups\n";
    echo "    --list-services\n\n";
}

?>

