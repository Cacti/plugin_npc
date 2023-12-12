<?php

//require the base Doctrine class
require_once(dirname(__FILE__) . '/lib/Doctrine.php');
require_once(dirname(__FILE__) . '/controllers/controller.php');

//register the autoloader
spl_autoload_register(array('Doctrine', 'autoload'));

$database_username = urlencode($database_username);
$database_password = urlencode($database_password);

// Setup the DSN
$dsn = "$database_type://$database_username:$database_password@$database_hostname:$database_port/$database_default";

// initialize a new Doctrine_Connection
$conn = Doctrine_Manager::connection($dsn);

// Setup conservative (on demand) model loading
Doctrine_Manager::getInstance()->setAttribute(Doctrine::ATTR_MODEL_LOADING, Doctrine::MODEL_LOADING_CONSERVATIVE); 

// Load our models
Doctrine::loadModels(dirname(__FILE__) . '/models'); 
