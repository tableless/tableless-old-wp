<?php
/**
 * Encapsulates the settings API for Bulk Delete Plugin
 *
 * @since      5.0
 * @author     Sudar
 * @package    BulkDelete\Settings
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class BD_Settings {

	/**
	 * Register settings used by the plugin
	 *
	 * @since 5.0
	 * @static
	 */
	public static function create_settings() {
		register_setting(
			Bulk_Delete::SETTING_OPTION_GROUP,        // Option group
			Bulk_Delete::SETTING_OPTION_NAME,         // Option name
			array( 'BD_Settings', 'check_license' )   // Sanitize
		);

		add_settings_section(
			Bulk_Delete::SETTING_SECTION_ID,          // ID
			__( 'Add Addon License', 'bulk-delete' ), // Title
			'__return_null',                          // Callback
			Bulk_Delete::ADDON_PAGE_SLUG              // Page
		);

		/**
		 * Runs just after registering license form fields
		 *
		 * This action is primarily for adding more fields to the license form
		 *
		 * @since 5.0
		 */
		do_action( 'bd_license_field' );
	}

	/**
	 * Callback for sanitizing settings
	 *
	 * @since 5.0
	 * @static
	 * @param array $input
	 * @return array
	 */
	public static function check_license( $input ) {
		/**
		 * Filter license form inputs
		 *
		 * @since 5.0
		 */
		return apply_filters( 'bd_license_input', $input );
	}
}

// hooks
add_action( 'admin_init', array( 'BD_Settings', 'create_settings' ), 100 );
?>
