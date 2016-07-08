<?php
/**
 * Addons related util functions.
 *
 * @since      5.5
 * @author     Sudar
 * @package    BulkDelete\Addon
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Compute class name from addon name.
 *
 * @since 5.5
 *
 * @param string $addon_name Name of the addon.
 * @return string Computed class name for the addon.
 */
function bd_get_addon_class_name( $addon_name ) {
	$addon_class_name = str_replace( ' ', '_', $addon_name );

	if ( false !== strpos( $addon_class_name, 'Scheduler' ) ) {
		$addon_class_name = str_replace( 'Bulk_Delete', 'BD', $addon_class_name );
	}

	$addon_class_name .= '_Addon';

	/**
	 * Filter to modify addon class name.
	 *
	 * @since 5.5
	 *
	 * @param string $addon_class_name Addon class name
	 * @param string $addon_name Addon name
	 */
	return apply_filters( 'bd_addon_class_name', $addon_class_name, $addon_name );
}

/**
 * Compute addon url from addon name.
 *
 * @since 5.5
 *
 * @param  string $addon_name    Name of the addon.
 * @param  array  $campaign_args Campaign_args. Default empty array
 * @return string                Computed url for the addon.
 */
function bd_get_addon_url( $addon_name, $campaign_args = array() ) {
	$base = 'http://bulkwp.com/addons/';
	$addon_slug = str_replace( ' ', '-', strtolower( $addon_name ) );

	if ( false !== strpos( $addon_name, 'scheduler' ) ) {
		$addon_slug = str_replace( 'bulk-delete-', '', $addon_name );
	}

	$addon_url = $base . $addon_slug;
	$addon_url = add_query_arg( $campaign_args, $addon_url );

	/**
	 * Filter to modify addon url.
	 *
	 * @since 5.5
	 *
	 * @param string $addon_name Addon name
	 * @param string $addon_url Addon url
	 * @param array  $campaign_args Campaign_args. Default empty array
	 */
	return apply_filters( 'bd_addon_url', $addon_url, $addon_name, $campaign_args );
}
?>
