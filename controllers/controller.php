<?php

class Controller {

    /**
     * The total number of records available
     *
     * @var string
     * @access public
     */
    var $totalCount = null;

    /**
     * The output format.
     *
     * @var string
     * @access public
     */
    var $format = 'json';

    /**
     * The results of a get method
     *
     * @var mixed A single html string or an array of results
     * @access protected
     */
    var $results = false;


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

}
