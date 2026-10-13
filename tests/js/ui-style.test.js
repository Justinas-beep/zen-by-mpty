'use strict';

const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const css = fs.readFileSync(path.join(__dirname, '../../assets/css/admin.css'), 'utf8');

test('admin UI follows the canonical MPTY header geometry', () => {
	assert.match(css, /\.mpty-ui__product-icon-box\s*{[^}]*width:\s*36px;[^}]*height:\s*36px;[^}]*flex:\s*0 0 36px;/s);
	assert.match(css, /\.mpty-ui h1\s*{[^}]*font-size:\s*30px;[^}]*font-weight:\s*700;[^}]*letter-spacing:\s*-0\.5px;[^}]*line-height:\s*45px;/s);
	assert.match(css, /\.mpty-ui__product-purpose\s*{[^}]*margin:\s*4px 0 0;[^}]*font-size:\s*14px;[^}]*line-height:\s*21px;/s);
});

test('admin UI uses selective containment without legacy patch rules', () => {
	assert.match(css, /\.mpty-ui__panel\s*{[^}]*border:\s*1px solid var\(--mpty-line\);[^}]*border-radius:\s*var\(--mpty-radius\);[^}]*background:\s*var\(--mpty-surface\);/s);
	assert.doesNotMatch(css, /!important/);
	assert.doesNotMatch(css, /mpty-ui__(?:panel--soft|panel--flat|eyebrow|intro|muted|button--primary)/);
	assert.doesNotMatch(css, /border-(?:left|inline-start)/);
});

test('admin UI retains responsive and accessible control states', () => {
	assert.match(css, /@media \(max-width:\s*720px\)[\s\S]*\.mpty-ui__product-icon-box\s*{[^}]*width:\s*32px;[^}]*height:\s*32px;/);
	assert.match(css, /:focus-visible\s*{[^}]*outline:\s*3px solid var\(--mpty-focus\);[^}]*outline-offset:\s*3px;/s);
	assert.match(css, /@media \(forced-colors:\s*active\)/);
});
