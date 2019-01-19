#!/usr/bin/php -q
<?php

include(dirname(__FILE__) . '/../../include/cli_check.php');
include(dirname(__FILE__) . '/config.php');
require_once($config['base_path'] . '/plugins/npc/controllers/hosts.php');
require_once($config['base_path'] . '/plugins/npc/controllers/hostgroups.php');
require_once($config['base_path'] . '/plugins/npc/controllers/services.php');

/* process calling arguments */
$parms = $_SERVER['argv'];
array_shift($parms);

$listHosts      = null;
$listHostgroups = null;
$listServices   = null;
$getPerfHistory = null;
$getPerfData    = null;
$begin          = null;
$end            = null;
$objectId       = null;
$serviceName    = null;
$hostname       = null;
$type           = null;

if (!sizeof($parms)) {
	display_help();
	exit(0);
} else {
    foreach ($parms as $parameter) {
		@list($arg, $value) = @explode('=', $parameter);

        switch ($arg) {
            case '--type':
                $type = trim($value);
                break;

            case '--id':
                $objectId = trim($value);
                break;

            case '--service':
                $serviceName = trim($value);
                break;

            case '--host':
                $hostname = trim($value);
                break;

            case '--hostgroup':
                $hostgroup = trim($value);
                break;

            case '--ds':
                $ds = trim($value);
                break;

            case '--list-hosts':
                $listHosts = 1;
                break;

            case '--list-hostgroups':
                $listHostgroups = 1;
                break;

            case '--list-services':
                $listServices = 1;
                break;

            case '--perfdata':
                $getPerfData = 1;
                break;

            case '--begin':
                $begin = trim($value);
                break;

            case '--end':
                $end = trim($value);
                break;

            case '--perf-history':
                $getPerfHistory = 1;
                break;

            default:
                $getPerfData = 1;
                break;
        }
    }
}

if ($getPerfHistory) { getPerfHistory(); }
if ($getPerfData)    { getPerfData();    }
if ($listHosts)      { listHosts();      }
if ($listHostgroups) { listHostgroups(); }
if ($listServices)   { listServices();   }

function getPerfHistory() {
	global $hostname, $serviceName, $begin, $end;

	if ($serviceName) {
		$class = 'NpcServicesController';
	} else {
		$class = 'NpcHostsController';
	}

	$obj = new $class;
	$results = $obj->getPerfHistory($hostname, $serviceName, $begin, $end);

	$count = count($results);

	for ($i = 0; $i < $count; $i++) {
		//$output = '';
		//$perfParts = explode(" ", $results[$i]['perfdata']);

		//foreach ($perfParts as $perf) {
		//    if (preg_match("/=/", $perf)) {
		//        preg_match("/(\S+)=([-+]?[0-9]*\.?[0-9]+)/", $perf, $matches);
		//        $output .= $matches[1] . ":" . $matches[2] . " ";
		//    }
		//}
		//print $results[$i]['end_time'] . " " . $output . "\n";
		print $results[$i]['end_time'] . ' ' . $results[$i]['perfdata'] . "\n";
	}
}

function getPerfData() {
	global $objectId, $hostname, $serviceName, $type;

	if ($type == 'host') {
		$class = 'NpcHostsController';
	} else {
		$class = 'NpcServicesController';
	}

	$obj = new $class;
	$results = $obj->getPerfData($objectId, $hostname, $serviceName);

	$perfParts = explode(' ', $results[0]['perfdata']);

	$output = '';

	foreach ($perfParts as $perf) {
		if (preg_match('/=/', $perf)) {
			preg_match('/(\S+)=([-+]?[0-9]*\.?[0-9]+)/', $perf, $matches);
			if (preg_match('/^iso./', $matches[1])) {
				$matches[1] = 'output';
			}
			$output .= $matches[1] . ':' . $matches[2] . ' ';
		}
	}

	print $output . "\n";
}

function listHosts() {
    $parms = $_SERVER['argv'];

	foreach ($parms as $parameter) {
		@list($arg, $value) = @explode('=', $parameter);
		switch ($arg) {
			case '--hostgroup':
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

	print "\n" . sprintf("%-10s %-30s %-30s\n", 'Id', 'Name', 'Address') . "\n";
	foreach ($results as $result) {
		print sprintf("%-10s %-30s %-30s\n", $result['id'], $result['name'], $result['address']);
	}

	exit;
}

function listHostgroups() {
	$obj = new NpcHostgroupsController;
	$results = $obj->listHostgroupsCli();

	print "\n" . sprintf("%-10s %-30s\n", 'Id', 'Name') . "\n";
	foreach ($results as $result) {
		print sprintf("%-10s %-30s\n", $result['id'], $result['name']);
	}

	exit;
}

function listServices() {
	$parms = $_SERVER['argv'];
	foreach ($parms as $parameter) {
		@list($arg, $value) = @explode('=', $parameter);

		switch ($arg) {
			case '--host':
				$hostname = trim($value);
				break;
		}
	}

	$obj = new NpcServicesController;
	$results = $obj->listServicesCli($hostname);

	$x = 1;
	print "\n" . sprintf("%-10s %-20s %-30s\n", 'ID', 'Host', 'Service') . "\n";
	foreach ($results as $result) {
		print sprintf("%-10s %-20s %-30s\n", $result['service_object_id'], $result['host'], $result['display_name']);
	}

	exit;
}

function display_help() {
	print "A simple command line utility to fetch host or service performance data from NPC\n\n";
	print "usage: perfdata.php --type=[host|service] --id=[ID] --datasource=[DS]\n\n";
	print "Required:\n";
	print "    --type          Type specifies that we are querting perfdata for a host or service\n";
	print "    --id            The host or service object ID\n";
	print "Optional:\n";
	print "    --ds            Return only the specified datasource. All returned by default.\n";
	print "List Options:\n";
	print "    --list-hosts\n";
	print "    --list-services\n\n";
}

