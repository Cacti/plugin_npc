<?php

class Controller {

    /**
     * The name of this controller.
     *
     * @var string
     * @access public
     */
    var $name = null;

    /**
     * An array containing the class names of models this controller uses.
     *
     * @var mixed A single name as a string or a list of names as an array.
     * @access protected
     */
    var $models = false;

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
