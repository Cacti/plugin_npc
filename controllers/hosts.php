<?php

class NPC_hosts {

    var $id = null;

    private $currentState = array('0' => 'up',
                                  '1' => 'down',
                                  '2' => 'unreachable',
                                  '-1' => 'pending');

    private $rowCount;

    /**
     * getHostSummary
     * 
     * Returns a summary of the state of all hosts.
     *
     * @return array  The host status summary
     */
    function getHostSummary() {

        $results = $this->hostStatus();

        $status = array('down' => 0, 
                        'unreachable' => 0,
                        'up' => 0,
                        'pending' => 0);

        for($i = 0; $i < count($results); $i++) {
            $status[$this->currentState[$results[$i]['current_state']]]++;
        }

        return(array(1, array($status)));
    }

    function hostPerfData() {

        $sql = "
            SELECT 
                ROUND(MIN(hc.execution_time), 3) AS min_execution, 
                ROUND(MAX(hc.execution_time), 3) AS max_execution, 
                ROUND(AVG(hc.execution_time), 3) AS avg_execution, 
                ROUND(MIN(hc.latency), 3) AS min_latency, 
                ROUND(MAX(hc.latency), 3) AS max_latency, 
                ROUND(AVG(hc.latency), 3) AS avg_latency
            FROM 
                npc_hostchecks hc, 
                npc_hosts h, 
                npc_objects o 
            WHERE 
                hc.host_object_id = o.object_id 
                AND o.is_active = 1 
                AND hc.host_object_id = h.host_object_id 
                AND h.active_checks_enabled = 1
        ";

        if ($this->id) {
            $sql .= " AND h.host_id = " . $this->id;
        }

        return(db_fetch_assoc($sql));
    }

    function hostStatus() {

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

        if ($this->host_id) {
            $sql .= " AND npc_hosts.host_id = " . $this->host_id;
        }

        return(db_fetch_assoc($sql));
    }
}
?>
