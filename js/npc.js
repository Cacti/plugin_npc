/**
 * NPC - Nagios Plugin for Cacti
 *
 * Cacti-native jQuery UI frontend replacing ExtJS 2.2.
 * Uses Cacti's bundled jQuery and standard table patterns.
 *
 * @package npc
 */
var npc = (function($) {
	'use strict';

	var cfg = window.npcConfig || {};
	var refreshTimers = {};

	/* --- state helpers --- */

	var hostStateMap  = {0: 'Up', 1: 'Down', 2: 'Unreachable', '-1': 'Pending'};
	var hostStateClass = {0: 'hostUp', 1: 'hostDown', 2: 'hostUnreachable', '-1': 'hostPending'};

	var svcStateMap   = {0: 'Ok', 1: 'Warning', 2: 'Critical', 3: 'Unknown', '-1': 'Pending'};
	var svcStateClass = {0: 'svcOk', 1: 'svcWarning', 2: 'svcCritical', 3: 'svcUnknown', '-1': 'svcPending'};

	/* --- ajax wrapper --- */

	function npcGet(params, callback) {
		params.__csrf_magic = cfg.csrfToken;
		$.getJSON(cfg.npcUrl, params, function(data) {
			if (data && typeof callback === 'function') {
				callback(data);
			}
		});
	}

	function npcPost(params, callback) {
		params.__csrf_magic = cfg.csrfToken;
		$.post(cfg.npcUrl, params, function(data) {
			if (typeof callback === 'function') {
				callback(data);
			}
		}, 'json');
	}

	/* --- rendering helpers --- */

	function escHtml(str) {
		if (str === null || str === undefined) {
			return '';
		}
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(String(str)));
		return div.innerHTML;
	}

	function stateLabel(val, map, classMap) {
		var label = map[val] || 'Unknown';
		var cls   = classMap[val] || '';
		return '<span class="npc-state ' + cls + '">' + escHtml(label) + '</span>';
	}

	function startAutoRefresh(key, fn, interval) {
		stopAutoRefresh(key);
		var secs = interval || cfg.portletRefresh || 60;
		refreshTimers[key] = setInterval(fn, secs * 1000);
	}

	function stopAutoRefresh(key) {
		if (refreshTimers[key]) {
			clearInterval(refreshTimers[key]);
			delete refreshTimers[key];
		}
	}

	/* --- table builder --- */

	function buildTable(id, title, columns, rows, options) {
		var opts = options || {};
		var html = '';

		html += '<div id="' + id + '-box" class="cactiTable">';
		html += '<div class="cactiTableTitle"><span>' + escHtml(title) + '</span></div>';
		html += '<div class="cactiTableBody">';
		html += '<table class="cactiTable" style="width:100%;">';

		/* header */
		html += '<thead><tr class="tableHeader">';
		for (var c = 0; c < columns.length; c++) {
			html += '<th class="tableSubHeaderColumn">' + escHtml(columns[c].label) + '</th>';
		}
		html += '</tr></thead>';

		/* body */
		html += '<tbody>';
		if (rows.length === 0) {
			html += '<tr><td colspan="' + columns.length + '" style="text-align:center;padding:8px;">' +
				escHtml(opts.emptyText || 'No records found.') + '</td></tr>';
		} else {
			for (var r = 0; r < rows.length; r++) {
				var rowClass = (r % 2 === 0) ? 'odd' : 'even';
				html += '<tr class="selectable tableRow ' + rowClass + '">';
				for (var ci = 0; ci < columns.length; ci++) {
					var col = columns[ci];
					var val = rows[r][col.field];
					var rendered = (typeof col.render === 'function') ? col.render(val, rows[r]) : escHtml(val);
					html += '<td class="' + (col.cls || '') + '">' + rendered + '</td>';
				}
				html += '</tr>';
			}
		}
		html += '</tbody></table></div></div>';

		return html;
	}

	/* --- dashboard tab --- */

	function renderDashboard() {
		var html = '<div class="npc-dashboard">';
		html += '<div id="npc-summary-row" style="display:flex;gap:10px;margin-bottom:10px;"></div>';
		html += '<div style="display:flex;gap:10px;">';
		html += '<div id="npc-dash-col1" style="flex:1;"></div>';
		html += '<div id="npc-dash-col2" style="flex:1;"></div>';
		html += '</div></div>';

		$('#npc-content').html(html);

		loadHostSummary();
		loadServiceSummary();
		loadHostProblems();
		loadServiceProblems();
	}

	function loadHostSummary() {
		npcGet({module: 'hosts', action: 'summary'}, function(data) {
			var rows = (data.data) ? data.data : [];
			var html = buildTable('host-summary', 'Host Status Summary', [
				{label: 'State', field: 'label'},
				{label: 'Count', field: 'count'}
			], rows);
			$('#npc-summary-row').append('<div style="flex:1;">' + html + '</div>');
		});
	}

	function loadServiceSummary() {
		npcGet({module: 'services', action: 'summary'}, function(data) {
			var rows = (data.data) ? data.data : [];
			var html = buildTable('svc-summary', 'Service Status Summary', [
				{label: 'State', field: 'label'},
				{label: 'Count', field: 'count'}
			], rows);
			$('#npc-summary-row').append('<div style="flex:1;">' + html + '</div>');
		});
	}

	function loadHostProblems() {
		npcGet({module: 'hosts', action: 'getHosts', p_state: 'not_ok'}, function(data) {
			var rows = (data.data) ? data.data : [];
			var html = buildTable('host-problems', 'Host Problems', [
				{label: 'Host', field: 'host_name'},
				{label: 'State', field: 'current_state', render: function(v) { return stateLabel(v, hostStateMap, hostStateClass); }},
				{label: 'Output', field: 'output'},
				{label: 'Duration', field: 'last_state_change'}
			], rows);
			$('#npc-dash-col1').html(html);
		});
	}

	function loadServiceProblems() {
		npcGet({module: 'services', action: 'getServices', p_state: 'not_ok'}, function(data) {
			var rows = (data.data) ? data.data : [];
			var html = buildTable('svc-problems', 'Service Problems', [
				{label: 'Host', field: 'host_name'},
				{label: 'Service', field: 'service_description'},
				{label: 'State', field: 'current_state', render: function(v) { return stateLabel(v, svcStateMap, svcStateClass); }},
				{label: 'Output', field: 'output'}
			], rows);
			$('#npc-dash-col2').html(html);
		});
	}

	/* --- hosts tab --- */

	function renderHosts(state) {
		var filter = state || 'any';
		$('#npc-content').html(
			'<div id="npc-filter" style="margin-bottom:5px;">' +
			'<form id="npc-host-filter">' +
			'<label for="npc-host-state">' + escHtml('State:') + '</label> ' +
			'<select id="npc-host-state" name="state">' +
			'<option value="any"' + (filter === 'any' ? ' selected' : '') + '>Any</option>' +
			'<option value="up"' + (filter === 'up' ? ' selected' : '') + '>Up</option>' +
			'<option value="down"' + (filter === 'down' ? ' selected' : '') + '>Down</option>' +
			'<option value="unreachable"' + (filter === 'unreachable' ? ' selected' : '') + '>Unreachable</option>' +
			'<option value="not_ok"' + (filter === 'not_ok' ? ' selected' : '') + '>Problems</option>' +
			'</select> ' +
			'<input type="submit" value="Go" class="ui-button">' +
			'</form></div>' +
			'<div id="npc-host-table"></div>'
		);

		$('#npc-host-filter').on('submit', function(e) {
			e.preventDefault();
			loadHostTable($('#npc-host-state').val());
		});

		loadHostTable(filter);
	}

	function loadHostTable(filter) {
		npcGet({module: 'hosts', action: 'getHosts', p_state: filter}, function(data) {
			var rows = (data.data) ? data.data : [];
			var html = buildTable('hosts-grid', 'Hosts', [
				{label: 'Host Name', field: 'host_name', render: function(v, row) {
					return '<a href="#" class="npc-host-link" data-id="' + escHtml(row.host_object_id) + '">' + escHtml(v) + '</a>';
				}},
				{label: 'Address', field: 'address'},
				{label: 'State', field: 'current_state', render: function(v) { return stateLabel(v, hostStateMap, hostStateClass); }},
				{label: 'Output', field: 'output'},
				{label: 'Last Check', field: 'last_check'},
				{label: 'Actions', field: 'host_object_id', render: function(v, row) {
					return '<a href="#" class="npc-cmd-link" data-host="' + escHtml(row.host_name) +
						'" title="Commands">&#9881;</a>';
				}}
			], rows);
			$('#npc-host-table').html(html);

			$('#npc-host-table').off('click', '.npc-host-link').on('click', '.npc-host-link', function(e) {
				e.preventDefault();
				showHostDetail($(this).data('id'));
			});

			$('#npc-host-table').off('click', '.npc-cmd-link').on('click', '.npc-cmd-link', function(e) {
				e.preventDefault();
				showHostCommandDialog($(this).data('host'));
			});
		});

		startAutoRefresh('hosts', function() { loadHostTable(filter); });
	}

	/* --- services tab --- */

	function renderServices(state) {
		var filter = state || 'any';
		$('#npc-content').html(
			'<div id="npc-filter" style="margin-bottom:5px;">' +
			'<form id="npc-svc-filter">' +
			'<label for="npc-svc-state">' + escHtml('State:') + '</label> ' +
			'<select id="npc-svc-state" name="state">' +
			'<option value="any"' + (filter === 'any' ? ' selected' : '') + '>Any</option>' +
			'<option value="ok"' + (filter === 'ok' ? ' selected' : '') + '>Ok</option>' +
			'<option value="warning"' + (filter === 'warning' ? ' selected' : '') + '>Warning</option>' +
			'<option value="critical"' + (filter === 'critical' ? ' selected' : '') + '>Critical</option>' +
			'<option value="unknown"' + (filter === 'unknown' ? ' selected' : '') + '>Unknown</option>' +
			'<option value="not_ok"' + (filter === 'not_ok' ? ' selected' : '') + '>Problems</option>' +
			'</select> ' +
			'<input type="submit" value="Go" class="ui-button">' +
			'</form></div>' +
			'<div id="npc-svc-table"></div>'
		);

		$('#npc-svc-filter').on('submit', function(e) {
			e.preventDefault();
			loadServiceTable($('#npc-svc-state').val());
		});

		loadServiceTable(filter);
	}

	function loadServiceTable(filter) {
		npcGet({module: 'services', action: 'getServices', p_state: filter}, function(data) {
			var rows = (data.data) ? data.data : [];
			var html = buildTable('svc-grid', 'Services', [
				{label: 'Host', field: 'host_name'},
				{label: 'Service', field: 'service_description', render: function(v, row) {
					return '<a href="#" class="npc-svc-link" data-id="' + escHtml(row.service_object_id) + '">' + escHtml(v) + '</a>';
				}},
				{label: 'State', field: 'current_state', render: function(v) { return stateLabel(v, svcStateMap, svcStateClass); }},
				{label: 'Output', field: 'output'},
				{label: 'Last Check', field: 'last_check'},
				{label: 'Actions', field: 'service_object_id', render: function(v, row) {
					return '<a href="#" class="npc-svc-cmd-link" data-host="' + escHtml(row.host_name) +
						'" data-svc="' + escHtml(row.service_description) + '" title="Commands">&#9881;</a>';
				}}
			], rows);
			$('#npc-svc-table').html(html);

			$('#npc-svc-table').off('click', '.npc-svc-link').on('click', '.npc-svc-link', function(e) {
				e.preventDefault();
				showServiceDetail($(this).data('id'));
			});

			$('#npc-svc-table').off('click', '.npc-svc-cmd-link').on('click', '.npc-svc-cmd-link', function(e) {
				e.preventDefault();
				showServiceCommandDialog($(this).data('host'), $(this).data('svc'));
			});
		});

		startAutoRefresh('services', function() { loadServiceTable(filter); });
	}

	/* --- comments tab --- */

	function renderComments() {
		$('#npc-content').html('<div id="npc-comments-table"></div>');

		npcGet({module: 'comments', action: 'getHostComments'}, function(data) {
			var rows = (data.data) ? data.data : [];
			var html = buildTable('host-comments', 'Host Comments', [
				{label: 'Host', field: 'host_name'},
				{label: 'Author', field: 'author_name'},
				{label: 'Comment', field: 'comment_data'},
				{label: 'Entry Time', field: 'comment_time'},
				{label: 'Persistent', field: 'is_persistent', render: function(v) { return v == 1 ? 'Yes' : 'No'; }},
				{label: 'Actions', field: 'internal_comment_id', render: function(v) {
					return '<a href="#" class="npc-del-comment" data-id="' + escHtml(v) + '" title="Delete">&#10005;</a>';
				}}
			], rows);
			$('#npc-comments-table').html(html);

			npcGet({module: 'comments', action: 'getServiceComments'}, function(sdata) {
				var srows = (sdata.data) ? sdata.data : [];
				var shtml = buildTable('svc-comments', 'Service Comments', [
					{label: 'Host', field: 'host_name'},
					{label: 'Service', field: 'service_description'},
					{label: 'Author', field: 'author_name'},
					{label: 'Comment', field: 'comment_data'},
					{label: 'Entry Time', field: 'comment_time'}
				], srows);
				$('#npc-comments-table').append(shtml);
			});

			$('#npc-comments-table').off('click', '.npc-del-comment').on('click', '.npc-del-comment', function(e) {
				e.preventDefault();
				var commentId = $(this).data('id');
				if (confirm('Delete this comment?')) {
					npcPost({module: 'nagios', action: 'command', p_command: 'DEL_HOST_COMMENT', p_comment_id: commentId}, function() {
						renderComments();
					});
				}
			});
		});
	}

	/* --- downtime tab --- */

	function renderDowntime() {
		$('#npc-content').html('<div id="npc-downtime-table"></div>');

		npcGet({module: 'downtime', action: 'getHostDowntime'}, function(data) {
			var rows = (data.data) ? data.data : [];
			var html = buildTable('host-downtime', 'Host Downtime', [
				{label: 'Host', field: 'host_name'},
				{label: 'Author', field: 'author_name'},
				{label: 'Comment', field: 'comment_data'},
				{label: 'Start', field: 'scheduled_start_time'},
				{label: 'End', field: 'scheduled_end_time'},
				{label: 'Fixed', field: 'is_fixed', render: function(v) { return v == 1 ? 'Yes' : 'No'; }}
			], rows);
			$('#npc-downtime-table').html(html);
		});

		npcGet({module: 'downtime', action: 'getServiceDowntime'}, function(data) {
			var rows = (data.data) ? data.data : [];
			var html = buildTable('svc-downtime', 'Service Downtime', [
				{label: 'Host', field: 'host_name'},
				{label: 'Service', field: 'service_description'},
				{label: 'Author', field: 'author_name'},
				{label: 'Comment', field: 'comment_data'},
				{label: 'Start', field: 'scheduled_start_time'},
				{label: 'End', field: 'scheduled_end_time'}
			], rows);
			$('#npc-downtime-table').append(html);
		});
	}

	/* --- event log tab --- */

	function renderEventLog() {
		$('#npc-content').html('<div id="npc-eventlog-table"></div>');

		npcGet({module: 'logentries', action: 'getLogs'}, function(data) {
			var rows = (data.data) ? data.data : [];
			var html = buildTable('eventlog', 'Event Log', [
				{label: 'Entry Time', field: 'entry_time'},
				{label: 'Log Entry', field: 'logentry_data'}
			], rows);
			$('#npc-eventlog-table').html(html);
		});

		startAutoRefresh('eventlog', renderEventLog);
	}

	/* --- process info tab --- */

	function renderProcessInfo() {
		$('#npc-content').html('<div id="npc-process-table"></div>');

		npcGet({module: 'nagios', action: 'getProcessInfoGrid'}, function(data) {
			var rows = (data.data) ? data.data : [];
			var html = buildTable('process-info', 'Nagios Process Information', [
				{label: 'Property', field: 'key'},
				{label: 'Value', field: 'value'}
			], rows);
			$('#npc-process-table').html(html);
		});
	}

	/* --- host detail dialog --- */

	function showHostDetail(hostObjectId) {
		npcGet({module: 'hosts', action: 'getHosts', p_id: hostObjectId}, function(data) {
			var rows = (data.data) ? data.data : [];
			if (rows.length === 0) {
				return;
			}
			var host = rows[0];

			var html = '<table class="cactiTable" style="width:100%;">';
			var fields = [
				['Host Name', 'host_name'], ['Address', 'address'],
				['State', 'current_state'], ['Output', 'output'],
				['Last Check', 'last_check'], ['Next Check', 'next_check'],
				['Last State Change', 'last_state_change'],
				['Notifications', 'notifications_enabled'],
				['Active Checks', 'active_checks_enabled'],
				['Acknowledged', 'problem_has_been_acknowledged']
			];

			for (var i = 0; i < fields.length; i++) {
				var val = host[fields[i][1]];
				if (fields[i][1] === 'current_state') {
					val = hostStateMap[val] || val;
				}
				html += '<tr class="' + ((i % 2 === 0) ? 'odd' : 'even') + '">' +
					'<td style="font-weight:bold;width:200px;">' + escHtml(fields[i][0]) + '</td>' +
					'<td>' + escHtml(val) + '</td></tr>';
			}
			html += '</table>';

			var $dlg = $('<div title="Host Detail: ' + escHtml(host.host_name) + '">' + html + '</div>');
			$dlg.dialog({
				width: 600,
				modal: true,
				buttons: {
					'Close': function() { $(this).dialog('close'); }
				},
				close: function() { $(this).remove(); }
			});
		});
	}

	/* --- service detail dialog --- */

	function showServiceDetail(svcObjectId) {
		npcGet({module: 'services', action: 'getServices', p_id: svcObjectId}, function(data) {
			var rows = (data.data) ? data.data : [];
			if (rows.length === 0) {
				return;
			}
			var svc = rows[0];

			var html = '<table class="cactiTable" style="width:100%;">';
			var fields = [
				['Host Name', 'host_name'], ['Service', 'service_description'],
				['State', 'current_state'], ['Output', 'output'],
				['Last Check', 'last_check'], ['Next Check', 'next_check'],
				['Last State Change', 'last_state_change'],
				['Notifications', 'notifications_enabled'],
				['Active Checks', 'active_checks_enabled'],
				['Acknowledged', 'problem_has_been_acknowledged']
			];

			for (var i = 0; i < fields.length; i++) {
				var val = svc[fields[i][1]];
				if (fields[i][1] === 'current_state') {
					val = svcStateMap[val] || val;
				}
				html += '<tr class="' + ((i % 2 === 0) ? 'odd' : 'even') + '">' +
					'<td style="font-weight:bold;width:200px;">' + escHtml(fields[i][0]) + '</td>' +
					'<td>' + escHtml(val) + '</td></tr>';
			}
			html += '</table>';

			var $dlg = $('<div title="Service Detail: ' + escHtml(svc.service_description) + '">' + html + '</div>');
			$dlg.dialog({
				width: 600,
				modal: true,
				buttons: {
					'Close': function() { $(this).dialog('close'); }
				},
				close: function() { $(this).remove(); }
			});
		});
	}

	/* --- command dialogs --- */

	function showHostCommandDialog(hostName) {
		var html = '<form id="npc-host-cmd-form">' +
			'<input type="hidden" name="module" value="nagios">' +
			'<input type="hidden" name="action" value="command">' +
			'<table class="cactiTable" style="width:100%;">' +
			'<tr><td><label for="npc-hcmd">Command:</label></td>' +
			'<td><select id="npc-hcmd" name="p_command">' +
			'<option value="ACKNOWLEDGE_HOST_PROBLEM">Acknowledge Problem</option>' +
			'<option value="ADD_HOST_COMMENT">Add Comment</option>' +
			'<option value="SCHEDULE_HOST_DOWNTIME">Schedule Downtime</option>' +
			'<option value="SCHEDULE_HOST_CHECK">Schedule Check</option>' +
			'<option value="ENABLE_HOST_NOTIFICATIONS">Enable Notifications</option>' +
			'<option value="DISABLE_HOST_NOTIFICATIONS">Disable Notifications</option>' +
			'</select></td></tr>' +
			'<tr><td><label>Host:</label></td>' +
			'<td><input type="text" name="p_host_name" value="' + escHtml(hostName) + '" readonly class="ui-state-disabled" style="width:250px;"></td></tr>' +
			'<tr><td><label for="npc-hcmd-author">Author:</label></td>' +
			'<td><input type="text" id="npc-hcmd-author" name="p_author" value="' + escHtml(cfg.userName) + '" style="width:250px;"></td></tr>' +
			'<tr><td><label for="npc-hcmd-comment">Comment:</label></td>' +
			'<td><textarea id="npc-hcmd-comment" name="p_comment" rows="3" style="width:250px;"></textarea></td></tr>' +
			'</table></form>';

		var $dlg = $('<div title="Host Command: ' + escHtml(hostName) + '">' + html + '</div>');
		$dlg.dialog({
			width: 500,
			modal: true,
			buttons: {
				'Submit': function() {
					var formData = {};
					$('#npc-host-cmd-form').serializeArray().forEach(function(item) {
						formData[item.name] = item.value;
					});
					npcPost(formData, function(resp) {
						if (resp && !resp.success) {
							alert(resp.msg || 'Command failed');
						}
					});
					$(this).dialog('close');
				},
				'Cancel': function() { $(this).dialog('close'); }
			},
			close: function() { $(this).remove(); }
		});
	}

	function showServiceCommandDialog(hostName, svcDesc) {
		var html = '<form id="npc-svc-cmd-form">' +
			'<input type="hidden" name="module" value="nagios">' +
			'<input type="hidden" name="action" value="command">' +
			'<table class="cactiTable" style="width:100%;">' +
			'<tr><td><label for="npc-scmd">Command:</label></td>' +
			'<td><select id="npc-scmd" name="p_command">' +
			'<option value="ACKNOWLEDGE_SVC_PROBLEM">Acknowledge Problem</option>' +
			'<option value="ADD_SVC_COMMENT">Add Comment</option>' +
			'<option value="SCHEDULE_SVC_DOWNTIME">Schedule Downtime</option>' +
			'<option value="SCHEDULE_SVC_CHECK">Schedule Check</option>' +
			'<option value="ENABLE_SVC_NOTIFICATIONS">Enable Notifications</option>' +
			'<option value="DISABLE_SVC_NOTIFICATIONS">Disable Notifications</option>' +
			'</select></td></tr>' +
			'<tr><td><label>Host:</label></td>' +
			'<td><input type="text" name="p_host_name" value="' + escHtml(hostName) + '" readonly class="ui-state-disabled" style="width:250px;"></td></tr>' +
			'<tr><td><label>Service:</label></td>' +
			'<td><input type="text" name="p_service_description" value="' + escHtml(svcDesc) + '" readonly class="ui-state-disabled" style="width:250px;"></td></tr>' +
			'<tr><td><label for="npc-scmd-author">Author:</label></td>' +
			'<td><input type="text" id="npc-scmd-author" name="p_author" value="' + escHtml(cfg.userName) + '" style="width:250px;"></td></tr>' +
			'<tr><td><label for="npc-scmd-comment">Comment:</label></td>' +
			'<td><textarea id="npc-scmd-comment" name="p_comment" rows="3" style="width:250px;"></textarea></td></tr>' +
			'</table></form>';

		var $dlg = $('<div title="Service Command: ' + escHtml(svcDesc) + '">' + html + '</div>');
		$dlg.dialog({
			width: 500,
			modal: true,
			buttons: {
				'Submit': function() {
					var formData = {};
					$('#npc-svc-cmd-form').serializeArray().forEach(function(item) {
						formData[item.name] = item.value;
					});
					npcPost(formData, function(resp) {
						if (resp && !resp.success) {
							alert(resp.msg || 'Command failed');
						}
					});
					$(this).dialog('close');
				},
				'Cancel': function() { $(this).dialog('close'); }
			},
			close: function() { $(this).remove(); }
		});
	}

	/* --- tab routing --- */

	function route(tab) {
		/* stop any running refresh timers */
		for (var key in refreshTimers) {
			stopAutoRefresh(key);
		}

		switch (tab) {
			case 'hosts':
				renderHosts();
				break;
			case 'services':
				renderServices();
				break;
			case 'comments':
				renderComments();
				break;
			case 'downtime':
				renderDowntime();
				break;
			case 'eventlog':
				renderEventLog();
				break;
			case 'process':
				renderProcessInfo();
				break;
			default:
				renderDashboard();
				break;
		}
	}

	/* --- init --- */

	$(function() {
		route(cfg.currentTab);
	});

	/* public API for extensibility */
	return {
		route: route,
		npcGet: npcGet,
		npcPost: npcPost,
		showHostDetail: showHostDetail,
		showServiceDetail: showServiceDetail
	};

})(jQuery);
