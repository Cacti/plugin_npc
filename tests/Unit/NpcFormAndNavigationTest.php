<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for npc_draw_navigation_text(), npc_config_form(),
 * npc_api_device_save(), and npc_page_head() in setup.php.
 */

beforeAll(function () {
	require_once __DIR__ . '/../../setup.php';
});

beforeEach(function () {
	$_POST = array();
});

it('adds the npc breadcrumb entries without disturbing existing ones', function () {
	$nav = npc_draw_navigation_text(array('other.php:' => array('title' => 'Other')));

	expect($nav)->toHaveKey('other.php:');
	expect($nav)->toHaveKey('npc.php:');
	expect($nav)->toHaveKey('statusDetail.php:');
	expect($nav)->toHaveKey('extinfo.php:');
	expect($nav)->toHaveKey('command.php:');
});

it('inserts the Nagios host mapping field immediately after disabled', function () {
	$GLOBALS['fields_host_edit'] = array(
		'name'     => array('friendly_name' => 'Description'),
		'disabled' => array('friendly_name' => 'Disabled'),
		'notes'    => array('friendly_name' => 'Notes'),
	);

	npc_config_form();

	$keys = array_keys($GLOBALS['fields_host_edit']);

	expect($keys)->toBe(array('name', 'disabled', 'npc_host_object_id', 'notes'));
	expect($GLOBALS['fields_host_edit']['npc_host_object_id']['method'])->toBe('drop_sql');
});

it('validates and defaults the npc_host_object_id on device save', function () {
	$save = npc_api_device_save(array('id' => 1));
	expect($save['npc_host_object_id'])->toBe('');

	$_POST['npc_host_object_id'] = '42';
	$save = npc_api_device_save(array('id' => 1));
	expect($save['npc_host_object_id'])->toBe('42');
});

it('prints no meaningful header output', function () {
	ob_start();
	npc_page_head();
	$output = ob_get_clean();

	expect(trim($output))->toBe('');
});
