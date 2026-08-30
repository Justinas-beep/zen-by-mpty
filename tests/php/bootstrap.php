<?php
/**
 * Minimal WordPress function seam for isolated Zen unit tests.
 *
 * @package MPTYZen
 */

define( 'ABSPATH', dirname( __DIR__, 2 ) . DIRECTORY_SEPARATOR );
define( 'MPTY_ZEN_VERSION', '0.6.0' );
define( 'MPTY_ZEN_FILE', ABSPATH . 'zen-by-mpty.php' );

/** Test option storage. */
$GLOBALS['mpty_zen_test_options'] = array();

function add_action() {}
function add_filter() {}

function plugin_basename( $file ) {
	return basename( $file );
}

function get_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['mpty_zen_test_options'] ) ? $GLOBALS['mpty_zen_test_options'][ $name ] : $default;
}

function add_option( $name, $value ) {
	if ( array_key_exists( $name, $GLOBALS['mpty_zen_test_options'] ) ) {
		return false;
	}
	$GLOBALS['mpty_zen_test_options'][ $name ] = $value;
	return true;
}

function delete_option( $name ) {
	unset( $GLOBALS['mpty_zen_test_options'][ $name ] );
	return true;
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, $args );
}

require_once ABSPATH . 'includes/class-mpty-zen.php';
