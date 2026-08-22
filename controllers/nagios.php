<?php
/* ex: set tabstop=4 expandtab: */
/**
 * Nagios controller class
 *
 * This is the access point to various tables used to
 * access Nagios application data.
 *
 *
 * @filesource
 * @author              Billy Gunn <billy@gunn.org>
 * @copyright           Copyright (c) 2007
 * @link                http://trac2.assembla.com/npc
 * @package             npc
 * @subpackage          npc.controllers
 * @since               NPC 2.0
 * @version             $Id$
 */

require_once('plugins/npc/nagioscmd.php');

/**
 * Nagios controller class
 *
 * This is the access point to various tables used to
 * access data specific to an entire Nagios install.
 * This includes the Nagios process, check performance,
 * and other Nagios statistics.
 *
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcNagiosController extends Controller {
	/**
	 * getProgramStatus
	 *
	 * Fetches the npc_programstatus
	 *
	 * @return string   json output
	 */
	function getProgramStatus() {
		return($this->jsonOutput($this->processInfo()));
	}

	/**
	 * getProcessInfoGrid
	 *
	 * Gets and formats Nagios process state information
	 *
	 * @return string   json output
	 */
	function getProcessInfoGrid() {
		$fields = array(
			'program_version',
			'instance_id',
			'process_id',
			'status_update_time',
			'program_start_time',
			'program_end_time',
			//'is_currently_running',
			'last_command_check',
			'last_log_rotation',
			'notifications_enabled',
			'active_service_checks_enabled',
			'passive_service_checks_enabled',
			'active_host_checks_enabled',
			'passive_host_checks_enabled',
			'event_handlers_enabled',
			'flap_detection_enabled',
			'process_performance_data',
			'obsess_over_hosts',
			'obsess_over_services'
		);

		$results = $this->processInfo();

		$x = 0;
		foreach ($fields as $key) {
			$output[$x] = array('name' => $this->columnAlias[$key], 'value' => $this->formatProcessInfo($key, $results[0]));
			$x++;
		}

		return($this->jsonOutput($output));
	}

	/**
	 * processInfo
	 *
	 * Gets and formats Nagios process state information
	 *
	 * @return string   json output
	 */
	function processInfo() {
		$results = db_fetch_assoc_prepared('SELECT ps.*
			FROM npc_programstatus ps', array());

		if (cacti_sizeof($results)) {
			$version = db_fetch_assoc_prepared('SELECT p.instance_id, p.program_version, MAX(p.processevent_id) AS max_id
				FROM npc_processevents p
				WHERE p.instance_id = ?
				GROUP BY p.program_version',
				array($results[0]['instance_id']));

			$results[0]['server_time'] = date('Y-m-d H:i:s');
			if (isset($version[0])) {
				$results[0]['program_version'] = $version[0]['program_version'];
			} else {
				$results[0]['program_version'] = 'unknown';
			}
		}

		return($results);
	}

	/**
	 * command
	 *
	 * Creates a nagios command object passing in command
	 * arguments from the client.
	 *
	 * @param  array    $params - The command and parameters
	 * @return string
	 */
	function command($params) {
		/* Get the passed command */
		$cmd = $params['command'];

		$globalCommands = array(
			'DISABLE_EVENT_HANDLERS',
			'ENABLE_EVENT_HANDLERS',
			'DISABLE_NOTIFICATIONS',
			'ENABLE_NOTIFICATIONS',
			'DISABLE_FLAP_DETECTION',
			'ENABLE_FLAP_DETECTION',
			'STOP_ACCEPTING_PASSIVE_HOST_CHECKS',
			'START_ACCEPTING_PASSIVE_HOST_CHECKS',
			'STOP_ACCEPTING_PASSIVE_SVC_CHECKS',
			'START_ACCEPTING_PASSIVE_SVC_CHECKS',
			'DISABLE_PERFORMANCE_DATA',
			'ENABLE_PERFORMANCE_DATA',
			'STOP_EXECUTING_HOST_CHECKS',
			'START_EXECUTING_HOST_CHECKS',
			'STOP_EXECUTING_SVC_CHECKS',
			'START_EXECUTING_SVC_CHECKS',
			'STOP_OBSESSING_OVER_HOST_CHECKS',
			'START_OBSESSING_OVER_HOST_CHECKS',
			'STOP_OBSESSING_OVER_SVC_CHECKS',
			'START_OBSESSING_OVER_SVC_CHECKS'
		);

		$nagios = new NagiosCmd;
		$args = array();

		/* Do some sanity checking */

		if (!read_config_option('npc_nagios_commands')) {
			$response = array('success' => false, 'msg' => __('Remote Commands must be enabled under console->Settings->NPC', 'npc'));
			return(json_encode($response));
        }

		if (!read_config_option('npc_nagios_cmd_path')) {
			$response = array('success' => false, 'msg' => __('The Nagios Command File Path must be set under console->Settings->NPC', 'npc'));
			return(json_encode($response));
		}

		if (!$nagios->setCommandFile(read_config_option('npc_nagios_cmd_path'))) {
			$response = array('success' => false, 'msg' => $nagios->message);
			return(json_encode($response));
		}

		/* Check that the user has permission to execute the command */
		if (!api_plugin_user_realm_auth('npc1.php')) {
			$response = array('success' => false, 'msg' => __('You do not have permission to execute this command.', 'npc'));
			return(json_encode($response));
		}

		/* Get the command definition */
		$commandDef = $nagios->getCommands($cmd);

		/* Build the args array */
		foreach ($commandDef as $k => $v) {
			if (isset($params[$k])) {
				$value = $params[$k];

				/* Checkboxes from EXT come as a string of either "true" or "false". */
				if ($value == 'true') {
					$value = 1;
				}

				if ($value == 'false') {
					$value = 0;
				}

				if ($k == 'comment') {
					$value = str_replace(array("\r", "\n"), '<br />', $value);
					$value = str_replace("&nbsp;", ' ', $value);
					$value = str_replace(";", ' ', $value);
				}

				$args[$k] = $value;
			}
		}

		/* Build the command string */
        if (!$nagios->setCommand($cmd, $args)) {
			$response = array('success' => false, 'msg' => $nagios->message);
			return(json_encode($response));
		}

		/* Execute the command */
		if (!$nagios->execute()) {
			$response = array('success' => false, 'msg' => $nagios->message);
			return(json_encode($response));
		}

		/* Some forms require extra business logic */
		if ($cmd == "SCHEDULE_HOSTGROUP_SVC_DOWNTIME" && $params['hosts'] == 'true') {
			$cmd = 'SCHEDULE_HOSTGROUP_HOST_DOWNTIME';
			$nagios->setCommand($cmd, $args);
			$nagios->execute();
		}

		return(json_encode(array('success' => true)));
	}

	/**
	 * checkPerf
	 *
	 * Returns a summary of service and host check performance
	 *
	 * @return string   json output
	 */
	function checkPerf($params) {
		if (isset($params['resolution'])) {
			$resolution = $params['resolution'];
		} else {
			$resolution = 7;
		}

		$hostPerf = db_fetch_assoc_prepared('SELECT
				ROUND(MIN(hc.execution_time), 3) AS min_execution,
				ROUND(MAX(hc.execution_time), 3) AS max_execution,
				ROUND(AVG(hc.execution_time), 3) AS avg_execution,
				ROUND(MIN(hc.latency), 3) AS min_latency,
				ROUND(MAX(hc.latency), 3) AS max_latency,
				ROUND(AVG(hc.latency), 3) AS avg_latency
			FROM npc_hostchecks hc, npc_hosts h, npc_objects o
			WHERE hc.host_object_id = o.object_id
				AND o.is_active = 1
				AND hc.start_time > DATE_SUB(NOW(), INTERVAL ? DAY)
				AND hc.host_object_id = h.host_object_id
				AND h.active_checks_enabled = 1',
			array($resolution));

		$servicePerf = db_fetch_assoc_prepared('SELECT
				ROUND(MIN(sc.execution_time), 3) AS min_execution,
				ROUND(MAX(sc.execution_time), 3) AS max_execution,
				ROUND(AVG(sc.execution_time), 3) AS avg_execution,
				ROUND(MIN(sc.latency), 3) AS min_latency,
				ROUND(MAX(sc.latency), 3) AS max_latency,
				ROUND(AVG(sc.latency), 3) AS avg_latency
			FROM npc_servicechecks sc, npc_services s, npc_objects o
			WHERE sc.service_object_id = o.object_id
				AND o.is_active = 1
				AND sc.start_time > DATE_SUB(NOW(), INTERVAL ? DAY)
				AND sc.service_object_id = s.service_object_id
				AND s.active_checks_enabled = 1',
			array($resolution));

		$output = array(
			array_merge(array('name' => __('Service Check Execution Time', 'npc')), array_slice($servicePerf[0], 0, 3)),
			array_merge(array('name' => __('Service Check Latency', 'npc')), array_slice($servicePerf[0], 3)),
			array_merge(array('name' => __('Host Check Execution Time', 'npc')), array_slice($hostPerf[0], 0, 3)),
			array_merge(array('name' => __('Host Check Latency', 'npc')), array_slice($hostPerf[0], 3))
		);

		for ($i = 0; $i < count($output); $i++) {
			foreach ($output[$i] as $key => $value) {
				$newKey = preg_replace('/(_\S+)/', '', $key);
				unset($output[$i][$key]);
				$output[$i][$newKey] = $value;
			}
		}

		return($this->jsonOutput($output));
	}

	/**
	 * formatProcessInfo
	 *
	 * Formats the process info results for display.
	 *
	 * @return string   The formatted results
	 */
	function formatProcessInfo($key, $results) {
		$return = $results[$key];

		$toggle = array(
			'notifications_enabled',
			'active_service_checks_enabled',
			'passive_service_checks_enabled',
			'active_host_checks_enabled',
			'passive_host_checks_enabled',
			'event_handlers_enabled',
			'obsess_over_services',
			'obsess_over_hosts',
			'flap_detection_enabled',
			'process_performance_data'
		);

		if (in_array($key, $toggle, true)) {
			$return = $results[$key] ? __('Yes', 'npc') : __('No', 'npc');
		}

		if ($key == 'program_start_time' || $key == 'status_update_time' || $key == 'last_command_check' || $key == 'last_log_rotation' || $key == 'program_end_time') {
			$format = read_config_option('npc_date_format') . ' ' . read_config_option('npc_time_format');
			$date = date($format, strtotime($results[$key]));

			if (preg_match("/1969/", $date)) {
				return('NA');
			}

			return($date);
        }

		if ($return == '' || !$return) {
			$return = 'NA';
		}

		return($return);
    }
}
