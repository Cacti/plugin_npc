<?php
/**
 * Comments controller class
 *
 * This is the access point to the npc_comments table.
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
 * Comments controller class
 *
 * Comments controller provides functionality, such as building the 
 * Doctrine querys and formatting output.
 * 
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcCommentsController extends Controller {

    /**
     * getComments
     * 
     * Returns a an array of comments
     *
     * @return array  The comments
     */
    function getComments() {

        $where = '';

        if ($this->id) {
            $where .= sprintf("c.object_id = %d", $this->id);
        }

        $q = new Doctrine_Pager(
            Doctrine_Query::create()
                ->select('i.instance_name,'
                        .'o.name1 AS host_name,'
                        .'o.name2 AS service_description,'
                        .'c.*')
                ->from('NpcComments c')
                ->leftJoin('c.Object o')
                ->leftJoin('c.Instance i')
                ->where("$where")
                ->orderby( 'c.entry_time DESC, c.entry_time_usec DESC' ),
            $this->currentPage,
            $this->limit
        );

        $results = $q->execute(array(), Doctrine::FETCH_ARRAY);

        // Set the total number of records 
        $this->numRecords = $q->getNumResults();

        return($this->jsonOutput($results));
    }

}
