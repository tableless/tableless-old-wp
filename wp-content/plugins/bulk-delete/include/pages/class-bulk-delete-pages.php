<?php
/**
 * Utility class for deleting pages
 *
 * @since   5.0
 * @author  Sudar
 * @package BulkDelete
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Bulk_Delete_Pages {

	/**
	 * Render delete pages by page status box
	 *
	 * @access public
	 * @static
	 * @since  5.0
	 */
	public static function render_delete_pages_by_status_box() {
		if ( BD_Util::is_pages_box_hidden( Bulk_Delete::BOX_PAGE_STATUS ) ) {
			printf( __( 'This section just got enabled. Kindly <a href = "%1$s">refresh</a> the page to fully enable it.', 'bulk-delete' ), 'admin.php?page=' . Bulk_Delete::PAGES_PAGE_SLUG );
			return;
		}

		$pages_count  = wp_count_posts( 'page' );
		$pages        = $pages_count->publish;
		$page_drafts  = $pages_count->draft;
		$page_future  = $pages_count->future;
		$page_pending = $pages_count->pending;
		$page_private = $pages_count->private;
?>
        <!-- Pages start-->
        <h4><?php _e( 'Select the status from which you want to delete pages', 'bulk-delete' ); ?></h4>

        <fieldset class="options">
        <table class="optiontable">
            <tr>
                <td>
                    <input name="smbd_published_pages" value="published_pages" type="checkbox">
                    <label for="smbd_published_pages"><?php _e( 'All Published Pages', 'bulk-delete' ); ?> (<?php echo $pages . ' '; _e( 'Pages', 'bulk-delete' ); ?>)</label>
                </td>
            </tr>

            <tr>
                <td>
                    <input name="smbd_draft_pages" value="draft_pages" type="checkbox">
                    <label for="smbd_draft_pages"><?php _e( 'All Draft Pages', 'bulk-delete' ); ?> (<?php echo $page_drafts . ' '; _e( 'Pages', 'bulk-delete' ); ?>)</label>
                </td>
            </tr>

            <tr>
                <td>
                    <input name="smbd_future_pages" value="scheduled_pages" type="checkbox">
                    <label for="smbd_future_pages"><?php _e( 'All Scheduled Pages', 'bulk-delete' ); ?> (<?php echo $page_future . ' '; _e( 'Pages', 'bulk-delete' ); ?>)</label>
                </td>
            </tr>

            <tr>
                <td>
                    <input name="smbd_pending_pages" value="pending_pages" type="checkbox">
                    <label for="smbd_pending_pages"><?php _e( 'All Pending Pages', 'bulk-delete' ); ?> (<?php echo $page_pending . ' '; _e( 'Pages', 'bulk-delete' ); ?>)</label>
                </td>
            </tr>

            <tr>
                <td>
                    <input name="smbd_private_pages" value="private_pages" type="checkbox">
                    <label for="smbd_private_pages"><?php _e( 'All Private Pages', 'bulk-delete' ); ?> (<?php echo $page_private . ' '; _e( 'Pages', 'bulk-delete' ); ?>)</label>
                </td>
            </tr>
		</table>

        <table class="optiontable">
<?php
			bd_render_filtering_table_header();
			bd_render_restrict_settings( 'pages', 'pages' );
			bd_render_delete_settings( 'pages' );
			bd_render_limit_settings( 'pages' );
			bd_render_cron_settings( 'pages','http://bulkwp.com/addons/scheduler-for-deleting-pages-by-status/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-sp' );
?>
        </table>
        </fieldset>
<?php
			bd_render_submit_button( 'delete_pages_by_status' );
	}

	/**
	 * Request handler for deleting pages by status
	 *
	 * @since 5.0
	 */
	public static function do_delete_pages_by_status() {
		$delete_options                 = array();
		$delete_options['restrict']     = array_get_bool( $_POST, 'smbd_pages_restrict', false );
		$delete_options['limit_to']     = absint( array_get( $_POST, 'smbd_pages_limit_to', 0 ) );
		$delete_options['force_delete'] = array_get_bool( $_POST, 'smbd_pages_force_delete', false );

		$delete_options['date_op']      = array_get( $_POST, 'smbd_pages_op' );
		$delete_options['days']         = absint( array_get( $_POST, 'smbd_pages_days' ) );

		$delete_options['publish']      = array_get( $_POST, 'smbd_published_pages' );
		$delete_options['drafts']       = array_get( $_POST, 'smbd_draft_pages' );
		$delete_options['pending']      = array_get( $_POST, 'smbd_pending_pages' );
		$delete_options['future']       = array_get( $_POST, 'smbd_future_pages' );
		$delete_options['private']      = array_get( $_POST, 'smbd_private_pages' );

		if ( 'true' == array_get( $_POST, 'smbd_pages_cron', 'false' ) ) {
			$freq = $_POST['smbd_pages_cron_freq'];
			$time = strtotime( $_POST['smbd_pages_cron_start'] ) - ( get_option( 'gmt_offset' ) * 60 * 60 );

			if ( $freq == -1 ) {
				wp_schedule_single_event( $time, Bulk_Delete::CRON_HOOK_PAGES_STATUS, array( $delete_options ) );
			} else {
				wp_schedule_event( $time, $freq , Bulk_Delete::CRON_HOOK_PAGES_STATUS, array( $delete_options ) );
			}
			$msg = __( 'The selected pages are scheduled for deletion.', 'bulk-delete' ) . ' ' .
				sprintf( __( 'See the full list of <a href = "%s">scheduled tasks</a>' , 'bulk-delete' ), get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=' . Bulk_Delete::CRON_PAGE_SLUG );
		} else {
			$deleted_count = self::delete_pages_by_status( $delete_options );
			$msg = sprintf( _n( 'Deleted %d page', 'Deleted %d pages' , $deleted_count, 'bulk-delete' ), $deleted_count );
		}

		add_settings_error(
			Bulk_Delete::PAGES_PAGE_SLUG,
			'deleted-cron',
			$msg,
			'updated'
		);
	}

	/**
	 * Bulk Delete pages
	 *
	 * @since 5.0
	 * @param array $delete_options
	 * @return integer
	 */
	public static function delete_pages_by_status( $delete_options ) {
		global $wp_query;

		// Backward compatibility code. Will be removed in Bulk Delete v6.0
		if ( array_key_exists( 'page_op', $delete_options ) ) {
			$delete_options['date_op'] = $delete_options['page_op'];
			$delete_options['days']    = $delete_options['page_days'];
		}
		$delete_options = apply_filters( 'bd_delete_options', $delete_options );

		$post_status = array();

		// published pages
		if ( 'published_pages' == $delete_options['publish'] ) {
			$post_status[] = 'publish';
		}

		// Drafts
		if ( 'draft_pages' == $delete_options['drafts'] ) {
			$post_status[] = 'draft';
		}

		// Pending pages
		if ( 'pending_pages' == $delete_options['pending'] ) {
			$post_status[] = 'pending';
		}

		// Future pages
		if ( 'future_pages' == $delete_options['future'] ) {
			$post_status[] = 'future';
		}

		// Private pages
		if ( 'private_pages' == $delete_options['private'] ) {
			$post_status[] = 'private';
		}

		$options = array(
			'post_type'   => 'page',
			'post_status' => $post_status,
		);

		$options = bd_build_query_options( $delete_options, $options );
		$pages = $wp_query->query( $options );
		foreach ( $pages as $page ) {
			wp_delete_post( $page->ID, $delete_options['force_delete'] );
		}

		return count( $pages );
	}

	/**
	 * Filter JS Array and add validation hooks
	 *
	 * @since 5.4
	 * @static
	 * @param array   $js_array JavaScript Array
	 * @return array           Modified JavaScript Array
	 */
	public static function filter_js_array( $js_array ) {
		$js_array['dt_iterators'][] = '_pages';
		return $js_array;
	}
}

add_action( 'bd_delete_pages_by_status', array( 'Bulk_Delete_Pages', 'do_delete_pages_by_status' ) );
add_filter( 'bd_javascript_array', array( 'Bulk_Delete_Pages', 'filter_js_array' ) );
?>
