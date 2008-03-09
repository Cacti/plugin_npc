<?php
/**
 * Hostgroups controller class
 *
 * This is the access point to the npc_hostgroups table.
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
 * Hostgroups controller class
 *
 * Hostgroups controller provides functionality, such as building the 
 * Doctrine querys and formatting output.
 * 
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcHostgroupsController extends Controller {

    /**
     * getHosts
     * 
     * Returns all hosts by hostgroup. Used to populate 
     * the Hostgroup Grid screen.
     *
     * @return string   json output
     */
    function getHosts() {

        $output = $this->setupResultsArray();

        // Set the total number of records 
        $this->numRecords = count($output);

        // Implement paging by slicing the ouput array
        $output = array_slice($output, $this->start, $this->limit);

        return($this->jsonOutput($output));
    }

    /**
     * getHostList
     *
     * Retrieves all hosts in the specified hostgroup
     *
     * @return array
     */
    function getHostList($params) {

        $column = key($params);
        $value = $params[$column];

        $q = new Doctrine_Query();
        $q->select('h.host_object_id, h.display_name, h.address')
          ->from('NpcHosts h, NpcHostgroups hg, NpcHostgroupMembers hgm')
          ->where('hg.hostgroup_id = hgm.hostgroup_id AND hgm.host_object_id = h.host_object_id AND hg.'.$column.' = ?', $value);

        $results = $q->execute(array(), Doctrine::FETCH_ARRAY);

        return($results);
    }


    /**
     * getHostgroups
     *
     * Retrieves all hosts with current state by hostgroup
     *
     * @return array
     */
    function getHostgroups() {

        $where = '';

        // Maps searchable fields passed in from the client
        $fieldMap = array('service_description' => 'o2.name2',
                          'host_name' => 'o2.name1',
                          'alias' => 'sg.alias',
                          'output' => 's.output');

        if ($this->searchString) {
            $where = $this->searchClause(null, $fieldMap);
        }

        $q = new Doctrine_Query();
        $q->select('i.instance_name,'
                  .'o1.name1 AS hostgroup_name,'
                  .'hs.host_object_id,'
                  .'hs.current_state,'
                  .'hs.output,'
                  .'o2.name1 AS host_name,'
                  .'hg.*')
          ->from('NpcHostgroups hg')
          ->innerJoin('hg.HostgroupMembers hgm')
          ->innerJoin('hg.Hoststatus hs ON hgm.host_object_id = hs.host_object_id')
          ->innerJoin('hg.Object o1')
          ->innerJoin('hs.Object o2')
          ->innerJoin('hg.Instance i')
          ->where("$where")
          ->orderBy('hostgroup_name ASC, host_name ASC');

        $results = $q->execute(array(), Doctrine::FETCH_ARRAY);

        return($results);
    }

    /**
     * setupResultsArray
     * 
     * A utility method to handle some common formatting tasks.
     *
     * @return array
     */
    function setupResultsArray() {

        // Get the servicegroups
        $results = $this->getHostgroups();

        // Flatten the 1st level of nested arrays
        $results = $this->flattenArray($results);

        // Combine servicegroup/service/host etc. into a single record.
        $results = $this->flattenNestedArray($results);

        return($results);
    }

}



