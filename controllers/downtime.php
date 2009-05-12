<?php
/**
 * Downtime controller class
 *
 * This is the access point to the npc_downtimehistory table.
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

/**
 * Downtime controller class
 *
 * Downtime controller provides functionality, such as building the 
 * Doctrine querys and formatting output.
 * 
 * @package     npc
 * @subpackage  npc.controllers
 */
class NpcDowntimeController extends Controller {


    /**
     * getDowntime
     *
     * An accessor method to return downtime records
     *
     * @return string   json output
     */
    function getDowntime() {
        return($this->jsonOutput($this->downtimeHistory()));
    }

    /**
     * getHostDowntime
     *
     * An accessor method to return all host downtime
     *
     * @return string   json output
     */
    function getHostDowntime() {
        $results = $this->flattenArray($this->downtimeHistory(null, 'o.objecttype_id = 1'));
        return($this->jsonOutput($results));
    }

    /**
     * getServiceDowntime
     *
     * An accessor method to return all service downtime
     *
     * @return string   json output
     */
    function getServiceDowntime() {
        $results = $this->flattenArray($this->downtimeHistory(null, 'o.objecttype_id = 2'));
        return($this->jsonOutput($results));
    }

    /**
     * inDowntime()
     *
     * Returns true if the requested host/service is currently in
     * a schedule downtime.
     *
     * @return boolean 
     */
    function inDowntime($id) {
        $results = $this->scheduledDowntime($id);
	$now = date('U');

	$start = isset($results[0]['scheduled_start_time']) ? strtotime($results[0]['scheduled_start_time']) : null;
	$end = isset($results[0]['scheduled_end_time']) ? strtotime($results[0]['scheduled_end_time']) : null;

	if ($now > $start && $now < $end) {
        	return(1);
	}

	return(0);
    }

    /**
     * getTriggeredByCombo
     *
     * Creates a json encoded name/value list of downtime ID's
     * and descriptions. These results are used to build a combobox
     * for selecting downtime triggers on the schedule downtime form.
     *
     * @return string   json output
     */
    function getTriggeredByCombo() {
        $results = $this->flattenArray($this->scheduledDowntime());

        for ($i = 0; $i < count($results); $i++) {
            $entry = $results[$i];

            $name = $entry['host_name'] . ": ";

            if ($entry['objecttype_id'] == 2) {
                $name .= $entry['service_description'] . " - ";
            }

            $format = read_config_option('npc_date_format') . ' ' . read_config_option('npc_time_format');
            $start = date($format, strtotime($entry['scheduled_start_time']));

            $name .= "Starting @ " . $start;

            $output[$i] = array(
                'name' => $name, 
                'value' => $entry['internal_downtime_id']
            );
        }

        return($this->jsonOutput($output));
    }

    /**
     * scheduledDowntime
     * 
     * Returns scheduled downtime
     *
     * @return array
     */
    function scheduledDowntime($id=null, $where='1=1') {

        if ($this->id || $id) {
            $where .= ' AND ';
            $where .= sprintf("d.object_id = %d", is_null($id) ? $this->id : $id);
        }

        $q = new Doctrine_Query();
        $q->select('i.instance_name,'
                  .'o.objecttype_id,'
                  .'o.name1 AS host_name,'
                  .'o.name2 AS service_description,'
                  .'d.*')
          ->from('NpcScheduleddowntime d')
          ->leftJoin('d.Object o')
          ->leftJoin('d.Instance i')
          ->where("$where")
          ->orderby( 'd.scheduled_start_time DESC, d.scheduleddowntime_id DESC' );

        $results = $q->execute(array(), Doctrine::HYDRATE_ARRAY);

        return($results);
    }

    /**
     * downtimeHistory
     * 
     * Returns downtime history
     *
     * @return array
     */
    function downtimeHistory($id=null, $where='') {

        if ($this->id || $id) {
            if ($where != '') {
                $where .= ' AND ';
            }
            $where .= sprintf("d.object_id = %d", is_null($id) ? $this->id : $id);
        }

        $q = new Doctrine_Pager(
            Doctrine_Query::create()
                ->select('i.instance_name,'
                        .'o.name1 AS host_name,'
                        .'o.name2 AS service_description,'
                        .'d.*')
                ->from('NpcDowntimehistory d')
                ->leftJoin('d.Object o')
                ->leftJoin('d.Instance i')
                ->where("$where")
                ->orderby( 'd.scheduled_start_time DESC, d.actual_start_time DESC, d.actual_start_time_usec DESC' ),
            $this->currentPage,
            $this->limit
        );

        $results = $q->execute(array(), Doctrine::HYDRATE_ARRAY);

        // Set the total number of records 
        $this->numRecords = $q->getNumResults();

        return($results);
    }
}
