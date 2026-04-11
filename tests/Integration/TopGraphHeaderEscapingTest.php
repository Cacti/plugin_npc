<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

describe('top graph header output wiring', function () {
	it('escapes the logged in username before rendering', function () {
		$path = realpath(__DIR__ . '/../../top_graph_header.php');
		expect($path)->not->toBeFalse();

		$contents = file_get_contents($path);
		expect($contents)->not->toBeFalse();

		expect($contents)->toContain('html_escape(db_fetch_cell("select username from user_auth where id=" . $_SESSION["sess_user_id"]))');
	});
});
