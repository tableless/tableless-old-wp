<?php
/**
 * Addon license related functions
 *
 * @since      5.0
 * @author     Sudar
 * @package    BulkDelete\License
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class BD_License {

	/**
	 * Output addon page content
	 *
	 * @since 5.0
	 * @static
	 */
	public static function display_addon_page() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . WPINC . '/class-wp-list-table.php';
		}

		if ( ! class_exists( 'License_List_Table' ) ) {
			require_once Bulk_Delete::$PLUGIN_DIR . '/include/license/class-license-list-table.php';
		}

		$license_list_table = new License_List_Table();
		$license_list_table->prepare_items();
?>
        <div class="wrap">
            <h2><?php _e( 'Addon Licenses', 'bulk-delete' );?></h2>
            <?php settings_errors(); ?>
            <form method="post" action="options.php">
<?php
		$license_list_table->display();
		do_action( 'bd_license_form' );
		bd_display_available_addon_list();
?>
            </form>
        </div>
<?php
		/**
		 * Runs just before displaying the footer text in the "Addon" admin page.
		 *
		 * This action is primarily for adding extra content in the footer of "Addon" admin page.
		 *
		 * @since 5.0
		 */
		do_action( 'bd_admin_footer_addon_page' );
	}

	/**
	 * Display License form
	 *
	 * @since 5.0
	 * @static
	 */
	public static function display_activate_license_form() {
		$bd = BULK_DELETE();
		if ( isset( $bd->display_activate_license_form ) && true === $bd->display_activate_license_form ) {
			// This prints out all hidden setting fields
			settings_fields( Bulk_Delete::SETTING_OPTION_GROUP );
			do_settings_sections( Bulk_Delete::ADDON_PAGE_SLUG );
			submit_button( __( 'Activate License', 'bulk-delete' ) );
		}
	}

	/**
	 * Check if an addon has a valid license or not
	 *
	 * @since  5.0
	 * @static
	 * @param string  $addon_name Addon Name
	 * @param string  $addon_code Addon short Name
	 * @return bool   True if addon has a valid license, False otherwise
	 */
	public static function has_valid_license( $addon_name, $addon_code ) {
		$key = Bulk_Delete::LICENSE_CACHE_KEY_PREFIX . $addon_code;
		$license_data = get_option( $key, false );

		if ( ! $license_data ) {
			// if data about license is not present, then fetch it.
			// ideally this should not happen
			$licenses = get_option( Bulk_Delete::SETTING_OPTION_NAME );
			if ( is_array( $licenses ) && key_exists( $addon_code, $licenses ) ) {
				$license_data = BD_EDD_API_Wrapper::check_license( $addon_name, $licenses[ $addon_code ] );
				update_option( $key, $license_data );
			}
		}

		// TODO Encapsulate below code into a separate function
		if ( $license_data && is_array( $license_data ) && key_exists( 'validity', $license_data ) ) {
			if ( 'valid' == $license_data['validity'] ) {
				if ( strtotime( 'now' ) < strtotime( $license_data['expires'] ) ) {
					return true;
				} else {
					$license_data['validity'] = 'expired';
					update_option( $key, $license_data );
				}
			}
		}

		return false;
	}

	/**
	 * Get the list of all licenses information to be displayed in the license page
	 *
	 * @since 5.0
	 * @static
	 * @return array $license_data License information
	 */
	public static function get_licenses() {
		$licenses = get_option( Bulk_Delete::SETTING_OPTION_NAME );
		$license_data = array();

		if ( is_array( $licenses ) ) {
			foreach ( $licenses as $addon_code => $license ) {
				$license_data[ $addon_code ] = self::get_license( $addon_code );
			}
		}

		return $license_data;
	}

	/**
	 * Retrieve license information about an addon
	 *
	 * @since  5.0
	 * @static
	 * @param string  $addon_code Addon short name
	 * @return object $license_data License information
	 */
	public static function get_license( $addon_code ) {
		$key = Bulk_Delete::LICENSE_CACHE_KEY_PREFIX . $addon_code;
		$license_data = get_option( $key, false );

		if ( $license_data && is_array( $license_data ) && key_exists( 'validity', $license_data ) ) {
			if ( 'valid' == $license_data['validity'] ) {
				if ( strtotime( 'now' ) < strtotime( $license_data['expires'] ) ) {
					// valid license
				} else {
					$license_data['validity'] = 'expired';
					update_option( $key, $license_data );
				}
			}
		}

		return $license_data;
	}

	/**
	 * Get license code of an addon
	 *
	 * @since 5.0
	 * @static
	 * @param string  $addon_code Addon code
	 * @return bool|string License code of the addon, False otherwise
	 */
	public static function get_license_code( $addon_code ) {
		$licenses = get_option( Bulk_Delete::SETTING_OPTION_NAME );

		if ( is_array( $licenses ) && key_exists( $addon_code, $licenses ) ) {
			return $licenses[ $addon_code ];
		}
		else {
			return false;
		}
	}

	/**
	 * Deactivate license
	 *
	 * @since 5.0
	 * @static
	 */
	public static function deactivate_license() {
		$msg          = array( 'msg' => '', 'type' => 'error' );
		$addon_code   = $_GET['addon-code'];
		$license_data = self::get_license( $addon_code );

		$license      = $license_data['license'];
		$addon_name   = $license_data['addon-name'];

		$deactivated  = BD_EDD_API_Wrapper::deactivate_license( $addon_name, $license );

		if ( $deactivated ) {
			self::delete_license_from_cache( $addon_code );
			$msg['msg']  = sprintf( __( 'The license key for "%s" addon was successfully deactivated', 'bulk-delete' ), $addon_name );
			$msg['type'] = 'updated';

		} else {
			self::validate_license( $addon_code, $addon_name );
			$msg['msg'] = sprintf( __( 'There was some problem while trying to deactivate license key for "%s" addon. Kindly try again', 'bulk-delete' ), $addon_name );
		}

		add_settings_error(
			Bulk_Delete::ADDON_PAGE_SLUG,
			'license-deactivation',
			$msg['msg'],
			$msg['type']
		);
	}

	/**
	 * Delete license
	 *
	 * @since 5.0
	 * @static
	 */
	public static function delete_license() {
		$msg          = array( 'msg' => '', 'type' => 'updated' );
		$addon_code   = $_GET['addon-code'];

		self::delete_license_from_cache( $addon_code );

		$msg['msg']  = __( 'The license key was successfully deleted', 'bulk-delete' );

		add_settings_error(
			Bulk_Delete::ADDON_PAGE_SLUG,
			'license-deleted',
			$msg['msg'],
			$msg['type']
		);
	}

	/**
	 * Delete license information from cache
	 *
	 * @since 5.0
	 * @static
	 * @param string  $addon_code Addon code
	 */
	private static function delete_license_from_cache( $addon_code ) {
		$key = Bulk_Delete::LICENSE_CACHE_KEY_PREFIX . $addon_code;
		delete_option( $key );

		$licenses = get_option( Bulk_Delete::SETTING_OPTION_NAME );

		if ( is_array( $licenses ) && key_exists( $addon_code, $licenses ) ) {
			unset( $licenses[ $addon_code ] );
		}
		update_option( Bulk_Delete::SETTING_OPTION_NAME, $licenses );
	}

	/**
     * Activate license
     *
     * @since  5.0
     * @static
     * @param  string $addon_name Addon name
     * @param  string $addon_code Addon code
     * @param  string $license    License code
     * @return bool   $valid      True if valid, False otherwise
     */
	public static function activate_license( $addon_name, $addon_code, $license ) {
		$license_data = BD_EDD_API_Wrapper::activate_license( $addon_name, $license );
		$valid        = false;
		$msg          = array(
			'msg'  => sprintf( __( 'There was some problem in contacting our store to activate the license key for "%s" addon', 'bulk-delete' ), $addon_name ),
			'type' => 'error',
		);

		if ( $license_data && is_array( $license_data ) && key_exists( 'validity', $license_data ) ) {
			if ( 'valid' == $license_data['validity'] ) {
				$key = Bulk_Delete::LICENSE_CACHE_KEY_PREFIX . $addon_code;
				$license_data['addon-code'] = $addon_code;
				update_option( $key, $license_data );

				$msg['msg']  = sprintf( __( 'The license key for "%s" addon was successfully activated. The addon will get updates automatically till the license key is valid.', 'bulk-delete' ), $addon_name );
				$msg['type'] = 'updated';
				$valid = true;
			} else {
				if ( key_exists( 'error', $license_data ) ) {
					switch ( $license_data['error'] ) {

						case 'no_activations_left':
							$msg['msg'] = sprintf( __( 'The license key for "%s" addon doesn\'t have any more activations left. Kindly buy a new license.', 'bulk-delete' ), $addon_name );
							break;

						case 'revoked':
							$msg['msg'] = sprintf( __( 'The license key for "%s" addon is revoked. Kindly buy a new license.', 'bulk-delete' ), $addon_name );
							break;

						case 'expired':
							$msg['msg'] = sprintf( __( 'The license key for "%s" addon has expired. Kindly buy a new license.', 'bulk-delete' ), $addon_name );
							break;

						default:
							$msg['msg'] = sprintf( __( 'The license key for "%s" addon is invalid', 'bulk-delete' ), $addon_name );
							break;
					}
				}
			}
		}

		add_settings_error(
			Bulk_Delete::ADDON_PAGE_SLUG,
			'license-activation',
			$msg['msg'],
			$msg['type']
		);

		if ( ! $valid && isset( $key ) ) {
			delete_option( $key );
		}
		return $valid;
	}

	/**
	 * Validate the license for the given addon
	 *
	 * @since 5.0
	 * @static
	 * @param string  $addon_code Addon code
	 * @param string  $addon_name Addon name
	 */
	public static function validate_license( $addon_code, $addon_name ) {
		$key = Bulk_Delete::LICENSE_CACHE_KEY_PREFIX . $addon_code;

		$licenses = get_option( Bulk_Delete::SETTING_OPTION_NAME );
		if ( is_array( $licenses ) && key_exists( $addon_code, $licenses ) ) {
			$license_data = BD_EDD_API_Wrapper::check_license( $addon_name, $licenses[ $addon_code ] );
			if ( $license_data ) {
				$license_data['addon-code'] = $addon_code;
				$license_data['addon-name'] = $license_data['item_name'];
				update_option( $key, $license_data );
			} else {
				delete_option( $key );
			}
		}

		if ( $license_data && is_array( $license_data ) && key_exists( 'validity', $license_data ) ) {
			if ( 'valid' == $license_data['validity'] ) {
				if ( strtotime( 'now' ) > strtotime( $license_data['expires'] ) ) {
					$license_data['validity'] = 'expired';
					update_option( $key, $license_data );
				}
			}
		}
	}
}

// hooks
add_action( 'bd_license_form'      , array( 'BD_License', 'display_activate_license_form' ), 100 );
add_action( 'bd_deactivate_license', array( 'BD_License', 'deactivate_license' ) );
add_action( 'bd_delete_license'    , array( 'BD_License', 'delete_license' ) );
add_action( 'bd_validate_license'  , array( 'BD_License', 'validate_license' ), 10, 2 );
?>
