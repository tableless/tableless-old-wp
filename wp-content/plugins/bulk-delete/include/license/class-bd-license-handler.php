<?php
/**
 * License Handler for Bulk Delete Addons
 *
 * @since      5.0
 * @author     Sudar
 * @package    BulkDelete/License
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class BD_License_Handler {

	/**
	 * Name of the addon
	 *
	 * @since 5.0
	 */
	private $addon_name;

	/**
	 * Code of the addon
	 *
	 * @since 5.0
	 */
	private $addon_code;

	/**
	 * Version of the plugin
	 *
	 * @since 5.0
	 */
	private $version;

	/**
	 * plugin file name
	 *
	 * @since 5.0
	 */
	private $plugin_file;

	/**
	 * Plugin base name
	 *
	 * @since 5.5
	 */
	private $plugin_basename;

	/**
	 * Author of the plugin
	 *
	 * @since 5.0
	 */
	private $author;

	/**
	 * Instance of the updater class.
	 *
	 * @since 5.5
	 */
	private $updater;

	/**
	 * Notice Message.
	 *
	 * @since 5.5
	 */
	private $notice_msg = '';

	/**
	 * Constructor
	 *
	 * @since 5.0
	 *
	 * @param string  $addon_name  Name of the addon
	 * @param string  $addon_code  Code of the addon
	 * @param string  $version     Version of the addon
	 * @param string  $plugin_file Addon file name
	 * @param string  $author      (optional) Author of the addon
	 */
	public function __construct( $addon_name, $addon_code, $version, $plugin_file, $author = 'Sudar Muthu' ) {
		$this->addon_name      = $addon_name;
		$this->addon_code      = $addon_code;
		$this->version         = $version;
		$this->plugin_file     = $plugin_file;
		$this->plugin_basename = plugin_basename( $plugin_file );
		$this->author          = $author;

		$this->hooks();
	}

	/**
	 * setup hooks
	 *
	 * @access private
	 * @since 5.0
	 */
	private function hooks() {
		add_action( 'admin_init', array( $this, 'check_license' ), 0 );
		add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
		add_action( 'after_plugin_row_' . $this->plugin_basename, array( $this, 'plugin_row' ), 11, 3 );

		add_action( 'bd_license_form' , array( $this, 'display_license_form' ) );
		add_action( 'bd_license_field', array( $this, 'add_license_field' ) );
		add_filter( 'bd_license_input', array( $this, 'parse_license_input' ), 1 );
	}

	/**
	 * Check whether the license is valid for the addon.
	 *
	 * If the license is not valid then add a notice about it.
	 * If it is valid then hook the plugin updater.
	 *
	 * @since 5.5
	 */
	public function check_license() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		$campaign_args = array(
			'utm_source'   => 'wpadmin',
			'utm_campaign' => 'BulkDelete',
			'utm_medium'   => 'header-notice',
			'utm_content'  => strtolower( $this->addon_code ),
		);
		$addon_url = bd_get_addon_url( $this->addon_name, $campaign_args );

		$license_code = BD_License::get_license_code( $this->addon_code );

		if ( false == $license_code ) {
			$this->notice_msg = sprintf( __( '"%1$s" addon is installed but not activated. To activate the addon, please <a href="%2$s">enter your license key</a>. If you don\'t have a license key, then you can <a href="%3$s" target="_blank">purchase one</a>.', 'bulk-delete' ), $this->addon_name, esc_url( get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=' . Bulk_Delete::ADDON_PAGE_SLUG ), esc_url( $addon_url ) );
		} else {
			if ( BD_License::has_valid_license( $this->addon_name, $this->addon_code ) ) {
				if ( false != $license_code ) {
					$this->hook_updater( $license_code );
				}
			} else {
				$this->notice_msg = sprintf( __( 'The license for "%1$s" addon is either invalid or has expired. Please <a href="%2$s" target="_blank">renew the license</a> or <a href="%3$s">enter a new license key</a> to receive updates and support.', 'bulk-delete' ), $this->addon_name, esc_url( $addon_url ), esc_url( get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=' . Bulk_Delete::ADDON_PAGE_SLUG ) );
			}
		}
	}

	/**
	 * Start the updater.
	 *
	 * @since 5.0
	 * @access private
	 * @param string  $license_code License Code
	 */
	private function hook_updater( $license_code ) {
		if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
			require_once Bulk_Delete::$PLUGIN_DIR . '/include/libraries/EDD_SL_Plugin_Updater.php';
		}

		$this->updater = new EDD_SL_Plugin_Updater( BD_EDD_API_Wrapper::STORE_URL, $this->plugin_file, array(
				'version'    => $this->version,
				'license'    => $license_code,
				'item_name'  => $this->addon_name,
				'addon_code' => $this->addon_code,
				'author'     => $this->author,
				'url'        => home_url()
			)
		);
	}

	/**
	 * Display notification at the top of all admin pages.
	 *
	 * @since 5.5
	 */
	public function show_admin_notices() {
		if ( '' != $this->notice_msg ) {
			printf( '<div class="error"><p><strong>%s</strong></p></div>', $this->notice_msg );
		}
	}

	/**
	 * Display license information about addon in plugin list table.
	 *
	 * @since 5.5
	 * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
	 * @param array  $plugin_data An array of plugin data.
	 * @param string $status      Status of the plugin.
	 */
	public function plugin_row( $plugin_file, $plugin_data, $status ) {
		if ( $plugin_file != $this->plugin_basename ) {
			return;
		}

		$campaign_args = array(
			'utm_source'   => 'wpadmin',
			'utm_campaign' => 'BulkDelete',
			'utm_medium'   => 'plugin-page',
			'utm_content'  => strtolower( $this->addon_code ),
		);
		$addon_url = bd_get_addon_url( $this->addon_name, $campaign_args );

		$license_code = BD_License::get_license_code( $this->addon_code );
		if ( false == $license_code ) {
			$plugin_row_msg = sprintf( __( 'Addon is not activated. To activate the addon, please <a href="%1$s">enter your license key</a>. If you don\'t have a license key, then you can <a href="%2$s" target="_blank">purchase one</a>.', 'bulk-delete' ), esc_url( get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=' . Bulk_Delete::ADDON_PAGE_SLUG ), esc_url( $addon_url ) );
?>
			<tr class="plugin-update-tr">
				<td colspan="3" class="plugin-update">
					<div class="update-message"><span class="bd-licence-activate-notice"><?php echo $plugin_row_msg; ?></span></div>
				</td>
			</tr>
<?php
		} else {
			if ( ! BD_License::has_valid_license( $this->addon_name, $this->addon_code ) ) {
				$plugin_row_msg = sprintf( __( 'The license for this addon is either invalid or has expired. Please <a href="%1$s" target="_blank">renew the license</a> or <a href="%2$s">enter a new license key</a> to receive updates and support.', 'bulk-delete' ), esc_url( $addon_url ), esc_url( get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=' . Bulk_Delete::ADDON_PAGE_SLUG ) );
?>
				<tr class="plugin-update-tr">
					<td colspan="3" class="plugin-update">
						<div class="update-message"><span class="bd-licence-activate-notice"><?php echo $plugin_row_msg; ?></span></div>
					</td>
				</tr>
<?php
			}
		}
	}

	/**
	 * Decide whether to display the license form or not
	 *
	 * @since 5.0
	 */
	public function display_license_form() {
		if ( ! BD_License::has_valid_license( $this->addon_name, $this->addon_code ) ) {
			$bd = BULK_DELETE();
			$bd->display_activate_license_form = true;
		}
	}

	/**
	 * Add the license field to license form
	 *
	 * @since 5.0
	 */
	public function add_license_field() {
		if ( ! BD_License::has_valid_license( $this->addon_name, $this->addon_code ) ) {
			add_settings_field(
				$this->addon_code, // ID
				'"' . $this->addon_name . '" ' . __( 'Addon License Key', 'bulk-delete' ), // Title
				array( $this, 'print_license_key_field' ), // Callback
				Bulk_Delete::ADDON_PAGE_SLUG, // Page
				Bulk_Delete::SETTING_SECTION_ID // Section
			);
		}
	}

	/**
	 * Print the license field
	 *
	 * @since 5.0
	 */
	public function print_license_key_field() {
		if ( ! BD_License::has_valid_license( $this->addon_name, $this->addon_code ) ) {
			printf(
				'<input type="text" id="%s" name="%s[%s]" placeholder="%s" size="40">',
				$this->addon_code,
				Bulk_Delete::SETTING_OPTION_NAME,
				$this->addon_code,
				__( 'Enter license key', 'bulk-delete' )
			);
		}
	}

	/**
	 * Parse the license key and activate it if needed.
	 * If the key is invalid, then don't save it in the setting option
	 *
	 * @since 5.0
	 * @param array $input
	 * @return array
	 */
	public function parse_license_input( $input ) {
		if ( is_array( $input ) && key_exists( $this->addon_code, $input ) ) {
			$license_code = trim( $input[ $this->addon_code ] );

			if ( ! empty( $license_code ) ) {
				if ( ! BD_License::has_valid_license( $this->addon_name, $this->addon_code ) ) {
					$activated = BD_License::activate_license( $this->addon_name, $this->addon_code, $license_code );
					if ( ! $activated ) {
						unset( $input[ $this->addon_code ] );
					}
				}
			} else {
				unset( $input[ $this->addon_code ] );
			}
		} else {
			if ( BD_License::has_valid_license( $this->addon_name, $this->addon_code ) ) {
				$license_code = BD_License::get_license_code( $this->addon_code );
				$input[ $this->addon_code ] = $license_code;
			}
		}
		return $input;
	}
}
?>
