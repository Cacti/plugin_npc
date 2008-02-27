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
     * The import results are a pipe delimited string parsed into 
     * an array on the client side.
     *
     * Columns: "description|imported|mapped|message|cacti_host_ID"
     *
     * @return string   The pipe delimited results
     */
    function import($params) {
   
        $config = $params['config'];
        $path = $config["base_path"] . "/cli/add_device.php";

        $return = $params['description'] . '|';

        // If the host already exists in Cacti map it and return.
        if ($cactiId = $this->checkHostExists($params['ip'], $params['cache_id'])) {
            NpcCactiController::mapHost($params['host_object_id'], $cactiId);
            $return .= '0|1|The host already existed in Cacti and was mapped|' . $cactiId;
            return($return);
        }

        $importCmd = 'php ' . $path 
                   . ' --description=' . $params['description'] 
                   . ' --ip=' . $params['ip']
                   . ' --template=' . $params['template_id'];

        exec($importCmd, $status);

        if(is_array($status)) {
            preg_match("/Success - new device-id: \((.*)\)/", $status[2], $matches);

            if (isset($matches[0])) {
                // The import was successful now map the hosts
                if (isset($matches[1])) {
                    NpcCactiController::mapHost($params['host_object_id'], $matches[1]);
                    $return .= '1|1|' . $status[1] . '|' . $matches[1];
                } else {
                    $return .= '1|0|' . $matches[0] . ' - ' . $status[1] . '|0';
                }
            } else {
                $return .= $status[1];
            }
        } else {
            $return .= '0|0|Unknown failure|0';
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

        $data = json_decode($params['data']);

        $results = array();

        foreach ($data as $hostgroup) {
            $hosts = NpcHostgroupsController::getHosts(array('alias' => $hostgroup->alias));
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

        $this->numRecords = count($output);

        return($this->jsonOutput($output));
    }

    /**
     * checkHostExists
     * 
     * This method checks to see if a host exists in 
     * Cacti by comparing IP addresses. If a match is 
     * the found, the Cacti host ID is returned.
     *
     * The first time this method is called a cache of 
     * id to ip address mappings will be built.
     *
     * @return int   The Cacti host ID
     */
    function checkHostExists($ip, $cache_id) {
        // $myIP = gethostbyname(trim(`hostname`));

        $buildCache = 0;

        $cacheFile = 'plugins/npc/address_cache.php';

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

            $string = "<?php\n\n";
            $string .= "\$cacheKey = '" . $cache_id . "';\n";
            $string .= "\$hosts = array(\n";

            $results = NpcCactiController::getHostnames();

            for ($i = 0; $i < count($results); $i++) {
                $address = gethostbyname($results[$i]['hostname']);
                $string .= "'" . $address . "' => '" . $results[$i]['id'] . "',\n";
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
