<?php
/**
 * Base class for a Bulk Delete User Meta Box Module.
 *
 * @since 5.5.2
 * @author Sudar
 * @package BulkDelete\Base\Users
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Encapsulates the Bulk Delete User Meta box Module Logic.
 * All Bulk Delete User Meta box Modules should extend this class.
 *
 * @see BD_Meta_Box_Module
 * @since 5.5.2
 * @abstract
 */
abstract class BD_User_Meta_Box_Module extends BD_Meta_Box_Module {

	/**
	 * Query and Delete users.
	 *
	 * @since  5.5.2
	 * @access protected
	 * @param  array     $options        Options to query users.
	 * @param  array     $delete_options Delete options.
	 * @return int                       Number of users who were deleted.
	 */
	protected function delete_users( $options, $delete_options ) {
		$count = 0;
		$users = get_users( $options );

		foreach ( $users as $user ) {
			if ( ! $this->can_delete_by_registered_date( $delete_options, $user ) ) {
				continue;
			}

			if ( ! $this->can_delete_by_logged_date( $delete_options, $user ) ) {
				continue;
			}

			if ( ! $this->can_delete_by_post_count( $delete_options, $user ) ) {
				continue;
			}

			$deleted = wp_delete_user( $user->ID );
			if ( $deleted ) {
				$count ++;
			}
		}

		return $count;
	}

	/**
	 * Can the user be deleted based on the 'post count' option?
	 *
	 * @since  5.5.2
	 * @access protected
	 * @param  array     $delete_options Delete Options.
	 * @param  object    $user           User objet that needs to be deleted.
	 * @return bool                      True if the user can be deleted, false otherwise.
	 */
	protected function can_delete_by_post_count( $delete_options, $user ) {
		if ( $delete_options['no_posts'] && count_user_posts( $user->ID ) > 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Can the user be deleted based on the 'registered date' option?
	 *
	 * @since  5.5.3
	 * @access protected
	 * @param  array     $delete_options Delete Options.
	 * @param  object    $user           User object that needs to be deleted.
	 * @return bool                      True if the user can be deleted, false otherwise.
	 */
	protected function can_delete_by_registered_date( $delete_options, $user ) {
		if ( $delete_options['registered_restrict'] ) {
			$registered_days = $delete_options['registered_days'];

			if ( $registered_days > 0 ) {
				$user_meta = get_userdata( $user->ID );
				if ( strtotime( $user_meta->user_registered ) > strtotime( '-' . $registered_days . 'days' ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Can the user be deleted based on the 'logged in date' option?
	 *
	 * @since  5.5.2
	 * @access protected
	 * @param  array     $delete_options Delete Options.
	 * @param  object    $user           User object that needs to be deleted.
	 * @return bool                      True if the user can be deleted, false otherwise.
	 */
	protected function can_delete_by_logged_date( $delete_options, $user ) {
		if ( $delete_options['login_restrict'] ) {
			$login_days = $delete_options['login_days'];
			$last_login = bd_get_last_login( $user->ID );

			if ( null !== $last_login ) {
				// we have a logged-in entry for the user in simple login log plugin.
				if ( strtotime( $last_login ) > strtotime( '-' . $login_days . 'days' ) ) {
					return false;
				}
			} else {
				// we don't have a logged-in entry for the user in simple login log plugin.
				if ( $login_days > 0 ) {
					// non-zero value for login date. So don't delete this user.
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Process user delete form.
	 * Helper function to handle common delete user fields.
	 *
	 * @since 5.5.3
	 * @access protected
	 * @param  array     $delete_options Delete Options.
	 */
	protected function process_user_delete( $delete_options ) {
		$delete_options['login_restrict']      = array_get_bool( $_POST, "smbd_{$this->field_slug}_login_restrict", false );
		$delete_options['login_days']          = absint( array_get( $_POST, "smbd_{$this->field_slug}_login_days", 0 ) );

		$delete_options['registered_restrict'] = array_get_bool( $_POST, "smbd_{$this->field_slug}_registered_restrict", false );
		$delete_options['registered_days']     = absint( array_get( $_POST, "smbd_{$this->field_slug}_registered_days", 0 ) );

		$delete_options['no_posts']            = array_get_bool( $_POST, "smbd_{$this->field_slug}_no_posts", false );
		$delete_options['limit_to']            = absint( array_get( $_POST, "smbd_{$this->field_slug}_limit_to", 0 ) );

		$this->process_delete( $delete_options );
	}

	/**
	 * Render User Login restrict settings.
	 *
	 * @since 5.5
	 */
	protected function render_user_login_restrict_settings() {
?>
		<tr>
			<td scope="row" colspan="2">
			<input name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_registered_restrict" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_registered_restrict" value="true" type="checkbox">
				<?php _e( 'Restrict to users who have registered at least ', 'bulk-delete' );?>
				<input type="number" name="smbd_<?php echo esc_attr( $this->field_slug ); ?>_registered_days" id="smbd_<?php echo esc_attr( $this->field_slug ); ?>_registered_days" class="screen-per-page" value="0" min="0" disabled> <?php _e( 'days ago', 'bulk-delete' );?>.
			</td>
		</tr>

		<?php
		if ( bd_is_simple_login_log_present() ) {
			$disabled = '';
		} else {
			$disabled = 'disabled';
		}
?>
		<tr>
			<td scope="row" colspan="2">
			<input name="smbd_<?php echo $this->field_slug; ?>_login_restrict" id="smbd_<?php echo $this->field_slug; ?>_login_restrict" value="true" type="checkbox" <?php echo $disabled; ?>>
				<?php _e( 'Restrict to users who have not logged in the last ', 'bulk-delete' );?>
				<input type="number" name="smbd_<?php echo $this->field_slug; ?>_login_days" id="smbd_<?php echo $this->field_slug; ?>_login_days" class="screen-per-page" value="0" min="0" disabled> <?php _e( 'days', 'bulk-delete' );?>.
		<?php if ( 'disabled' == $disabled ) { ?>
				<span style = "color:red">
					<?php _e( 'Need the free "Simple Login Log" Plugin', 'bulk-delete' ); ?> <a href = "http://wordpress.org/plugins/simple-login-log/">Install now</a>
				</span>
		<?php } ?>
			</td>
		</tr>

		<tr>
			<td scope="row" colspan="2">
				<?php _e( 'Enter "0 days" to delete users who have never logged in after the "Simple Login Log" plugin has been installed.', 'bulk-delete' ); ?>
		</tr>
<?php
	}

	/**
	 * Render delete user with no posts settings.
	 *
	 * @since 5.5
	 */
	protected function render_user_with_no_posts_settings() {
?>
		<tr>
			<td scope="row" colspan="2">
				<input name="smbd_<?php echo $this->field_slug; ?>_no_posts" id="smbd_<?php echo $this->field_slug; ?>_no_posts" value="true" type="checkbox">
				<?php _e( "Only if user doesn't have any post. Only posts from 'post' post type would be considered.", 'bulk-delete' ); ?>
			</td>
		</tr>
<?php
	}
}
