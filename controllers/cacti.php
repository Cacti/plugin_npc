<?php

require_once("include/auth.php");

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
