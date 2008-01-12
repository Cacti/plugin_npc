<?php

chdir('../../');
require_once($config["include_path"] . "/auth.php");


class NPC_eventlog {

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

        $count = db_fetch_cell("SELECT count(*) FROM npc_logentries");

        $sql = "SELECT logentry_id, unix_timestamp(entry_time) as entry_time, logentry_data from npc_logentries ";

        if (isset($params['id'])) {
            $sql .= " WHERE npc_logentries.logentry_id = " . $params['id'];
        }

        $sql .= " ORDER BY entry_time DESC, entry_time_usec DESC LIMIT $start,$limit";

        return(array($count, db_fetch_assoc($sql)));
    }
}
?>
