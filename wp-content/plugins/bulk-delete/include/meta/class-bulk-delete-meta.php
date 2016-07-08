<?php
/**
 * Utility class for deleting Meta Fields.
 *
 * @since      5.4
 * @author     Sudar
 * @package    BulkDelete\Meta
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Bulk_Delete_Meta {

	/**
	 * Slug for *meta* page.
	 *
	 * @since 5.4
	 */
	const META_PAGE_SLUG = 'bulk-delete-meta';

	/**
	 * User meta that stores box preferences.
	 *
	 * @since 5.4
	 */
	const VISIBLE_META_BOXES = 'metaboxhidden_bulk-delete_page_bulk-delete-meta';

	/**
	 * Add *meta* menu.
	 *
	 * @static
	 * @since 5.4
	 */
	public static function add_menu() {
		$bd = BULK_DELETE();

		$bd->meta_page = add_submenu_page(
			Bulk_Delete::POSTS_PAGE_SLUG,
			__( 'Bulk Delete Meta Fields', 'bulk-delete' ),
			__( 'Bulk Delete Meta Fields', 'bulk-delete' ),
			'delete_posts',
			self::META_PAGE_SLUG,
			array( __CLASS__, 'display_meta_page' )
		);

		// enqueue JavaScript
		add_action( 'admin_print_scripts-' . $bd->meta_page, array( $bd, 'add_script' ) );

		// delete menus page
		add_action( "load-{$bd->meta_page}", array( __CLASS__, 'add_delete_meta_settings_panel' ) );
		add_action( "add_meta_boxes_{$bd->meta_page}", array( __CLASS__, 'add_delete_meta_meta_boxes' ) );
	}

	/**
	 * Add settings Panel for delete meta page.
	 *
	 * @static
	 * @since  5.4
	 */
	public static function add_delete_meta_settings_panel() {
		$bd = BULK_DELETE();

		/**
		 * Add contextual help for admin screens.
		 *
		 * @since 5.4
		 */
		do_action( 'bd_add_contextual_help', $bd->meta_page );

		/* Trigger the add_meta_boxes hooks to allow meta boxes to be added */
		do_action( 'add_meta_boxes_' . $bd->meta_page, null );

		/* Enqueue WordPress' script for handling the meta boxes */
		wp_enqueue_script( 'postbox' );
	}

	/**
	 * Register meta boxes for delete meta page.
	 *
	 * @static
	 * @since 5.4
	 */
	public static function add_delete_meta_meta_boxes() {

		/**
		 * Add meta box in meta page.
		 * This hook can be used for adding additional meta boxes in *meta* page
		 *
		 * @since 5.4
		 */
		do_action( 'bd_add_meta_box_for_meta' );
	}

	/**
	 * Show the delete meta page.
	 *
	 * @static
	 * @since 5.4
	 */
	public static function display_meta_page() {
?>
<div class="wrap">
    <h2><?php _e( 'Bulk Delete Meta Fields', 'bulk-delete' );?></h2>
    <?php settings_errors(); ?>

    <form method = "post">
<?php
		wp_nonce_field( 'sm-bulk-delete-meta', 'sm-bulk-delete-meta-nonce' );

		/* Used to save closed meta boxes and their order */
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
?>
    <div id = "poststuff">
        <div id="post-body" class="metabox-holder columns-2">

            <div class="notice notice-warning">
                <p><strong><?php _e( 'WARNING: Items deleted once cannot be retrieved back. Use with caution.', 'bulk-delete' ); ?></strong></p>
            </div>

			<?php bd_render_sidebar_iframe(); ?>

            <div id="postbox-container-2" class="postbox-container">
                <?php do_meta_boxes( '', 'advanced', null ); ?>
            </div> <!-- #postbox-container-2 -->

        </div> <!-- #post-body -->
    </div><!-- #poststuff -->
    </form>
</div><!-- .wrap -->
<?php
		/**
		 * Runs just before displaying the footer text in the "Bulk Delete Meta" admin page.
		 * This action is primarily for adding extra content in the footer of "Bulk Delete Meta" admin page.
		 *
		 * @since 5.4
		 */
		do_action( 'bd_admin_footer_meta_page' );
	}

	/**
	 * Check whether the meta box in meta page is hidden or not.
	 *
	 * @static
	 * @access private
	 * @since  5.4
	 * @param string  $box The name of the box to check
	 * @return bool        True if the box is hidden, False otherwise
	 */
	public static function is_meta_box_hidden( $box ) {
		$hidden_boxes = self::get_meta_hidden_boxes();
		return is_array( $hidden_boxes ) && in_array( $box, $hidden_boxes );
	}

	/**
	 * Get the list of hidden boxes in meta page.
	 *
	 * @static
	 * @access private
	 * @since  5.4
	 * @return array The array of hidden meta boxes
	 */
	private static function get_meta_hidden_boxes() {
		$current_user = wp_get_current_user();
		return get_user_meta( $current_user->ID, self::VISIBLE_META_BOXES, true );
	}
}

// Add menu
add_action( 'bd_after_primary_menus', array( 'Bulk_Delete_Meta', 'add_menu' ) );

// Modify admin footer
add_action( 'bd_admin_footer_meta_page', 'bd_modify_admin_footer' );
?>
