<?php
/**
 * Page Addons related functions.
 *
 * @since      5.5
 * @author     Sudar
 * @package    BulkDelete\Addon
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Register post related addons.
 *
 * @since 5.5
 */
function bd_register_page_addons() {
	$bd = BULK_DELETE();

	add_meta_box( Bulk_Delete::BOX_PAGE_FROM_TRASH , __( 'Pages in Trash' , 'bulk-delete' ) , 'bd_render_delete_pages_from_trash', $bd->pages_page , 'advanced' );
}
add_action( 'bd_add_meta_box_for_pages', 'bd_register_page_addons' );

/**
 * Render delete pages from trash box.
 *
 * @since 5.5
 */
function bd_render_delete_pages_from_trash() {
	if ( BD_Util::is_pages_box_hidden( Bulk_Delete::BOX_PAGE_FROM_TRASH ) ) {
		printf( __( 'This section just got enabled. Kindly <a href = "%1$s">refresh</a> the page to fully enable it.', 'bulk-delete' ), 'admin.php?page=' . Bulk_Delete::PAGES_PAGE_SLUG );
		return;
	}

	if ( ! class_exists( 'Bulk_Delete_From_Trash' ) ) {
?>
		<!-- pages In Trash box start-->
		<p>
			<span class = "bd-pages-trash-pro" style = "color:red">
				<?php _e( 'You need "Bulk Delete From Trash" Addon, to delete pages in Trash.', 'bulk-delete' ); ?>
				<a href = "http://bulkwp.com/addons/bulk-delete-from-trash/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-th">Buy now</a>
			</span>
		</p>
		<!-- pages In Trash box end-->
<?php
	} else {

		/**
		 * Render delete pages from trash box
		 *
		 * @since 5.4
		 */
		do_action( 'bd_render_delete_pages_from_trash' );
	}
}
?>
