(function (root, factory) {
	'use strict';

	const api = factory();

	if (typeof module === 'object' && module.exports) {
		module.exports = api;
	}

	if (root) {
		root.MPTYZenClassifier = api;
	}
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
	'use strict';

	const VERSION = 1;

	const operationalPatterns = [
		['critical', /\bcritical\b/i, 9],
		['fatal', /\bfatal\b/i, 12],
		['error', /\berror\b/i, 8],
		['failed', /\bfailed\b/i, 9],
		['failure', /\bfailure\b/i, 9],
		['vulnerability', /\bvulnerab(?:ility|le)\b/i, 11],
		['malware', /\bmalware\b/i, 13],
		['hacked', /\bhacked\b/i, 13],
		['compromised', /\bcompromis(?:ed|e)\b/i, 13],
		['security-warning', /\bsecurity\s+(?:issue|issues|problem|problems|warning|warnings|alert|alerts|risk|risks|incident|incidents)\b/i, 11],
		['database-problem', /\bdatabase\s+(?:error|failed|failure|corrupt|corrupted|problem|issue|upgrade required|repair)\b/i, 11],
		['backup-problem', /\bbackup\s+(?:failed|failure|error|stopped|incomplete|missing)\b/i, 13],
		['payment-problem', /\bpayment\s+(?:failed|failure|declined|error|overdue)\b/i, 13],
		['connection-problem', /\bconnection\s+(?:failed|failure|error|lost)\b/i, 10],
		['configuration-problem', /\bconfiguration\s+(?:required|error|failed|missing|invalid)\b/i, 10],
		['required-action', /\brequired\s+(?:action|configuration|migration|update)\b/i, 9],
		['action-required', /\baction\s+required\b/i, 9],
		['permission-problem', /\bpermission\s+(?:denied|error)\b/i, 10],
		['certificate-problem', /\bcertificate\s+(?:expired|invalid|error|problem)\b/i, 11],
		['ssl-problem', /\bssl\s+(?:certificate\s+)?(?:expired|invalid|error|problem|failure|failed)\b/i, 11],
		['php-problem', /\bphp\s+(?:error|warning|incompatible|unsupported|version required|requirement)\b/i, 9],
		['cron-problem', /\b(?:wp[- ]?cron|cron|scheduled\s+(?:task|event|action))\s+(?:failed|failure|error|not running|missed|overdue)\b/i, 10],
		['incompatible', /\bincompatib(?:le|ility)\b/i, 10],
		['not-compatible', /\bnot compatible\b/i, 10],
		['restore-failed', /\brestore failed\b/i, 13],
		['license-operational', /\blicense\s+(?:invalid|expired)\b.{0,120}\b(?:updates?|service|feature|backup|scan|support)\b/i, 9],
		['wordpress-update', /\bwordpress\b.{0,120}\b(?:update(?:\s+now)?|please update|is available|new version)\b/i, 10],
		['update-failed', /\b(?:wordpress|plugin|theme|core)?\s*update\s+(?:failed|failure|error|required)\b/i, 11],
		['woocommerce-operational', /\bwoocommerce\b.{0,120}\b(?:database\s+update|required|failed|failure|error|critical|action required|configuration)\b/i, 10]
	];

	const promoPatterns = [
		['go-pro', /\bgo\s+pro\b/i, 11],
		['upgrade-now', /\bupgrade\s+now\b/i, 11],
		['upgrade-paid', /\bupgrade\s+to\s+(?:pro|premium|paid)\b/i, 10],
		['unlock-premium', /\bunlock\s+(?:all|more|premium|pro|advanced|powerful)\b/i, 9],
		['premium-features', /\bpremium\s+(?:features|version|plan|tools?|plugins?)\b/i, 8],
		['limited-time', /\blimited\s+time\b/i, 10],
		['special-offer', /\bspecial\s+offer\b/i, 10],
		['black-friday', /\bblack\s+friday\b/i, 12],
		['cyber-monday', /\bcyber\s+monday\b/i, 12],
		['save-percent', /\bsave\s+(?:up\s+to\s+)?\d+%\b/i, 11],
		['percent-off', /\b\d+%\s+off\b/i, 11],
		['buy-now', /\bbuy\s+now\b/i, 9],
		['get-paid', /\bget\s+(?:pro|premium)\b/i, 9],
		['discount', /\bdiscount\b/i, 7],
		['exclusive-offer', /\bexclusive\s+(?:deal|offer)\b/i, 9],
		['upgrade-plan', /\bupgrade\s+your\s+plan\b/i, 10],
		['trial', /\bstart\s+(?:your\s+)?(?:free\s+)?trial\b/i, 9],
		['free-trial', /\bfree\s+trial\b/i, 8],
		['pro-features', /\bpro\s+features?\b/i, 8],
		['enhance-pro', /\benhance\b.{0,120}\bpro\b/i, 8],
		['advanced-connect', /\badvanced\s+features?\b.{0,100}\b(?:connect|upgrade|pro|premium)\b/i, 8],
		['web-app-connect', /\bconnect\s+to\b.{0,80}\bweb\s+app\b/i, 9],
		['more-features', /\bmore\s+features?.{0,80}\b(?:pro|premium|upgrade)\b/i, 8]
	];

	const reviewPatterns = [
		['rate-us', /\brate\s+us\b/i, 12],
		['leave-review', /\bleave\s+(?:us\s+)?a\s+review\b/i, 12],
		['write-review', /\bwrite\s+(?:us\s+)?a\s+review\b/i, 12],
		['five-star', /\b5[-\s]?star\b/i, 12],
		['five-star-word', /\bfive[-\s]?star\b/i, 12],
		['wordpress-review', /\breview\s+on\s+wordpress/i, 11],
		['support-us', /\bsupport\s+us\b/i, 8],
		['help-grow', /\bhelp\s+us\s+(?:grow|improve)\b/i, 8],
		['feedback', /\bshare\s+your\s+feedback\b/i, 8],
		['enjoying', /\benjoying\s+.{1,80}\?/i, 7]
	];

	const announcementPatterns = [
		['updated-version', /\bhas\s+been\s+updated\s+to\s+version\b/i, 12],
		['whats-new', /\bwhat(?:'|’)s\s+new\b/i, 9],
		['new-in-version', /\bnew\s+in\s+version\b/i, 9],
		['introducing', /\bintroducing\b.{0,120}\b(?:feature|version|integration)\b/i, 7],
		['new-features', /\bnew\s+features?\b/i, 6]
	];

	const promoHrefPatterns = [
		['commercial-url', /\/(?:pricing|upgrade|premium|pro|plans?|checkout)(?:\/|$|\?|#)/i, 9],
		['commercial-query', /[?&](?:upgrade|plan|pricing|pro|premium)=/i, 8],
		['utm', /utm_(?:campaign|medium|source)=/i, 2],
		['affiliate', /affiliate|aff(?:iliate)?[_-]?id=/i, 4]
	];

	const reviewHrefPatterns = [
		['wporg-review-url', /wordpress\.org\/support\/plugin\/[^/]+\/reviews/i, 16],
		['wporg-plugin-review-url', /wordpress\.org\/plugins\/[^/]+\/#reviews/i, 16],
		['review-url', /\/(?:review|reviews|rating|rate-us|feedback)(?:\/|$|\?|#)/i, 10],
		['review-query', /[?&](?:review|rating|feedback)=/i, 10]
	];

	const promoImagePatterns = [
		['promo-image-name', /\b(?:promo|promotion|offer|sale|discount|deal|upgrade|upsell|premium|pro[-_ ]?banner|license)[-_./]/i, 8],
		['promo-image-path', /[-_./](?:promo|promotion|offer|sale|discount|deal|upgrade|upsell|premium|license)(?:[-_./]|$)/i, 8],
		['seasonal-image', /\b(?:black[-_ ]?friday|cyber[-_ ]?monday|special[-_ ]?offer)\b/i, 10]
	];

	function normalize(value) {
		return String(value || '').replace(/\s+/g, ' ').trim();
	}

	function addPatterns(value, patterns, bucket, signals, prefix) {
		let score = 0;
		for (const pattern of patterns) {
			if (pattern[1].test(value)) {
				score += pattern[2];
				signals.push(prefix + ':' + pattern[0]);
			}
		}
		bucket.value += score;
		return score;
	}

	function classify(rawFeatures, rawSettings) {
		const features = rawFeatures || {};
		const settings = Object.assign({
			hidePromotionalNotices: true,
			hideReviewNags: true,
			hidePromotionalUI: true
		}, rawSettings || {});
		const signals = [];
		const operational = { value: 0 };
		const promo = { value: 0 };
		const review = { value: 0 };
		const announcement = { value: 0 };
		const text = normalize(features.text);
		const attr = normalize(features.attr).toLowerCase();
		const links = Array.isArray(features.links) ? features.links : [];
		const images = Array.isArray(features.images) ? features.images : [];
		let externalLinks = 0;
		let externalImages = 0;
		let largeBannerImages = 0;
		let promoImageScore = 0;
		let promoHrefScore = 0;
		let reviewMetaScore = 0;

		addPatterns(text, operationalPatterns, operational, signals, 'operational');
		addPatterns(text, promoPatterns, promo, signals, 'promo');
		addPatterns(text, reviewPatterns, review, signals, 'review');
		addPatterns(text, announcementPatterns, announcement, signals, 'announcement');

		if (features.isCoreError) {
			operational.value += 25;
			signals.push('operational:core-error-container');
		}
		if (/(?:^|[\s_-])(error|critical|failed|failure|vulnerability|malware)(?:$|[\s_-])/i.test(attr)) {
			operational.value += 7;
			signals.push('operational:error-metadata');
		}
		if (/(?:^|[\s_-])(promo|promotion|upsell|upgrade|advert|offer|marketing|premium|pro-banner)(?:$|[\s_-])/i.test(attr)) {
			promo.value += 8;
			signals.push('promo:metadata');
		}
		if (/(?:^|[\s_-])(review|rating|feedback|rate-us)(?:$|[\s_-])/i.test(attr)) {
			review.value += 8;
			signals.push('review:metadata');
		}
		if (/(?:notice[-_ ]review|review[-_ ]notice|review[-_ ]feedback|feedback[-_ ]review)/i.test(attr)) {
			review.value += 6;
			signals.push('review:notice-metadata');
		}

		for (const link of links.slice(0, 12)) {
			const href = normalize(link.href);
			const label = normalize(link.label);
			const meta = normalize(link.meta).toLowerCase();
			promoHrefScore += addPatterns(href, promoHrefPatterns, promo, signals, 'promo');
			addPatterns(href, reviewHrefPatterns, review, signals, 'review');
			addPatterns(label, promoPatterns, promo, signals, 'promo');
			addPatterns(label, reviewPatterns, review, signals, 'review');

			if (/(?:^|[\s_-])(?:review|rating|rate-us|leave-review|write-review)(?:$|[\s_-])/i.test(meta)) {
				review.value += 10;
				reviewMetaScore += 10;
				signals.push('review:action-metadata');
			}
			if (/(?:^|[\s_-])(?:promo|promotion|upsell|upgrade|buy|purchase|premium|pro)(?:$|[\s_-])/i.test(meta)) {
				promo.value += 5;
				signals.push('promo:action-metadata');
			}
			if (link.external) {
				externalLinks++;
			}
		}

		for (const image of images.slice(0, 8)) {
			const imageValue = normalize(image.src) + ' ' + normalize(image.alt);
			const before = promo.value;
			addPatterns(imageValue, promoImagePatterns, promo, signals, 'promo');
			promoImageScore += promo.value - before;
			if (image.external) {
				externalImages++;
			}
			const width = Number(image.width) || 0;
			const height = Number(image.height) || 0;
			if (width >= 500 && height >= 100 && width / Math.max(height, 1) >= 2.2) {
				largeBannerImages++;
			}
		}

		if (externalLinks && (promo.value >= 4 || review.value >= 4 || announcement.value >= 5)) {
			promo.value += 2;
			signals.push('promo:external-commercial-context');
		}
		if (externalImages && largeBannerImages && operational.value === 0) {
			promo.value += 4;
			signals.push('promo:large-external-banner');
		}
		if (externalImages && promoImageScore >= 8 && operational.value === 0) {
			promo.value += 3;
			signals.push('promo:external-promotional-image');
		}
		if (largeBannerImages && promoHrefScore >= 8 && operational.value === 0) {
			promo.value += 5;
			signals.push('promo:banner-commercial-link');
		}
		if (features.hasDismiss && externalLinks && links.length >= 2 && operational.value === 0) {
			promo.value += 3;
			signals.push('promo:dismissible-multi-cta');
		}
		if (features.hasDismiss && reviewMetaScore >= 10 && links.length >= 2 && operational.value === 0) {
			review.value += 3;
			signals.push('review:dismissible-review-actions');
		}
		if (/\b(?:\$|€|£)\s?\d+(?:[.,]\d+)?\b/.test(text) && /\b(?:save|off|discount|upgrade|pro|premium)\b/i.test(text)) {
			promo.value += 5;
			signals.push('promo:price-discount');
		}

		const threshold = features.isNotice ? 9 : (features.isDashboardWidget ? 10 : 11);
		const allowPromo = features.isNotice ? Boolean(settings.hidePromotionalNotices) : Boolean(settings.hidePromotionalUI);
		let result;

		if (operational.value >= 8) {
			result = {
				decision: 'keep',
				classification: 'OPERATIONAL',
				reason: 'operational',
				confidence: operational.value
			};
		} else if (settings.hideReviewNags && review.value >= threshold && review.value >= promo.value) {
			result = {
				decision: 'suppress',
				classification: 'REVIEW',
				reason: 'review',
				confidence: review.value
			};
		} else if (allowPromo && promo.value >= threshold) {
			result = {
				decision: 'suppress',
				classification: 'PROMOTIONAL',
				reason: 'promotion',
				confidence: promo.value
			};
		} else if (settings.hidePromotionalNotices && features.isNotice && announcement.value >= threshold) {
			result = {
				decision: 'suppress',
				classification: 'ANNOUNCEMENT',
				reason: 'announcement',
				confidence: announcement.value
			};
		} else {
			result = {
				decision: 'keep',
				classification: 'AMBIGUOUS',
				reason: 'uncertain',
				confidence: Math.max(promo.value, review.value, announcement.value)
			};
		}

		result.scores = {
			operational: operational.value,
			promotional: promo.value,
			review: review.value,
			announcement: announcement.value
		};
		result.signals = Array.from(new Set(signals));
		return result;
	}

	function actionEvidence(action) {
		const feature = {
			text: '',
			attr: '',
			links: [action || {}],
			images: [],
			isNotice: false,
			isDashboardWidget: false,
			isCoreError: false,
			hasDismiss: false
		};
		const result = classify(feature, {
			hidePromotionalNotices: true,
			hideReviewNags: true,
			hidePromotionalUI: true
		});
		return Math.max(result.scores.promotional, result.scores.review);
	}

	return {
		version: VERSION,
		classify: classify,
		actionEvidence: actionEvidence
	};
}));
