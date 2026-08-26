<?php
/**
 * Plugin Name: Zen by MPTY
 * Description: Hides promotional notices, review requests, upsells, and other WordPress admin clutter while keeping important notices visible.
 * Version: 0.5.0
 * Author: MPTY Projects
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: zen-by-mpty
 * Requires at least: 6.4
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MPTY_ZEN_VERSION', '0.5.0' );
define( 'MPTY_ZEN_FILE', __FILE__ );
define( 'MPTY_ZEN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MPTY_ZEN_URL', plugin_dir_url( __FILE__ ) );

require_once MPTY_ZEN_DIR . 'includes/class-mpty-zen.php';

register_activation_hook( MPTY_ZEN_FILE, array( 'MPTY_Zen', 'activate' ) );

MPTY_Zen::instance();
