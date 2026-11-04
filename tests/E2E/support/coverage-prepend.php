<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
*/

/*
 * Test helper, used as auto_prepend_file by the end-to-end tests when they are
 * asked to measure coverage. It records which lines of the plugin ran during a
 * request and writes them as JSON when the request ends.
 *
 * Inert unless a test process asked for it: it does nothing outside the CLI and
 * the built-in server, nothing unless NPC_COVERAGE_DIR names an existing
 * directory, and it only ever writes inside that directory. The file name is
 * the label below, restricted to a plain name.
 */

if ((PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server') && is_dir((string) getenv('NPC_COVERAGE_DIR')) && function_exists('xdebug_start_code_coverage') && strpos((string) ini_get('xdebug.mode'), 'coverage') !== false) {
	xdebug_start_code_coverage(XDEBUG_CC_UNUSED);

	register_shutdown_function(function () {
		$executed = array();

		foreach (xdebug_get_code_coverage() as $file => $lines) {
			if (strpos($file, '/plugins/npc/') !== false) {
				$executed[$file] = array(
					'hit' => array_keys(array_filter($lines, function ($hits) {
						return $hits > 0;
					})),
					'all' => array_keys(array_filter($lines, function ($hits) {
						return $hits !== -2;
					})),
				);
			}
		}

		xdebug_stop_code_coverage();

		$label = getenv('NPC_COVERAGE_LABEL');

		if (PHP_SAPI === 'cli-server' && isset($_SERVER['HTTP_X_NPC_TEST'])) {
			$label = $_SERVER['HTTP_X_NPC_TEST'];
		}

		$label = substr(preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) $label), 0, 120);
		$label = $label === '' ? 'unlabelled' : $label;

		file_put_contents(rtrim(getenv('NPC_COVERAGE_DIR'), '/') . '/' . $label . '-' . bin2hex(random_bytes(4)) . '.json', json_encode($executed));
	});
}
