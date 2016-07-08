<?php
/**
 * Utility class for deleting Jetpack Contact Form Messages
 *
 * @since      5.3
 * @author     Sudar
 * @package    BulkDelete\Misc
 */


class Bulk_Delete_Jetpack_Contact_Form_Message {

	// box slugs
	const BOX_JETPACK_MESSAGES = 'bd-jetpack-feedback';

	const FEEDBACK_POST_TYPE   = 'feedback';
	const CRON_HOOK            = 'do-bulk-delete-feedback';

	/**
	 * Register Jetpack Feedback meta box for delete misc page
	 *
	 * @static
	 * @since 5.3
	 */
	public static function add_delete_jetpack_messages_meta_box() {
		$bd = BULK_DELETE();

		add_meta_box(
			self::BOX_JETPACK_MESSAGES,
			__( 'Bulk Delete Jetpack Contact Form Messages', 'bulk-delete' ),
			array( __CLASS__, 'render_delete_jetpack_messages_box' ),
			$bd->misc_page,
			'advanced'
		);
	}

	/**
	 * Render Jetpack Feedback meta box for delete misc page
	 *
	 * @static
	 * @since 5.3
	 */
	public static function render_delete_jetpack_messages_box() {
		if ( Bulk_Delete_Misc::is_misc_box_hidden( self::BOX_JETPACK_MESSAGES ) ) {
			printf( __( 'This section just got enabled. Kindly <a href = "%1$s">refresh</a> the page to fully enable it.', 'bulk-delete' ), 'admin.php?page=' . Bulk_Delete_Misc::MISC_PAGE_SLUG );
			return;
		}

		if ( ! self::is_jetpack_contact_active() ) {
?>
            <!-- Delete Jetpack Feedback box start-->
            <p>
                <span style="color:red">
                    <?php _e( 'Jetpack contact form is not enabled.', 'bulk-delete' ); ?>
                </span>
            </p>
            <!-- Delete Jetpack Feedback box end-->
<?php
		} else {
			$feedback_count = wp_count_posts( self::FEEDBACK_POST_TYPE );
?>
            <!-- Delete Jetpack Feedback box start-->
            <fieldset class="options">
            <table class="optiontable">
                <tr>
                    <td scope="row" >
                        <input name="smbd_feedback_use_filter" value = "false" type = "radio" checked>
                    </td>
                    <td>
                    <label for="smbd_feedback"><?php echo __( 'Delete all Contact Form Messages', 'bulk-delete' ), ' ( ', $feedback_count->publish, ' ', __( 'in total', 'bulk-delete' ), ' )'; ?></label>
                    </td>
                </tr>

                <tr>
                    <td scope="row" >
                        <input name="smbd_feedback_use_filter" id="smbd_feedback_use_filter" value = "true" type = "radio" disabled>
                    </td>
                    <td>
                        <label for="smbd_feedback"><?php _e( 'Delete Messages based on filters', 'bulk-delete' ); ?></label>
                        <span class = "bd-feedback-pro" style = "color:red; vertical-align: middle;"><?php _e( 'Only available in Pro Addon', 'bulk-delete' ); ?> <a href = "http://bulkwp.com/addons/bulk-delete-jetpack-contact-form-messages/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-jcm" target="_blank">Buy now</a></span>
                    </td>
                </tr>
            </table>
<?php
			/**
			 * Add more fields to the delete jetpack messages form
			 * This hook can be used to add more fields to the delete jetpack messages form
			 *
			 * @since 5.3
			 */
			do_action( 'bd_delete_jetpack_messages_form' );
?>
            <table class="optiontable">
                <tr>
                    <td colspan="2">
                        <h4><?php _e( 'Choose your date options', 'bulk-delete' ); ?></h4>
                    </td>
                </tr>

                <tr>
                    <td scope="row">
                        <input name="smbd_feedback_restrict" id ="smbd_feedback_restrict" value = "true" type = "checkbox">
                    </td>
                    <td>
                        <?php _e( 'Only restrict to posts which are ', 'bulk-delete' );?>
                        <select name="smbd_feedback_op" id="smbd_feedback_op" disabled>
                            <option value ="<"><?php _e( 'older than', 'bulk-delete' );?></option>
                            <option value =">"><?php _e( 'posted within last', 'bulk-delete' );?></option>
                        </select>
                        <input type ="textbox" name="smbd_feedback_days" id ="smbd_feedback_days" value ="0"  maxlength="4" size="4" disabled><?php _e( 'days', 'bulk-delete' );?>
                    </td>
                </tr>

                <tr>
                    <td scope="row">
                        <input name="smbd_feedback_limit" id="smbd_feedback_limit" value = "true" type = "checkbox">
                    </td>
                    <td>
                        <?php _e( 'Only delete first ', 'bulk-delete' );?>
                        <input type ="textbox" name="smbd_feedback_limit_to" id="smbd_feedback_limit_to" disabled value ="0" maxlength="4" size="4"><?php _e( 'posts.', 'bulk-delete' );?>
                        <?php _e( 'Use this option if there are more than 1000 posts and the script timesout.', 'bulk-delete' ) ?>
                    </td>
                </tr>

                <tr>
                    <td colspan="2">
                        <h4><?php _e( 'Choose your deletion options', 'bulk-delete' ); ?></h4>
                    </td>
                </tr>

                <tr>
                    <td scope="row" colspan="2">
                        <input name="smbd_feedback_force_delete" value = "false" type = "radio" checked="checked"> <?php _e( 'Move to Trash', 'bulk-delete' ); ?>
                        <input name="smbd_feedback_force_delete" value = "true" type = "radio"> <?php _e( 'Delete permanently', 'bulk-delete' ); ?>
                    </td>
                </tr>

            <tr>
                <td scope="row" colspan="2">
                    <input name="smbd_feedback_cron" value = "false" type = "radio" checked="checked" > <?php _e( 'Delete now', 'bulk-delete' ); ?>
                    <input name="smbd_feedback_cron" value = "true" type = "radio" id = "smbd_feedback_cron" disabled > <?php _e( 'Schedule', 'bulk-delete' ); ?>
                    <input name="smbd_feedback_cron_start" id = "smbd_feedback_cron_start" value = "now" type = "text" disabled><?php _e( 'repeat ', 'bulk-delete' );?>
                    <select name = "smbd_feedback_cron_freq" id = "smbd_feedback_cron_freq" disabled>
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
                    <span class = "bd-feedback-pro" style = "color:red"><?php _e( 'Only available in Pro Addon', 'bulk-delete' ); ?> <a href = "http://bulkwp.com/addons/bulk-delete-jetpack-contact-form-messages/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-jcm" target="_blank">Buy now</a></span>
                </td>
            </tr>

            </table>
            </fieldset>
            <p class="submit">
                <button type='submit' name='bd_action' value='delete_jetpack_messages' class='button-primary'><?php _e( 'Bulk Delete ', 'bulk-delete' ) ?>&raquo;</button>
            </p>
            <!-- Delete Jetpack Feedback box end-->
<?php
		}
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
		$js_array['dt_iterators'][] = '_feedback';
		$js_array['validators']['delete_jetpack_messages'] = 'noValidation';

		$js_array['pre_action_msg']['delete_jetpack_messages'] = 'deleteJetpackWarning';
		$js_array['msg']['deleteJetpackWarning'] = __( 'Are you sure you want to delete all the Jetpack contact form messages based on the selected filters?', 'bulk-delete' );
		return $js_array;
	}

	/**
	 * Controller for deleting Jetpack contact form messages
	 *
	 * @static
	 * @since  5.3
	 */
	public static function do_delete_jetpack_messages() {
		$delete_options                  = array();

		$delete_options['use_filter']    = array_get( $_POST, 'smbd_feedback_use_filter', 'false' );

		$delete_options['restrict']      = array_get( $_POST, 'smbd_feedback_restrict', false );
		$delete_options['limit_to']      = absint( array_get( $_POST, 'smbd_feedback_limit_to', 0 ) );
		$delete_options['force_delete']  = array_get( $_POST, 'smbd_feedback_force_delete', 'false' );

		$delete_options['feedback_op']   = array_get( $_POST, 'smbd_feedback_op' );
		$delete_options['feedback_days'] = array_get( $_POST, 'smbd_feedback_days' );

		/**
		 * Delete jetpack feedback delete options filter
		 * This filter is for processing filtering options for deleting jetpack message
		 *
		 * @since 5.3
		 */
		$delete_options = apply_filters( 'bd_delete_jetpack_messages_delete_options', $delete_options, $_POST );

		if ( 'true' == array_get( $_POST, 'smbd_feedback_cron', 'false' ) ) {
			$freq = $_POST['smbd_feedback_cron_freq'];
			$time = strtotime( $_POST['smbd_feedback_cron_start'] ) - ( get_option( 'gmt_offset' ) * 60 * 60 );

			if ( $freq == -1 ) {
				wp_schedule_single_event( $time, self::CRON_HOOK, array( $delete_options ) );
			} else {
				wp_schedule_event( $time, $freq, self::CRON_HOOK, array( $delete_options ) );
			}
			$msg = __( 'Jetpack contact form messages with the selected criteria are scheduled for deletion.', 'bulk-delete' ) . ' ' .
				sprintf( __( 'See the full list of <a href = "%s">scheduled tasks</a>' , 'bulk-delete' ), get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=' . Bulk_Delete::CRON_PAGE_SLUG );
		} else {
			$deleted_count = self::delete_jetpack_messages( $delete_options );
			$msg = sprintf( _n( 'Deleted %d Jetpack contact form message', 'Deleted %d Jetpack contact form messages' , $deleted_count, 'bulk-delete' ), $deleted_count );
		}

		add_settings_error(
			Bulk_Delete_Misc::MISC_PAGE_SLUG,
			'deleted-posts',
			$msg,
			'updated'
		);
	}

	/**
	 * Delete Jetpack contact form messages
	 *
	 * @static
	 * @since  5.3
	 * @param array   $delete_options Options for deleting
	 * @return int                   Number of posts that were deleted
	 */
	public static function delete_jetpack_messages( $delete_options ) {
		$count = 0;
		$use_filter = $delete_options['use_filter'];

		$options = array(
			'post_status' => 'publish',
			'post_type'   => self::FEEDBACK_POST_TYPE,
		);

		$limit_to = $delete_options['limit_to'];

		if ( $limit_to > 0 ) {
			$options['showposts'] = $limit_to;
		} else {
			$options['nopaging'] = 'true';
		}

		$force_delete = $delete_options['force_delete'];

		if ( 'true' == $force_delete ) {
			$force_delete = true;
		} else {
			$force_delete = false;
		}

		if ( 'true' == $delete_options['restrict'] ) {
			$options['op'] = $delete_options['feedback_op'];
			$options['days'] = $delete_options['feedback_days'];

			if ( ! class_exists( 'Bulk_Delete_By_Days' ) ) {
				require_once Bulk_Delete::$PLUGIN_DIR . '/include/util/class-bulk-delete-by-days.php';
			}
			new Bulk_Delete_By_Days;
		}

		$post_ids = bd_query( $options );
		foreach ( $post_ids as $post_id ) {
			if ( 'true' == $use_filter ) {

				/**
				 * Process additional filters for deleting jetpack messages
				 *
				 * @since 5.3
				 */
				$can_delete = apply_filters( 'bd_delete_jetpack_messages_can_delete', $delete_options, $post_id );
				if ( ! $can_delete ) {
					continue;
				}
			}

			// $force delete parameter to custom post types doesn't work
			if ( $force_delete ) {
				wp_delete_post( $post_id, true );
			} else {
				wp_trash_post( $post_id );
			}
			$count++;
		}

		return $count;
	}

	/**
	 * Check whether Jetpack Contact Form is active
	 *
	 * @static
	 * @since  5.3
	 * @return bool True if active, False otherwise
	 */
	public static function is_jetpack_contact_active() {
		$jetpack_active_modules = get_option( 'jetpack_active_modules' );
		if ( class_exists( 'Jetpack', false ) && $jetpack_active_modules && in_array( 'contact-form', $jetpack_active_modules ) ) {
			return true;
		}

		return false;
	}
}

// hooks
add_action( 'bd_add_meta_box_for_misc'  , array( 'Bulk_Delete_Jetpack_Contact_Form_Message', 'add_delete_jetpack_messages_meta_box' ) );
add_action( 'bd_delete_jetpack_messages', array( 'Bulk_Delete_Jetpack_Contact_Form_Message', 'do_delete_jetpack_messages' ) );

add_filter( 'bd_javascript_array'       , array( 'Bulk_Delete_Jetpack_Contact_Form_Message', 'filter_js_array' ) );
?>
