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
