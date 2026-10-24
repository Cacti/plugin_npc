'use strict';

/*
 * Runs js/npc.js against a minimal stand-in for jQuery and the DOM, so the
 * rendering code can be exercised without a browser. The stand-in records the
 * HTML written to each selector, the requests sent, the dialogs opened and the
 * event handlers registered.
 *
 * Text escaping follows what a browser does for a text node: & < > only, quotes
 * stay as they are.
 */

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const source = fs.readFileSync(path.join(__dirname, '../../js/npc.js'), 'utf8');

function load(options) {
	const opts = options || {};
	const env = {
		html: {},
		requests: [],
		dialogs: [],
		handlers: {},
		alerts: []
	};
	const ready = [];

	function wrap(target) {
		const sel = typeof target === 'string' ? target : null;
		const obj = {
			html(value) {
				if (value === undefined) {
					return env.html[sel] || '';
				}
				env.html[sel] = value;
				return obj;
			},
			append(value) {
				env.html[sel] = (env.html[sel] || '') + value;
				return obj;
			},
			off() {
				return obj;
			},
			on(event, a, b) {
				env.handlers[(sel || 'element') + ' ' + event + ' ' + (typeof a === 'string' ? a : '')] = typeof a === 'function' ? a : b;
				return obj;
			},
			val() {
				return (opts.values || {})[sel];
			},
			data(name) {
				return target && target.__data ? target.__data[name] : undefined;
			},
			find(selector) {
				return wrap(selector);
			},
			each(fn) {
				((opts.elements || {})[sel] || []).forEach((element, index) => fn.call(element, index, element));
				return obj;
			},
			toggle(show) {
				if (target && typeof target === 'object') {
					target.__shown = show;
				}
				return obj;
			},
			serializeArray() {
				return (opts.forms || {})[sel] || [];
			},
			dialog(settings) {
				if (typeof settings === 'object') {
					env.dialogs.push({markup: target, settings});
				}
				return obj;
			},
			remove() {
				return obj;
			}
		};
		return obj;
	}

	function jq(target) {
		if (typeof target === 'function') {
			ready.push(target);
			return undefined;
		}
		return wrap(target);
	}

	jq.getJSON = function(url, params, callback) {
		env.requests.push({method: 'GET', params});
		const key = params.module + '/' + params.action;
		if (opts.responses && key in opts.responses) {
			callback(opts.responses[key]);
		}
	};

	jq.post = function(url, params, callback) {
		env.requests.push({method: 'POST', params});
		callback(opts.postResponse || {success: true});
	};

	const context = {
		jQuery: jq,
		window: {
			npcConfig: Object.assign({npcUrl: 'npc.php', csrfToken: 'token', userName: 'admin', portletRefresh: 60}, opts.config || {})
		},
		document: {
			createElement() {
				return {
					appendChild(node) {
						this.text = node.text;
					},
					get innerHTML() {
						return String(this.text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
					}
				};
			},
			createTextNode(text) {
				return {text};
			}
		},
		setInterval() {
			return 1;
		},
		clearInterval() {},
		confirm() {
			return true;
		},
		alert(message) {
			env.alerts.push(message);
		},
		console
	};

	vm.createContext(context);
	vm.runInContext(source, context);
	env.npc = context.npc;
	env.ready = function() {
		ready.forEach((fn) => fn());
	};

	return env;
}

module.exports = {load};
