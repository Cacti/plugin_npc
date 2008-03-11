<?php
/**
 * Nagios controller class
 *
 * This is the access point to various tables used to 
 * access Nagios application data.
 * 
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

require_once("plugins/npc/nagioscmd.php");

/**
 * Nagios controller class
 *
 * This is the access point to various tables used to 
 * access data specific to an entire Nagios install.
 * This includes the Nagios process, check performance,
 * and other Nagios statistics. 
 * 
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcNagiosController extends Controller {

    /**
     * command
     * 
     * Creates a nagios command object passing in command
     * arguments from the client.
     *
     * @param  array    $params - The command and parameters
     * @return string
     */
    function command($params) {

        $nagios = new NagiosCmd;
        $args = array();

        if (!$nagios->setCommandFile(read_config_option('npc_nagios_cmd_path'))) {
            $response = array('success' => false, 'msg' => $nagios->message);
            return(json_encode($response));
        }

        // Get the passed command
        $cmd = $params['command'];

        // Get the command definition
        $commandDef = $nagios->getCommands($cmd);

        // Build the args array
        foreach ($commandDef as $k => $v) {
            if (isset($params[$k])) {
                $value = $params[$k];

                // Checkboxes from EXT come as a string of either "true" or "false".
                // These need to be set to 1 or 0
                if ($value == 'true') {
                    $value = 1;
                }
                if ($value == 'false') {
                    $value = 0;
                }

                if ($k == 'comment') {
                    // Replace newline characters: 
                    $value = str_replace(array("\r", "\n"), '<br />', $value);
                }
                $args[$k] = $value;
            }
        }

        // Buld the command string
        if (!$nagios->setCommand($cmd, $args)) {
            $response = array('success' => false, 'msg' => $nagios->message);
            return(json_encode($response));
        }

        // Execute the command
        if (!$nagios->execute()) {
            $response = array('success' => false, 'msg' => $nagios->message);
            return(json_encode($response));
        }

        // Return success to the form
        return(json_encode(array('success' => true)));
    }

    /**
     * checkPerf
     * 
     * Returns a summary of service and host check performance
     *
     * @return string   json output
     */
    function checkPerf($params) {

        // Set the resolution in days to measure check performance.
        if (isset($params['resolution'])) {
            $resolution = $params['resolution'];
        } else {
            $resolution = 7;
        }

        $q = new Doctrine_Query();
        $q->select('ROUND(MIN(hc.execution_time), 3) AS min_execution,
                    ROUND(MAX(hc.execution_time), 3) AS max_execution,
                    ROUND(AVG(hc.execution_time), 3) AS avg_execution,
                    ROUND(MIN(hc.latency), 3) AS min_latency,
                    ROUND(MAX(hc.latency), 3) AS max_latency,
                    ROUND(AVG(hc.latency), 3) AS avg_latency'
                  );
        $q->from('NpcHostchecks hc, NpcHosts h, NpcObjects o');
        $q->where('hc.host_object_id = o.object_id AND o.is_active = 1 AND hc.start_time > DATE_SUB(NOW(),INTERVAL ? DAY) '
                . 'AND hc.host_object_id = h.host_object_id AND h.active_checks_enabled = 1');

        $hostPerf = $q->execute(array($resolution), Doctrine::FETCH_ARRAY);

        $q = new Doctrine_Query();
        $q->select('ROUND(MIN(sc.execution_time), 3) AS min_execution,
                    ROUND(MAX(sc.execution_time), 3) AS max_execution,
                    ROUND(AVG(sc.execution_time), 3) AS avg_execution,
                    ROUND(MIN(sc.latency), 3) AS min_latency,
                    ROUND(MAX(sc.latency), 3) AS max_latency,
                    ROUND(AVG(sc.latency), 3) AS avg_latency'
                  );
        $q->from('NpcServicechecks sc, NpcServices s, NpcObjects o');
        $q->where('sc.service_object_id = o.object_id AND o.is_active = 1 AND sc.start_time > DATE_SUB(NOW(),INTERVAL ? DAY) '
                . 'AND sc.service_object_id = s.service_object_id AND s.active_checks_enabled = 1');

        $servicePerf = $q->execute(array($resolution), Doctrine::FETCH_ARRAY);

        $output = array(array_merge(array('name' => 'Service Check Execution Time'), array_slice($servicePerf[0], 0, 3)),
                        array_merge(array('name' => 'Service Check Latency'), array_slice($servicePerf[0], 3)),
                        array_merge(array('name' => 'Host Check Execution Time'), array_slice($hostPerf[0], 0, 3)),
                        array_merge(array('name' => 'Host Check Latency'), array_slice($hostPerf[0], 3)));

        for ($i = 0; $i < count($output); $i++) {
            foreach ($output[$i] as $key => $value) {
                $newKey = preg_replace('/(_\S+)/', '', $key);
		        unset($output[$i][$key]);
		        $output[$i][$newKey] = $value;
            }
        }

        return($this->jsonOutput($output));
    }
}
