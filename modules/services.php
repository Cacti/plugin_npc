<?php

class NPC_services {

    /**
     * getHostSummary
     * 
     * Returns a summary of the state of all hosts.
     *
     * @return array  The host status summary
     */
    function getServiceSummary() {

        $results = $this->getServiceStatus();

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
    function listServices($params = array()) {

        $output = array();

        if (isset($params['start'])) {
            $start = $params['start'];
        } else {
            $start = 0;
        }

        if (isset($params['limit'])) {
            $limit = $params['limit'];
        } else {
            $limit = 20;
        }

        if (!isset($params['state'])) {
            $params['state'] = "any";
        }

        switch($params['state']) {
            case "ok":
                $state = array(0);
                break;
            case "warning":
                $state = array(1);
                break;
            case "critical":
                $state = array(2);
                break;
            case "unknown":
                $state = array(3);
                break;
            case "pending":
                $state = array(-1);
                break;
            case "any":
                $state = array(0, 1, 2, 3, -1);
                break;
            case "not_ok":
                $state = array(1, 2, 3);
                break;
        }


        if (isset($params['portlet'])) {
            $columns = array('host_object_id',
                             'host_name',
                             'service_object_id',
                             'service_description',
                             'current_state',
                             'output'
                       );
        } else {
            $columns = array('host_object_id',
                             'host_name',
                             'service_object_id',
                             'service_description',
                             'current_state',
                             'last_check',
                             'next_check',
                             'last_state_change',
                             'current_check_attempt',
                             'max_check_attempts',
                             'output'
                       );
        }


        $results = $this->getServiceStatus($start, $limit);

        $x = 0;
        for ($i=0; $i < count($results); $i++) {
            foreach ($results[$i] as $key => $value) {
                if (in_array($results[$i]['current_state'], $state)) {
                    if(in_array($key, $columns )) {
                        if ($key == 'last_check' || $key == 'next_check' || $key == 'last_state_change') {
                            $value = strtotime($value);
                        }
                        $output[$x][$key] = $value;
                    } 
                }
            }
            if(isset($output[$x])) { $x++; }
        }

        return(array(count($output), $output));
    }

    function showService() {

    }

    function getServiceStatus($start=null, $limit=null, $service_id=null) {


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

        if ($service_id) {
            $where .= " AND npc_services.service_id = $service_id";
        }

        $sql = $sql . $where . " ORDER BY instance_name ASC, host_name ASC, service_description ASC ";

        $count = count(db_fetch_assoc($sql));

        if ($start && $limit) {
            $sql = $sql . " LIMIT $start,$limit";
        }

        return(db_fetch_assoc($sql));
    }

}
?>
