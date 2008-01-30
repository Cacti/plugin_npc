<?php

class Controller {

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
