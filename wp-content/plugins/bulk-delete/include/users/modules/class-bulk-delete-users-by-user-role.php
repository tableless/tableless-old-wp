<?php
/**
 * Bulk Delete Users by User Role
 *
 * @since   5.5
 * @author  Sudar
 * @package BulkDelete\Users\Modules
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Bulk Delete Users by User Role.
 *
 * @since 5.5
 */
class Bulk_Delete_Users_By_User_Role extends BD_User_Meta_Box_Module {
	/**
	 * Make this class a "hybrid Singleton".
	 *
	 * @static
	 * @since 5.5
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * Initialize and setup variables.
	 *
	 * @since 5.5
	 */
	protected function initialize() {
		$this->item_type     = 'users';
		$this->field_slug    = 'u_role';
		$this->meta_box_slug = 'bd_users_by_role';
		$this->meta_box_hook = "bd_add_meta_box_for_{$this->item_type}";
		$this->delete_action = 'delete_users_by_role';
		$this->cron_hook     = 'do-bulk-delete-users-by-role';
		$this->scheduler_url = 'http://bulkwp.com/addons/scheduler-for-deleting-users-by-role/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-u-ur';
		$this->messages = array(
			'box_label'      => __( 'By User Role', 'bulk-delete' ),
			'scheduled'      => __( 'Users from the selected userrole are scheduled for deletion.', 'bulk-delete' ),
			'deleted_single' => __( 'Deleted %d user from the selected roles', 'bulk-delete' ),
			'deleted_plural' => __( 'Deleted %d users from the selected roles', 'bulk-delete' ),
		);
	}

	/**
	 * Render delete users box.
	 *
	 * @since 5.5
	 */
	public function render() {
?>
        <!-- Users Start-->
        <h4><?php _e( 'Select the user roles from which you want to delete users', 'bulk-delete' ); ?></h4>

        <fieldset class="options">
        <table class="optiontable">
<?php
		$users_count = count_users();
		foreach ( $users_count['avail_roles'] as $role => $count ) {
?>
            <tr>
                <td scope="row" >
                    <input name="smbd_u_roles[]" value = "<?php echo $role; ?>" type = "checkbox">
                    <label for="smbd_u_roles"><?php echo $role; ?> (<?php echo $count . ' '; _e( 'Users', 'bulk-delete' ); ?>)</label>
                </td>
            </tr>
<?php
		}
?>
		</table>

        <table class="optiontable">
<?php
		$this->render_filtering_table_header();
		$this->render_user_login_restrict_settings();
		$this->render_user_with_no_posts_settings();
		$this->render_limit_settings();
		$this->render_cron_settings();
?>
        </table>
        </fieldset>
        <!-- Users end-->
<?php
		$this->render_submit_button();
	}

	/**
	 * Process the request for deleting users by role.
	 *
	 * @since 5.5
	 */
	public function process() {
		$delete_options                   = array();
		$delete_options['selected_roles'] = array_get( $_POST, 'smbd_u_roles' );

		$this->process_user_delete( $delete_options );
	}

	/**
	 * Delete users by user role.
	 *
	 * @since 5.5
	 * @param array $delete_options Delete Options
	 * @return int  Number of users deleted
	 */
	public function delete( $delete_options ) {
		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		$count = 0;

		foreach ( $delete_options['selected_roles'] as $role ) {
			$options = array('role' => $role);
			if ( $delete_options['limit_to'] > 0 ) {
				$options['number'] = $delete_options['limit_to'];
			}

			$count += $this->delete_users( $options, $delete_options );
		}

		return $count;
	}

	/**
	 * Filter JS Array and add validation hooks
	 *
	 * @since 5.5
	 * @param array  $js_array JavaScript Array
	 * @return array           Modified JavaScript Array
	 */
	public function filter_js_array( $js_array ) {
		$js_array['dt_iterators'][] = '_' . $this->field_slug;

		$js_array['pre_action_msg'][ $this->delete_action ] = 'deleteUsersWarning';
		$js_array['msg']['deleteUsersWarning'] = __( 'Are you sure you want to delete all the users from the selected user role?', 'bulk-delete' );

		$js_array['error_msg'][ $this->delete_action ] = 'selectOneUserRole';
		$js_array['msg']['selectOneUserRole'] = __( 'Select at least one user role from which users should be deleted', 'bulk-delete' );

		return $js_array;
	}
}

Bulk_Delete_Users_By_User_Role::factory();
?>
