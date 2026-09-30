<?php
/* $Id: setup.php 213 2009-06-19 12:28:18Z divagater $ */

require_once(__DIR__ . '/includes/database.php');
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
 * Return the CSP nonce attribute for inline <script> tags, safely across
 * Cacti versions. Newer Cacti releases enforce a Content-Security-Policy that
 * requires a per-request nonce on parser-inserted scripts; older releases lack
 * the CactiSecureHeaders class, so this returns an empty string there.
 *
 * @return string The nonce attribute when supported, otherwise empty string.
 */
function plugin_npc_csp_nonce(): string {
	if (class_exists('CactiSecureHeaders')) {
		return CactiSecureHeaders::getNonceAttribute();
	}

	return '';
}

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
 * Version information
 */
function plugin_npc_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/npc/INFO', true);

	return $info['info'];
}

function plugin_npc_install() {
	npc_setup_tables();

	api_plugin_register_realm('npc', 'npc.php', 'NPC', 1);
	api_plugin_register_realm('npc', 'npc1.php', 'NPC Global Commands', 1);

	// setup all arrays needed for npc
	api_plugin_register_hook('npc', 'config_arrays', 'npc_config_arrays', 'setup.php');

	// Add the npc tab
	api_plugin_register_hook('npc', 'top_header_tabs', 'npc_show_tab', 'setup.php');
	api_plugin_register_hook('npc', 'top_graph_header_tabs', 'npc_show_tab', 'setup.php');

	// Provide navigation texts
	api_plugin_register_hook('npc', 'draw_navigation_text', 'npc_draw_navigation_text', 'setup.php');

	// Add Nagios host mapping select box
	api_plugin_register_hook('npc', 'config_form', 'npc_config_form', 'setup.php');

	// Saves the selection from the host mapping select box
	api_plugin_register_hook('npc', 'api_device_save', 'npc_api_device_save', 'setup.php');

	// Add a npc tab to the settings page
	api_plugin_register_hook('npc', 'config_settings', 'npc_config_settings', 'setup.php');

	// Add header items
	api_plugin_register_hook('npc', 'page_head', 'npc_page_head', 'setup.php');
}

function npc_page_head() {
	global $config;

	?>
	<?php
}

/**
 * Remove all NPC database changes
 */
function plugin_npc_uninstall() {
	// Drop all npc tables
	db_execute('DROP TABLE IF EXISTS `npc_acknowledgements`');
	db_execute('DROP TABLE IF EXISTS `npc_commands`');
	db_execute('DROP TABLE IF EXISTS `npc_commenthistory`');
	db_execute('DROP TABLE IF EXISTS `npc_comments`');
	db_execute('DROP TABLE IF EXISTS `npc_configfiles`');
	db_execute('DROP TABLE IF EXISTS `npc_configfilevariables`');
	db_execute('DROP TABLE IF EXISTS `npc_conninfo`');
	db_execute('DROP TABLE IF EXISTS `npc_contact_addresses`');
	db_execute('DROP TABLE IF EXISTS `npc_contact_notificationcommands`');
	db_execute('DROP TABLE IF EXISTS `npc_contactgroup_members`');
	db_execute('DROP TABLE IF EXISTS `npc_contactgroups`');
	db_execute('DROP TABLE IF EXISTS `npc_contactnotificationmethods`');
	db_execute('DROP TABLE IF EXISTS `npc_contactnotifications`');
	db_execute('DROP TABLE IF EXISTS `npc_contacts`');
	db_execute('DROP TABLE IF EXISTS `npc_contactstatus`');
	db_execute('DROP TABLE IF EXISTS `npc_customvariables`');
	db_execute('DROP TABLE IF EXISTS `npc_customvariablestatus`');
	db_execute('DROP TABLE IF EXISTS `npc_dbversion`');
	db_execute('DROP TABLE IF EXISTS `npc_downtimehistory`');
	db_execute('DROP TABLE IF EXISTS `npc_eventhandlers`');
	db_execute('DROP TABLE IF EXISTS `npc_externalcommands`');
	db_execute('DROP TABLE IF EXISTS `npc_flappinghistory`');
	db_execute('DROP TABLE IF EXISTS `npc_host_contactgroups`');
	db_execute('DROP TABLE IF EXISTS `npc_host_contacts`');
	db_execute('DROP TABLE IF EXISTS `npc_host_graphs`');
	db_execute('DROP TABLE IF EXISTS `npc_host_parenthosts`');
	db_execute('DROP TABLE IF EXISTS `npc_hostchecks`');
	db_execute('DROP TABLE IF EXISTS `npc_hostdependencies`');
	db_execute('DROP TABLE IF EXISTS `npc_hostescalation_contactgroups`');
	db_execute('DROP TABLE IF EXISTS `npc_hostescalation_contacts`');
	db_execute('DROP TABLE IF EXISTS `npc_hostescalations`');
	db_execute('DROP TABLE IF EXISTS `npc_hostgroup_members`');
	db_execute('DROP TABLE IF EXISTS `npc_hostgroups`');
	db_execute('DROP TABLE IF EXISTS `npc_hosts`');
	db_execute('DROP TABLE IF EXISTS `npc_hoststatus`');
	db_execute('DROP TABLE IF EXISTS `npc_instances`');
	db_execute('DROP TABLE IF EXISTS `npc_logentries`');
	db_execute('DROP TABLE IF EXISTS `npc_notifications`');
	db_execute('DROP TABLE IF EXISTS `npc_objects`');
	db_execute('DROP TABLE IF EXISTS `npc_processevents`');
	db_execute('DROP TABLE IF EXISTS `npc_programstatus`');
	db_execute('DROP TABLE IF EXISTS `npc_runtimevariables`');
	db_execute('DROP TABLE IF EXISTS `npc_scheduleddowntime`');
	db_execute('DROP TABLE IF EXISTS `npc_service_contactgroups`');
	db_execute('DROP TABLE IF EXISTS `npc_service_contacts`');
	db_execute('DROP TABLE IF EXISTS `npc_service_graphs`');
	db_execute('DROP TABLE IF EXISTS `npc_servicechecks`');
	db_execute('DROP TABLE IF EXISTS `npc_servicedependencies`');
	db_execute('DROP TABLE IF EXISTS `npc_serviceescalation_contactgroups`');
	db_execute('DROP TABLE IF EXISTS `npc_serviceescalation_contacts`');
	db_execute('DROP TABLE IF EXISTS `npc_serviceescalations`');
	db_execute('DROP TABLE IF EXISTS `npc_servicegroup_members`');
	db_execute('DROP TABLE IF EXISTS `npc_servicegroups`');
	db_execute('DROP TABLE IF EXISTS `npc_services`');
	db_execute('DROP TABLE IF EXISTS `npc_servicestatus`');
	db_execute('DROP TABLE IF EXISTS `npc_settings`');
	db_execute('DROP TABLE IF EXISTS `npc_statehistory`');
	db_execute('DROP TABLE IF EXISTS `npc_systemcommands`');
	db_execute('DROP TABLE IF EXISTS `npc_timedeventqueue`');
	db_execute('DROP TABLE IF EXISTS `npc_timedevents`');
	db_execute('DROP TABLE IF EXISTS `npc_timeperiod_timeranges`');
	db_execute('DROP TABLE IF EXISTS `npc_timeperiods`');

	db_execute('ALTER TABLE `host` DROP `npc_host_object_id`');
	db_execute('DELETE FROM `settings` WHERE `name` like "npc\_%"');

	api_plugin_remove_realms('npc');
}

function npc_config_arrays() {
	global $user_auth_realms, $user_auth_realm_filenames, $npc_date_format, $npc_time_format;
	global $npc_default_settings, $npc_log_level, $npc_config_type;

	if (isset($_SESSION['sess_user_id'])) {
		$user_id=$_SESSION['sess_user_id'];

		$npc_realm = db_fetch_cell_prepared("SELECT id FROM plugin_config WHERE directory = ?", array('npc'));
		$npc_enabled = db_fetch_cell_prepared("SELECT status FROM plugin_config WHERE directory = ?", array('npc'));

		if ($npc_enabled == '1') {
			$user_auth_realm_filenames['npc.php'] = 9000 + $npc_realm;

			$npc_log_level = array(
				'0' => __('None', 'npc'),
				'1' => __('ERROR - Log errors only', 'npc'),
				'2' => __('WARN  - Log errors and warnings', 'npc'),
				'3' => __('INFO  - Log errors, warnings, and info messages', 'npc'),
				'4' => __('DEBUG - Log everything', 'npc')
			);

			$npc_config_type = array(
				'0' => '0',
				'1' => '1'
			);

			$npc_date_format = array(
				'Y-m-d' => '2007-12-27',
				'm-d-Y' => '12-27-2007',
				'd-m-Y' => '27-12-2007',
				'Y/m/d' => '2007/12/27',
				'm/d/Y' => '12/27/2007',
				'd/m/Y' => '27/12/2007',
				'Y.m.d' => '2007.12.27',
				'd.m.Y' => '27.12.2007',
				'm.d.Y' => '12.27.2007'
			);

			$npc_time_format = array(
				'H:i:s'  => '23:07',
				'h:i:sa' => '11:07pm',
				'h:i:sA' => '11:07PM',
				'H.i.s'  => '23.07',
				'h.i.sa' => '11.07pm',
				'h.i.sA' => '11.07PM'
			);

			// Initial settings for server side state handling
			$npc_default_settings = array(
				'date_format' => 's%3AY-m-d',
				'time_format' => 's%3AH%3Ai%3As',
				'serviceProblems' => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol1%5Ehidden%3Db%253A0%5Eindex%3Ds%253A0%5Erefresh%3Dn%253A120%5Eheight%3Dn%253A150',
				'serviceSummary' => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol1%5Ehidden%3Db%253A0%5Eindex%3Ds%253A1%5Erefresh%3Dn%253A120%5E',
				'servicegroupServiceStatus' => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol1%5Ehidden%3Db%253A0%5Eindex%3Ds%253A2%5Erefresh%3Dn%253A120%5Eheight%3Dn%253A150',
				'servicegroupHostStatus' => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol1%5Ehidden%3Db%253A0%5Eindex%3Ds%253A3%5Erefresh%3Dn%253A120%5Eheight%3Dn%253A150',
				'monitoringPerf' => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol1%5Ehidden%3Db%253A1%5Eindex%3Ds%253A4%5Erefresh%3Dn%253A120%5E',
				'hostProblems' => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol2%5Ehidden%3Db%253A0%5Eindex%3Ds%253A0%5Erefresh%3Dn%253A120%5Eheight%3Dn%253A150',
				'hostSummary' => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol2%5Ehidden%3Db%253A0%5Eindex%3Ds%253A1%5Erefresh%3Dn%253A120%5E',
				'hostgroupServiceStatus' => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol2%5Ehidden%3Db%253A0%5Eindex%3Ds%253A2%5Erefresh%3Dn%253A120%5Eheight%3Dn%253A150',
				'hostgroupHostStatus' => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol2%5Ehidden%3Db%253A0%5Eindex%3Ds%253A3%5Erefresh%3Dn%253A120%5Eheight%3Dn%253A150',
				'eventLog' => 'o%3Acollapsed%3Db%253A0%5Ecolumn%3Ds%253Adashcol2%5Ehidden%3Db%253A1%5Eindex%3Ds%253A4%5Erefresh%3Dn%253A120%5Eheight%3Dn%253A150'
			);

			api_plugin_load_realms();
		}
	}
}

function npc_config_form() {
	global $fields_host_edit;

	$fields_host_edit2 = $fields_host_edit;
	$fields_host_edit3 = array();

	foreach ($fields_host_edit2 as $f => $a) {
		$fields_host_edit3[$f] = $a;
		if ($f == 'disabled') {
			$fields_host_edit3['npc_host_object_id'] = array(
				'method' => 'drop_sql',
				'friendly_name' => __('Nagios Host Mapping', 'npc'),
				'description' => __('Select the Nagios host that maps to this host.', 'npc'),
				'value' => '|arg1:npc_host_object_id|',
				'none_value' => __('None', 'npc'),
				'default' => '0',
				'sql' => 'SELECT npc_hosts.host_object_id as id, concat(npc_instances.instance_name, ": ", obj1.name1) AS name
					FROM `npc_hosts`
					LEFT JOIN npc_objects as obj1
					ON npc_hosts.host_object_id=obj1.object_id
					LEFT JOIN npc_instances
					ON npc_hosts.instance_id=npc_instances.instance_id',
				'form_id' => false
			);
		}
	}

	$fields_host_edit = $fields_host_edit3;
}

function npc_api_device_save($save) {
	if (isset_request_var('npc_host_object_id')) {
		$save['npc_host_object_id'] = form_input_validate(get_filter_request_var('npc_host_object_id'), 'npc_host_object_id', '^[0-9]+$', true, 3);
	} else {
		$save['npc_host_object_id'] = form_input_validate('', 'npc_host_object_id', '', true, 3);
	}

	return $save;
}

function npc_draw_navigation_text($nav) {
   $nav['npc.php:'] = array(
		'title' => __('NPC', 'npc'),
		'mapping' => 'index.php:',
		'url' => 'npc.php',
		'level' => '1'
	);

   $nav['statusDetail.php:'] = array(
		'title' => __('Status Detail', 'npc'),
		'mapping' => 'npc.php:',
		'url' => 'statusDetail.php',
		'level' => '3'
	);

   $nav['extinfo.php:'] = array(
		'title' => __('Service / Host Information', 'npc'),
		'mapping' => 'statusDetail.php:',
		'url' => 'extinfo.php',
		'level' => '4'
	);

   $nav['command.php:'] = array(
		'title' => __('Command Execution', 'npc'),
		'mapping' => 'extinfo.php:',
		'url' => 'command.php',
		'level' => '5'
	);

   return $nav;
}


function npc_show_tab() {
	global $config;

	if (isset($_SESSION["sess_user_id"])) {
		$user_id = $_SESSION["sess_user_id"];

		$npc_realm = db_fetch_cell_prepared("SELECT id FROM plugin_config WHERE directory = ?", array('npc'));
		$npc_enabled = db_fetch_cell_prepared("SELECT status FROM plugin_config WHERE directory = ?", array('npc'));

		if ($npc_enabled == "1") {
			if (api_user_realm_auth('npc.php')) {
				$cp = false;
				if (basename($_SERVER["PHP_SELF"]) == "npc.php") {
					$cp = true;
				}

				print '<a href="' . html_escape($config['url_path'] . 'plugins/npc/npc.php') . '"><img src="'
					. $config['url_path'] . 'plugins/npc/images/tab_npc'
					. ($cp ? "_down": "") . '.gif" alt="' . __('NPC', 'npc') . '" align="absmiddle" border="0"></a>';
			}
		}
	}
}

function npc_config_settings() {
	global $tabs, $settings, $npc_date_format, $npc_time_format, $npc_log_level, $npc_default_settings, $npc_portlet_refresh;
	global $npc_config_type;

	if (isset($_SESSION['sess_user_id'])) {
		$user_id = $_SESSION['sess_user_id'];

		$npc_realm = db_fetch_cell_prepared("SELECT id FROM plugin_config WHERE directory = ?", array('npc'));
		$npc_enabled = db_fetch_cell_prepared("SELECT status FROM plugin_config WHERE directory = ?", array('npc'));

		# Check for upgraded NPC
		$current = plugin_npc_version();
		$current_npc_version = $current['version'];

		$old_npc_version = db_fetch_cell("SELECT version FROM plugin_config WHERE directory='npc'");

		if (($current_npc_version != $old_npc_version) && ($old_npc_version != '')) {
			npc_upgrade_tables();

			// Add a new realm
			if ($old_npc_version != '2.0.2' || $old_npc_version != '2.0.3') {
				api_plugin_register_realm ('npc', 'npc1.php', 'NPC Global Commands', 1);
			}

			// Reset stored cookie values.
			db_execute('DELETE FROM npc_settings');

			db_execute("UPDATE plugin_config SET version = '".$current_npc_version."' where directory='npc'");
		}

		if ($npc_enabled == '1') {
			$tabs['npc'] = ' NPC ';

			$cUser = db_fetch_assoc('SELECT id FROM user_auth');
			$nUser = db_fetch_assoc('SELECT user_id FROM npc_settings');

			// Add exitsting users to npc_settings
			for ($i = 0; $i < count($cUser); $i++) {
				if (!db_fetch_cell('SELECT user_id FROM npc_settings WHERE user_id = ' . $cUser[$i]['id'])) {
					db_execute('INSERT INTO npc_settings VALUES('. $cUser[$i]['id'].",'".serialize($npc_default_settings)."')");
				}
			}

			// Delete non existent users from npc_settings
			for ($i = 0; $i < count($nUser); $i++) {
				if (isset($nUser[$i]['id']) && !db_fetch_cell('SELECT id FROM user_auth WHERE id = ' . $nUser[$i]['id'])) {
					db_execute('DELETE FROM npc_settings WHERE user_id = ' . $nUser[$i]['id']);
				}
			}

			$settings['npc'] = array(
				'npc_header' => array(
					'friendly_name' => __('General Settings', 'npc'),
					'method' => 'spacer',
				),
				'npc_nagios_commands' => array(
					'friendly_name' => __('Remote Commands', 'npc'),
					'description' => __('Allow commands to be written to the Nagios command file.', 'npc'),
					'method' => 'checkbox',
				),
				'npc_nagios_cmd_path' => array(
					'friendly_name' => __('Nagios Command File Path', 'npc'),
					'description' => __('The path to the Nagios command file (nagios.cmd).', 'npc'),
					'method' => 'textbox',
					'max_length' => 255,
				),
				'npc_nagios_url' => array(
					'friendly_name' => __('Nagios URL', 'npc'),
					'description' => __('The full URL to your Nagios installation (http://nagios.company.com/nagios/)', 'npc'),
					'method' => 'textbox',
					'max_length' => 255,
				),
				'npc_date_format' => array(
					'friendly_name' => __('Date Format', 'npc'),
					'description' => __('Select the format you want for displaying dates.', 'npc'),
					'method' => 'drop_array',
					'default' => 'Y-m-d',
					'array' => $npc_date_format,
				),
				'npc_time_format' => array(
					'friendly_name' => __('Time Format', 'npc'),
					'description' => __('Select the format you want for displaying times.', 'npc'),
					'method' => 'drop_array',
					'default' => 'H:i',
					'array' => $npc_time_format,
				),
				'npc_portlet_refresh' => array(
					'friendly_name' => __('Portlet Refresh Rate', 'npc'),
					'description' => __('The amount of time in seconds to wait before the portlets refresh. The minimum is 30 seconds.', 'npc'),
					'method' => 'textbox',
					'default' => '60',
					'max_length' => 3
				),
				'npc_config_type' => array(
					'friendly_name' => __('Host/Service Config Type', 'npc'),
					'description' => __('The config type is based on whether or not you are restoring retained information when Nagios starts. If you are unsure just leave the default value. If you can see host and service groups but not hosts or services try changing this setting.', 'npc'),
					'method' => 'drop_array',
					'default' => '1',
					'array' => $npc_config_type,
				),
				'npc_host_icons' => array(
					'friendly_name' => __('Host Icons', 'npc'),
					'description' => __('Enable displaying host icons in the hosts grid. The icon_image and icon_image_alt parameters of the Nagios host definition are used to set the image. Icons should be 16x16 to get the best look. this setting does not affect the host status icons.', 'npc'),
					'method' => 'checkbox',
				),
				'npc_service_icons' => array(
					'friendly_name' => __('Service Icons', 'npc'),
					'description' => __('Enable displaying service icons in the services grid. The icon_image and icon_image_alt parameters of the Nagios service definition are used to set the image. Icons should be 16x16 to get the best look. This setting does not affect the service status icons.', 'npc'),
					'method' => 'checkbox',
				),
				'npc_logging_header' => array(
					'friendly_name' => __('Logging', 'npc'),
					'method' => 'spacer',
				),
				'npc_log_level' => array(
					'friendly_name' => __('Logging Level', 'npc'),
					'description' => __('The level of detail you want sent to the Cacti log file. WARNING: Leaving in DEBUG will quickly fill the cacti log.', 'npc'),
					'method' => 'drop_array',
					'default' => '0',
					'array' => $npc_log_level,
				)
			);
		}
	}
}

function npc_upgrade_tables() {
	if (!db_column_exists('npc_hostchecks', 'long_output')) {
		db_execute("ALTER TABLE `npc_hostchecks` ADD COLUMN `long_output` varchar(8192) NOT NULL default '' AFTER `output`");
	}

	if (!db_column_exists('npc_hoststatus', 'long_output')) {
		db_execute("ALTER TABLE `npc_hoststatus` ADD COLUMN `long_output` varchar(8192) NOT NULL default '' AFTER `output`");
	}

	if (!db_column_exists('npc_servicechecks', 'long_output')) {
		db_execute("ALTER TABLE `npc_servicechecks` ADD COLUMN `long_output` varchar(8192) NOT NULL default '' AFTER `output`");
	}

	if (!db_column_exists('npc_servicestatus', 'long_output')) {
		db_execute("ALTER TABLE `npc_servicestatus` ADD COLUMN `long_output` varchar(8192) NOT NULL default '' AFTER `output`");
	}

	if (!db_column_exists('npc_statehistory', 'long_output')) {
		db_execute("ALTER TABLE `npc_statehistory` ADD COLUMN `long_output` varchar(8192) NOT NULL default '' AFTER `output`");
	}

	if (!db_column_exists('npc_eventhandlers', 'long_output')) {
		db_execute("ALTER TABLE `npc_eventhandlers` ADD COLUMN `long_output` varchar(8192) NOT NULL default '' AFTER `output`");
	}

	if (!db_column_exists('npc_systemcommands', 'long_output')) {
		db_execute("ALTER TABLE `npc_systemcommands` ADD COLUMN `long_output` varchar(8192) NOT NULL default '' AFTER `output`");
	}

	if (!db_column_exists('npc_notifications', 'long_output')) {
		db_execute("ALTER TABLE `npc_notifications` ADD COLUMN `long_output` varchar(8192) NOT NULL default '' AFTER `output`");
	}

	if (!db_column_exists('npc_services', 'importance')) {
		db_execute("ALTER TABLE `npc_services` ADD COLUMN `importance` smallint(6) NOT NULL default '0' AFTER `icon_image_alt`");
	}

	if (!db_column_exists('npc_hosts', 'importance')) {
		db_execute("ALTER TABLE `npc_hosts` ADD COLUMN `importance` smallint(6) NOT NULL default '0' AFTER `z_3d`");
	}

	if (!db_column_exists('npc_contracts', 'minimum_importance')) {
		db_execute("ALTER TABLE `npc_contracts` ADD COLUMN `minimum_importance` smallint(6) NOT NULL default '0' AFTER `notify_host_downtime`");
	}
}

