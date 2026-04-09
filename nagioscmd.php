<?php

declare(strict_types=1);
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

require_once('include/auth.php');

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
     * A list of the commands and their required attributes
     *
     * @var array
     * @access private
     */
    private $commands = array(
		'ACKNOWLEDGE_HOST_PROBLEM' => array(
			'host_name' => [],
			'sticky' => [],
			'notify' => [],
			'persistent' => [],
			'author' => [],
			'comment' => []
		),

 		'ACKNOWLEDGE_SVC_PROBLEM' => array(
			'host_name' => [],
			'service_description' => [],
			'sticky' => [],
			'notify' => [],
			'persistent' => [],
			'author' => [],
			'comment' => []
		),

 		'ADD_HOST_COMMENT' => array(
			'host_name' => [],
			'persistent' => [],
			'author' => [],
			'comment' => []
		),

 		'ADD_SVC_COMMENT' => array(
			'host_name' => [],
			'service_description' => [],
			'persistent' => [],
			'author' => [],
			'comment' => []
		),

 		'CHANGE_CONTACT_HOST_NOTIFICATION_TIMEPERIOD' => array(
			'contact_name' => [],
			'notification_timeperiod' => []
		),

 		'CHANGE_CONTACT_MODATTR' => array(
			'contact_name' => [],
			'value' => []
		),

 		'CHANGE_CONTACT_MODHATTR' => array(
			'contact_name' => [],
			'value' => []
		),

 		'CHANGE_CONTACT_MODSATTR' => array(
			'contact_name' => [],
			'value' => []
		),

 		'CHANGE_CONTACT_SVC_NOTIFICATION_TIMEPERIOD' => array(
			'contact_name' => [],
			'notification_timeperiod' => []
		),

 		'CHANGE_CUSTOM_CONTACT_VAR' => array(
			'contact_name' => [],
			'varname' => [],
			'varvalue' => []
		),

 		'CHANGE_CUSTOM_HOST_VAR' => array(
			'host_name' => [],
			'varname' => [],
			'varvalue' => []
		),

 		'CHANGE_CUSTOM_SVC_VAR' => array(
			'host_name' => [],
			'service_description' => [],
			'varname' => [],
			'varvalue' => []
		),

 		'CHANGE_GLOBAL_HOST_EVENT_HANDLER' => array(
			'event_handler_command' => []
		),

 		'CHANGE_GLOBAL_SVC_EVENT_HANDLER' => array(
			'event_handler_command' => []
		),

 		'CHANGE_HOST_CHECK_COMMAND' => array(
			'host_name' => [],
			'check_command' => []
		),

 		'CHANGE_HOST_CHECK_TIMEPERIOD' => [],

 		'CHANGE_HOST_EVENT_HANDLER' => array(
			'host_name' => [],
			'event_handler_command' => []
		),

 		'CHANGE_HOST_MODATTR' => array(
			'host_name' => [],
			'value' => []
		),

 		'CHANGE_MAX_HOST_CHECK_ATTEMPTS' => array(
			'host_name' => [],
			'check_attempts' => []
		),

 		'CHANGE_MAX_SVC_CHECK_ATTEMPTS' => array(
			'host_name' => [],
			'service_description' => [],
			'check_attempts' => []
		),

 		'CHANGE_NORMAL_HOST_CHECK_INTERVAL' => array(
			'host_name' => [],
			'check_interval' => []
		),

 		'CHANGE_NORMAL_SVC_CHECK_INTERVAL' => array(
			'host_name' => [],
			'service_description' => [],
			'check_interval' => []
		),

        /* FIXME I think documentation is incorrect for this command
         * http://www.nagios.org/developerinfo/externalcommands/commandinfo.php?command_id=136
         * parameters listed as <host_name>;<service_description>;<check_interval>
         * example shows: CHANGE_RETRY_HOST_CHECK_INTERVAL;host1;5\n" $now > $commandfile
         * which should be command;<host_name>;<check_interval> (no service_description)
		*/
 		'CHANGE_RETRY_HOST_CHECK_INTERVAL' => array(
			'host_name' => [],
			'check_interval' => []
		),

 		'CHANGE_RETRY_SVC_CHECK_INTERVAL' => array(
			'host_name' => [],
			'service_description' => [],
			'check_interval' => []
		),

 		'CHANGE_SVC_CHECK_COMMAND' => array(
			'host_name' => [],
			'service_description' => [],
			'check_command' => []
		),

 		'CHANGE_SVC_CHECK_TIMEPERIOD' => array(
			'host_name' => [],
			'service_description' => [],
			'check_timeperiod' => []
		),

 		'CHANGE_SVC_EVENT_HANDLER' => array(
			'host_name' => [],
			'service_description' => [],
			'event_handler_command' => []
		),

 		'CHANGE_SVC_MODATTR' => array(
			'host_name' => [],
			'service_description' => [],
			'value' => []
		),

 		'CHANGE_SVC_NOTIFICATION_TIMEPERIOD' => array(
			'host_name' => [],
			'service_description' => [],
			'notification_timeperiod' => []
		),

 		'DELAY_HOST_NOTIFICATION' => array(
			'host_name' => [],
			'notification_time' => []
		),

 		'DELAY_SVC_NOTIFICATION' => array(
			'host_name' => [],
			'service_description' => [],
			'notification_time' => []
		),

 		'DEL_ALL_HOST_COMMENTS' => array(
			'host_name' => []
		),

 		'DEL_ALL_SVC_COMMENTS' => array(
			'host_name' => [],
			'service_description' => []
		),

 		'DEL_HOST_COMMENT' => array(
			'comment_id' => []
		),

 		'DEL_HOST_DOWNTIME' => array(
			'downtime_id' => []
		),

 		'DEL_SVC_COMMENT' => array(
			'comment_id' => []
		),

 		'DEL_SVC_DOWNTIME' => array(
			'downtime_id' => []
		),

 		'DISABLE_ALL_NOTIFICATIONS_BEYOND_HOST' => array(
			'host_name' => []
		),

 		'DISABLE_CONTACTGROUP_HOST_NOTIFICATIONS' => array(
			'contactgroup_name' => []
		),

 		'DISABLE_CONTACTGROUP_SVC_NOTIFICATIONS' => array(
			'contactgroup_name' => []
		),

 		'DISABLE_CONTACT_HOST_NOTIFICATIONS' => array(
			'contact_name' => []
		),

 		'DISABLE_CONTACT_SVC_NOTIFICATIONS' => array(
			'contact_name' => []
		),

 		'DISABLE_EVENT_HANDLERS' => [],
 		'DISABLE_FAILURE_PREDICTION' => [],
 		'DISABLE_FLAP_DETECTION' => [],

 		'DISABLE_HOSTGROUP_HOST_CHECKS' => array(
			'hostgroup_name' => []
		),

 		'DISABLE_HOSTGROUP_HOST_NOTIFICATIONS' => array(
			'hostgroup_name' => []
		),

 		'DISABLE_HOSTGROUP_PASSIVE_HOST_CHECKS' => array(
			'hostgroup_name' => []
		),

 		'DISABLE_HOSTGROUP_PASSIVE_SVC_CHECKS' => array(
			'hostgroup_name' => []
		),

 		'DISABLE_HOSTGROUP_SVC_CHECKS' => array(
			'hostgroup_name' => []
		),

 		'DISABLE_HOSTGROUP_SVC_NOTIFICATIONS' => array(
			'hostgroup_name' => []
		),

 		'DISABLE_HOST_AND_CHILD_NOTIFICATIONS' => array(
			'host_name' => []
		),

 		'DISABLE_HOST_CHECK' => array(
			'host_name' => []
		),

 		'DISABLE_HOST_EVENT_HANDLER' => array(
			'host_name' => []
		),

 		'DISABLE_HOST_FLAP_DETECTION' => array(
			'host_name' => []
		),

 		'DISABLE_HOST_FRESHNESS_CHECKS' => [],

 		'DISABLE_HOST_NOTIFICATIONS' => array(
			'host_name' => []
		),

 		'DISABLE_HOST_SVC_CHECKS' => array(
			'host_name' => []
		),

 		'DISABLE_HOST_SVC_NOTIFICATIONS' => array(
			'host_name' => []
		),

 		'DISABLE_NOTIFICATIONS' => [],

 		'DISABLE_PASSIVE_HOST_CHECKS' => array(
			'host_name' => []
		),

 		'DISABLE_PASSIVE_SVC_CHECKS' => array(
			'host_name' => [],
			'service_description' => []
		),

 		'DISABLE_PERFORMANCE_DATA' => [],

 		'DISABLE_SERVICEGROUP_HOST_CHECKS' => array(
			'servicegroup_name' => []
		),

 		'DISABLE_SERVICEGROUP_HOST_NOTIFICATIONS' => array(
			'servicegroup_name' => []
		),

 		'DISABLE_SERVICEGROUP_PASSIVE_HOST_CHECKS' => array(
			'servicegroup_name' => []
		),

 		'DISABLE_SERVICEGROUP_PASSIVE_SVC_CHECKS' => array(
			'servicegroup_name' => []
		),

 		'DISABLE_SERVICEGROUP_SVC_CHECKS' => array(
			'servicegroup_name' => []
		),

 		'DISABLE_SERVICEGROUP_SVC_NOTIFICATIONS' => array(
			'servicegroup_name' => []
		),

 		'DISABLE_SERVICE_FLAP_DETECTION' => array(
			'host_name' => [],
			'service_description' => []
		),

 		'DISABLE_SERVICE_FRESHNESS_CHECKS' => [],

 		'DISABLE_SVC_CHECK' => array(
			'host_name' => [],
			'service_description' => []
		),

 		'DISABLE_SVC_EVENT_HANDLER' => array(
			'host_name' => [],
			'service_description' => []
		),

 		'DISABLE_SVC_FLAP_DETECTION' => array(
			'host_name' => [],
			'service_description' => []
		),

 		'DISABLE_SVC_NOTIFICATIONS' => array(
			'host_name' => [],
			'service_description' => []
		),

 		'ENABLE_ALL_NOTIFICATIONS_BEYOND_HOST' => array(
			'host_name' => []
		),

 		'ENABLE_CONTACTGROUP_HOST_NOTIFICATIONS' => array(
			'contactgroup_name' => []
		),

 		'ENABLE_CONTACTGROUP_SVC_NOTIFICATIONS' => array(
			'contactgroup_name' => []
		),

 		'ENABLE_CONTACT_HOST_NOTIFICATIONS' => array(
			'contact_name' => []
		),

 		'ENABLE_CONTACT_SVC_NOTIFICATIONS' => array(
			'contact_name' => []
		),

 		'ENABLE_EVENT_HANDLERS' => [],
 		'ENABLE_FAILURE_PREDICTION' => [],
 		'ENABLE_FLAP_DETECTION' => [],

 		'ENABLE_HOSTGROUP_HOST_CHECKS' => array(
			'hostgroup_name' => []
		),

 		'ENABLE_HOSTGROUP_HOST_NOTIFICATIONS' => array(
		'hostgroup_name' => []
		),

 		'ENABLE_HOSTGROUP_PASSIVE_HOST_CHECKS' => array(
			'hostgroup_name' => []
		),

 		'ENABLE_HOSTGROUP_PASSIVE_SVC_CHECKS' => array(
			'hostgroup_name' => []
		),

 		'ENABLE_HOSTGROUP_SVC_CHECKS' => array(
			'hostgroup_name' => []
		),

 		'ENABLE_HOSTGROUP_SVC_NOTIFICATIONS' => array(
			'hostgroup_name' => []
		),

 		'ENABLE_HOST_AND_CHILD_NOTIFICATIONS' => array(
			'host_name' => []
		),

 		'ENABLE_HOST_CHECK' => array(
			'host_name' => []
		),

 		'ENABLE_HOST_EVENT_HANDLER' => array(
			'host_name' => []
		),

 		'ENABLE_HOST_FLAP_DETECTION' => array(
			'host_name' => []
		),

 		'ENABLE_HOST_FRESHNESS_CHECKS' => [],

 		'ENABLE_HOST_NOTIFICATIONS' => array(
			'host_name' => []
		),

 		'ENABLE_HOST_SVC_CHECKS' => array(
			'host_name' => []
		),

 		'ENABLE_HOST_SVC_NOTIFICATIONS' => array(
		'host_name' => []
		),

 		'ENABLE_NOTIFICATIONS' => [],

 		'ENABLE_PASSIVE_HOST_CHECKS' => array(
			'host_name' => []
		),

 		'ENABLE_PASSIVE_SVC_CHECKS' => array(
			'host_name' => [],
			'service_description' => []
		),

		'ENABLE_PERFORMANCE_DATA' => [],

		'ENABLE_SERVICEGROUP_HOST_CHECKS' => array(
			'servicegroup_name' => []
		),

		'ENABLE_SERVICEGROUP_HOST_NOTIFICATIONS' => array(
			'servicegroup_name' => []
		),

		'ENABLE_SERVICEGROUP_PASSIVE_HOST_CHECKS' => array(
			'servicegroup_name' => []
		),

		'ENABLE_SERVICEGROUP_PASSIVE_SVC_CHECKS' => array(
			'servicegroup_name' => []
		),

		'ENABLE_SERVICEGROUP_SVC_CHECKS' => array(
			'servicegroup_name' => []
		),

		'ENABLE_SERVICEGROUP_SVC_NOTIFICATIONS' => array(
			'servicegroup_name' => []
		),

		'ENABLE_SERVICE_FRESHNESS_CHECKS' => [],

		'ENABLE_SVC_CHECK' => array(
			'host_name' => [],
			'service_description' => []
		),

		'ENABLE_SVC_EVENT_HANDLER' => array(
			'host_name' => [],
			'service_description' => []
		),

		'ENABLE_SVC_FLAP_DETECTION' => array(
			'host_name' => [],
			'service_description' => []
		),

		'ENABLE_SVC_NOTIFICATIONS' => array(
			'host_name' => [],
			'service_description' => []
		),

		'PROCESS_FILE' => array(
			'file_name' => [],
			'delete' => []
		),

		'PROCESS_HOST_CHECK_RESULT' => array(
			'host_name' => [],
			'status_code' => [],
			'plugin_output' => []
		),

		'PROCESS_SERVICE_CHECK_RESULT' => array(
			'host_name' => [],
			'service_description' => [],
			'return_code' => [],
			'plugin_output' => []
		),

		'READ_STATE_INFORMATION' => [],

		'REMOVE_HOST_ACKNOWLEDGEMENT' => array(
			'host_name' => []
		),

		'REMOVE_SVC_ACKNOWLEDGEMENT' => array(
			'host_name' => [],
			'service_description' => []
		),

		'RESTART_PROGRAM' => [],
		'SAVE_STATE_INFORMATION' => [],

		'SCHEDULE_AND_PROPAGATE_HOST_DOWNTIME' => array(
			'host_name' => [],
			'start_time' => [],
			'end_time' => [],
			'fixed' => [],
			'trigger_id' => [],
			'duration' => [],
			'author' => [],
			'comment' => []
		),

		'SCHEDULE_AND_PROPAGATE_TRIGGERED_HOST_DOWNTIME' => array(
			'host_name' => [],
			'start_time' => [],
			'end_time' => [],
			'fixed' => [],
			'trigger_id' => [],
			'duration' => [],
			'author' => [],
			'comment' => []
		),

		'SCHEDULE_FORCED_HOST_CHECK' => array(
			'host_name' => [],
			'check_time' => []
		),

		'SCHEDULE_FORCED_HOST_SVC_CHECKS' => array(
			'host_name' => [],
			'check_time' => []
		),

		'SCHEDULE_FORCED_SVC_CHECK' => array(
			'host_name' => [],
			'service_description' => [],
			'check_time' => []
		),

		'SCHEDULE_HOSTGROUP_HOST_DOWNTIME' => array(
			'hostgroup_name' => [],
			'start_time' => [],
			'end_time' => [],
			'fixed' => [],
			'trigger_id' => [],
			'duration' => [],
			'author' => [],
			'comment' => []
		),

		'SCHEDULE_HOSTGROUP_SVC_DOWNTIME' => array(
			'hostgroup_name' => [],
			'start_time' => [],
			'end_time' => [],
			'fixed' => [],
			'trigger_id' => [],
			'duration' => [],
			'author' => [],
			'comment' => []
		),

		'SCHEDULE_HOST_CHECK' => array(
			'host_name' => [],
			'check_time' => []
		),

		'SCHEDULE_HOST_DOWNTIME' => array(
			'host_name' => [],
			'start_time' => [],
			'end_time' => [],
			'fixed' => [],
			'trigger_id' => [],
			'duration' => [],
			'author' => [],
			'comment' => []
		),

		'SCHEDULE_HOST_SVC_CHECKS' => array(
			'host_name' => [],
			'check_time' => []
		),

		'SCHEDULE_HOST_SVC_DOWNTIME' => array(
			'host_name' => [],
			'start_time' => [],
			'end_time' => [],
			'fixed' => [],
			'trigger_id' => [],
			'duration' => [],
			'author' => [],
			'comment' => []
		),

		'SCHEDULE_SERVICEGROUP_HOST_DOWNTIME' => array(
			'servicegroup_name' => [],
			'start_time' => [],
			'end_time' => [],
			'fixed' => [],
			'trigger_id' => [],
			'duration' => [],
			'author' => [],
			'comment' => []
		),

		'SCHEDULE_SERVICEGROUP_SVC_DOWNTIME' => array(
			'servicegroup_name' => [],
			'start_time' => [],
			'end_time' => [],
			'fixed' => [],
			'trigger_id' => [],
			'duration' => [],
			'author' => [],
			'comment' => []
		),

		'SCHEDULE_SVC_CHECK' => array(
			'host_name' => [],
			'service_description' => [],
			'check_time' => []
		),

		'SCHEDULE_SVC_DOWNTIME' => array(
			'host_name' => [],
			'service_description' => [],
			'start_time' => [],
			'end_time' => [],
			'fixed' => [],
			'trigger_id' => [],
			'duration' => [],
			'author' => [],
			'comment' => []
		),

		'SEND_CUSTOM_HOST_NOTIFICATION' => array(
			'host_name' => [],
			'options' => [],
			'author' => [],
			'comment' => []
		),

		'SEND_CUSTOM_SVC_NOTIFICATION' => array(
			'host_name' => [],
			'service_description' => [],
			'options' => [],
			'author' => [],
			'comment' => []
		),

		'SET_HOST_NOTIFICATION_NUMBER' => array(
			'host_name' => [],
			'notification_number' => []
		),

		'SET_SVC_NOTIFICATION_NUMBER' => array(
			'host_name' => [],
			'service_description' => [],
			'notification_number' => []
		),

		'SHUTDOWN_PROGRAM' => [],
		'START_ACCEPTING_PASSIVE_HOST_CHECKS' => [],
		'START_ACCEPTING_PASSIVE_SVC_CHECKS' => [],
		'START_EXECUTING_HOST_CHECKS' => [],
		'START_EXECUTING_SVC_CHECKS' => [],
		'START_OBSESSING_OVER_HOST' => array(
			'host_name' => []
		),

		'START_OBSESSING_OVER_HOST_CHECKS' => [],
		'START_OBSESSING_OVER_SVC' => array(
			'host_name' => [],
			'service_description' => []
		),

		'START_OBSESSING_OVER_SVC_CHECKS' => [],
		'STOP_ACCEPTING_PASSIVE_HOST_CHECKS' => [],
		'STOP_ACCEPTING_PASSIVE_SVC_CHECKS' => [],
		'STOP_EXECUTING_HOST_CHECKS' => [],
		'STOP_EXECUTING_SVC_CHECKS' => [],
		'STOP_OBSESSING_OVER_HOST' => array(
			'host_name' => []
		),

		'STOP_OBSESSING_OVER_HOST_CHECKS' => [],
		'STOP_OBSESSING_OVER_SVC' => array(
			'host_name' => [],
			'service_description' => []
		),

		'STOP_OBSESSING_OVER_SVC_CHECKS' => [],
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
     * $args = [];
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
        if (!is_[]) {
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
