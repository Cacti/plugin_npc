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

        if (!api_user_realm_auth('host.php')) {
            return($params['description'] . '|0|0|You do not have permission to add devices');
        }

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
        $cactiId = $this->checkHostExists($params['ip'], $params['cache_id']);

        if ($cactiId === false) {
            return $return . '0|0|The address matches more than one Cacti device';
        }

        if ($cactiId !== null) {
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
            return($this->logger('error', get_class($this), __FUNCTION__ , 'The host selection is not a JSON object'));
        }

        $results = array();

	$hgc = new NpcHostgroupsController();
        $cacti = new NpcCactiController();
        foreach ($data as $hostgroup) {
            $hosts = $hgc->getHostList(array('alias' => $hostgroup->alias));
            foreach ($hosts as $host) {
                if (!$cacti->isMapped($host['host_object_id'])) {
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
     * The DNS and system lookups, as callables so a test can replace them.
     *
     * @var callable
     */
    var $dnsLookup = 'dns_get_record';
    var $nameLookup = 'gethostbyname';

    /**
     * checkHostExists
     *
     * This method checks to see if a host exists in
     * Cacti by comparing IP addresses. If a match is
     * found the Cacti host ID is returned.
     *
     * The first time this method is called a cache of
     * id to ip address mappings will be built. Every address of a host is
     * stored, IPv4 and IPv6, in the canonical text form of
     * normalizeAddress(). The map is kept as JSON in the Cacti cache directory
     * and is rebuilt when the key differs or the file cannot be read.
     *
     * @return int|string|false|null Device ID, false for ambiguity, null if absent.
     */
    function checkHostExists($ip, $cache_id) {
        global $config;

        $cacheFile = $config['base_path'] . '/cache/npc_address_cache.json';
        $hosts     = null;

        if (is_file($cacheFile) && is_readable($cacheFile)) {
            $cache = json_decode((string) file_get_contents($cacheFile), true);

            if (is_array($cache) && ($cache['version'] ?? 0) === 2 && isset($cache['key']) && $cache['key'] === (string) $cache_id && isset($cache['hosts']) && is_array($cache['hosts'])) {
                $hosts = $cache['hosts'];
            }
        }

        if ($hosts === null) {
            $hosts = array();

            $cacti   = new NpcCactiController();
            $results = $cacti->getHostnames();

            for ($i = 0; $i < count($results); $i++) {
                foreach ($this->resolveAddresses($results[$i]['hostname']) as $address) {
                    if (array_key_exists($address, $hosts) && $hosts[$address] !== $results[$i]['id']) {
                        $hosts[$address] = false;
                    } else {
                        $hosts[$address] = $results[$i]['id'];
                    }
                }
            }

            $this->writeAddressCache($cacheFile, $cache_id, $hosts);
        }

        $match = null;
        foreach ($this->resolveAddresses($ip) as $address) {
            if (array_key_exists($address, $hosts)) {
                if ($hosts[$address] === false || ($match !== null && $match !== $hosts[$address])) {
                    return false;
                }
                $match = $hosts[$address];
            }
        }

        return $match;
    }

    /**
     * normalizeAddress
     *
     * Returns the canonical text form of an IPv4 or IPv6 address, or null when
     * the text is not an address. The same address written in different ways
     * (2001:DB8::1, 2001:db8:0:0:0:0:0:1, [2001:db8::1]) gives the same
     * text. IPv6 zones are retained; IPv4-mapped IPv6 gives the IPv4 text.
     *
     * @param  string $address
     * @return string|null
     */
    function normalizeAddress($address) {
        $address = trim((string) $address);

        if (strlen($address) > 1 && $address[0] == '[' && substr($address, -1) == ']') {
            $address = substr($address, 1, -1);
        }

        // Link-local addresses on different interfaces name different endpoints.
        $parts = explode('%', $address, 2);
        $address = $parts[0];
        $zone = $parts[1] ?? '';

        if (filter_var($address, FILTER_VALIDATE_IP) === false) {
            return(null);
        }

        $packed = inet_pton($address);

        if (strlen($packed) == 16 && strncmp($packed, "\0\0\0\0\0\0\0\0\0\0\xff\xff", 12) == 0) {
            $packed = substr($packed, 12);
        }

        $address = inet_ntop($packed);
        if (strlen($packed) === 16 && $zone !== '') {
            $address .= '%' . $zone;
        }

        return $address;
    }

    /**
     * resolveAddresses
     *
     * Returns every address, in canonical form, that a host name or address
     * stands for. An address is returned as it is without a lookup. A name is
     * looked up for A and AAAA records, and for names the resolver does not
     * return records for, the system lookup that earlier versions used.
     *
     * @param  string $name  host name or address
     * @return array         canonical addresses, empty when nothing resolves
     */
    function resolveAddresses($name) {
        $name = trim((string) $name);

        $literal = $this->normalizeAddress($name);

        if ($literal !== null) {
            return(array($literal));
        }

        if ($name === '') {
            return(array());
        }

        $found = array();

        foreach ($this->dnsRecords($name) as $record) {
            $address = $this->normalizeAddress(isset($record['ipv6']) ? $record['ipv6'] : (isset($record['ip']) ? $record['ip'] : ''));

            if ($address !== null && !in_array($address, $found, true)) {
                $found[] = $address;
            }
        }

        if (!count($found)) {
            $address = $this->normalizeAddress($this->legacyLookup($name));

            if ($address !== null) {
                $found[] = $address;
            }
        }

        return($found);
    }

    /**
     * dnsRecords
     *
     * The A and AAAA records of a name. A failed lookup gives an empty list.
     * dns_get_record() reports a failed query as a warning, which the empty
     * list already says.
     *
     * @param  string $name
     * @return array
     */
    function dnsRecords($name) {
        $records = @call_user_func($this->dnsLookup, $name, DNS_A | DNS_AAAA);

        return(is_array($records) ? $records : array());
    }

    /**
     * legacyLookup
     *
     * The system resolver lookup earlier versions used. It returns the name
     * unchanged when the name does not resolve.
     *
     * @param  string $name
     * @return string
     */
    function legacyLookup($name) {
        return(call_user_func($this->nameLookup, $name));
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

        $bytes = file_put_contents($tmp, json_encode(array('version' => 2, 'key' => (string) $cache_id, 'hosts' => $hosts)));

        if ($bytes === false || is_dir($cacheFile) || !rename($tmp, $cacheFile)) {
            $this->logger('warn', get_class($this), __FUNCTION__, 'Unable to write the address cache ' . $cacheFile);
            unlink($tmp);
        }
    }

}
