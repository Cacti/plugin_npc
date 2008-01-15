<?php

chdir('../../');
require_once($config["include_path"] . "/auth.php");


class NPC_eventlog {

    var $start = 0;
    var $limit = 20;
    var $id = null;

    /**
     * getLogEntries
     * 
     * Returns log entries
     *
     * @param  array  Method parameters
     *     start - The start/offset point
     *     limit - The number of rows to return
     * @return array  The host status summary
     */
    function getLogEntries($params) {

        $count = db_fetch_cell("SELECT count(*) FROM npc_logentries");

        $sql = "SELECT logentry_id, entry_time, logentry_data from npc_logentries ";

        if ($this->id) {
            $sql .= " WHERE npc_logentries.logentry_id = " . $this->id;
        }

        $sql .= " ORDER BY entry_time DESC, entry_time_usec DESC LIMIT " . $this->start . "," . $this->limit;

        return(array($count, db_fetch_assoc($sql)));
    }
}
?>
