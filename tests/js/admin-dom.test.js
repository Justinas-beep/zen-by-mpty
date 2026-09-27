'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { JSDOM } = require('jsdom');

const classifierSource = fs.readFileSync(path.join(__dirname, '../../assets/js/classifier.js'), 'utf8');
const adminSource = fs.readFileSync(path.join(__dirname, '../../assets/js/admin.js'), 'utf8');

function boot(markup) {
	const dom = new JSDOM(`<!doctype html><html><body><main id="wpbody-content">${markup}</main><button id="mpty-zen-toggle-reveal"></button><p id="mpty-zen-reveal-help"></p></body></html>`, {
		runScripts: 'outside-only',
		url: 'https://example.test/wp-admin/options-general.php'
	});
	dom.window.MPTYZen = {
		settings: { enabled: true, hidePromotionalNotices: true, hideReviewNags: true, hidePromotionalUI: true },
		revealKey: 'mpty_zen_test_reveal',
		strings: {}
	};
	dom.window.eval(classifierSource);
	dom.window.eval(adminSource);
	dom.window.document.dispatchEvent(new dom.window.Event('DOMContentLoaded'));
	return dom;
}

function bootDuringParse(markup) {
	const dom = new JSDOM('<!doctype html><html><body><main id="wpbody-content"></main><button id="mpty-zen-toggle-reveal"></button><p id="mpty-zen-reveal-help"></p></body></html>', {
		runScripts: 'outside-only',
		url: 'https://example.test/wp-admin/options-general.php'
	});
	dom.window.MPTYZen = {
		settings: { enabled: true, hidePromotionalNotices: true, hideReviewNags: true, hidePromotionalUI: true },
		revealKey: 'mpty_zen_test_reveal',
		strings: {}
	};
	const NativeMutationObserver = dom.window.MutationObserver;
	const observerStats = { active: 0, maximumActive: 0 };
	dom.window.MutationObserver = class extends NativeMutationObserver {
		observe(...args) {
			if (!this.mptyZenActive) {
				this.mptyZenActive = true;
				observerStats.active++;
				observerStats.maximumActive = Math.max(observerStats.maximumActive, observerStats.active);
			}
			return super.observe(...args);
		}

		disconnect() {
			if (this.mptyZenActive) {
				this.mptyZenActive = false;
				observerStats.active--;
			}
			return super.disconnect();
		}
	};
	dom.mptyZenObserverStats = observerStats;
	dom.window.eval(classifierSource);
	dom.window.eval(adminSource);
	dom.window.document.getElementById('wpbody-content').insertAdjacentHTML('beforeend', markup);
	dom.window.document.dispatchEvent(new dom.window.Event('DOMContentLoaded'));
	return dom;
}

function toggle(dom) {
	dom.window.document.getElementById('mpty-zen-toggle-reveal').click();
}

async function waitFor(dom, condition, timeout = 500) {
	const started = Date.now();
	while (!condition()) {
		if (Date.now() - started >= timeout) {
			return false;
		}
		await new Promise((resolve) => dom.window.setTimeout(resolve, 5));
	}
	return true;
}

for (const [name, declaration, expected, priority] of [
	['no inline display', '', '', ''],
	['display block', 'display:block', 'block', ''],
	['display flex', 'display:flex', 'flex', ''],
	['display grid', 'display:grid', 'grid', ''],
	['important display', 'display:flex !important', 'flex', 'important']
]) {
	test('Pause restores ' + name, () => {
		const dom = boot(`<div id="promo" class="notice promo-banner" style="${declaration}">Upgrade to Pro now</div>`);
		const promo = dom.window.document.getElementById('promo');
		assert.equal(promo.style.getPropertyValue('display'), 'none');
		toggle(dom);
		assert.equal(promo.style.getPropertyValue('display'), expected);
		assert.equal(promo.style.getPropertyPriority('display'), priority);
		dom.window.close();
	});
}

test('multiple Pause and Resume cycles preserve the original display state', () => {
	const dom = boot('<div id="promo" class="notice promo-banner" style="display:grid !important">Upgrade to Pro now</div>');
	const promo = dom.window.document.getElementById('promo');
	toggle(dom);
	assert.equal(promo.style.cssText, 'display: grid !important;');
	toggle(dom);
	assert.equal(promo.style.getPropertyValue('display'), 'none');
	toggle(dom);
	assert.equal(promo.style.cssText, 'display: grid !important;');
	dom.window.close();
});

test('Resume immediately suppresses the same parser-time promotion across repeated cycles', async () => {
	const dom = bootDuringParse('<div id="promo" class="notice promo-banner" style="display:flex !important" aria-hidden="false" data-mpty-zen-reason="original">Upgrade to Pro now</div>');
	const promo = dom.window.document.getElementById('promo');
	const button = dom.window.document.getElementById('mpty-zen-toggle-reveal');
	const help = dom.window.document.getElementById('mpty-zen-reveal-help');
	const zenWrap = dom.window.document.createElement('div');
	zenWrap.className = 'mpty-zen-wrap';
	dom.window.document.getElementById('wpbody-content').appendChild(zenWrap);

	assert.equal(await waitFor(dom, () => promo.style.getPropertyValue('display') === 'none'), true);
	zenWrap.appendChild(promo);
	for (let cycle = 0; cycle < 2; cycle++) {
		toggle(dom);
		assert.equal(promo.style.getPropertyValue('display'), 'flex');
		assert.equal(promo.style.getPropertyPriority('display'), 'important');
		assert.equal(promo.getAttribute('aria-hidden'), 'false');
		assert.equal(promo.getAttribute('data-mpty-zen-reason'), 'original');
		assert.equal(button.getAttribute('aria-pressed'), 'true');
		assert.equal(help.textContent, 'Zen is paused. Promotional content is currently visible.');

		toggle(dom);
		assert.equal(promo.style.getPropertyValue('display'), 'none');
		assert.equal(promo.getAttribute('aria-hidden'), 'true');
		assert.equal(promo.getAttribute('data-mpty-zen-reason'), 'promotion');
		assert.equal(button.getAttribute('aria-pressed'), 'false');
		assert.equal(help.textContent, 'Pause Zen to see everything plugins are displaying.');
	}
	assert.equal(dom.mptyZenObserverStats.active, 1);
	assert.equal(dom.mptyZenObserverStats.maximumActive, 1);

	const dynamicPromo = dom.window.document.createElement('div');
	dynamicPromo.className = 'notice promo-banner';
	dynamicPromo.textContent = 'Limited time special offer: upgrade now';
	const dynamicLegitimate = dom.window.document.createElement('div');
	dynamicLegitimate.className = 'notice notice-warning';
	dynamicLegitimate.textContent = 'Payment failed. Review the operational record.';
	dom.window.document.getElementById('wpbody-content').append(dynamicPromo, dynamicLegitimate);
	assert.equal(await waitFor(dom, () => dynamicPromo.style.getPropertyValue('display') === 'none'), true);
	assert.notEqual(dynamicLegitimate.style.getPropertyValue('display'), 'none');
	assert.equal(dom.mptyZenObserverStats.active, 1);
	assert.equal(dom.mptyZenObserverStats.maximumActive, 1);

	dom.window.close();
});

test('a functional panel is not hidden because it contains an Upgrade button', () => {
	const dom = boot('<section id="functional"><label>API key <input></label><button>Upgrade to Pro</button></section>');
	assert.notEqual(dom.window.document.getElementById('functional').style.display, 'none');
	dom.window.close();
});

test('a settings panel with a Pro badge remains visible', () => {
	const dom = boot('<form id="settings" action="options.php"><h2>Settings <span>Pro</span></h2><input name="api_key"></form>');
	assert.notEqual(dom.window.document.getElementById('settings').style.display, 'none');
	dom.window.close();
});

test('functional controls with an embedded premium upsell remain visible', () => {
	const dom = boot('<section id="orders"><button>Refund order</button><a href="https://vendor.test/pro">Premium reports</a></section>');
	assert.notEqual(dom.window.document.getElementById('orders').style.display, 'none');
	dom.window.close();
});

test('an explicitly promotional card is still hidden', () => {
	const dom = boot('<aside id="promo" class="promo-card">Upgrade to Pro now</aside>');
	assert.equal(dom.window.document.getElementById('promo').style.display, 'none');
	dom.window.close();
});

test('a dynamically inserted promotion is suppressed and faithfully restored', async () => {
	const dom = boot('');
	const promo = dom.window.document.createElement('div');
	promo.className = 'notice promo-banner';
	promo.textContent = 'Upgrade to Pro now';
	promo.style.setProperty('display', 'flex', 'important');
	dom.window.document.getElementById('wpbody-content').appendChild(promo);
	await new Promise((resolve) => dom.window.setTimeout(resolve, 10));
	assert.equal(promo.style.getPropertyValue('display'), 'none');
	toggle(dom);
	assert.equal(promo.style.getPropertyValue('display'), 'flex');
	assert.equal(promo.style.getPropertyPriority('display'), 'important');
	dom.window.close();
});

test('a large burst of dynamic nodes is processed across bounded batches', async () => {
	const dom = boot('');
	const root = dom.window.document.getElementById('wpbody-content');
	for (let index = 0; index < 85; index++) {
		const promo = dom.window.document.createElement('div');
		promo.className = 'notice promo-banner';
		promo.textContent = 'Upgrade to Pro now ' + index;
		root.appendChild(promo);
	}
	assert.equal(await waitFor(dom, () => root.querySelectorAll('.mpty-zen-suppressed').length === 85), true);
	assert.equal(root.querySelectorAll('.mpty-zen-suppressed').length, 85);
	dom.window.close();
});
