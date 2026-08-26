(function () {
	'use strict';

	const boot = window.MPTYZen || {};
	const classifier = window.MPTYZenClassifier;
	const settings = boot.settings || {};
	const revealKey = boot.revealKey || 'mpty_zen_reveal_v1';
	const hiddenClass = 'mpty-zen-suppressed';
	const MAX_TEXT = 7000;
	const featureCache = new WeakMap();
	const decisionCache = new WeakMap();
	const pendingNodes = new Set();
	let batchScheduled = false;
	let observer = null;

	const neverHideSelectors = [
		'.mpty-zen-wrap',
		'#wpadminbar',
		'#adminmenu',
		'#adminmenuback',
		'#adminmenuwrap',
		'#screen-meta',
		'#screen-meta-links',
		'.update-nag',
		'.maintenance-nag',
		'.notice-error',
		'.error',
		'[role="alert"]',
		'.plugin-update-tr',
		'.plugin-card',
		'.media-modal',
		'.components-modal__frame',
		'[role="dialog"]'
	].join(',');

	const protectedDescendantSelectors = [
		'.wp-list-table',
		'.tablenav',
		'#posts-filter',
		'#bulk-action-selector-top',
		'form[action="options.php"]',
		'.media-modal',
		'.components-modal__frame',
		'[role="dialog"]'
	].join(',');

	function candidateSelector() {
		return [
			'.notice',
			'.updated',
			'[class*="notice" i]',
			'[class*="nag" i]',
			'[class*="promo" i]',
			'[class*="upsell" i]',
			'[class*="marketing" i]',
			'[class*="advert" i]',
			'[class*="offer" i]',
			'[class*="banner" i]',
			'[id*="promo" i]',
			'[id*="banner" i]',
			'#dashboard-widgets .postbox'
		].join(',');
	}

	function normalizeText(value) {
		return String(value || '')
			.replace(/[\u200B-\u200D\uFEFF]/g, '')
			.replace(/\s+/g, ' ')
			.trim();
	}

	function readSessionValue(key, fallback) {
		try {
			const value = sessionStorage.getItem(key);
			return value === null ? fallback : value;
		} catch (error) {
			return fallback;
		}
	}

	function writeSessionValue(key, value) {
		try {
			sessionStorage.setItem(key, value);
		} catch (error) {
			// Storage can be unavailable in hardened/private browser contexts.
		}
	}

	function removeSessionValue(key) {
		try {
			sessionStorage.removeItem(key);
		} catch (error) {
			// Storage can be unavailable in hardened/private browser contexts.
		}
	}

	function isRevealEnabled() {
		return readSessionValue(revealKey, '0') === '1';
	}

	function isNeverHide(element) {
		if (!element || element.nodeType !== 1) {
			return true;
		}

		if (element.matches && element.matches(neverHideSelectors)) {
			return true;
		}

		if (element.closest && element.closest('.mpty-zen-wrap,#wpadminbar,#adminmenuwrap,#screen-meta')) {
			return true;
		}

		return Boolean(element.querySelector && element.querySelector(protectedDescendantSelectors));
	}

	function isExternalUrl(value) {
		if (!/^https?:\/\//i.test(value)) {
			return false;
		}

		try {
			return new URL(value, document.baseURI).origin !== window.location.origin;
		} catch (error) {
			return false;
		}
	}

	function collectLinks(element) {
		const output = [];
		if (!element.querySelectorAll) {
			return output;
		}

		for (const node of element.querySelectorAll('a[href],button,input[type="button"],input[type="submit"]')) {
			if (output.length >= 12) {
				break;
			}

			const href = (node.getAttribute && node.getAttribute('href')) || '';
			output.push({
				href: href,
				label: normalizeText(node.textContent || node.value || (node.getAttribute && node.getAttribute('aria-label'))),
				meta: normalizeText([
					node.id || '',
					typeof node.className === 'string' ? node.className : '',
					node.getAttribute && node.getAttribute('name'),
					node.getAttribute && node.getAttribute('data-action'),
					node.getAttribute && node.getAttribute('aria-label')
				].join(' ')).toLowerCase(),
				external: isExternalUrl(href)
			});
		}

		return output;
	}

	function collectImages(element) {
		const output = [];
		if (!element.querySelectorAll) {
			return output;
		}

		for (const node of element.querySelectorAll('img[src]')) {
			if (output.length >= 8) {
				break;
			}

			const src = node.getAttribute('src') || '';
			output.push({
				src: src,
				alt: normalizeText(node.getAttribute('alt') || ''),
				width: Number(node.getAttribute('width')) || Number(node.naturalWidth) || 0,
				height: Number(node.getAttribute('height')) || Number(node.naturalHeight) || 0,
				external: isExternalUrl(src)
			});
		}

		return output;
	}

	function makeFeatures(element) {
		const text = normalizeText(element.textContent || '');
		const attr = normalizeText([
			element.id || '',
			typeof element.className === 'string' ? element.className : '',
			element.getAttribute && element.getAttribute('data-plugin'),
			element.getAttribute && element.getAttribute('data-slug'),
			element.getAttribute && element.getAttribute('aria-label')
		].join(' ')).toLowerCase();
		const links = collectLinks(element);
		const images = collectImages(element);
		const signature = [
			text.slice(0, 500),
			attr.slice(0, 220),
			links.map((item) => item.href + '|' + item.label + '|' + item.meta).join('~').slice(0, 500),
			images.map((item) => item.src + '|' + item.width + 'x' + item.height).join('~').slice(0, 400)
		].join('|');
		const cached = featureCache.get(element);

		if (cached && cached.signature === signature) {
			return cached.features;
		}

		const features = {
			text: text,
			attr: attr,
			links: links,
			images: images,
			isNotice: Boolean(element.matches && element.matches('.notice,.updated,[class*="notice" i],[class*="nag" i]')),
			isDashboardWidget: Boolean(element.matches && element.matches('#dashboard-widgets .postbox,.postbox')),
			isCoreError: Boolean(element.matches && element.matches('.notice-error,.error,[role="alert"]')),
			hasDismiss: Boolean(element.querySelector && element.querySelector('.notice-dismiss,[class*="dismiss" i],[aria-label*="dismiss" i],[aria-label*="close" i]')),
			signature: signature
		};

		featureCache.set(element, { signature: signature, features: features });
		return features;
	}

	function classifyElement(element) {
		if (!classifier || typeof classifier.classify !== 'function' || isNeverHide(element)) {
			return null;
		}

		const features = makeFeatures(element);
		if (features.text.length > MAX_TEXT) {
			return null;
		}

		if (
			features.text.length < 6 &&
			!features.links.length &&
			!features.images.length &&
			!/(?:promo|promotion|upsell|upgrade|advert|offer|marketing|premium|banner|review|rating)/i.test(features.attr)
		) {
			return null;
		}

		const cached = decisionCache.get(element);
		if (cached && cached.signature === features.signature) {
			return cached.result;
		}

		const result = classifier.classify(features, settings);
		decisionCache.set(element, { signature: features.signature, result: result });
		return result;
	}

	function suppress(element, result) {
		if (
			!element ||
			!element.classList ||
			element.classList.contains(hiddenClass) ||
			(element.parentElement && element.parentElement.closest('.' + hiddenClass))
		) {
			return;
		}

		element.classList.add(hiddenClass);
		element.setAttribute('data-mpty-zen-reason', result.reason);
		element.setAttribute('aria-hidden', 'true');
		element.style.setProperty('display', 'none', 'important');
	}

	function maybeHide(element) {
		if (isRevealEnabled()) {
			return;
		}

		const result = classifyElement(element);
		if (result && result.decision === 'suppress') {
			suppress(element, result);
		}
	}

	function scan(scope) {
		if (!scope || !scope.querySelectorAll) {
			return;
		}

		const selector = candidateSelector();
		if (scope.nodeType === 1 && scope.matches && scope.matches(selector)) {
			maybeHide(scope);
		}
		for (const element of scope.querySelectorAll(selector)) {
			maybeHide(element);
		}
	}

	function scoreAction(action) {
		if (!classifier || typeof classifier.actionEvidence !== 'function') {
			return 0;
		}

		const href = (action.getAttribute && action.getAttribute('href')) || '';
		return classifier.actionEvidence({
			href: href,
			label: normalizeText(action.textContent || action.value || (action.getAttribute && action.getAttribute('aria-label'))),
			meta: normalizeText([
				action.id || '',
				typeof action.className === 'string' ? action.className : '',
				action.getAttribute && action.getAttribute('data-action')
			].join(' ')),
			external: isExternalUrl(href)
		});
	}

	function scanActionAncestors(scope) {
		if (!scope || !scope.querySelectorAll) {
			return;
		}

		for (const action of scope.querySelectorAll('a[href],button,input[type="button"],input[type="submit"]')) {
			if (scoreAction(action) < 7) {
				continue;
			}

			let current = action.parentElement;
			for (let depth = 0; depth < 5 && current; depth++, current = current.parentElement) {
				if (
					current.id === 'wpbody-content' ||
					current.classList.contains('wrap') ||
					/^(HTML|BODY|FORM|TABLE|TBODY|THEAD|TR)$/.test(current.tagName)
				) {
					break;
				}
				if (current.matches('td,th,tr,.plugin-card,.plugin-install-php,.media-modal,[role="dialog"]')) {
					break;
				}

				const result = classifyElement(current);
				if (result && result.decision === 'suppress') {
					suppress(current, result);
					break;
				}
			}
		}
	}

	function processNode(node) {
		if (!node || node.nodeType !== 1) {
			return;
		}
		if (node.closest && node.closest('#wpadminbar,#adminmenuwrap,#screen-meta')) {
			return;
		}

		scan(node);
		scanActionAncestors(node);
	}

	function scheduleBatch() {
		if (batchScheduled) {
			return;
		}

		batchScheduled = true;
		const callback = function () {
			batchScheduled = false;
			const nodes = Array.from(pendingNodes);
			pendingNodes.clear();
			for (const node of nodes) {
				processNode(node);
			}
		};

		if (typeof window.queueMicrotask === 'function') {
			window.queueMicrotask(callback);
		} else {
			Promise.resolve().then(callback);
		}
	}

	function queueNode(node) {
		if (!node || node.nodeType !== 1) {
			return;
		}

		for (const existing of pendingNodes) {
			if (existing.contains(node)) {
				return;
			}
			if (node.contains(existing)) {
				pendingNodes.delete(existing);
			}
		}

		pendingNodes.add(node);
		scheduleBatch();
	}

	function restorePage() {
		for (const element of document.querySelectorAll('.' + hiddenClass)) {
			element.classList.remove(hiddenClass);
			element.removeAttribute('aria-hidden');
			element.removeAttribute('data-mpty-zen-reason');
			element.style.removeProperty('display');
		}
	}

	function stopSuppression() {
		if (observer) {
			observer.disconnect();
		}
		restorePage();
	}

	function observe(root, deepProcessing) {
		if (observer) {
			observer.disconnect();
		}

		observer = new MutationObserver(function (mutations) {
			if (isRevealEnabled()) {
				return;
			}

			for (const mutation of mutations) {
				for (const node of mutation.addedNodes) {
					if (!node || node.nodeType !== 1) {
						continue;
					}

					if (deepProcessing) {
						queueNode(node);
					} else if (node.matches && node.matches(candidateSelector())) {
						maybeHide(node);
					}
				}
			}
		});

		observer.observe(root, { childList: true, subtree: true });
	}

	function startSuppression() {
		if (!settings.enabled || isRevealEnabled()) {
			return;
		}

		scan(document);
		scanActionAncestors(document);
		observe(document.getElementById('wpbody-content') || document.body, true);
	}

	function updateRevealControls() {
		const button = document.getElementById('mpty-zen-toggle-reveal');
		const help = document.getElementById('mpty-zen-reveal-help');
		const revealed = isRevealEnabled();

		if (button) {
			button.setAttribute('aria-pressed', revealed ? 'true' : 'false');
			button.textContent = revealed ? ((boot.strings && boot.strings.resume) || 'Resume Zen') : ((boot.strings && boot.strings.pause) || 'Pause Zen');
		}

		if (help) {
			help.textContent = revealed
				? ((boot.strings && boot.strings.pausedHelp) || 'Zen is paused. Promotional content is currently visible.')
				: ((boot.strings && boot.strings.activeHelp) || 'Pause Zen to see everything plugins are displaying.');
		}
	}

	function bindSettingsControls() {
		updateRevealControls();

		const revealButton = document.getElementById('mpty-zen-toggle-reveal');
		if (revealButton) {
			revealButton.addEventListener('click', function () {
				if (isRevealEnabled()) {
					removeSessionValue(revealKey);
					startSuppression();
				} else {
					writeSessionValue(revealKey, '1');
					stopSuppression();
				}
				updateRevealControls();
			});
		}

	}

	function enableDiagnostics() {
		if (!boot.debug || !classifier) {
			return;
		}

		window.MPTYZenDiagnostics = Object.freeze({
			inspect: function (element) {
				const result = classifyElement(element);
				if (!result) {
					return null;
				}

				return {
					classification: result.classification,
					decision: result.decision,
					confidence: result.confidence,
					scores: Object.assign({}, result.scores),
					signals: result.signals.slice()
				};
			}
		});
	}

	function init() {
		bindSettingsControls();
		enableDiagnostics();

		if (!settings.enabled || isRevealEnabled()) {
			stopSuppression();
			return;
		}

		startSuppression();
	}

	if (settings.enabled && !isRevealEnabled()) {
		// While the document is parsing, inspect only direct semantic candidates.
		// Full candidate discovery begins once wp-admin has finished building its DOM.
		observe(document.documentElement, false);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init, { once: true });
	} else {
		init();
	}
}());
