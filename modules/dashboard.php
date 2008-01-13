<?php

require_once("plugins/npc/modules/services.php");
require_once("plugins/npc/modules/hosts.php");

class NPC_Dashboard {

    private $rowCount;

    /**
     * getPerfDataSummary
     * 
     * Returns a summary of service and host check performance
     *
     * @return array  The performance summary
     */
    function getPerfSummary() {

        $servicePerf = NPC_services::servicePerfData();
        $hostPerf = NPC_hosts::hostPerfData();

        $output[0] = array_merge(array('name' => 'Service Check Execution Time'), array_slice($servicePerf[0], 0, 3));
        $output[1] = array_merge(array('name' => 'Service Check Latency'), array_slice($servicePerf[0], 3));
        $output[2] = array_merge(array('name' => 'Host Check Execution Time'), array_slice($hostPerf[0], 0, 3));
        $output[3] = array_merge(array('name' => 'Host Check Latency'), array_slice($hostPerf[0], 3));

        for ($i = 0; $i < count($output); $i++) {
            foreach ($output[$i] as $key => $value) {
                $newKey = preg_replace('/(_\S+)/', '', $key);
		unset($output[$i][$key]);
		$output[$i][$newKey] = $value;
            }
        }
        
        return(array(1, $output));
    }

}
?>
