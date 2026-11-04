<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Unit coverage for controllers/layout.php: the frame that carries the NPC
 * tabs and the script block handing the current tab to js/npc.js.
 */

beforeAll(function () {
	require_once dirname(__DIR__, 2) . '/setup.php';
	require_once dirname(__DIR__, 2) . '/controllers/controller.php';
	npc_test_load(dirname(__DIR__, 2) . '/controllers/layout.php');

	if (!function_exists('general_header')) {
		function general_header() {
		}
	}

	if (!function_exists('bottom_footer')) {
		function bottom_footer() {
		}
	}

	if (!function_exists('csrf_get_tokens')) {
		function csrf_get_tokens() {
			return 'token';
		}
	}
});

afterEach(function () {
	unset($_GET['tab'], $_SESSION['sess_user_id']);
});

function npc_layout_output($tab) {
	$_SESSION['sess_user_id'] = 1;

	if ($tab !== null) {
		$_GET['tab'] = $tab;
	}

	ob_start();

	try {
		(new NpcLayoutController())->drawFrame(array('config' => $GLOBALS['config']));
	} finally {
		$html = ob_get_clean();
	}

	return $html;
}

it('hands a known tab name to the page script', function () {
	expect(npc_layout_output('hosts'))->toContain("currentTab:       'hosts'");
});

it('falls back to the dashboard when no tab is requested', function () {
	expect(npc_layout_output(null))->toContain("currentTab:       'dashboard'");
});

it('never writes an unknown or malformed tab value into the script block', function ($tab) {
	$html = npc_layout_output($tab);

	expect($html)->toContain("currentTab:       'dashboard'");
	expect($html)->not->toContain('breakout');
})->with(array(
	'markup that closes the script block' => array("x</script><b>breakout</b>"),
	'quote and statement'                  => array("x';breakout();'"),
	'array value'                          => array(array('breakout')),
));

it('writes empty strings for settings that were never saved', function () {
	$GLOBALS['__test_config'] = array('npc_date_format' => null, 'npc_time_format' => null, 'npc_nagios_url' => null);

	try {
		$html = npc_layout_output('hosts');
	} finally {
		$GLOBALS['__test_config'] = array();
	}

	expect($html)->toContain("dateFormat:       ''");
	expect($html)->toContain("timeFormat:       ''");
	expect($html)->toContain("nagiosUrl:        ''");
});

it('escapes quotes in the saved date format and Nagios URL', function () {
	$GLOBALS['__test_config'] = array('npc_date_format' => "Y'm", 'npc_time_format' => 'H:i', 'npc_nagios_url' => "https://nagios.example/'x");

	try {
		$html = npc_layout_output('hosts');
	} finally {
		$GLOBALS['__test_config'] = array();
	}

	expect($html)->toContain("dateFormat:       'Y\\'m'");
	expect($html)->toContain("nagiosUrl:        'https://nagios.example/\\'x'");
});
