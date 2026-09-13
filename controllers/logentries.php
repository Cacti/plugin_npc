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
 * queries and formatting output.
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

        /* Maps searchable fields passed in from the client */
        $fieldMap = array('logentry_data' => 'l.logentry_data',
                          'instance_name' => 'i.instance_name');

        $where = '1 = 1';
        $params = array();

        if ($this->searchString) {
            $where = $this->searchClause($where, $fieldMap, $params);
        }

        /* Get total count */
        $this->numRecords = db_fetch_cell_prepared('SELECT COUNT(*)
            FROM npc_logentries l
            LEFT JOIN npc_instances i ON l.instance_id = i.instance_id
            WHERE ' . $where,
            $params);

        $offset = ($this->currentPage - 1) * $this->limit;

        $results = db_fetch_assoc_prepared('SELECT i.instance_name, l.*
            FROM npc_logentries l
            LEFT JOIN npc_instances i ON l.instance_id = i.instance_id
            WHERE ' . $where . '
            ORDER BY l.entry_time DESC, l.entry_time_usec DESC
            LIMIT ?, ?',
            array_merge($params, array($offset, $this->limit)));

        $results = $this->flattenArray($results);

        $response['response']['value']['items'] = $results;
        $response['response']['value']['total_count'] = $this->numRecords;
        $response['response']['value']['version']     = 1;

        return(json_encode($response));
    }
}
