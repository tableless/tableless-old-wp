<?php
/**
 * Utility class for deleting User Meta.
 *
 * @since      5.4
 * @author     Sudar
 * @package    BulkDelete\Meta
 */


class Bulk_Delete_User_Meta {

	/**
	 * Box slug.
	 *
	 * @since 5.4
	 */
	const BOX_USER_META = 'bd-user-meta';

	/**
	 * Cron Hook.
	 *
	 * @since 5.4
	 */
	const CRON_HOOK     = 'do-bulk-delete-user-meta';

	/**
	 * Register user-meta meta box for delete meta page.
	 *
	 * @static
	 * @since 5.4
	 */
	public static function add_delete_user_meta_box() {
		$bd = BULK_DELETE();

		add_meta_box(
			self::BOX_USER_META,
			__( 'Bulk Delete User Meta', 'bulk-delete' ),
			array( __CLASS__, 'render_delete_user_meta_box' ),
			$bd->meta_page,
			'advanced'
		);
	}

	/**
	 * Render delete user-meta meta box for delete meta page.
	 *
	 * @static
	 * @since 5.4
	 */
	public static function render_delete_user_meta_box() {
		if ( Bulk_Delete_Meta::is_meta_box_hidden( self::BOX_USER_META ) ) {
			printf( __( 'This section just got enabled. Kindly <a href = "%1$s">refresh</a> the page to fully enable it.', 'bulk-delete' ), 'admin.php?page=' . Bulk_Delete_meta::META_PAGE_SLUG );
			return;
		}
?>
        <!-- User Meta box start-->
        <fieldset class="options">
        <h4><?php _e( 'Select the user role whose user meta fields you want to delete', 'bulk-delete' ); ?></h4>
        <table class="optiontable">
<?php
		$users_count = count_users();
		foreach ( $users_count['avail_roles'] as $role => $count ) {
?>
            <tr>
                <td>
                    <input name="smbd_um_role" value = "<?php echo $role; ?>" type = "radio" <?php checked( $role, 'administrator' ); ?>>
                    <label for="smbd_um_role"><?php echo $role; ?> (<?php echo $count . ' '; _e( 'Users', 'bulk-delete' ); ?>)</label>
                </td>
            </tr>
<?php
		}
?>
        </table>

        <h4><?php _e( 'Choose your user meta field settings', 'bulk-delete' ); ?></h4>
        <table class="optiontable">
            <tr>
                <td>
                    <input name="smbd_um_use_value" value="false" type="radio" checked>
                    <label for="smbd_um_use_value"><?php echo __( 'Delete based on user meta key name only', 'bulk-delete' ); ?></label>
                </td>
            </tr>

            <tr>
                <td>
                    <input name="smbd_um_use_value" id="smbd_um_use_value" value="true" type="radio" disabled>
                    <label for="smbd_um_use_value"><?php echo __( 'Delete based on user meta key name and value', 'bulk-delete' ); ?></label>
                    <span class="bd-um-pro" style="color:red; vertical-align: middle;">
                        <?php _e( 'Only available in Pro Addon', 'bulk-delete' ); ?> <a href = "http://bulkwp.com/addons/bulk-delete-user-meta/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-m-u" target="_blank">Buy now</a>
                    </span>
                </td>
            </tr>

            <tr>
                <td>
                    <label for="smbd_um_key"><?php _e( 'User Meta Key ', 'bulk-delete' ); ?></label>
                    <input name="smbd_um_key" id="smbd_um_key" placeholder="<?php _e( 'Meta Key', 'bulk-delete' ); ?>">
                </td>
            </tr>
        </table>
<?php
		/**
		 * Add more fields to the delete user meta field form.
		 * This hook can be used to add more fields to the delete user meta field form
		 *
		 * @since 5.4
		 */
		do_action( 'bd_delete_user_meta_form' );
?>
        <table class="optiontable">
            <tr>
                <td>
                    <h4><?php _e( 'Choose your deletion options', 'bulk-delete' ); ?></h4>
                </td>
            </tr>

            <tr>
                <td>
                    <input name="smbd_um_limit" id="smbd_um_limit" value = "true" type = "checkbox">
                    <?php _e( 'Only delete user meta field from first ', 'bulk-delete' );?>
                    <input type ="textbox" name="smbd_um_limit_to" id="smbd_um_limit_to" disabled value ="0" maxlength="4" size="4"><?php _e( 'users.', 'bulk-delete' );?>
                    <?php _e( 'Use this option if there are more than 1000 users and the script timesout.', 'bulk-delete' ) ?>
                </td>
            </tr>

            <tr>
                <td>
                    <input name="smbd_um_cron" value = "false" type = "radio" checked="checked"> <?php _e( 'Delete now', 'bulk-delete' ); ?>
                    <input name="smbd_um_cron" value = "true" type = "radio" id = "smbd_um_cron" disabled > <?php _e( 'Schedule', 'bulk-delete' ); ?>
                    <input name="smbd_um_cron_start" id = "smbd_um_cron_start" value = "now" type = "text" disabled><?php _e( 'repeat ', 'bulk-delete' );?>
                    <select name="smbd_um_cron_freq" id = "smbd_um_cron_freq" disabled>
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
                    <span class="bd-um-pro" style="color:red">
                        <?php _e( 'Only available in Pro Addon', 'bulk-delete' ); ?> <a href = "http://bulkwp.com/addons/bulk-delete-user-meta/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-m-u">Buy now</a>
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
            <button type="submit" name="bd_action" value="delete_meta_user" class="button-primary"><?php _e( 'Bulk Delete ', 'bulk-delete' ) ?>&raquo;</button>
        </p>
        <!-- User Meta box end-->
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
		$js_array['dt_iterators'][] = '_um';
		$js_array['validators']['delete_meta_user'] = 'noValidation';

		$js_array['pre_action_msg']['delete_meta_user'] = 'deleteUMWarning';
		$js_array['msg']['deleteUMWarning'] = __( 'Are you sure you want to delete all the user meta fields that match the selected filters?', 'bulk-delete' );

		return $js_array;
	}

	/**
	 * Controller for deleting user meta fields.
	 *
	 * @static
	 * @since  5.4
	 */
	public static function do_delete_user_meta() {
		$delete_options              = array();
		$delete_options['user_role'] = array_get( $_POST, 'smbd_um_role', 'administrator' );

		$delete_options['use_value'] = array_get_bool( $_POST, 'smbd_um_use_value', false );
		$delete_options['meta_key']  = esc_sql( array_get( $_POST, 'smbd_um_key', '' ) );

		$delete_options['limit_to']  = absint( array_get( $_POST, 'smbd_um_limit_to', 0 ) );

		/**
		 * Delete user-meta delete options filter.
		 * This filter is for processing filtering options for deleting user meta
		 *
		 * @since 5.4
		 */
		$delete_options = apply_filters( 'bd_delete_user_meta_options', $delete_options, $_POST );

		if ( 'true' == array_get( $_POST, 'smbd_um_cron', 'false' ) ) {
			$freq = $_POST['smbd_um_cron_freq'];
			$time = strtotime( $_POST['smbd_um_cron_start'] ) - ( get_option( 'gmt_offset' ) * 60 * 60 );

			if ( $freq == -1 ) {
				wp_schedule_single_event( $time, self::CRON_HOOK, array( $delete_options ) );
			} else {
				wp_schedule_event( $time, $freq, self::CRON_HOOK, array( $delete_options ) );
			}
			$msg = __( 'User meta fields from the users with the selected criteria are scheduled for deletion.', 'bulk-delete' ) . ' ' .
				sprintf( __( 'See the full list of <a href = "%s">scheduled tasks</a>' , 'bulk-delete' ), get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=' . Bulk_Delete::CRON_PAGE_SLUG );
		} else {
			$deleted_count = self::delete_user_meta( $delete_options );
			$msg = sprintf( _n( 'Deleted user meta field from %d user', 'Deleted user meta field from %d users' , $deleted_count, 'bulk-delete' ), $deleted_count );
		}

		add_settings_error(
			Bulk_Delete_Meta::META_PAGE_SLUG,
			'deleted-users',
			$msg,
			'updated'
		);
	}

	/**
	 * Delete User Meta.
	 *
	 * @static
	 * @since  5.4
	 * @param array   $delete_options Options for deleting
	 * @return int                   Number of users that were deleted
	 */
	public static function delete_user_meta( $delete_options ) {
		$count     = 0;
		$user_role = $delete_options['user_role'];
		$meta_key  = $delete_options['meta_key'];
		$use_value = $delete_options['use_value'];
		$limit_to  = $delete_options['limit_to'];

		$options = array(
			'role' => $user_role,
		);

		if ( $limit_to > 0 ) {
			$options['number'] = $limit_to;
		}

		if ( $use_value ) {
			$options['meta_query'] = apply_filters( 'bd_delete_user_meta_query', array(), $delete_options );
		} else {
			$options['meta_key'] = $meta_key;
		}

		$users = get_users( $options );

		foreach ( $users as $user ) {
			if ( delete_user_meta( $user->ID, $meta_key ) ) {
				$count++;
			}
		}
		return $count;
	}
}

// hooks
add_action( 'bd_add_meta_box_for_meta', array( 'Bulk_Delete_User_Meta', 'add_delete_user_meta_box' ) );
add_action( 'bd_delete_meta_user', array( 'Bulk_Delete_User_Meta', 'do_delete_user_meta' ) );

add_filter( 'bd_javascript_array', array( 'Bulk_Delete_User_Meta', 'filter_js_array' ) );
?>
