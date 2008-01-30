<?php

class NPC_settings {

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

}
?>
