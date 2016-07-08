<?php
/**
 * Deprecated Class.
 * It is still hear for compatibility reasons and most probably will be removed in v6.0.
 *
 * @author     Sudar
 * @package    BulkDelete\Deprecated
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Bulk_Delete_Users {

	/**
	 * Wire up proper class for backward compatibility.
	 *
	 * @since 5.5
	 */
	public static function delete_users_by_role( $delete_options ) {
		$factory = Bulk_Delete_Users_By_User_Role::factory();
		return $factory->delete( $delete_options );
	}
}
?>
