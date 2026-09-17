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
	$info   = file_get_contents(realpath(__DIR__ . '/../../INFO'));

	it('defines plugin_npc_install function', function () use ($source) {
		expect($source)->toContain('function plugin_npc_install');
	});

	it('defines plugin_npc_version function', function () use ($source) {
		expect($source)->toContain('function plugin_npc_version');
	});

	it('defines plugin_npc_uninstall function', function () use ($source) {
		expect($source)->toContain('function plugin_npc_uninstall');
	});

	it('reads version info from the INFO file with a name key', function () use ($source, $info) {
		expect($source)->toContain("parse_ini_file(\$config['base_path'] . '/plugins/npc/INFO', true)");
		expect($info)->toMatch('/^name\s*=/m');
	});

	it('reads version info from the INFO file with a version key', function () use ($source, $info) {
		expect($source)->toContain("parse_ini_file(\$config['base_path'] . '/plugins/npc/INFO', true)");
		expect($info)->toMatch('/^version\s*=/m');
	});

	it('registers hooks in install function', function () use ($source) {
		expect($source)->toContain('api_plugin_register_hook');
	});
});
