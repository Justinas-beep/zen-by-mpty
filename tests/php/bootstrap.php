<?php
/**
 * Minimal WordPress function seam for isolated Zen unit tests.
 *
 * @package MPTYZen
 */

define( 'ABSPATH', dirname( __DIR__, 2 ) . DIRECTORY_SEPARATOR );
define( 'MPTY_ZEN_VERSION', '0.6.1' );
define( 'MPTY_ZEN_FILE', ABSPATH . 'zen-by-mpty.php' );

/** Test option storage. */
$GLOBALS['mpty_zen_test_options'] = array();
$GLOBALS['mpty_zen_test_active_plugins'] = array();

function add_action() {}
function add_filter() {}

function plugin_basename( $file ) {
	return basename( $file );
}

function get_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['mpty_zen_test_options'] ) ? $GLOBALS['mpty_zen_test_options'][ $name ] : $default;
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, $args );
}

/**
 * Test seam for WordPress plugin activation state.
 *
 * @param string $plugin_file Plugin basename.
 * @return bool
 */
function is_plugin_active( $plugin_file ) {
	return in_array( $plugin_file, $GLOBALS['mpty_zen_test_active_plugins'], true );
}

require_once ABSPATH . 'includes/class-mpty-zen.php';
