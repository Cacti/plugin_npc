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
 * @version             $Id: $
 */

/**
 * Statehistory controller class
 *
 * Statehistory controller provides functionality, such as building the 
 * Doctrine querys and formatting output.
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

        $where = '';

        if ($this->id) {
            $where .= sprintf("sh.object_id = %d ", $this->id);
        }

        $q = new Doctrine_Pager(
            Doctrine_Query::create()
                ->select('i.instance_name,'
                        .'o.name1 AS host_name,'
                        .'o.name2 AS service_description,'
                        .'sh.*')
                ->from('NpcStatehistory sh')
                ->leftJoin('sh.Object o')
                ->leftJoin('sh.Instance i')
                ->where("$where")
                ->orderby( 'sh.state_time DESC, sh.state_time_usec DESC' ),
            $this->currentPage,
            $this->limit
        );

        $results = $q->execute(array(), Doctrine::FETCH_ARRAY);

        // Set the total number of records 
        $this->numRecords = $q->getNumResults();

        return($this->jsonOutput($results));
    }

}
