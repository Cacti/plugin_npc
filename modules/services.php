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
                             'service_description',
                             'current_state',
                             'output');

    var $defaultCols = array('host_object_id',
                             'host_name',
                             'service_id',
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

    // A getter to reformat the service data into 
    // rows of key value pairs. This is to assist the
    // client in drawing a vertical grid.
    function getServiceDetail() {
        $results = $this->serviceStatus();

        $x = 0;
        for ($i=0; $i < count($results); $i++) {
            foreach ($results[$i] as $key => $value) {
                $output[$x] = array('name' => $key, 'value' => $value);
                $x++;
            }
        }
        return(array(count($output), $output));
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
            $sql .= " AND s.service_id = " . $this->id;
        }

        return(db_fetch_assoc($sql));
    }

    function serviceStatus() {

        $sql = "
            SELECT 
                npc_instances.instance_id,
                npc_instances.instance_name,
                npc_services.host_object_id,
                obj1.name1 AS host_name,
                npc_services.service_id,
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
            $where .= " AND npc_services.service_id = " . $this->id;
        }

        $where .= " AND npc_servicestatus.current_state in (" . $this->states[$this->state] . ")";
                
        $sql = $sql . $where . " ORDER BY instance_name ASC, host_name ASC, service_description ASC ";

        $this->rowCount = count(db_fetch_assoc($sql));

        $sql = $sql . " LIMIT " . $this->start . "," . $this->limit;

        return(db_fetch_assoc($sql));
    }

}
?>
