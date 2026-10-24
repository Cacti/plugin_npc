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
 * @version             $Id$
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
        $cacti = new NpcCactiController();

        $return = $params['description'] . '|';

        // If the host exists and is already mapped return with notice
        if ($cacti->isMapped($params['host_object_id'])) {
            $this->logger('info', get_class($this), __FUNCTION__ , $params['description'] . " already exists and is mapped.");
            $return .= '0|0|The host already exists and is mapped';
            return($return);
        }

        // If the host already exists, map it and return.
        if ($cactiId = $this->checkHostExists($params['ip'], $params['cache_id'])) {
            $cacti->mapHost($params['host_object_id'], $cactiId);
            $this->logger('info', get_class($this), __FUNCTION__ , $params['description'] . " already existed and was mapped.");
            $return .= '0|1|The host already existed in Cacti with device ID: ' . $cactiId;
            return($return);
        }

        if ($params['description'] === '' || $params['ip'] === '' || !ctype_digit((string) $params['template_id'])) {
            $this->logger('warn', get_class($this), __FUNCTION__ , "Import rejected, invalid parameters for " . $params['description']);
            $return .= '0|0|Invalid import parameters';
            return($return);
        }

        $importCmd = 'php ' . cacti_escapeshellarg($path)
                   . ' --description=' . cacti_escapeshellarg($params['description'])
                   . ' --ip=' . cacti_escapeshellarg($params['ip'])
                   . ' --template=' . cacti_escapeshellarg($params['template_id'])
                   . ' 2> /dev/null';

        $this->logger('debug', get_class($this), __FUNCTION__ , "Import command: $importCmd");

        exec($importCmd, $status);

        if(is_array($status)) {
            foreach ($status as $output) {

                preg_match("/Success - new device-id: \((.*)\)/", $output, $matches);

                if (isset($matches[1])) {
                    // Import was successful. Now map the hosts
                    $cacti->mapHost($params['host_object_id'], $matches[1]);
                    $this->logger('info', get_class($this), __FUNCTION__ , "Successfully imported and mapped " . $params['description'] . ": $output");
                    $return .= '1|1|' . $output;
                } elseif (isset($matches[0])) {
                    $this->logger('error', get_class($this), __FUNCTION__ , "Import failed: " . print_r($status, true));
                    $return .= '1|0|A failure occurred during import.';
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

	$hgc = new NpcHostgroupsController();
        foreach ($data as $hostgroup) {
            $hosts = $hgc->getHostList(array('alias' => $hostgroup->alias));
            foreach ($hosts as $host) {
                if (!NpcCactiController::isMapped($host['host_object_id'])) {
                    $results[] = array(
                        'host_object_id' => $host['host_object_id'],
                        'display_name' => $host['display_name'],
                        'address' => $host['address'],
                        'create_graphs' => $hostgroup->create_graphs,
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

	$hgc = new NpcHostgroupsController();
        $results = $hgc->getHostgroups();

        $i = 0;
	$cc = new NpcCactiController();
        foreach($results as $hostgroup) {
            $output[$i]['alias'] = $results[$i]['alias'];
            $output[$i]['members'] = 0;
            foreach ($results[$i]['Hoststatus'] as $host) {
                if (!$cc->isMapped($host['host_object_id'])) {
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
        global $config;

        $cacheFile = $config['base_path'] . '/cache/npc_address_cache.json';
        $hosts     = null;

        if (is_file($cacheFile) && is_readable($cacheFile)) {
            $cache = json_decode((string) file_get_contents($cacheFile), true);

            if (is_array($cache) && isset($cache['key']) && $cache['key'] === (string) $cache_id && isset($cache['hosts']) && is_array($cache['hosts'])) {
                $hosts = $cache['hosts'];
            }
        }

        if ($hosts === null) {
            $hosts = array();

            $cacti   = new NpcCactiController();
            $results = $cacti->getHostnames();

            for ($i = 0; $i < count($results); $i++) {
                $hosts[gethostbyname($results[$i]['hostname'])] = $results[$i]['id'];
            }

            $this->writeAddressCache($cacheFile, $cache_id, $hosts);
        }

        if (isset($hosts[$ip])) {
            return($hosts[$ip]);
        }

        return(null);
    }

    /**
     * writeAddressCache
     *
     * Stores the address map as JSON in the Cacti cache directory. The file is
     * written under a temporary name and renamed so a concurrent import never
     * reads a partial file. A cache that cannot be written only costs a rebuild
     * on the next call.
     *
     * @return void
     */
    private function writeAddressCache($cacheFile, $cache_id, $hosts) {
        if (!is_dir(dirname($cacheFile)) || !is_writable(dirname($cacheFile))) {
            $this->logger('warn', get_class($this), __FUNCTION__, 'Unable to create the address cache in ' . dirname($cacheFile));
            return;
        }

        $tmp = tempnam(dirname($cacheFile), 'npc');

        $bytes = file_put_contents($tmp, json_encode(array('key' => (string) $cache_id, 'hosts' => $hosts)));

        if ($bytes === false || is_dir($cacheFile) || !rename($tmp, $cacheFile)) {
            $this->logger('warn', get_class($this), __FUNCTION__, 'Unable to write the address cache ' . $cacheFile);
            unlink($tmp);
        }
    }

}
