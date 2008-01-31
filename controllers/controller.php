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
     * @var string
     * @access public
     */
    var $start = 0;

    /**
     * The number of rows to fetch
     *
     * @var string
     * @access public
     */
    var $limit = 25;

    /**
     * The current page to fetch results for
     *
     * @var string
     * @access public
     */
    var $currentPage = 1;

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

    function jsonOutput($results, $totalCount=0) {

        if (!$totalCount) {
            $totalCount = count($results);
        }   

        if (count($results) && !is_array($results[0])) {
            $results = array($results);
        }

        // Setup the output array:
        $output = array('totalCount' => $totalCount, 'data' => $results);

        print json_encode($output);
    }

}
