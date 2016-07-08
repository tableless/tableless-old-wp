<?php
/**
 * Base class for all Scheduler Addons.
 *
 * @since   5.5
 * @author  Sudar
 * @package BulkDelete\Addons\Base
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Base class for Base Addons.
 *
 * @abstract
 * @since 5.5
 */
abstract class BD_Scheduler_Addon extends BD_Addon {

	/**
	 * @var No base addon for this scheduler addon.
	 */
	protected $no_base_addon = false;

	/**
	 * @var Base addon name.
	 */
	protected $base_addon;

	/**
	 * @var Base addon version.
	 */
	protected $base_addon_version;

	/**
	 * @var Base addon class name.
	 */
	protected $base_addon_class_name;

	/**
	 * @var Base addon object.
	 */
	protected $base_addon_obj;

	/**
	 * @var Cron Hook to run the scheduler on.
	 */
	protected $cron_hook;

	/**
	 * Use `factory()` method to create instance of this class.
	 * Don't create instances directly
	 *
	 * @since 5.5
	 *
	 * @see factory()
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Check if the base addon is available.
	 *
	 * @access protected
	 * @since 5.5
	 *
	 * @todo check for version as well
	 *
	 * @param string Base Addon class name. Default null. If not specified then it is auto calculated based on addon name.
	 * @return bool True if base addon is found, False other wise
	 */
	protected function check_base_addon( $addon_class_name = null ) {
		if ( null == $addon_class_name ) {
			$this->base_addon_class_name = bd_get_addon_class_name( $this->base_addon );
		} else {
			$this->base_addon_class_name = $addon_class_name;
		}

		if ( class_exists( $this->base_addon_class_name ) ) {
			// Ugly workaround, since we need to support PHP 5.2
			$this->base_addon_obj = call_user_func( array( $this->base_addon_class_name, 'factory' ) );
			return true;
		} else {
			add_action( 'admin_notices', array( $this, 'addon_missing_notice' ) );
			return false;
		}
	}

	/**
	 * Show a notice if the base addon is not available.
	 *
	 * @since 5.5
	 */
	public function addon_missing_notice() {
		$campaign_args = array(
			'utm_source'   => 'wpadmin',
			'utm_campaign' => 'BulkDelete',
			'utm_medium'   => 'header-notice',
			'utm_content'  => $this->addon_code,
		);
		$addon_url = bd_get_addon_url( $this->base_addon, $campaign_args );

		printf(
			'<div class="error"><p>%s</p></div>',
			sprintf( __( '"%s" addon requires "<a href="%s" target="_blank">%s</a>" addon to be installed and activated!', 'bulk-delete' ), $this->addon_name, $addon_url , $this->base_addon )
		);
	}

	/**
	 * Setup hooks.
	 *
	 * @since 5.5
	 */
	protected function setup_hooks() {
		add_filter( 'bd_javascript_array', array( $this, 'filter_js_array' ) );

		$cron_hook = $this->get_cron_hook();
		if ( ! empty( $cron_hook ) ) {
			add_action( $cron_hook, array( $this, 'do_delete' ), 10, 1 );
		}
	}

	/**
	 * Filter JS Array and add pro hooks.
	 *
	 * @since 5.5
	 * @param array  $js_array JavaScript Array
	 * @return array           Modified JavaScript Array
	 */
	public function filter_js_array( $js_array ) {
		$js_array['pro_iterators'][] = $this->get_module()->get_field_slug();

		return $js_array;
	}

	/**
	 * Hook handler.
	 *
	 * @since 5.5
	 * @param array $delete_options
	 */
	public function do_delete( $delete_options ) {
		do_action( 'bd_before_scheduler', $this->addon_name );
		$count = $this->get_module()->delete( $delete_options );
		do_action( 'bd_after_scheduler', $this->addon_name, $count );
	}

	/**
	 * Get the cron hook.
	 *
	 * @access protected
	 * @since 5.5
	 * @return string Cron hook.
	 */
	protected function get_cron_hook() {
		$cron_hook = '';
		if ( null != $this->base_addon_obj ) {
			$cron_hook = $this->base_addon_obj->get_cron_hook();
		}

		return $cron_hook;
	}

	/**
	 * Get base module.
	 *
	 * @access protected
	 * @since 5.5
	 * @return object Base module object
	 */
	protected function get_module() {
		if ( $this->no_base_addon ) {
			return $this->base_addon_obj;
		} else {
			return $this->base_addon_obj->get_module();
		}
	}
}
?>
