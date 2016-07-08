<?php
/**
 * Base class for all Pages.
 *
 * @since   5.5.4
 * @author  Sudar
 * @package BulkDelete\Base\Page
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Base class for Pages.
 *
 * @abstract
 * @since 5.5.4
 */
abstract class BD_Base_Page {
	/**
	 * @var string Page Slug.
	 */
	protected $page_slug;

	/**
	 * @var string Menu action.
	 */
	protected $menu_action = 'bd_after_primary_menus';

	/**
	 * @var string Minimum capability needed for viewing this page.
	 */
	protected $capability = 'manage_options';

	/**
	 * @var bool Whether sidebar is needed or not.
	 */
	protected $render_sidebar = true;

	/**
	 * @var string The screen variable for this page.
	 */
	protected $screen;

	/**
	 * @var array Labels used in this page.
	 */
	protected $label = array();

	/**
	 * @var array Messages shown to the user.
	 */
	protected $messages = array();

	/**
	 * @var array Actions used in this page.
	 */
	protected $actions = array();

	/**
	 * Initialize and setup variables.
	 *
	 * @since 5.5.4
	 * @abstract
	 * @return void
	 */
	abstract protected function initialize();

	/**
	 * Render body content.
	 *
	 * @since 5.5.4
	 * @abstract
	 * @return void
	 */
	abstract protected function render_body();

	/**
	 * Use `factory()` method to create instance of this class.
	 * Don't create instances directly
	 *
	 * @since 5.5.4
	 *
	 * @see factory()
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Setup the module.
	 *
	 * @since 5.5.4
	 */
	protected function setup() {
		$this->initialize();
		$this->setup_hooks();
	}

	/**
	 * Setup hooks.
	 *
	 * @since 5.5.4
	 */
	protected function setup_hooks() {
		add_action( $this->menu_action, array( $this, 'add_menu' ) );
		add_action( "bd_admin_footer_for_{$this->page_slug}", array( $this, 'modify_admin_footer' ) );

		add_filter( 'bd_action_nonce_check', array( $this, 'nonce_check' ), 10, 2 );
		add_filter( 'bd_admin_help_tabs', array( $this, 'render_help_tab' ), 10, 2 );
	}

	/**
	 * Add menu.
	 *
	 * @since 5.5.4
	 */
	public function add_menu() {
		$this->screen = add_submenu_page(
			Bulk_Delete::POSTS_PAGE_SLUG,
			$this->label['page_title'],
			$this->label['menu_title'],
			$this->capability,
			$this->page_slug,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Check for nonce before executing the action.
	 *
	 * @since 5.5.4
	 * @param bool   $result The current result.
	 * @param string $action Action name.
	 */
	public function nonce_check( $result, $action ) {
		if ( in_array( $action, $this->actions ) ) {
			if ( check_admin_referer( "bd-{$this->page_slug}", "bd-{$this->page_slug}-nonce" ) ) {
				return true;
			}
		}

		return $result;
	}

	/**
	 * Modify help tabs for the current page.
	 *
	 * @since 5.5.4
	 * @param array  $help_tabs Current list of help tabs.
	 * @param string $screen Current screen name.
	 * @return array Modified list of help tabs.
	 */
	public function render_help_tab( $help_tabs, $screen ) {
		if ( $this->screen == $screen ) {
			$help_tabs = $this->add_help_tab( $help_tabs );
		}

		return $help_tabs;
	}

	/**
	 * Add help tabs.
	 * Help tabs can be added by overriding this function in the child class.
	 *
	 * @since 5.5.4
	 * @param array $help_tabs Current list of help tabs.
	 * @return array List of help tabs.
	 */
	protected function add_help_tab( $help_tabs ) {
		return $help_tabs;
	}

	/**
	 * Render the page.
	 *
	 * @since 5.5.4
	 */
	public function render_page() {
?>
		<div class="wrap">
			<h2><?php echo $this->label['page_title'];?></h2>
			<?php settings_errors(); ?>

			<form method = "post">
			<?php $this->render_nonce_fields(); ?>

			<div id = "poststuff">
				<div id="post-body" class="metabox-holder columns-2">

					<?php $this->render_header(); ?>

					<div id="postbox-container-2" class="postbox-container">
						<?php $this->render_body(); ?>
					</div> <!-- #postbox-container-2 -->

				</div> <!-- #post-body -->
			</div><!-- #poststuff -->
			</form>
		</div><!-- .wrap -->
<?php
		$this->render_footer();
	}

	/**
	 * Print nonce fields.
	 *
	 * @since 5.5.4
	 */
	protected function render_nonce_fields() {
		wp_nonce_field( "bd-{$this->page_slug}", "bd-{$this->page_slug}-nonce" );
	}

	/**
	 * Render header for the page.
	 *
	 * If sidebar is enabled, then it is rendered as well.
	 *
	 * @since 5.5.4
	 */
	protected function render_header() {
?>
		<div class="notice notice-warning">
			<p><strong><?php echo $this->messages['warning_message']; ?></strong></p>
		</div>
<?php
		if ( $this->render_sidebar ) {
			bd_render_sidebar_iframe();
		}
	}

	/**
	 * Render footer.
	 *
	 * @since 5.5.4
	 */
	protected function render_footer() {
		/**
		 * Runs just before displaying the footer text in the admin page.
		 *
		 * This action is primarily for adding extra content in the footer of admin page.
		 *
		 * @since 5.5.4
		 */
		do_action( "bd_admin_footer_for_{$this->page_slug}" );
	}

	/**
	 * Modify admin footer in Bulk Delete plugin pages.
	 */
	public function modify_admin_footer() {
		add_filter( 'admin_footer_text', 'bd_add_rating_link' );
	}

	/**
	 * Getter for screen.
	 *
	 * @return string Current value of screen
	 */
	public function get_screen() {
		return $this->screen;
	}

	/**
	 * Getter for page_slug.
	 *
	 * @return string Current value of page_slug
	 */
	public function get_page_slug() {
		return $this->page_slug;
	}
}
?>
