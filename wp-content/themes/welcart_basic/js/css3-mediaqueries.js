/*
css3-mediaqueries.js - CSS Helper and CSS3 Media Queries Enabler

author: Wouter van der Graaf <wouter at dynora nl>
version: 1.0 (20110330)
license: MIT
website: http://code.google.com/p/css3-mediaqueries-js/

W3C spec: http://www.w3.org/TR/css3-mediaqueries/

Note: use of embedded <style> is not recommended when using media queries, because IE  has no way of returning the raw literal css text from a <style> element.
*/

// true prototypal inheritance (http://javascript.crockford.com/prototypal.html)
if (typeof Object.create !== 'function') {
	Object.create = function (o) {
		function F() {}
		F.prototype = o;
		return new F();
	};
}

// user agent sniffing shortcuts
const ua = {
	toString() {
		return navigator.userAgent;
	},
	test(s) {
		return this.toString().toLowerCase().indexOf(s.toLowerCase()) > -1;
	},
};
ua.version = (ua
	.toString()
	.toLowerCase()
	.match(/[\s\S]+(?:rv|it|ra|ie)[\/: ]([\d.]+)/) || [])[1];
ua.webkit = ua.test('webkit');
ua.gecko = ua.test('gecko') && !ua.webkit;
ua.opera = ua.test('opera');
ua.ie = ua.test('msie') && !ua.opera;
ua.ie6 =
	ua.ie &&
	document.compatMode &&
	typeof document.documentElement.style.maxHeight === 'undefined';
ua.ie7 =
	ua.ie &&
	document.documentElement &&
	typeof document.documentElement.style.maxHeight !== 'undefined' &&
	typeof XDomainRequest === 'undefined';
ua.ie8 = ua.ie && typeof XDomainRequest !== 'undefined';

// initialize when DOM content is loaded
const domReady = (function () {
	const fns = [];
	const init = function () {
		if (!arguments.callee.done) {
			// run init functions once
			arguments.callee.done = true;
			for (let i = 0; i < fns.length; i++) {
				fns[i]();
			}
		}
	};

	// listeners for different browsers
	if (document.addEventListener) {
		document.addEventListener('DOMContentLoaded', init, false);
	}
	if (ua.ie) {
		(function () {
			try {
				// throws errors until after ondocumentready
				document.documentElement.doScroll('left');

				// If we are in an iframe, the above does not work properly.
				// Trying to access the length attribute of document.body, however,
				// does throw an error until ondocumentready, fixing this issue.
				document.body.length;
			} catch (e) {
				setTimeout(arguments.callee, 50);
				return;
			}
			// no errors, fire
			init();
		})();
		// trying to always fire before onload
		document.onreadystatechange = function () {
			if (document.readyState === 'complete') {
				document.onreadystatechange = null;
				init();
			}
		};
	}
	if (ua.webkit && document.readyState) {
		(function () {
			if (document.readyState !== 'loading') {
				init();
			} else {
				setTimeout(arguments.callee, 10);
			}
		})();
	}
	window.onload = init; // fallback

	return function (fn) {
		// add fn to init functions
		if (typeof fn === 'function') {
			// If DOM ready has already been fired, fire the function
			// right away.
			if (init.done) {
				fn();
			} else {
				// Add to the queue
				fns[fns.length] = fn;
			}
		}
		return fn;
	};
})();

// helper library for parsing css to objects
var cssHelper = (function () {
	const regExp = {
		BLOCKS: /[^\s{;][^{;]*\{(?:[^{}]*\{[^{}]*\}[^{}]*|[^{}]*)*\}/g,
		BLOCKS_INSIDE: /[^\s{][^{]*\{[^{}]*\}/g,
		DECLARATIONS: /[a-zA-Z\-]+[^;]*:[^;]+;/g,
		RELATIVE_URLS: /url\(['"]?([^\/\)'"][^:\)'"]+)['"]?\)/g,
		// strip whitespace and comments, @import is evil
		REDUNDANT_COMPONENTS:
			/(?:\/\*([^*\\\\]|\*(?!\/))+\*\/|@import[^;]+;|@-moz-document\s*url-prefix\(\)\s*{(([^{}])+{([^{}])+}([^{}])+)+})/g,
		REDUNDANT_WHITESPACE: /\s*(,|:|;|\{|\})\s*/g,
		WHITESPACE_IN_PARENTHESES: /\(\s*(\S*)\s*\)/g,
		MORE_WHITESPACE: /\s{2,}/g,
		FINAL_SEMICOLONS: /;\}/g,
		NOT_WHITESPACE: /\S+/g,
	};

	let parsed,
		parsing = false;

	const waiting = [];
	const wait = function (fn) {
		if (typeof fn === 'function') {
			waiting[waiting.length] = fn;
		}
	};
	const ready = function () {
		for (let i = 0; i < waiting.length; i++) {
			waiting[i](parsed);
		}
	};
	const events = {};
	const broadcast = function (n, v) {
		if (events[n]) {
			const listeners = events[n].listeners;
			if (listeners) {
				for (let i = 0; i < listeners.length; i++) {
					listeners[i](v);
				}
			}
		}
	};

	const requestText = function (url, fnSuccess, fnFailure) {
		if (ua.ie && !window.XMLHttpRequest) {
			window.XMLHttpRequest = function () {
				return new ActiveXObject('Microsoft.XMLHTTP');
			};
		}
		if (!XMLHttpRequest) {
			return '';
		}
		let r = new XMLHttpRequest();
		try {
			r.open('get', url, true);
			r.setRequestHeader('X_REQUESTED_WITH', 'XMLHttpRequest');
		} catch (e) {
			fnFailure();
			return;
		}
		let done = false;
		setTimeout(function () {
			done = true;
		}, 5000);
		document.documentElement.style.cursor = 'progress';
		r.onreadystatechange = function () {
			if (r.readyState === 4 && !done) {
				if (
					(!r.status && location.protocol === 'file:') ||
					(r.status >= 200 && r.status < 300) ||
					r.status === 304 ||
					(navigator.userAgent.indexOf('Safari') > -1 &&
						typeof r.status === 'undefined')
				) {
					fnSuccess(r.responseText);
				} else {
					fnFailure();
				}
				document.documentElement.style.cursor = '';
				r = null; // avoid memory leaks
			}
		};
		r.send('');
	};

	const sanitize = function (text) {
		text = text.replace(regExp.REDUNDANT_COMPONENTS, '');
		text = text.replace(regExp.REDUNDANT_WHITESPACE, '$1');
		text = text.replace(regExp.WHITESPACE_IN_PARENTHESES, '($1)');
		text = text.replace(regExp.MORE_WHITESPACE, ' ');
		text = text.replace(regExp.FINAL_SEMICOLONS, '}'); // optional final semicolons
		return text;
	};

	var objects = {
		stylesheet(el) {
			const o = {};
			let amqs = [],
				mqls = [],
				rs = [],
				rsw = [];
			const s = el.cssHelperText;

			// add attribute media queries
			const attr = el.getAttribute('media');
			if (attr) {
				var qts = attr.toLowerCase().split(',');
			} else {
				var qts = ['all']; // imply 'all'
			}
			for (var i = 0; i < qts.length; i++) {
				amqs[amqs.length] = objects.mediaQuery(qts[i], o);
			}

			// add media query lists and rules (top down order)
			const blocks = s.match(regExp.BLOCKS); // @charset is not a block
			if (blocks !== null) {
				for (var i = 0; i < blocks.length; i++) {
					if (blocks[i].substring(0, 7) === '@media ') {
						// media query (list)
						const mql = objects.mediaQueryList(blocks[i], o);
						rs = rs.concat(mql.getRules());
						mqls[mqls.length] = mql;
					} else {
						// regular rule set, page context (@page) or font description (@font-face)
						rs[rs.length] = rsw[rsw.length] = objects.rule(
							blocks[i],
							o,
							null
						);
					}
				}
			}

			o.element = el;
			o.getCssText = function () {
				return s;
			};
			o.getAttrMediaQueries = function () {
				return amqs;
			};
			o.getMediaQueryLists = function () {
				return mqls;
			};
			o.getRules = function () {
				return rs;
			};
			o.getRulesWithoutMQ = function () {
				return rsw;
			};
			return o;
		},

		mediaQueryList(s, stsh) {
			const o = {};
			const idx = s.indexOf('{');
			const lt = s.substring(0, idx);
			s = s.substring(idx + 1, s.length - 1);
			const mqs = [],
				rs = [];

			// add media queries
			const qts = lt.toLowerCase().substring(7).split(',');
			for (var i = 0; i < qts.length; i++) {
				// parse each media query
				mqs[mqs.length] = objects.mediaQuery(qts[i], o);
			}

			// add rule sets
			const rts = s.match(regExp.BLOCKS_INSIDE);
			if (rts !== null) {
				for (i = 0; i < rts.length; i++) {
					rs[rs.length] = objects.rule(rts[i], stsh, o);
				}
			}

			o.type = 'mediaQueryList';
			o.getMediaQueries = function () {
				return mqs;
			};
			o.getRules = function () {
				return rs;
			};
			o.getListText = function () {
				return lt;
			};
			o.getCssText = function () {
				return s;
			};
			return o;
		},

		mediaQuery(s, listOrSheet) {
			s = s || '';
			let mql, stsh;
			if (listOrSheet.type === 'mediaQueryList') {
				mql = listOrSheet;
			} else {
				stsh = listOrSheet;
			}
			let not = false,
				type;
			const expr = [];
			const valid = true;
			const tokens = s.match(regExp.NOT_WHITESPACE);

			for (let i = 0; i < tokens.length; i++) {
				const token = tokens[i];
				if (!type && (token === 'not' || token === 'only')) {
					// 'not' and 'only' keywords
					// keyword 'only' does nothing, as if it was not present
					if (token === 'not') {
						not = true;
					}
				} else if (!type) {
					// media type
					type = token;
				} else if (token.charAt(0) === '(') {
					// media feature expression
					const pair = token
						.substring(1, token.length - 1)
						.split(':');
					expr[expr.length] = {
						mediaFeature: pair[0],
						value: pair[1] || null,
					};
				}
			}

			return {
				getQueryText() {
					return s;
				},
				getAttrStyleSheet() {
					return stsh || null;
				},
				getList() {
					return mql || null;
				},
				getValid() {
					return valid;
				},
				getNot() {
					return not;
				},
				getMediaType() {
					return type;
				},
				getExpressions() {
					return expr;
				},
			};
		},

		rule(s, stsh, mql) {
			const o = {};
			const idx = s.indexOf('{');
			const st = s.substring(0, idx);
			const ss = st.split(',');
			const ds = [];
			const dts = s.substring(idx + 1, s.length - 1).split(';');
			for (let i = 0; i < dts.length; i++) {
				ds[ds.length] = objects.declaration(dts[i], o);
			}

			o.getStylesheet = function () {
				return stsh || null;
			};
			o.getMediaQueryList = function () {
				return mql || null;
			};
			o.getSelectors = function () {
				return ss;
			};
			o.getSelectorText = function () {
				return st;
			};
			o.getDeclarations = function () {
				return ds;
			};
			o.getPropertyValue = function (n) {
				for (let i = 0; i < ds.length; i++) {
					if (ds[i].getProperty() === n) {
						return ds[i].getValue();
					}
				}
				return null;
			};
			return o;
		},

		declaration(s, r) {
			const idx = s.indexOf(':');
			const p = s.substring(0, idx);
			const v = s.substring(idx + 1);
			return {
				getRule() {
					return r || null;
				},
				getProperty() {
					return p;
				},
				getValue() {
					return v;
				},
			};
		},
	};

	const parseText = function (el) {
		if (typeof el.cssHelperText !== 'string') {
			return;
		}
		const o = {
			stylesheet: null,
			mediaQueryLists: [],
			rules: [],
			selectors: {},
			declarations: [],
			properties: {},
		};

		// build stylesheet object
		const stsh = (o.stylesheet = objects.stylesheet(el));

		// collect media query lists
		const mqls = (o.mediaQueryLists = stsh.getMediaQueryLists());

		// collect all rules
		const ors = (o.rules = stsh.getRules());

		// collect all selectors
		const oss = o.selectors;
		const collectSelectors = function (r) {
			const ss = r.getSelectors();
			for (let i = 0; i < ss.length; i++) {
				const n = ss[i];
				if (!oss[n]) {
					oss[n] = [];
				}
				oss[n][oss[n].length] = r;
			}
		};
		for (i = 0; i < ors.length; i++) {
			collectSelectors(ors[i]);
		}

		// collect all declarations
		let ods = o.declarations;
		for (i = 0; i < ors.length; i++) {
			ods = o.declarations = ods.concat(ors[i].getDeclarations());
		}

		// collect all properties
		const ops = o.properties;
		for (i = 0; i < ods.length; i++) {
			const n = ods[i].getProperty();
			if (!ops[n]) {
				ops[n] = [];
			}
			ops[n][ops[n].length] = ods[i];
		}

		el.cssHelperParsed = o;
		parsed[parsed.length] = el;
		return o;
	};

	const parseEmbedded = function (el, s) {
		return;
		// This function doesn't work because of a bug in IE, where innerHTML gives us parsed css instead of raw literal.
		el.cssHelperText = sanitize(s || el.innerHTML);
		return parseText(el);
	};

	const parse = function () {
		parsing = true;
		parsed = [];
		const linked = [];
		const finish = function () {
			for (var i = 0; i < linked.length; i++) {
				parseText(linked[i]);
			}
			const styles = document.getElementsByTagName('style');
			for (i = 0; i < styles.length; i++) {
				parseEmbedded(styles[i]);
			}
			parsing = false;
			ready();
		};
		const links = document.getElementsByTagName('link');
		for (var i = 0; i < links.length; i++) {
			const link = links[i];
			if (
				link.getAttribute('rel').indexOf('style') > -1 &&
				link.href &&
				link.href.length !== 0 &&
				!link.disabled
			) {
				linked[linked.length] = link;
			}
		}
		if (linked.length > 0) {
			let c = 0;
			const checkForFinish = function () {
				c++;
				if (c === linked.length) {
					// parse in right order, so after last link is read
					finish();
				}
			};
			const processLink = function (link) {
				const href = link.href;
				requestText(
					href,
					function (text) {
						// fix url's
						text = sanitize(text).replace(
							regExp.RELATIVE_URLS,
							'url(' +
								href.substring(0, href.lastIndexOf('/')) +
								'/$1)'
						);
						link.cssHelperText = text;
						checkForFinish();
					},
					checkForFinish
				);
			};
			for (i = 0; i < linked.length; i++) {
				processLink(linked[i]);
			}
		} else {
			finish();
		}
	};

	const types = {
		stylesheets: 'array',
		mediaQueryLists: 'array',
		rules: 'array',
		selectors: 'object',
		declarations: 'array',
		properties: 'object',
	};

	const collections = {
		stylesheets: null,
		mediaQueryLists: null,
		rules: null,
		selectors: null,
		declarations: null,
		properties: null,
	};

	const addToCollection = function (name, v) {
		if (collections[name] !== null) {
			if (types[name] === 'array') {
				return (collections[name] = collections[name].concat(v));
			}

			const c = collections[name];
			for (const n in v) {
				if (v.hasOwnProperty(n)) {
					if (!c[n]) {
						c[n] = v[n];
					} else {
						c[n] = c[n].concat(v[n]);
					}
				}
			}
			return c;
		}
	};

	const collect = function (name) {
		collections[name] = types[name] === 'array' ? [] : {};
		for (let i = 0; i < parsed.length; i++) {
			const pname = name === 'stylesheets' ? 'stylesheet' : name; // the exception
			addToCollection(name, parsed[i].cssHelperParsed[pname]);
		}
		return collections[name];
	};

	// viewport size
	const getViewportSize = function (d) {
		if (typeof window.innerWidth !== 'undefined') {
			return window['inner' + d];
		} else if (
			typeof document.documentElement !== 'undefined' &&
			typeof document.documentElement.clientWidth !== 'undefined' &&
			document.documentElement.clientWidth != 0
		) {
			return document.documentElement['client' + d];
		}
	};

	// public static functions
	return {
		addStyle(s, mediaTypes, process) {
			let el;
			let styleElId = 'css-mediaqueries-js';
			let styleMedia = '';

			const styleEl = document.getElementById(styleElId);

			if (mediaTypes && mediaTypes.length > 0) {
				styleMedia = mediaTypes.join(',');
				styleElId += styleMedia;
			}

			if (null !== styleEl) {
				el = styleEl;
			} else {
				el = document.createElement('style');
				el.setAttribute('type', 'text/css');
				el.setAttribute('id', styleElId);
				el.setAttribute('media', styleMedia);
				document.getElementsByTagName('head')[0].appendChild(el);
			}

			if (el.styleSheet) {
				// IE
				el.styleSheet.cssText += s;
			} else {
				el.appendChild(document.createTextNode(s));
			}

			el.addedWithCssHelper = true;

			if (typeof process === 'undefined' || process === true) {
				cssHelper.parsed(function (parsed) {
					const o = parseEmbedded(el, s);
					for (const n in o) {
						if (o.hasOwnProperty(n)) {
							addToCollection(n, o[n]);
						}
					}
					broadcast('newStyleParsed', el);
				});
			} else {
				el.parsingDisallowed = true;
			}
			return el;
		},

		removeStyle(el) {
			if (el.parentNode) return el.parentNode.removeChild(el);
		},

		parsed(fn) {
			if (parsing) {
				wait(fn);
			} else if (typeof parsed !== 'undefined') {
				if (typeof fn === 'function') {
					fn(parsed);
				}
			} else {
				wait(fn);
				parse();
			}
		},

		stylesheets(fn) {
			cssHelper.parsed(function (parsed) {
				fn(collections.stylesheets || collect('stylesheets'));
			});
		},

		mediaQueryLists(fn) {
			cssHelper.parsed(function (parsed) {
				fn(collections.mediaQueryLists || collect('mediaQueryLists'));
			});
		},

		rules(fn) {
			cssHelper.parsed(function (parsed) {
				fn(collections.rules || collect('rules'));
			});
		},

		selectors(fn) {
			cssHelper.parsed(function (parsed) {
				fn(collections.selectors || collect('selectors'));
			});
		},

		declarations(fn) {
			cssHelper.parsed(function (parsed) {
				fn(collections.declarations || collect('declarations'));
			});
		},

		properties(fn) {
			cssHelper.parsed(function (parsed) {
				fn(collections.properties || collect('properties'));
			});
		},

		broadcast,

		addListener(n, fn) {
			// in case n is 'styleadd': added function is called everytime style is added and parsed
			if (typeof fn === 'function') {
				if (!events[n]) {
					events[n] = {
						listeners: [],
					};
				}
				events[n].listeners[events[n].listeners.length] = fn;
			}
		},

		removeListener(n, fn) {
			if (typeof fn === 'function' && events[n]) {
				const ls = events[n].listeners;
				for (let i = 0; i < ls.length; i++) {
					if (ls[i] === fn) {
						ls.splice(i, 1);
						i -= 1;
					}
				}
			}
		},

		getViewportWidth() {
			return getViewportSize('Width');
		},

		getViewportHeight() {
			return getViewportSize('Height');
		},
	};
})();

// function to test and apply parsed media queries against browser capabilities
domReady(
	(function enableCssMediaQueries() {
		let meter;

		const regExp = {
			LENGTH_UNIT: /[0-9]+(em|ex|px|in|cm|mm|pt|pc)$/,
			RESOLUTION_UNIT: /[0-9]+(dpi|dpcm)$/,
			ASPECT_RATIO: /^[0-9]+\/[0-9]+$/,
			ABSOLUTE_VALUE: /^[0-9]*(\.[0-9]+)*$/,
		};

		let styles = [];

		var nativeSupport = function () {
			// check support for media queries
			const id = 'css3-mediaqueries-test';
			const el = document.createElement('div');
			el.id = id;
			const style = cssHelper.addStyle(
				'@media all and (width) { #' +
					id +
					' { width: 1px !important; } }',
				[],
				false
			); // false means don't parse this temp style
			document.body.appendChild(el);
			const ret = el.offsetWidth === 1;
			style.parentNode.removeChild(style);
			el.parentNode.removeChild(el);
			nativeSupport = function () {
				return ret;
			};
			return ret;
		};

		const createMeter = function () {
			// create measuring element
			meter = document.createElement('div');
			meter.style.cssText =
				'position:absolute;top:-9999em;left:-9999em;' +
				'margin:0;border:none;padding:0;width:1em;font-size:1em;'; // cssText is needed for IE, works for the others
			document.body.appendChild(meter);
			// meter must have browser default font size of 16px
			if (meter.offsetWidth !== 16) {
				meter.style.fontSize = 16 / meter.offsetWidth + 'em';
			}
			meter.style.width = '';
		};

		const measure = function (value) {
			meter.style.width = value;
			const amount = meter.offsetWidth;
			meter.style.width = '';
			return amount;
		};

		const testMediaFeature = function (feature, value) {
			// non-testable features: monochrome|min-monochrome|max-monochrome|scan|grid
			let l = feature.length;
			let min = feature.substring(0, 4) === 'min-';
			let max = !min && feature.substring(0, 4) === 'max-';

			if (value !== null) {
				// determine value type and parse to usable amount
				var valueType;
				var amount;
				if (regExp.LENGTH_UNIT.exec(value)) {
					valueType = 'length';
					amount = measure(value);
				} else if (regExp.RESOLUTION_UNIT.exec(value)) {
					valueType = 'resolution';
					amount = parseInt(value, 10);
					var unit = value.substring((amount + '').length);
				} else if (regExp.ASPECT_RATIO.exec(value)) {
					valueType = 'aspect-ratio';
					amount = value.split('/');
				} else if (regExp.ABSOLUTE_VALUE) {
					valueType = 'absolute';
					amount = value;
				} else {
					valueType = 'unknown';
				}
			}

			let width, height;
			if ('device-width' === feature.substring(l - 12, l)) {
				// screen width
				width = screen.width;
				if (value !== null) {
					if (valueType === 'length') {
						return (
							(min && width >= amount) ||
							(max && width < amount) ||
							(!min && !max && width === amount)
						);
					}

					return false;
				}
				// test width without value
				return width > 0;
			} else if ('device-height' === feature.substring(l - 13, l)) {
				// screen height
				height = screen.height;
				if (value !== null) {
					if (valueType === 'length') {
						return (
							(min && height >= amount) ||
							(max && height < amount) ||
							(!min && !max && height === amount)
						);
					}

					return false;
				}
				// test height without value
				return height > 0;
			} else if ('width' === feature.substring(l - 5, l)) {
				// viewport width
				width =
					document.documentElement.clientWidth ||
					document.body.clientWidth; // the latter for IE quirks mode
				if (value !== null) {
					if (valueType === 'length') {
						return (
							(min && width >= amount) ||
							(max && width < amount) ||
							(!min && !max && width === amount)
						);
					}

					return false;
				}
				// test width without value
				return width > 0;
			} else if ('height' === feature.substring(l - 6, l)) {
				// viewport height
				height =
					document.documentElement.clientHeight ||
					document.body.clientHeight; // the latter for IE quirks mode
				if (value !== null) {
					if (valueType === 'length') {
						return (
							(min && height >= amount) ||
							(max && height < amount) ||
							(!min && !max && height === amount)
						);
					}

					return false;
				}
				// test height without value
				return height > 0;
			} else if ('orientation' === feature.substring(l - 11, l)) {
				// orientation

				width =
					document.documentElement.clientWidth ||
					document.body.clientWidth; // the latter for IE quirks mode
				height =
					document.documentElement.clientHeight ||
					document.body.clientHeight; // the latter for IE quirks mode

				if (valueType === 'absolute') {
					return amount === 'portrait'
						? width <= height
						: width > height;
				}

				return false;
			} else if ('aspect-ratio' === feature.substring(l - 12, l)) {
				// window aspect ratio
				width =
					document.documentElement.clientWidth ||
					document.body.clientWidth; // the latter for IE quirks mode
				height =
					document.documentElement.clientHeight ||
					document.body.clientHeight; // the latter for IE quirks mode

				let curRatio = width / height;
				const ratio = amount[1] / amount[0];

				if (valueType === 'aspect-ratio') {
					return (
						(min && curRatio >= ratio) ||
						(max && curRatio < ratio) ||
						(!min && !max && curRatio === ratio)
					);
				}

				return false;
			} else if ('device-aspect-ratio' === feature.substring(l - 19, l)) {
				// screen aspect ratio
				return (
					valueType === 'aspect-ratio' &&
					screen.width * amount[1] === screen.height * amount[0]
				);
			} else if ('color-index' === feature.substring(l - 11, l)) {
				// number of colors
				let colors = Math.pow(2, screen.colorDepth);
				if (value !== null) {
					if (valueType === 'absolute') {
						return (
							(min && colors >= amount) ||
							(max && colors < amount) ||
							(!min && !max && colors === amount)
						);
					}

					return false;
				}
				// test height without value
				return colors > 0;
			} else if ('color' === feature.substring(l - 5, l)) {
				// bits per color component
				let color = screen.colorDepth;
				if (value !== null) {
					if (valueType === 'absolute') {
						return (
							(min && color >= amount) ||
							(max && color < amount) ||
							(!min && !max && color === amount)
						);
					}

					return false;
				}
				// test height without value
				return color > 0;
			} else if ('resolution' === feature.substring(l - 10, l)) {
				let res;
				if (unit === 'dpcm') {
					res = measure('1cm');
				} else {
					res = measure('1in');
				}
				if (value !== null) {
					if (valueType === 'resolution') {
						return (
							(min && res >= amount) ||
							(max && res < amount) ||
							(!min && !max && res === amount)
						);
					}

					return false;
				}
				// test height without value
				return res > 0;
			}

			return false;
		};

		const testMediaQuery = function (mq) {
			let test = mq.getValid();
			const expressions = mq.getExpressions();
			const l = expressions.length;
			if (l > 0) {
				for (let i = 0; i < l && test; i++) {
					test = testMediaFeature(
						expressions[i].mediaFeature,
						expressions[i].value
					);
				}
				const not = mq.getNot();
				return (test && !not) || (not && !test);
			}
			return test;
		};

		const testMediaQueryList = function (mql, ts) {
			// ts is null or an array with any media type but 'all'.
			const mqs = mql.getMediaQueries();
			const t = {};
			for (let i = 0; i < mqs.length; i++) {
				const type = mqs[i].getMediaType();
				if (mqs[i].getExpressions().length === 0) {
					continue;
					// TODO: Browser check! Assuming old browsers do apply the bare media types, even in a list with media queries.
				}
				let typeAllowed = true;
				if (type !== 'all' && ts && ts.length > 0) {
					typeAllowed = false;
					for (let j = 0; j < ts.length; j++) {
						if (ts[j] === type) {
							typeAllowed = true;
						}
					}
				}
				if (typeAllowed && testMediaQuery(mqs[i])) {
					t[type] = true;
				}
			}
			let s = [],
				c = 0;
			for (const n in t) {
				if (t.hasOwnProperty(n)) {
					if (c > 0) {
						s[c++] = ',';
					}
					s[c++] = n;
				}
			}
			if (s.length > 0) {
				styles[styles.length] = cssHelper.addStyle(
					'@media ' + s.join('') + '{' + mql.getCssText() + '}',
					ts,
					false
				);
			}
		};

		const testMediaQueryLists = function (mqls, ts) {
			for (let i = 0; i < mqls.length; i++) {
				testMediaQueryList(mqls[i], ts);
			}
		};

		const testStylesheet = function (stsh) {
			const amqs = stsh.getAttrMediaQueries();
			let allPassed = false;
			const t = {};
			for (let i = 0; i < amqs.length; i++) {
				if (testMediaQuery(amqs[i])) {
					t[amqs[i].getMediaType()] =
						amqs[i].getExpressions().length > 0;
				}
			}
			const ts = [],
				tswe = [];
			for (const n in t) {
				if (t.hasOwnProperty(n)) {
					ts[ts.length] = n;
					if (t[n]) {
						tswe[tswe.length] = n;
					}
					if (n === 'all') {
						allPassed = true;
					}
				}
			}
			if (tswe.length > 0) {
				// types with query expressions that passed the test
				styles[styles.length] = cssHelper.addStyle(
					stsh.getCssText(),
					tswe,
					false
				);
			}
			const mqls = stsh.getMediaQueryLists();
			if (allPassed) {
				// If 'all' in media attribute passed the test, then test all @media types in linked CSS and create style with those types.
				testMediaQueryLists(mqls);
			} else {
				// Or else, test only media attribute types that passed the test and also 'all'.
				// For positive '@media all', create style with attribute types that passed their test.
				testMediaQueryLists(mqls, ts);
			}
		};

		const testStylesheets = function (stshs) {
			for (let i = 0; i < stshs.length; i++) {
				testStylesheet(stshs[i]);
			}
			if (ua.ie) {
				// force repaint in IE
				document.documentElement.style.display = 'block';
				setTimeout(function () {
					document.documentElement.style.display = '';
				}, 0);
				// delay broadcast somewhat for IE
				setTimeout(function () {
					cssHelper.broadcast('cssMediaQueriesTested');
				}, 100);
			} else {
				cssHelper.broadcast('cssMediaQueriesTested');
			}
		};

		const test = function () {
			for (let i = 0; i < styles.length; i++) {
				cssHelper.removeStyle(styles[i]);
			}
			styles = [];
			cssHelper.stylesheets(testStylesheets);
		};

		let scrollbarWidth = 0;
		const checkForResize = function () {
			let cvpw = cssHelper.getViewportWidth();
			let cvph = cssHelper.getViewportHeight();

			// determine scrollbar width in IE, see resizeHandler
			if (ua.ie) {
				const el = document.createElement('div');
				el.style.position = 'absolute';
				el.style.top = '-9999em';
				el.style.overflow = 'scroll';
				document.body.appendChild(el);
				scrollbarWidth = el.offsetWidth - el.clientWidth;
				document.body.removeChild(el);
			}

			let timer;
			const resizeHandler = function () {
				const vpw = cssHelper.getViewportWidth();
				const vph = cssHelper.getViewportHeight();
				// check whether vp size has really changed, because IE also triggers resize event when body size changes
				// 20px allowance to accomodate short appearance of scrollbars in IE in some cases
				if (
					Math.abs(vpw - cvpw) > scrollbarWidth ||
					Math.abs(vph - cvph) > scrollbarWidth
				) {
					cvpw = vpw;
					cvph = vph;
					clearTimeout(timer);
					timer = setTimeout(function () {
						if (!nativeSupport()) {
							test();
						} else {
							cssHelper.broadcast('cssMediaQueriesTested');
						}
					}, 500);
				}
			};

			window.onresize = (function () {
				const x = window.onresize || function () {}; // save original
				return function () {
					x();
					resizeHandler();
				};
			})();
		};

		// prevent jumping of layout by hiding everything before painting <body>
		const docEl = document.documentElement;
		docEl.style.marginLeft = '-32767px';

		// make sure it comes back after a while
		setTimeout(function () {
			docEl.style.marginLeft = '';
		}, 5000);

		return function () {
			if (!nativeSupport()) {
				// if browser doesn't support media queries
				cssHelper.addListener('newStyleParsed', function (el) {
					testStylesheet(el.cssHelperParsed.stylesheet);
				});
				// return visibility after media queries are tested
				cssHelper.addListener('cssMediaQueriesTested', function () {
					// force repaint in IE by changing width
					if (ua.ie) {
						docEl.style.width = '1px';
					}
					setTimeout(function () {
						docEl.style.width = ''; // undo width
						docEl.style.marginLeft = ''; // undo hide
					}, 0);
					// remove this listener to prevent following execution
					cssHelper.removeListener(
						'cssMediaQueriesTested',
						arguments.callee
					);
				});
				createMeter();
				test();
			} else {
				docEl.style.marginLeft = ''; // undo visibility hidden
			}
			checkForResize();
		};
	})()
);

// bonus: hotfix for IE6 SP1 (bug KB823727)
try {
	document.execCommand('BackgroundImageCache', false, true);
} catch (e) {}
