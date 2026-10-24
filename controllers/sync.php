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
     * The file holding the address map. It is a property so a test can point
     * it at a temporary file.
     *
     * @var string
     */
    var $addressCacheFile = '/tmp/npc_address_cache.php';

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
     * normalizeAddress(). The file layout is the one earlier versions wrote;
     * the added $cacheVersion makes older files rebuild, because they only
     * hold the first IPv4 address of each device.
     *
     * @return int   The Cacti host ID
     */
    function checkHostExists($ip, $cache_id) {

        $cacheFile = $this->addressCacheFile;

        $buildCache = 0;
        $cacheVersion = 1;

        if (file_exists($cacheFile)) {
            include($cacheFile);
            if ($cacheKey != $cache_id || $cacheVersion < 2) {
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
            $string .= "\$cacheVersion = 2;\n";
            $string .= "\$hosts = array(\n";

            $cacti = new NpcCactiController();
            $results = $cacti->getHostnames();

            for ($i = 0; $i < count($results); $i++) {
                foreach ($this->resolveAddresses($results[$i]['hostname']) as $address) {
                    $string .= "'" . $address . "' => '" . $results[$i]['id'] . "',\n";
                    $hosts[$address] = $results[$i]['id'];
                }
            }

            $string .= ");";

            fwrite($fh, $string);

            fclose($fh);
        }

        foreach ($this->resolveAddresses($ip) as $address) {
            if (isset($hosts[$address])) {
                return($hosts[$address]);
            }
        }

        return(null);
    }

    /**
     * normalizeAddress
     *
     * Returns the canonical text form of an IPv4 or IPv6 address, or null when
     * the text is not an address. The same address written in different ways
     * (2001:DB8::1, 2001:db8:0:0:0:0:0:1, [2001:db8::1], fe80::1%eth0) gives
     * the same text, and an IPv4-mapped IPv6 address gives the IPv4 text.
     *
     * @param  string $address
     * @return string|null
     */
    function normalizeAddress($address) {
        $address = trim((string) $address);

        if (strlen($address) > 1 && $address[0] == '[' && substr($address, -1) == ']') {
            $address = substr($address, 1, -1);
        }

        /* A scope id names the interface, not the host. */
        $address = preg_replace('/%.*$/', '', $address);

        if (filter_var($address, FILTER_VALIDATE_IP) === false) {
            return(null);
        }

        $packed = inet_pton($address);

        if (strlen($packed) == 16 && strncmp($packed, "\0\0\0\0\0\0\0\0\0\0\xff\xff", 12) == 0) {
            $packed = substr($packed, 12);
        }

        return(inet_ntop($packed));
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

}
