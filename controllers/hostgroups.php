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
     * listHostgroups
     * 
     * Returns a simple list of hostgroups with the number of hosts
     *
     * @return string   json output
     */
    function listHostgroups() {

        $results = $this->getHostgroups();

	print_r($results);
	exit;
        //return($results);
    }

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
}



