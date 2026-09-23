<?php
/**
 * Notifications controller class
 *
 * This is the access point to the npc_notifications table.
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
 * Notifications controller class
 *
 * Notifications controller provides functionality, such as building the
 * queries and formatting output.
 *
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcNotificationsController extends Controller {

    /**
     * getNotifications
     *
     * Returns notifications
     *
     * @return string   json output
     */
    function getNotifications() {

        $where = '1 = 1';
        $params = array();

        if ($this->id) {
            $where = 'n.object_id = ?';
            $params[] = $this->id;
        }

        /* Get total count */
        $this->numRecords = db_fetch_cell_prepared('SELECT COUNT(*)
            FROM npc_notifications n
            LEFT JOIN npc_objects o ON n.object_id = o.object_id
            LEFT JOIN npc_instances i ON n.instance_id = i.instance_id
            WHERE ' . $where,
            $params);

        $offset = ($this->currentPage - 1) * $this->limit;

        $results = db_fetch_assoc_prepared('SELECT i.instance_name,
                o.name1 AS host_name,
                o.name2 AS service_description,
                n.*
            FROM npc_notifications n
            LEFT JOIN npc_objects o ON n.object_id = o.object_id
            LEFT JOIN npc_instances i ON n.instance_id = i.instance_id
            WHERE ' . $where . '
            ORDER BY n.start_time DESC, n.start_time_usec DESC
            LIMIT ?, ?',
            array_merge($params, array($offset, $this->limit)));

        return($this->jsonOutput($results));
    }
}
