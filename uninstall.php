<?php
/**
 * Uninstall cleanup for Zen by MPTY.
 *
 * @package MPTYZen
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'mpty_zen_settings' );
