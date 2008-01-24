<?php

class NPC_services {

    var $start = 0;
    var $limit = 20;
    var $state = "any";
    var $portlet = 0;
    var $id = null;

    var $portletCols = array('host_object_id',
                             'host_name',
                             'service_id',
                             'service_object_id',
                             'service_description',
                             'current_state',
                             'output');

    var $defaultCols = array('host_object_id',
                             'host_name',
                             'service_id',
                             'service_object_id',
                             'service_description',
                             'current_state',
                             'last_check',
                             'next_check',
                             'last_state_change',
                             'current_check_attempt',
                             'max_check_attempts',
                             'output');

    private $states = array('ok'       => '0',
                            'warning'  => '1',
                            'critical' => '2',
                            'unkown'   => '3',
                            'pending'  => '-1',
                            'any'      => '0,1,2,3,-1',
                            'not_ok'   => '1,2,3');

    private $friendlyNames = array('instance_id' => 'Instance Id',
                                   'instance_name' => 'Instance Name',
                                   'host_object_id' => 'Host Object Id',
                                   'host_name' => 'Host Name',
                                   'service_id' => 'Service Id',
                                   'service_description' => 'Service Description',
                                   'servicestatus_id' => 'Servicestatus Id',
                                   'service_object_id' => 'Service Object Id',
                                   'status_update_time' => 'Status Update Time',
                                   'output' => 'Output',
                                   'perfdata' => 'Perfdata',
                                   'current_state' => 'Current State',
                                   'has_been_checked' => 'Has Been Checked',
                                   'should_be_scheduled' => 'Should Be Scheduled',
                                   'current_check_attempt' => 'Current Check Attempt',
                                   'max_check_attempts' => 'Max Check Attempts',
                                   'last_check' => 'Last Check',
                                   'next_check' => 'Next Check',
                                   'check_type' => 'Check Type',
                                   'last_state_change' => 'Last State Change',
                                   'last_hard_state_change' => 'Last Hard State Change',
                                   'last_hard_state' => 'Last Hard State',
                                   'last_time_ok' => 'Last Time Ok',
                                   'last_time_warning' => 'Last Time Warning',
                                   'last_time_unknown' => 'Last Time Unknown',
                                   'last_time_critical' => 'Last Time Critical',
                                   'state_type' => 'State Type',
                                   'last_notification' => 'Last Notification',
                                   'next_notification' => 'Next Notification',
                                   'no_more_notifications' => 'No More Notifications',
                                   'notifications_enabled' => 'Notifications Enabled',
                                   'problem_has_been_acknowledged' => 'Problem Has Been Acknowledged',
                                   'acknowledgement_type' => 'Acknowledgement Type',
                                   'current_notification_number' => 'Current Notification Number',
                                   'passive_checks_enabled' => 'Passive Checks Enabled',
                                   'active_checks_enabled' => 'Active Checks Enabled',
                                   'event_handler_enabled' => 'Event Handler Enabled',
                                   'flap_detection_enabled' => 'Flap Detection Enabled',
                                   'is_flapping' => 'Flapping',
                                   'percent_state_change' => 'Percent State Change',
                                   'latency' => 'Latency',
                                   'execution_time' => 'Execution Time',
                                   'scheduled_downtime_depth' => 'In Scheduled Downtime',
                                   'failure_prediction_enabled' => 'Failure Prediction Enabled',
                                   'process_performance_data' => 'Process Performance Data',
                                   'obsess_over_service' => 'Obsess Over Service',
                                   'modified_service_attributes' => 'Modified Service Attributes',
                                   'event_handler' => 'Event Handler',
                                   'check_command' => 'Check Command',
                                   'normal_check_interval' => 'Normal Check Interval',
                                   'retry_check_interval' => 'Retry Check Interval',
                                   'check_timeperiod_object_id' => 'Check Timeperiod Object Id');
                                   

    private $rowCount;


    /**
     * getHostSummary
     * 
     * Returns a summary of the state of all hosts.
     *
     * @return array  The host status summary
     */
    function getServiceSummary() {

        $results = $this->serviceStatus();

        $status = array('critical' => 0,
                        'warning'  => 0,
                        'unknown'  => 0,
                        'ok'       => 0,
                        'pending'  => 0);

        for($i = 0; $i < count($results); $i++) {
            switch($results[$i]['current_state']) {
                case '0':
                    $status['ok']++;
                    break;

                case '1':
                    $status['warning']++;
                    break;

                case '2':
                    $status['critical']++;
                    break;

                case '3':
                    $status['unknown']++;
                    break;

                case '-1':
                    $status['pending']++;
                    break;
            }
        }

        return(array(1, array($status)));
    }

    /**
     * listServiceStatus
     * 
     * Returns a list of services with status information.
     *
     * @return array  Service list
     */
    function getServices() {

        $output = array();

        if ($this->portlet) {
            $columns = $this->portletCols;
        } elseif ($this->id) {
            $columns = 0;
        } else {
            $columns = $this->defaultCols;
        }

        $results = $this->serviceStatus();

        $x = 0;
        for ($i=0; $i < count($results); $i++) {
            foreach ($results[$i] as $key => $value) {
                if (!$columns) {
                    $output[$x][$key] = $value;
                } else if (in_array($key, $columns )) {
                    $output[$x][$key] = $value;
                }
            }
            if (isset($output[$x])) { $x++; }
        }
        return(array($this->rowCount, $output));
    }

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

        $results = $this->serviceStatus();  

        $x = 0;
        foreach ($fields as $key) {  
            $output[$x] = array('name' => $this->friendlyNames[$key], 'value' => $this->renderValue($key, $results[0]));  
            $x++;  
        }  
        return(array(count($output), $output));  
    }

    function renderValue($key, $results) {

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

    function servicePerfData() {

        $sql = "
            SELECT 
                ROUND(MIN(sc.execution_time), 3) AS min_execution, 
                ROUND(MAX(sc.execution_time), 3) AS max_execution, 
                ROUND(AVG(sc.execution_time), 3) AS avg_execution, 
                ROUND(MIN(sc.latency), 3) AS min_latency, 
                ROUND(MAX(sc.latency), 3) AS max_latency, 
                ROUND(AVG(sc.latency), 3) AS avg_latency
            FROM 
                npc_servicechecks sc, 
                npc_services s, 
                npc_objects o 
            WHERE 
                sc.service_object_id = o.object_id 
                AND o.is_active = 1 
                AND sc.service_object_id = s.service_object_id 
                AND s.active_checks_enabled = 1
        ";

        if ($this->id) {
            $sql .= sprintf(" AND s.service_id = %d", $this->id);
        }

        return(db_fetch_assoc($sql));
    }

    function getServiceDowntimeHistory() {

        $sql = "
            SELECT
                npc_instances.instance_id,
                npc_instances.instance_name,
                npc_downtimehistory.object_id,
                obj1.name1 AS host_name,
                obj1.name2 AS service_description,
                npc_downtimehistory.*
            FROM 
                `npc_downtimehistory`
                LEFT JOIN npc_objects as obj1 ON npc_downtimehistory.object_id=obj1.object_id
                LEFT JOIN npc_instances ON npc_downtimehistory.instance_id=npc_instances.instance_id
            WHERE obj1.objecttype_id='2' ";

        if ($this->id) {
            $sql .= sprintf(" AND npc_downtimehistory.object_id = %d", $this->id);
        }

        $sql .= " ORDER BY scheduled_start_time DESC, "
              . " actual_start_time DESC, "
              . " actual_start_time_usec DESC, "
              . " downtimehistory_id DESC ";

        $this->rowCount = count(db_fetch_assoc($sql));

        $sql = sprintf($sql . " LIMIT %d,%d", $this->start, $this->limit);

        return(array($this->rowCount, db_fetch_assoc($sql)));
    }

    function getServiceAlertHistory() {

        $sql = "
            SELECT 
                npc_instances.instance_id,
                npc_instances.instance_name,
                npc_statehistory.object_id,
                obj1.name1 AS host_name,
                obj1.name2 AS service_description,
                npc_statehistory.* 
            FROM 
                `npc_statehistory` 
                LEFT JOIN npc_objects as obj1 ON npc_statehistory.object_id=obj1.object_id 
                LEFT JOIN npc_instances ON npc_statehistory.instance_id=npc_instances.instance_id 
            WHERE 
                obj1.objecttype_id='2' ";

        if ($this->id) {
            $sql .= sprintf(" AND npc_statehistory.object_id = %d", $this->id);
        }

        $this->rowCount = count(db_fetch_assoc($sql));
        $sql .= sprintf(" ORDER BY state_time DESC, state_time_usec DESC LIMIT %d,%d", $this->start, $this->limit);

        return(array($this->rowCount, db_fetch_assoc($sql)));
    }

    function getServiceNotifications() {

        $sql = "
            SELECT
                npc_instances.instance_id,
                npc_instances.instance_name,
                npc_notifications.object_id AS service_object_id,
                obj1.name1 AS host_name,
                obj1.name2 AS service_description,
                npc_notifications.*
            FROM 
                `npc_notifications`
            LEFT JOIN npc_objects as obj1 ON npc_notifications.object_id=obj1.object_id
            LEFT JOIN npc_instances ON npc_notifications.instance_id=npc_instances.instance_id
            WHERE obj1.objecttype_id='2' ";

        if ($this->id) {
            $sql .= sprintf(" AND npc_notifications.object_id = %d", $this->id);
        }

        $this->rowCount = count(db_fetch_assoc($sql));
        $sql .= sprintf(" ORDER BY start_time DESC, start_time_usec DESC LIMIT %d,%d", $this->start, $this->limit);

        return(array($this->rowCount, db_fetch_assoc($sql)));
    }

    function serviceStatus($fields=null) {

        $sql = "
            SELECT 
                npc_instances.instance_id,
                npc_instances.instance_name,
                npc_services.host_object_id,
                obj1.name1 AS host_name,
                npc_services.service_object_id,
                obj1.name2 AS service_description,
                npc_servicestatus.* 
            FROM 
                `npc_servicestatus` 
            LEFT JOIN npc_objects as obj1 
                ON npc_servicestatus.service_object_id=obj1.object_id 
            LEFT JOIN npc_services 
                ON npc_servicestatus.service_object_id=npc_services.service_object_id 
            LEFT JOIN npc_instances 
                ON npc_services.instance_id=npc_instances.instance_id 
        ";

        $where = " WHERE npc_services.config_type='1' ";

        if ($this->id) {
            $where .= sprintf(" AND npc_services.service_object_id = %d", $this->id);
        }

        $where .= " AND npc_servicestatus.current_state in (" . $this->states[$this->state] . ")";
                
        $sql = $sql . $where . " ORDER BY instance_name ASC, host_name ASC, service_description ASC ";

        $this->rowCount = count(db_fetch_assoc($sql));

        $sql = sprintf($sql . " LIMIT %d,%d", $this->start, $this->limit);

        return(db_fetch_assoc($sql));
    }
}
?>
