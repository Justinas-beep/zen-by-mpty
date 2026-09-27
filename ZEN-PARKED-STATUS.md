# Zen by MPTY — Parked Status and Restart Manual

> **IF YOU ARE RESUMING ZEN:**
> 1. Read this document.
> 2. Verify `develop`, `HEAD`, and working-tree status.
> 3. Verify the 0.6.1 candidate anchor below.
> 4. Do not reopen development without a concrete new reason.
> 5. Resume from the publication decision.

## 1. Executive status

| Item | Authoritative parked state |
|---|---|
| Product | Zen by MPTY |
| Qualified candidate | 0.6.1 |
| Status | **RELEASE-QUALIFIED / NOT YET PUBLISHED** |
| Branch | `develop` |
| Candidate commit | Current clean, synchronized `develop` HEAD containing this handoff |
| Previous public release | 0.6.0, tag `v0.6.0` |
| Final CI | PASS — latest successful GitHub Actions run for the candidate `develop` HEAD |
| Repository | <https://github.com/Justinas-beep/zen-by-mpty> |

The 0.6.1 candidate includes the accepted removal of a never-public development-era settings migration, its marker handling, and its dedicated tests and documentation. Current Zen settings, classifier behavior, Pause/Resume behavior, and the production manifest are otherwise unchanged. The authoritative commit is the clean, synchronized `develop` HEAD that contains this document; always resolve and verify its exact SHA from Git rather than relying on a self-referential hash embedded here. There is no known release blocker. No 0.6.1 merge to `main`, tag, GitHub Release, WordPress.org upload, or other publication has been performed.

The distinction is deliberate:

- **0.6.0 is publicly released.** `main` and tag `v0.6.0` currently resolve to `bf8771b`.
- **0.6.1 is a release-qualified corrective candidate and remains unpublished.**

## 2. Product definition

Zen is a local WordPress admin-cleanup plugin for administrators who want fewer promotional notices, review requests, upsells, and marketing panels without breaking legitimate administration.

Its governing principle is: **reduce promotional clutter conservatively; avoiding false positives matters more than hiding every promotion.** If classification is uncertain, Zen keeps the content visible.

Zen operates only in `wp-admin`. Classification runs locally against the browser DOM. It has no public-site behavior, cloud classifier, telemetry, analytics, or network requests.

## 3. Current shipped capabilities

The 0.6.1 candidate provides:

- a conservative DOM classifier with operational evidence taking priority;
- separate settings for promotional notices, review requests, and other promotional UI;
- mandatory protections for operational, security, error, form, dialog, list-table, and functional admin content;
- post-load dynamic-content handling through a bounded, deduplicated `MutationObserver` queue;
- session-scoped Pause/Resume with faithful display, priority, ARIA, and diagnostic-state restoration;
- a native settings screen at **Settings → Zen by MPTY**;
- local-only Guard by MPTY and Sign by MPTY status reporting as Active, Installed, or Not installed;
- diagnostics exposed only when both `WP_DEBUG` and `MPTY_ZEN_DEBUG` are enabled;
- allowlisted settings persistence; and
- uninstall cleanup for Zen's canonical option.

These are shipped capabilities. Optional work listed later is not.

## 4. Architecture

Zen intentionally uses a small WordPress-native architecture rather than a framework:

- `zen-by-mpty.php` — plugin header, constants, controller loading, and bootstrap.
- `includes/class-mpty-zen.php` — final `MPTY_Zen` admin controller, hooks, settings, assets, page rendering, and local product-state detection.
- `assets/js/classifier.js` — dependency-free classification policy and scoring.
- `assets/js/admin.js` — DOM feature collection, suppression/restoration, Pause/Resume, diagnostics, batching, and observation.
- `uninstall.php` — canonical owned-data cleanup.
- `scripts/production-manifest.php` and `scripts/build-production.php` — exact production boundary, deterministic build, and validation.

The important separation is:

1. classification policy decides whether evidence is promotional, review-oriented, operational, or ambiguous;
2. DOM integration suppresses and restores eligible elements without changing third-party data; and
3. the WordPress controller owns settings, capabilities, rendering, and asset loading.

## 5. Classifier safety model

Suppression requires positive container-level evidence. A word such as “Upgrade,” “Pro,” or “Premium” does not by itself make a functional panel eligible.

Representative protected categories include database and core errors, security notices, operational failures, required configuration, forms, dialogs, list tables, functional panels, and legitimate admin workflows. Generic large containers are not hidden merely because a child action looks promotional.

The automated classifier suite retains explicit **SHOULD HIDE**, **MUST KEEP**, and **AMBIGUOUS KEEP** groups. Live acceptance also verified that functional content containing “Premium” and “Upgrade” wording remained visible while a genuine promotional card was suppressed.

Zen does not claim perfect classification. Its safe failure mode is to leave uncertain content visible.

## 6. Dynamic content

`assets/js/admin.js` observes post-load additions inside the admin content area. Added nodes are deduplicated, reduced to the broadest pending ancestor where possible, and processed in batches of at most 40 nodes. Large queues yield to a later task instead of creating an unbounded synchronous scan.

Qualification verified that dynamically inserted promotional content is suppressed, dynamically inserted legitimate operational content remains visible, and no duplicate observer or repeated-processing loop appears across Resume cycles. Zen does not contain a generalized background-job or unbounded observer architecture.

## 7. Pause / Resume contract

### Pause

Pause disconnects the observer, clears pending nodes, and restores previously suppressed eligible elements. It restores the original inline `display` value and priority, the original `aria-hidden` state, and the original `data-mpty-zen-reason` state. It retains the internal suppression state required for a later Resume. Pause state is stored for the browser tab in `sessionStorage`.

### Resume

Resume clears the session Pause value, immediately scans currently present eligible admin content, suppresses restored promotions again, and restarts dynamic observation.

### Permanent regression boundary

The released 0.6.0 behavior contained a real defect. A promotion could be suppressed while the document was parsing, then moved by WordPress into Zen's protected `.mpty-zen-wrap`. Pause restored it and deleted its suppression-state entry. Resume then treated it as ordinary protected Zen content and left it visible even while the button and help text reported Zen active.

The 0.6.1 correction makes suppression state survive Pause. Content in Zen's protected wrapper remains protected unless Zen previously suppressed that exact element. The previously suppressed element can therefore be reevaluated on Resume. Repeated Pause/Resume cycles are stable. This lifecycle contract must remain a regression target.

## 8. Settings and data model

Zen owns no custom tables, user metadata, files, cron events, or remote records.

Persisted/site-local state:

- canonical settings option: `mpty_zen_settings`;
- browser-tab Pause state: `sessionStorage` key `mpty_zen_reveal_v1`.

The canonical settings array contains four allowlisted integer flags: `enabled`, `hide_promotional_notices`, `hide_review_nags`, and `hide_promotional_ui`. The Settings API sanitize callback discards unknown keys and normalizes each known checkbox to `0` or `1`.

Deactivation has no destructive hook: settings remain available for reactivation, while Zen's admin scripts no longer run. Reactivation resumes with the retained settings. Uninstall removes `mpty_zen_settings`.

## 9. Privacy and security boundaries

Zen inspects admin-page DOM content locally in the administrator's browser. Notice contents are not transmitted. There is no telemetry, tracking, remote classifier, REST endpoint, AJAX endpoint, or `admin-post` state-changing endpoint.

The settings screen requires `manage_options`. Persistence uses the WordPress Settings API; `settings_fields()` supplies the standard Settings API nonce and action fields. Settings are allowlisted and normalized. Rendered URLs, attributes, translated labels, descriptions, status text, and version output use context-appropriate WordPress escaping.

Zen is an admin presentation product, not a security plugin. Its security boundary is limited to preserving authorization, output safety, data ownership, and conservative treatment of third-party admin content.

## 10. Free, paid, and licensing

Zen is currently one Free product. It has no Free/Pro split, paid entitlement model, MPTY Licensing Server client, trial, downgrade-from-paid behavior, updater/licensing dependency, or paid feature discovery.

“Pro” and “Premium” in classifier vocabulary describe possible third-party promotional content, not Zen product tiers. Cross-product consistency is not a reason to add licensing to Zen.

## 11. MPTY product-status integration

Zen's settings page displays local status for Guard by MPTY and Sign by MPTY. It uses WordPress's installed-plugin inventory and `is_plugin_active()`. Standard plugin paths are recognized directly; exact plugin names provide a fallback for nonstandard directories.

The three visible states are Active, Installed, and Not installed. Detection is entirely local, does not query an account or license, and creates no dependency. Zen remains fully functional when Guard and Sign are absent.

## 12. Responsive and accessibility evidence

Real 0.6.1 acceptance covered approximately 1440px desktop, 768px tablet, and 390px mobile widths. It verified:

- no Zen-caused horizontal overflow;
- usable settings and product-status rows;
- keyboard operation and visible focus;
- Space-key activation for Pause and Resume;
- `aria-pressed` synchronized with the actual suppression state;
- explicit text status rather than color-only meaning; and
- a clean browser console during the accepted flows.

This is keyboard and practical accessibility acceptance, not a claim of formal screen-reader or advanced assistive-technology certification.

## 13. Multisite and network scope

The qualified release is deliberately **site-scoped**. Network-wide settings, network-activation lifecycle, new-site provisioning, and network uninstall semantics are not implemented or qualified.

This is an accepted product boundary, not a pre-publication defect. Future multisite support is optional and would require deliberate design and dedicated qualification.

## 14. Downgrade and compatibility model

Zen does not justify a Guard-style downgrade framework. It stores a small allowlisted settings array, with no custom schema or table state that could make an older runtime destructively interpret newer data.

The current declared and exercised compatibility boundary is:

- WordPress: requires at least 6.4; readme tested up to 7.1;
- PHP: requires PHP 7.4;
- executable CI: WordPress 6.4 / PHP 7.4 and current WordPress / PHP 8.4;
- production syntax: PHP 7.4; source gates: PHP 8.2 and PHP 8.4.

Compatibility claims must be reconsidered if headers, readme metadata, CI, or runtime requirements change.

## 15. Distribution and build architecture

Zen has one Free WordPress.org production profile. It does not have Guard's separate direct/commercial profile.

The authoritative six-file production manifest is `scripts/production-manifest.php`. `scripts/build-production.php` builds a deterministic ZIP and validates its exact file set, one-directory ZIP layout, versions, and production PHP syntax. `scripts/release.ps1` performs fail-fast preflight on clean `develop`, requires explicit manual-QA acknowledgement, runs the release checks, and records the checksum. `RELEASE-CHECKLIST.md`, `RELEASE-PROCESS.md`, and `ENGINEERING-STANDARD.md` are binding repository guidance.

GitHub Actions independently runs source/static checks, PHP 7.4 production syntax, minimum/current WordPress compatibility, exact artifact build and revalidation, checksum verification, artifact handoff, and Plugin Check. Ordinary CI does not merge, tag, publish, or create a GitHub Release. Promotion and WordPress.org publication remain separately authorized human-controlled steps.

## 16. Release history and artifacts

### Public release 0.6.0

- Tag: `v0.6.0`
- Published ZIP: `C:\Users\lapej\Desktop\MPTY projects\Zen\zen-by-mpty-0.6.0.zip`
- SHA-256: `e91587a8f44505c183f104634727f5fb473f6ab691dd03d669c9cec756f3b17c`

The exact 0.6.0 ZIP was used for retroactive LocalWP qualification. The Pause/Resume defect was reproduced from that artifact.

### Qualified, unpublished candidate 0.6.1

- Reconstruction anchor: current clean, synchronized `develop` HEAD containing this handoff
- Current local ZIP: `C:\Users\lapej\Desktop\MPTY projects\Zen\plugin\zen-by-mpty\build\zen-by-mpty-0.6.1.zip`
- SHA-256: `284979f01968c705fa253b72b0cdba6e24c7d1acbcccb80655c02adcbc266975`
- File count: 6 files beneath one `zen-by-mpty/` directory.

Manifest contents:

- `assets/js/admin.js`
- `assets/js/classifier.js`
- `includes/class-mpty-zen.php`
- `readme.txt`
- `uninstall.php`
- `zen-by-mpty.php`

The build directory is not a permanent identity anchor. The candidate commit, authoritative manifest, deterministic builder, version, and checksum are the reconstruction anchors.

## 17. Real acceptance evidence

Live LocalWP/browser qualification exercised the exact cleaned 0.6.1 ZIP for activation, settings persistence, Pause/Resume control state, and WordPress uninstall cleanup. The earlier full 0.6.1 acceptance also exercised:

- installation of the exact 0.6.0 ZIP;
- settings rendering and saving;
- deactivate/reactivate behavior;
- uninstall cleanup;
- promotional notice, review request, and upsell suppression;
- dynamic promotional suppression;
- continued visibility of database errors, security warnings, operational failures, settings forms, dialogs, list tables, and a functional Premium/Upgrade panel;
- continued visibility of legitimate dynamically inserted content;
- Pause restoration of display, priority, ARIA, and diagnostic state;
- immediate Resume re-suppression after the 0.6.1 fix;
- at least two repeated Pause/Resume cycles;
- suppression of a dynamic promotion inserted after Resume;
- desktop/tablet/mobile layout acceptance;
- keyboard/focus and Space-key operation; and
- a clean browser console.

This live evidence is distinct from the automated PHPUnit, classifier, DOM, compatibility, artifact, and Plugin Check evidence below.

## 18. 0.6.1 defect and correction

Retroactive 0.6.0 qualification exposed one product defect: Pause → Resume could report Zen active while an already-restored promotion remained visible.

Root cause: parser-time suppression, followed by WordPress relocation into Zen's protected wrapper, followed by deletion of suppression state during Pause.

Correction: suppression state survives Pause, and previously suppressed content relocated into the protected wrapper remains eligible for reevaluation on Resume. This is a permanent regression boundary, not an invitation to redesign classifier policy.

## 19. Final qualification evidence

Qualified candidate: current clean, synchronized `develop` HEAD containing this handoff.

Final GitHub Actions evidence: the latest successful checks run for the candidate `develop` HEAD in <https://github.com/Justinas-beep/zen-by-mpty/actions>.

The final run completed successfully and proved:

- WordPress 6.4 / PHP 7.4 activation and integration smoke: PASS;
- current WordPress / PHP 8.4 activation and integration smoke: PASS;
- PHP 7.4 production syntax: PASS;
- complete source/static gates on PHP 8.2 and PHP 8.4: PASS;
- deterministic production artifact build and revalidation: PASS;
- checksum verification before and after CI artifact handoff: PASS; and
- WordPress Plugin Check against the production files: PASS.

The final local release gate recorded:

- PHP syntax: PASS;
- PHPStan: PASS;
- PHPCS/WPCS: PASS;
- PHPUnit: 7 tests, 7 assertions, PASS;
- JavaScript: 50 tests, PASS;
- deterministic artifact validation: PASS;
- `git diff --check`: PASS; and
- candidate ZIP SHA-256: `284979f01968c705fa253b72b0cdba6e24c7d1acbcccb80655c02adcbc266975`.

The candidate product behavior remains the accepted 0.6.1 Pause/Resume correction. The only later runtime change removes the never-public development-era settings migration; no classifier, settings semantics, or user-facing workflow changed.

## 20. CI infrastructure lesson

The first qualification run, `36313223467`, failed only in the WordPress 6.4 / PHP 7.4 environment before Zen executed. `@wordpress/env` 11.14.0 generated a Bullseye container whose rotated Debian security-package references returned HTTP 404. A failed-job retry reproduced the same external failure on another runner.

The bounded correction updated the locked development dependency from `@wordpress/env` 11.14.0 to 11.16.0. That upstream version handles Bullseye's archived repositories. No Zen runtime or production-manifest file changed, and PHP 7.4 coverage was not removed or weakened. Replacement run `36313623950` actually started WordPress 6.4 on PHP 7.4, activated Zen, and passed the integration smoke test.

Future engineers must distinguish environment provisioning failures from Zen failures and must not “fix” product code for a package-mirror problem.

## 21. Engineering workflow

The user is not a developer. ChatGPT acts as development/product advisor: keep guidance concise, define bounded Codex tasks, and translate evidence into operational decisions. Codex performs repository engineering: read repository standards and code, make bounded changes, and run proportionate checks.

### Normal development

1. Define one bounded change.
2. Inspect affected code and trust boundaries.
3. Implement the smallest coherent diff.
4. Run focused tests and touched static analysis.
5. Run `git diff --check`.
6. Do not run a full gate, build, commit, or push unless requested.

### Checkpoint

1. Review the complete diff.
2. Run the full gate.
3. Validate the artifact where appropriate.
4. Commit only with authorization.
5. Push explicitly with `git push origin develop` only when requested.
6. Treat GitHub Actions as independent CI evidence.

### Release qualification

1. Establish exact source and artifact identity.
2. Exercise the exact artifact in LocalWP/browser acceptance.
3. Verify minimum/current compatibility and Plugin Check.
4. Record final checksum, candidate commit, and CI run.
5. Obtain separate authorization before promotion, tagging, or publication.

Do not create infrastructure merely to duplicate LocalWP plus GitHub Actions.

## 22. Local development environment

- Repository: `C:\Users\lapej\Desktop\MPTY projects\Zen\plugin\zen-by-mpty`
- LocalWP site: `C:\Users\lapej\Local Sites\mpty-projects3`
- Installed plugin path: `C:\Users\lapej\Local Sites\mpty-projects3\app\public\wp-content\plugins\zen-by-mpty`
- Mapping: Windows junction to `C:\Users\lapej\Desktop\MPTY projects\Zen\plugin\zen-by-mpty`
- Disposable qualification fixture: absent at parking time.

LocalWP is the normal browser-acceptance environment. For exact-ZIP qualification, the junction may be parked temporarily and replaced by the unpacked candidate; restore and verify the junction afterward. Never record LocalWP or WordPress credentials in repository documentation.

## 23. Deferred and optional work

The following items are deliberately deferred and do not block publishing 0.6.1:

- multisite/network-wide support;
- additional classifier rules without real compatibility evidence;
- advanced assistive-technology qualification beyond the completed keyboard/accessibility acceptance;
- WordPress.org SVN publication work if not already part of the human release channel;
- a paid Zen tier, licensing, trials, or entitlement architecture;
- telemetry or a cloud classifier; and
- unnecessary framework, architecture, or refactor work.

## 24. Publication status and resume point

**Zen 0.6.0 is publicly released.**

**Zen 0.6.1 is a release-qualified corrective candidate and is not yet published.** It has not been merged to `main`, tagged as `v0.6.1`, published as a GitHub Release, uploaded to WordPress.org, or otherwise distributed as the replacement public release.

There is no known product release blocker. Do not reopen product development or qualification without a concrete new reason. Resume from:

**publication decision → final source/artifact identity confirmation → fail-safe promotion/tag/publication under `RELEASE-PROCESS.md`.**

If runtime source or artifact content changes after the clean, synchronized candidate `develop` HEAD, reconsider the relevant qualification before publication.

## 25. STARTING A NEW CHAT

Copy and paste this into a completely new ChatGPT conversation:

```text
I am not a developer. Keep instructions concise, operational, and explicit. Act as my development/product advisor; Codex performs repository engineering.

We are resuming Zen by MPTY. First read the authoritative repository handoff:
C:\Users\lapej\Desktop\MPTY projects\Zen\plugin\zen-by-mpty\ZEN-PARKED-STATUS.md

Treat the repository and code as authoritative. Important anchors:
- Repository: C:\Users\lapej\Desktop\MPTY projects\Zen\plugin\zen-by-mpty
- GitHub: https://github.com/Justinas-beep/zen-by-mpty
- 0.6.0 is publicly released; tag v0.6.0.
- 0.6.1 is release-qualified but unpublished.
- Qualified candidate commit: resolve the clean, synchronized develop HEAD containing this handoff.
- Qualified ZIP SHA-256: 284979f01968c705fa253b72b0cdba6e24c7d1acbcccb80655c02adcbc266975
- Final CI: latest successful checks run for that exact develop commit.
- Zen is site-scoped for this release.
- Zen has no Pro/licensing/entitlement architecture.
- Pause/Resume is a permanent regression boundary: Pause must restore exact state and Resume must immediately reevaluate the same existing promotion.
- Publication is the resume point. Do not reopen completed architecture, classifier, UX, or qualification work without a concrete new reason.
- LocalWP provides browser acceptance; GitHub Actions provides independent source, compatibility, artifact, and Plugin Check evidence.

Before advising publication, verify develop/HEAD/status and the candidate anchor. If current repository evidence conflicts with the handoff, ask for evidence or investigate rather than guessing. Do not merge, tag, publish, or modify files without a bounded explicit instruction.
```

## 26. CODEX RESUME PROMPT

Copy and paste this into a new Codex task:

```text
ZEN BY MPTY — FACTUAL PARKED-STATE RESUME CHECK

Repository:
C:\Users\lapej\Desktop\MPTY projects\Zen\plugin\zen-by-mpty

Read ZEN-PARKED-STATUS.md first, then inspect the current branch, status, HEAD, remote tracking state, tags, release files, candidate ZIP if present, and GitHub CI metadata.

Qualified anchor:
- branch: develop
- candidate: 0.6.1
- commit: resolve the clean, synchronized develop HEAD containing this handoff
- ZIP SHA-256: 284979f01968c705fa253b72b0cdba6e24c7d1acbcccb80655c02adcbc266975
- final CI: latest successful checks run for that exact develop commit

Verify whether the repository still matches that qualified anchor and whether any source/artifact drift exists. Report publication readiness factually.

Do not modify files merely because time has passed. Do not rerun completed qualification unless source/artifact identity changed or a concrete new reason exists. Do not merge main, tag, publish, rebuild, commit, or push. Stop after the factual resume report.
```

## 27. Immediate resume pointer

The immediate next decision is whether to publish the already qualified 0.6.1 corrective release. Before acting, verify clean and synchronized `develop`, resolve its exact HEAD, confirm the artifact checksum above, and confirm the successful CI run belongs to that exact commit. Then follow the fail-safe promotion and separately authorized publication process. Do not restart product development by default.

## 28. Document validation record

At creation time, the following were independently verified:

- repository path, branch, clean/synchronized state, HEAD, remote, and tags;
- 0.6.0 ZIP path and SHA-256;
- 0.6.1 ZIP path, SHA-256, one-directory layout, six-file count, and exact manifest;
- final CI run, candidate commit, and every required job result;
- WordPress/PHP declarations and CI matrix;
- settings keys, session key, uninstall behavior, capabilities, and product-state detection;
- LocalWP site path and junction target; and
- absence of the disposable LocalWP qualification fixture.

This document contains no credentials or secrets. Run `git diff --check` after every edit to it.
