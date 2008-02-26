<?php

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

        // Set the total number of records 
        $this->numRecords = count($results);

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

}
?>
