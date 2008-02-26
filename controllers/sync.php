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

require_once("include/auth.php");
require_once("plugins/npc/controllers/hostgroups.php");
require_once("plugins/npc/controllers/cacti.php");

/**
 * Hosts controller class
 *
 * Hosts controller provides functionality, such as building the 
 * Doctrine querys and formatting output.
 * 
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcSyncController extends Controller {

    /**
     * import
     * 
     * Handle importing Nagios hosts to cacti returning the import
     * results back to the client.
     *
     * @return string   The json encoded results
     */
    function import($params) {
   
        $config = $params['config'];
        $path = $config["base_path"] . "/cli/add_device.php";

        $results = array();

        foreach ($data as $hostgroup) {
            $hosts = NpcHostgroupsController::getHosts(array('alias' => $hostgroup->alias));
            foreach ($hosts as $host) {
                if (!NpcCactiController::isMapped($host['host_object_id'])) {
                    exec("php ".$path." --description=".$host['display_name']." --ip=".$host['address'] . 
                         " --template=".$hostgroup->template, $status);
                    exec("echo \"" . print_r($status, true) . "\" > /tmp/DEBUG");
                }
            }
        }

        return('woot');
    }

    /**
     * getHosts
     * 
     * Returns hosts with template id's that will be imported
     *
     * @return string   The json encoded results
     */
    function getHosts($params) {

        $data = json_decode($params['data']);

        $results = array();

        foreach ($data as $hostgroup) {
            $hosts = NpcHostgroupsController::getHosts(array('alias' => $hostgroup->alias));
            foreach ($hosts as $host) {
                if (!NpcCactiController::isMapped($host['host_object_id'])) {
                    $results[] = array('host' => $host['host_object_id'], 'template' => $hostgroup->template);
                }
            }
        }

        return(json_encode($results));
    }

    /**
     * listHostgroups
     * 
     * Returns a simple list of hostgroups with the number of hosts
     * that would be imported. Hosts that are currently mapped are 
     * not included in the count.
     *
     * @return string   json output
     */
    function listHostgroups() {

        $output = array();

        $results = NpcHostgroupsController::getHostgroups();

        $i = 0;
        foreach($results as $hostgroup) {
            $output[$i]['alias'] = $results[$i]['alias'];
            $output[$i]['members'] = 0;
            foreach ($results[$i]['Hoststatus'] as $host) {
                if (!NpcCactiController::isMapped($host['host_object_id'])) {
                    $output[$i]['members']++;
                }
            }
            $i++;
        }

        $this->numRecords = count($output);

        return($this->jsonOutput($output));
    }



}
