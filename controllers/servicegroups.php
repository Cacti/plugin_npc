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

require_once('include/auth.php');
require_once('plugins/npc/controllers/comments.php');

class NpcServicegroupsController extends Controller {

	private $hostStatusCache = array();

	function getHostStatusPortlet() {
		$output = array();
		$hosts  = array();
		$fields = array('servicegroup_object_id', 'alias', 'instance_id');

		$results = $this->setupResultsArray();

		for ($i = 0; $i < count($results); $i++) {
			$sg = $results[$i]['servicegroup_object_id'];
			if (!isset($output[$sg])) {
				$output[$sg] = array('down' => 0, 'unreachable' => 0, 'up' => 0, 'pending' => 0);
			}
			if (!isset($hosts[$sg][$results[$i]['host_name']])) {
				$hostState = $this->getServicegroupMemberHoststatus($results[$i]['host_name']);
				if (isset($this->hostState[$hostState])) {
					$output[$sg][$this->hostState[$hostState]]++;
				}
				$hosts[$sg][$results[$i]['host_name']] = 1;
			}
			foreach ($results[$i] as $key => $val) {
				if (in_array($key, $fields, true)) {
					$output[$sg][$key] = $val;
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

	function getServicegroupServiceStatus() {
		$output = array();
		$fields = array('servicegroup_object_id', 'alias', 'instance_id');

		$results = $this->setupResultsArray();

		for ($i = 0; $i < count($results); $i++) {
			$sg = $results[$i]['servicegroup_object_id'];
			if (!isset($output[$sg])) {
				$output[$sg] = array('critical' => 0, 'warning' => 0, 'unknown' => 0, 'ok' => 0, 'pending' => 0);
			}
			foreach ($results[$i] as $key => $val) {
				if ($key == 'current_state' && isset($this->serviceState[$val])) {
					$output[$sg][$this->serviceState[$val]]++;
				} elseif (in_array($key, $fields, true)) {
					$output[$sg][$key] = $val;
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
		$fields = array('servicegroup_object_id', 'alias', 'instance_id', 'host_name');
		$output = array();
		$temp   = array();

		$results = $this->setupResultsArray();

		for ($i = 0; $i < count($results); $i++) {
			$sg   = $results[$i]['servicegroup_object_id'];
			$host = $results[$i]['host_name'];
			$hostState = $this->getServicegroupMemberHoststatus($host);
			if (!isset($temp[$sg][$host])) {
				$temp[$sg][$host] = array(
					'host_state' => $hostState,
					'critical'   => 0,
					'warning'    => 0,
					'unknown'    => 0,
					'ok'         => 0,
					'pending'    => 0
				);
			}
			foreach ($results[$i] as $key => $val) {
				if ($key == 'current_state' && isset($this->serviceState[$val])) {
					$temp[$sg][$host][$this->serviceState[$val]]++;
				} elseif (in_array($key, $fields, true)) {
					$temp[$sg][$host][$key] = $val;
				}
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

	function getHostSummary() {
		/* Placeholder; original had debug print_r/exit */
		return $this->jsonOutput(array());
	}

	function getServices() {
		$results  = $this->setupResultsArray();
		$comments = new NpcCommentsController;
		$services = $this->flattenArray($results);

		for ($i = 0; $i < count($services); $i++) {
			if ($services[$i]['problem_has_been_acknowledged']) {
				$services[$i]['acknowledgement'] = $comments->getAck($services[$i]['service_object_id']);
			}
			$services[$i]['comment'] = $comments->getLastComment($services[$i]['service_object_id']);
		}

		$this->numRecords = count($services);
		$services = array_slice($services, $this->start, $this->limit);

		return $this->jsonOutput($services);
	}

	function getServicegroupMemberHoststatus($hostname) {
		if (isset($this->hostStatusCache[$hostname])) {
			return $this->hostStatusCache[$hostname];
		}

		$result = db_fetch_row_prepared(
			'SELECT hs.current_state
			FROM npc_hoststatus hs
			INNER JOIN npc_hosts h ON hs.host_object_id = h.host_object_id
			WHERE h.display_name = ?',
			array($hostname));

		$this->hostStatusCache[$hostname] = cacti_sizeof($result) ? $result['current_state'] : '0';

		return $this->hostStatusCache[$hostname];
	}

	function getServicegroups() {
		$fieldMap = array(
			'servicegroup_name'   => 'o1.name1',
			'host_name'           => 'o2.name1',
			'service_description' => 'o2.name2',
			'output'              => 'ss.output'
		);
		$params = array();
		$where  = '1 = 1';

		if ($this->id) {
			$where .= ' AND sg.servicegroup_object_id = ?';
			$params[] = intval($this->id);
		}

		if ($this->searchString) {
			$where = $this->searchClause($where, $fieldMap, $params);
		}

		return db_fetch_assoc_prepared(
			'SELECT DISTINCT i.instance_name,
				o1.name1 AS servicegroup_name,
				o2.name1 AS host_name,
				o2.name2 AS service_description,
				ss.*,
				sg.*
			FROM npc_servicegroups sg
			INNER JOIN npc_servicegroup_members sgm ON sg.servicegroup_id = sgm.servicegroup_id
			INNER JOIN npc_servicestatus ss ON sgm.service_object_id = ss.service_object_id
			INNER JOIN npc_objects o1 ON sg.servicegroup_object_id = o1.object_id
			INNER JOIN npc_objects o2 ON ss.service_object_id = o2.object_id
			INNER JOIN npc_instances i ON sg.instance_id = i.instance_id
			WHERE ' . $where . '
			ORDER BY o1.name1 ASC, o2.name1 ASC, o2.name2 ASC',
			$params);
	}

	function setupResultsArray() {
		$results = $this->getServicegroups();
		$results = $this->flattenArray($results);
		$results = $this->flattenNestedArray($results);

		return $results;
	}
}
