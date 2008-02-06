<?php
/**
 * Base controller class
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

/**
 * Base controller class
 *
 * @package     npc
 * @subpackage  npc.controllers
 */
class Controller {

    /**
     * The starting row for fetching results
     *
     * @var integer
     * @access public
     */
    var $start = 0;

    /**
     * The number of rows to fetch
     *
     * @var integer
     * @access public
     */
    var $limit = 25;

    /**
     * The current page to fetch results for
     *
     * @var integer
     * @access public
     */
    var $currentPage = 1;

    /**
     * The total number of records from
     * the last query.
     *
     * @var integer
     * @access public
     */
    var $numRecords = 1;

    /**
     * The ID of the requested record
     *
     * @var integer
     * @access public
     */
    var $id = null;

    /**
     * Maps a hosts current_state
     *
     * @var array
     * @access public
     */
    var $hostState = array(
        '0'  => 'up',
        '1'  => 'down',
        '2'  => 'unreachable',
        '-1' => 'pending'
    );

    /**
     * Maps a services current_state
     *
     * @var array
     * @access public
     */
    var $serviceState = array(
        '0'  => 'ok',
        '1'  => 'warning',
        '2'  => 'critical',
        '3'  => 'unknown',
        '-1' => 'pending'
    );

    /**
     * Holds all params passed and named.
     *
     * @var mixed
     * @access public
     */
    var $passedArgs = array();


    /**
     * Constructor.
     *
     */
    function __construct() {

    }

    function jsonOutput($results) {

        if (count($results) && !is_array($results[0])) {
            $results = array($results);
        }

        // Setup the output array:
        $output = array('totalCount' => $this->numRecords, 'data' => $results);

        return(json_encode($output));
    }

    /**
     * flattenArray
     * 
     * Flattens the 1st level of nesting
     *
     * @return array  list of all services with status
     */
    function flattenArray($array=array()) {

        $newArray = array();

        for ($i = 0; $i < count($array); $i++) {
            foreach ($array[$i] as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $newArray[$i][$k] = $v;
                    }
                } else {
                    $newArray[$i][$key] = $val; 
                }
            }
        }

        return($newArray);
    }
}
