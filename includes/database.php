<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

function npc_setup_tables() {
	global $config, $database_default;

	include_once($config['library_path'] . '/database.php');

	// Set the version
	$version = plugin_npc_version();
	$version = $version['version'];

	db_execute_prepared("REPLACE INTO settings
		(name, value) VALUES
		('plugin_npc_version', ?)", array($version));

	if (!db_column_exists('host', 'npc_host_object_id')) {
		db_execute("ALTER TABLE host ADD npc_host_object_id int(11) default NULL COMMENT 'Nagios host object mapping'");
	}

	$sql = array();

	if (!db_table_exists('npc_acknowledgements')) {
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
			PRIMARY KEY (`acknowledgement_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Current and historical host and service acknowledgements';";

		// Add some default values
		$sql[] = "INSERT INTO settings VALUES ('npc_date_format','Y-m-d');";
		$sql[] = "INSERT INTO settings VALUES ('npc_time_format','H:i');";
		$sql[] = "INSERT INTO settings VALUES ('npc_log_level','0');";
	}

	if (!db_table_exists('npc_commands')) {
		$sql[] = "CREATE TABLE `npc_commands` (
			`command_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`config_type` smallint(6) NOT NULL default '0',
			`object_id` int(11) NOT NULL default '0',
			`command_line` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`command_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`object_id`,`config_type`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Command definitions';";
	}

	if (!db_table_exists('npc_commenthistory')) {
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
			KEY `idx_internal_comment_id` (`internal_comment_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Historical host and service comments';";
	}

	if (!db_table_exists('npc_comments')) {
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
			KEY `idx1` (`object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic;";
	}

	if (!db_table_exists('npc_configfiles')) {
		$sql[] = "CREATE TABLE `npc_configfiles` (
			`configfile_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`configfile_type` smallint(6) NOT NULL default '0',
			`configfile_path` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`configfile_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`configfile_type`,`configfile_path`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Configuration files';";
	}

	if (!db_table_exists('npc_configfilevariables')) {
		$sql[] = "CREATE TABLE `npc_configfilevariables` (
			`configfilevariable_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`configfile_id` int(11) NOT NULL default '0',
			`varname` varchar(64) NOT NULL default '',
			`varvalue` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`configfilevariable_id`),
			KEY `instance_id` (`instance_id`,`configfile_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Configuration file variables';";
	}

	if (!db_table_exists('npc_conninfo')) {
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
			PRIMARY KEY  (`conninfo_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='NDO2DB daemon connection information';";
	}

	if (!db_table_exists('npc_contact_addresses')) {
		$sql[] = "CREATE TABLE `npc_contact_addresses` (
			`contact_address_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`contact_id` int(11) NOT NULL default '0',
			`address_number` smallint(6) NOT NULL default '0',
			`address` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`contact_address_id`),
			UNIQUE KEY `contact_id` (`contact_id`,`address_number`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Contact addresses';";
    }

	if (!db_table_exists('npc_contact_notificationcommands')) {
		$sql[] = "CREATE TABLE `npc_contact_notificationcommands` (
			`contact_notificationcommand_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`contact_id` int(11) NOT NULL default '0',
			`notification_type` smallint(6) NOT NULL default '0',
			`command_object_id` int(11) NOT NULL default '0',
			`command_args` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`contact_notificationcommand_id`),
			UNIQUE KEY `contact_id` (`contact_id`,`notification_type`,`command_object_id`,`command_args`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Contact host and service notification commands';";
	}

	if (!db_table_exists('npc_contactgroup_members')) {
		$sql[] = "CREATE TABLE `npc_contactgroup_members` (
			`contactgroup_member_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`contactgroup_id` int(11) NOT NULL default '0',
			`contact_object_id` int(11) NOT NULL default '0',
			PRIMARY KEY  (`contactgroup_member_id`),
			UNIQUE KEY `instance_id` (`contactgroup_id`,`contact_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Contactgroup members';";
	}

	if (!db_table_exists('npc_contactgroups')) {
		$sql[] = "CREATE TABLE `npc_contactgroups` (
			`contactgroup_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`config_type` smallint(6) NOT NULL default '0',
			`contactgroup_object_id` int(11) NOT NULL default '0',
			`alias` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`contactgroup_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`contactgroup_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Contactgroup definitions';";
	}

	if (!db_table_exists('npc_contactnotificationmethods')) {
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
			UNIQUE KEY `instance_id` (`instance_id`,`contactnotification_id`,`start_time`,`start_time_usec`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Historical record of contact notification methods';";
	}

	if (!db_table_exists('npc_contactnotifications')) {
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
			UNIQUE KEY `instance_id` (`instance_id`,`contact_object_id`,`start_time`,`start_time_usec`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Historical record of contact notifications';";
	}

	if (!db_table_exists('npc_contacts')) {
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
			`minimum_importance` smallint(6) NOT NULL default '0',
			PRIMARY KEY  (`contact_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`contact_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Contact definitions';";
	}

	if (!db_table_exists('npc_contactstatus')) {
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
			UNIQUE KEY `contact_object_id` (`contact_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Contact status';";
	}

	if (!db_table_exists('npc_customvariables')) {
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
			KEY `varname` (`varname`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Custom variables';";
	}

	if (!db_table_exists('npc_customvariablestatus')) {
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
			KEY `varname` (`varname`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Custom variable status information';";
	}

	if (!db_table_exists('npc_dbversion')) {
		$sql[] = "CREATE TABLE `npc_dbversion` (
			`name` varchar(10) NOT NULL default '',
			`version` varchar(10) NOT NULL default '')
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic;";
	}

	if (!db_table_exists('npc_downtimehistory')) {
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
			UNIQUE KEY `instance_id` (`instance_id`,`object_id`,`entry_time`,`internal_downtime_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Historical scheduled host and service downtime';";
	}

	if (!db_table_exists('npc_eventhandlers')) {
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
			`long_output` varchar(8192) NOT NULL default '',
			PRIMARY KEY  (`eventhandler_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`object_id`,`start_time`,`start_time_usec`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Historical host and service event handlers';";
	}

	if (!db_table_exists('npc_externalcommands')) {
		$sql[] = "CREATE TABLE `npc_externalcommands` (
			`externalcommand_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`entry_time` datetime NOT NULL default '0000-00-00 00:00:00',
			`command_type` smallint(6) NOT NULL default '0',
			`command_name` varchar(128) NOT NULL default '',
			`command_args` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`externalcommand_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Historical record of processed external commands';";
	}

	if (!db_table_exists('npc_flappinghistory')) {
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
			PRIMARY KEY  (`flappinghistory_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Current and historical record of host and service flapping';";
    }

	if (!db_table_exists('npc_host_contactgroups')) {
        $sql[] = "CREATE TABLE `npc_host_contactgroups` (
			`host_contactgroup_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`host_id` int(11) NOT NULL default '0',
			`contactgroup_object_id` int(11) NOT NULL default '0',
			PRIMARY KEY  (`host_contactgroup_id`),
			UNIQUE KEY `instance_id` (`host_id`,`contactgroup_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Host contact groups';";
    }

	if (!db_table_exists('npc_host_contacts')) {
        $sql[] = "CREATE TABLE `npc_host_contacts` (
			`host_contact_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`host_id` int(11) NOT NULL default '0',
			`contact_object_id` int(11) NOT NULL default '0',
			PRIMARY KEY  (`host_contact_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`host_id`,`contact_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic;";
    }

	if (!db_table_exists('npc_host_parenthosts')) {
        $sql[] = "CREATE TABLE `npc_host_parenthosts` (
			`host_parenthost_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`host_id` int(11) NOT NULL default '0',
			`parent_host_object_id` int(11) NOT NULL default '0',
			PRIMARY KEY  (`host_parenthost_id`),
			UNIQUE KEY `instance_id` (`host_id`,`parent_host_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Parent hosts';";
    }

	if (!db_table_exists('npc_hostchecks')) {
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
			`long_output` varchar(8192) NOT NULL default '',
			`perfdata` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`hostcheck_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`host_object_id`,`start_time`,`start_time_usec`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Historical host checks';";
    }

	if (!db_table_exists('npc_hostdependencies')) {
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
			UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`host_object_id`,`dependent_host_object_id`,`dependency_type`,`inherits_parent`,`fail_on_up`,`fail_on_down`,`fail_on_unreachable`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Host dependency definitions';";
    }

	if (!db_table_exists('npc_hostescalation_contactgroups')) {
        $sql[] = "CREATE TABLE `npc_hostescalation_contactgroups` (
			`hostescalation_contactgroup_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`hostescalation_id` int(11) NOT NULL default '0',
			`contactgroup_object_id` int(11) NOT NULL default '0',
			PRIMARY KEY  (`hostescalation_contactgroup_id`),
			UNIQUE KEY `instance_id` (`hostescalation_id`,`contactgroup_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Host escalation contact groups';";
    }

	if (!db_table_exists('npc_hostescalation_contacts')) {
        $sql[] = "CREATE TABLE `npc_hostescalation_contacts` (
			`hostescalation_contact_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`hostescalation_id` int(11) NOT NULL default '0',
			`contact_object_id` int(11) NOT NULL default '0',
			PRIMARY KEY  (`hostescalation_contact_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`hostescalation_id`,`contact_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic;";
    }

	if (!db_table_exists('npc_hostescalations')) {
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
			UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`host_object_id`,`timeperiod_object_id`,`first_notification`,`last_notification`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Host escalation definitions';";
    }

	if (!db_table_exists('npc_hostgroup_members')) {
        $sql[] = "CREATE TABLE `npc_hostgroup_members` (
			`hostgroup_member_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`hostgroup_id` int(11) NOT NULL default '0',
			`host_object_id` int(11) NOT NULL default '0',
			PRIMARY KEY  (`hostgroup_member_id`),
			UNIQUE KEY `instance_id` (`hostgroup_id`,`host_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Hostgroup members';";
    }

	if (!db_table_exists('npc_hostgroups')) {
        $sql[] = "CREATE TABLE `npc_hostgroups` (
			`hostgroup_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`config_type` smallint(6) NOT NULL default '0',
			`hostgroup_object_id` int(11) NOT NULL default '0',
			`alias` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`hostgroup_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`hostgroup_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Hostgroup definitions';";
    }

	if (!db_table_exists('npc_hosts')) {
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
			`importance` smallint(6) NOT NULL default '0',
			PRIMARY KEY  (`host_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`host_object_id`),
			KEY `idx1` (`host_object_id`),
			KEY `idx2` (`config_type`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Host definitions';";
    }

	if (!db_table_exists('npc_hoststatus')) {
        $sql[] = "CREATE TABLE `npc_hoststatus` (
			`hoststatus_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`host_object_id` int(11) NOT NULL default '0',
			`status_update_time` datetime NOT NULL default '0000-00-00 00:00:00',
			`output` varchar(255) NOT NULL default '',
			`long_output` varchar(8192) NOT NULL default '',
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
			KEY `idx1` (`current_state`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Current host status information';";
    }

	if (!db_table_exists('npc_instances')) {
        $sql[] = "CREATE TABLE `npc_instances` (
			`instance_id` smallint(6) NOT NULL auto_increment,
			`instance_name` varchar(64) NOT NULL default '',
			`instance_description` varchar(128) NOT NULL default '',
			PRIMARY KEY  (`instance_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Location names of various Nagios installations';";
    }

	if (!db_table_exists('npc_logentries')) {
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
			KEY `idx1` (`entry_time`,`entry_time_usec`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Historical record of log entries';";
    }

	if (!db_table_exists('npc_notifications')) {
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
			`long_output` varchar(8192) NOT NULL default '',
			`escalated` smallint(6) NOT NULL default '0',
			`contacts_notified` smallint(6) NOT NULL default '0',
			PRIMARY KEY  (`notification_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`object_id`,`start_time`,`start_time_usec`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Historical record of host and service notifications';";
    }

	if (!db_table_exists('npc_objects')) {
        $sql[] = "CREATE TABLE `npc_objects` (
			`object_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`objecttype_id` smallint(6) NOT NULL default '0',
			`name1` varchar(128) NOT NULL default '',
			`name2` varchar(128) default NULL,
			`is_active` smallint(6) NOT NULL default '0',
			PRIMARY KEY  (`object_id`),
			KEY `objecttype_id` (`objecttype_id`,`name1`,`name2`),
			KEY `name_idx` (`name1`,`name2`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Current and historical objects of all kinds';";
    }

	if (!db_table_exists('npc_processevents')) {
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
			PRIMARY KEY  (`processevent_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Historical Nagios process events';";
    }

	if (!db_table_exists('npc_programstatus')) {
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
			UNIQUE KEY `instance_id` (`instance_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Current program status information';";
    }

	if (!db_table_exists('npc_runtimevariables')) {
        $sql[] = "CREATE TABLE `npc_runtimevariables` (
			`runtimevariable_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`varname` varchar(64) NOT NULL default '',
			`varvalue` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`runtimevariable_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`varname`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Runtime variables from the Nagios daemon';";
    }

	if (!db_table_exists('npc_scheduleddowntime')) {
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
			UNIQUE KEY `instance_id` (`instance_id`,`object_id`,`entry_time`,`internal_downtime_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Current scheduled host and service downtime';";
    }

	if (!db_table_exists('npc_service_contactgroups')) {
        $sql[] = "CREATE TABLE `npc_service_contactgroups` (
			`service_contactgroup_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`service_id` int(11) NOT NULL default '0',
			`contactgroup_object_id` int(11) NOT NULL default '0',
			PRIMARY KEY  (`service_contactgroup_id`),
			UNIQUE KEY `instance_id` (`service_id`,`contactgroup_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Service contact groups';";
    }

	if (!db_table_exists('npc_service_contacts')) {
        $sql[] = "CREATE TABLE `npc_service_contacts` (
			`service_contact_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`service_id` int(11) NOT NULL default '0',
			`contact_object_id` int(11) NOT NULL default '0',
			PRIMARY KEY  (`service_contact_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`service_id`,`contact_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic;";
    }

	if (!db_table_exists('npc_servicechecks')) {
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
			`long_output` varchar(8192) NOT NULL default '',
			`perfdata` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`servicecheck_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`service_object_id`,`start_time`,`start_time_usec`),
			KEY `idx1` (`service_object_id`,`start_time`),
			KEY `idx2` (`instance_id`,`start_time`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Historical service checks';";
    }

	if (!db_table_exists('npc_servicedependencies')) {
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
			UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`service_object_id`,`dependent_service_object_id`,`dependency_type`,`inherits_parent`,`fail_on_ok`,`fail_on_warning`,`fail_on_unknown`,`fail_on_critical`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Service dependency definitions';";
    }

	if (!db_table_exists('npc_serviceescalation_contactgroups')) {
        $sql[] = "CREATE TABLE `npc_serviceescalation_contactgroups` (
			`serviceescalation_contactgroup_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`serviceescalation_id` int(11) NOT NULL default '0',
			`contactgroup_object_id` int(11) NOT NULL default '0',
			PRIMARY KEY  (`serviceescalation_contactgroup_id`),
			UNIQUE KEY `instance_id` (`serviceescalation_id`,`contactgroup_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Service escalation contact groups';";
    }

	if (!db_table_exists('npc_serviceescalation_contacts')) {
        $sql[] = "CREATE TABLE `npc_serviceescalation_contacts` (
			`serviceescalation_contact_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`serviceescalation_id` int(11) NOT NULL default '0',
			`contact_object_id` int(11) NOT NULL default '0',
			PRIMARY KEY  (`serviceescalation_contact_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`serviceescalation_id`,`contact_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic;";
    }

	if (!db_table_exists('npc_serviceescalations')) {
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
			UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`service_object_id`,`timeperiod_object_id`,`first_notification`,`last_notification`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Service escalation definitions';";
    }

	if (!db_table_exists('npc_servicegroup_members')) {
        $sql[] = "CREATE TABLE `npc_servicegroup_members` (
			`servicegroup_member_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`servicegroup_id` int(11) NOT NULL default '0',
			`service_object_id` int(11) NOT NULL default '0',
			PRIMARY KEY  (`servicegroup_member_id`),
			UNIQUE KEY `instance_id` (`servicegroup_id`,`service_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Servicegroup members';";
    }

	if (!db_table_exists('npc_servicegroups')) {
        $sql[] = "CREATE TABLE `npc_servicegroups` (
			`servicegroup_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`config_type` smallint(6) NOT NULL default '0',
			`servicegroup_object_id` int(11) NOT NULL default '0',
			`alias` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`servicegroup_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`servicegroup_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Servicegroup definitions';";
    }

	if (!db_table_exists('npc_services')) {
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
			`importance` smallint(6) NOT NULL default '0',
			PRIMARY KEY  (`service_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`service_object_id`),
			KEY `idx1` (`config_type`),
			KEY `idx2` (`host_object_id`),
			KEY `idx3` (`service_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Service definitions';";
    }

	if (!db_table_exists('npc_servicestatus')) {
        $sql[] = "CREATE TABLE `npc_servicestatus` (
			`servicestatus_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`service_object_id` int(11) NOT NULL default '0',
			`status_update_time` datetime NOT NULL default '0000-00-00 00:00:00',
			`output` varchar(255) NOT NULL default '',
			`long_output` varchar(8192) NOT NULL default '',
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
			KEY `idx1` (`current_state`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Current service status information';";
    }

	if (!db_table_exists('npc_statehistory')) {
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
			`long_output` varchar(8192) NOT NULL default '',
			PRIMARY KEY  (`statehistory_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Historical host and service state changes';";
    }

	if (!db_table_exists('npc_systemcommands')) {
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
			`long_output` varchar(8192) NOT NULL default '',
			PRIMARY KEY  (`systemcommand_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`start_time`,`start_time_usec`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Historical system commands that are executed';";
    }

	if (!db_table_exists('npc_timedeventqueue')) {
        $sql[] = "CREATE TABLE `npc_timedeventqueue` (
			`timedeventqueue_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`event_type` smallint(6) NOT NULL default '0',
			`queued_time` datetime NOT NULL default '0000-00-00 00:00:00',
			`queued_time_usec` int(11) NOT NULL default '0',
			`scheduled_time` datetime NOT NULL default '0000-00-00 00:00:00',
			`recurring_event` smallint(6) NOT NULL default '0',
			`object_id` int(11) NOT NULL default '0',
			PRIMARY KEY  (`timedeventqueue_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Current Nagios event queue';";
    }

	if (!db_table_exists('npc_timedevents')) {
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
			UNIQUE KEY `instance_id` (`instance_id`,`event_type`,`scheduled_time`,`object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Historical events from the Nagios event queue';";
    }

	if (!db_table_exists('npc_timeperiod_timeranges')) {
        $sql[] = "CREATE TABLE `npc_timeperiod_timeranges` (
			`timeperiod_timerange_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`timeperiod_id` int(11) NOT NULL default '0',
			`day` smallint(6) NOT NULL default '0',
			`start_sec` int(11) NOT NULL default '0',
			`end_sec` int(11) NOT NULL default '0',
			PRIMARY KEY  (`timeperiod_timerange_id`),
			UNIQUE KEY `instance_id` (`timeperiod_id`,`day`,`start_sec`,`end_sec`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Timeperiod definitions';";
    }

	if (!db_table_exists('npc_timeperiods')) {
        $sql[] = "CREATE TABLE `npc_timeperiods` (
			`timeperiod_id` int(11) NOT NULL auto_increment,
			`instance_id` smallint(6) NOT NULL default '0',
			`config_type` smallint(6) NOT NULL default '0',
			`timeperiod_object_id` int(11) NOT NULL default '0',
			`alias` varchar(255) NOT NULL default '',
			PRIMARY KEY  (`timeperiod_id`),
			UNIQUE KEY `instance_id` (`instance_id`,`config_type`,`timeperiod_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='Timeperiod definitions';";
    }

	if (!db_table_exists('npc_service_graphs')) {
        $sql[] = "CREATE TABLE `npc_service_graphs` (
			`service_graph_id` int(11) NOT NULL auto_increment,
			`service_object_id` int(11) NOT NULL,
			`local_graph_id` mediumint(8) unsigned NOT NULL,
			`pri` tinyint(1) default 1,
			PRIMARY KEY  (`service_graph_id`),
			KEY `idx1` (`service_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic;";
    }

	if (!db_table_exists('npc_host_graphs')) {
		$sql[] = "CREATE TABLE `npc_host_graphs` (
			`host_graph_id` int(11) NOT NULL auto_increment,
			`host_object_id` int(11) NOT NULL,
			`local_graph_id` mediumint(8) unsigned NOT NULL,
			`pri` tinyint(1) default 1,
			PRIMARY KEY  (`host_graph_id`),
			KEY `idx1` (`host_object_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			ROW_FORMAT=Dynamic;";
    }

	if (!db_table_exists('npc_settings')) {
		$sql[] = "CREATE TABLE `npc_settings` (
			`user_id` mediumint(8) unsigned NOT NULL,
			`settings` text default null,
			PRIMARY KEY  (`user_id`))
			ENGINE=InnoDB
			ROW_FORMAT=Dynamic
			COMMENT='NPC user settings';";
	}

    if (cacti_sizeof($sql)) {
		foreach($sql as $query) {
			$result = db_execute($query);
		}
   }
}
