<?php
/**
 * Logentries controller class
 *
 * This is the access point to the npc_logentries table.
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
 * Logentries controller class
 *
 * Logentries controller provides basic functionality, such as building the 
 * Doctrine querys and formatting output.
 * 
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcLogentriesController extends Controller {

    /**
     * getLogs
     * 
     * Returns log entries
     *
     * @return string   json output
     */
    function getLogs() {

        $q = new Doctrine_Pager(
            Doctrine_Query::create()
                ->from( 'NpcLogentries l' )
                ->orderby( 'l.entry_time DESC, l.entry_time_usec DESC' ),
            $this->currentPage,
            $this->limit
        );

        $results = $q->execute(array(), Doctrine::FETCH_ARRAY);

        // Set the total number of records
        $this->numRecords = $q->getNumResults();

        return($this->jsonOutput($results));
    }
}
