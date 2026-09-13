<?php
/*
 +-------------------------------------------------------------------------+
 | Nagios Plugin for Cacti                                                 |
 |                                                                         |
 | Copyright (C) 2007 Billy Gunn (billy@gunn.org)                          |
 | Copyright (C) 2024 The Cacti Group, Inc.                                |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 +-------------------------------------------------------------------------+
*/

/*
 * Verifies that setup.php declares all required Cacti plugin hooks and realm
 * registrations. Cacti discovers and integrates plugins through these hooks;
 * missing registrations leave plugin functionality silently unavailable.
 */

$pluginRoot = dirname(__DIR__, 2);
$setupFile  = $pluginRoot . DIRECTORY_SEPARATOR . 'setup.php';

// ---------------------------------------------------------------------------

it('setup.php exists', function () use ($setupFile) {
	expect(file_exists($setupFile))->toBeTrue('setup.php not found at plugin root');
});

it('setup.php declares plugin_npc_install()', function () use ($setupFile) {
	$source = file_get_contents($setupFile);
	expect((bool) preg_match('/function\s+plugin_npc_install\s*\(/', $source))
		->toBeTrue('plugin_npc_install() is required by the Cacti plugin framework');
});

it('setup.php declares plugin_npc_version()', function () use ($setupFile) {
	$source = file_get_contents($setupFile);
	expect((bool) preg_match('/function\s+plugin_npc_version\s*\(/', $source))
		->toBeTrue('plugin_npc_version() is required for version reporting');
});

it('setup.php declares plugin_npc_check_config()', function () use ($setupFile) {
	$source = file_get_contents($setupFile);
	expect((bool) preg_match('/function\s+plugin_npc_check_config\s*\(/', $source))
		->toBeTrue('plugin_npc_check_config() is required by the Cacti plugin framework');
});

it('setup.php registers a realm for npc.php', function () use ($setupFile) {
	$source = file_get_contents($setupFile);
	expect((bool) preg_match('/api_plugin_register_realm\s*\(\s*[\'"]npc[\'"]/', $source))
		->toBeTrue('setup.php must register a Cacti realm for the npc plugin');
});

it('setup.php registers the top_header_tabs hook', function () use ($setupFile) {
	$source = file_get_contents($setupFile);
	expect((bool) preg_match('/api_plugin_register_hook\s*\([^)]*top_header_tabs/', $source))
		->toBeTrue('top_header_tabs hook required to display the NPC navigation tab');
});

it('setup.php registers the draw_navigation_text hook', function () use ($setupFile) {
	$source = file_get_contents($setupFile);
	expect((bool) preg_match('/api_plugin_register_hook\s*\([^)]*draw_navigation_text/', $source))
		->toBeTrue('draw_navigation_text hook required for breadcrumb navigation');
});

it('setup.php registers the config_arrays hook', function () use ($setupFile) {
	$source = file_get_contents($setupFile);
	expect((bool) preg_match('/api_plugin_register_hook\s*\([^)]*config_arrays/', $source))
		->toBeTrue('config_arrays hook required for Cacti menu integration');
});

it('INFO file exists and is parseable', function () use ($pluginRoot) {
	$infoFile = $pluginRoot . DIRECTORY_SEPARATOR . 'INFO';
	expect(file_exists($infoFile))->toBeTrue('INFO file required for plugin_npc_version()');

	$info = parse_ini_file($infoFile, true);
	expect($info)->not->toBeFalse('INFO file could not be parsed as INI');
	expect(isset($info['info']))->toBeTrue('INFO file must have an [info] section');
});
