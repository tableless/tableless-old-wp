<?php
/**
 * Deprecated and backward compatibility code.
 * Don't depend on the code in this file. It would be removed in future versions of the plugin.
 *
 * @author     Sudar
 * @package    BulkDelete\Util\Deprecated
 * @since 5.5
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Backward compatibility for delete options.
 *
 * @since 5.5
 * @param array $options Old options.
 * @return array New options.
 */
function bd_delete_options_compatibility( $options ) {
	// Convert bool keys to boolean
	$bool_keys = array( 'restrict', 'force_delete', 'private' );
	foreach ( $bool_keys as $key ) {
		if ( array_key_exists( $key, $options ) ) {
			$options[ $key ] = bd_to_bool( $options[ $key ] );
		}
	}

	// convert old date comparison operators
	if ( array_key_exists( 'date_op', $options ) && array_key_exists( 'days', $options ) ) {
		if ( '<' == $options['date_op'] ) {
			$options['date_op'] = 'before';
		} else if ( '>' == $options['date_op'] ) {
			$options['date_op'] = 'after';
		}
	}

	return $options;
}
add_filter( 'bd_delete_options', 'bd_delete_options_compatibility' );

/**
 * Enable cron for old pro addons that required separate JavaScript.
 * This will be removed in v6.0
 *
 * @since 5.5
 * @param array  $js_array JavaScript Array
 * @return array           Modified JavaScript Array
 */
function bd_enable_cron_for_old_addons( $js_array ) {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	if ( is_plugin_active( 'bulk-delete-scheduler-for-deleting-users-by-role/bulk-delete-scheduler-for-deleting-users-by-role.php' ) ) {
		$js_array['pro_iterators'][] = 'u_role';
	}

	return $js_array;
}
add_filter( 'bd_javascript_array', 'bd_enable_cron_for_old_addons' );
?>
