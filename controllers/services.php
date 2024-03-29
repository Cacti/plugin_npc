<?php
/**
 * Services controller class
 *
 * This is the access point to the npc_services table.
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

require_once($config["base_path"]."/plugins/npc/controllers/comments.php");
require_once($config["base_path"]."/plugins/npc/controllers/downtime.php");

/**
 * Services controller class
 *
 * Services controller provides functionality, such as building the
 * Doctrine queries and formatting output.
 *
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcServicesController extends Controller {

    /**
     * getServices
     *
     * Gets and formats services for output.
     *
     * @return string   json output
     */
    function getServices() {

        $services = $this->services();

        $comments = new NpcCommentsController;
        $downtime = new NpcDowntimeController;

        for ($i = 0; $i < count($services); $i++) {

                foreach($services[$i] as $k => $v) {
                        if (is_array($v)) {
                                $services[$i] = array_merge($services[$i], $v);
                                unset($services[$i][$k]);
                        }
                }

                unset($services[$i]['Host']);
                if ($services[$i]['problem_has_been_acknowledged']) {
                        $services[$i]['acknowledgement'] = $comments->getAck($services[$i]['service_object_id']);
                }

                // Add the last comment to the array
                $services[$i]['comment'] = $comments->getLastComment($services[$i]['service_object_id']);

        // Set the in_downtime bit
        $services[$i]['in_downtime'] = 0;
        if ($downtime->inDowntime($services[$i]['service_object_id'])) {
            $services[$i]['in_downtime'] = 1;
        }
        }

        $response['response']['value']['items'] = $services;
        $response['response']['value']['total_count'] = $this->numRecords;
        $response['response']['value']['version']     = 1;

        return(json_encode($response));
    }

    /**
     * getStateInfo
     *
     * Gets and formats service state information
     *
     * @return string   json output
     */
    function getStateInfo() {

    require_once("plugins/npc/controllers/hostgroups.php");
    $obj = new NpcHostgroupsController;
    $hg = $obj->setupResultsArray();
    // $results[$i]['hostgroup_object_id']

        $fields = array(
            'current_state',
            'output',
            'perfdata',
            'notes',
            'last_state_change',
            'check_command',
            'command_line',
            'host_address',
            'Host Groups',
            'current_check_attempt',
            'last_check',
            'next_check',
            'event_handler',
            'latency',
            'execution_time',
            'is_flapping',
            'scheduled_downtime_depth',
            'process_performance_data',
            'active_checks_enabled',
            'passive_checks_enabled',
            'event_handler_enabled',
            'flap_detection_enabled',
            'notifications_enabled',
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

        $x = 0;
        foreach ($fields as $key) {
            if ($key == 'Host Groups') {
                $name = 'Host Groups';
                $value = implode(", ", array_unique($hostgroups));
            } else {
                $name = $this->columnAlias[$key];
                $value = $this->formatStateInfo($key, $results[0]);
            }

            $output[$x] = array('name' => $name, 'value' => $value);
            $x++;
        }

        return($this->jsonOutput($output));
    }

    /**
     * summary
     *
     * Returns a summary of the state of all services.
     *
     * @return string   json output
     */
    function summary() {

        $status = array('critical' => 0,
                        'warning'  => 0,
                        'unknown'  => 0,
                        'ok'       => 0,
                        'pending'  => 0);

        $q = new Doctrine_Query();
        $q->select('ss.current_state')
          ->from('NpcServicestatus ss')
          ->leftJoin('ss.Service s')
          ->where('s.config_type = ?', $this->config_type);

        $services = $q->execute(array(), Doctrine::HYDRATE_ARRAY);

        for ($i = 0; $i < count($services); $i++) {
            $status[$this->serviceState[$services[$i]['current_state']]]++;
        }

        return($this->jsonOutput($status));
    }

    /**
     * getServiceStatesByHost
     *
     * A utility method to simply return the state of every service belonging
     * to the specified host.
     *
     * @return array  list of all services with status
     */
    function getServiceStatesByHost($host_object_id) {

        $q = new Doctrine_Query();
        $q->select('ss.current_state')
          ->from('NpcServicestatus ss, NpcServices s')
          ->where('ss.service_object_id = s.service_object_id AND s.host_object_id = ?', $host_object_id);

        $results = $q->execute(array(), Doctrine::HYDRATE_ARRAY);

        return($results);
    }

    /**
     * services
     *
     * Retrieves all services along with status information
     *
     * @return array  list of all services with status
     */
    function services($id=null, $where=null) {

        // Maps searchable fields passed in from the client
        $fieldMap = array('service_description' => 'o.name2',
                          'host_name'  => 'o.name1',
                          'host_alias' => 'h.alias',
                          'notes'      => 's.notes',
                          'output'     => 'ss.output');


        // Build the where clause
        if ($where) {
            $where .= ' AND ';
        }

        $where .= " ss.current_state in (" . $this->stringToState[$this->state] . ") AND s.config_type = " . $this->config_type;

        if (isset($this->unhandled)) {
            $where .= " AND ss.problem_has_been_acknowledged = 0 ";
        }

        if ($this->id || $id) {
            $where .= sprintf(" AND s.service_object_id = %d", is_null($id) ? $this->id : $id);;
        }

		if (isset($this->hostgroup)) {
			$where .= sprintf(" AND hg.alias = '%s'", $this->hostgroup);
		}

        if ($this->searchString) {
            $where = $this->searchClause($where, $fieldMap);
        }

		if ($this->sort) {
			$orderBy = $this->sort . ' ' . $this->dir;
		} else {
			$orderBy = 'host_name ASC, service_description ASC';
		}

        $q = new Doctrine_Pager(
            Doctrine_Query::create()
                ->select('i.instance_name,'
                        .'s.host_object_id,'
                        .'s.notes,'
                        .'s.notes_url,'
                        .'s.action_url,'
                        .'s.icon_image,'
                        .'s.icon_image_alt,'
                        .'h.alias AS host_alias,'
                        .'h.address AS host_address,'
                        .'h.icon_image AS host_icon_image,'
                        .'h.icon_image_alt AS host_icon_image_alt,'
                        .'h.host_object_id,'
                        .'hg.hostgroup_id,'
                        .'o.name1 AS host_name,'
                        .'o.name2 AS service_description,'
                        .'g.local_graph_id,'
                        .'ss.*')
                ->from('NpcServicestatus ss')
                ->leftJoin('ss.Object o')
                ->leftJoin('ss.Service s')
                ->leftJoin('s.Host h')
                ->leftJoin('h.Hostgroup hg')
                ->leftJoin('ss.Instance i')
                ->leftJoin('ss.Graph g')
                ->where($where)
                ->orderby($orderBy),
            $this->currentPage,
            $this->limit
        );

        $services = $q->execute(array(), Doctrine::HYDRATE_ARRAY);

        // Set the total number of records
        $this->numRecords = $q->getNumResults();

        return($services);
    }

    /**
     * Returns the last perfdata entry for a particular service
     *
     * @return array
     */
    function getPerfData($id=null, $host=null, $service=null) {

        $id = $this->id ? $this->id : $id;


        // Get the last update
        if (!$host) {
            $q = new Doctrine_Query();
            $q->select('max(n.servicecheck_id) AS id')
            ->from('NpcServicechecks n')
            ->where('n.service_object_id = ?', $id);
            $id = $q->execute();
        } else {
            $q = new Doctrine_Query();
            $q->select('max(n.servicecheck_id) AS id')
            ->from('NpcServicechecks n, NpcObjects o')
            ->where('o.is_active = 1 AND o.object_id = n.service_object_id')
            ->andWhere('o.name1 = ?', $host)
            ->andWhere('o.name2 = ?', $service);
            $id = $q->execute();
        }

        // Get the perf data
        $q = new Doctrine_Query();
        $q->select('n.*')
        ->from('NpcServicechecks n')
        ->where('n.servicecheck_id = ?', $id[0]['id']);

        return($q->execute(array(), Doctrine::HYDRATE_ARRAY));
    }

    /**
     * Returns the performance history for the specified service and period
     *
     * @return array
     */
    function getPerfHistory($host, $service, $begin, $end=null) {

        $q = new Doctrine_Query();
        $q->select('end_time, perfdata')
        ->from('NpcServicechecks n, NpcObjects o')
        ->where('o.is_active = 1 AND o.object_id = n.service_object_id')
        ->andWhere('o.name1 = ?', $host)
        ->andWhere('o.name2 = ?', $service)
        ->andWhere('n.end_time >= ?', $begin);

        if ($end) {
            $q->andWhere('n.end_time <= ?', $end);
        }

        return($q->execute(array(), Doctrine::HYDRATE_ARRAY));
    }


    /**
     * listServivcesCli
     *
     * Returns all services and associated object ID's
     *
     * @return array   Array of services/id's
     */
    function listServicesCli($host = null) {

        $q = new Doctrine_Query();
        $q->select('s.*,'
                  .'h.display_name AS host,'
                  .'i.instance_name AS instance')
          ->from('NpcServices s, s.Host h, s.Instance i');

		if ($host) {
			$q->where('h.display_name = ?', $host);
		}

        return($this->flattenArray($q->execute(array(), Doctrine::HYDRATE_ARRAY)));
    }

    /**
     * getMappedGraph
     *
     * Returns the url to the currently mapped graph
     *
     * @return string   json encoded results
     */
    function getMappedGraph() {
        $q = new Doctrine_Query();
        $q->select('sg.*')
          ->from('NpcServiceGraphs sg')
          ->where('sg.service_object_id = ?', $this->id);

        $results = $q->execute(array(), Doctrine::HYDRATE_ARRAY);

        return($this->jsonOutput($results));
    }

    /**
     * setMappedGraph
     *
     * Sets the graph mapping
     *
     * @return string   json encoded results
     */
    function setMappedGraph($params) {
        $table = $this->conn->getTable('NpcServiceGraphs');

        $results = $table->findByDql("service_object_id = ?", array($params['object_id']));
        $graph = $results[0];

        if (!isset($graph->local_graph_id)) {
            $graph = new NpcServiceGraphs();
        }

        $graph->service_object_id = $params['object_id'];
        $graph->local_graph_id = $params['local_graph_id'];
        $graph->save();

        return(json_encode(array('success' => true)));
    }

    /**
     * formatStateInfo
     *
     * Formats the service state info results for display.
     * This is a workaround for some of the limitations of
     * EXT property grid.
     *
     * @return string   The formatted results
     */
    function formatStateInfo($key, $results) {

        // Set the default return value
        if (isset($results[$key])) {
            $return = $results[$key];
        }

        $cs = array(
            '0'  => '<img ext:qtip="OK" src="images/icons/greendot.gif">',
            '1'  => '<img ext:qtip="WARNING" src="images/icons/yellowdot.gif">',
            '2'  => '<img ext:qtip="CRITICAL" src="images/icons/reddot.gif">',
            '3'  => '<img ext:qtip="UNKNOWN" src="images/icons/orangedot.gif">',
            '-1' => '<img ext:qtip="PENDING" src="images/icons/bluedot.gif">'
        );

        if ($key == 'current_state') {
            $return = $cs[$results[$key]];
            if ($results['problem_has_been_acknowledged']) {
                $comments = new NpcCommentsController;
                $string = $comments->getAck($results['service_object_id']);
                $ack = preg_split("/\*\|\*/", $string);
                $return = '<pre>' . $return . '   (Acknowledged by ' . $ack[0] . ')</pre>';
            }
        }

        if ($key == 'current_check_attempt') {
            $return = $results[$key] . '/' . $results['max_check_attempts'];
        }

        if (preg_match("/_enabled/", $key) || $key == 'obsess_over_service') {
            if($results[$key]) {
                $return = '<img src="images/icons/tick.png">';
            } else {
                $return = '<img src="images/icons/cross.png">';
            }
        }

        if ($key == 'last_state_change' || $key == 'last_check' || $key == 'next_check') {
            $format = read_config_option('npc_date_format') . ' ' . read_config_option('npc_time_format');
            $return = date($format, strtotime($results[$key]));
        }

        if ($key == 'scheduled_downtime_depth' || $key == 'is_flapping' || $key == 'process_performance_data') {
            if ($results[$key]) {
                $return = 'Yes';
            } else {
                $return = 'No';
            }
        }

        // Add the full command as a tooltip
        if ($key == 'command_line') {
            $perf = $this->getPerfData($results['service_object_id']);
            $return = $perf[0]['command_line'];
        }

        if ($return == '' || !$return) {
            $return = 'NA';
        }

        return($return);
    }
}



