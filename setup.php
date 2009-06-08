<?php
/* $Id$ */
/*
 +-------------------------------------------------------------------------+
 | Nagios Plugin for Cacti                                                 |
 |                                                                         |
 | Copyright (C) 2007 Billy Gunn (billy@gunn.org)                          |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti and Nagios are the copyright of their respective owners.          |
 +-------------------------------------------------------------------------+
*/

/**
 * Called after install
 *
 * if return true, plugin will be installed but disabled
 * if return false, plugin will be waiting configuration
 *
 * @return  bool
 */
function plugin_npc_check_config() {
    return true;
}

/**
 * compatibility for plugin update
 */
function npc_version() {
    return plugin_npc_version();
}

/**
 * Version information
 */
function plugin_npc_version() {
    return array(   'name'          => 'npc',
                    'version'       => '2.0.3',
                    'longname'      => 'Nagios plugin for Cacti',
                    'author'        => 'Billy Gunn',
                    'homepage'      => 'http://trac2.assembla.com/npc',
                    'email'         => 'billy@gunn.org',
                    'url'           => 'http://trac2.assembla.com/npc'
    );
}

function plugin_npc_install() {

    npc_setup_tables();

    api_plugin_register_realm ('npc', 'npc.php', 'NPC', 1);
    api_plugin_register_realm ('npc', 'npc1.php', 'NPC Global Commands', 1);

    // setup all arrays needed for npc
    api_plugin_register_hook ('npc', 'config_arrays', 'npc_config_arrays', 'setup.php');

    // Add the npc tab
    api_plugin_register_hook ('npc', 'top_header_tabs', 'npc_show_tab', 'setup.php');
    api_plugin_register_hook ('npc', 'top_graph_header_tabs', 'npc_show_tab', 'setup.php');

    // Provide navigation texts
    api_plugin_register_hook ('npc', 'draw_navigation_text', 'npc_draw_navigation_text', 'setup.php');

    // Add Nagios host mapping select box
    api_plugin_register_hook ('npc', 'config_form', 'npc_config_form', 'setup.php');

    // Saves the selection from the host mapping select box
    api_plugin_register_hook ('npc', 'api_device_save', 'npc_api_device_save', 'setup.php');

    // Add a npc tab to the settings page
    api_plugin_register_hook ('npc', 'config_settings', 'npc_config_settings', 'setup.php');
}

/**
 * Remove all NPC database changes
 */
function plugin_npc_uninstall () {

    // Drop all npc tables
    db_execute("DROP TABLE `npc_acknowledgements`");
    db_execute("DROP TABLE `npc_commands`");
    db_execute("DROP TABLE `npc_commenthistory`");
    db_execute("DROP TABLE `npc_comments`");
    db_execute("DROP TABLE `npc_configfiles`");
    db_execute("DROP TABLE `npc_configfilevariables`");
    db_execute("DROP TABLE `npc_conninfo`");
    db_execute("DROP TABLE `npc_contact_addresses`");
    db_execute("DROP TABLE `npc_contact_notificationcommands`");
    db_execute("DROP TABLE `npc_contactgroup_members`");
    db_execute("DROP TABLE `npc_contactgroups`");
    db_execute("DROP TABLE `npc_contactnotificationmethods`");
    db_execute("DROP TABLE `npc_contactnotifications`");
    db_execute("DROP TABLE `npc_contacts`");
    db_execute("DROP TABLE `npc_contactstatus`");
    db_execute("DROP TABLE `npc_customvariables`");
    db_execute("DROP TABLE `npc_customvariablestatus`");
    db_execute("DROP TABLE `npc_dbversion`");
    db_execute("DROP TABLE `npc_downtimehistory`");
    db_execute("DROP TABLE `npc_eventhandlers`");
    db_execute("DROP TABLE `npc_externalcommands`");
    db_execute("DROP TABLE `npc_flappinghistory`");
    db_execute("DROP TABLE `npc_host_contactgroups`");
    db_execute("DROP TABLE `npc_host_contacts`");
    db_execute("DROP TABLE `npc_host_graphs`");
    db_execute("DROP TABLE `npc_host_parenthosts`");
    db_execute("DROP TABLE `npc_hostchecks`");
    db_execute("DROP TABLE `npc_hostdependencies`");
    db_execute("DROP TABLE `npc_hostescalation_contactgroups`");
    db_execute("DROP TABLE `npc_hostescalation_contacts`");
    db_execute("DROP TABLE `npc_hostescalations`");
    db_execute("DROP TABLE `npc_hostgroup_members`");
    db_execute("DROP TABLE `npc_hostgroups`");
    db_execute("DROP TABLE `npc_hosts`");
    db_execute("DROP TABLE `npc_hoststatus`");
    db_execute("DROP TABLE `npc_instances`");
    db_execute("DROP TABLE `npc_logentries`");
    db_execute("DROP TABLE `npc_notifications`");
    db_execute("DROP TABLE `npc_objects`");
    db_execute("DROP TABLE `npc_processevents`");
    db_execute("DROP TABLE `npc_programstatus`");
    db_execute("DROP TABLE `npc_runtimevariables`");
    db_execute("DROP TABLE `npc_scheduleddowntime`");
    db_execute("DROP TABLE `npc_service_contactgroups`");
    db_execute("DROP TABLE `npc_service_contacts`");
    db_execute("DROP TABLE `npc_service_graphs`");
    db_execute("DROP TABLE `npc_servicechecks`");
    db_execute("DROP TABLE `npc_servicedependencies`");
    db_execute("DROP TABLE `npc_serviceescalation_contactgroups`");
    db_execute("DROP TABLE `npc_serviceescalation_contacts`");
    db_execute("DROP TABLE `npc_serviceescalations`");
    db_execute("DROP TABLE `npc_servicegroup_members`");
    db_execute("DROP TABLE `npc_servicegroups`");
    db_execute("DROP TABLE `npc_services`");
    db_execute("DROP TABLE `npc_servicestatus`");
    db_execute("DROP TABLE `npc_settings`");
    db_execute("DROP TABLE `npc_statehistory`");
    db_execute("DROP TABLE `npc_systemcommands`");
    db_execute("DROP TABLE `npc_timedeventqueue`");
    db_execute("DROP TABLE `npc_timedevents`");
    db_execute("DROP TABLE `npc_timeperiod_timeranges`");
    db_execute("DROP TABLE `npc_timeperiods`");

    db_execute("ALTER TABLE `host` DROP `npc_host_object_id`");
    db_execute("DELETE FROM `settings` WHERE `name` like 'npc\_%'");
  
    api_plugin_remove_realms ('npc');
}

function npc_config_arrays () {

    global $user_auth_realms, $user_auth_realm_filenames, $npc_date_format, $npc_time_format;
    global $npc_default_settings, $npc_log_level, $npc_config_type;

      if (isset($_SESSION["sess_user_id"])) {

        $user_id=$_SESSION["sess_user_id"];

        $npc_realm = db_fetch_cell("SELECT id FROM plugin_config WHERE directory = 'npc'");
        $npc_enabled = db_fetch_cell("SELECT status FROM plugin_config WHERE directory = 'npc'");

        if ($npc_enabled == "1") {

            $user_auth_realm_filenames['npc.php'] = 9000 + $npc_realm;

            $npc_log_level = array(
                "0" => "None",
                "1" => "ERROR - Log errors only",
                "2" => "WARN  - Log errors and warnings",
                "3" => "INFO  - Log errors, warnings, and info messages",
                "4" => "DEBUG - Log everything"
            );

            $npc_config_type = array(
                "0" => "0",
                "1" => "1"
            );

            $npc_date_format = array(
                "Y-m-d" => "2007-12-27",
                "m-d-Y" => "12-27-2007",
                "d-m-Y" => "27-12-2007",
                "Y/m/d" => "2007/12/27",
                "m/d/Y" => "12/27/2007",
                "d/m/Y" => "27/12/2007",
                "Y.m.d" => "2007.12.27",
                "d.m.Y" => "27.12.2007",
                "m.d.Y" => "12.27.2007"
            );

            $npc_time_format = array(
                "H:i:s"  => "23:07",
                "h:i:sa" => "11:07pm",
                "h:i:sA" => "11:07PM",
                "H.i.s"  => "23.07",
                "h.i.sa" => "11.07pm",
                "h.i.sA" => "11.07PM"
            );  

            // Initial settings for server side state handling
            $npc_default_settings = array(
                'date_format' => "s%3AY-m-d",
                'time_format' => "s%3AH%3Ai%3As",

                "serviceProblems" => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol1%5Ehidden%3Db%253A0%5Eindex%3Ds%253A0%5Erefresh%3Dn%253A120%5Eheight%3Dn%253A150',
                "serviceSummary" => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol1%5Ehidden%3Db%253A0%5Eindex%3Ds%253A1%5Erefresh%3Dn%253A120%5E',
                "servicegroupServiceStatus" => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol1%5Ehidden%3Db%253A0%5Eindex%3Ds%253A2%5Erefresh%3Dn%253A120%5Eheight%3Dn%253A150',
                "servicegroupHostStatus" => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol1%5Ehidden%3Db%253A0%5Eindex%3Ds%253A3%5Erefresh%3Dn%253A120%5Eheight%3Dn%253A150',
                "monitoringPerf" => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol1%5Ehidden%3Db%253A1%5Eindex%3Ds%253A4%5Erefresh%3Dn%253A120%5E',

                "hostProblems" => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol2%5Ehidden%3Db%253A0%5Eindex%3Ds%253A0%5Erefresh%3Dn%253A120%5Eheight%3Dn%253A150',
                "hostSummary" => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol2%5Ehidden%3Db%253A0%5Eindex%3Ds%253A1%5Erefresh%3Dn%253A120%5E',
                "hostgroupServiceStatus" => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol2%5Ehidden%3Db%253A0%5Eindex%3Ds%253A2%5Erefresh%3Dn%253A120%5Eheight%3Dn%253A150',
                "hostgroupHostStatus" => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol2%5Ehidden%3Db%253A0%5Eindex%3Ds%253A3%5Erefresh%3Dn%253A120%5Eheight%3Dn%253A150',
                "eventLog" => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol2%5Ehidden%3Db%253A1%5Eindex%3Ds%253A4%5Erefresh%3Dn%253A120%5Eheight%3Dn%253A150'
            );

            api_plugin_load_realms();
        }
    }
}

function npc_config_form () {
        global $fields_host_edit;
        $fields_host_edit2 = $fields_host_edit;
        $fields_host_edit3 = array();
        foreach ($fields_host_edit2 as $f => $a) {
                $fields_host_edit3[$f] = $a;
                if ($f == 'disabled') {
                        $fields_host_edit3["npc_host_object_id"] = array(
                                "method" => "drop_sql",
                                "friendly_name" => "Nagios Host Mapping",
                                "description" => "Select the Nagios host that maps to this host.",
                                "value" => "|arg1:npc_host_object_id|",
                                "none_value" => "None",
                                "default" => "0",
                                "sql" => "SELECT npc_hosts.host_object_id as id, concat(npc_instances.instance_name, ': ', obj1.name1) "
                                       . "AS name FROM `npc_hosts` LEFT JOIN npc_objects as obj1 ON npc_hosts.host_object_id=obj1.object_id "
                                       . "LEFT JOIN npc_instances ON npc_hosts.instance_id=npc_instances.instance_id",
                                "form_id" => false
                        );
                }
        }
        $fields_host_edit = $fields_host_edit3;
}

function npc_api_device_save ($save) {
        if (isset($_POST['npc_host_object_id'])) {
                $save["npc_host_object_id"] = form_input_validate($_POST['npc_host_object_id'], "npc_host_object_id", "", true, 3);
        } else {
                $save['npc_host_object_id'] = form_input_validate('', "npc_host_object_id", "", true, 3);
	}

        return $save;
}

function npc_draw_navigation_text ($nav) {
   $nav["npc.php:"] = array("title" => "NPC", "mapping" => "index.php:", "url" => "npc.php", "level" => "1");
   $nav["statusDetail.php:"] = array("title" => "Status Detail", "mapping" => "npc.php:", "url" => "statusDetail.php", "level" => "3");
   $nav["extinfo.php:"] = array("title" => "Service / Host Information", "mapping" => "statusDetail.php:", "url" => "extinfo.php", "level" => "4");
   $nav["command.php:"] = array("title" => "Command Execution", "mapping" => "extinfo.php:", "url" => "command.php", "level" => "5");
   return $nav;
}

function npc_setup_tables() {
    global $config, $database_default;

    include_once($config["library_path"] . "/database.php");

    // Set the version
    $version = npc_version();
    $version = $version['version'];
    db_execute("REPLACE INTO settings (name, value) VALUES ('plugin_npc_version', '$version')");

    $found = false;
    $result = mysql_query("SHOW COLUMNS FROM host FROM " . $database_default) or die (mysql_error());
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        if ($row['Field'] == 'npc_host_object_id') {
            $found = true;
        }
    }

    if (!$found) {
        $sql = "ALTER TABLE host ADD npc_host_object_id int(11) default NULL COMMENT 'Nagios host object mapping'";
        $result = mysql_query($sql) or die (mysql_error());
    }


    $result = db_fetch_assoc('show tables');

    $tables = array();
    $sql = array();

    if (count($result) > 1) {
            foreach($result as $index => $arr) {
                    foreach ($arr as $t) {
                            $tables[] = $t;
                    }
            }
    }

    if (!in_array('npc_acknowledgements', $tables)) {
        $sql[] = "CREATE TABLE `npc_acknowledgements` (
                    `acknowledgement_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `entry_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `entry_time_usec` int(11) NOT NULL default '0',
                    `acknowledgement_type` smallint(6) NOT NULL default '0',
                    `object_id` int(11) NOT NULL default '0',
                    `state` smallint(6) NOT NULL default '0',
                    `author_name` varchar(64) NOT NULL default '',
                    `comment_data` varchar(255) NOT NULL default '',
                    `is_sticky` smallint(6) NOT NULL default '0',
                    `persistent_comment` smallint(6) NOT NULL default '0',
                    `notify_contacts` smallint(6) NOT NULL default '0',
                    PRIMARY KEY  (`acknowledgement_id`)
                 )  ENGINE=InnoDB COMMENT='Current and historical host and service acknowledgements';";

        // Add some default values
        $sql[] = "INSERT INTO settings VALUES ('npc_date_format','Y-m-d');";
        $sql[] = "INSERT INTO settings VALUES ('npc_time_format','H:i');";
        $sql[] = "INSERT INTO settings VALUES ('npc_log_level','0');";
    }

    if (!in_array('npc_commands', $tables)) {
        $sql[] = "CREATE TABLE `npc_commands` (
                    `command_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `config_type` smallint(6) NOT NULL default '0',
                    `object_id` int(11) NOT NULL default '0',
                    `command_line` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`command_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`object_id`,`config_type`)
                  ) ENGINE=InnoDB COMMENT='Command definitions';";
    }

    if (!in_array('npc_commenthistory', $tables)) {
        $sql[] = "CREATE TABLE `npc_commenthistory` (
                    `commenthistory_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `entry_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `entry_time_usec` int(11) NOT NULL default '0',
                    `comment_type` smallint(6) NOT NULL default '0',
                    `entry_type` smallint(6) NOT NULL default '0',
                    `object_id` int(11) NOT NULL default '0',
                    `comment_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `internal_comment_id` int(11) NOT NULL default '0',
                    `author_name` varchar(64) NOT NULL default '',
                    `comment_data` varchar(255) NOT NULL default '',
                    `is_persistent` smallint(6) NOT NULL default '0',
                    `comment_source` smallint(6) NOT NULL default '0',
                    `expires` smallint(6) NOT NULL default '0',
                    `expiration_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `deletion_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `deletion_time_usec` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`commenthistory_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`comment_time`,`internal_comment_id`),
                    KEY `idx_internal_comment_id` (`internal_comment_id`)
                  ) ENGINE=InnoDB COMMENT='Historical host and service comments';";
    }

    if (!in_array('npc_comments', $tables)) {
        $sql[] = "CREATE TABLE `npc_comments` (
                    `comment_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `entry_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `entry_time_usec` int(11) NOT NULL default '0',
                    `comment_type` smallint(6) NOT NULL default '0',
                    `entry_type` smallint(6) NOT NULL default '0',
                    `object_id` int(11) NOT NULL default '0',
                    `comment_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `internal_comment_id` int(11) NOT NULL default '0',
                    `author_name` varchar(64) NOT NULL default '',
                    `comment_data` varchar(255) NOT NULL default '',
                    `is_persistent` smallint(6) NOT NULL default '0',
                    `comment_source` smallint(6) NOT NULL default '0',
                    `expires` smallint(6) NOT NULL default '0',
                    `expiration_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    PRIMARY KEY  (`comment_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`comment_time`,`internal_comment_id`),
                    KEY `idx1` (`object_id`)
                  ) ENGINE=InnoDB;";
    }

    if (!in_array('npc_configfiles', $tables)) {
        $sql[] = "CREATE TABLE `npc_configfiles` (
                    `configfile_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `configfile_type` smallint(6) NOT NULL default '0',
                    `configfile_path` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`configfile_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`configfile_type`,`configfile_path`)
                  ) ENGINE=InnoDB COMMENT='Configuration files';";
    }

    if (!in_array('npc_configfilevariables', $tables)) {
        $sql[] = "CREATE TABLE `npc_configfilevariables` (
                    `configfilevariable_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `configfile_id` int(11) NOT NULL default '0',
                    `varname` varchar(64) NOT NULL default '',
                    `varvalue` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`configfilevariable_id`),
                    KEY `instance_id` (`instance_id`,`configfile_id`)
                  ) ENGINE=InnoDB COMMENT='Configuration file variables';";
    }

    if (!in_array('npc_conninfo', $tables)) {
        $sql[] = "CREATE TABLE `npc_conninfo` (
                    `conninfo_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `agent_name` varchar(32) NOT NULL default '',
                    `agent_version` varchar(8) NOT NULL default '',
                    `disposition` varchar(16) NOT NULL default '',
                    `connect_source` varchar(16) NOT NULL default '',
                    `connect_type` varchar(16) NOT NULL default '',
                    `connect_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `disconnect_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `last_checkin_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `data_start_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `data_end_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `bytes_processed` int(11) NOT NULL default '0',
                    `lines_processed` int(11) NOT NULL default '0',
                    `entries_processed` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`conninfo_id`)
                  ) ENGINE=InnoDB COMMENT='NDO2DB daemon connection information';";
    }

    if (!in_array('npc_contact_addresses', $tables)) {
        $sql[] = "CREATE TABLE `npc_contact_addresses` (
                    `contact_address_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `contact_id` int(11) NOT NULL default '0',
                    `address_number` smallint(6) NOT NULL default '0',
                    `address` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`contact_address_id`),
                    UNIQUE KEY `contact_id` (`contact_id`,`address_number`)
                  ) ENGINE=InnoDB COMMENT='Contact addresses';";
    }

    if (!in_array('npc_contact_notificationcommands', $tables)) {
        $sql[] = "CREATE TABLE `npc_contact_notificationcommands` (
                    `contact_notificationcommand_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `contact_id` int(11) NOT NULL default '0',
                    `notification_type` smallint(6) NOT NULL default '0',
                    `command_object_id` int(11) NOT NULL default '0',
                    `command_args` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`contact_notificationcommand_id`),
                    UNIQUE KEY `contact_id` (`contact_id`,`notification_type`,`command_object_id`,`command_args`)
                  ) ENGINE=InnoDB COMMENT='Contact host and service notification commands';";
    }

    if (!in_array('npc_contactgroup_members', $tables)) {
        $sql[] = "CREATE TABLE `npc_contactgroup_members` (
                    `contactgroup_member_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `contactgroup_id` int(11) NOT NULL default '0',
                    `contact_object_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`contactgroup_member_id`),
                    UNIQUE KEY `instance_id` (`contactgroup_id`,`contact_object_id`)
                  ) ENGINE=InnoDB COMMENT='Contactgroup members';";
    }

    if (!in_array('npc_contactgroups', $tables)) {
        $sql[] = "CREATE TABLE `npc_contactgroups` (
                    `contactgroup_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `config_type` smallint(6) NOT NULL default '0',
                    `contactgroup_object_id` int(11) NOT NULL default '0',
                    `alias` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`contactgroup_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`contactgroup_object_id`)
                  ) ENGINE=InnoDB COMMENT='Contactgroup definitions';";
    }

    if (!in_array('npc_contactnotificationmethods', $tables)) {
        $sql[] = "CREATE TABLE `npc_contactnotificationmethods` (
                    `contactnotificationmethod_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `contactnotification_id` int(11) NOT NULL default '0',
                    `start_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `start_time_usec` int(11) NOT NULL default '0',
                    `end_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `end_time_usec` int(11) NOT NULL default '0',
                    `command_object_id` int(11) NOT NULL default '0',
                    `command_args` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`contactnotificationmethod_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`contactnotification_id`,`start_time`,`start_time_usec`)
                  ) ENGINE=InnoDB COMMENT='Historical record of contact notification methods';";
    }

    if (!in_array('npc_contactnotifications', $tables)) {
        $sql[] = "CREATE TABLE `npc_contactnotifications` (
                    `contactnotification_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `notification_id` int(11) NOT NULL default '0',
                    `contact_object_id` int(11) NOT NULL default '0',
                    `start_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `start_time_usec` int(11) NOT NULL default '0',
                    `end_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `end_time_usec` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`contactnotification_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`contact_object_id`,`start_time`,`start_time_usec`)
                  ) ENGINE=InnoDB COMMENT='Historical record of contact notifications';";
    }

    if (!in_array('npc_contacts', $tables)) {
        $sql[] = "CREATE TABLE `npc_contacts` (
                    `contact_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `config_type` smallint(6) NOT NULL default '0',
                    `contact_object_id` int(11) NOT NULL default '0',
                    `alias` varchar(64) NOT NULL default '',
                    `email_address` varchar(255) NOT NULL default '',
                    `pager_address` varchar(64) NOT NULL default '',
                    `host_timeperiod_object_id` int(11) NOT NULL default '0',
                    `service_timeperiod_object_id` int(11) NOT NULL default '0',
                    `host_notifications_enabled` smallint(6) NOT NULL default '0',
                    `service_notifications_enabled` smallint(6) NOT NULL default '0',
                    `can_submit_commands` smallint(6) NOT NULL default '0',
                    `notify_service_recovery` smallint(6) NOT NULL default '0',
                    `notify_service_warning` smallint(6) NOT NULL default '0',
                    `notify_service_unknown` smallint(6) NOT NULL default '0',
                    `notify_service_critical` smallint(6) NOT NULL default '0',
                    `notify_service_flapping` smallint(6) NOT NULL default '0',
                    `notify_service_downtime` smallint(6) NOT NULL default '0',
                    `notify_host_recovery` smallint(6) NOT NULL default '0',
                    `notify_host_down` smallint(6) NOT NULL default '0',
                    `notify_host_unreachable` smallint(6) NOT NULL default '0',
                    `notify_host_flapping` smallint(6) NOT NULL default '0',
                    `notify_host_downtime` smallint(6) NOT NULL default '0',
                    PRIMARY KEY  (`contact_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`contact_object_id`)
                  ) ENGINE=InnoDB COMMENT='Contact definitions';";
    }

    if (!in_array('npc_contactstatus', $tables)) {
        $sql[] = "CREATE TABLE `npc_contactstatus` (
                    `contactstatus_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `contact_object_id` int(11) NOT NULL default '0',
                    `status_update_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `host_notifications_enabled` smallint(6) NOT NULL default '0',
                    `service_notifications_enabled` smallint(6) NOT NULL default '0',
                    `last_host_notification` datetime NOT NULL default '0000-00-00 00:00:00',
                    `last_service_notification` datetime NOT NULL default '0000-00-00 00:00:00',
                    `modified_attributes` int(11) NOT NULL default '0',
                    `modified_host_attributes` int(11) NOT NULL default '0',
                    `modified_service_attributes` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`contactstatus_id`),
                    UNIQUE KEY `contact_object_id` (`contact_object_id`)
                  ) ENGINE=InnoDB COMMENT='Contact status';";
    }

    if (!in_array('npc_customvariables', $tables)) {
        $sql[] = "CREATE TABLE `npc_customvariables` (
                    `customvariable_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `object_id` int(11) NOT NULL default '0',
                    `config_type` smallint(6) NOT NULL default '0',
                    `has_been_modified` smallint(6) NOT NULL default '0',
                    `varname` varchar(255) NOT NULL default '',
                    `varvalue` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`customvariable_id`),
                    UNIQUE KEY `object_id_2` (`object_id`,`config_type`,`varname`),
                    KEY `varname` (`varname`)
                  ) ENGINE=InnoDB COMMENT='Custom variables';";
    }

    if (!in_array('npc_customvariablestatus', $tables)) {
        $sql[] = "CREATE TABLE `npc_customvariablestatus` (
                    `customvariablestatus_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `object_id` int(11) NOT NULL default '0',
                    `status_update_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `has_been_modified` smallint(6) NOT NULL default '0',
                    `varname` varchar(255) NOT NULL default '',
                    `varvalue` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`customvariablestatus_id`),
                    UNIQUE KEY `object_id_2` (`object_id`,`varname`),
                    KEY `varname` (`varname`)
                  ) ENGINE=InnoDB COMMENT='Custom variable status information';";
    }

    if (!in_array('npc_dbversion', $tables)) {
        $sql[] = "CREATE TABLE `npc_dbversion` (
                    `name` varchar(10) NOT NULL default '',
                    `version` varchar(10) NOT NULL default ''
                  ) ENGINE=InnoDB;";
    }

    if (!in_array('npc_downtimehistory', $tables)) {
        $sql[] = "CREATE TABLE `npc_downtimehistory` (
                    `downtimehistory_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `downtime_type` smallint(6) NOT NULL default '0',
                    `object_id` int(11) NOT NULL default '0',
                    `entry_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `author_name` varchar(64) NOT NULL default '',
                    `comment_data` varchar(255) NOT NULL default '',
                    `internal_downtime_id` int(11) NOT NULL default '0',
                    `triggered_by_id` int(11) NOT NULL default '0',
                    `is_fixed` smallint(6) NOT NULL default '0',
                    `duration` smallint(6) NOT NULL default '0',
                    `scheduled_start_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `scheduled_end_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `was_started` smallint(6) NOT NULL default '0',
                    `actual_start_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `actual_start_time_usec` int(11) NOT NULL default '0',
                    `actual_end_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `actual_end_time_usec` int(11) NOT NULL default '0',
                    `was_cancelled` smallint(6) NOT NULL default '0',
                    PRIMARY KEY  (`downtimehistory_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`object_id`,`entry_time`,`internal_downtime_id`)
                  ) ENGINE=InnoDB COMMENT='Historical scheduled host and service downtime';";
    }

    if (!in_array('npc_eventhandlers', $tables)) {
        $sql[] = "CREATE TABLE `npc_eventhandlers` (
                    `eventhandler_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `eventhandler_type` smallint(6) NOT NULL default '0',
                    `object_id` int(11) NOT NULL default '0',
                    `state` smallint(6) NOT NULL default '0',
                    `state_type` smallint(6) NOT NULL default '0',
                    `start_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `start_time_usec` int(11) NOT NULL default '0',
                    `end_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `end_time_usec` int(11) NOT NULL default '0',
                    `command_object_id` int(11) NOT NULL default '0',
                    `command_args` varchar(255) NOT NULL default '',
                    `command_line` varchar(255) NOT NULL default '',
                    `timeout` smallint(6) NOT NULL default '0',
                    `early_timeout` smallint(6) NOT NULL default '0',
                    `execution_time` double NOT NULL default '0',
                    `return_code` smallint(6) NOT NULL default '0',
                    `output` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`eventhandler_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`object_id`,`start_time`,`start_time_usec`)
                  ) ENGINE=InnoDB COMMENT='Historical host and service event handlers';";
    }

    if (!in_array('npc_externalcommands', $tables)) {
        $sql[] = "CREATE TABLE `npc_externalcommands` (
                    `externalcommand_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `entry_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `command_type` smallint(6) NOT NULL default '0',
                    `command_name` varchar(128) NOT NULL default '',
                    `command_args` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`externalcommand_id`)
                  ) ENGINE=InnoDB COMMENT='Historical record of processed external commands';";
    }

    if (!in_array('npc_flappinghistory', $tables)) {
        $sql[] = "CREATE TABLE `npc_flappinghistory` (
                    `flappinghistory_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `event_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `event_time_usec` int(11) NOT NULL default '0',
                    `event_type` smallint(6) NOT NULL default '0',
                    `reason_type` smallint(6) NOT NULL default '0',
                    `flapping_type` smallint(6) NOT NULL default '0',
                    `object_id` int(11) NOT NULL default '0',
                    `percent_state_change` double NOT NULL default '0',
                    `low_threshold` double NOT NULL default '0',
                    `high_threshold` double NOT NULL default '0',
                    `comment_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `internal_comment_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`flappinghistory_id`)
                  ) ENGINE=InnoDB COMMENT='Current and historical record of host and service flapping';";
    }

    if (!in_array('npc_host_contactgroups', $tables)) {
        $sql[] = "CREATE TABLE `npc_host_contactgroups` (
                    `host_contactgroup_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `host_id` int(11) NOT NULL default '0',
                    `contactgroup_object_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`host_contactgroup_id`),
                    UNIQUE KEY `instance_id` (`host_id`,`contactgroup_object_id`)
                  ) ENGINE=InnoDB COMMENT='Host contact groups';";
    }

    if (!in_array('npc_host_contacts', $tables)) {
        $sql[] = "CREATE TABLE `npc_host_contacts` (
                    `host_contact_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `host_id` int(11) NOT NULL default '0',
                    `contact_object_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`host_contact_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`host_id`,`contact_object_id`)
                  ) ENGINE=InnoDB;";
    }

    if (!in_array('npc_host_parenthosts', $tables)) {
        $sql[] = "CREATE TABLE `npc_host_parenthosts` (
                    `host_parenthost_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `host_id` int(11) NOT NULL default '0',
                    `parent_host_object_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`host_parenthost_id`),
                    UNIQUE KEY `instance_id` (`host_id`,`parent_host_object_id`)
                  ) ENGINE=InnoDB COMMENT='Parent hosts';";
    }

    if (!in_array('npc_hostchecks', $tables)) {
        $sql[] = "CREATE TABLE `npc_hostchecks` (
                    `hostcheck_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `host_object_id` int(11) NOT NULL default '0',
                    `check_type` smallint(6) NOT NULL default '0',
                    `is_raw_check` smallint(6) NOT NULL default '0',
                    `current_check_attempt` smallint(6) NOT NULL default '0',
                    `max_check_attempts` smallint(6) NOT NULL default '0',
                    `state` smallint(6) NOT NULL default '0',
                    `state_type` smallint(6) NOT NULL default '0',
                    `start_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `start_time_usec` int(11) NOT NULL default '0',
                    `end_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `end_time_usec` int(11) NOT NULL default '0',
                    `command_object_id` int(11) NOT NULL default '0',
                    `command_args` varchar(255) NOT NULL default '',
                    `command_line` varchar(255) NOT NULL default '',
                    `timeout` smallint(6) NOT NULL default '0',
                    `early_timeout` smallint(6) NOT NULL default '0',
                    `execution_time` double NOT NULL default '0',
                    `latency` double NOT NULL default '0',
                    `return_code` smallint(6) NOT NULL default '0',
                    `output` varchar(255) NOT NULL default '',
                    `perfdata` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`hostcheck_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`host_object_id`,`start_time`,`start_time_usec`)
                  ) ENGINE=InnoDB COMMENT='Historical host checks';";
    }

    if (!in_array('npc_hostdependencies', $tables)) {
        $sql[] = "CREATE TABLE `npc_hostdependencies` (
                    `hostdependency_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `config_type` smallint(6) NOT NULL default '0',
                    `host_object_id` int(11) NOT NULL default '0',
                    `dependent_host_object_id` int(11) NOT NULL default '0',
                    `dependency_type` smallint(6) NOT NULL default '0',
                    `inherits_parent` smallint(6) NOT NULL default '0',
                    `timeperiod_object_id` int(11) NOT NULL default '0',
                    `fail_on_up` smallint(6) NOT NULL default '0',
                    `fail_on_down` smallint(6) NOT NULL default '0',
                    `fail_on_unreachable` smallint(6) NOT NULL default '0',
                    PRIMARY KEY  (`hostdependency_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`host_object_id`,`dependent_host_object_id`,`dependency_type`,`inherits_parent`,`fail_on_up`,`fail_on_down`,`fail_on_unreachable`)
                  ) ENGINE=InnoDB COMMENT='Host dependency definitions';";
    }

    if (!in_array('npc_hostescalation_contactgroups', $tables)) {
        $sql[] = "CREATE TABLE `npc_hostescalation_contactgroups` (
                    `hostescalation_contactgroup_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `hostescalation_id` int(11) NOT NULL default '0',
                    `contactgroup_object_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`hostescalation_contactgroup_id`),
                    UNIQUE KEY `instance_id` (`hostescalation_id`,`contactgroup_object_id`)
                  ) ENGINE=InnoDB COMMENT='Host escalation contact groups';";
    }

    if (!in_array('npc_hostescalation_contacts', $tables)) {
        $sql[] = "CREATE TABLE `npc_hostescalation_contacts` (
                    `hostescalation_contact_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `hostescalation_id` int(11) NOT NULL default '0',
                    `contact_object_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`hostescalation_contact_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`hostescalation_id`,`contact_object_id`)
                  ) ENGINE=InnoDB;";
    }

    if (!in_array('npc_hostescalations', $tables)) {
        $sql[] = "CREATE TABLE `npc_hostescalations` (
                    `hostescalation_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `config_type` smallint(6) NOT NULL default '0',
                    `host_object_id` int(11) NOT NULL default '0',
                    `timeperiod_object_id` int(11) NOT NULL default '0',
                    `first_notification` smallint(6) NOT NULL default '0',
                    `last_notification` smallint(6) NOT NULL default '0',
                    `notification_interval` double NOT NULL default '0',
                    `escalate_on_recovery` smallint(6) NOT NULL default '0',
                    `escalate_on_down` smallint(6) NOT NULL default '0',
                    `escalate_on_unreachable` smallint(6) NOT NULL default '0',
                    PRIMARY KEY  (`hostescalation_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`host_object_id`,`timeperiod_object_id`,`first_notification`,`last_notification`)
                  ) ENGINE=InnoDB COMMENT='Host escalation definitions';";
    }

    if (!in_array('npc_hostgroup_members', $tables)) {
        $sql[] = "CREATE TABLE `npc_hostgroup_members` (
                    `hostgroup_member_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `hostgroup_id` int(11) NOT NULL default '0',
                    `host_object_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`hostgroup_member_id`),
                    UNIQUE KEY `instance_id` (`hostgroup_id`,`host_object_id`)
                  ) ENGINE=InnoDB COMMENT='Hostgroup members';";
    }

    if (!in_array('npc_hostgroups', $tables)) {
        $sql[] = "CREATE TABLE `npc_hostgroups` (
                    `hostgroup_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `config_type` smallint(6) NOT NULL default '0',
                    `hostgroup_object_id` int(11) NOT NULL default '0',
                    `alias` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`hostgroup_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`hostgroup_object_id`)
                  ) ENGINE=InnoDB COMMENT='Hostgroup definitions';";
    }

    if (!in_array('npc_hosts', $tables)) {
        $sql[] = "CREATE TABLE `npc_hosts` (
                    `host_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `config_type` smallint(6) NOT NULL default '0',
                    `host_object_id` int(11) NOT NULL default '0',
                    `alias` varchar(64) NOT NULL default '',
                    `display_name` varchar(64) NOT NULL default '',
                    `address` varchar(128) NOT NULL default '',
                    `check_command_object_id` int(11) NOT NULL default '0',
                    `check_command_args` varchar(255) NOT NULL default '',
                    `eventhandler_command_object_id` int(11) NOT NULL default '0',
                    `eventhandler_command_args` varchar(255) NOT NULL default '',
                    `notification_timeperiod_object_id` int(11) NOT NULL default '0',
                    `check_timeperiod_object_id` int(11) NOT NULL default '0',
                    `failure_prediction_options` varchar(64) NOT NULL default '',
                    `check_interval` double NOT NULL default '0',
                    `retry_interval` double NOT NULL default '0',
                    `max_check_attempts` smallint(6) NOT NULL default '0',
                    `first_notification_delay` double NOT NULL default '0',
                    `notification_interval` double NOT NULL default '0',
                    `notify_on_down` smallint(6) NOT NULL default '0',
                    `notify_on_unreachable` smallint(6) NOT NULL default '0',
                    `notify_on_recovery` smallint(6) NOT NULL default '0',
                    `notify_on_flapping` smallint(6) NOT NULL default '0',
                    `notify_on_downtime` smallint(6) NOT NULL default '0',
                    `stalk_on_up` smallint(6) NOT NULL default '0',
                    `stalk_on_down` smallint(6) NOT NULL default '0',
                    `stalk_on_unreachable` smallint(6) NOT NULL default '0',
                    `flap_detection_enabled` smallint(6) NOT NULL default '0',
                    `flap_detection_on_up` smallint(6) NOT NULL default '0',
                    `flap_detection_on_down` smallint(6) NOT NULL default '0',
                    `flap_detection_on_unreachable` smallint(6) NOT NULL default '0',
                    `low_flap_threshold` double NOT NULL default '0',
                    `high_flap_threshold` double NOT NULL default '0',
                    `process_performance_data` smallint(6) NOT NULL default '0',
                    `freshness_checks_enabled` smallint(6) NOT NULL default '0',
                    `freshness_threshold` smallint(6) NOT NULL default '0',
                    `passive_checks_enabled` smallint(6) NOT NULL default '0',
                    `event_handler_enabled` smallint(6) NOT NULL default '0',
                    `active_checks_enabled` smallint(6) NOT NULL default '0',
                    `retain_status_information` smallint(6) NOT NULL default '0',
                    `retain_nonstatus_information` smallint(6) NOT NULL default '0',
                    `notifications_enabled` smallint(6) NOT NULL default '0',
                    `obsess_over_host` smallint(6) NOT NULL default '0',
                    `failure_prediction_enabled` smallint(6) NOT NULL default '0',
                    `notes` varchar(255) NOT NULL default '',
                    `notes_url` varchar(255) NOT NULL default '',
                    `action_url` varchar(255) NOT NULL default '',
                    `icon_image` varchar(255) NOT NULL default '',
                    `icon_image_alt` varchar(255) NOT NULL default '',
                    `vrml_image` varchar(255) NOT NULL default '',
                    `statusmap_image` varchar(255) NOT NULL default '',
                    `have_2d_coords` smallint(6) NOT NULL default '0',
                    `x_2d` smallint(6) NOT NULL default '0',
                    `y_2d` smallint(6) NOT NULL default '0',
                    `have_3d_coords` smallint(6) NOT NULL default '0',
                    `x_3d` double NOT NULL default '0',
                    `y_3d` double NOT NULL default '0',
                    `z_3d` double NOT NULL default '0',
                    PRIMARY KEY  (`host_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`host_object_id`),
                    KEY `idx1` (`host_object_id`),
                    KEY `idx2` (`config_type`)
                  ) ENGINE=InnoDB COMMENT='Host definitions';";
    }

    if (!in_array('npc_hoststatus', $tables)) {
        $sql[] = "CREATE TABLE `npc_hoststatus` (
                    `hoststatus_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `host_object_id` int(11) NOT NULL default '0',
                    `status_update_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `output` varchar(255) NOT NULL default '',
                    `perfdata` varchar(255) NOT NULL default '',
                    `current_state` smallint(6) NOT NULL default '0',
                    `has_been_checked` smallint(6) NOT NULL default '0',
                    `should_be_scheduled` smallint(6) NOT NULL default '0',
                    `current_check_attempt` smallint(6) NOT NULL default '0',
                    `max_check_attempts` smallint(6) NOT NULL default '0',
                    `last_check` datetime NOT NULL default '0000-00-00 00:00:00',
                    `next_check` datetime NOT NULL default '0000-00-00 00:00:00',
                    `check_type` smallint(6) NOT NULL default '0',
                    `last_state_change` datetime NOT NULL default '0000-00-00 00:00:00',
                    `last_hard_state_change` datetime NOT NULL default '0000-00-00 00:00:00',
                    `last_hard_state` smallint(6) NOT NULL default '0',
                    `last_time_up` datetime NOT NULL default '0000-00-00 00:00:00',
                    `last_time_down` datetime NOT NULL default '0000-00-00 00:00:00',
                    `last_time_unreachable` datetime NOT NULL default '0000-00-00 00:00:00',
                    `state_type` smallint(6) NOT NULL default '0',
                    `last_notification` datetime NOT NULL default '0000-00-00 00:00:00',
                    `next_notification` datetime NOT NULL default '0000-00-00 00:00:00',
                    `no_more_notifications` smallint(6) NOT NULL default '0',
                    `notifications_enabled` smallint(6) NOT NULL default '0',
                    `problem_has_been_acknowledged` smallint(6) NOT NULL default '0',
                    `acknowledgement_type` smallint(6) NOT NULL default '0',
                    `current_notification_number` smallint(6) NOT NULL default '0',
                    `passive_checks_enabled` smallint(6) NOT NULL default '0',
                    `active_checks_enabled` smallint(6) NOT NULL default '0',
                    `event_handler_enabled` smallint(6) NOT NULL default '0',
                    `flap_detection_enabled` smallint(6) NOT NULL default '0',
                    `is_flapping` smallint(6) NOT NULL default '0',
                    `percent_state_change` double NOT NULL default '0',
                    `latency` double NOT NULL default '0',
                    `execution_time` double NOT NULL default '0',
                    `scheduled_downtime_depth` smallint(6) NOT NULL default '0',
                    `failure_prediction_enabled` smallint(6) NOT NULL default '0',
                    `process_performance_data` smallint(6) NOT NULL default '0',
                    `obsess_over_host` smallint(6) NOT NULL default '0',
                    `modified_host_attributes` int(11) NOT NULL default '0',
                    `event_handler` varchar(255) NOT NULL default '',
                    `check_command` varchar(255) NOT NULL default '',
                    `normal_check_interval` double NOT NULL default '0',
                    `retry_check_interval` double NOT NULL default '0',
                    `check_timeperiod_object_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`hoststatus_id`),
                    UNIQUE KEY `object_id` (`host_object_id`),
                    KEY `idx1` (`current_state`)
                  ) ENGINE=InnoDB COMMENT='Current host status information';";
    }

    if (!in_array('npc_instances', $tables)) {
        $sql[] = "CREATE TABLE `npc_instances` (
                    `instance_id` smallint(6) NOT NULL auto_increment,
                    `instance_name` varchar(64) NOT NULL default '',
                    `instance_description` varchar(128) NOT NULL default '',
                    PRIMARY KEY  (`instance_id`)
                  ) ENGINE=InnoDB COMMENT='Location names of various Nagios installations';";
    }

    if (!in_array('npc_logentries', $tables)) {
        $sql[] = "CREATE TABLE `npc_logentries` (
                    `logentry_id` int(11) NOT NULL auto_increment,
                    `instance_id` int(11) NOT NULL default '0',
                    `logentry_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `entry_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `entry_time_usec` int(11) NOT NULL default '0',
                    `logentry_type` int(11) NOT NULL default '0',
                    `logentry_data` varchar(255) NOT NULL default '',
                    `realtime_data` smallint(6) NOT NULL default '0',
                    `inferred_data_extracted` smallint(6) NOT NULL default '0',
                    PRIMARY KEY  (`logentry_id`),
                    KEY `idx1` (`entry_time`,`entry_time_usec`)
                  ) ENGINE=InnoDB COMMENT='Historical record of log entries';";
    }

    if (!in_array('npc_notifications', $tables)) {
        $sql[] = "CREATE TABLE `npc_notifications` (
                    `notification_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `notification_type` smallint(6) NOT NULL default '0',
                    `notification_reason` smallint(6) NOT NULL default '0',
                    `object_id` int(11) NOT NULL default '0',
                    `start_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `start_time_usec` int(11) NOT NULL default '0',
                    `end_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `end_time_usec` int(11) NOT NULL default '0',
                    `state` smallint(6) NOT NULL default '0',
                    `output` varchar(255) NOT NULL default '',
                    `escalated` smallint(6) NOT NULL default '0',
                    `contacts_notified` smallint(6) NOT NULL default '0',
                    PRIMARY KEY  (`notification_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`object_id`,`start_time`,`start_time_usec`)
                  ) ENGINE=InnoDB COMMENT='Historical record of host and service notifications';";
    }

    if (!in_array('npc_objects', $tables)) {
        $sql[] = "CREATE TABLE `npc_objects` (
                    `object_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `objecttype_id` smallint(6) NOT NULL default '0',
                    `name1` varchar(128) NOT NULL default '',
                    `name2` varchar(128) default NULL,
                    `is_active` smallint(6) NOT NULL default '0',
                    PRIMARY KEY  (`object_id`),
                    KEY `objecttype_id` (`objecttype_id`,`name1`,`name2`),
                    KEY `name_idx` (`name1`,`name2`)
                  ) ENGINE=InnoDB COMMENT='Current and historical objects of all kinds';";
    }

    if (!in_array('npc_processevents', $tables)) {
        $sql[] = "CREATE TABLE `npc_processevents` (
                    `processevent_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `event_type` smallint(6) NOT NULL default '0',
                    `event_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `event_time_usec` int(11) NOT NULL default '0',
                    `process_id` int(11) NOT NULL default '0',
                    `program_name` varchar(16) NOT NULL default '',
                    `program_version` varchar(20) NOT NULL default '',
                    `program_date` varchar(10) NOT NULL default '',
                    PRIMARY KEY  (`processevent_id`)
                  ) ENGINE=InnoDB COMMENT='Historical Nagios process events';";
    }

    if (!in_array('npc_programstatus', $tables)) {
        $sql[] = "CREATE TABLE `npc_programstatus` (
                    `programstatus_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `status_update_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `program_start_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `program_end_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `is_currently_running` smallint(6) NOT NULL default '0',
                    `process_id` int(11) NOT NULL default '0',
                    `daemon_mode` smallint(6) NOT NULL default '0',
                    `last_command_check` datetime NOT NULL default '0000-00-00 00:00:00',
                    `last_log_rotation` datetime NOT NULL default '0000-00-00 00:00:00',
                    `notifications_enabled` smallint(6) NOT NULL default '0',
                    `active_service_checks_enabled` smallint(6) NOT NULL default '0',
                    `passive_service_checks_enabled` smallint(6) NOT NULL default '0',
                    `active_host_checks_enabled` smallint(6) NOT NULL default '0',
                    `passive_host_checks_enabled` smallint(6) NOT NULL default '0',
                    `event_handlers_enabled` smallint(6) NOT NULL default '0',
                    `flap_detection_enabled` smallint(6) NOT NULL default '0',
                    `failure_prediction_enabled` smallint(6) NOT NULL default '0',
                    `process_performance_data` smallint(6) NOT NULL default '0',
                    `obsess_over_hosts` smallint(6) NOT NULL default '0',
                    `obsess_over_services` smallint(6) NOT NULL default '0',
                    `modified_host_attributes` int(11) NOT NULL default '0',
                    `modified_service_attributes` int(11) NOT NULL default '0',
                    `global_host_event_handler` varchar(255) NOT NULL default '',
                    `global_service_event_handler` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`programstatus_id`),
                    UNIQUE KEY `instance_id` (`instance_id`)
                  ) ENGINE=InnoDB COMMENT='Current program status information';";
    }

    if (!in_array('npc_runtimevariables', $tables)) {
        $sql[] = "CREATE TABLE `npc_runtimevariables` (
                    `runtimevariable_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `varname` varchar(64) NOT NULL default '',
                    `varvalue` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`runtimevariable_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`varname`)
                  ) ENGINE=InnoDB COMMENT='Runtime variables from the Nagios daemon';";
    }

    if (!in_array('npc_scheduleddowntime', $tables)) {
        $sql[] = "CREATE TABLE `npc_scheduleddowntime` (
                    `scheduleddowntime_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `downtime_type` smallint(6) NOT NULL default '0',
                    `object_id` int(11) NOT NULL default '0',
                    `entry_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `author_name` varchar(64) NOT NULL default '',
                    `comment_data` varchar(255) NOT NULL default '',
                    `internal_downtime_id` int(11) NOT NULL default '0',
                    `triggered_by_id` int(11) NOT NULL default '0',
                    `is_fixed` smallint(6) NOT NULL default '0',
                    `duration` smallint(6) NOT NULL default '0',
                    `scheduled_start_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `scheduled_end_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `was_started` smallint(6) NOT NULL default '0',
                    `actual_start_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `actual_start_time_usec` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`scheduleddowntime_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`object_id`,`entry_time`,`internal_downtime_id`)
                  ) ENGINE=InnoDB COMMENT='Current scheduled host and service downtime';";
    }

    if (!in_array('npc_service_contactgroups', $tables)) {
        $sql[] = "CREATE TABLE `npc_service_contactgroups` (
                    `service_contactgroup_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `service_id` int(11) NOT NULL default '0',
                    `contactgroup_object_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`service_contactgroup_id`),
                    UNIQUE KEY `instance_id` (`service_id`,`contactgroup_object_id`)
                  ) ENGINE=InnoDB COMMENT='Service contact groups';";
    }

    if (!in_array('npc_service_contacts', $tables)) {
        $sql[] = "CREATE TABLE `npc_service_contacts` (
                    `service_contact_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `service_id` int(11) NOT NULL default '0',
                    `contact_object_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`service_contact_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`service_id`,`contact_object_id`)
                  ) ENGINE=InnoDB;";
    }

    if (!in_array('npc_servicechecks', $tables)) {
        $sql[] = "CREATE TABLE `npc_servicechecks` (
                    `servicecheck_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `service_object_id` int(11) NOT NULL default '0',
                    `check_type` smallint(6) NOT NULL default '0',
                    `current_check_attempt` smallint(6) NOT NULL default '0',
                    `max_check_attempts` smallint(6) NOT NULL default '0',
                    `state` smallint(6) NOT NULL default '0',
                    `state_type` smallint(6) NOT NULL default '0',
                    `start_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `start_time_usec` int(11) NOT NULL default '0',
                    `end_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `end_time_usec` int(11) NOT NULL default '0',
                    `command_object_id` int(11) NOT NULL default '0',
                    `command_args` varchar(255) NOT NULL default '',
                    `command_line` varchar(255) NOT NULL default '',
                    `timeout` smallint(6) NOT NULL default '0',
                    `early_timeout` smallint(6) NOT NULL default '0',
                    `execution_time` double NOT NULL default '0',
                    `latency` double NOT NULL default '0',
                    `return_code` smallint(6) NOT NULL default '0',
                    `output` varchar(255) NOT NULL default '',
                    `perfdata` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`servicecheck_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`service_object_id`,`start_time`,`start_time_usec`),
                    KEY `idx1` (`service_object_id`,`start_time`),
                    KEY `idx2` (`instance_id`,`start_time`)
                  ) ENGINE=InnoDB COMMENT='Historical service checks';";
    }

    if (!in_array('npc_servicedependencies', $tables)) {
        $sql[] = "CREATE TABLE `npc_servicedependencies` (
                    `servicedependency_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `config_type` smallint(6) NOT NULL default '0',
                    `service_object_id` int(11) NOT NULL default '0',
                    `dependent_service_object_id` int(11) NOT NULL default '0',
                    `dependency_type` smallint(6) NOT NULL default '0',
                    `inherits_parent` smallint(6) NOT NULL default '0',
                    `timeperiod_object_id` int(11) NOT NULL default '0',
                    `fail_on_ok` smallint(6) NOT NULL default '0',
                    `fail_on_warning` smallint(6) NOT NULL default '0',
                    `fail_on_unknown` smallint(6) NOT NULL default '0',
                    `fail_on_critical` smallint(6) NOT NULL default '0',
                    PRIMARY KEY  (`servicedependency_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`service_object_id`,`dependent_service_object_id`,`dependency_type`,`inherits_parent`,`fail_on_ok`,`fail_on_warning`,`fail_on_unknown`,`fail_on_critical`)
                  ) ENGINE=InnoDB COMMENT='Service dependency definitions';";
    }

    if (!in_array('npc_serviceescalation_contactgroups', $tables)) {
        $sql[] = "CREATE TABLE `npc_serviceescalation_contactgroups` (
                    `serviceescalation_contactgroup_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `serviceescalation_id` int(11) NOT NULL default '0',
                    `contactgroup_object_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`serviceescalation_contactgroup_id`),
                    UNIQUE KEY `instance_id` (`serviceescalation_id`,`contactgroup_object_id`)
                  ) ENGINE=InnoDB COMMENT='Service escalation contact groups';";
    }

    if (!in_array('npc_serviceescalation_contacts', $tables)) {
        $sql[] = "CREATE TABLE `npc_serviceescalation_contacts` (
                    `serviceescalation_contact_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `serviceescalation_id` int(11) NOT NULL default '0',
                    `contact_object_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`serviceescalation_contact_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`serviceescalation_id`,`contact_object_id`)
                  ) ENGINE=InnoDB;";
    }

    if (!in_array('npc_serviceescalations', $tables)) {
        $sql[] = "CREATE TABLE `npc_serviceescalations` (
                    `serviceescalation_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `config_type` smallint(6) NOT NULL default '0',
                    `service_object_id` int(11) NOT NULL default '0',
                    `timeperiod_object_id` int(11) NOT NULL default '0',
                    `first_notification` smallint(6) NOT NULL default '0',
                    `last_notification` smallint(6) NOT NULL default '0',
                    `notification_interval` double NOT NULL default '0',
                    `escalate_on_recovery` smallint(6) NOT NULL default '0',
                    `escalate_on_warning` smallint(6) NOT NULL default '0',
                    `escalate_on_unknown` smallint(6) NOT NULL default '0',
                    `escalate_on_critical` smallint(6) NOT NULL default '0',
                    PRIMARY KEY  (`serviceescalation_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`service_object_id`,`timeperiod_object_id`,`first_notification`,`last_notification`)
                  ) ENGINE=InnoDB COMMENT='Service escalation definitions';";
    }

    if (!in_array('npc_servicegroup_members', $tables)) {
        $sql[] = "CREATE TABLE `npc_servicegroup_members` (
                    `servicegroup_member_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `servicegroup_id` int(11) NOT NULL default '0',
                    `service_object_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`servicegroup_member_id`),
                    UNIQUE KEY `instance_id` (`servicegroup_id`,`service_object_id`)
                  ) ENGINE=InnoDB COMMENT='Servicegroup members';";
    }

    if (!in_array('npc_servicegroups', $tables)) {
        $sql[] = "CREATE TABLE `npc_servicegroups` (
                    `servicegroup_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `config_type` smallint(6) NOT NULL default '0',
                    `servicegroup_object_id` int(11) NOT NULL default '0',
                    `alias` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`servicegroup_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`servicegroup_object_id`)
                  ) ENGINE=InnoDB COMMENT='Servicegroup definitions';";
    }

    if (!in_array('npc_services', $tables)) {
        $sql[] = "CREATE TABLE `npc_services` (
                    `service_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `config_type` smallint(6) NOT NULL default '0',
                    `host_object_id` int(11) NOT NULL default '0',
                    `service_object_id` int(11) NOT NULL default '0',
                    `display_name` varchar(64) NOT NULL default '',
                    `check_command_object_id` int(11) NOT NULL default '0',
                    `check_command_args` varchar(255) NOT NULL default '',
                    `eventhandler_command_object_id` int(11) NOT NULL default '0',
                    `eventhandler_command_args` varchar(255) NOT NULL default '',
                    `notification_timeperiod_object_id` int(11) NOT NULL default '0',
                    `check_timeperiod_object_id` int(11) NOT NULL default '0',
                    `failure_prediction_options` varchar(64) NOT NULL default '',
                    `check_interval` double NOT NULL default '0',
                    `retry_interval` double NOT NULL default '0',
                    `max_check_attempts` smallint(6) NOT NULL default '0',
                    `first_notification_delay` double NOT NULL default '0',
                    `notification_interval` double NOT NULL default '0',
                    `notify_on_warning` smallint(6) NOT NULL default '0',
                    `notify_on_unknown` smallint(6) NOT NULL default '0',
                    `notify_on_critical` smallint(6) NOT NULL default '0',
                    `notify_on_recovery` smallint(6) NOT NULL default '0',
                    `notify_on_flapping` smallint(6) NOT NULL default '0',
                    `notify_on_downtime` smallint(6) NOT NULL default '0',
                    `stalk_on_ok` smallint(6) NOT NULL default '0',
                    `stalk_on_warning` smallint(6) NOT NULL default '0',
                    `stalk_on_unknown` smallint(6) NOT NULL default '0',
                    `stalk_on_critical` smallint(6) NOT NULL default '0',
                    `is_volatile` smallint(6) NOT NULL default '0',
                    `flap_detection_enabled` smallint(6) NOT NULL default '0',
                    `flap_detection_on_ok` smallint(6) NOT NULL default '0',
                    `flap_detection_on_warning` smallint(6) NOT NULL default '0',
                    `flap_detection_on_unknown` smallint(6) NOT NULL default '0',
                    `flap_detection_on_critical` smallint(6) NOT NULL default '0',
                    `low_flap_threshold` double NOT NULL default '0',
                    `high_flap_threshold` double NOT NULL default '0',
                    `process_performance_data` smallint(6) NOT NULL default '0',
                    `freshness_checks_enabled` smallint(6) NOT NULL default '0',
                    `freshness_threshold` smallint(6) NOT NULL default '0',
                    `passive_checks_enabled` smallint(6) NOT NULL default '0',
                    `event_handler_enabled` smallint(6) NOT NULL default '0',
                    `active_checks_enabled` smallint(6) NOT NULL default '0',
                    `retain_status_information` smallint(6) NOT NULL default '0',
                    `retain_nonstatus_information` smallint(6) NOT NULL default '0',
                    `notifications_enabled` smallint(6) NOT NULL default '0',
                    `obsess_over_service` smallint(6) NOT NULL default '0',
                    `failure_prediction_enabled` smallint(6) NOT NULL default '0',
                    `notes` varchar(255) NOT NULL default '',
                    `notes_url` varchar(255) NOT NULL default '',
                    `action_url` varchar(255) NOT NULL default '',
                    `icon_image` varchar(255) NOT NULL default '',
                    `icon_image_alt` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`service_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`service_object_id`),
                    KEY `idx1` (`config_type`),
                    KEY `idx2` (`host_object_id`),
                    KEY `idx3` (`service_object_id`)
                  ) ENGINE=InnoDB COMMENT='Service definitions';";
    }

    if (!in_array('npc_servicestatus', $tables)) {
        $sql[] = "CREATE TABLE `npc_servicestatus` (
                    `servicestatus_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `service_object_id` int(11) NOT NULL default '0',
                    `status_update_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `output` varchar(255) NOT NULL default '',
                    `perfdata` varchar(255) NOT NULL default '',
                    `current_state` smallint(6) NOT NULL default '0',
                    `has_been_checked` smallint(6) NOT NULL default '0',
                    `should_be_scheduled` smallint(6) NOT NULL default '0',
                    `current_check_attempt` smallint(6) NOT NULL default '0',
                    `max_check_attempts` smallint(6) NOT NULL default '0',
                    `last_check` datetime NOT NULL default '0000-00-00 00:00:00',
                    `next_check` datetime NOT NULL default '0000-00-00 00:00:00',
                    `check_type` smallint(6) NOT NULL default '0',
                    `last_state_change` datetime NOT NULL default '0000-00-00 00:00:00',
                    `last_hard_state_change` datetime NOT NULL default '0000-00-00 00:00:00',
                    `last_hard_state` smallint(6) NOT NULL default '0',
                    `last_time_ok` datetime NOT NULL default '0000-00-00 00:00:00',
                    `last_time_warning` datetime NOT NULL default '0000-00-00 00:00:00',
                    `last_time_unknown` datetime NOT NULL default '0000-00-00 00:00:00',
                    `last_time_critical` datetime NOT NULL default '0000-00-00 00:00:00',
                    `state_type` smallint(6) NOT NULL default '0',
                    `last_notification` datetime NOT NULL default '0000-00-00 00:00:00',
                    `next_notification` datetime NOT NULL default '0000-00-00 00:00:00',
                    `no_more_notifications` smallint(6) NOT NULL default '0',
                    `notifications_enabled` smallint(6) NOT NULL default '0',
                    `problem_has_been_acknowledged` smallint(6) NOT NULL default '0',
                    `acknowledgement_type` smallint(6) NOT NULL default '0',
                    `current_notification_number` smallint(6) NOT NULL default '0',
                    `passive_checks_enabled` smallint(6) NOT NULL default '0',
                    `active_checks_enabled` smallint(6) NOT NULL default '0',
                    `event_handler_enabled` smallint(6) NOT NULL default '0',
                    `flap_detection_enabled` smallint(6) NOT NULL default '0',
                    `is_flapping` smallint(6) NOT NULL default '0',
                    `percent_state_change` double NOT NULL default '0',
                    `latency` double NOT NULL default '0',
                    `execution_time` double NOT NULL default '0',
                    `scheduled_downtime_depth` smallint(6) NOT NULL default '0',
                    `failure_prediction_enabled` smallint(6) NOT NULL default '0',
                    `process_performance_data` smallint(6) NOT NULL default '0',
                    `obsess_over_service` smallint(6) NOT NULL default '0',
                    `modified_service_attributes` int(11) NOT NULL default '0',
                    `event_handler` varchar(255) NOT NULL default '',
                    `check_command` varchar(255) NOT NULL default '',
                    `normal_check_interval` double NOT NULL default '0',
                    `retry_check_interval` double NOT NULL default '0',
                    `check_timeperiod_object_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`servicestatus_id`),
                    UNIQUE KEY `object_id` (`service_object_id`),
                    KEY `idx1` (`current_state`)
                  ) ENGINE=InnoDB COMMENT='Current service status information';";
    }

    if (!in_array('npc_statehistory', $tables)) {
        $sql[] = "CREATE TABLE `npc_statehistory` (
                    `statehistory_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `state_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `state_time_usec` int(11) NOT NULL default '0',
                    `object_id` int(11) NOT NULL default '0',
                    `state_change` smallint(6) NOT NULL default '0',
                    `state` smallint(6) NOT NULL default '0',
                    `state_type` smallint(6) NOT NULL default '0',
                    `current_check_attempt` smallint(6) NOT NULL default '0',
                    `max_check_attempts` smallint(6) NOT NULL default '0',
                    `last_state` smallint(6) NOT NULL default '-1',
                    `last_hard_state` smallint(6) NOT NULL default '-1',
                    `output` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`statehistory_id`)
                  ) ENGINE=InnoDB COMMENT='Historical host and service state changes';";
    }

    if (!in_array('npc_systemcommands', $tables)) {
        $sql[] = "CREATE TABLE `npc_systemcommands` (
                    `systemcommand_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `start_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `start_time_usec` int(11) NOT NULL default '0',
                    `end_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `end_time_usec` int(11) NOT NULL default '0',
                    `command_line` varchar(255) NOT NULL default '',
                    `timeout` smallint(6) NOT NULL default '0',
                    `early_timeout` smallint(6) NOT NULL default '0',
                    `execution_time` double NOT NULL default '0',
                    `return_code` smallint(6) NOT NULL default '0',
                    `output` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`systemcommand_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`start_time`,`start_time_usec`)
                  ) ENGINE=InnoDB COMMENT='Historical system commands that are executed';";
    }

    if (!in_array('npc_timedeventqueue', $tables)) {
        $sql[] = "CREATE TABLE `npc_timedeventqueue` (
                    `timedeventqueue_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `event_type` smallint(6) NOT NULL default '0',
                    `queued_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `queued_time_usec` int(11) NOT NULL default '0',
                    `scheduled_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `recurring_event` smallint(6) NOT NULL default '0',
                    `object_id` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`timedeventqueue_id`)
                  ) ENGINE=InnoDB COMMENT='Current Nagios event queue';";
    }

    if (!in_array('npc_timedevents', $tables)) {
        $sql[] = "CREATE TABLE `npc_timedevents` (
                    `timedevent_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `event_type` smallint(6) NOT NULL default '0',
                    `queued_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `queued_time_usec` int(11) NOT NULL default '0',
                    `event_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `event_time_usec` int(11) NOT NULL default '0',
                    `scheduled_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `recurring_event` smallint(6) NOT NULL default '0',
                    `object_id` int(11) NOT NULL default '0',
                    `deletion_time` datetime NOT NULL default '0000-00-00 00:00:00',
                    `deletion_time_usec` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`timedevent_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`event_type`,`scheduled_time`,`object_id`)
                  ) ENGINE=InnoDB COMMENT='Historical events from the Nagios event queue';";
    }

    if (!in_array('npc_timeperiod_timeranges', $tables)) {
        $sql[] = "CREATE TABLE `npc_timeperiod_timeranges` (
                    `timeperiod_timerange_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `timeperiod_id` int(11) NOT NULL default '0',
                    `day` smallint(6) NOT NULL default '0',
                    `start_sec` int(11) NOT NULL default '0',
                    `end_sec` int(11) NOT NULL default '0',
                    PRIMARY KEY  (`timeperiod_timerange_id`),
                    UNIQUE KEY `instance_id` (`timeperiod_id`,`day`,`start_sec`,`end_sec`)
                  ) ENGINE=InnoDB COMMENT='Timeperiod definitions';";
    }

    if (!in_array('npc_timeperiods', $tables)) {
        $sql[] = "CREATE TABLE `npc_timeperiods` (
                    `timeperiod_id` int(11) NOT NULL auto_increment,
                    `instance_id` smallint(6) NOT NULL default '0',
                    `config_type` smallint(6) NOT NULL default '0',
                    `timeperiod_object_id` int(11) NOT NULL default '0',
                    `alias` varchar(255) NOT NULL default '',
                    PRIMARY KEY  (`timeperiod_id`),
                    UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`timeperiod_object_id`)
                  ) ENGINE=InnoDB COMMENT='Timeperiod definitions';";
    }

    if (!in_array('npc_service_graphs', $tables)) {
        $sql[] = "CREATE TABLE `npc_service_graphs` (
                    `service_graph_id` int(11) NOT NULL auto_increment,
                    `service_object_id` int(11) NOT NULL,
                    `local_graph_id` mediumint(8) unsigned NOT NULL,
                    `pri` tinyint(1) default 1,
                    PRIMARY KEY  (`service_graph_id`),
                    KEY `idx1` (`service_object_id`)
                  ) ENGINE=InnoDB;";
    }

    if (!in_array('npc_host_graphs', $tables)) {
        $sql[] = "CREATE TABLE `npc_host_graphs` (
                    `host_graph_id` int(11) NOT NULL auto_increment,
                    `host_object_id` int(11) NOT NULL,
                    `local_graph_id` mediumint(8) unsigned NOT NULL,
                    `pri` tinyint(1) default 1,
                    PRIMARY KEY  (`host_graph_id`),
                    KEY `idx1` (`host_object_id`)
                  ) ENGINE=InnoDB;";
    }

    if (!in_array('npc_settings', $tables)) {
        $sql[] = "CREATE TABLE `npc_settings` (
                    `user_id` mediumint(8) unsigned NOT NULL,
                    `settings` text default null,
                    PRIMARY KEY  (`user_id`)
                  ) ENGINE=InnoDB COMMENT='NPC user settings';";
    } else {

    }

    if (!empty($sql)) {
        for ($a = 0; $a < count($sql); $a++) {
             $result = db_execute($sql[$a]);
        }
   }
}

function npc_show_tab() {
    global $config;

    if (isset($_SESSION["sess_user_id"])) {
  
        $user_id = $_SESSION["sess_user_id"];

        $npc_realm = db_fetch_cell("SELECT id FROM plugin_config WHERE directory = 'npc'");
        $npc_enabled = db_fetch_cell("SELECT status FROM plugin_config WHERE directory = 'npc'");

        if ($npc_enabled == "1") {
            if (api_user_realm_auth('npc.php')) {
                $cp = false;
                if (basename($_SERVER["PHP_SELF"]) == "npc.php") { $cp = true; }

                print '<a href="' . $config['url_path'] . 'plugins/npc/npc.php"><img src="' 
                     . $config['url_path'] . 'plugins/npc/images/tab_npc' 
                     . ($cp ? "_down": "") . '.gif" alt="npc" align="absmiddle" border="0"></a>';
            }
        }
    }
}

function npc_config_settings() {

    global $tabs, $settings, $npc_date_format, $npc_time_format, $npc_log_level, $npc_default_settings, $npc_portlet_refresh;
    global $npc_config_type;

    if (isset($_SESSION["sess_user_id"])) {
  
        $user_id = $_SESSION["sess_user_id"];

        $npc_realm = db_fetch_cell("SELECT id FROM plugin_config WHERE directory = 'npc'");
        $npc_enabled = db_fetch_cell("SELECT status FROM plugin_config WHERE directory = 'npc'");

        # Check for upgraded NPC
        $current = plugin_npc_version ();
        $current_npc_version = $current['version'];
        $old_npc_version = db_fetch_cell("select version from plugin_config where directory='npc'");
        if ( ($current_npc_version != $old_npc_version) && ($old_npc_version != "") ) {
            //npc_upgrade_tables ();

            // Add a new realm
            if ($old_npc_version != '2.0.2') { 
                api_plugin_register_realm ('npc', 'npc1.php', 'NPC Global Commands', 1);
            }
        
            // Reset stored cookie values.
            db_execute("DELETE FROM npc_settings");

            db_execute("UPDATE plugin_config SET version = '".$current_npc_version."' where directory='npc'");
        }

        if ($npc_enabled == "1") {

            $tabs["npc"] = " NPC ";

            $cUser = db_fetch_assoc('SELECT id FROM user_auth');
            $nUser = db_fetch_assoc('SELECT user_id FROM npc_settings');

            // Add exitsting users to npc_settings
            for ($i = 0; $i < count($cUser); $i++) {
                if (!db_fetch_cell('SELECT user_id FROM npc_settings WHERE user_id = ' . $cUser[$i]['id'])) {
                    db_execute("INSERT INTO npc_settings VALUES(". $cUser[$i]['id'].",'".serialize($npc_default_settings)."')");
                }
            }

            // Delete non existent users from npc_settings
            for ($i = 0; $i < count($nUser); $i++) {
                if (isset($nUser[$i]['id']) && !db_fetch_cell('SELECT id FROM user_auth WHERE id = ' . $nUser[$i]['id'])) {
                    db_execute('DELETE FROM npc_settings WHERE user_id = ' . $nUser[$i]['id']);
                }
            }

            $settings['npc'] = array(
                "npc_header" => array(
                    "friendly_name" => "General Settings",
                    "method" => "spacer",
                ),
                "npc_nagios_commands" => array(
                    "friendly_name" => "Remote Commands",
                    "description" => "Allow commands to be written to the Nagios command file.",
                    "method" => "checkbox",
                ),
                "npc_nagios_cmd_path" => array(
                    "friendly_name" => "Nagios Command File Path",
                    "description" => "The path to the Nagios command file (nagios.cmd).",
                    "method" => "textbox",
                    "max_length" => 255,
                ),  
                "npc_nagios_url" => array(
                    "friendly_name" => "Nagios URL",
                    "description" => "The full URL to your Nagios installation (http://nagios.company.com/nagios/)",
                    "method" => "textbox",
                    "max_length" => 255,
                ),
                "npc_date_format" => array(
                    "friendly_name" => "Date Format",
                    "description" => "Select the format you want for displaying dates.",
                    "method" => "drop_array",
                    "default" => "Y-m-d",
                    "array" => $npc_date_format,
                ),
                "npc_time_format" => array(
                    "friendly_name" => "Time Format",
                    "description" => "Select the format you want for displaying times.",
                    "method" => "drop_array",
                    "default" => "H:i",
                    "array" => $npc_time_format,
                ),
                "npc_portlet_refresh" => array(
                    "friendly_name" => "Portlet Refresh Rate",
                    "description" => "The amount of time in seconds to wait before the portlets refresh. The minimum is 30 seconds.",
                    "method" => "textbox",
                    "default" => "60",
                    "max_length" => 3
                ),
                "npc_config_type" => array(
                    "friendly_name" => "Host/Service Config Type",
                    "description" => "The config type is based on whether or not you are restoring retained information when Nagios starts. If you are unsure just leave the default value. If you can see host and service groups but not hosts or services try changing this setting.",
                    "method" => "drop_array",
                    "default" => "1",
                    "array" => $npc_config_type,
                ),
                "npc_host_icons" => array(
                    "friendly_name" => "Host Icons",
                    "description" => "Enable displaying host icons in the hosts grid. The icon_image and icon_image_alt parameters of the Nagios host definition are used to set the image. Icons should be 16x16 to get the best look. this setting does not affect the host status icons.",
                    "method" => "checkbox",
                ),
                "npc_service_icons" => array(
                    "friendly_name" => "Service Icons",
                    "description" => "Enable displaying service icons in the services grid. The icon_image and icon_image_alt parameters of the Nagios service definition are used to set the image. Icons should be 16x16 to get the best look. This setting does not affect the service status icons.",
                    "method" => "checkbox",
                ),
                "npc_logging_header" => array(
                    "friendly_name" => "Logging",
                    "method" => "spacer",
                ),
                "npc_log_level" => array(
                    "friendly_name" => "Logging Level",
                    "description" => "The level of detail you want sent to the Cacti log file. WARNING: Leaving in DEBUG will quickly fill the cacti log.",
                    "method" => "drop_array",
                    "default" => "0",
                    "array" => $npc_log_level,
                )
            );
        }
    }
}

