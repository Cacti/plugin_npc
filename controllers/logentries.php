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
 * @version             $Id$
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

        // Maps searchable fields passed in from the client
        $fieldMap = array('logentry_data' => 'l.logentry_data',
                          'instance_name' => 'i.instance_name');


        $where = '1 = 1';

        if ($this->searchString) {
            $where = $this->searchClause($where, $fieldMap);
        }

        $q = new Doctrine_Pager(
            Doctrine_Query::create()
                ->select('i.instance_name,'
                        .'l.*')
                ->from('NpcLogentries l')
                ->leftJoin('l.Instance i')
                ->where("$where")
                ->orderby( 'l.entry_time DESC, l.entry_time_usec DESC' ),
            $this->currentPage,
            $this->limit
        );

        $results = $this->flattenArray($q->execute(array(), Doctrine::HYDRATE_ARRAY));

        // Set the total number of records
        $this->numRecords = $q->getNumResults();

        $response['response']['value']['items'] = $results;
        $response['response']['value']['total_count'] = $this->numRecords;
        $response['response']['value']['version']     = 1;

        return(json_encode($response));
    }
}
