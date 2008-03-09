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
 * @version             $Id: $
 */

require_once("include/auth.php");
require_once("plugins/npc/controllers/comments.php");

/**
 * Services controller class
 *
 * Services controller provides functionality, such as building the 
 * Doctrine querys and formatting output.
 * 
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcServicesController extends Controller {

    /**
     * Column aliases
     *
     * @var array
     * @access public
     */
    var $friendlyNames = array(
        'instance_id'                   => 'Instance Id',
        'instance_name'                 => 'Instance Name',
        'host_object_id'                => 'Host Object Id',
        'host_name'                     => 'Host Name',
        'service_id'                    => 'Service Id',
        'service_description'           => 'Service Description',
        'servicestatus_id'              => 'Servicestatus Id',
        'service_object_id'             => 'Service Object Id',
        'status_update_time'            => 'Status Update Time',
        'output'                        => 'Output',
        'perfdata'                      => 'Perfdata',
        'current_state'                 => 'Current State',
        'has_been_checked'              => 'Has Been Checked',
        'should_be_scheduled'           => 'Should Be Scheduled',
        'current_check_attempt'         => 'Current Check Attempt',
        'max_check_attempts'            => 'Max Check Attempts',
        'last_check'                    => 'Last Check',
        'next_check'                    => 'Next Check',
        'check_type'                    => 'Check Type',
        'last_state_change'             => 'Last State Change',
        'last_hard_state_change'        => 'Last Hard State Change',
        'last_hard_state'               => 'Last Hard State',
        'last_time_ok'                  => 'Last Time Ok',
        'last_time_warning'             => 'Last Time Warning',
        'last_time_unknown'             => 'Last Time Unknown',
        'last_time_critical'            => 'Last Time Critical',
        'state_type'                    => 'State Type',
        'last_notification'             => 'Last Notification',
        'next_notification'             => 'Next Notification',
        'no_more_notifications'         => 'No More Notifications',
        'notifications_enabled'         => 'Notifications Enabled',
        'problem_has_been_acknowledged' => 'Problem Has Been Acknowledged',
        'acknowledgement_type'          => 'Acknowledgement Type',
        'current_notification_number'   => 'Current Notification Number',
        'passive_checks_enabled'        => 'Passive Checks Enabled',
        'active_checks_enabled'         => 'Active Checks Enabled',
        'event_handler_enabled'         => 'Event Handler Enabled',
        'flap_detection_enabled'        => 'Flap Detection Enabled',
        'is_flapping'                   => 'Flapping',
        'percent_state_change'          => 'Percent State Change',
        'latency'                       => 'Latency',
        'execution_time'                => 'Execution Time',
        'scheduled_downtime_depth'      => 'In Scheduled Downtime',
        'failure_prediction_enabled'    => 'Failure Prediction Enabled',
        'process_performance_data'      => 'Process Performance Data',
        'obsess_over_service'           => 'Obsess Over Service',
        'modified_service_attributes'   => 'Modified Service Attributes',
        'event_handler'                 => 'Event Handler',
        'check_command'                 => 'Check Command',
        'normal_check_interval'         => 'Normal Check Interval',
        'retry_check_interval'          => 'Retry Check Interval',
        'check_timeperiod_object_id'    => 'Check Timeperiod Object Id');
        
    /**
     * getServices
     * 
     * Gets and formats services for output. 
     *
     * @return string   json output
     */
    function getServices() {

        $results = $this->services();

        $comments = new NpcCommentsController;

        $services = $this->flattenArray($results);

        for ($i = 0; $i < count($services); $i++) {
            if ($services[$i]['problem_has_been_acknowledged']) {
                $services[$i]['acknowledgement'] = $comments->getAck($services[$i]['service_object_id']);
            }
            // Add the last comment to the array
            $services[$i]['comment'] = $comments->getLastComment($services[$i]['service_object_id']);
        }    

        return($this->jsonOutput($services));
    }

    /**
     * getServiceStateInfo
     * 
     * Gets and formats service state information
     *
     * @return string   json output
     */
    function getServiceStateInfo() {

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
            'active_checks_enabled',
            'passive_checks_enabled',
            'event_handler_enabled',
            'flap_detection_enabled',
            'notifications_enabled',
            'failure_prediction_enabled',
            'process_performance_data',
            'obsess_over_service'
        );

        $service = $this->services();

        $results = $this->flattenArray($service);

        $x = 0;
        foreach ($fields as $key) {
            $output[$x] = array('name' => $this->friendlyNames[$key], 'value' => $this->formatServiceStateInfo($key, $results[0]));
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
        $services = $q->from('NpcServices s')->where('s.config_type = 1')->execute();

        foreach($services as $service) {
            $status[$this->serviceState[$service->Status->current_state]]++;
        }

        return($this->jsonOutput($status));
    }

    /**
     * services
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

        $results = $q->execute(array(), Doctrine::FETCH_ARRAY);

        return($results);
    }

    /**
     * services
     * 
     * Retrieves all services along with status information
     *
     * @return array  list of all services with status
     */
    function services($id=null, $where='') {

        // Maps searchable fields passed in from the client
        $fieldMap = array('service_description' => 'o.name2',
                          'host_name' => 'o.name1',
                          'output' => 'ss.output');


        // Build the where clause
        if ($where != '') {
            $where .= ' AND ';
        }

        $where .= ' s.config_type = 1 ';

        if ($this->id || $id) {
            $where .= sprintf(" AND s.service_object_id = %d", is_null($id) ? $this->id : $id);;
        }

        $where .= " AND ss.current_state in (" . $this->stringToState[$this->state] . ") ";

        if ($this->searchString) {
            $where = $this->searchClause($where, $fieldMap);    
        }

        $q = new Doctrine_Pager(
            Doctrine_Query::create()
                ->select('i.instance_name,'
                        .'s.host_object_id,'
                        .'o.name1 AS host_name,'
                        .'o.name2 AS service_description,'
                        .'ss.*')
                ->from('NpcServicestatus ss')
                ->leftJoin('ss.Object o')
                ->leftJoin('ss.Service s')
                ->leftJoin('ss.Instance i')
                ->where("$where")
                ->orderby( 'i.instance_name ASC, host_name ASC, service_description ASC' ),
            $this->currentPage,
            $this->limit
        );
                
        $services = $q->execute(array(), Doctrine::FETCH_ARRAY);

        // Set the total number of records
        $this->numRecords = $q->getNumResults();

        return($services);
    }

    function getPerfData($id) {

        $q = new Doctrine_Pager(
            Doctrine_Query::create()
                ->select('n.perfdata')
            	->from('NpcServicechecks n, NpcServices n2')
          	    ->where('n.service_object_id = ? AND n2.service_object_id = n.service_object_id AND n.start_time'
                       .' > now() - INTERVAL n2.check_interval * 2 MINUTE', $id)
          	    ->orderby('n.start_time DESC'), 0, 1);

        return($q->execute(array(), Doctrine::FETCH_ARRAY));
    }

    /**
     * listServivcesCli
     *
     * Returns all services and associated object ID's
     *
     * @return array   Array of services/id's
     */
    function listServicesCli() {

        $q = new Doctrine_Query();
        $q->select('display_name as name, service_object_id as id')->from('NpcServices')->orderBy('display_name ASC');

        return($q->execute(array(), Doctrine::FETCH_ARRAY));
    }

    /**
     * formatServiceStateInfo
     * 
     * Formats the service state info results for display
     *
     * @return string   The formatted results
     */
    function formatServiceStateInfo($key, $results) {

        // Set the default return value
        $return = $results[$key];

        $cs = array(
            '0'  => '<img src="images/nagios/recovery.png">',
            '1'  => '<img src="images/nagios/warning.png">',
            '2'  => '<img src="images/nagios/critical.png">',
            '3'  => '<img src="images/nagios/unknown.png">',
            '-1' => '<img src="images/nagios/info.png">'
        );

        if ($key == 'current_state') {
            $return = $cs[$results[$key]];
        }

        if ($key == 'current_check_attempt') {
            $return = $results[$key] . '/' . $results['max_check_attempts'];
        }

        if ($key == 'is_flapping') {
            if ($results[$key]) {
                $return = 'Yes';
            } else {
                $return = 'No';
            }
        }

        if (preg_match("/_enabled/", $key) || $key == 'process_performance_data' || $key == 'obsess_over_service') {
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

        if ($key == 'scheduled_downtime_depth') {
            if ($results[$key]) {
                $return = 'Yes';
            } else {
                $return = 'No';
            }
        }

        if ($return == '') {
            $return = 'NA';
        }

        if (!$return) {
            $return = 'NA';
        }

        return($return);
    }
}



