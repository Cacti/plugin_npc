<?php

require_once("include/auth.php");
require_once($config["base_path"]."/plugins/npc/controllers/hosts.php");
require_once($config["base_path"]."/plugins/npc/controllers/services.php");

class NpcCactiController extends Controller {

    /**
     * getSetting
     * 
     * Returns the value of the requested setting
     *
     * @return string  The setting value
     */
    function getSetting($setting) {
        return(array('setting' => read_config_option($setting)));
    }

    function getHostTemplates() {
        $results = db_fetch_assoc("SELECT id, name FROM host_template");
        return($this->jsonOutput($results));
    }

    /**
     * isMapped
     * 
     * Checks to see if the passed nagios host is currently mapped 
     * to a cacti device.
     *
     * @return boolean
     */
    function isMapped($id) {
        $results = db_fetch_assoc("SELECT id FROM host WHERE npc_host_object_id = $id");
        return(count($results));
    }

    /**
     * mapHost
     * 
     * Maps a nagios host to a cacti host
     *
     * @return boolean
     */
    function mapHost($npc_id, $cacti_id) {
        db_execute("UPDATE host SET npc_host_object_id = $npc_id WHERE id = $cacti_id");
    }

    /**
     * getHostnames
     * 
     * Returns a list of cacti host ip addresses
     *
     * @return array   array of ip addresses
     */
    function getHostnames() {
        return(db_fetch_assoc("SELECT id, hostname FROM host"));
    }

    /**
     * addDataInputMethod
     * 
     * Adds a data input method for Nagios perf data
     *
     * @return string   json encoded results
     */
    function addDataInputMethod($params) {

        $name = 'NPC - Perfdata - ' . mysql_real_escape_string($params['host']);

        if (isset($params['service'])) {
            $type = 'service';
            $class = 'NpcServicesController';
            $name .= ': ' . mysql_real_escape_string($params['service']);
        } else {
            $type = 'host';
            $class = 'NpcHostsController';
        }

        $object_id = mysql_real_escape_string($params['object_id']);

        $input = "php <path_cacti>/plugins/npc/perfdata.php --type=$type --id=$object_id 2> /dev/null";

        $sql = sprintf("INSERT INTO data_input (hash, name, input_string, type_id) VALUES ('%s', '%s', '%s', 1)", $this->generateHash(), $name, $input);

        db_execute($sql);
        $data_input_id = db_fetch_insert_id();

        $obj = new $class;
        $results = $obj->getPerfData($object_id);

        $perfParts = explode(";", $results[0]['perfdata']);

        foreach ($perfParts as $perf) {
            if (preg_match("/=/", $perf)) {
                $ds = explode("=", $perf);
                $sql = sprintf("INSERT INTO data_input_fields (hash, data_input_id, name, data_name, input_output, update_rra, sequence) VALUES ('%s', %d, '%s', '%s', 'out', 'on', 0)", $this->generateHash(), $data_input_id, $ds[0], $ds[0]);
                db_execute($sql);
            }
        }

        return(json_encode(array('success' => true)));
    }

    /**
     * generateHash
     * 
     * Generates a unique has for populating the hash column
     *
     * @return string   a 128-bit, hexadecimal hash
     */
    function generateHash() {
        return md5(session_id() . microtime() . rand(0,1000));
    }

    /**
     * getGraphList
     * 
     * Returns a list of graphs and properties for populating a 
     * client side graph select box.
     *
     * @return boolean
     */
    function getGraphList($npc_id, $cacti_id) {
        $sql = "
            SELECT
                graph_templates_graph.id,          
                graph_templates_graph.local_graph_id,          
                graph_templates_graph.height, 
                graph_templates_graph.width, 
                graph_templates_graph.title_cache as title, 
                graph_templates.name, 
                graph_local.host_id 
            FROM
                (graph_local,graph_templates_graph)
            LEFT JOIN 
                graph_templates ON graph_local.graph_template_id = graph_templates.id
            WHERE
                graph_local.id = graph_templates_graph.local_graph_id
                AND graph_templates_graph.title_cache like '%'          
            ORDER BY   
                graph_templates_graph.title_cache, graph_local.host_id
        ";

        $results = array_merge(array(array('title' => 'None', 'local_graph_id' => 0)), db_fetch_assoc($sql));

        return($this->jsonOutput($results));
    }

}
?>
