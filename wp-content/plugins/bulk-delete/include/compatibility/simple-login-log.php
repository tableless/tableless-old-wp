<?php
/**
 * Code to enable compatibility with other plugins.
 *
 * @since 5.5
 * @author Sudar
 * @package BulkDelete\Util\Compatibility
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Find out if Simple Login Log is installed or not.
 * http://wordpress.org/plugins/simple-login-log/
 *
 * @since 5.5
 * @return bool    True if plugin is installed, False otherwise
 */
function bd_is_simple_login_log_present() {
	global $wpdb;
	$simple_login_log_table = 'simple_login_log';

	return (bool) $wpdb->get_row( "SHOW TABLES LIKE '{$wpdb->prefix}{$simple_login_log_table}'" );
}

/**
 * Find the last login date/time of a user.
 *
 * @since 5.5
 * @param int $user_id
 * @return string
 */
function bd_get_last_login( $user_id ) {
	global $wpdb;

	$simple_login_log_table = 'simple_login_log';

	return $wpdb->get_var( $wpdb->prepare( "SELECT time FROM {$wpdb->prefix}{$simple_login_log_table} WHERE uid = %d ORDER BY time DESC LIMIT 1", $user_id ) );
}
?>
