<?php
/**
 * Addon related util functions.
 *
 * @since      5.5
 * @author     Sudar
 * @package    BulkDelete\Util\Addon
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Display information about all available addons
 *
 * @since 5.5
 */
function bd_display_available_addon_list() {
	echo '<p>';
	_e( 'The following are the list of pro addons that are currently available for purchase.', 'bulk-delete' );
	echo '</p>';

	echo '<ul style="list-style:disc; padding-left:35px">';

	echo '<li>';
	echo '<strong>', __( 'Delete posts by custom field', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to delete posts based on custom fields', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/bulk-delete-posts-by-custom-field/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-cf">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Delete posts by title', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to delete posts based on title', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/bulk-delete-posts-by-title/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-ti">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Delete posts by duplicate title', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to delete posts based on duplicate title', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/bulk-delete-posts-by-duplicate-title/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-dti">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Delete posts by attachment', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to delete posts based on whether it contains attachment or not', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/bulk-delete-posts-by-attachment/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-p-at">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Delete posts by user role', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to delete posts based on user role', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/bulk-delete-posts-by-user-role/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-ur">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Delete from trash', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to delete posts and pages from trash', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/bulk-delete-from-trash/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-th">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Scheduler Email', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Sends an email every time a Bulk WP scheduler runs', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/scheduler-email/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-se">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Scheduler for deleting Posts by Category', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to schedule auto delete of posts based on category', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/scheduler-for-deleting-posts-by-category/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-sc">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Scheduler for deleting Posts by Tag', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to schedule auto delete of posts based on tag', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/scheduler-for-deleting-posts-by-tag/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-st">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Scheduler for deleting Posts by Custom Taxonomy', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to schedule auto delete of posts based on custom taxonomy', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/scheduler-for-deleting-posts-by-taxonomy/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-stx">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Scheduler for deleting Posts by Custom Post Type', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to schedule auto delete of posts based on custom post type', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/scheduler-for-deleting-posts-by-post-type/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-spt">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Scheduler for deleting Posts by Post Status', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to schedule auto delete of posts based on post status like drafts, pending posts, scheduled posts etc.', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/scheduler-for-deleting-posts-by-status/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-sps">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Scheduler for deleting Pages by Status', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to schedule auto delete pages based on status', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/scheduler-for-deleting-pages-by-status/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-sp">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Scheduler for deleting Users by User Role', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to schedule auto delete of users based on user role', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/scheduler-for-deleting-users-by-role/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-u-ur">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Scheduler for deleting Users by User Meta', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to schedule auto delete of users based on user meta', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/scheduler-for-deleting-users-by-meta/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bds-u-ma">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Delete Post Meta Fields', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to delete post meta fields based on value and to schedule automatic deletion', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/bulk-delete-post-meta/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-m-p">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Delete Comment Meta Fields', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to delete comment meta fields based on value and to schedule automatic deletion', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/bulk-delete-comment-meta/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-m-c">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Delete User Meta Fields', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to delete user meta fields based on value and to schedule automatic deletion', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/bulk-delete-user-meta/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-m-u">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Delete attachment', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to delete attachments based on whether they are attached to a post or not', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/bulk-delete-attachments/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-at">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '<li>';
	echo '<strong>', __( 'Delete Jetpack Contact Form Messages', 'bulk-delete' ), '</strong>', ' - ';
	echo __( 'Adds the ability to delete Jetpack Contact Form Messages based on filters and to schedule automatic deletion', 'bulk-delete' );
	echo ' <a href = "http://bulkwp.com/addons/bulk-delete-jetpack-contact-form-messages/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=addonlist&utm_content=bd-jcm">', __( 'More Info', 'bulk-delete' ), '</a>.';
	echo '</li>';

	echo '</ul>';
}
?>
