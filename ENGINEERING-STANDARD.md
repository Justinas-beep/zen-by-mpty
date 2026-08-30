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
- Add dependencies only when necessary. Review and pin versions, licenses, provenance, maintenance, transitive risk, and production footprint.
- Development-only dependencies must never enter the production artifact. Runtime dependencies should remain exceptional.

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

- **VERIFIED** — directly established by executed checks or definitive evidence;
- **REVIEWED** — established by code/configuration review but not executed end to end;
- **INFERRED** — reasoned but still requiring confirmation.

## 6. Severity and release verdicts

- **CRITICAL** — direct compromise, arbitrary execution, authorization bypass, destructive broad data loss, or equivalent systemic impact. Block release.
- **HIGH** — serious security, availability, or false-positive suppression risk, or missing evidence for a release-sensitive behavior. Block release.
- **MEDIUM** — material weakness needing meaningful conditions or having limited impact. Fix before release unless explicitly accepted with mitigation and ownership.
- **LOW** — limited-risk defect or hardening opportunity. Fix when practical or track it.
- **INFO** — observation or justified exception without demonstrated functional or security impact.

Final verdicts:

- **PASS** — all gates pass and no release-blocking finding remains;
- **PASS WITH RECOMMENDATIONS** — all gates pass and only documented LOW/INFO work remains;
- **BLOCK RELEASE** — a gate fails, required evidence is missing, or a CRITICAL/HIGH or unaccepted MEDIUM finding remains.

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
