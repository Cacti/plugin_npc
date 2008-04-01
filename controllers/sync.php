<?php
/**
 * Sync controller class
 *
 * This class handles importing Nagios hosts to Cacti
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
 * Sync controller class
 *
 * This class handles importing Nagios hosts to Cacti
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
     * The import results are a pipe delimited string parsed into 
     * an array on the client side.
     *
     * Columns: "description|imported|mapped|message|cacti_host_ID"
     *
     * @params array    Array of parameters passed from the client
     * @return string   The pipe delimited results
     */
    function import($params) {

        $this->logger('info', get_class($this), __FUNCTION__ , "Starting import of " . $params['description']);
   
        $config = $params['config'];
        $path = $config["base_path"] . "/cli/add_device.php";

        $return = $params['description'] . '|';

        // If the host exists and is already mapped return with notice
        if (NpcCactiController::isMapped($params['host_object_id'])) {
            $this->logger('info', get_class($this), __FUNCTION__ , $params['description'] . " already exists and is mapped.");
            $return .= '0|0|The host already exists and is mapped';
            return($return);
        }

        // If the host already exists, map it and return.
        if ($cactiId = $this->checkHostExists($params['ip'], $params['cache_id'])) {
            NpcCactiController::mapHost($params['host_object_id'], $cactiId);
            $this->logger('info', get_class($this), __FUNCTION__ , $params['description'] . " already existed and was mapped.");
            $return .= '0|1|The host already existed in Cacti with device ID: ' . $cactiId;
            return($return);
        }

        $importCmd = 'php ' . $path 
                   . ' --description=' . $params['description'] 
                   . ' --ip=' . $params['ip']
                   . ' --template=' . $params['template_id']
                   . ' 2> /dev/null';
        
        $this->logger('debug', get_class($this), __FUNCTION__ , "Import command: $importCmd");

        exec($importCmd, $status);

        if(is_array($status)) {
            foreach ($status as $output) {

                preg_match("/Success - new device-id: \((.*)\)/", $output, $matches);

                if (isset($matches[1])) {
                    // Import was successful. Now map the hosts
                    NpcCactiController::mapHost($params['host_object_id'], $matches[1]);
                    $this->logger('info', get_class($this), __FUNCTION__ , "Successfully imported and mapped " . $params['description'] . ": $output");
                    $return .= '1|1|' . $output;
                } elseif (isset($matches[0])) {
                    $this->logger('error', get_class($this), __FUNCTION__ , "Import failed: " . print_r($status, true));
                    $return .= '1|0|A failure occured during import.';
                }
            }
        } else {
            $this->logger('error', get_class($this), __FUNCTION__ , "Import command failed: $importCmd");
            $return .= '0|0|Unknown failure';
        }

        return($return);
    }

    /**
     * getHosts
     * 
     * Returns hosts with template id's that will be imported
     *
     * @return string   The json encoded results
     */
    function getHosts($params) {

        $data = json_decode(stripslashes($params['data']));

        if (!is_object($data)) {
            return($this->logger('error', get_class($this), __FUNCTION__ , "json_decode(".$params['data'].") returned: $data"));
        }

        $results = array();

        foreach ($data as $hostgroup) {
            $hosts = NpcHostgroupsController::getHostList(array('alias' => $hostgroup->alias));
            foreach ($hosts as $host) {
                if (!NpcCactiController::isMapped($host['host_object_id'])) {
                    $results[] = array(
                        'host_object_id' => $host['host_object_id'], 
                        'display_name' => $host['display_name'],
                        'address' => $host['address'],
                        'template' => $hostgroup->template
                    );
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

        return($this->jsonOutput($output));
    }

    /**
     * checkHostExists
     * 
     * This method checks to see if a host exists in 
     * Cacti by comparing IP addresses. If a match is 
     * found the Cacti host ID is returned.
     *
     * The first time this method is called a cache of 
     * id to ip address mappings will be built.
     *
     * @return int   The Cacti host ID
     */
    function checkHostExists($ip, $cache_id) {

        $cacheFile = '/tmp/npc_address_cache.php';

        $buildCache = 0;

        if (file_exists($cacheFile)) {
            include($cacheFile);
            if ($cacheKey != $cache_id) {
                $buildCache = 1;
            }
        } else {
            $buildCache = 1;
        }

        if ($buildCache) {

            $fh = fopen($cacheFile, 'w') or die("can't open file");

            $hosts = array();

            $string = "<?php\n\n";
            $string .= "\$cacheKey = '" . $cache_id . "';\n";
            $string .= "\$hosts = array(\n";

            $results = NpcCactiController::getHostnames();

            for ($i = 0; $i < count($results); $i++) {
                $address = gethostbyname($results[$i]['hostname']);
                $string .= "'" . $address . "' => '" . $results[$i]['id'] . "',\n";
                $hosts[$address] = $results[$i]['id'];
            }

            $string .= ");";

            fwrite($fh, $string);

            fclose($fh);
        }

        if (isset($hosts[$ip])) {
            return($hosts[$ip]);
        }

        return(null);
    }

}
