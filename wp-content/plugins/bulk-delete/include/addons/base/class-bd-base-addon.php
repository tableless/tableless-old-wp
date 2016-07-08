<?php
/**
 * Base class for all Base Addons.
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
abstract class BD_Base_Addon extends BD_Addon {

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
	 * Setup hooks.
	 * This can be overridden by the child class.
	 *
	 * @since 5.5
	 */
	protected function setup_hooks() {
	}

	/**
	 * Getter for cron hook.
	 *
	 * @since 5.5
	 * @return string Cron hook.
	 */
	public function get_cron_hook() {
		return $this->module->get_cron_hook();
	}

	/**
	 * Return reference to the module.
	 *
	 * @since 5.5
	 * @return Module Reference to Module Object
	 */
	public function get_module() {
		return $this->module;
	}
}
?>
