'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const classifier = require('../../assets/js/classifier.js');

function features(overrides) {
	return Object.assign({
		text: '',
		attr: '',
		links: [],
		images: [],
		isNotice: true,
		isDashboardWidget: false,
		isCoreError: false,
		hasDismiss: false
	}, overrides || {});
}

const shouldHide = [
	['upgrade to Pro', features({ text: 'Upgrade to Pro and unlock more features.' })],
	['premium feature advertisement', features({ text: 'Unlock premium features today.' })],
	['discount banner', features({ text: 'Black Friday: save 40% on Premium.' })],
	['review request', features({ text: 'Enjoying Example? Leave us a review.' })],
	['rating prompt', features({ text: 'Please give us a five-star rating.', attr: 'review-notice' })],
	['newsletter promotion', features({ text: 'Get Pro tips in our newsletter.', attr: 'promo-banner' })],
	['product cross-sell', features({ text: 'Try our other premium plugin.', attr: 'upsell-card' })],
	['plugin advertisement', features({ text: 'Special offer: get Premium now.' })],
	['marketing announcement', features({ text: "What's new in our premium features." })],
	['pure announcement', features({ text: "What's new in version 4.0" })],
	['commercial card', features({ text: 'Upgrade your plan.', isNotice: false, attr: 'promo-card' })],
	['image-only promotion', features({ text: '', attr: 'promo-banner', images: [{ src: 'https://vendor.test/sale/premium-banner.png', alt: 'Special offer', width: 900, height: 220, external: true }] })]
];

const mustKeep = [
	['WordPress error', features({ text: 'WordPress encountered an error.', isCoreError: true })],
	['PHP failure', features({ text: 'PHP error: the plugin failed to load.' })],
	['update failure', features({ text: 'Plugin update failed. Upgrade to Pro is unavailable.' })],
	['backup failure', features({ text: 'Backup failed and is incomplete.' })],
	['database migration', features({ text: 'Database upgrade required before continuing.' })],
	['maintenance warning', features({ text: 'Maintenance warning: scheduled task failed.', attr: 'maintenance-nag' })],
	['broken integration', features({ text: 'Payment API integration disconnected.' })],
	['service failure', features({ text: 'External service unavailable; features are offline.' })],
	['payment failure', features({ text: 'Payment failed for your active service.' })],
	['expired credentials', features({ text: 'API credentials expired and synchronization stopped.' })],
	['security notice', features({ text: 'Security warning: vulnerability detected.' })],
	['destructive confirmation', features({ text: 'Confirm permanent delete. This cannot be undone.' })],
	['setup required', features({ text: 'Setup required before the integration can operate.' })],
	['ordinary settings form', features({ text: 'General settings Save changes', isNotice: false })],
	['validation error', features({ text: 'Validation error: enter a valid email address.', isCoreError: true })],
	['WooCommerce operational', features({ text: 'WooCommerce database update required.' })],
	['normal metabox', features({ text: 'Publish Status Visibility', isNotice: false, isDashboardWidget: true })],
	['functional Upgrade wording', features({ text: 'Upgrade database schema', isNotice: false })],
	['functional Pro wording', features({ text: 'Profile configuration', isNotice: false })],
	['functional Premium wording', features({ text: 'Premium tax configuration settings', isNotice: false })]
];

const ambiguous = [
	['new tools available', features({ text: 'New tools are available.' })],
	['account notice', features({ text: 'Review your account settings.' })],
	['short upgrade label', features({ text: 'Upgrade', isNotice: false })],
	['premium status label', features({ text: 'Premium', isNotice: false })]
];

for (const [name, sample] of shouldHide) {
	test('SHOULD HIDE: ' + name, () => {
		assert.equal(classifier.classify(sample).decision, 'suppress');
	});
}

for (const [name, sample] of mustKeep) {
	test('MUST KEEP: ' + name, () => {
		assert.equal(classifier.classify(sample).decision, 'keep');
	});
}

for (const [name, sample] of ambiguous) {
	test('AMBIGUOUS KEEP: ' + name, () => {
		const result = classifier.classify(sample);
		assert.equal(result.decision, 'keep');
		assert.equal(result.classification, 'AMBIGUOUS');
	});
}

test('fixture category counts remain visible in test output', (context) => {
	context.diagnostic(`SHOULD HIDE: ${shouldHide.length}; MUST KEEP: ${mustKeep.length}; AMBIGUOUS KEEP: ${ambiguous.length}`);
	assert.ok(mustKeep.length > shouldHide.length, 'false-positive fixtures must be the largest category');
});
