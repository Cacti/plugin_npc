#!/usr/bin/php -q
<?php

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
        die("<br><strong>This script is only meant to run at the command line.</strong>");
}

include(dirname(__FILE__)."/../../include/global.php");
include(dirname(__FILE__) . "/config.php");
require_once($config["base_path"]."/plugins/npc/controllers/hosts.php");
require_once($config["base_path"]."/plugins/npc/controllers/services.php");

/* process calling arguments */
$parms = $_SERVER["argv"];
array_shift($parms);

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

            case "--ds":
                $ds = trim($value);
                break;

            case "--list-hosts":
                listHosts();
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

$obj = new $class;
$results = $obj->getPerfData($objectId);

$perfParts = explode(";", $results[0]['perfdata']);

$output = '';

foreach ($perfParts as $perf) {
    if (preg_match("/=/", $perf)) {
        $output .= preg_replace("/=/", ':', $perf) . " ";
    }
}

echo $output . "\n";


function listHosts() {

    $obj = new NpcHostsController;
    $results = $obj->listHostsCli();

    $x = 1;
    echo "ID   Name\n";
    foreach($results as $result) {
        echo $result['id'] . "   " . $result['name'] . "\n";
    }

    exit;
}

function listServices() {

    $obj = new NpcServicesController;
    $results = $obj->listServicesCli();

    $x = 1;
    echo "ID   Name\n";
    foreach($results as $result) {
        echo $result['id'] . "   " . $result['name'] . "\n";
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
    echo "    --list-hosts\n";
    echo "    --list-services\n\n";
}

?>
