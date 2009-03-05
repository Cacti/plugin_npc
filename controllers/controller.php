<?php
/**
 * Base controller class
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

/**
 * Base controller class
 *
 * @package     npc
 * @subpackage  npc.controllers
 */
class Controller {

    var $conn = null;

    /**
     * The default state to query
     *
     * @var string
     * @access public
     */
    var $state = 'any';

    /**
     * String to state mapping
     *
     * @var array
     * @access public
     */
    var $stringToState = array(
        'ok'          => '0',
        'up'          => '0',
        'warning'     => '1',
        'down'        => '1',
        'critical'    => '2',
        'unreachable' => '2',
        'unkown'      => '3',
        'pending'     => '-1',
        'any'         => '0,1,2,3,-1',
        'not_ok'      => '1,2,3'
    );

    /**
     * The starting row for fetching results
     *
     * @var integer
     * @access public
     */
    var $start = 0;

    /**
     * The number of rows to fetch
     *
     * @var integer
     * @access public
     */
    var $limit = 25;

    /**
     * The current page to fetch results for
     *
     * @var integer
     * @access public
     */
    var $currentPage = 1;

    /**
     * The host and service config type
     *
     * @var integer
     * @access public
     */
    var $config_type = 1;

    /**
     * The total number of records from
     * the last query.
     *
     * @var integer
     * @access public
     */
    var $numRecords = null;

    /**
     * The ID of the requested record
     *
     * @var integer
     * @access public
     */
    var $id = null;

    /**
     * The search string passed in from the client
     *
     * @var string
     * @access public
     */
    var $searchString = null;

    /**
     * json encoded list of fields to search
     *
     * @var string
     * @access public
     */
    var $searchFields = null;

    /**
     * Maps a hosts current_state
     *
     * @var array
     * @access public
     */
    var $hostState = array(
        '0'  => 'up',
        '1'  => 'down',
        '2'  => 'unreachable',
        '-1' => 'pending'
    );

    /**
     * Maps a services current_state
     *
     * @var array
     * @access public
     */
    var $serviceState = array(
        '0'  => 'ok',
        '1'  => 'warning',
        '2'  => 'critical',
        '3'  => 'unknown',
        '-1' => 'pending'
    );

    /**
     * Holds all params passed and named.
     *
     * @var mixed
     * @access public
     */
    var $passedArgs = array();

    /**
     * Column aliases
     *
     * @var array
     * @access public
     */
    var $columnAlias = array(
        'instance_id'                   => 'Instance ID',
        'instance_name'                 => 'Instance Name',
        'host_object_id'                => 'Host Object Id',
        'host_name'                     => 'Host Name',
        'service_id'                    => 'Service Id',
        'host_id'                       => 'Host Id',
        'address'                       => 'IP Address',
        'host_address'                  => 'Host Address',
        'service_description'           => 'Service Description',
        'servicestatus_id'              => 'Servicestatus Id',
        'service_object_id'             => 'Service Object Id',
        'status_update_time'            => 'Status Update Time',
        'output'                        => 'Status Information',
        'perfdata'                      => 'Performance Data',
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
        'passive_service_checks_enabled' => 'Passive Service Checks Enabled',
        'passive_host_checks_enabled'   => 'Passive Host Checks Enabled',
        'active_checks_enabled'         => 'Active Checks Enabled',
        'active_host_checks_enabled'    => 'Active Host Checks Enabled',
        'active_service_checks_enabled' => 'Active Service Checks Enabled',
        'event_handler_enabled'         => 'Event Handler Enabled',
        'event_handlers_enabled'        => 'Event Handlers Enabled',
        'flap_detection_enabled'        => 'Flap Detection Enabled',
        'is_flapping'                   => 'Flapping',
        'percent_state_change'          => 'Percent State Change',
        'program_version'               => 'Nagios Version',
        'latency'                       => 'Latency',
        'execution_time'                => 'Execution Time',
        'scheduled_downtime_depth'      => 'In Scheduled Downtime',
        'failure_prediction_enabled'    => 'Failure Prediction Enabled', 
        'process_performance_data'      => 'Processing Performance Data', 
        'obsess_over_service'           => 'Obsess Over Service', 
        'obsess_over_host'              => 'Obsess Over Host', 
        'obsess_over_services'          => 'Obsess Over Services', 
        'obsess_over_hosts'             => 'Obsess Over Hosts', 
        'is_currently_running'          => 'Currently Running', 
        'last_log_rotation'             => 'Last Log Rotation', 
        'last_command_check'            => 'Last External Command Check', 
        'program_start_time'            => 'Program Start Time', 
        'program_end_time'              => 'Program Stop Time', 
        'modified_service_attributes'   => 'Modified Service Attributes', 
        'event_handler'                 => 'Event Handler', 
        'check_command'                 => 'Check Command', 
        'command_line'                  => 'Command Line', 
        'normal_check_interval'         => 'Normal Check Interval', 
        'retry_check_interval'          => 'Retry Check Interval', 
        'process_id'                    => 'Process ID', 
        'check_timeperiod_object_id'    => 'Check Timeperiod Object Id'); 


    /**
     * Constructor.
     *
     */
    function __construct() {
        
        // Get the config type. Default to 1 if not found.
        $config_type = read_config_option('npc_config_type');
        $this->config_type = isset($config_type) ? $config_type : 1;
    }

    function jsonOutput($results=array()) {

        if (!$this->numRecords) {
            $this->numRecords = count($results);
        }

        if (count($results) && !isset($results[0])) {
            $results = array($results);
        }

        // Setup the output array:
        $output = array('totalCount' => $this->numRecords, 'data' => $results);

        return(json_encode($output));
    }

    function csvOutput($results=array()) {

        header("Content-type: text/csv");
        header("Cache-Control: no-store, no-cache");
        header('Content-Disposition: attachment; filename="filename.csv"');

        $outstream = fopen("php://output",'w');

        foreach( $test_data as $row ) {
            fputcsv($outstream, $row, ',', '"');
        }
 
        fclose($outstream);
        exit;
    }

    /**
     * flattenArray
     * 
     * Flattens the 1st level of nesting
     *
     * @return array  list of all services with status
     */
    function flattenArray($array=array()) {

        $newArray = array();

        for ($i = 0; $i < count($array); $i++) {
            foreach ($array[$i] as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $newArray[$i][$k] = $v;
                    }
                } else {
                    $newArray[$i][$key] = $val; 
                }
            }
        }

        return($newArray);
    }

    /**
     * searchClause
     * 
     * Appends search parameters to the passed in where clause
     * @param string $where  An existing where clause
     * @param array $fieldMap  Maps passed in field names
     * @return string  The appended where clasue
     */
    function searchClause($where, $fieldMap) {

        if (!$where) {
            $where = " ( ";
        } else {
            $where .= " AND ( ";
        }

        $fields = json_decode(stripslashes($this->searchFields));
        $count = count($fields);

        $x = 1;
        foreach ($fields as $field) {
            if (isset($fieldMap[$field])) {
                $where .= $fieldMap[$field] . " LIKE '%" . $this->searchString . "%' ";
                if ($x < $count) {
                    $where .= " OR ";
                }
                $x++;
            } else {
                $count = $count - 1;
            }
        }

        $where .= " ) ";

        return($where);
    }

    function flattenNestedArray($array) {

        $results = array();

        $x = 0;
        for ($i = 0; $i < count($array); $i++) {
            foreach ($array[$i] as $key => $val) {
                if (is_array($val)) {
                    $t[0] = $val;
                    $v = $this->flattenArray($t); 
                    unset($array[$i][$key]);
                    foreach ($array[$i] as $key => $val) {
                        if (!is_array($val)) {
                            $a[$key] = $val;
                        }
                    }
                    $results[$x] = array_merge($a, $v[0]);    
                    $x++;
                }
            }
        }

        return($results);
    }

    /**
     * getTimer
     * 
     * Returns time in seconds used for debug timing
     *
     * @return string  - 
     */
    function getTime() {
        $mtime = microtime();
        $mtime = explode(' ', $mtime);
        $mtime = $mtime[1] + $mtime[0];
        return($mtime);
    }

    /**
     * logger
     * 
     * A utility method to wrap the Cacti logging mechanism
     *
     * @param  string $level     The log level of the message (error, warn, etc.)
     * @param  string $class     The calling class
     * @param  string $method    The calling method
     * @param  string $message   The log message
     * @return string  - On error a json encoded error message is returned to the client.
     */
    function logger($level, $class, $method, $message) {

        $logLevelConf = read_config_option('npc_log_level'); 

        $logLevels = array(
            "error" => 1,
            "warn"  => 2,
            "info"  => 3,
            "debug" => 4
        );

        if ($logLevels[$level] <= $logLevelConf) {
            $message = strtoupper($level) . " [$class] ($method) - $message";
            cacti_log($message, false, 'NPC');
        }

        // If this was an error send a genric response to the client
        if ($level == 'error') {
            return(json_encode(array('success' => false, 'msg' => "An error occurred in $class -> $method. See error logs for detail.")));
        }
    }

}

