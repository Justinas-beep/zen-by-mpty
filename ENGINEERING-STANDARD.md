# Zen by MPTY WordPress Plugin Engineering Standard

This standard applies to every Zen by MPTY change. It is binding release policy. Where automation cannot verify a rule, the reviewer must record the evidence and judgment used.

## 1. Product and trust boundary

Zen makes `wp-admin` quieter by conservatively suppressing non-essential promotional content. It does not redesign WordPress and is not a security, performance, analytics, AI, or remote-service product.

Zen's primary trust boundary is the admin DOM produced by WordPress, themes, and third-party plugins. A false negative leaves clutter visible; a false positive removes useful content. False positives are more serious. When uncertain, Zen must keep content visible.

- Classifier, protected-selector, ancestor-suppression, and MutationObserver changes are release-sensitive.
- No large generic admin container may be hidden solely because one child looks promotional.
- Pause/Resume must restore the exact pre-existing inline display value and priority and must not damage accessibility state.
- MutationObserver changes require compatibility and performance review on complex admin pages.
- Zen must never inject recurring nags or promotional banners. MPTY discovery belongs only in Zen's own interface and must remain secondary to its controls.

## 2. Mandatory engineering rules

### Inspect before changing

- Read the relevant implementation, callers, tests, configuration, stored-data behavior, recent diff, and repository instructions before editing.
- Identify the trust boundary, compatibility constraints, failure modes, and highest change-risk classification.
- Prefer the smallest coherent change. Do not mix opportunistic refactoring or unrelated architecture into a task.
- Treat generated, copied, and AI-generated code as untrusted until it receives the same review and tests as human-written code.

### WordPress-native boundaries

- Prefer WordPress APIs for settings, capabilities, nonces, URLs, HTTP, filesystem, database, scheduling, escaping, sanitization, and translation.
- Every privileged state-changing request must independently establish authorization and intent with an appropriate capability and verified nonce.
- Validate input against its semantic type and allowlist, sanitize it, and escape output for its final context.
- Prefix global classes, functions, hooks, options, constants, handles, request actions, and browser globals with the MPTY Zen identity.

### Network, privacy, and dependencies

- Do not add telemetry, marketing tracking, a remote classifier, downloaded executable behavior, or a remote dependency for local UI.
- Any new network request requires an explicit product need, privacy/data-flow review, bounded timeouts, safe failure, and WordPress.org compliance review.
- Every distributed file, library, font, icon, image, and other asset must be GPL-compatible. Record provenance and retain required copyright and license notices.
- Prefer libraries and components already provided by WordPress. Add dependencies only when necessary, and review and pin versions, licenses, provenance, maintenance status, security posture, transitive risk, and production footprint.
- Development-only dependencies must never enter the production artifact. Runtime dependencies should remain exceptional.
- Distributed source must remain human-readable. If a future build step minifies, bundles, or generates production material, publish the corresponding source and reproducible build instructions in an accessible location disclosed in the readme.
- External calls must have a necessary plugin purpose, accurate privacy disclosure, and user consent where required. Tracking, public credits, and attribution links must be opt-in and must not be disguised as operational traffic.

### Compatibility and WordPress quality

- Support the PHP and WordPress minimums declared in the plugin header and `readme.txt`; CI must exercise the minimum syntax/runtime boundary and a current supported WordPress release.
- Follow WordPress Coding Standards. Make user-facing text translation-ready with the `zen-by-mpty` text domain and escape translated output in context.
- Preserve semantic controls, labels, keyboard operation, focus behavior, control state, and non-visual status feedback.
- Meet current WordPress.org requirements, including GPL compatibility, truthful readme claims, privacy transparency, human-readable source, no prohibited tracking, and Plugin Check.

### Data lifecycle and multisite

- Document every option, user-meta value, transient, table, file, scheduled event, and remote data flow before release.
- Stored-format changes require a small versioned, repeatable migration with clean-install, upgrade, already-migrated, and failure-path coverage proportional to the data.
- Uninstall must remove Zen-owned data according to documented policy without touching other plugins' data.
- Zen options are site-scoped. Network activation, new-site behavior, and network uninstall must be explicitly supported and tested or explicitly documented as unsupported/site-by-site.
- Review downgrade behavior whenever persisted structures change.

## 3. Classifier and DOM safety rules

- Suppression requires positive, container-level evidence. A label such as `Upgrade`, `Pro`, or `Premium` is insufficient by itself.
- Operational evidence wins over promotional evidence. Destructive confirmations, failures, required configuration, security notices, validation errors, and functional interfaces must remain visible.
- Generic forms, tables, dialogs, list tables, main wrappers, plugin cards, and functional panels are protected unless a future narrowly defined rule has direct false-positive coverage.
- Classifier tests must retain three explicit groups: SHOULD HIDE, MUST KEEP, and AMBIGUOUS KEEP. MUST KEEP is the priority group.
- Real-world regressions should be represented by minimal, non-sensitive fixtures. Do not retain customer content or personal data.
- DOM work must be bounded, deduplicated, cached where safe, and resistant to observer loops. Do not trade correctness for an unmeasured micro-optimization.

## 4. Source, production, and release integrity

- The source repository retains tests, documentation, static-analysis configuration, CI, and build tools.
- Production ZIPs contain only files in an exact authoritative manifest. Development dependencies, tests, caches, local files, credentials, and repository metadata are excluded.
- Builds and validation must be deterministic and reproducible from a clean checkout.
- Validate exact manifest contents, top-level ZIP structure, version agreement, syntax, checksum, and installability against the final artifact.
- Plugin header version, internal constants, `readme.txt` stable tag/changelog, tag, artifact filename, and release metadata must agree.
- Plugin Check runs against the exact production artifact and is a release gate.
- CI uses least privileges and immutable commit-pinned actions where practical. Publish privileges must be isolated from ordinary checks.

### WordPress.org distribution governance

- Zen is one complete, standalone Free plugin. It has no Free/Pro split, entitlement client, licensing client, paid trial, usage quota, custom updater, or remote feature dependency. Do not invent any of those systems merely for distribution.
- The exact proposed WordPress.org ZIP requires an explicit semantic compliance approval against the current official plugin guidelines. Record the distribution channel, commit, production-manifest identity, ZIP filename and SHA-256, reviewer, review date, and guidance date or source used.
- Plugin Check and green CI are necessary technical evidence, but neither is WordPress.org distribution approval. A reviewer must separately establish that the exact ZIP is complete and operational, contains only permitted GPL-compatible material, exposes readable source, has truthful metadata and privacy disclosures, and does not bundle locked premium behavior.
- The plugin name, slug, author identity, trademarks, third-party names, and public claims must be authorized and accurate. The plugin header, internal version, `readme.txt` Stable Tag and changelog, SVN tag, ZIP name, and release metadata must identify the same intended release.
- A code release requires a new version and corresponding changelog entry. A readme-only update must be identified as such and must not imply a code release or silently change the qualified code artifact.
- If commercial functionality, paid access, quotas, trials, license enforcement, or a substantive external service is proposed later, complete a lightweight distribution review before implementation. That review must define the Free plugin's complete operational boundary, code and artifact ownership, local and remote data flows, consent and privacy behavior, offline and failure behavior, update path, GPL/source obligations, and WordPress.org permissibility. It does not authorize or require a commercial architecture now.
- Add future automated compliance checks only when they close a real gap. The current manifest, version, artifact, CI, and Plugin Check controls already cover mechanical package integrity; GPL provenance, naming rights, operational completeness, external-service acceptability, and exact-artifact approval remain human review. If a future commercial boundary or substantive service creates a machine-verifiable separation rule, add the narrow check with that change.

## 5. Automated and review gates

The applicable gates must pass on the exact release candidate:

- `composer check`: PHP syntax, PHPStan, PHPCS/WPCS, PHPUnit where applicable, and JavaScript tests;
- classifier false-positive regression coverage;
- WordPress minimum/current activation and smoke tests;
- Plugin Check against the built artifact;
- exact production-manifest and ZIP validation;
- required CI checks and focused tests for the change.

Automation does not replace review. Review capability/nonce boundaries, sanitization, escaping, DOM safety, performance bounds, compatibility, i18n, accessibility, data lifecycle, privacy, packaging, and manual QA relevant to the change.

Record material conclusions as:

- **VERIFIED** - directly established by executed checks or definitive evidence;
- **REVIEWED** - established by code/configuration review but not executed end to end;
- **INFERRED** - reasoned but still requiring confirmation.

## 6. Severity and release verdicts

- **CRITICAL** - direct compromise, arbitrary execution, authorization bypass, destructive broad data loss, or equivalent systemic impact. Block release.
- **HIGH** - serious security, availability, or false-positive suppression risk, or missing evidence for a release-sensitive behavior. Block release.
- **MEDIUM** - material weakness needing meaningful conditions or having limited impact. Fix before release unless explicitly accepted with mitigation and ownership.
- **LOW** - limited-risk defect or hardening opportunity. Fix when practical or track it.
- **INFO** - observation or justified exception without demonstrated functional or security impact.

Final verdicts:

- **PASS** - all gates pass and no release-blocking finding remains;
- **PASS WITH RECOMMENDATIONS** - all gates pass and only documented LOW/INFO work remains;
- **BLOCK RELEASE** - a gate fails, required evidence is missing, or a CRITICAL/HIGH or unaccepted MEDIUM finding remains.

## 7. Change-risk classification

### LOW

Documentation, restrained copy changes, isolated styling, and settings-page presentation that does not change state or classification. Require focused review, relevant checks, and manual QA when visible.

### MEDIUM

Settings behavior, ordinary feature logic, persistence changes without destructive migration, meaningful admin workflows, dependencies, or network behavior. Require focused tests, full gates, compatibility review, and local QA.

### HIGH

Classifier decisions, protected selectors, ancestor suppression, Pause/Resume restoration, MutationObserver scope/bounds, capabilities/nonces, migrations, filesystem/database mutation, secrets, and update integrity. Require explicit trust-boundary review, false-positive and failure-path tests, full gates, compatibility/performance review, and targeted manual QA.

### CRITICAL

Fundamental authorization bypass prevention, remotely supplied executable behavior, arbitrary execution controls, or destructive broad migrations. Require HIGH evidence plus dedicated adversarial review.

The risk cannot be downgraded merely because the diff is small.

## 8. Working and test protocol

1. Define scope and acceptance criteria.
2. Inspect source, tests, data flow, current status, and relevant standards.
3. Record risk and trust boundaries.
4. Implement the smallest coherent change.
5. Add a regression that would fail without the behavior being protected.
6. Review the diff for unintended suppression, state loss, privacy, and unrelated changes.
7. Run focused checks during implementation and the broad gate once at completion.
8. Perform representative manual QA for user-visible and environment-dependent behavior.
9. Report commands, results, omissions, evidence classification, unresolved risks, and verdict.

Never claim a test passed, a defect is fixed, or a release is ready without evidence.

Documentation-only governance changes require focused Markdown, consistency, and diff checks. They do not require the runtime, artifact, compatibility, or Plugin Check gates unless they alter release metadata, packaged content, product behavior, or another release-sensitive input.
