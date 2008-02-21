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
     * A host status cache
     *
     * @var array
     * @access public
     */
    var $hostStatusCache = array();

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
     * getHostSummary
     * 
     * Returns host status by servicegroup
     *
     * @return string   json output
     */
    function getHostSummary() {
        $status = $this->getServicegroupMemberHoststatus('workstation');

        print "DEBUG: " . $status;
        exit;
    }

    /**
     * getServices
     * 
     * Returns all services by servicegroup
     *
     * @return string   json output
     */
    function getServices() {

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

    function getServicegroupMemberHoststatus($hostname) {

        if(isset($this->hostStatusCache[$hostname])) {
            return($this->hostStatusCache[$hostname]);
        }

        $q = new Doctrine_Query();
        $q->select('hs.current_state')
          ->from('NpcHoststatus hs, NpcHosts h')
          ->where('hs.host_object_id = h.host_object_id AND h.display_name = ?', $hostname);

        $results = $q->execute(array(), Doctrine::FETCH_ARRAY);

        $this->hostStatusCache[$hostname] = $results[0]['current_state'];

        return($this->hostStatusCache[$hostname]);
    }

    function getServicegroups() {

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
                    .'o1.name1 AS servicegroup_name,'
                    .'ss.service_object_id,'
                    .'ss.current_state,' 
                    .'ss.output,' 
                    .'o2.name1 AS host_name,'
                    .'o2.name2 AS service_description,'
                    .'sg.*')
            ->from('NpcServicegroups sg')
            ->innerJoin('sg.ServicegroupMembers sgm')
            ->innerJoin('sg.Servicestatus ss ON sgm.service_object_id = ss.service_object_id')
            ->innerJoin('sg.Object o1')
            ->innerJoin('ss.Object o2')
            ->innerJoin('sg.Instance i')
            ->where("$where")
            ->orderBy('servicegroup_name ASC, host_name ASC, service_description ASC');

        $results = $q->execute(array(), Doctrine::FETCH_ARRAY);

        return($results);
    }
}



