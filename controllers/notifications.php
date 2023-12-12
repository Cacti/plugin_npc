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
 * Doctrine queries and formatting output.
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

        if ($this->id) {
            $where = sprintf("n.object_id = %d", $this->id);
        }

        $q = new Doctrine_Pager(
            Doctrine_Query::create()
                ->select('i.instance_name,'
                        .'o.name1 AS host_name,'
                        .'o.name2 AS service_description,'
                        .'n.*')
                ->from('NpcNotifications n')
                ->leftJoin('n.Object o')
                ->leftJoin('n.Instance i')
                ->where("$where")
                ->orderby( 'n.start_time DESC, n.start_time_usec DESC' ),
            $this->currentPage,
            $this->limit
        );

        $results = $q->execute(array(), Doctrine::HYDRATE_ARRAY);

        // Set the total number of records
        $this->numRecords = $q->getNumResults();

        return($this->jsonOutput($results));
    }
}
