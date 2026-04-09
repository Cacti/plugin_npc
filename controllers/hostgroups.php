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

if (isset($config)) {
	require_once($config['base_path'] . '/plugins/npc/controllers/services.php');
} else {
	require_once('plugins/npc/controllers/services.php');
}

class NpcHostgroupsController extends Controller {

	private $statusCache = array();

	function getHostgroupHostStatus() {
		$output = array();
		$hosts  = array();

		$fields = array('hostgroup_object_id', 'alias', 'instance_id');
		$results = $this->setupResultsArray();

		for ($i = 0; $i < count($results); $i++) {
			$hg = $results[$i]['hostgroup_object_id'];
			if (!isset($output[$hg])) {
				$output[$hg] = array('down' => 0, 'unreachable' => 0, 'up' => 0, 'pending' => 0);
			}
			if (!isset($hosts[$hg][$results[$i]['host_name']])) {
				$output[$hg][$this->hostState[$results[$i]['current_state']]]++;
				$hosts[$hg][$results[$i]['host_name']] = 1;
			}
			foreach ($results[$i] as $key => $val) {
				if (in_array($key, $fields, true)) {
					$output[$hg][$key] = $val;
				}
			}
		}

		$this->numRecords = count($output);
		$output = array_slice($output, $this->start, $this->limit);

		$response = array(
			'response' => array(
				'value' => array(
					'items'       => $output,
					'total_count' => $this->numRecords,
					'version'     => 1,
				)
			)
		);

		return json_encode($response);
	}

	function getHostgroupServiceStatus() {
		$output = array();
		$fields = array('hostgroup_object_id', 'alias', 'hostgroup_name', 'instance_id');

		$results = $this->setupResultsArray();

		for ($i = 0; $i < count($results); $i++) {
			$hg = $results[$i]['hostgroup_object_id'];
			$ss = $this->getHostgroupMemberServiceStatus($results[$i]['host_object_id']);
			if (!isset($output[$hg])) {
				$output[$hg] = $ss;
			} else {
				foreach ($ss as $k => $v) {
					$output[$hg][$k] = $output[$hg][$k] + $v;
				}
			}
			foreach ($results[$i] as $key => $val) {
				if (in_array($key, $fields, true)) {
					$output[$hg][$key] = $val;
				}
			}
		}

		$this->numRecords = count($output);
		$output = array_slice($output, $this->start, $this->limit);

		$response = array(
			'response' => array(
				'value' => array(
					'items'       => $output,
					'total_count' => $this->numRecords,
					'version'     => 1,
				)
			)
		);

		return json_encode($response);
	}

	function getOverview() {
		$fields = array('hostgroup_object_id', 'alias', 'instance_id', 'host_name');
		$output = array();
		$temp   = array();

		$results = $this->setupResultsArray();

		for ($i = 0; $i < count($results); $i++) {
			$hg   = $results[$i]['hostgroup_object_id'];
			$host = $results[$i]['host_name'];
			$ss   = $this->getHostgroupMemberServiceStatus($results[$i]['host_object_id']);
			if (!isset($temp[$hg][$host])) {
				$ss['host_state'] = $results[$i]['current_state'];
				$temp[$hg][$host] = $ss;
			}
			foreach ($results[$i] as $key => $val) {
				$temp[$hg][$host][$key] = $val;
			}
		}

		$x = 0;
		foreach ($temp as $i => $s) {
			foreach ($s as $h => $v) {
				foreach ($v as $key => $val) {
					$output[$x][$key] = $val;
				}
				$x++;
			}
		}

		$this->numRecords = count($output);
		$output = array_slice($output, $this->start, $this->limit);

		return $this->jsonOutput($output);
	}

	function getHosts() {
		$output = $this->setupResultsArray();
		$this->numRecords = count($output);
		$output = array_slice($output, $this->start, $this->limit);

		return $this->jsonOutput($output);
	}

	function getHostList($params) {
		$allowed_columns = array('alias', 'hostgroup_object_id', 'hostgroup_id');
		$column = key($params);

		if (!in_array($column, $allowed_columns, true)) {
			return array();
		}

		$value = $params[$column];

		return db_fetch_assoc_prepared(
			'SELECT h.host_object_id, h.display_name, h.address
			FROM npc_hosts h
			INNER JOIN npc_hostgroup_members hgm ON hgm.host_object_id = h.host_object_id
			INNER JOIN npc_hostgroups hg ON hg.hostgroup_id = hgm.hostgroup_id
			WHERE hg.' . $column . ' = ?',
			array($value));
	}

	private function getHostgroupMemberServiceStatus($host_object_id) {
		if (isset($this->statusCache[$host_object_id])) {
			return $this->statusCache[$host_object_id];
		}

		$this->statusCache[$host_object_id] = array(
			'critical' => 0, 'warning' => 0, 'unknown' => 0, 'ok' => 0, 'pending' => 0
		);

		$obj = new NpcServicesController;
		$results = $obj->getServiceStatesByHost($host_object_id);

		for ($i = 0; $i < count($results); $i++) {
			$state_key = $results[$i]['current_state'];
			if (isset($this->serviceState[$state_key])) {
				$this->statusCache[$host_object_id][$this->serviceState[$state_key]]++;
			}
		}

		return $this->statusCache[$host_object_id];
	}

	function listHostsCli($hg) {
		return db_fetch_assoc_prepared(
			'SELECT h.host_id, h.host_object_id AS id, h.display_name AS name, h.address
			FROM npc_hosts h
			INNER JOIN npc_hostgroup_members hgm ON hgm.host_object_id = h.host_object_id
			INNER JOIN npc_hostgroups hg ON hg.hostgroup_id = hgm.hostgroup_id
			WHERE hg.alias = ?',
			array($hg));
	}

	function listHostgroupsCli() {
		return db_fetch_assoc('SELECT alias AS name, hostgroup_object_id AS id
			FROM npc_hostgroups
			ORDER BY alias ASC');
	}

	function getHostgroups() {
		$params = array();
		$where  = '1 = 1';

		if ($this->id) {
			$where .= ' AND hg.hostgroup_object_id = ?';
			$params[] = intval($this->id);
		}

		return db_fetch_assoc_prepared(
			'SELECT DISTINCT i.instance_name,
				o1.name1 AS hostgroup_name,
				hs.host_object_id,
				hs.current_state,
				hs.output,
				o2.name1 AS host_name,
				hg.*
			FROM npc_hostgroups hg
			INNER JOIN npc_hostgroup_members hgm ON hg.hostgroup_id = hgm.hostgroup_id
			INNER JOIN npc_hoststatus hs ON hgm.host_object_id = hs.host_object_id
			INNER JOIN npc_objects o1 ON hg.hostgroup_object_id = o1.object_id
			INNER JOIN npc_objects o2 ON hs.host_object_id = o2.object_id
			INNER JOIN npc_instances i ON hg.instance_id = i.instance_id
			WHERE ' . $where . '
			ORDER BY o1.name1 ASC, o2.name1 ASC',
			$params);
	}

	function setupResultsArray() {
		$results = $this->getHostgroups();
		$results = $this->flattenArray($results);
		$results = $this->flattenNestedArray($results);

		return $results;
	}
}
