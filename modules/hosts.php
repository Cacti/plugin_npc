<?php

class NPC_hosts {

    /**
     * getHostSummary
     * 
     * Returns a summary of the state of all hosts.
     *
     * @return array  The host status summary
     */
    function getHostSummary() {

        $results = $this->getHostStatus();

        $status = array('down' => 0, 
                        'unreachable' => 0,
                        'up' => 0,
                        'pending' => 0);

        for($i = 0; $i < count($results); $i++) {
            switch($results[$i]['current_state']) {
                case '0':
                    $status['up']++;
                    break;

                case '1':
                    $status['down']++;
                    break;

                case '2':
                    $status['unreachable']++;
                    break;

                case '-1':
                    $status['pending']++;
                    break;
            }
        }

        return(array(1, array($status)));
    }

    function getHostStatus($params = array()) {

        $sql = "
            SELECT 
                npc_instances.instance_id ,
                npc_instances.instance_name ,
                npc_hosts.host_object_id ,
                obj1.name1 AS host_name ,
                npc_hoststatus.*
            FROM 
                `npc_hoststatus` 
            LEFT JOIN 
                npc_objects as obj1 
                ON npc_hoststatus.host_object_id=obj1.object_id 
            LEFT JOIN 
                npc_hosts 
                ON npc_hoststatus.host_object_id=npc_hosts.host_object_id 
            LEFT JOIN 
                npc_instances 
                ON npc_hosts.instance_id=npc_instances.instance_id 
            WHERE 
                npc_hosts.config_type='1'
        ";

        if (isset($params['host_id'])) {
            $sql .= " AND npc_hosts.host_id = " . $params['host_id'];
        }

        return(db_fetch_assoc($sql));
    }
}
?>
