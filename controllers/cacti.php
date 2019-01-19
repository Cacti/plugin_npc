<?php

require_once('include/auth.php');
require_once($config['base_path'] . '/plugins/npc/controllers/hosts.php');
require_once($config['base_path'] . '/plugins/npc/controllers/services.php');

class NpcCactiController extends Controller {
	/**
	 * getSetting
	 *
	 * Returns the value of the requested setting
	 *
	 * @return string  The setting value
	 */
	function getSetting($setting) {
		return(array('setting' => read_config_option($setting)));
	}

	function getHostTemplates() {
		$results = db_fetch_assoc('SELECT id, name
			FROM host_template');

		return($this->jsonOutput($results));
    }

	/**
	 * isMapped
	 *
	 * Checks to see if the passed nagios host is currently mapped
	 * to a cacti device.
	 *
	 * @return boolean
	 */
	function isMapped($id) {
		$results = db_fetch_assoc_prepared('SELECT id
			FROM host
			WHERE npc_host_object_id = ?',
			array($id));

		return(cacti_count($results));
	}

    /**
     * mapHost
     *
     * Maps a nagios host to a cacti host
     *
     * @params int  The NPC object ID
     * @params int  The Cacti host ID
     */
    function mapHost($npc_id, $cacti_id) {
        return(db_execute_prepared('UPDATE host
			SET npc_host_object_id = ?
			WHERE id = ?', array($npc_id, $cacti_id))
		);
	}

	/**
	 * getHostnames
	 *
	 * Returns a list of cacti host ip addresses
	 *
     * @return array   array of ip addresses
	 */
	function getHostnames() {
		return(db_fetch_assoc('SELECT id, hostname
			FROM host')
		);
	}

	/**
	 * addDataInputMethod
	 *
	 * Adds a data input method for Nagios perf data
	 *
	 * @return string   json encoded results
	 */
	function addDataInputMethod($params) {
		$name = 'NPC - Perfdata - ' . $params['host'];

        if (isset($params['service'])) {
            $type = 'service';
            $class = 'NpcServicesController';
            $name .= ': ' . $params['service'];
        } else {
            $type = 'host';
            $class = 'NpcHostsController';
        }

        $object_id = cacti_escapeshellarg($params['object_id']);

        $input = "php <path_cacti>/plugins/npc/perfdata.php --type=$type --id=$object_id 2> /dev/null";

		$save = array();
		$save['hash'] = $this->generateHash();
		$save['name'] = $name;
		$save['input_string'] = $input;
		$save['type_id'] = 1;

		$data_input_id = sql_save($save, 'data_input');

        $obj = new $class;
        $results = $obj->getPerfData($object_id);

        $perfParts = explode(';', $results[0]['perfdata']);

        foreach ($perfParts as $perf) {
            if (preg_match('/=/', $perf)) {
                $ds = explode('=', $perf);

                db_execute_prepared('INSERT INTO data_input_fields
					(hash, data_input_id, name, data_name, input_output, update_rra, sequence)
					VALUES (?, ?, ?, ?, "out", "on", 0)',
					array($this->generateHash(), $data_input_id, $ds[0], $ds[0]));
			}
		}

		return(json_encode(array('success' => true)));
	}

	/**
	 * generateHash
	 *
	 * Generates a unique has for populating the hash column
	 *
	 * @return string   a 128-bit, hexadecimal hash
	 */
	function generateHash() {
		return md5(session_id() . microtime() . rand(0,1000));
	}

	/**
	 * getGraphList
	 *
	 * Returns a list of graphs and properties for populating a
	 * client side graph select box.
	 *
	 * @return boolean
	 */
	function getGraphList() {
		$sql = 'SELECT gtg.id, gtg.local_graph_id, gtg.height, gtg.width,
			gtg.title_cache as title, gt.name, gl.host_id
			FROM graph_local AS gl
			INNER JOIN graph_templates_graph AS gtg
			ON gl.id=gtg.local_graph_id
			LEFT JOIN graph_templates AS gt
			ON gl.graph_template_id = gt.id
			ORDER BY gtg.title_cache, gl.host_id';

		$results = array_merge(array(array('title' => __('None', 'npc'), 'local_graph_id' => 0)), db_fetch_assoc($sql));

		return($this->jsonOutput($results));
	}
}

