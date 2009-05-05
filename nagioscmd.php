<?php
/**
 * Nagios command class
 *
 * An object oriented interface for submitting Nagios
 * commands via the Nagios command file.
 *
 * @filesource
 * @author              Billy Gunn <billy@gunn.org>
 * @copyright           Copyright (c) 2007
 * @link                http://trac2.assembla.com/npc
 * @package             npc
 * @since               NPC 2.0
 * @version             $Id$
 */

require_once("include/auth.php");

/**
 * Nagios command class
 *
 * An object oriented interface for submitting Nagios
 * commands via the Nagios command file.
 *
 * Attempts to check commands for accuracy and completeness
 * prior to submitting. For a full list of commands and parameters see:
 * http://www.nagios.org/developerinfo/externalcommands/commandlist.php
 * 
 * @package     npc
 */
class NagiosCmd {

    /**
     * The status message from the last action
     *
     * @var string
     * @access public
     */ 
    var $message = null;

    /**
     * The current command string
     *
     * The preferred way to set this is using setCommand.
     * setCommand has a simple interface and validates
     * all the parameters. On success $command is set 
     * with a properly formatted string.
     *
     * You can set the command string directly by 
     * accessing this property.
     *
     * @var string
     * @access public
     */ 
    var $command = null;

    /**
     * The full path to the Nagios command file
     *
     * @var string
     * @access public
     */ 
    private $commandFile = null;

    /**
     * A list of the commands and thier required attributes
     *
     * @var array
     * @access private
     */ 
    private $commands = array(
        'ACKNOWLEDGE_HOST_PROBLEM' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
            'sticky' => array(
                'required' => true,
                'type' => 'boolean'),
            'notify' => array(
                'required' => true,
                'type' => 'boolean'),
            'persistent' => array(
                'required' => true,
                'type' => 'boolean'),
            'author' => array(
                'required' => true,
                'type' => 'string'),
            'comment' => array(
                'required' => true,
                'type' => 'string')
        ),
        'ACKNOWLEDGE_SVC_PROBLEM' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
            'service_description' => array(
                'required' => true,
                'type' => 'string'),
            'sticky' => array(
                'required' => true,
                'type' => 'boolean'),
            'notify' => array(
                'required' => true,
                'type' => 'boolean'),
            'persistent' => array(
                'required' => true,
                'type' => 'boolean'),
            'author' => array(
                'required' => true,
                'type' => 'string'),
            'comment' => array(
                'required' => true,
                'type' => 'string')
        ),
        'ADD_HOST_COMMENT' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
            'persistent' => array(
                'required' => true,
                'type' => 'boolean'),
            'author' => array(
                'required' => true,
                'type' => 'string'),
            'comment' => array(
                'required' => true,
                'type' => 'string')
        ),
        'ADD_SVC_COMMENT' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
            'service_description' => array(
                'required' => true,
                'type' => 'string'),
            'persistent' => array(
                'required' => true,
                'type' => 'boolean'),
            'author' => array(
                'required' => true,
                'type' => 'string'),
            'comment' => array(
                'required' => true,
                'type' => 'string')
        ),

        'CHANGE_CONTACT_HOST_NOTIFICATION_TIMEPERIOD' => array(
            'contact_name' => array(
                'required' => true,
                'type' => 'string'),
            'notification_timeperiod' => array(
                'required' => true,
                'type' => 'string')
        ),
       
        'CHANGE_CONTACT_MODATTR' => array(
        	'contact_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'value' => array(
        		'required' => true,
        		'type' => 'integer')
        ),
       
        'CHANGE_CONTACT_MODHATTR' => array(
        	'contact_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'value' => array(
        		'required' => true,
        		'type' => 'integer')
        ),
       
        'CHANGE_CONTACT_MODSATTR' => array(
        	'contact_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'value' => array(
        		'required' => true,
        		'type' => 'integer')
        ),
       
        'CHANGE_CONTACT_SVC_NOTIFICATION_TIMEPERIOD' => array(
        	'contact_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'notification_timeperiod' => array(
                'required' => true,
                'type' => 'string')
        ),
       
        'CHANGE_CUSTOM_CONTACT_VAR' => array(
        	'contact_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'varname' => array(
        		'required' => true,
        		'type' => 'string'),
        	'varvalue' => array(
        		'required' => true,
        		'type' => 'string')
        ),
       
        'CHANGE_CUSTOM_HOST_VAR' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'varname' => array(
        		'required' => true,
        		'type' => 'string'),
        	'varvalue' => array(
        		'required' => true,
        		'type' => 'string')
        ),
       
        'CHANGE_CUSTOM_SVC_VAR' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string'),
        	'varname' => array(
        		'required' => true,
        		'type' => 'string'),
        	'varvalue' => array(
        		'required' => true,
        		'type' => 'string')
        ),
       
        'CHANGE_GLOBAL_HOST_EVENT_HANDLER' => array(
        	'event_handler_command' => array(
        		'required' => true,
        		'type' => 'string')
        ),
       
        'CHANGE_GLOBAL_SVC_EVENT_HANDLER' => array(
        	'event_handler_command' => array(
        		'required' => true,
        		'type' => 'string')
        ),
       
        'CHANGE_HOST_CHECK_COMMAND' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'check_command' => array(
        		'required' => true,
        		'type' => 'string')
        ),

        'CHANGE_HOST_CHECK_TIMEPERIOD' => array(),
        
        'CHANGE_HOST_EVENT_HANDLER' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'event_handler_command' => array(
        		'required' => true,
        		'type' => 'string')
        ),
        
        'CHANGE_HOST_MODATTR' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'value' => array(
        		'required' => true,
        		'type' => 'integer')
        ),
        
        'CHANGE_MAX_HOST_CHECK_ATTEMPTS' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'check_attempts' => array(
        		'required' => true,
        		'type' => 'integer')
        ),
        
        'CHANGE_MAX_SVC_CHECK_ATTEMPTS' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string'),
        	'check_attempts' => array(
        		'required' => true,
        		'type' => 'integer')
        ),
        
        'CHANGE_NORMAL_HOST_CHECK_INTERVAL' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'check_interval' => array(
        		'required' => true,
        		'type' => 'integer')
        ),
        
        'CHANGE_NORMAL_SVC_CHECK_INTERVAL' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string'),
        	'check_interval' => array(
        		'required' => true,
        		'type' => 'integer')
        ),
        /* FIXME I think documentation is incorrect for this command
         * http://www.nagios.org/developerinfo/externalcommands/commandinfo.php?command_id=136
         * parameters listed as <host_name>;<service_description>;<check_interval>
         * example shows: CHANGE_RETRY_HOST_CHECK_INTERVAL;host1;5\n" $now > $commandfile
         * which should be command;<host_name>;<check_interval> (no service_description)
		*/
        'CHANGE_RETRY_HOST_CHECK_INTERVAL' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'check_interval' => array(
        		'required' => true,
        		'type' => 'integer')
        ),
        
        'CHANGE_RETRY_SVC_CHECK_INTERVAL' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string'),
        	'check_interval' => array(
        		'required' => true,
        		'type' => 'integer')
        ),
        
        'CHANGE_SVC_CHECK_COMMAND' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string'),
        	'check_command' => array(
        		'required' => true,
        		'type' => 'string')
        ),
        
        'CHANGE_SVC_CHECK_TIMEPERIOD' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string'),
        	'check_timeperiod' => array(
        		'required' => true,
        		'type' => 'string')
        ),
        
        'CHANGE_SVC_EVENT_HANDLER' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string'),
        	'event_handler_command' => array(
        		'required' => true,
        		'type' => 'string')
        ),
        
        'CHANGE_SVC_MODATTR' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string'),
        	'value' => array(
        		'required' => true,
        		'type' => 'integer')
        ),
        
        'CHANGE_SVC_NOTIFICATION_TIMEPERIOD' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string'),
        	'notification_timeperiod' => array(
        		'required' => true,
        		'type' => 'string')
        ),
        
        'DELAY_HOST_NOTIFICATION' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'notification_time' => array(
        		'required' => true,
        		'type' => 'integer')
        ),
        
        'DELAY_SVC_NOTIFICATION' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string'),
        	'notification_time' => array(
        		'required' => true,
        		'type' => 'integer')
        ),
        
        'DEL_ALL_HOST_COMMENTS' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DEL_ALL_SVC_COMMENTS' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
            'service_description' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DEL_HOST_COMMENT' => array(   
            'comment_id' => array(
                'required' => true,
                'type' => 'integer')
        ),
        
        'DEL_HOST_DOWNTIME' => array(   
            'downtime_id' => array(
                'required' => true,
                'type' => 'integer')
        ),
        
        'DEL_SVC_COMMENT' => array( 
            'comment_id' => array(
                'required' => true,
                'type' => 'integer')
        ),
        
        'DEL_SVC_DOWNTIME' => array(   
            'downtime_id' => array(
                'required' => true,
                'type' => 'integer')
        ),
        
        'DISABLE_ALL_NOTIFICATIONS_BEYOND_HOST' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_CONTACTGROUP_HOST_NOTIFICATIONS' => array(   
            'contactgroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_CONTACTGROUP_SVC_NOTIFICATIONS' => array(   
            'contactgroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_CONTACT_HOST_NOTIFICATIONS' => array(   
            'contact_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_CONTACT_SVC_NOTIFICATIONS' => array(   
            'contact_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_EVENT_HANDLERS' => array(),
        'DISABLE_FAILURE_PREDICTION' => array(),
        'DISABLE_FLAP_DETECTION' => array(),
        
        'DISABLE_HOSTGROUP_HOST_CHECKS' => array(   
            'hostgroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_HOSTGROUP_HOST_NOTIFICATIONS' => array(   
            'hostgroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_HOSTGROUP_PASSIVE_HOST_CHECKS' => array(   
            'hostgroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_HOSTGROUP_PASSIVE_SVC_CHECKS' => array(   
            'hostgroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_HOSTGROUP_SVC_CHECKS' => array(   
            'hostgroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_HOSTGROUP_SVC_NOTIFICATIONS' => array(   
            'hostgroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_HOST_AND_CHILD_NOTIFICATIONS' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_HOST_CHECK' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_HOST_EVENT_HANDLER' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_HOST_FLAP_DETECTION' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_HOST_FRESHNESS_CHECKS' => array(),
        
        'DISABLE_HOST_NOTIFICATIONS' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_HOST_SVC_CHECKS' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_HOST_SVC_NOTIFICATIONS' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_NOTIFICATIONS' => array(),
        
        'DISABLE_PASSIVE_HOST_CHECKS' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_PASSIVE_SVC_CHECKS' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string')
        ),

        'DISABLE_PERFORMANCE_DATA' => array(),
        
        'DISABLE_SERVICEGROUP_HOST_CHECKS' => array(   
            'servicegroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_SERVICEGROUP_HOST_NOTIFICATIONS' => array(   
            'servicegroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_SERVICEGROUP_PASSIVE_HOST_CHECKS' => array(   
            'servicegroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_SERVICEGROUP_PASSIVE_SVC_CHECKS' => array(   
            'servicegroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_SERVICEGROUP_SVC_CHECKS' => array(   
            'servicegroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_SERVICEGROUP_SVC_NOTIFICATIONS' => array(   
            'servicegroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'DISABLE_SERVICE_FLAP_DETECTION' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string')
        ),

        'DISABLE_SERVICE_FRESHNESS_CHECKS' => array(),
        
        'DISABLE_SVC_CHECK' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string')
        ),
        
        'DISABLE_SVC_EVENT_HANDLER' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string')
        ),
        
        'DISABLE_SVC_FLAP_DETECTION' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string')
        ),
        
        'DISABLE_SVC_NOTIFICATIONS' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string')
        ),
        
        'ENABLE_ALL_NOTIFICATIONS_BEYOND_HOST' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string')
        ),
        
        'ENABLE_CONTACTGROUP_HOST_NOTIFICATIONS' => array(
        	'contactgroup_name' => array(
        		'required' => true,
        		'type' => 'string')
        ),
        
        'ENABLE_CONTACTGROUP_SVC_NOTIFICATIONS' => array(
        	'contactgroup_name' => array(
        		'required' => true,
        		'type' => 'string')
        ),
        
        'ENABLE_CONTACT_HOST_NOTIFICATIONS' => array(
        	'contact_name' => array(
        		'required' => true,
        		'type' => 'string')
        ),
        
        'ENABLE_CONTACT_SVC_NOTIFICATIONS' => array(
        	'contact_name' => array(
        		'required' => true,
        		'type' => 'string')
        ),

        'ENABLE_EVENT_HANDLERS' => array(),
        'ENABLE_FAILURE_PREDICTION' => array(),
        'ENABLE_FLAP_DETECTION' => array(),
        
        'ENABLE_HOSTGROUP_HOST_CHECKS' => array(   
            'hostgroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_HOSTGROUP_HOST_NOTIFICATIONS' => array(   
            'hostgroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_HOSTGROUP_PASSIVE_HOST_CHECKS' => array(   
            'hostgroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_HOSTGROUP_PASSIVE_SVC_CHECKS' => array(   
            'hostgroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_HOSTGROUP_SVC_CHECKS' => array(   
            'hostgroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_HOSTGROUP_SVC_NOTIFICATIONS' => array(   
            'hostgroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_HOST_AND_CHILD_NOTIFICATIONS' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_HOST_CHECK' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_HOST_EVENT_HANDLER' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_HOST_FLAP_DETECTION' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),

        'ENABLE_HOST_FRESHNESS_CHECKS' => array(),
        
        'ENABLE_HOST_NOTIFICATIONS' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_HOST_SVC_CHECKS' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_HOST_SVC_NOTIFICATIONS' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),

        'ENABLE_NOTIFICATIONS' => array(),
        
        'ENABLE_PASSIVE_HOST_CHECKS' => array(   
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_PASSIVE_SVC_CHECKS' => array(
        	'host_name' => array(
        		'required' => true,
        		'type' => 'string'),
        	'service_description' => array(
        		'required' => true,
        		'type' => 'string')
        ),

        'ENABLE_PERFORMANCE_DATA' => array(),
        
        'ENABLE_SERVICEGROUP_HOST_CHECKS' => array(   
            'servicegroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_SERVICEGROUP_HOST_NOTIFICATIONS' => array(   
            'servicegroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_SERVICEGROUP_PASSIVE_HOST_CHECKS' => array(   
            'servicegroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_SERVICEGROUP_PASSIVE_SVC_CHECKS' => array(   
            'servicegroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_SERVICEGROUP_SVC_CHECKS' => array(   
            'servicegroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_SERVICEGROUP_SVC_NOTIFICATIONS' => array(   
            'servicegroup_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_SERVICE_FRESHNESS_CHECKS' => array(),
        
        'ENABLE_SVC_CHECK' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
            'service_description' => array(
                'required' => true,
                'type' => 'string')
        ),
        'ENABLE_SVC_EVENT_HANDLER' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
            'service_description' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_SVC_FLAP_DETECTION' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
            'service_description' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'ENABLE_SVC_NOTIFICATIONS' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
            'service_description' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'PROCESS_FILE' => array(
            'file_name' => array(
                'required' => true,
                'type' => 'string'),
            'delete' => array(
                'required' => true,
                'type' => 'boolean')
        ),
        
        'PROCESS_HOST_CHECK_RESULT' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
            'status_code' => array(
                'required' => true,
                'type' => 'integer'),
        	'plugin_output' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'PROCESS_SERVICE_CHECK_RESULT' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'service_description' => array(
                'required' => true,
                'type' => 'string'),
            'return_code' => array(
                'required' => true,
                'type' => 'integer'),
        	'plugin_output' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'READ_STATE_INFORMATION' => array(),
        
        'REMOVE_HOST_ACKNOWLEDGEMENT' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'REMOVE_SVC_ACKNOWLEDGEMENT' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'service_description' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'RESTART_PROGRAM' => array(),
        'SAVE_STATE_INFORMATION' => array(),
        
        'SCHEDULE_AND_PROPAGATE_HOST_DOWNTIME' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'start_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'end_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'fixed' => array(
                'required' => true,
                'type' => 'boolean'),
        	'trigger_id' => array(
                'required' => true,
                'type' => 'integer'),
        	'duration' => array(
                'required' => true,
                'type' => 'integer'),
        	'author' => array(
                'required' => true,
                'type' => 'string'),
        	'comment' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'SCHEDULE_AND_PROPAGATE_TRIGGERED_HOST_DOWNTIME' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'start_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'end_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'fixed' => array(
                'required' => true,
                'type' => 'boolean'),
        	'trigger_id' => array(
                'required' => true,
                'type' => 'integer'),
        	'duration' => array(
                'required' => true,
                'type' => 'integer'),
        	'author' => array(
                'required' => true,
                'type' => 'string'),
        	'comment' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'SCHEDULE_FORCED_HOST_CHECK' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'check_time' => array(
                'required' => true,
                'type' => 'integer')
        ),
        
        'SCHEDULE_FORCED_HOST_SVC_CHECKS' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'check_time' => array(
                'required' => true,
                'type' => 'integer')
        ),
        
        'SCHEDULE_FORCED_SVC_CHECK' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'service_description' => array(
                'required' => true,
                'type' => 'string'),
        	'check_time' => array(
                'required' => true,
                'type' => 'integer')
        ),
        
        'SCHEDULE_HOSTGROUP_HOST_DOWNTIME' => array(
            'hostgroup_name' => array(
                'required' => true,
                'type' => 'string'),
        	'start_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'end_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'fixed' => array(
                'required' => true,
                'type' => 'boolean'),
        	'trigger_id' => array(
                'required' => true,
                'type' => 'integer'),
        	'duration' => array(
                'required' => true,
                'type' => 'integer'),
        	'author' => array(
                'required' => true,
                'type' => 'string'),
        	'comment' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'SCHEDULE_HOSTGROUP_SVC_DOWNTIME' => array(
            'hostgroup_name' => array(
                'required' => true,
                'type' => 'string'),
        	'start_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'end_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'fixed' => array(
                'required' => true,
                'type' => 'boolean'),
        	'trigger_id' => array(
                'required' => true,
                'type' => 'integer'),
        	'duration' => array(
                'required' => true,
                'type' => 'integer'),
        	'author' => array(
                'required' => true,
                'type' => 'string'),
        	'comment' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'SCHEDULE_HOST_CHECK' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'check_time' => array(
                'required' => true,
                'type' => 'integer')
        ),
        
        'SCHEDULE_HOST_DOWNTIME' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'start_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'end_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'fixed' => array(
                'required' => true,
                'type' => 'boolean'),
        	'trigger_id' => array(
                'required' => true,
                'type' => 'integer'),
        	'duration' => array(
                'required' => true,
                'type' => 'integer'),
        	'author' => array(
                'required' => true,
                'type' => 'string'),
        	'comment' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'SCHEDULE_HOST_SVC_CHECKS' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'check_time' => array(
                'required' => true,
                'type' => 'integer')
        ),
        
        'SCHEDULE_HOST_SVC_DOWNTIME' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'start_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'end_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'fixed' => array(
                'required' => true,
                'type' => 'boolean'),
        	'trigger_id' => array(
                'required' => true,
                'type' => 'integer'),
        	'duration' => array(
                'required' => true,
                'type' => 'integer'),
        	'author' => array(
                'required' => true,
                'type' => 'string'),
        	'comment' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'SCHEDULE_SERVICEGROUP_HOST_DOWNTIME' => array(
            'servicegroup_name' => array(
                'required' => true,
                'type' => 'string'),
        	'start_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'end_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'fixed' => array(
                'required' => true,
                'type' => 'boolean'),
        	'trigger_id' => array(
                'required' => true,
                'type' => 'integer'),
        	'duration' => array(
                'required' => true,
                'type' => 'integer'),
        	'author' => array(
                'required' => true,
                'type' => 'string'),
        	'comment' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'SCHEDULE_SERVICEGROUP_SVC_DOWNTIME' => array(
            'servicegroup_name' => array(
                'required' => true,
                'type' => 'string'),
        	'start_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'end_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'fixed' => array(
                'required' => true,
                'type' => 'boolean'),
        	'trigger_id' => array(
                'required' => true,
                'type' => 'integer'),
        	'duration' => array(
                'required' => true,
                'type' => 'integer'),
        	'author' => array(
                'required' => true,
                'type' => 'string'),
        	'comment' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'SCHEDULE_SVC_CHECK' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'service_description' => array(
                'required' => true,
                'type' => 'string'),
        	'check_time' => array(
                'required' => true,
                'type' => 'integer')
        ),
        
        'SCHEDULE_SVC_DOWNTIME' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'service_description' => array(
                'required' => true,
                'type' => 'string'),
        	'start_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'end_time' => array(
                'required' => true,
                'type' => 'integer'),
        	'fixed' => array(
                'required' => true,
                'type' => 'boolean'),
        	'trigger_id' => array(
                'required' => true,
                'type' => 'integer'),
        	'duration' => array(
                'required' => true,
                'type' => 'integer'),
        	'author' => array(
                'required' => true,
                'type' => 'string'),
        	'comment' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'SEND_CUSTOM_HOST_NOTIFICATION' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'options' => array(
                'required' => true,
                'type' => 'integer'),
        	'author' => array(
                'required' => true,
                'type' => 'string'),
        	'comment' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'SEND_CUSTOM_SVC_NOTIFICATION' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'service_description' => array(
                'required' => true,
                'type' => 'string'),
        	'options' => array(
                'required' => true,
                'type' => 'integer'),
        	'author' => array(
                'required' => true,
                'type' => 'string'),
        	'comment' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'SET_HOST_NOTIFICATION_NUMBER' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'notification_number' => array(
                'required' => true,
                'type' => 'integer')
        ),
        
        'SET_SVC_NOTIFICATION_NUMBER' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'service_description' => array(
                'required' => true,
                'type' => 'string'),
        	'notification_number' => array(
                'required' => true,
                'type' => 'integer')
        ),
        
        'SHUTDOWN_PROGRAM' => array(),        
        'START_ACCEPTING_PASSIVE_HOST_CHECKS' => array(),
        'START_ACCEPTING_PASSIVE_SVC_CHECKS' => array(),
        'START_EXECUTING_HOST_CHECKS' => array(),
        'START_EXECUTING_SVC_CHECKS' => array(),
        'START_OBSESSING_OVER_HOST' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'START_OBSESSING_OVER_HOST_CHECKS' => array(),
        'START_OBSESSING_OVER_SVC' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'service_description' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'START_OBSESSING_OVER_SVC_CHECKS' => array(),
        'STOP_ACCEPTING_PASSIVE_HOST_CHECKS' => array(),
        'STOP_ACCEPTING_PASSIVE_SVC_CHECKS' => array(),
        'STOP_EXECUTING_HOST_CHECKS' => array(),
        'STOP_EXECUTING_SVC_CHECKS' => array(),
        'STOP_OBSESSING_OVER_HOST' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'STOP_OBSESSING_OVER_HOST_CHECKS' => array(),
        'STOP_OBSESSING_OVER_SVC' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
        	'service_description' => array(
                'required' => true,
                'type' => 'string')
        ),
        
        'STOP_OBSESSING_OVER_SVC_CHECKS' => array(),
    );

    /**
     * getCommand
     * 
     * An accessor method to return var $command
     *
     * @return string
     */
    function getCommand() {
        return($this->command);
    }

    /**
     * getCommands
     * 
     * An accessor method to return var $commands
     *
     * @return array
     */
    function getCommands($cmd=null) {
        if ($cmd) {
            return($this->commands[$cmd]);
        }
        return($this->commands);
    }

    /**
     * getMessage
     * 
     * An accessor method to return var $message
     *
     * @return array
     */
    function getMessage() {
        return($this->message);
    }

    /**
     * getCommandFile
     * 
     * An accessor method to return var $commandFile
     *
     * @return string
     */
    function getCommandFile() {
        return($this->commandFile);
    }

    /**
     * setCommandFile
     * 
     * A simple setter method to set var $commandFile.
     *
     * @return boolean
     */
    function setCommandFile($file) {
        if (!file_exists($file)) {
            $this->message = "$file does not exist.";
            return(false);
        }

        if (!is_writable($file) || !is_readable($file)) {
            $this->message = "$file must be readable and writable by the web server user.";
            return(false);
        }

        $this->commandFile = $file;
        return(true);
    }

    /**
     * setCommand
     * 
     * Validate the command based on the passed parameters
     *
     * Example of the expected parameters:
     *
     * $cmd = 'ACKNOWLEDGE_HOST_PROBLEM';
     * $args = array('host_name'  => 'localhost',
     *               'sticky'     => 1,
     *               'notify'     => 1,
     *               'persistent' => 0,
     *               'author'     => 'jdoe',
     *               'comment'    => 'I am working on this problem');
     *
     * @param  string    $cmd - The command
     * @param  array     $args - The command arguments
     * @return boolean
     */
    function setCommand($cmd, $args) {

        // Check that the command is valid
        if (!array_key_exists($cmd, $this->commands)) {
            $this->message = $cmd . ' is not a valid command.';
            return(false);
        }

        // Check that the command is implemented
        if (!is_array($this->commands[$cmd])) {
            $this->message = 'Command ' . $cmd . ' is not yet implemented.';
            return(false);
        }


        // Build the command string as we go:
        $now = date('U');
        $this->command = "[$now] $cmd";

        foreach ($this->commands[$cmd] as $param => $attrib) {
            if ($attrib['required']) {
                if (!array_key_exists($param, $args)) {
                    $this->message = 'Missing required parameter: ' . $param;
                    return(false);
                }
            }

            if ($attrib['type'] == 'boolean') {
                if ($args[$param] != 1 && $args[$param] != 0) {
                    $this->message = $param . ' must be equal to 1 or 0';
                    return(false);
                }
            }

            $this->command .= ";" . $args[$param];
        }

        $this->command .= "\n";

        return(true);
    }

    /**
     * execute
     * 
     * Write the command to the Nagios command file.
     * No command validation is done here. The passed 
     * command will be written to the Nagios command file.
     *
     * @return boolean
     */
    function execute($cmd = null) {

        if ($cmd) { 
            $this->command = $cmd;
        }

        // Verify the command is set
        if (!$this->command) {
            $this->message = 'You must supply a command.';
            return(false);
        }

        // Verify the command file is set
        if (!$this->commandFile) {
            $this->message = 'You must supply the command file path.';
            return(false);
        }
            
        // Write the command to the command file 
        try {
            if (!$pipe = fopen($this->commandFile, 'r+')) {
                throw new Exception('Failed to open '.$this->commandFile);
            }
            if (fwrite($pipe, $this->command) === FALSE) {
                throw new Exception('Failed to write to file: '.$this->commandFile);
            }
            fclose($pipe);
        } catch (Exception $e) {
            $this->message = $e->getMessage();
            return(false);
        }

        return(true);
    }

}
