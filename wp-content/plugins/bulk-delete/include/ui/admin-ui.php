<?php
/**
 * Customize admin UI for Bulk Delete plugin
 *
 * @since      5.0
 * @author     Sudar
 * @package    BulkDelete\Admin
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Add rating links to the admin dashboard
 *
 * @since     5.0
 * @param string  $footer_text The existing footer text
 * @return      string
 */
function bd_add_rating_link( $footer_text ) {
	$rating_text = sprintf( __( 'Thank you for using <a href = "%1$s">Bulk Delete</a> plugin! Kindly <a href = "%2$s">rate us</a> at <a href = "%2$s">WordPress.org</a>', 'bulk-delete' ),
		'http://bulkwp.com?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=footer',
		'http://wordpress.org/support/view/plugin-reviews/bulk-delete?filter=5#postform'
	);

	$rating_text = apply_filters( 'bd_rating_link', $rating_text );
	return str_replace( '</span>', '', $footer_text ) . ' | ' . $rating_text . '</span>';
}

/**
 * Modify admin footer in Bulk Delete plugin pages
 *
 * @since     5.0
 */
function bd_modify_admin_footer() {
	add_filter( 'admin_footer_text', 'bd_add_rating_link' );
}

/**
 * Adds setting links in plugin listing page.
 * Based on http://striderweb.com/nerdaphernalia/2008/06/wp-use-action-links/
 *
 * @param array   $links List of current links
 * @param string  $file  Plugin filename
 * @return array  $links Modified list of links
 */
function bd_add_plugin_action_links( $links, $file ) {
	$this_plugin = plugin_basename( Bulk_Delete::$PLUGIN_FILE );

	if ( $file == $this_plugin ) {
		$delete_posts_link = '<a href="admin.php?page=' . Bulk_Delete::POSTS_PAGE_SLUG . '">' . __( 'Bulk Delete Posts', 'bulk-delete' ) . '</a>';
		array_unshift( $links, $delete_posts_link ); // before other links
	}

	return $links;
}

/**
 * Add additional links in the Plugin listing page.
 * Based on http://zourbuth.com/archives/751/creating-additional-wordpress-plugin-links-row-meta/
 *
 * @param array   $links List of current links
 * @param string  $file  Plugin filename
 * @return array  $links Modified list of links
 */
function bd_add_links_in_plugin_listing( $links, $file ) {
	$plugin = plugin_basename( Bulk_Delete::$PLUGIN_FILE );

	if ( $file == $plugin ) { // only for this plugin
		return array_merge( $links, array(
				'<a href="http://bulkwp.com/addons?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=plugin-page" target="_blank">' . __( 'Buy Addons', 'bulk-delete' ) . '</a>'
			) );
	}

	return $links;
}

// Modify admin footer
add_action( 'bd_admin_footer_posts_page', 'bd_modify_admin_footer' );
add_action( 'bd_admin_footer_pages_page', 'bd_modify_admin_footer' );
add_action( 'bd_admin_footer_cron_page' , 'bd_modify_admin_footer' );
add_action( 'bd_admin_footer_addon_page', 'bd_modify_admin_footer' );
add_action( 'bd_admin_footer_info_page' , 'bd_modify_admin_footer' );

// Change plugin listing page
add_filter( 'plugin_action_links', 'bd_add_plugin_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'bd_add_links_in_plugin_listing', 10, 2 );
?>
