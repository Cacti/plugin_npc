<?php
/*
 +-------------------------------------------------------------------------+
 | Nagios Plugin for Cacti                                                 |
 |                                                                         |
 | Copyright (C) 2007 Billy Gunn (billy@gunn.org)                          |
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti and Nagios are the copyright of their respective owners.          |
 +-------------------------------------------------------------------------+
*/

require_once($config['base_path'] . '/plugins/npc/controllers/comments.php');
require_once($config['base_path'] . '/plugins/npc/controllers/downtime.php');

class NpcServicesController extends Controller {

	function getServices() {
		$services = $this->services();

		$comments = new NpcCommentsController;
		$downtime = new NpcDowntimeController;

		for ($i = 0; $i < count($services); $i++) {
			foreach ($services[$i] as $k => $v) {
				if (is_array($v)) {
					$services[$i] = array_merge($services[$i], $v);
					unset($services[$i][$k]);
				}
			}

			unset($services[$i]['Host']);

			if ($services[$i]['problem_has_been_acknowledged']) {
				$services[$i]['acknowledgement'] = $comments->getAck($services[$i]['service_object_id']);
			}

			$services[$i]['comment'] = $comments->getLastComment($services[$i]['service_object_id']);

			$services[$i]['in_downtime'] = 0;
			if ($downtime->inDowntime($services[$i]['service_object_id'])) {
				$services[$i]['in_downtime'] = 1;
			}
		}

		$response = array(
			'response' => array(
				'value' => array(
					'items'       => $services,
					'total_count' => $this->numRecords,
					'version'     => 1,
				)
			)
		);

		return json_encode($response);
	}

	function getStateInfo() {
		require_once('plugins/npc/controllers/hostgroups.php');
		$obj = new NpcHostgroupsController;
		$hg  = $obj->setupResultsArray();

		$fields = array(
			'current_state', 'output', 'perfdata', 'notes',
			'last_state_change', 'check_command', 'command_line',
			'host_address', 'Host Groups', 'current_check_attempt',
			'last_check', 'next_check', 'event_handler', 'latency',
			'execution_time', 'is_flapping', 'scheduled_downtime_depth',
			'process_performance_data', 'active_checks_enabled',
			'passive_checks_enabled', 'event_handler_enabled',
			'flap_detection_enabled', 'notifications_enabled',
			'obsess_over_service'
		);

		$service = $this->services();
		$results = $this->flattenArray($service);

		$hostgroups = array();
		foreach ($hg as $i => $a) {
			if ($a['host_name'] == $results[0]['host_name']) {
				$hostgroups[] = $a['hostgroup_name'];
			}
		}

		$output = array();
		$x = 0;
		foreach ($fields as $key) {
			if ($key == 'Host Groups') {
				$name  = __('Host Groups', 'npc');
				$value = implode(', ', array_unique($hostgroups));
			} else {
				$name  = $this->columnAlias[$key];
				$value = $this->formatStateInfo($key, $results[0]);
			}
			$output[$x] = array('name' => $name, 'value' => $value);
			$x++;
		}

		return $this->jsonOutput($output);
	}

	function summary() {
		$status = array(
			'critical' => 0,
			'warning'  => 0,
			'unknown'  => 0,
			'ok'       => 0,
			'pending'  => 0
		);

		$services = db_fetch_assoc_prepared('SELECT ss.current_state
			FROM npc_servicestatus ss
			LEFT JOIN npc_services s ON ss.service_object_id = s.service_object_id
			WHERE s.config_type = ?',
			array($this->config_type));

		for ($i = 0; $i < count($services); $i++) {
			$state_key = $services[$i]['current_state'];
			if (isset($this->serviceState[$state_key])) {
				$status[$this->serviceState[$state_key]]++;
			}
		}

		return $this->jsonOutput($status);
	}

	function getServiceStatesByHost($host_object_id) {
		return db_fetch_assoc_prepared('SELECT ss.current_state
			FROM npc_servicestatus ss
			INNER JOIN npc_services s ON ss.service_object_id = s.service_object_id
			WHERE s.host_object_id = ?',
			array($host_object_id));
	}

	function services($id = null, $where = null) {
		$fieldMap = array(
			'service_description' => 'o.name2',
			'host_name'           => 'o.name1',
			'host_alias'          => 'h.alias',
			'notes'               => 's.notes',
			'output'              => 'ss.output'
		);

		$params = array();

		if ($where) {
			$where .= ' AND ';
		} else {
			$where = '';
		}

		$states = $this->stringToState[$this->state];
		$state_list = implode(',', array_map('intval', explode(',', $states)));
		$where .= 'ss.current_state IN (' . $state_list . ')';
		$where .= ' AND s.config_type = ?';
		$params[] = $this->config_type;

		if (isset($this->unhandled)) {
			$where .= ' AND ss.problem_has_been_acknowledged = 0';
		}

		$svc_id = $this->id ? $this->id : $id;
		if ($svc_id) {
			$where .= ' AND s.service_object_id = ?';
			$params[] = intval($svc_id);
		}

		if (isset($this->hostgroup)) {
			$where .= ' AND hg.alias = ?';
			$params[] = $this->hostgroup;
		}

		if ($this->searchString) {
			$where = $this->searchClause($where, $fieldMap, $params);
		}

		$orderBy = 'o.name1 ASC, o.name2 ASC';
		if ($this->sort) {
			$allowed_sorts = array(
				'instance_name', 'host_name', 'service_description', 'host_alias',
				'host_address', 'current_state', 'last_check', 'output',
				'last_state_change'
			);
			if (in_array($this->sort, $allowed_sorts, true)) {
				$dir = ($this->dir == 'DESC') ? 'DESC' : 'ASC';
				$orderBy = $this->sort . ' ' . $dir;
			}
		}

		/* Total count */
		$this->numRecords = db_fetch_cell_prepared(
			'SELECT COUNT(*)
			FROM npc_servicestatus ss
			LEFT JOIN npc_objects o ON ss.service_object_id = o.object_id
			LEFT JOIN npc_services s ON ss.service_object_id = s.service_object_id
			LEFT JOIN npc_hosts h ON s.host_object_id = h.host_object_id
			LEFT JOIN npc_hostgroup_members hgm ON h.host_object_id = hgm.host_object_id
			LEFT JOIN npc_hostgroups hg ON hgm.hostgroup_id = hg.hostgroup_id
			LEFT JOIN npc_instances i ON s.instance_id = i.instance_id
			WHERE ' . $where,
			$params);

		$offset = ($this->currentPage - 1) * $this->limit;

		$services = db_fetch_assoc_prepared(
			'SELECT i.instance_name,
				s.host_object_id,
				s.notes,
				s.notes_url,
				s.action_url,
				s.icon_image,
				s.icon_image_alt,
				h.alias AS host_alias,
				h.address AS host_address,
				h.icon_image AS host_icon_image,
				h.icon_image_alt AS host_icon_image_alt,
				h.host_object_id,
				o.name1 AS host_name,
				o.name2 AS service_description,
				sg.local_graph_id,
				ss.*
			FROM npc_servicestatus ss
			LEFT JOIN npc_objects o ON ss.service_object_id = o.object_id
			LEFT JOIN npc_services s ON ss.service_object_id = s.service_object_id
			LEFT JOIN npc_hosts h ON s.host_object_id = h.host_object_id
			LEFT JOIN npc_hostgroup_members hgm ON h.host_object_id = hgm.host_object_id
			LEFT JOIN npc_hostgroups hg ON hgm.hostgroup_id = hg.hostgroup_id
			LEFT JOIN npc_instances i ON s.instance_id = i.instance_id
			LEFT JOIN npc_service_graphs sg ON ss.service_object_id = sg.service_object_id
			WHERE ' . $where . '
			ORDER BY ' . $orderBy . '
			LIMIT ?, ?',
			array_merge($params, array($offset, $this->limit)));

		return $services;
	}

	function getPerfData($id = null, $host = null, $service = null) {
		$id = $this->id ? $this->id : $id;

		if (!$host) {
			$check_id = db_fetch_cell_prepared(
				'SELECT MAX(servicecheck_id) FROM npc_servicechecks WHERE service_object_id = ?',
				array($id));
		} else {
			$check_id = db_fetch_cell_prepared(
				'SELECT MAX(n.servicecheck_id)
				FROM npc_servicechecks n
				INNER JOIN npc_objects o ON o.object_id = n.service_object_id
				WHERE o.is_active = 1 AND o.name1 = ? AND o.name2 = ?',
				array($host, $service));
		}

		if (!$check_id) {
			return array();
		}

		return db_fetch_assoc_prepared(
			'SELECT * FROM npc_servicechecks WHERE servicecheck_id = ?',
			array($check_id));
	}

	function getPerfHistory($host, $service, $begin, $end = null) {
		$params = array($host, $service, $begin);
		$sql = 'SELECT n.end_time, n.perfdata
			FROM npc_servicechecks n
			INNER JOIN npc_objects o ON o.object_id = n.service_object_id
			WHERE o.is_active = 1 AND o.name1 = ? AND o.name2 = ?
			AND n.end_time >= ?';

		if ($end) {
			$sql .= ' AND n.end_time <= ?';
			$params[] = $end;
		}

		return db_fetch_assoc_prepared($sql, $params);
	}

	function listServicesCli($host = null) {
		$sql = 'SELECT s.*, h.display_name AS host, i.instance_name AS instance
			FROM npc_services s
			LEFT JOIN npc_hosts h ON s.host_object_id = h.host_object_id
			LEFT JOIN npc_instances i ON s.instance_id = i.instance_id';

		if ($host) {
			$sql .= ' WHERE h.display_name = ?';
			return $this->flattenArray(db_fetch_assoc_prepared($sql, array($host)));
		}

		return $this->flattenArray(db_fetch_assoc($sql));
	}

	function getMappedGraph() {
		$results = db_fetch_assoc_prepared(
			'SELECT * FROM npc_service_graphs WHERE service_object_id = ?',
			array($this->id));

		return $this->jsonOutput($results);
	}

	function setMappedGraph($params) {
		$object_id      = intval($params['object_id']);
		$local_graph_id = intval($params['local_graph_id']);

		$existing = db_fetch_row_prepared(
			'SELECT * FROM npc_service_graphs WHERE service_object_id = ?',
			array($object_id));

		if (cacti_sizeof($existing)) {
			db_execute_prepared(
				'UPDATE npc_service_graphs SET local_graph_id = ? WHERE service_object_id = ?',
				array($local_graph_id, $object_id));
		} else {
			db_execute_prepared(
				'INSERT INTO npc_service_graphs (service_object_id, local_graph_id) VALUES (?, ?)',
				array($object_id, $local_graph_id));
		}

		return json_encode(array('success' => true));
	}

	function formatStateInfo($key, $results) {
		$return = isset($results[$key]) ? $results[$key] : '';

		$cs = array(
			'0'  => '<span class="serviceOk" title="OK"></span>',
			'1'  => '<span class="serviceWarning" title="WARNING"></span>',
			'2'  => '<span class="serviceCritical" title="CRITICAL"></span>',
			'3'  => '<span class="serviceUnknown" title="UNKNOWN"></span>',
			'-1' => '<span class="servicePending" title="PENDING"></span>'
		);

		if ($key == 'current_state') {
			$return = isset($cs[$results[$key]]) ? $cs[$results[$key]] : '';
			if ($results['problem_has_been_acknowledged']) {
				$comments = new NpcCommentsController;
				$string = $comments->getAck($results['service_object_id']);
				$ack = preg_split('/\*\|\*/', $string);
				$return .= ' (Acknowledged by ' . html_escape($ack[0]) . ')';
			}
		}

		if ($key == 'current_check_attempt') {
			$return = $results[$key] . '/' . $results['max_check_attempts'];
		}

		if (preg_match('/_enabled/', $key) || $key == 'obsess_over_service') {
			$return = $results[$key] ? __('Yes', 'npc') : __('No', 'npc');
		}

		if ($key == 'last_state_change' || $key == 'last_check' || $key == 'next_check') {
			$format = read_config_option('npc_date_format') . ' ' . read_config_option('npc_time_format');
			$return = date($format, strtotime($results[$key]));
		}

		if ($key == 'scheduled_downtime_depth' || $key == 'is_flapping' || $key == 'process_performance_data') {
			$return = $results[$key] ? __('Yes', 'npc') : __('No', 'npc');
		}

		if ($key == 'command_line') {
			$perf = $this->getPerfData($results['service_object_id']);
			$return = cacti_sizeof($perf) ? $perf[0]['command_line'] : '';
		}

		if ($return == '' || !$return) {
			$return = __('N/A', 'npc');
		}

		return $return;
	}
}
