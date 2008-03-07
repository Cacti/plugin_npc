<?php
/**
 * Hosts controller class
 *
 * This is the access point to the npc_hosts table.
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
 * Hosts controller class
 *
 * Hosts controller provides functionality, such as building the 
 * Doctrine querys and formatting output.
 * 
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcHostsController extends Controller {

    /**
     * summary
     * 
     * Returns a state count for all hosts
     *
     * @return string   json output
     */
    function summary() {

        $status = array(
            'down'        => 0, 
            'unreachable' => 0,
            'up'          => 0,
            'pending'     => 0
        );

        $q = new Doctrine_Query();
        $hosts = $q->from('NpcHosts h')->where('h.config_type = 1')->execute();

        foreach($hosts as $host) {
            $status[$this->hostState[$host->Status->current_state]]++;
        }

        return($this->jsonOutput($status));
    }

    function getPerfData($id) {

        $q = new Doctrine_Query();
        $q->select('perfdata')->from('NpcHostchecks')->where('host_object_id = ?', $id);
        
        return($q->execute(array(), Doctrine::FETCH_ARRAY));
    }

    /**
     * hosts
     * 
     * Retrieves all hosts along with status information
     *
     * @return array 
     */
    function hosts() {

        // Maps searchable fields passed in from the client
        $fieldMap = array('host_name' => 'o.name1',
                          'output' => 'hs.output');


        // Build the where clause
        $where = ' h.config_type = 1 ';

        if ($this->id) {
            $where .= sprintf(" AND hs.host_object_id = %d", $this->id);
        }

        $where .= " AND hs.current_state in (" . $this->stringToState[$this->state] . ") ";

        if ($this->searchString) {
            $where = $this->searchClause($where, $fieldMap);
        }

        $q = new Doctrine_Pager(
            Doctrine_Query::create()
                ->select('i.instance_name,'
                        .'o.name1 AS host_name,'
                        .'s.service_object_id,'
                        .'s.display_name,'
                        .'hs.*')
                ->from('NpcHoststatus hs')
                ->leftJoin('hs.Object o')
                ->leftJoin('hs.Host h')
                ->leftJoin('hs.Instance i')
                ->leftJoin('hs.Services s')
                ->where("$where")
                ->orderby( 'i.instance_name ASC, host_name ASC' ),
            $this->currentPage,
            $this->limit
        );

        $hosts = $q->execute(array(), Doctrine::FETCH_ARRAY);

        // Set the total number of records
        $this->numRecords = $q->getNumResults();

        return($hosts);
    }

    /**
     * listHostsCli
     * 
     * Returns all hosts and associated object ID's
     *
     * @return array   Array of hosts/id's
     */
    function listHostsCli() {

        $q = new Doctrine_Query();
        $q->select('display_name as name, host_object_id as id')->from('NpcHosts')->orderBy('display_name ASC');

        return($q->execute(array(), Doctrine::FETCH_ARRAY));
    }
}
