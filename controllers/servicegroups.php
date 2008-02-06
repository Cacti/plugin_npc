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
     * Returns json formatted results for the 
     * servicegroup overview screen.
     *
     * @return string   json output
     */
    function getOverview() {

        // Get the servicegroups
        $results = $this->getServicegroups();

        print_r($results);
        exit;
    }

    /**
     * getGrid
     * 
     * Returns json formatted results for the 
     * servicegroup grid screen.
     *
     * @return string   json output
     */
    function getGrid() {

        $output = array();

        // Get the servicegroups
        $results = $this->getServicegroups();

        // Flatten the 1st level of nested arrays
        $results = $this->flattenArray($results);

        // Re-format the results so that each service/servicegroup
        // combonation is a single record 
        $x = 0;
        for ($i = 0; $i < count($results); $i++) {
            foreach ($results[$i] as $key => $val) {
                if (is_array($val)) {
                    $t[0] = $val;
                    $v = $this->flattenArray($t); 
                    unset($results[$i][$key]);
                    foreach ($results[$i] as $key => $val) {
                        if (!is_array($val)) {
                            $a[$key] = $val;
                        }
                    }
                    $output[$x] = array_merge($a, $v[0]);    
                    $x++;
                }
            }
        }

        // Set the total number of records 
        $this->numRecords = count($output);

        // Implement paging by slicing the ouput array
        $output = array_slice($output, $this->start, $this->limit);

        return($this->jsonOutput($output));
    }

    function getServicegroups() {

        $q = new Doctrine_Query();
        $q->select('i.instance_name,'
                    .'o1.name1 AS servicegroup_name,'
                    .'s.service_object_id,'
                    .'s.current_state,' 
                    .'s.output,' 
                    .'o2.name1 AS host_name,'
                    .'o2.name2 AS service_description,'
                    .'sg.*')
            ->from('NpcServicegroups sg')
            ->innerJoin('sg.ServicegroupMembers sgm')
            ->innerJoin('sg.Servicestatus s ON sgm.service_object_id = s.service_object_id')
            ->innerJoin('sg.Object o1')
            ->innerJoin('s.Object o2')
            ->innerJoin('sg.Instance i')
            ->orderBy('servicegroup_name ASC, host_name ASC, service_description ASC');

        $results = $q->execute(array(), Doctrine::FETCH_ARRAY);

        return($results);
    }
}



