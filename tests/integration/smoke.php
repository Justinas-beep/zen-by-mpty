<?php
/**
 * Disposable WordPress activation and settings smoke coverage.
 *
 * @package MPTYZen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * Fail a disposable compatibility run with a clear reason.
 *
 * @param bool   $condition Expected condition.
 * @param string $message   Failure reason.
 * @return void
 * @throws RuntimeException When the smoke expectation is not met.
 */
function mpty_zen_smoke_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( esc_html( $message ) );
	}
}

global $wp_version;
mpty_zen_smoke_assert( defined( 'MPTY_ZEN_EXPECTED_WP' ), 'Expected WordPress version was not configured.' );
mpty_zen_smoke_assert( defined( 'MPTY_ZEN_EXPECTED_PHP' ), 'Expected PHP version was not configured.' );
mpty_zen_smoke_assert( 0 === strpos( $wp_version, MPTY_ZEN_EXPECTED_WP ), 'Unexpected WordPress compatibility version.' );
mpty_zen_smoke_assert( 0 === strpos( PHP_VERSION, MPTY_ZEN_EXPECTED_PHP ), 'Unexpected PHP compatibility version.' );
mpty_zen_smoke_assert( is_plugin_active( 'zen-by-mpty/zen-by-mpty.php' ), 'Zen did not activate.' );
mpty_zen_smoke_assert( class_exists( 'MPTY_Zen' ), 'Zen controller is unavailable after activation.' );
mpty_zen_smoke_assert( '0.6.0' === MPTY_ZEN_VERSION, 'Unexpected Zen runtime version.' );

wp_set_current_user( 1 );
$zen = MPTY_Zen::instance();

$saved = $zen->sanitize_settings(
	array(
		'enabled'                   => 1,
		'hide_promotional_notices' => 0,
		'hide_review_nags'          => 1,
		'hide_promotional_ui'       => 1,
		'unexpected'                => 'discard',
	)
);
update_option( 'mpty_zen_settings', $saved, false );
$stored = get_option( 'mpty_zen_settings' );
mpty_zen_smoke_assert( is_array( $stored ), 'Zen settings were not stored as an array.' );
mpty_zen_smoke_assert( ! isset( $stored['unexpected'] ), 'Zen retained an unknown setting.' );
mpty_zen_smoke_assert( 0 === $stored['hide_promotional_notices'], 'Zen did not preserve the normalized setting.' );

$zen->enqueue_admin_assets( 'index.php' );
mpty_zen_smoke_assert( wp_script_is( 'mpty-zen-classifier', 'enqueued' ), 'Classifier script was not enqueued in wp-admin.' );
mpty_zen_smoke_assert( wp_script_is( 'mpty-zen-admin', 'enqueued' ), 'Admin integration script was not enqueued in wp-admin.' );

ob_start();
$zen->render_settings_page();
$settings_html = ob_get_clean();
mpty_zen_smoke_assert( is_string( $settings_html ) && false !== strpos( $settings_html, 'mpty-zen-toggle-reveal' ), 'Settings page did not render.' );
mpty_zen_smoke_assert( false !== strpos( $settings_html, 'Other MPTY tools' ), 'MPTY tools section did not render inside Zen settings.' );

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	define( 'WP_UNINSTALL_PLUGIN', 'zen-by-mpty/zen-by-mpty.php' );
}
require dirname( __DIR__, 2 ) . '/uninstall.php';
mpty_zen_smoke_assert( false === get_option( 'mpty_zen_settings', false ), 'Uninstall did not remove Zen settings.' );
mpty_zen_smoke_assert( false === get_option( 'mpty_zen_migration_050', false ), 'Uninstall did not remove Zen migration state.' );

fwrite( STDOUT, 'Zen compatibility smoke test passed.' . PHP_EOL );
