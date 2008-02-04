<?php
/**
 * Servicegroups controller class
 *
 * This is the access point to the npc_servicegroups table.
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
 * Servicegroups controller class
 *
 * Servicegroups controller provides functionality, such as building the 
 * Doctrine querys and formatting output.
 * 
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcServicegroupsController extends Controller {

    /**
     * getOverview
     * 
     * Returns an overview of each service group
     *
     * @return string   json output
     */
    function getOverview() {

        $q = new Doctrine_Pager(
            Doctrine_Query::create()
                ->select('i.instance_name,'
                        .'s.current_state,' 
                        .'o1.name1 AS servicegroup_name,'
                        .'s.service_object_id,'
                        .'o2.name1 AS host_name,'
                        .'o2.name2 AS service_description,'
                        .'sg.*')
                ->from('NpcServicegroups sg')
                ->innerJoin('sg.ServicegroupMembers sgm')
                ->innerJoin('sg.Servicestatus s ON sgm.service_object_id = s.service_object_id')
                ->innerJoin('sg.Object o1')
                ->innerJoin('s.Object o2')
                ->innerJoin('sg.Instance i'),
            $this->currentPage,
            $this->limit
        );

        $results = $q->execute(array(), Doctrine::FETCH_ARRAY);

        // Set the total number of records 
        $this->numRecords = $q->getNumResults();

print_r($results);
exit;

        //return($this->jsonOutput($results));
    }
}
