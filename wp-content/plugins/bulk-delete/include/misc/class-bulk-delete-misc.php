<?php
/**
 * Utility class for deleting Misc stuff
 *
 * @since      5.3
 * @author     Sudar
 * @package    BulkDelete\Misc
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Bulk_Delete_Misc {

	/**
	 * Slug for *misc* page
	 *
	 * @since 5.3
	 */
	const MISC_PAGE_SLUG = 'bulk-delete-misc';

	const VISIBLE_MISC_BOXES   = 'metaboxhidden_bulk-delete_page_bulk-delete-misc';

	/**
	 * Add *misc* menu
	 *
	 * @static
	 * @since 5.3
	 */
	public static function add_menu() {
		$bd = BULK_DELETE();

		$bd->misc_page = add_submenu_page(
			Bulk_Delete::POSTS_PAGE_SLUG,
			__( 'Bulk Delete Miscellaneous Items', 'bulk-delete' ),
			__( 'Bulk Delete Misc', 'bulk-delete' ),
			'delete_posts',
			self::MISC_PAGE_SLUG,
			array( __CLASS__, 'display_misc_page' )
		);

		// enqueue JavaScript
		add_action( 'admin_print_scripts-' . $bd->misc_page, array( $bd, 'add_script' ) );

		// delete menus page
		add_action( "load-{$bd->misc_page}", array( __CLASS__, 'add_delete_misc_settings_panel' ) );
		add_action( "add_meta_boxes_{$bd->misc_page}", array( __CLASS__, 'add_delete_misc_meta_boxes' ) );
	}

	/**
	 * Add settings Panel for delete misc page
	 *
	 * @static
	 * @since  5.3
	 */
	public static function add_delete_misc_settings_panel() {
		$bd = BULK_DELETE();

		/**
		 * Add contextual help for admin screens
		 *
		 * @since 5.3
		 */
		do_action( 'bd_add_contextual_help', $bd->misc_page );

		/* Trigger the add_meta_boxes hooks to allow meta boxes to be added */
		do_action( 'add_meta_boxes_' . $bd->misc_page, null );

		/* Enqueue WordPress' script for handling the meta boxes */
		wp_enqueue_script( 'postbox' );
	}

	/**
	 * Register meta boxes for delete misc page
	 *
	 * @static
	 * @since 5.3
	 */
	public static function add_delete_misc_meta_boxes() {

		/**
		 * Add meta box in misc page
		 * This hook can be used for adding additional meta boxes in *misc* page
		 *
		 * @since 5.3
		 */
		do_action( 'bd_add_meta_box_for_misc' );
	}

	/**
	 * Show the delete misc page
	 *
	 * @static
	 * @since 5.3
	 */
	public static function display_misc_page() {
?>
<div class="wrap">
    <h2><?php _e( 'Bulk Delete Miscellaneous Items', 'bulk-delete' );?></h2>
    <?php settings_errors(); ?>

    <form method = "post">
<?php
		// nonce for bulk delete
		wp_nonce_field( 'sm-bulk-delete-misc', 'sm-bulk-delete-misc-nonce' );

		/* Used to save closed meta boxes and their order */
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
?>
    <div id = "poststuff">
        <div id="post-body" class="metabox-holder columns-2">

            <div class="notice notice-warning">
                <p><strong><?php _e( 'WARNING: Posts deleted once cannot be retrieved back. Use with caution.', 'bulk-delete' ); ?></strong></p>
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
		 * Runs just before displaying the footer text in the "Bulk Delete Misc" admin page.
		 *
		 * This action is primarily for adding extra content in the footer of "Bulk Delete Misc" admin page.
		 *
		 * @since 5.3
		 */
		do_action( 'bd_admin_footer_misc_page' );
	}

	/**
	 * Check whether the meta box in misc page is hidden or not
	 *
	 * @static
	 * @access private
	 * @since  5.3
	 * @param string  $box The name of the box to check
	 * @return bool        True if the box is hidden, False otherwise
	 */
	public static function is_misc_box_hidden( $box ) {
		$hidden_boxes = self::get_misc_hidden_boxes();
		return is_array( $hidden_boxes ) && in_array( $box, $hidden_boxes );
	}

	/**
	 * Get the list of hidden boxes in misc page
	 *
	 * @static
	 * @access private
	 * @since  5.3
	 * @return array The array of hidden meta boxes
	 */
	private static function get_misc_hidden_boxes() {
		$current_user = wp_get_current_user();
		return get_user_meta( $current_user->ID, self::VISIBLE_MISC_BOXES, true );
	}
}

// Add menu
add_action( 'bd_after_primary_menus', array( 'Bulk_Delete_Misc', 'add_menu' ) );

// Modify admin footer
add_action( 'bd_admin_footer_misc_page', 'bd_modify_admin_footer' );
?>
