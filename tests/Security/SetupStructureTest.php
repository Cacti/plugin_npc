<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

describe('npc setup.php structure', function () {
	$source = file_get_contents(realpath(__DIR__ . '/../../setup.php'));

	$infoFile = parse_ini_file(realpath(__DIR__ . '/../../INFO'), true);
	if (!is_array($infoFile) || !isset($infoFile['info']) || !is_array($infoFile['info'])) {
		throw new RuntimeException('Unable to parse the INFO section');
	}
	$info = $infoFile['info'];

	it('defines plugin_npc_install function', function () use ($source) {
		expect($source)->toContain('function plugin_npc_install');
	});

	it('defines plugin_npc_version function', function () use ($source) {
		expect($source)->toContain('function plugin_npc_version');
	});

	it('defines plugin_npc_uninstall function', function () use ($source) {
		expect($source)->toContain('function plugin_npc_uninstall');
	});

	it('declares a plugin name in INFO', function () use ($source, $info) {
		expect($source)->toContain("parse_ini_file(\$config['base_path'] . '/plugins/npc/INFO', true)");
		expect($info)->toHaveKey('name');
	});

	it('declares a plugin version in INFO', function () use ($source, $info) {
		expect($source)->toContain("parse_ini_file(\$config['base_path'] . '/plugins/npc/INFO', true)");
		expect($info)->toHaveKey('version');
	});

	it('registers hooks in install function', function () use ($source) {
		expect($source)->toContain('api_plugin_register_hook');
	});
});
