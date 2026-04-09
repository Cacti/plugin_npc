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

class NpcHostsController extends Controller {

	function getHosts() {
		$results = $this->hosts();

		$comments = new NpcCommentsController;
		$hosts = $this->flattenArray($results);

		for ($i = 0; $i < count($hosts); $i++) {
			if ($hosts[$i]['problem_has_been_acknowledged']) {
				$hosts[$i]['acknowledgement'] = $comments->getAck($hosts[$i]['host_object_id']);
			}
			$hosts[$i]['comment'] = $comments->getLastComment($hosts[$i]['host_object_id']);

			$services = 0;
			foreach ($hosts[$i] as $k => $v) {
				if (is_array($v)) {
					$services++;
					unset($hosts[$i][$k]);
				}
			}
			$hosts[$i]['service_count'] = $services;
		}

		$response = array(
			'response' => array(
				'value' => array(
					'items'       => $hosts,
					'total_count' => $this->numRecords,
					'version'     => 1,
				)
			)
		);

		return json_encode($response);
	}

	function getStateInfo() {
		$fields = array(
			'current_state', 'output', 'perfdata', 'last_state_change',
			'check_command', 'address', 'current_check_attempt', 'last_check',
			'next_check', 'event_handler', 'latency', 'execution_time',
			'is_flapping', 'scheduled_downtime_depth', 'process_performance_data',
			'active_checks_enabled', 'passive_checks_enabled',
			'event_handler_enabled', 'flap_detection_enabled',
			'notifications_enabled', 'obsess_over_host'
		);

		$hosts   = $this->hosts();
		$results = $this->flattenArray($hosts);
		$output  = array();

		$x = 0;
		foreach ($fields as $key) {
			$output[$x] = array(
				'name'  => $this->columnAlias[$key],
				'value' => $this->formatStateInfo($key, $results[0])
			);
			$x++;
		}

		return $this->jsonOutput($output);
	}

	function summary() {
		$status = array(
			'down'        => 0,
			'unreachable' => 0,
			'up'          => 0,
			'pending'     => 0
		);

		$hosts = db_fetch_assoc_prepared('SELECT hs.current_state
			FROM npc_hoststatus hs
			LEFT JOIN npc_hosts h ON hs.host_object_id = h.host_object_id
			WHERE h.config_type = ?',
			array($this->config_type));

		for ($i = 0; $i < count($hosts); $i++) {
			$state_key = $hosts[$i]['current_state'];
			if (isset($this->hostState[$state_key])) {
				$status[$this->hostState[$state_key]]++;
			}
		}

		return $this->jsonOutput($status);
	}

	function getPerfData($id) {
		return db_fetch_assoc_prepared('SELECT perfdata
			FROM npc_hostchecks
			WHERE host_object_id = ?',
			array($id));
	}

	function hosts() {
		$fieldMap = array(
			'host_name' => 'o.name1',
			'alias'     => 'h.alias',
			'output'    => 'hs.output'
		);

		$params = array();
		$where  = '';

		/* State filter */
		$states = $this->stringToState[$this->state];
		$state_list = implode(',', array_map('intval', explode(',', $states)));
		$where .= 'hs.current_state IN (' . $state_list . ')';
		$where .= ' AND h.config_type = ?';
		$params[] = $this->config_type;

		if ($this->id) {
			$where .= ' AND hs.host_object_id = ?';
			$params[] = intval($this->id);
		}

		if ($this->searchString) {
			$where = $this->searchClause($where, $fieldMap, $params);
		}

		$orderBy = 'i.instance_name ASC, o.name1 ASC';
		if ($this->sort) {
			$allowed_sorts = array(
				'instance_name', 'host_name', 'alias', 'address',
				'current_state', 'last_check', 'output', 'last_state_change'
			);
			if (in_array($this->sort, $allowed_sorts, true)) {
				$dir = ($this->dir == 'DESC') ? 'DESC' : 'ASC';
				$orderBy = $this->sort . ' ' . $dir;
			}
		}

		/* Total count */
		$this->numRecords = db_fetch_cell_prepared(
			'SELECT COUNT(*)
			FROM npc_hoststatus hs
			LEFT JOIN npc_objects o ON hs.host_object_id = o.object_id
			LEFT JOIN npc_hosts h ON hs.host_object_id = h.host_object_id
			LEFT JOIN npc_instances i ON h.instance_id = i.instance_id
			WHERE ' . $where,
			$params);

		/* Paginated results */
		$offset = ($this->currentPage - 1) * $this->limit;

		$hosts = db_fetch_assoc_prepared(
			'SELECT i.instance_name,
				o.name1 AS host_name,
				h.alias,
				h.address,
				h.notes,
				h.notes_url,
				h.action_url,
				h.icon_image,
				h.icon_image_alt,
				hg.local_graph_id,
				hs.*
			FROM npc_hoststatus hs
			LEFT JOIN npc_objects o ON hs.host_object_id = o.object_id
			LEFT JOIN npc_hosts h ON hs.host_object_id = h.host_object_id
			LEFT JOIN npc_instances i ON h.instance_id = i.instance_id
			LEFT JOIN npc_host_graphs hg ON hs.host_object_id = hg.host_object_id
			WHERE ' . $where . '
			ORDER BY ' . $orderBy . '
			LIMIT ?, ?',
			array_merge($params, array($offset, $this->limit)));

		return $hosts;
	}

	function listHostsCli() {
		return db_fetch_assoc('SELECT display_name AS name, host_object_id AS id, address
			FROM npc_hosts
			ORDER BY display_name ASC');
	}

	function getMappedGraph() {
		$results = db_fetch_assoc_prepared(
			'SELECT * FROM npc_host_graphs WHERE host_object_id = ?',
			array($this->id));

		return $this->jsonOutput($results);
	}

	function setMappedGraph($params) {
		$object_id     = intval($params['object_id']);
		$local_graph_id = intval($params['local_graph_id']);

		$existing = db_fetch_row_prepared(
			'SELECT * FROM npc_host_graphs WHERE host_object_id = ?',
			array($object_id));

		if (cacti_sizeof($existing)) {
			db_execute_prepared(
				'UPDATE npc_host_graphs SET local_graph_id = ? WHERE host_object_id = ?',
				array($local_graph_id, $object_id));
		} else {
			db_execute_prepared(
				'INSERT INTO npc_host_graphs (host_object_id, local_graph_id) VALUES (?, ?)',
				array($object_id, $local_graph_id));
		}

		return json_encode(array('success' => true));
	}

	function formatStateInfo($key, $results) {
		$return = isset($results[$key]) ? $results[$key] : '';

		$cs = array(
			'0'  => '<span class="hostUp" title="UP"></span>',
			'1'  => '<span class="hostDown" title="DOWN"></span>',
			'2'  => '<span class="hostUnreachable" title="UNREACHABLE"></span>',
			'-1' => '<span class="hostPending" title="PENDING"></span>'
		);

		if ($key == 'current_state') {
			$return = isset($cs[$results[$key]]) ? $cs[$results[$key]] : '';
			if ($results['problem_has_been_acknowledged']) {
				$comments = new NpcCommentsController;
				$string = $comments->getAck($results['host_object_id']);
				$ack = preg_split('/\*\|\*/', $string);
				$return .= ' (Acknowledged by ' . html_escape($ack[0]) . ')';
			}
		}

		if ($key == 'current_check_attempt') {
			$return = $results[$key] . '/' . $results['max_check_attempts'];
		}

		if (preg_match('/_enabled/', $key) || $key == 'obsess_over_host') {
			$return = $results[$key] ? __('Yes', 'npc') : __('No', 'npc');
		}

		if ($key == 'last_state_change' || $key == 'last_check' || $key == 'next_check') {
			$format = read_config_option('npc_date_format') . ' ' . read_config_option('npc_time_format');
			$return = date($format, strtotime($results[$key]));
		}

		if ($key == 'scheduled_downtime_depth' || $key == 'is_flapping' || $key == 'process_performance_data') {
			$return = $results[$key] ? __('Yes', 'npc') : __('No', 'npc');
		}

		if ($return == '' || !$return) {
			$return = __('N/A', 'npc');
		}

		return $return;
	}
}
