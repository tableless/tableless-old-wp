<?php
/**
 * Utility class for deleting Comment Meta.
 *
 * @since      5.4
 * @author     Sudar
 * @package    BulkDelete\Meta
 */


class Bulk_Delete_Comment_Meta {

	/**
	 * Box slug.
	 *
	 * @since 5.4
	 */
	const BOX_COMMENT_META = 'bd-comment-meta';

	/**
	 * Cron Hook.
	 *
	 * @since 5.4
	 */
	const CRON_HOOK     = 'do-bulk-delete-comment-meta';

	/**
	 * Register comment-meta meta box for delete meta page.
	 *
	 * @static
	 * @since 5.4
	 */
	public static function add_delete_comment_meta_box() {
		$bd = BULK_DELETE();

		add_meta_box(
			self::BOX_COMMENT_META,
			__( 'Bulk Delete Comment Meta', 'bulk-delete' ),
			array( __CLASS__, 'render_delete_comment_meta_box' ),
			$bd->meta_page,
			'advanced'
		);
	}

	/**
	 * Render delete comment-meta meta box for delete meta page.
	 *
	 * @static
	 * @since 5.4
	 */
	public static function render_delete_comment_meta_box() {
		if ( Bulk_Delete_Meta::is_meta_box_hidden( self::BOX_COMMENT_META ) ) {
			printf( __( 'This section just got enabled. Kindly <a href = "%1$s">refresh</a> the page to fully enable it.', 'bulk-delete' ), 'admin.php?page=' . Bulk_Delete_meta::META_PAGE_SLUG );
			return;
		}
?>
        <!-- Comment Meta box start-->
        <fieldset class="options">
<?php
		$types = get_post_types( array(
				'public'   => true,
				'_builtin' => false,
			), 'names'
		);

		array_unshift( $types, 'post' );
?>
        <h4><?php _e( 'Select the post type whose comment meta fields you want to delete', 'bulk-delete' ); ?></h4>
        <table class="optiontable">
<?php
		foreach ( $types as $type ) {
?>
            <tr>
                <td>
                    <input name="smbd_cm_post_type" value = "<?php echo $type; ?>" type = "radio" class = "smbd_cm_post_type" <?php checked( $type, 'post' ); ?>>
                    <label for="smbd_cm_post_type"><?php echo $type; ?> </label>
                </td>
            </tr>
<?php
		}
?>
        </table>

        <h4><?php _e( 'Choose your comment meta field settings', 'bulk-delete' ); ?></h4>
        <table class="optiontable">
            <tr>
                <td>
                    <input name="smbd_cm_use_value" value="false" type="radio" checked>
                    <label for="smbd_cm_use_value"><?php echo __( 'Delete based on comment meta key name only', 'bulk-delete' ); ?></label>
                </td>
            </tr>

            <tr>
                <td>
                    <input name="smbd_cm_use_value" id="smbd_cm_use_value" value="true" type="radio" disabled>
                    <label for="smbd_cm_use_value"><?php echo __( 'Delete based on comment meta key name and value', 'bulk-delete' ); ?></label>
                    <span class="bd-cm-pro" style="color:red; vertical-align: middle;">
                        <?php _e( 'Only available in Pro Addon', 'bulk-delete' ); ?> <a href = "http://bulkwp.com/addons/bulk-delete-comment-meta/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-m-c" target="_blank">Buy now</a>
                    </span>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="smbd_cm_key"><?php _e( 'Comment Meta Key ', 'bulk-delete' ); ?></label>
                    <input name="smbd_cm_key" id="smbd_cm_key" placeholder="<?php _e( 'Meta Key', 'bulk-delete' ); ?>">
                </td>
            </tr>
        </table>
<?php
		/**
		 * Add more fields to the delete comment meta field form.
		 * This hook can be used to add more fields to the delete comment meta field form
		 *
		 * @since 5.4
		 */
		do_action( 'bd_delete_comment_meta_form' );
?>
        <table class="optiontable">
            <tr>
                <td>
                    <h4><?php _e( 'Choose your deletion options', 'bulk-delete' ); ?></h4>
                </td>
            </tr>

            <tr>
                <td>
                    <input name="smbd_cm_restrict" id="smbd_cm_restrict" value = "true" type = "checkbox">
                    <?php _e( 'Only restrict to comments which are ', 'bulk-delete' );?>
                    <select name="smbd_cm_op" id="smbd_cm_op" disabled="disabled">
                        <option value ="before"><?php _e( 'older than', 'bulk-delete' );?></option>
                        <option value ="after"><?php _e( 'commented within last', 'bulk-delete' );?></option>
                    </select>
                    <input type="number" name="smbd_cm_days" id="smbd_cm_days" disabled value ="0" maxlength="4" size="4"><?php _e( 'days', 'bulk-delete' );?>
                </td>
            </tr>

            <tr>
                <td>
                    <input name="smbd_cm_limit" id="smbd_cm_limit" value = "true" type = "checkbox">
                    <?php _e( 'Only delete comment meta field from first ', 'bulk-delete' );?>
                    <input type="number" name="smbd_cm_limit_to" id="smbd_cm_limit_to" disabled value ="0" maxlength="4" size="4"><?php _e( 'comments.', 'bulk-delete' );?>
                    <?php _e( 'Use this option if there are more than 1000 posts and the script timesout.', 'bulk-delete' ) ?>
                </td>
            </tr>

            <tr>
                <td>
                    <input name="smbd_cm_cron" value="false" type = "radio" checked="checked"> <?php _e( 'Delete now', 'bulk-delete' ); ?>
                    <input name="smbd_cm_cron" value="true" type = "radio" id = "smbd_cm_cron" disabled > <?php _e( 'Schedule', 'bulk-delete' ); ?>
                    <input name="smbd_cm_cron_start" id="smbd_cm_cron_start" value = "now" type = "text" disabled><?php _e( 'repeat ', 'bulk-delete' );?>
                    <select name="smbd_cm_cron_freq" id="smbd_cm_cron_freq" disabled>
                        <option value = "-1"><?php _e( "Don't repeat", 'bulk-delete' ); ?></option>
<?php
		$schedules = wp_get_schedules();
		foreach ( $schedules as $key => $value ) {
?>
                        <option value = "<?php echo $key; ?>"><?php echo $value['display']; ?></option>
<?php
		}
?>
                    </select>
                    <span class="bd-cm-pro" style="color:red">
                        <?php _e( 'Only available in Pro Addon', 'bulk-delete' ); ?> <a href = "http://bulkwp.com/addons/bulk-delete-comment-meta/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-m-c">Buy now</a>
                    </span>
                </td>
            </tr>

            <tr>
                <td>
                    <?php _e( 'Enter time in Y-m-d H:i:s format or enter now to use current time', 'bulk-delete' );?>
                </td>
            </tr>

        </table>
        </fieldset>

        <p>
            <button type="submit" name="bd_action" value="delete_meta_comment" class="button-primary"><?php _e( 'Bulk Delete ', 'bulk-delete' ) ?>&raquo;</button>
        </p>
        <!-- Comment Meta box end-->
<?php
	}

	/**
	 * Filter JS Array and add validation hooks.
	 *
	 * @since 5.4
	 * @static
	 * @param array   $js_array JavaScript Array
	 * @return array           Modified JavaScript Array
	 */
	public static function filter_js_array( $js_array ) {
		$js_array['dt_iterators'][] = '_cm';
		$js_array['validators']['delete_meta_comment'] = 'noValidation';

		$js_array['pre_action_msg']['delete_meta_comment'] = 'deleteCMWarning';
		$js_array['msg']['deleteCMWarning'] = __( 'Are you sure you want to delete all the comment meta fields that match the selected filters?', 'bulk-delete' );

		return $js_array;
	}

	/**
	 * Controller for deleting comment meta fields.
	 *
	 * @static
	 * @since  5.4
	 */
	public static function do_delete_comment_meta() {
		$delete_options              = array();
		$delete_options['post_type'] = esc_sql( array_get( $_POST, 'smbd_cm_post_type', 'post' ) );

		$delete_options['use_value'] = array_get_bool( $_POST, 'smbd_cm_use_value', false );
		$delete_options['meta_key']  = esc_sql( array_get( $_POST, 'smbd_cm_key', '' ) );

		$delete_options['limit_to']  = absint( array_get( $_POST, 'smbd_cm_limit_to', 0 ) );

		$delete_options['restrict']  = array_get_bool( $_POST, 'smbd_cm_restrict', false );
		$delete_options['op']        = esc_sql( array_get( $_POST, 'smbd_cm_op', 'before' ) );
		$delete_options['days']      = absint( array_get( $_POST, 'smbd_cm_days', 0 ) );

		/**
		 * Delete comment-meta delete options filter.
		 * This filter is for processing filtering options for deleting comment meta
		 *
		 * @since 5.4
		 */
		$delete_options = apply_filters( 'bd_delete_comment_meta_options', $delete_options, $_POST );

		if ( 'true' == array_get( $_POST, 'smbd_cm_cron', 'false' ) ) {
			$freq = $_POST['smbd_cm_cron_freq'];
			$time = strtotime( $_POST['smbd_cm_cron_start'] ) - ( get_option( 'gmt_offset' ) * 60 * 60 );

			if ( $freq == -1 ) {
				wp_schedule_single_event( $time, self::CRON_HOOK, array( $delete_options ) );
			} else {
				wp_schedule_event( $time, $freq, self::CRON_HOOK, array( $delete_options ) );
			}
			$msg = __( 'Comment meta fields from the comments with the selected criteria are scheduled for deletion.', 'bulk-delete' ) . ' ' .
				sprintf( __( 'See the full list of <a href = "%s">scheduled tasks</a>' , 'bulk-delete' ), get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=' . Bulk_Delete::CRON_PAGE_SLUG );
		} else {
			$deleted_count = self::delete_comment_meta( $delete_options );
			$msg = sprintf( _n( 'Deleted comment meta field from %d comment', 'Deleted comment meta field from %d comments' , $deleted_count, 'bulk-delete' ), $deleted_count );
		}

		add_settings_error(
			Bulk_Delete_Meta::META_PAGE_SLUG,
			'deleted-comments',
			$msg,
			'updated'
		);
	}

	/**
	 * Delete Comment Meta.
	 *
	 * @static
	 * @since  5.4
	 * @param array   $delete_options Options for deleting
	 * @return int                   Number of comments that were deleted
	 */
	public static function delete_comment_meta( $delete_options ) {
		$count     = 0;
		$post_type = $delete_options['post_type'];
		$limit_to  = $delete_options['limit_to'];
		$meta_key  = $delete_options['meta_key'];
		$use_value = $delete_options['use_value'];
		$restrict  = $delete_options['restrict'];
		$days      = $delete_options['days'];
		$op        = $delete_options['op'];

		$options = array(
			'post_type' => $post_type,
		);

		if ( $limit_to > 0 ) {
			$options['number'] = $limit_to;
		}

		if ( $restrict ) {
			$options['date_query'] = array(
				array(
					'column' => 'comment_date',
					$op      => "{$days} day ago",
				),
			);
		}

		if ( $use_value ) {
			$options['meta_query'] = apply_filters( 'bd_delete_comment_meta_query', array(), $delete_options );
		} else {
			$options['meta_key'] = $meta_key;
		}

		$comments = get_comments( $options );

		foreach ( $comments as $comment ) {
			if ( delete_comment_meta( $comment->comment_ID, $meta_key ) ) {
				$count++;
			}
		}
		return $count;
	}
}

// hooks
add_action( 'bd_add_meta_box_for_meta', array( 'Bulk_Delete_Comment_Meta', 'add_delete_comment_meta_box' ) );
add_action( 'bd_delete_meta_comment', array( 'Bulk_Delete_Comment_Meta', 'do_delete_comment_meta' ) );

add_filter( 'bd_javascript_array', array( 'Bulk_Delete_Comment_Meta', 'filter_js_array' ) );
?>
