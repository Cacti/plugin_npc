<?php
// ex: set tabstop=4 expandtab:
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
 * @version             $Id$
 */

if (isset($config)) {
    require_once($config["base_path"]."/plugins/npc/controllers/services.php");
} else {
    require_once("plugins/npc/controllers/services.php");
}


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
     * A service status cache
     *
     * @var array
     * @access private
     */
    private $statusCache = array();

    /**
     * getHostgroupHostStatus
     *
     * Returns host status counts by hostgroup.
     *
     * @return string   json output
     */
    function getHostgroupHostStatus() {

        // Initialize the output array
        $output = array();

        // Initialize the hosts array
        $hosts = array();

        $fields = array('hostgroup_object_id',
                        'alias',
                        'instance_id');

        $results = $this->setupResultsArray();

        for ($i = 0; $i < count($results); $i++) {
            $hg = $results[$i]['hostgroup_object_id'];
            if(!isset($output[$hg])) {
                $output[$hg] = array('down'        => 0,
                                     'unreachable' => 0,
                                     'up'          => 0,
                                     'pending'     => 0);
            }
            if (!isset($hosts[$hg][$results[$i]['host_name']])) {
                $output[$hg][$this->hostState[$results[$i]['current_state']]]++;
                $hosts[$hg][$results[$i]['host_name']] = 1;
            }
            foreach ($results[$i] as $key => $val) {
                if (in_array($key, $fields)) {
                    $output[$hg][$key] = $val;
                }
            }
        }

        // Set the total number of records
        $this->numRecords = count($output);

        // Implement paging by slicing the ouput array
        $output = array_slice($output, $this->start, $this->limit);

        $response['response']['value']['items'] = $output;
        $response['response']['value']['total_count'] = $this->numRecords;
        $response['response']['value']['version']     = 1;

        return(json_encode($response));
    }

    /**
     * getHostgroupServiceStatus
     *
     * Returns service status counts by hostgroup.
     *
     * @return string   json output
     */
    function getHostgroupServiceStatus() {

        // Initialize the output array
        $output = array();

        $fields = array('hostgroup_object_id',
                        'alias',
                        'hostgroup_name',
                        'instance_id');

        // Combine servicegroup/service/host etc. into a single record
        $results = $this->setupResultsArray();

        for ($i = 0; $i < count($results); $i++) {
            $hg = $results[$i]['hostgroup_object_id'];
            $ss = $this->getHostgroupMemberServiceStatus($results[$i]['host_object_id']);
            if(!isset($output[$hg])) {
                $output[$hg] = $ss;
            } else {
                foreach ($ss as $k => $v) {
                    $output[$hg][$k] = $output[$hg][$k] + $v;
                }
            }
            foreach ($results[$i] as $key => $val) {
                if (in_array($key, $fields)) {
                    $output[$hg][$key] = $val;
                }
            }
        }

        // Set the total number of records
        $this->numRecords = count($output);

        // Implement paging by slicing the ouput array
        $output = array_slice($output, $this->start, $this->limit);

        $response['response']['value']['items'] = $output;
        $response['response']['value']['total_count'] = $this->numRecords;
        $response['response']['value']['version']     = 1;

        return(json_encode($response));
    }


    /**
     * getOverview
     *
     * Returns all hosts by hostgroup. Used to populate
     * the Servicegroup Grid screen.
     *
     * @return string   json output
     */
    function getOverview() {

        $fields = array('hostgroup_object_id',
                        'alias',
                        'instance_id',
                        'host_name');

        // Initialize the output array
        $output = array();

        // Combine servicegroup/service/host etc. into a single record
        $results = $this->setupResultsArray();

        /*  Loop through the results array and build an ouput array
         *  that includes a single record per host within the hostgroup
         *  and the number of crit, warn , ok services within
         *  that hostgroup.
         */
        for ($i = 0; $i < count($results); $i++) {
            $hg = $results[$i]['hostgroup_object_id'];
            $host = $results[$i]['host_name'];
            $ss = $this->getHostgroupMemberServiceStatus($results[$i]['host_object_id']);
            if(!isset($temp[$hg][$host])) {
                $ss['host_state'] = $results[$i]['current_state'];
                $temp[$hg][$host] = $ss;
            }
            foreach ($results[$i] as $key => $val) {
                $temp[$hg][$host][$key] = $val;
            }
        }

        $x = 0;
        foreach ($temp as $i => $s) {
            foreach ($s as $h => $v) {
                foreach ($v as $key => $val) {
                    $output[$x][$key] = $val;
                }
            $x++;
            }
        }

        // Set the total number of records
        $this->numRecords = count($output);

        // Implement paging by slicing the ouput array
        $output = array_slice($output, $this->start, $this->limit);

        return($this->jsonOutput($output));
    }


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

        $results = $q->execute(array(), Doctrine::HYDRATE_ARRAY);

        return($results);
    }

    /**
     * getHostgroupMemberServiceStatus
     *
     * Returns a count of the status of all services for a given host.
     *
     * @return array
     */
    private function getHostgroupMemberServiceStatus($host_object_id) {

        if(isset($this->statusCache[$host_object_id])) {
            return($this->statusCache[$host_object_id]);
        }

        // initialize the status array
        $this->statusCache[$host_object_id] = array('critical' => 0,
                                                     'warning'  => 0,
                                                     'unknown'  => 0,
                                                     'ok'       => 0,
                                                     'pending'  => 0);


        $results = NpcServicesController::getServiceStatesByHost($host_object_id);

        for ($i = 0; $i < count($results); $i++) {
            $this->statusCache[$host_object_id][$this->serviceState[$results[$i]['current_state']]]++;
        }

        return($this->statusCache[$host_object_id]);
    }

    /**
     * listHostsCli
     *
     * Retrieves all hosts in the specified hostgroup for the cli
     *
     * @return array
     */
    function listHostsCli($hg) {

        $q = new Doctrine_Query();
        $q->select('h.host_id, h.host_object_id AS id, h.display_name AS name, h.address')
          ->from('NpcHosts h, NpcHostgroups hg, NpcHostgroupMembers hgm')
          ->where('hg.hostgroup_id = hgm.hostgroup_id AND hgm.host_object_id = h.host_object_id AND hg.alias = ?', $hg);

        $results = $q->execute(array(), Doctrine::HYDRATE_ARRAY);

        return($results);
    }


    /**
     * listHostgroupsCli
     *
     * Returns all hostgroups and associated object ID's
     *
     * @return array   Array of hostgroups/id's
     */
    function listHostgroupsCli() {

        $q = new Doctrine_Query();
        $q->select('alias as name, hostgroup_object_id as id')->from('NpcHostgroups')->orderBy('alias ASC');

        return($q->execute(array(), Doctrine::HYDRATE_ARRAY));
    }


    /**
     * getHostgroups
     *
     * Retrieves all hosts with current state by hostgroup
     *
     * @return array
     */
    function getHostgroups() {
        $where = '1 = 1';

        // Maps searchable fields passed in from the client
        $fieldMap = array('service_description' => 'o2.name2',
                          'host_name' => 'o2.name1',
                          'alias' => 'sg.alias',
                          'output' => 's.output');

        if ($this->id) {
            $where .= " AND hg.hostgroup_object_id = " . $this->id . " ";
        }

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
          ->distinct()
          ->from('NpcHostgroups hg')
          ->innerJoin('hg.HostgroupMembers hgm')
          ->innerJoin('hg.Hoststatus hs ON hgm.host_object_id = hs.host_object_id')
          ->innerJoin('hg.Object o1')
          ->innerJoin('hs.Object o2')
          ->innerJoin('hg.Instance i')
          ->where("$where")
          ->orderBy('hostgroup_name ASC, host_name ASC');

        $results = $q->execute(array(), Doctrine::HYDRATE_ARRAY);

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



