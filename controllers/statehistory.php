<?php
/**
 * Statehistory controller class
 *
 * This is the access point to the npc_statehistory table.
 *
 * @filesource
 * @author              Billy Gunn <billy@gunn.org>
 * @copyright           Copyright (c) 2007
 * @link                http://trac2.assembla.com/npc
 * @package             npc
 * @subpackage          npc.controllers
 * @since               NPC 2.0
 * @version             $Id$
 */

/**
 * Statehistory controller class
 *
 * Statehistory controller provides functionality, such as building the
 * queries and formatting output.
 *
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcStatehistoryController extends Controller {

    /**
     * getStateHistory
     *
     * Returns the state history
     *
     * @return string   json output
     */
    function getStateHistory() {

        $where = '1 = 1';
        $params = array();

        if ($this->id) {
            $where = 'sh.object_id = ?';
            $params[] = $this->id;
        }

        /* Get total count */
        $this->numRecords = db_fetch_cell_prepared('SELECT COUNT(*)
            FROM npc_statehistory sh
            LEFT JOIN npc_objects o ON sh.object_id = o.object_id
            LEFT JOIN npc_instances i ON sh.instance_id = i.instance_id
            WHERE ' . $where,
            $params);

        $offset = ($this->currentPage - 1) * $this->limit;

        $results = db_fetch_assoc_prepared('SELECT i.instance_name,
                o.name1 AS host_name,
                o.name2 AS service_description,
                sh.*
            FROM npc_statehistory sh
            LEFT JOIN npc_objects o ON sh.object_id = o.object_id
            LEFT JOIN npc_instances i ON sh.instance_id = i.instance_id
            WHERE ' . $where . '
            ORDER BY sh.state_time DESC, sh.state_time_usec DESC
            LIMIT ?, ?',
            array_merge($params, array($offset, $this->limit)));

        return($this->jsonOutput($results));
    }

}
