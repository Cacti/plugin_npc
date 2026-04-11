<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
*/

describe('username output escaping helper behavior', function () {
	it('escapes html metacharacters in usernames', function () {
		$payload = '<img src=x onerror=alert(1)>';
		$escaped = html_escape($payload);

		expect($escaped)->not->toContain('<img');
		expect($escaped)->toContain('&lt;img');
	});
});
