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
 * @version             $Id: $
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
                'type' => 'string'),
        ),
        'CHANGE_CONTACT_MODATTR' => null,
        'CHANGE_CONTACT_MODHATTR' => null,
        'CHANGE_CONTACT_MODSATTR' => null,
        'CHANGE_CONTACT_SVC_NOTIFICATION_TIMEPERIOD' => null,
        'CHANGE_CUSTOM_CONTACT_VAR' => null,
        'CHANGE_CUSTOM_HOST_VAR' => null,
        'CHANGE_CUSTOM_SVC_VAR' => null,
        'CHANGE_GLOBAL_HOST_EVENT_HANDLER' => null,
        'CHANGE_GLOBAL_SVC_EVENT_HANDLER' => null,
        'CHANGE_HOST_CHECK_COMMAND' => null,
        'CHANGE_HOST_CHECK_TIMEPERIOD' => null,
        'CHANGE_HOST_CHECK_TIMEPERIOD' => null,
        'CHANGE_HOST_EVENT_HANDLER' => null,
        'CHANGE_HOST_MODATTR' => null,
        'CHANGE_MAX_HOST_CHECK_ATTEMPTS' => null,
        'CHANGE_MAX_SVC_CHECK_ATTEMPTS' => null,
        'CHANGE_NORMAL_HOST_CHECK_INTERVAL' => null,
        'CHANGE_NORMAL_SVC_CHECK_INTERVAL' => null,
        'CHANGE_RETRY_HOST_CHECK_INTERVAL' => null,
        'CHANGE_RETRY_SVC_CHECK_INTERVAL' => null,
        'CHANGE_SVC_CHECK_COMMAND' => null,
        'CHANGE_SVC_CHECK_TIMEPERIOD' => null,
        'CHANGE_SVC_EVENT_HANDLER' => null,
        'CHANGE_SVC_MODATTR' => null,
        'CHANGE_SVC_NOTIFICATION_TIMEPERIOD' => null,
        'DELAY_HOST_NOTIFICATION' => null,
        'DELAY_SVC_NOTIFICATION' => null,
        'DEL_ALL_HOST_COMMENTS' => null,
        'DEL_ALL_SVC_COMMENTS' => null,
        'DEL_HOST_COMMENT' => null,
        'DEL_HOST_DOWNTIME' => null,
        'DEL_SVC_COMMENT' => null,
        'DEL_SVC_DOWNTIME' => null,
        'DISABLE_ALL_NOTIFICATIONS_BEYOND_HOST' => null,
        'DISABLE_CONTACTGROUP_HOST_NOTIFICATIONS' => null,
        'DISABLE_CONTACTGROUP_SVC_NOTIFICATIONS' => null,
        'DISABLE_CONTACT_HOST_NOTIFICATIONS' => null,
        'DISABLE_CONTACT_SVC_NOTIFICATIONS' => null,
        'DISABLE_EVENT_HANDLERS' => null,
        'DISABLE_FAILURE_PREDICTION' => null,
        'DISABLE_FLAP_DETECTION' => null,
        'DISABLE_HOSTGROUP_HOST_CHECKS' => null,
        'DISABLE_HOSTGROUP_HOST_NOTIFICATIONS' => null,
        'DISABLE_HOSTGROUP_PASSIVE_HOST_CHECKS' => null,
        'DISABLE_HOSTGROUP_PASSIVE_SVC_CHECKS' => null,
        'DISABLE_HOSTGROUP_SVC_CHECKS' => null,
        'DISABLE_HOSTGROUP_SVC_NOTIFICATIONS' => null,
        'DISABLE_HOST_AND_CHILD_NOTIFICATIONS' => null,
        'DISABLE_HOST_CHECK' => null,
        'DISABLE_HOST_EVENT_HANDLER' => null,
        'DISABLE_HOST_FLAP_DETECTION' => null,
        'DISABLE_HOST_FRESHNESS_CHECKS' => null,
        'DISABLE_HOST_NOTIFICATIONS' => null,
        'DISABLE_HOST_SVC_CHECKS' => null,
        'DISABLE_HOST_SVC_NOTIFICATIONS' => null,
        'DISABLE_NOTIFICATIONS' => null,
        'DISABLE_PASSIVE_HOST_CHECKS' => null,
        'DISABLE_PASSIVE_SVC_CHECKS' => null,
        'DISABLE_PERFORMANCE_DATA' => null,
        'DISABLE_SERVICEGROUP_HOST_CHECKS' => null,
        'DISABLE_SERVICEGROUP_HOST_NOTIFICATIONS' => null,
        'DISABLE_SERVICEGROUP_PASSIVE_HOST_CHECKS' => null,
        'DISABLE_SERVICEGROUP_PASSIVE_SVC_CHECKS' => null,
        'DISABLE_SERVICEGROUP_SVC_CHECKS' => null,
        'DISABLE_SERVICEGROUP_SVC_NOTIFICATIONS' => null,
        'DISABLE_SERVICE_FLAP_DETECTION' => null,
        'DISABLE_SERVICE_FRESHNESS_CHECKS' => null,
        'DISABLE_SVC_CHECK' => null,
        'DISABLE_SVC_EVENT_HANDLER' => null,
        'DISABLE_SVC_FLAP_DETECTION' => null,
        'DISABLE_SVC_NOTIFICATIONS' => null,
        'ENABLE_ALL_NOTIFICATIONS_BEYOND_HOST' => null,
        'ENABLE_CONTACTGROUP_HOST_NOTIFICATIONS' => null,
        'ENABLE_CONTACTGROUP_SVC_NOTIFICATIONS' => null,
        'ENABLE_CONTACT_HOST_NOTIFICATIONS' => null,
        'ENABLE_CONTACT_SVC_NOTIFICATIONS' => null,
        'ENABLE_EVENT_HANDLERS' => null,
        'ENABLE_FAILURE_PREDICTION' => null,
        'ENABLE_FLAP_DETECTION' => null,
        'ENABLE_HOSTGROUP_HOST_CHECKS' => null,
        'ENABLE_HOSTGROUP_HOST_NOTIFICATIONS' => null,
        'ENABLE_HOSTGROUP_PASSIVE_HOST_CHECKS' => null,
        'ENABLE_HOSTGROUP_PASSIVE_SVC_CHECKS' => null,
        'ENABLE_HOSTGROUP_SVC_CHECKS' => null,
        'ENABLE_HOSTGROUP_SVC_NOTIFICATIONS' => null,
        'ENABLE_HOST_AND_CHILD_NOTIFICATIONS' => null,
        'ENABLE_HOST_CHECK' => null,
        'ENABLE_HOST_EVENT_HANDLER' => null,
        'ENABLE_HOST_FLAP_DETECTION' => null,
        'ENABLE_HOST_FRESHNESS_CHECKS' => null,
        'ENABLE_HOST_NOTIFICATIONS' => null,
        'ENABLE_HOST_SVC_CHECKS' => null,
        'ENABLE_HOST_SVC_NOTIFICATIONS' => null,
        'ENABLE_NOTIFICATIONS' => null,
        'ENABLE_PASSIVE_HOST_CHECKS' => null,
        'ENABLE_PASSIVE_SVC_CHECKS' => null,
        'ENABLE_PERFORMANCE_DATA' => null,
        'ENABLE_SERVICEGROUP_HOST_CHECKS' => null,
        'ENABLE_SERVICEGROUP_HOST_NOTIFICATIONS' => null,
        'ENABLE_SERVICEGROUP_PASSIVE_HOST_CHECKS' => null,
        'ENABLE_SERVICEGROUP_PASSIVE_SVC_CHECKS' => null,
        'ENABLE_SERVICEGROUP_SVC_CHECKS' => null,
        'ENABLE_SERVICEGROUP_SVC_NOTIFICATIONS' => null,
        'ENABLE_SERVICE_FRESHNESS_CHECKS' => null,
        'ENABLE_SVC_CHECK' => array(
            'host_name' => array(
                'required' => true,
                'type' => 'string'),
            'service_description' => array(
                'required' => true,
                'type' => 'string')
        ),
        'ENABLE_SVC_EVENT_HANDLER' => null,
        'ENABLE_SVC_FLAP_DETECTION' => null,
        'ENABLE_SVC_NOTIFICATIONS' => null,
        'PROCESS_FILE' => null,
        'PROCESS_HOST_CHECK_RESULT' => null,
        'PROCESS_SERVICE_CHECK_RESULT' => null,
        'READ_STATE_INFORMATION' => null,
        'REMOVE_HOST_ACKNOWLEDGEMENT' => null,
        'REMOVE_SVC_ACKNOWLEDGEMENT' => null,
        'RESTART_PROGRAM' => null,
        'SAVE_STATE_INFORMATION' => null,
        'SCHEDULE_AND_PROPAGATE_HOST_DOWNTIME' => null,
        'SCHEDULE_AND_PROPAGATE_TRIGGERED_HOST_DOWNTIME' => null,
        'SCHEDULE_FORCED_HOST_CHECK' => null,
        'SCHEDULE_FORCED_HOST_SVC_CHECKS' => null,
        'SCHEDULE_FORCED_SVC_CHECK' => null,
        'SCHEDULE_HOSTGROUP_HOST_DOWNTIME' => null,
        'SCHEDULE_HOSTGROUP_SVC_DOWNTIME' => null,
        'SCHEDULE_HOST_CHECK' => null,
        'SCHEDULE_HOST_DOWNTIME' => null,
        'SCHEDULE_HOST_SVC_CHECKS' => null,
        'SCHEDULE_HOST_SVC_DOWNTIME' => null,
        'SCHEDULE_SERVICEGROUP_HOST_DOWNTIME' => null,
        'SCHEDULE_SERVICEGROUP_SVC_DOWNTIME' => null,
        'SCHEDULE_SVC_CHECK' => null,
        'SCHEDULE_SVC_DOWNTIME' => null,
        'SEND_CUSTOM_HOST_NOTIFICATION' => null,
        'SEND_CUSTOM_SVC_NOTIFICATION' => null,
        'SET_HOST_NOTIFICATION_NUMBER' => null,
        'SET_SVC_NOTIFICATION_NUMBER' => null,
        'SHUTDOWN_PROGRAM' => null,
        'START_ACCEPTING_PASSIVE_HOST_CHECKS' => null,
        'START_ACCEPTING_PASSIVE_SVC_CHECKS' => null,
        'START_EXECUTING_HOST_CHECKS' => null,
        'START_EXECUTING_SVC_CHECKS' => null,
        'START_OBSESSING_OVER_HOST' => null,
        'START_OBSESSING_OVER_HOST_CHECKS' => null,
        'START_OBSESSING_OVER_SVC' => null,
        'START_OBSESSING_OVER_SVC_CHECKS' => null,
        'STOP_ACCEPTING_PASSIVE_HOST_CHECKS' => null,
        'STOP_ACCEPTING_PASSIVE_SVC_CHECKS' => null,
        'STOP_EXECUTING_HOST_CHECKS' => null,
        'STOP_EXECUTING_SVC_CHECKS' => null,
        'STOP_OBSESSING_OVER_HOST' => null,
        'STOP_OBSESSING_OVER_HOST_CHECKS' => null,
        'STOP_OBSESSING_OVER_SVC' => null,
        'STOP_OBSESSING_OVER_SVC_CHECKS' => null,
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
