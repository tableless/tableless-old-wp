<?php
/**
 * Post Addons related functions.
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
function bd_register_post_addons() {
	$bd = BULK_DELETE();

	add_meta_box( Bulk_Delete::BOX_CUSTOM_FIELD    , __( 'By Custom Field'      , 'bulk-delete' ) , 'bd_render_delete_posts_by_custom_field_box'    , $bd->posts_page , 'advanced' );
	add_meta_box( Bulk_Delete::BOX_TITLE           , __( 'By Title'             , 'bulk-delete' ) , 'bd_render_delete_posts_by_title_box'           , $bd->posts_page , 'advanced' );
	add_meta_box( Bulk_Delete::BOX_DUPLICATE_TITLE , __( 'By Duplicate Title'   , 'bulk-delete' ) , 'bd_render_delete_posts_by_duplicate_title_box' , $bd->posts_page , 'advanced' );
	add_meta_box( Bulk_Delete::BOX_POST_BY_ROLE    , __( 'By User Role'         , 'bulk-delete' ) , 'bd_render_delete_posts_by_user_role_box'       , $bd->posts_page , 'advanced' );
	add_meta_box( Bulk_Delete::BOX_POST_FROM_TRASH , __( 'Posts in Trash'       , 'bulk-delete' ) , 'bd_render_delete_posts_from_trash'             , $bd->posts_page , 'advanced' );
}
add_action( 'bd_add_meta_box_for_posts', 'bd_register_post_addons' );

/**
 * Render delete posts by custom field box
 *
 * @since 5.5
 */
function bd_render_delete_posts_by_custom_field_box() {

	if ( BD_Util::is_posts_box_hidden( Bulk_Delete::BOX_CUSTOM_FIELD ) ) {
		printf( __( 'This section just got enabled. Kindly <a href = "%1$s">refresh</a> the page to fully enable it.', 'bulk-delete' ), 'admin.php?page=' . Bulk_Delete::POSTS_PAGE_SLUG );
		return;
	}

	if ( ! class_exists( 'Bulk_Delete_Posts_By_Custom_Field' ) ) {
?>
		<!-- Custom Field box start-->
		<p>
			<span class = "bd-post-custom-field-pro" style = "color:red">
				<?php _e( 'You need "Bulk Delete Posts by Custom Field" Addon, to delete post by custom field.', 'bulk-delete' ); ?>
				<a href = "http://bulkwp.com/addons/bulk-delete-posts-by-custom-field/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-cf">Buy now</a>
			</span>
		</p>
		<!-- Custom Field box end-->
<?php
	} else {
		Bulk_Delete_Posts_By_Custom_Field::render_delete_posts_by_custom_field_box();
	}
}

/**
 * Render posts by title box
 *
 * @since 5.5
 */
function bd_render_delete_posts_by_title_box() {

	if ( BD_Util::is_posts_box_hidden( Bulk_Delete::BOX_TITLE ) ) {
		printf( __( 'This section just got enabled. Kindly <a href = "%1$s">refresh</a> the page to fully enable it.', 'bulk-delete' ), 'admin.php?page=' . Bulk_Delete::POSTS_PAGE_SLUG );
		return;
	}

	if ( ! class_exists( 'Bulk_Delete_Posts_By_Title' ) ) {
?>
		<!-- Title box start-->
		<p>
			<span class = "bd-post-title-pro" style = "color:red">
				<?php _e( 'You need "Bulk Delete Posts by Title" Addon, to delete post by title.', 'bulk-delete' ); ?>
				<a href = "http://bulkwp.com/addons/bulk-delete-posts-by-title/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-ti">Buy now</a>
			</span>
		</p>
		<!-- Title box end-->
<?php
	} else {
		Bulk_Delete_Posts_By_Title::render_delete_posts_by_title_box();
	}
}

/**
 * Render delete posts by duplicate title box
 *
 * @since 5.5
 */
function bd_render_delete_posts_by_duplicate_title_box() {

	if ( BD_Util::is_posts_box_hidden( Bulk_Delete::BOX_DUPLICATE_TITLE ) ) {
		printf( __( 'This section just got enabled. Kindly <a href = "%1$s">refresh</a> the page to fully enable it.', 'bulk-delete' ), 'admin.php?page=' . Bulk_Delete::POSTS_PAGE_SLUG );
		return;
	}

	if ( ! class_exists( 'Bulk_Delete_Posts_By_Duplicate_Title' ) ) {
?>
		<!-- Duplicate Title box start-->
		<p>
			<span class = "bd-post-title-pro" style = "color:red">
				<?php _e( 'You need "Bulk Delete Posts by Duplicate Title" Addon, to delete post by duplicate title.', 'bulk-delete' ); ?>
				<a href = "http://bulkwp.com/addons/bulk-delete-posts-by-duplicate-title/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-dti">Buy now</a>
			</span>
		</p>
		<!-- Duplicate Title box end-->
<?php
	} else {
		Bulk_Delete_Posts_By_Duplicate_Title::render_delete_posts_by_duplicate_title_box();
	}
}

/**
 * Delete posts by user role
 *
 * @since 5.5
 */
function bd_render_delete_posts_by_user_role_box() {

	if ( BD_Util::is_posts_box_hidden( Bulk_Delete::BOX_POST_BY_ROLE ) ) {
		printf( __( 'This section just got enabled. Kindly <a href = "%1$s">refresh</a> the page to fully enable it.', 'bulk-delete' ), 'admin.php?page=' . Bulk_Delete::POSTS_PAGE_SLUG );
		return;
	}
	if ( ! class_exists( 'Bulk_Delete_Posts_By_User_Role' ) ) {
?>
		<!-- Posts by user role start-->
		<p>
			<span class = "bd-post-by-role-pro" style = "color:red">
				<?php _e( 'You need "Bulk Delete Posts by User Role" Addon, to delete post based on User Role', 'bulk-delete' ); ?>
				<a href = "http://bulkwp.com/addons/bulk-delete-posts-by-user-role/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-ur">Buy now</a>
			</span>
		</p>
		<!-- Posts by user role end-->
<?php
	} else {
		Bulk_Delete_Posts_By_User_Role::render_delete_posts_by_user_role_box();
	}
}

/**
 * Render delete posts from trash box
 *
 * @since 5.5
 */
function bd_render_delete_posts_from_trash() {
	if ( BD_Util::is_posts_box_hidden( Bulk_Delete::BOX_POST_FROM_TRASH ) ) {
		printf( __( 'This section just got enabled. Kindly <a href = "%1$s">refresh</a> the page to fully enable it.', 'bulk-delete' ), 'admin.php?page=' . Bulk_Delete::POSTS_PAGE_SLUG );
		return;
	}

	if ( ! class_exists( 'Bulk_Delete_From_Trash' ) ) {
?>
		<!-- Posts In Trash box start-->
		<p>
			<span class = "bd-post-trash-pro" style = "color:red">
				<?php _e( 'You need "Bulk Delete From Trash" Addon, to delete post in Trash.', 'bulk-delete' ); ?>
				<a href = "http://bulkwp.com/addons/bulk-delete-from-trash/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-th">Buy now</a>
			</span>
		</p>
		<!-- Posts In Trash box end-->
<?php
	} else {

		/**
		 * Render delete posts from trash box
		 *
		 * @since 5.4
		 */
		do_action( 'bd_render_delete_posts_from_trash' );
	}
}
?>
