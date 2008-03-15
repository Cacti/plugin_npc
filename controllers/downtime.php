<?php
/**
 * Downtime controller class
 *
 * This is the access point to the npc_downtimehistory table.
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
 * Downtime controller class
 *
 * Downtime controller provides functionality, such as building the 
 * Doctrine querys and formatting output.
 * 
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcDowntimeController extends Controller {


    /**
     * getDowntime
     *
     * An accessor method to return downtime records
     *
     * @return string   json output
     */
    function getDowntime() {
        return($this->jsonOutput($this->downtime()));
    }

    /**
     * getHostDowntime
     *
     * An accessor method to return all host comments
     *
     * @return string   json output
     */
    function getHostDowntime() {
        $results = $this->flattenArray($this->downtime(null, 'o.objecttype_id = 1'));
        return($this->jsonOutput($results));
    }

    /**
     * getServiceDowntime
     *
     * An accessor method to return all service comments
     *
     * @return string   json output
     */
    function getServiceDowntime() {
        $results = $this->flattenArray($this->downtime(null, 'o.objecttype_id = 2'));
        return($this->jsonOutput($results));
    }

    /**
     * downtime
     * 
     * Returns downtime history
     *
     * @return array
     */
    function downtime($id=null, $where='') {

        if ($this->id || $id) {
            if ($where != '') {
                $where .= ' AND ';
            }
            $where .= sprintf("d.object_id = %d", is_null($id) ? $this->id : $id);
        }

        $q = new Doctrine_Pager(
            Doctrine_Query::create()
                ->select('i.instance_name,'
                        .'o.name1 AS host_name,'
                        .'o.name2 AS service_description,'
                        .'d.*')
                ->from('NpcDowntimehistory d')
                ->leftJoin('d.Object o')
                ->leftJoin('d.Instance i')
                ->where("$where")
                ->orderby( 'd.scheduled_start_time DESC, d.actual_start_time DESC, d.actual_start_time_usec DESC' ),
            $this->currentPage,
            $this->limit
        );

        $results = $q->execute(array(), Doctrine::FETCH_ARRAY);

        // Set the total number of records 
        $this->numRecords = $q->getNumResults();

        return($results);
    }
}
