# Zen by MPTY Release Checklist

Complete this checklist against the exact commit and ZIP intended for release. Any unchecked required item means **BLOCK RELEASE**.

## Source and scope

- [ ] The working tree is clean and the pre-release candidate is checked out on `develop`; `main` remains the stable/released branch.
- [ ] The diff contains only approved Zen changes.
- [ ] Plugin header, internal version, package version, `readme.txt`, changelog, tag, and artifact filename agree.
- [ ] Declared PHP and WordPress compatibility remains accurate.
- [ ] The highest change-risk classification and affected trust boundaries are recorded.
- [ ] Classifier, selectors, ancestor suppression, restoration, or observer changes received HIGH-risk review when present.

## Automated gates

- [ ] Development dependencies install from committed lock files without unexpected changes.
- [ ] `composer check` passes: PHP syntax, PHPStan, PHPCS/WPCS, PHPUnit, and JavaScript tests.
- [ ] SHOULD HIDE, MUST KEEP, and AMBIGUOUS KEEP fixture counts are recorded; all false-positive regressions pass.
- [ ] WordPress Plugin Check passes against the exact production artifact; justified warnings are recorded.
- [ ] Required CI checks are green with least-privilege permissions and pinned actions where practical.

## Product and manual QA

- [ ] Clean install, activation, deactivation/reactivation, settings save, and uninstall are tested in a disposable site.
- [ ] Zen defaults to conservative suppression and uncertain content remains visible.
- [ ] Pause restores no-display, block, flex, grid, and `!important` inline display states exactly across repeated cycles.
- [ ] Dynamically inserted notices are handled correctly without observer loops.
- [ ] Representative core, plugin, WooCommerce, page-builder, dashboard, list-table, editor, and settings pages remain functional.
- [ ] A functional panel containing Upgrade/Pro/Premium is not hidden; a true promotional card is hidden.
- [ ] Large/dynamic admin pages show no material interaction or rendering regression.
- [ ] The MPTY tools section appears only on Zen's settings page, reports local installed state, and performs no remote request or automatic action.
- [ ] Keyboard operation, labels, focus, control state, status announcements, escaping, and translated strings are reviewed.

### Manual browser matrix — human verification required

Leave every row pending until a human performs it in the stated environment. For each row verify: obvious promotions hide; operational notices remain; functional panels remain; Pause restores correctly; layout remains intact; Zen causes no console errors or obvious sluggishness.

| Admin environment | Promotion/operational behavior | Functional/layout safety | Pause/Resume | Console/performance |
|---|---|---|---|---|
| Standard Dashboard | [ ] Pending | [ ] Pending | [ ] Pending | [ ] Pending |
| Plugins | [ ] Pending | [ ] Pending | [ ] Pending | [ ] Pending |
| Themes | [ ] Pending | [ ] Pending | [ ] Pending | [ ] Pending |
| Posts and Pages | [ ] Pending | [ ] Pending | [ ] Pending | [ ] Pending |
| WordPress and plugin Settings | [ ] Pending | [ ] Pending | [ ] Pending | [ ] Pending |
| Plugin settings page containing notices | [ ] Pending | [ ] Pending | [ ] Pending | [ ] Pending |
| WooCommerce-style dynamic page, when available | [ ] Pending | [ ] Pending | [ ] Pending | [ ] Pending |
| Large or highly dynamic DOM page | [ ] Pending | [ ] Pending | [ ] Pending | [ ] Pending |
| Narrow viewport | [ ] Pending | [ ] Pending | [ ] Pending | [ ] Pending |
| Keyboard-only navigation | [ ] Pending | [ ] Pending | [ ] Pending | [ ] Pending |

## Data, privacy, and compatibility

- [ ] Options, migration markers, session storage, network requests, and all other data flows are documented and accurate.
- [ ] Capability/nonce, validation, sanitization, and escaping boundaries are reviewed.
- [ ] No telemetry, tracking parameter, remote classifier, downloaded code, secret, or unnecessary remote dependency was added.
- [ ] Site-scoped and multisite/network-activation behavior is documented and tested or explicitly limited.
- [ ] Migration, uninstall, and downgrade behavior is reviewed against supported prior versions.
- [ ] PHP 7.4 and WordPress 6.4 minimum compatibility receive executable evidence; current supported WordPress is smoke-tested.

## Production artifact

- [ ] The authoritative production manifest is exact and validation rejects missing or unexpected files.
- [ ] A fresh ZIP is built automatically from the approved release commit.
- [ ] The ZIP has one `zen-by-mpty` top-level directory and installs/activates cleanly.
- [ ] Tests, docs not intended for users, tooling, `vendor`, `node_modules`, caches, logs, local configuration, credentials, editor files, `.git`, and `.github` are absent.
- [ ] The ZIP checksum is recorded and the unpacked ZIP exactly matches the artifact that passed validation and Plugin Check.
- [ ] License, readme, changelog, privacy wording, source/build information, and WordPress.org requirements are current.

## Publish and final review

- [ ] The validated `develop` candidate is promoted to `main` only through the separately controlled repository process after every required gate passes.
- [ ] Release notes accurately describe user-visible classifier and compatibility changes without overstating safety guarantees.
- [ ] The approved tag/release assets match the validated commit and checksum.
- [ ] A post-release smoke test is recorded.
- [ ] Unresolved risks have severity, mitigation, owner, and follow-up.

Final verdict (select one):

- [ ] **PASS**
- [ ] **PASS WITH RECOMMENDATIONS**
- [ ] **BLOCK RELEASE**

Version/tag: ____________________  Commit: ____________________

Reviewer/date: ____________________

Evidence and open risks: ________________________________________________
