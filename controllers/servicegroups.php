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
 * @version             $Id$
 */

require_once("include/auth.php");
require_once("plugins/npc/controllers/comments.php");

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
     * @access private
     */
    private $hostStatusCache = array();


    /**
     * getHostStatusPortlet
     * 
     * Returns host status counts by servicegroup.
     *
     * @return string   json output
     */
    function getHostStatusPortlet() {

        $startTime = $this->getTime();

        // Initialize the output array
        $output = array();

        // Initialize the hosts array
        $hosts = array();

        $fields = array('servicegroup_object_id',
                        'alias',
                        'instance_id');
                 
        // Combine servicegroup/service/host etc. into a single record
        $results = $this->setupResultsArray();

        // Loop through the results array and build an ouput array
        // that includes a single record per servicegroup
        // and the number of crit, warn , ok services within 
        // that servicegroup.
        for ($i = 0; $i < count($results); $i++) {
            $sg = $results[$i]['servicegroup_object_id'];
            if(!isset($output[$sg])) {
                $output[$sg] = array('down'        => 0,
                                     'unreachable' => 0,
                                     'up'          => 0,
                                     'pending'     => 0);
            }
            if (!isset($hosts[$sg][$results[$i]['host_name']])) {
                $hostState = $this->getServicegroupMemberHoststatus($results[$i]['host_name']);
                $output[$sg][$this->hostState[$hostState]]++;
                $hosts[$sg][$results[$i]['host_name']] = 1;
            }
            foreach ($results[$i] as $key => $val) {
                if (in_array($key, $fields)) {
                    $output[$sg][$key] = $val;
                }
            }
        }

        // Set the total number of records 
        $this->numRecords = count($output);

        // Implement paging by slicing the ouput array
        $output = array_slice($output, $this->start, $this->limit);

        $this->logger('debug', get_class($this), 'getHostStatusPortlet', "Method execution time: ".sprintf("%01.2f", ($this->getTime() - $startTime)). " seconds");

        return($this->jsonOutput($output));
    }

    /**
     * getServicegroupServiceStatus
     * 
     * Returns service status counts by servicegroup.
     *
     * @return string   json output
     */
    function getServicegroupServiceStatus() {

        $startTime = $this->getTime();

        // Initialize the output array
        $output = array();

        $fields = array('servicegroup_object_id',
                        'alias',
                        'instance_id');
                 
        // Combine servicegroup/service/host etc. into a single record
        $results = $this->setupResultsArray();

        for ($i = 0; $i < count($results); $i++) {
            $sg = $results[$i]['servicegroup_object_id'];
            if(!isset($output[$sg])) {
                $output[$sg] = array('critical' => 0,
                                   'warning'  => 0,
                                   'unknown'  => 0,
                                   'ok'       => 0,
                                   'pending'  => 0);
            }
            foreach ($results[$i] as $key => $val) {
                if ($key == 'current_state') { 
                    $output[$sg][$this->serviceState[$val]]++;
                } else if(in_array($key, $fields)) {
                    $output[$sg][$key] = $val;
                }
            }
        }

        // Set the total number of records 
        $this->numRecords = count($output);

        // Implement paging by slicing the ouput array
        $output = array_slice($output, $this->start, $this->limit);

        $this->logger('debug', get_class($this), 'getServicegroupServiceStatus', "Method execution time: ".sprintf("%01.2f", ($this->getTime() - $startTime)). " seconds");

        return($this->jsonOutput($output));
    }

    /**
     * getOverview
     * 
     * Returns all hosts by servicegroup. Used to populate 
     * the Servicegroup Grid screen.
     *
     * @return string   json output
     */
    function getOverview() {

        $startTime = $this->getTime();

        $fields = array('servicegroup_object_id',
                        'alias',
                        'instance_id',
                        'host_name');
                 
        // Initialize the output array
        $output = array();

        // Combine servicegroup/service/host etc. into a single record
        $results = $this->setupResultsArray();

        // Loop through the results array and build an ouput array
        // that includes a single record per host with the servicegroup
        // and the number of crit, warn , ok services within 
        // that servicegroup.
        for ($i = 0; $i < count($results); $i++) {
            $sg = $results[$i]['servicegroup_object_id'];
            $host = $results[$i]['host_name'];
            $hostState = $this->getServicegroupMemberHoststatus($host);
            if(!isset($temp[$sg][$host])) {
                $temp[$sg][$host] = array('host_state' => $hostState,
                                          'critical' => 0,
                                          'warning'  => 0,
                                          'unknown'  => 0,
                                          'ok'       => 0,
                                          'pending'  => 0);
            }
            foreach ($results[$i] as $key => $val) {
                if ($key == 'current_state') { 
                    $temp[$sg][$host][$this->serviceState[$val]]++;
                } else if(in_array($key, $fields)) {
                    $temp[$sg][$host][$key] = $val;
                }
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

        $this->logger('debug', get_class($this), 'getOverview', "Method execution time: ".sprintf("%01.2f", ($this->getTime() - $startTime)). " seconds");

        return($this->jsonOutput($output));
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

        print_r($status);
        exit;
    }

    /**
     * getServices
     * 
     * Returns all services by servicegroup. Used to populate 
     * the Servicegroup Grid screen.
     *
     * @return string   json output
     */
    function getServices() {

        $startTime = $this->getTime();

        $results = $this->setupResultsArray();

        $comments = new NpcCommentsController;

        $services = $this->flattenArray($results);

        for ($i = 0; $i < count($services); $i++) {
            if ($services[$i]['problem_has_been_acknowledged']) {
                $services[$i]['acknowledgement'] = $comments->getAck($services[$i]['service_object_id']);
            }
            // Add the last comment to the array
            $services[$i]['comment'] = $comments->getLastComment($services[$i]['service_object_id']);
        }

        // Set the total number of records 
        $this->numRecords = count($services);

        // Implement paging by slicing the ouput array
        $services = array_slice($services, $this->start, $this->limit);

        $this->logger('debug', get_class($this), 'getServices', "Method execution time: ".sprintf("%01.2f", ($this->getTime() - $startTime)). " seconds");

        return($this->jsonOutput($services));
    }

    function getServicegroupMemberHoststatus($hostname) {

        $startTime = $this->getTime();

        if(isset($this->hostStatusCache[$hostname])) {
            return($this->hostStatusCache[$hostname]);
        }

        $q = new Doctrine_Query();
        $q->select('hs.current_state')
          ->from('NpcHoststatus hs, NpcHosts h')
          ->where('hs.host_object_id = h.host_object_id AND h.display_name = ?', $hostname);

        $results = $q->execute(array(), Doctrine::HYDRATE_ARRAY);

        $this->hostStatusCache[$hostname] = $results[0]['current_state'];

        $this->logger('debug', get_class($this), 'getServicegroupMemberHoststatus', "Method execution time: ".sprintf("%01.2f", ($this->getTime() - $startTime)). " seconds");

        return($this->hostStatusCache[$hostname]);
    }

    function getServicegroups() {

        $startTime = $this->getTime();

        $where = '1 = 1';

        if ($this->id) {
            $where = "sg.servicegroup_object_id = " . $this->id;
        }

        // Maps searchable fields passed in from the client
        $fieldMap = array('service_description' => 'o2.name2',
                          'host_name' => 'o2.name1',
                          'alias' => 'sg.alias',
                          'output' => 'ss.output');

        if ($this->searchString) {
            $where .= $this->searchClause(null, $fieldMap);
        }

        $q = new Doctrine_Query();
        $q->select('i.instance_name,'
                    .'o1.name1 AS servicegroup_name,'
                    .'o2.name1 AS host_name,'
                    .'o2.name2 AS service_description,'
                    .'ss.*,' 
                    .'sg.*')
            ->from('NpcServicegroups sg')
            ->innerJoin('sg.ServicegroupMembers sgm')
            ->innerJoin('sg.Servicestatus ss ON sgm.service_object_id = ss.service_object_id')
            ->innerJoin('sg.Object o1')
            ->innerJoin('ss.Object o2')
            ->innerJoin('sg.Instance i')
            ->where("$where")
            ->orderBy('servicegroup_name ASC, host_name ASC, service_description ASC');

        $results = $q->execute(array(), Doctrine::HYDRATE_ARRAY);

        $this->logger('debug', get_class($this), 'getServicegroups', "Method execution time: ".sprintf("%01.2f", ($this->getTime() - $startTime)). " seconds");

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
        $results = $this->getServicegroups();

        // Flatten the 1st level of nested arrays
        $results = $this->flattenArray($results);

        // Combine servicegroup/service/host etc. into a single record.
        $results = $this->flattenNestedArray($results);

        return($results);
    }

}



