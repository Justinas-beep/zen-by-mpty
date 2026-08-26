=== Zen by MPTY ===
Tags: admin, notices, cleanup, productivity, dashboard
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Keep WordPress admin focused by hiding promotional clutter while keeping important notices visible.

== Description ==

Zen by MPTY reduces noise in WordPress admin by hiding promotional notices, review requests, upsells, sales banners and other promotional content.

Important notices stay visible. Zen is designed to preserve operational, security, update, compatibility and error information. If Zen is unsure about an item, it leaves it visible.

Zen only changes what is shown in WordPress admin. It does not delete another plugin's notices, settings, database records or other data.

Zen runs locally in WordPress admin. It does not modify the public-facing website, make remote requests, collect analytics or send telemetry.

== Installation ==

1. Install and activate Zen by MPTY.
2. Zen starts working immediately with conservative defaults.
3. To change what Zen hides, go to Settings > Zen by MPTY.

== Frequently Asked Questions ==

= What does Zen hide? =

Zen targets promotional notices, review and rating requests, upgrade offers, sales banners, cross-promotions, upsells and similar promotional content in WordPress admin.

= What does Zen keep visible? =

Zen is designed to keep important operational notices visible, including security, update, compatibility and error information.

= What happens if Zen is unsure? =

The item stays visible. Zen favors safety over hiding more content.

= Does Zen delete notices or data from other plugins? =

No. Zen only changes presentation in WordPress admin. Deactivating Zen returns the admin interface to its normal behavior.

= Does Zen affect the public website? =

No. Zen does not load frontend scripts or styles and does not modify theme output.

= Does Zen send data to MPTY Projects? =

No. Zen works locally and makes no remote requests.

== Changelog ==

= 0.5.0 =
* Renamed the previous development identity to Zen by MPTY and changed the developer identity to MPTY Projects.
* Migrated PHP, JavaScript, DOM, asset, settings, build and package identifiers to the canonical MPTY identity.
* Added a one-time settings migration from previous development settings to the canonical MPTY settings key.
* Changed the plugin directory/text domain to zen-by-mpty before public distribution.
* No classifier decision rules were changed.

= 0.4.4 =
* Removed the hidden-item counter from the settings screen because it did not provide useful configuration context.
* Kept the temporary Pause Zen / Resume Zen control as the only reveal mechanism.
* No classifier or suppression behavior changes.

= 0.4.3 =
* Changed the suppression counter to show unique hidden items on the current admin page only.
* Removed the accumulated browser-tab count and the unnecessary reset control.
* Simplified temporary reveal wording and removed ambiguous browser-tab terminology.
* Simplified settings labels and descriptions.
* Classifier behavior is unchanged.

= 0.4.2 =
* Reviewed and simplified all user-facing plugin text for clarity and consistency.
* Clarified the safety model: important notices stay visible and uncertain items are left visible.
* Simplified settings labels, descriptions and temporary controls.
* Simplified the WordPress.org description and FAQ.
* No classifier or suppression behavior changes.

= 0.4.1 =
* Added the required WordPress.org readme "Tested up to" metadata after Plugin Check identified the missing header.
* No classifier or runtime behavior changes.

= 0.4.0 =
* Renamed the earlier cleanup prototype to the previous Zen development identity.
* Added one-time migration for compatible development-era Clean settings.
* Removed all public-site/footer-credit functionality and frontend assets.
* Made important-notice protection mandatory rather than user-disableable.
* Split classifier scoring into a testable, dependency-free core module.
* Added conservative regression coverage for promotional, review, operational and ambiguous cases, including the real CookieYes review nag and image-only promotion patterns.
* Added opt-in development diagnostics that expose decision, scores and signal names without notice contents.
* Added per-element decision caching and bounded mutation processing to reduce repeated classification work.
* Reworked temporary reveal into a browser-tab pause/resume control.
* Simplified settings UI to native WordPress controls.
* Added deterministic release packaging and release validation tooling in the development source.

== Upgrade Notice ==

= 0.5.0 =
Zen is now Zen by MPTY. Existing 0.4.x settings are migrated automatically on first activation/admin load.
