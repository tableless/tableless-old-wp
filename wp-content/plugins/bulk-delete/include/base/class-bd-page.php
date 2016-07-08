<?php
/**
 * Base class for all Metabox Pages.
 *
 * @since   5.5
 * @author  Sudar
 * @package BulkDelete\Base\Page
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Base class for Metabox Pages.
 *
 * @abstract
 * @since 5.5
 */
abstract class BD_Page extends BD_Base_Page {
	/**
	 * @var string Item Type. Possible values 'posts', 'pages', 'users' etc.
	 */
	protected $item_type;

	/**
	 * Setup hooks.
	 *
	 * @since 5.5
	 */
	protected function setup_hooks() {
		parent::setup_hooks();

		add_action( "bd_admin_footer_for_{$this->item_type}", array( $this, 'modify_admin_footer' ) );
	}

	/**
	 * Check for nonce before executing the action.
	 *
	 * @since 5.5
	 * @param bool   $result The current result.
	 * @param string $action Action name.
	 */
	public function nonce_check( $result, $action ) {
		$action_prefix = "delete_{$this->item_type}_";

		if ( $action_prefix === substr( $action, 0, strlen( $action_prefix ) )
			&& check_admin_referer( "bd-{$this->page_slug}", "bd-{$this->page_slug}-nonce" ) ) {
			return true;
		} else {
			return $result;
		}
	}

	/**
	 * Add menu.
	 *
	 * @since 5.5
	 */
	public function add_menu() {
		parent::add_menu();

		$bd = BULK_DELETE();

		add_action( "admin_print_scripts-{$this->screen}", array( $bd, 'add_script' ) );

		add_action( "load-{$this->screen}", array( $this, 'add_settings_panel' ) );
		add_action( "add_meta_boxes_{$this->screen}", array( $this, 'add_meta_boxes' ) );
	}

	/**
	 * Add settings Panel.
	 *
	 * @since 5.5
	 */
	public function add_settings_panel() {
		/**
		 * Add contextual help for admin screens.
		 *
		 * @since 5.1
		 */
		do_action( 'bd_add_contextual_help', $this->screen );

		// Trigger the add_meta_boxes hooks to allow meta boxes to be added
		do_action( 'add_meta_boxes_' . $this->screen, null );

		// Enqueue WordPress' script for handling the meta boxes
		wp_enqueue_script( 'postbox' );
	}

	/**
	 * Register meta boxes.
	 *
	 * @since 5.5
	 */
	public function add_meta_boxes() {
		/**
		 * Add meta box in delete users page.
		 * This hook can be used for adding additional meta boxes in delete users page
		 *
		 * @since 5.3
		 */
		do_action( "bd_add_meta_box_for_{$this->item_type}", $this->screen, $this->page_slug  );
	}

	/**
	 * Add additional nonce fields.
	 *
	 * @since 5.5.4
	 */
	protected function render_nonce_fields() {
		parent::render_nonce_fields();

		// Used to save closed meta boxes and their order
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
	}

	/**
	 * Render meta boxes in body.
	 *
	 * @since 5.5.4
	 */
	protected function render_body() {
		do_meta_boxes( '', 'advanced', null );
	}

	/**
	 * Render footer.
	 *
	 * @since 5.5.4
	 */
	protected function render_footer() {
		parent::render_footer();

		/**
		 * Runs just before displaying the footer text in the admin page.
		 *
		 * This action is primarily for adding extra content in the footer of admin page.
		 *
		 * @since 5.5.4
		 */
		do_action( "bd_admin_footer_for_{$this->item_type}" );
	}
}
?>
