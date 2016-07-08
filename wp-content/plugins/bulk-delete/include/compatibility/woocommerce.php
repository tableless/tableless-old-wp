<?php
/**
 * Code to enable compatibility with 'WooCommerce' plugin.
 *
 * @since 5.5.1
 * @author Sudar
 * @package BulkDelete\Util\Compatibility
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Deregister select2 script registered by WooCommerce plugin.
 * WooCommerce is using an older version of select2 which is conflicting with the version used by Bulk WP.
 * @see https://github.com/sudar/bulk-delete/issues/111
 * @see https://github.com/woothemes/woocommerce/issues/8766
 *
 * @since 5.5.1
 */
function bd_deregister_woo_select2() {
	wp_deregister_script( 'select2' );
}
add_action( 'bd_before_admin_enqueue_scripts', 'bd_deregister_woo_select2' );
