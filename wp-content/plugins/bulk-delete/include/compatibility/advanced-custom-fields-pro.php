<?php
/**
 * Code to enable compatibility with 'Advanced Custom Fields Pro' plugin.
 *
 * @since 5.5.2
 * @author Sudar
 * @package BulkDelete\Util\Compatibility
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Deregister select2 style registered by Advanced Custom Fields Pro plugin.
 * Advanced Custom Fields Pro is including their version of select2 in all admin pages.
 * @see https://github.com/sudar/bulk-delete/issues/114
 *
 * @since 5.5.2
 */
function bd_deregister_acf_select2() {
	wp_deregister_style( 'select2' );
}
add_action( 'bd_before_admin_enqueue_scripts', 'bd_deregister_acf_select2' );
