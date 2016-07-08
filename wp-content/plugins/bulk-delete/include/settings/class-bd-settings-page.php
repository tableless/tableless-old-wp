<?php
/**
 * Utility class for Settings page
 *
 * @since      5.3
 * @author     Sudar
 * @package    BulkDelete\Admin\Settings
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class BD_Settings_Page {

	/**
	 * Slug for settings page
	 *
	 * @since 5.3
	 */
	const SETTINGS_PAGE_SLUG = 'bd-settings';

	/**
	 * Slugs for addon settings
	 *
	 * @since 5.3
	 */
	const ADDON_SETTING_OPTION_GROUP = 'bd_addon_settings';
	const ADDON_SETTING_OPTION_NAME  = 'bd_addon_settings';

	/**
	 * Add settings menu if needed
	 *
	 * @static
	 * @since  5.3
	 */
	public static function add_menu() {
		$settings_page_needed = apply_filters( 'bd_settings_page_needed', false );
		if ( ! $settings_page_needed ) {
			return;
		}

		$bd = BULK_DELETE();

		// add page
		$bd->settings_page = add_submenu_page(
			Bulk_Delete::POSTS_PAGE_SLUG,
			__( 'Bulk Delete Settings', 'bulk-delete' ),
			__( 'Settings', 'bulk-delete' ),
			'delete_posts',
			self::SETTINGS_PAGE_SLUG,
			array( __CLASS__, 'display_settings_page' )
		);

		// register settings
		register_setting(
			self::ADDON_SETTING_OPTION_GROUP,       // Option group
			self::ADDON_SETTING_OPTION_NAME,        // Option name
			array( __CLASS__, 'sanitize_settings' ) // Sanitize callback
		);
	}

	/**
	 * Sanitize Settings
	 *
	 * @static
	 * @since 5.3
	 * @param array $input (optional) Input array
	 * @return array Sanitized input
	 */
	public static function sanitize_settings( $input = array() ) {
		return apply_filters( 'bd_sanitize_settings_page_fields', $input );
	}

	/**
	 * Return Addon settings
	 *
	 * @since 5.3
	 * @static
	 * @return array Addon settings
	 */
	public static function get_addon_settings() {
		$options = get_option( self::ADDON_SETTING_OPTION_NAME, array() );
		return apply_filters( 'bd_addon_settings', $options );
	}

	/**
	 * Show the settings page
	 *
	 * @static
	 * @since 5.3
	 */
	public static function display_settings_page() {
?>
    <div class="wrap">
        <h2><?php _e( 'Bulk Delete Settings', 'bulk-delete' );?></h2>
        <?php settings_errors(); ?>

        <div id = "poststuff">
            <div id="post-body" class="metabox-holder columns-2">

				<?php bd_render_sidebar_iframe(); ?>

                <div id="postbox-container-2" class="postbox-container">
                    <form method = "post" action="options.php">
                        <table class="form-table">
<?php
		settings_fields( self::ADDON_SETTING_OPTION_GROUP );
		do_settings_sections( self::SETTINGS_PAGE_SLUG );
?>
                        </table>
                        <?php submit_button(); ?>
                    </form>
                </div> <!-- #postbox-container-2 -->

            </div> <!-- #post-body -->
        </div><!-- #poststuff -->
    </div><!-- .wrap -->
<?php
		/**
		 * Runs just before displaying the footer text in the "Bulk Delete Settings" admin page.
		 *
		 * This action is primarily for adding extra content in the footer of "Bulk Delete Settings" admin page.
		 *
		 * @since 5.3
		 */
		do_action( 'bd_admin_footer_settings_page' );
	}
}

// Add menu
add_action( 'bd_before_secondary_menus', array( 'BD_Settings_Page', 'add_menu' ) );

// Modify admin footer
add_action( 'bd_admin_footer_settings_page', 'bd_modify_admin_footer' );
?>
