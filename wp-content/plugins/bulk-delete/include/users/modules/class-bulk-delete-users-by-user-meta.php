<?php
/**
 * Bulk Delete Users by User Meta.
 *
 * @since   5.5
 * @author  Sudar
 * @package BulkDelete\Users\Modules
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Bulk Delete Users by User Meta.
 *
 * @since 5.5
 */
class Bulk_Delete_Users_By_User_Meta extends BD_User_Meta_Box_Module {
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
		$this->field_slug    = 'u_meta';
		$this->meta_box_slug = 'bd_users_by_meta';
		$this->meta_box_hook = "bd_add_meta_box_for_{$this->item_type}";
		$this->delete_action = 'delete_users_by_meta';
		$this->cron_hook     = 'do-bulk-delete-users-by-meta';
		$this->scheduler_url = 'http://bulkwp.com/addons/scheduler-for-deleting-users-by-meta/?utm_source=wpadmin&utm_campaign=BulkDelete&utm_medium=buynow&utm_content=bd-u-ma';
		$this->messages = array(
			'box_label'      => __( 'By User Meta', 'bulk-delete' ),
			'scheduled'      => __( 'Users from with the selected user meta are scheduled for deletion.', 'bulk-delete' ),
			'deleted_single' => __( 'Deleted %d user with the selected user meta', 'bulk-delete' ),
			'deleted_plural' => __( 'Deleted %d users with the selected user meta', 'bulk-delete' ),
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
        <h4><?php _e( 'Select the user meta from which you want to delete users', 'bulk-delete' ); ?></h4>

        <fieldset class="options">
        <table class="optiontable">
		<select name="smbd_u_meta_key" class="select2">
<?php
		$meta_keys = $this->get_unique_meta_keys();
		foreach ( $meta_keys as $meta_key ) {
			printf( '<option value="%s">%s</option>', $meta_key, $meta_key );
		}
?>
		</select>
		<select name="smbd_u_meta_compare">
			<option value="=">=</option>
			<option value="!=">!=</option>
			<option value=">">></option>
			<option value=">=">>=</option>
			<option value="<"><</option>
			<option value="<="><=</option>
		</select>
		<input type="text" name="smbd_u_meta_value" id="smbd_u_meta_value" placeholder="<?php _e( 'Meta Value', 'bulk-delete' );?>">

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
	 * Process the request for deleting users by meta.
	 *
	 * @since 5.5
	 */
	public function process() {
		$delete_options                   = array();
		$delete_options['meta_key']       = array_get( $_POST, 'smbd_u_meta_key' );
		$delete_options['meta_compare']   = array_get( $_POST, 'smbd_u_meta_compare', '=' );
		$delete_options['meta_value']     = array_get( $_POST, 'smbd_u_meta_value' );

		$this->process_user_delete( $delete_options );
	}

	/**
	 * Delete users by user meta.
	 *
	 * @since 5.5
	 * @param array $delete_options Delete Options
	 * @return int  Number of users deleted
	 */
	public function delete( $delete_options ) {
		if ( ! function_exists( 'wp_delete_user' ) ) {
			require_once ABSPATH . 'wp-admin/includes/user.php';
		}

		$options = array(
			'meta_key'     => $delete_options['meta_key'],
			'meta_value'   => $delete_options['meta_value'],
			'meta_compare' => $delete_options['meta_compare'],
		);

		if ( $delete_options['limit_to'] > 0 ) {
			$options['number'] = $delete_options['limit_to'];
		}

		return $this->delete_users( $options, $delete_options );
	}

	/**
	 * Filter JS Array and add validation hooks.
	 *
	 * @since 5.5
	 * @param array  $js_array JavaScript Array
	 * @return array           Modified JavaScript Array
	 */
	public function filter_js_array( $js_array ) {
		$js_array['dt_iterators'][] = '_' . $this->field_slug;
        $js_array['validators'][ $this->delete_action ] = 'validateUserMeta';

		$js_array['pre_action_msg'][ $this->delete_action ] = 'deleteUsersByMetaWarning';
		$js_array['msg']['deleteUsersByMetaWarning'] = __( 'Are you sure you want to delete all the users from the selected user meta?', 'bulk-delete' );

        $js_array['error_msg'][ $this->delete_action ] = 'enterUserMetaValue';
        $js_array['msg']['enterUserMetaValue'] = __( 'Please enter the value for the user meta field based on which you want to delete users', 'bulk-delete' );

		return $js_array;
	}

	/**
	 * Get unique user meta keys.
	 *
	 * @since 5.5
	 * @return array List of unique meta keys.
	 */
	private function get_unique_meta_keys() {
		global $wpdb;

		return $wpdb->get_col( "SELECT DISTINCT(meta_key) FROM {$wpdb->prefix}usermeta ORDER BY meta_key" );
	}
}

Bulk_Delete_Users_By_User_Meta::factory();
?>
