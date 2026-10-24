'use strict';

const test = require('node:test');
const assert = require('node:assert');
const {load} = require('./harness');

const hostRows = [{host_object_id: 10, host_name: 'web1', address: '192.0.2.10', current_state: 1, output: 'down', last_check: 'now'}];

function gridResponse(rows) {
	return {response: {value: {items: rows, total_count: rows.length, version: 1}}};
}

test('lists hosts from the grid response shape', () => {
	const env = load({config: {currentTab: 'hosts'}, responses: {'hosts/getHosts': gridResponse(hostRows)}});

	env.npc.route('hosts');

	assert.match(env.html['#npc-host-table'], /web1/);
	assert.match(env.html['#npc-host-table'], /192\.0\.2\.10/);
});

test('lists hosts from the data response shape', () => {
	const env = load({responses: {'hosts/getHosts': {totalCount: 1, data: hostRows}}});

	env.npc.route('hosts');

	assert.match(env.html['#npc-host-table'], /web1/);
});

test('shows the empty message when a response has no rows', () => {
	const env = load({responses: {'hosts/getHosts': gridResponse([])}});

	env.npc.route('hosts');

	assert.match(env.html['#npc-host-table'], /No records found/);
});

test('lists services, comments and log entries from the grid response shape', () => {
	const env = load({responses: {
		'services/getServices': gridResponse([{host_name: 'web1', service_description: 'Load', service_object_id: 3, current_state: 2, output: 'high'}]),
		'comments/getHostComments': gridResponse([{host_name: 'web1', author_name: 'ann', comment_data: 'looking', comment_time: 't', is_persistent: 1, internal_comment_id: 4}]),
		'comments/getServiceComments': gridResponse([{host_name: 'web1', service_description: 'Load', author_name: 'bob', comment_data: 'checked', comment_time: 't'}]),
		'logentries/getLogs': gridResponse([{entry_time: 't', logentry_data: 'restart requested'}])
	}});

	env.npc.route('services');
	assert.match(env.html['#npc-svc-table'], /Load/);

	env.npc.route('comments');
	assert.match(env.html['#npc-comments-table'], /looking/);
	assert.match(env.html['#npc-comments-table'], /checked/);

	env.npc.route('eventlog');
	assert.match(env.html['#npc-eventlog-table'], /restart requested/);
});

test('renders the state counts of the host and service summaries', () => {
	const env = load({responses: {
		'hosts/summary': {totalCount: 1, data: [{down: 2, unreachable: 0, up: 9, pending: 1}]},
		'services/summary': {totalCount: 1, data: [{critical: 3, warning: 4, unknown: 0, ok: 40, pending: 0}]},
		'hosts/getHosts': gridResponse([]),
		'services/getServices': gridResponse([])
	}});

	env.npc.route('dashboard');

	const hosts = env.html['#npc-summary-row'];
	assert.match(hosts, /<td class="">Down<\/td><td class="">2<\/td>/);
	assert.match(hosts, /<td class="">Up<\/td><td class="">9<\/td>/);
	assert.match(hosts, /<td class="">Critical<\/td><td class="">3<\/td>/);
	assert.match(hosts, /<td class="">Ok<\/td><td class="">40<\/td>/);
});

test('shows the property names of the process information', () => {
	const env = load({responses: {'nagios/getProcessInfoGrid': {totalCount: 2, data: [
		{name: 'Nagios Version', value: '4.5'},
		{name: 'Process ID', value: '321'}
	]}}});

	env.npc.route('process');

	assert.match(env.html['#npc-process-table'], /<td class="">Nagios Version<\/td><td class="">4\.5<\/td>/);
	assert.match(env.html['#npc-process-table'], /<td class="">Process ID<\/td><td class="">321<\/td>/);
});

/* Values built inside the vm context have that context's prototypes. */
function plain(value) {
	return JSON.parse(JSON.stringify(value));
}

test('rowsOf accepts either shape and nothing else', () => {
	const env = load({});

	assert.deepStrictEqual(plain(env.npc.rowsOf({data: [1]})), [1]);
	assert.deepStrictEqual(plain(env.npc.rowsOf(gridResponse([2]))), [2]);
	assert.deepStrictEqual(plain(env.npc.rowsOf({data: 'x'})), []);
	assert.deepStrictEqual(plain(env.npc.rowsOf(null)), []);
});

const quoted = 'a"b\'c';

test('escapes quotes in values placed inside attributes', () => {
	const env = load({});

	assert.strictEqual(env.npc.escAttr(quoted), 'a&quot;b&#39;c');
	assert.strictEqual(env.npc.escAttr('<b>&'), '&lt;b&gt;&amp;');
});

test('keeps a host name with quotes inside its attributes in the host grid', () => {
	const rows = [{host_object_id: 10, host_name: quoted, address: 'x', current_state: 0, output: '', last_check: ''}];
	const env  = load({responses: {'hosts/getHosts': gridResponse(rows)}});

	env.npc.route('hosts');

	assert.match(env.html['#npc-host-table'], /data-host="a&quot;b&#39;c"/);
	assert.ok(!env.html['#npc-host-table'].includes('data-host="a"'));
});

test('keeps a service description with quotes inside its attributes in the service grid', () => {
	const rows = [{host_name: 'web1', service_description: quoted, service_object_id: 3, current_state: 0, output: ''}];
	const env  = load({responses: {'services/getServices': gridResponse(rows)}});

	env.npc.route('services');

	assert.match(env.html['#npc-svc-table'], /data-svc="a&quot;b&#39;c"/);
});

test('keeps the user name with quotes inside the value attribute of the command dialog', () => {
	const rows = [{host_object_id: 10, host_name: 'web1', address: 'x', current_state: 0, output: '', last_check: ''}];
	const env  = load({config: {userName: quoted}, responses: {'hosts/getHosts': gridResponse(rows)}});

	env.npc.route('hosts');
	env.handlers['#npc-host-table click .npc-cmd-link'].call({__data: {host: 'web1'}}, {preventDefault() {}});

	assert.match(env.dialogs[0].markup, /name="p_author" value="a&quot;b&#39;c"/);
});

test('names the kind of each command the dialogs offer', () => {
	const env = load({});
	const kinds = {
		ACKNOWLEDGE_HOST_PROBLEM: 'ack',
		ACKNOWLEDGE_SVC_PROBLEM: 'ack',
		ADD_HOST_COMMENT: 'comment',
		ADD_SVC_COMMENT: 'comment',
		SCHEDULE_HOST_DOWNTIME: 'downtime',
		SCHEDULE_SVC_DOWNTIME: 'downtime',
		SCHEDULE_HOST_CHECK: 'check',
		SCHEDULE_SVC_CHECK: 'check',
		ENABLE_HOST_NOTIFICATIONS: 'plain',
		DISABLE_SVC_NOTIFICATIONS: 'plain'
	};

	Object.keys(kinds).forEach((cmd) => assert.strictEqual(env.npc.commandKind(cmd), kinds[cmd], cmd));
});

test('completes an acknowledgement with its flags', () => {
	const env  = load({});
	const base = {p_command: 'ACKNOWLEDGE_HOST_PROBLEM', p_host_name: 'web1', p_author: 'ann', p_comment: 'x'};

	assert.deepStrictEqual(plain(env.npc.buildCommandParams(Object.assign({p_sticky: '1', p_notify: '1'}, base), 100)),
		Object.assign({p_sticky: 1, p_notify: 1, p_persistent: 0}, base));
	assert.deepStrictEqual(plain(env.npc.buildCommandParams(base, 100)),
		Object.assign({p_sticky: 0, p_notify: 0, p_persistent: 0}, base));
});

test('completes a comment with its persistence flag', () => {
	const env  = load({});
	const base = {p_command: 'ADD_SVC_COMMENT', p_host_name: 'web1', p_service_description: 'Load', p_author: 'ann', p_comment: 'x'};

	assert.strictEqual(env.npc.buildCommandParams(Object.assign({p_persistent: '1'}, base), 100).p_persistent, 1);
	assert.strictEqual(env.npc.buildCommandParams(base, 100).p_persistent, 0);
});

test('completes a downtime with a fixed window starting now', () => {
	const env    = load({});
	const params = plain(env.npc.buildCommandParams({p_command: 'SCHEDULE_HOST_DOWNTIME', p_host_name: 'web1', duration_minutes: '30'}, 1000));

	assert.deepStrictEqual(params, {
		p_command: 'SCHEDULE_HOST_DOWNTIME',
		p_host_name: 'web1',
		p_start_time: 1000,
		p_end_time: 2800,
		p_fixed: 1,
		p_trigger_id: 0,
		p_duration: 1800
	});
	assert.strictEqual(env.npc.buildCommandParams({p_command: 'SCHEDULE_SVC_DOWNTIME', duration_minutes: ''}, 1000).p_duration, 3600);
	assert.strictEqual(env.npc.buildCommandParams({p_command: 'SCHEDULE_SVC_DOWNTIME', duration_minutes: '-5'}, 1000).p_duration, 3600);
});

test('completes a check with the time to run it', () => {
	const env = load({});

	assert.strictEqual(env.npc.buildCommandParams({p_command: 'SCHEDULE_HOST_CHECK', p_host_name: 'web1'}, 1000).p_check_time, 1000);
});

test('leaves the other commands as the form sent them', () => {
	const env  = load({});
	const form = {p_command: 'ENABLE_HOST_NOTIFICATIONS', p_host_name: 'web1', p_sticky: '1'};

	assert.deepStrictEqual(plain(env.npc.buildCommandParams(form, 1000)), {p_command: 'ENABLE_HOST_NOTIFICATIONS', p_host_name: 'web1'});
});

function openHostDialog(extra) {
	const rows = [{host_object_id: 10, host_name: 'web1', address: 'x', current_state: 0, output: '', last_check: ''}];
	const env  = load(Object.assign({responses: {'hosts/getHosts': gridResponse(rows)}}, extra));

	env.npc.route('hosts');
	env.handlers['#npc-host-table click .npc-cmd-link'].call({__data: {host: 'web1'}}, {preventDefault() {}});

	return env;
}

test('sends the extra values when a host acknowledgement is submitted', () => {
	const env = openHostDialog({forms: {'#npc-host-cmd-form': [
		{name: 'module', value: 'nagios'},
		{name: 'action', value: 'command'},
		{name: 'p_command', value: 'ACKNOWLEDGE_HOST_PROBLEM'},
		{name: 'p_host_name', value: 'web1'},
		{name: 'p_author', value: 'admin'},
		{name: 'p_comment', value: 'looking'},
		{name: 'p_sticky', value: '1'}
	]}});

	env.dialogs[0].settings.buttons.Submit.call({dialog() {}});

	const post = env.requests.filter((request) => request.method === 'POST')[0].params;
	assert.strictEqual(post.p_sticky, 1);
	assert.strictEqual(post.p_notify, 0);
	assert.strictEqual(post.p_persistent, 0);
	assert.strictEqual(post.module, 'nagios');
	assert.strictEqual(post.action, 'command');
});

test('shows only the options that fit the selected command', () => {
	const elements = [{__data: {kinds: 'ack'}}, {__data: {kinds: 'ack comment'}}, {__data: {kinds: 'downtime'}}];

	openHostDialog({
		values: {'select[name="p_command"]': 'ADD_HOST_COMMENT'},
		elements: {'.npc-opt': elements}
	});

	assert.deepStrictEqual(elements.map((element) => element.__shown), [false, true, false]);
});

test('reports a failed command to the user', () => {
	const env = openHostDialog({
		postResponse: {success: false, msg: 'Missing required parameter: persistent'},
		forms: {'#npc-host-cmd-form': [{name: 'p_command', value: 'ENABLE_HOST_NOTIFICATIONS'}]}
	});

	env.dialogs[0].settings.buttons.Submit.call({dialog() {}});

	assert.deepStrictEqual(env.alerts, ['Missing required parameter: persistent']);
});
