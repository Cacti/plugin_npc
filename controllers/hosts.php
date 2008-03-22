<?php
/**
 * Hosts controller class
 *
 * This is the access point to the npc_hosts table.
 *
 * @filesource
 * @author              Billy Gunn <billy@gunn.org>
 * @copyright           Copyright (c) 2007
 * @link                http://trac2.assembla.com/npc
 * @package             npc
 * @subpackage          npc.controllers
 * @since               NPC 2.0
 * @version             $Id: $
 */

require_once("include/auth.php");
require_once("plugins/npc/controllers/comments.php");

/**
 * Hosts controller class
 *
 * Hosts controller provides functionality, such as building the 
 * Doctrine querys and formatting output.
 * 
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcHostsController extends Controller {

    /**
     * getHosts
     * 
     * Gets and formats hosts for output. 
     *
     * @return string   json output
     */
    function getHosts() {

        $results = $this->hosts();

        $comments = new NpcCommentsController;

        $hosts = $this->flattenArray($results);


        for ($i = 0; $i < count($hosts); $i++) {
            if ($hosts[$i]['problem_has_been_acknowledged']) {
                $hosts[$i]['acknowledgement'] = $comments->getAck($hosts[$i]['host_object_id']);
            }
            // Add the last comment to the array
            $hosts[$i]['comment'] = $comments->getLastComment($hosts[$i]['host_object_id']);

            // Count the services and delete the entries
            $services = 0;
            foreach ($hosts[$i] as $k => $v) {
                if (is_array($v)) {
                    $services++;
                    unset($hosts[$i][$k]);
                }
            }

            $hosts[$i]['service_count'] = $services;
        }    

        return($this->jsonOutput($hosts));
    }

    /**
     * getStateInfo
     * 
     * Gets and formats host state information
     *
     * @return string   json output
     */
    function getStateInfo() {

        $fields = array(
            'current_state',
            'output',
            'last_state_change',
            'check_command',
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
            'obsess_over_host'
        );

        $hosts = $this->hosts();

        $results = $this->flattenArray($hosts);

        $x = 0;
        foreach ($fields as $key) {
            $output[$x] = array('name' => $this->columnAlias[$key], 'value' => $this->formatStateInfo($key, $results[0]));
            $x++;
        }

        return($this->jsonOutput($output));
    }

    /**
     * summary
     * 
     * Returns a state count for all hosts
     *
     * @return string   json output
     */
    function summary() {

        $status = array(
            'down'        => 0, 
            'unreachable' => 0,
            'up'          => 0,
            'pending'     => 0
        );

        $q = new Doctrine_Query();
        $hosts = $q->from('NpcHosts h')->where('h.config_type = 1')->execute();

        foreach($hosts as $host) {
            $status[$this->hostState[$host->Status->current_state]]++;
        }

        return($this->jsonOutput($status));
    }

    function getPerfData($id) {

        $q = new Doctrine_Query();
        $q->select('perfdata')->from('NpcHostchecks')->where('host_object_id = ?', $id);
        
        return($q->execute(array(), Doctrine::FETCH_ARRAY));
    }

    /**
     * hosts
     * 
     * Retrieves all hosts along with status information
     *
     * @return array 
     */
    function hosts() {

        // Maps searchable fields passed in from the client
        $fieldMap = array('host_name' => 'o.name1',
                          'alias' => 'h.alias',
                          'output' => 'hs.output');


        // Build the where clause
        $where = ' h.config_type = 1 ';

        if ($this->id) {
            $where .= sprintf(" AND hs.host_object_id = %d", $this->id);
        }

        $where .= " AND hs.current_state in (" . $this->stringToState[$this->state] . ") ";

        if ($this->searchString) {
            $where = $this->searchClause($where, $fieldMap);
        }

        $q = new Doctrine_Pager(
            Doctrine_Query::create()
                ->select('i.instance_name,'
                        .'o.name1 AS host_name,'
                        .'h.alias,'
                        .'s.service_object_id,'
                        .'s.display_name,'
                        .'hs.*')
                ->from('NpcHoststatus hs')
                ->leftJoin('hs.Object o')
                ->leftJoin('hs.Host h')
                ->leftJoin('hs.Instance i')
                ->leftJoin('hs.Services s')
                ->where("$where")
                ->orderby( 'i.instance_name ASC, host_name ASC' ),
            $this->currentPage,
            $this->limit
        );

        $hosts = $q->execute(array(), Doctrine::FETCH_ARRAY);

        // Set the total number of records
        $this->numRecords = $q->getNumResults();

        return($hosts);
    }

    /**
     * listHostsCli
     * 
     * Returns all hosts and associated object ID's
     *
     * @return array   Array of hosts/id's
     */
    function listHostsCli() {

        $q = new Doctrine_Query();
        $q->select('display_name as name, host_object_id as id')->from('NpcHosts')->orderBy('display_name ASC');

        return($q->execute(array(), Doctrine::FETCH_ARRAY));
    }

    /**
     * formatStateInfo
     * 
     * Formats the host state info results for display
     *
     * @return string   The formatted results
     */
    function formatStateInfo($key, $results) {

        // Set the default return value
        $return = $results[$key];

        $cs = array(
            '0'  => '<img ext:qtip="UP" src="images/icons/greendot.gif">',
            '1'  => '<img ext:qtip="DOWN" src="images/icons/reddot.gif">',
            '2'  => '<img ext:qtip="UNREACHABLE" src="images/icons/reddot.gif">',
            '-1' => '<img ext:qtip="PENDING" src="images/icons/bluedot.gif">'
        );

        if ($key == 'current_state') {
            $return = $cs[$results[$key]];
        }

        if ($key == 'current_check_attempt') {
            $return = $results[$key] . '/' . $results['max_check_attempts'];
        }

        if (preg_match("/_enabled/", $key) || $key == 'obsess_over_host') {
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

        if ($return == '' || !$return) {
            $return = 'NA';
        }

        return($return);
    }

}
