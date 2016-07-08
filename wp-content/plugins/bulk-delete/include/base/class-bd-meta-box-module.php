<?php
/**
 * Base class for a Bulk Delete Meta Box Module.
 *
 * @since 5.5
 * @author Sudar
 * @package BulkDelete\Base
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Encapsulates the Bulk Delete Meta box Module Logic.
 * All Bulk Delete Meta box Modules should extend this class.
 *
 * @since 5.5
 * @abstract
 */
abstract class BD_Meta_Box_Module {
	/**
	 * @var string Item Type. Possible values 'posts', 'pages', 'users' etc.
	 */
	protected $item_type = 'posts';

	/**
	 * @var string The screen where this meta box would be shown.
	 */
	protected $screen;

	/**
	 * @var string Slug of the page where this module will be shown.
	 */
	protected $page_slug;

	/**
	 * @var string Slug for the form fields.
	 */
	protected $field_slug;

	/**
	 * @var string Slug of the meta box.
	 */
	protected $meta_box_slug;

	/**
	 * @var string Hook in which this meta box should be shown.
	 */
	protected $meta_box_hook;

	/**
	 * @var string Action in which the delete operation should be performed.
	 */
	protected $delete_action;

	/**
	 * @var string Hook for scheduler.
	 */
	protected $cron_hook;

	/**
	 * @var string Url of the scheduler addon.
	 */
	protected $scheduler_url;

	/**
	 * @var array Messages shown to the user.
	 */
	protected $messages = array();

	/**
	 * Initialize and setup variables.
	 *
	 * @since 5.5
	 * @abstract
	 * @return void
	 */
	abstract protected function initialize();

	/**
	 * Initialize and setup variables.
	 *
	 * @since 5.5
	 * @abstract
	 * @return void
	 */
	abstract public function render();

	/**
	 * Process the deletion.
	 *
	 * @since 5.5
	 * @abstract
	 * @return void
	 */
	abstract public function process();

	/**
	 * Perform the deletion
	 *
	 * @since 5.5
	 * @abstract
	 * @return int  Number of users deleted
	 */
	abstract public function delete( $delete_options );

	/**
	 * Use `factory()` method to create instance of this class.
	 * Don't create instances directly
	 *
	 * @since 5.5
	 *
	 * @see factory()
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Setup the module.
	 *
	 * @since 5.5
	 */
	protected function setup() {
		$this->initialize();
		$this->setup_hooks();
	}

	/**
	 * Setup hooks.
	 *
	 * @since 5.5
	 */
	protected function setup_hooks() {
		add_action( $this->meta_box_hook, array( $this, 'setup_metabox' ), 10, 2 );
		add_action( 'bd_' . $this->delete_action, array( $this, 'process' ) );
		add_filter( 'bd_javascript_array', array( $this, 'filter_js_array' ) );
	}

	/**
	 * Setup the meta box.
	 *
	 * @since 5.5
	 */
	public function setup_metabox( $screen, $page_slug ) {
		$this->screen = $screen;
		$this->page_slug = $page_slug;

		add_meta_box( $this->meta_box_slug, $this->messages['box_label'], array( $this, 'render_box' ), $this->screen, 'advanced' );
	}

	/**
	 * Render the meta box.
	 *
	 * @since 5.5
	 */
	public function render_box() {
		if ( $this->is_hidden() ) {
			printf( __( 'This section just got enabled. Kindly <a href = "%1$s">refresh</a> the page to fully enable it.', 'bulk-delete' ), 'admin.php?page=' . $this->page_slug );
			return;
		}

		$this->render();
	}

	/**
	 * Is the current meta box hidden by user.
	 *
	 * @since 5.5
	 * @return bool True, if hidden. False, otherwise.
	 */
	protected function is_hidden() {
		$current_user = wp_get_current_user();
		$user_meta_field = $this->get_hidden_box_user_meta_field();
		$hidden_boxes = get_user_meta( $current_user->ID, $user_meta_field, true );

		return is_array( $hidden_boxes ) && in_array( $this->meta_box_slug, $hidden_boxes );
	}

	/**
	 * Get the user meta field that stores the status of the hidden meta boxes.
	 *
	 * @since 5.5
	 * @return string Name of the User Meta field.
	 */
	protected function get_hidden_box_user_meta_field() {
		if ( 'posts' == $this->item_type ) {
			return 'metaboxhidden_toplevel_page_bulk-delete-posts';
		} else {
			return 'metaboxhidden_bulk-wp_page_' . $this->page_slug;
		}
	}

	/**
	 * Filter the js array.
	 * This function will be overridden by the child classes.
	 *
	 * @since 5.5
	 * @param array  $js_array JavaScript Array
	 * @return array           Modified JavaScript Array
	 */
	public function filter_js_array( $js_array) {
		return $js_array;
	}

	/**
	 * Render filtering table header.
	 *
	 * @since 5.5
	 */
	protected function render_filtering_table_header() {
		bd_render_filtering_table_header();
	}

	/**
	 * Render restrict settings.
	 *
	 * @since 5.5
	 */
	protected function render_restrict_settings() {
		bd_render_restrict_settings( $this->field_slug, $this->item_type );
	}

	/**
	 * Render delete settings.
	 *
	 * @since 5.5
	 */
	protected function render_delete_settings() {
		bd_render_delete_settings( $this->field_slug );
	}

	/**
	 * Render limit settings.
	 *
	 * @since 5.5
	 */
	protected function render_limit_settings() {
		bd_render_limit_settings( $this->field_slug, $this->item_type );
	}

	/**
	 * Render cron settings.
	 *
	 * @since 5.5
	 */
	protected function render_cron_settings() {
		bd_render_cron_settings( $this->field_slug, $this->scheduler_url );
	}

	/**
	 * Render submit button.
	 *
	 * @since 5.5
	 */
	protected function render_submit_button() {
		bd_render_submit_button( $this->delete_action );
	}

	/**
	 * Helper function for processing deletion.
	 * Setups up cron and invokes the actual delete method.
	 *
	 * @since 5.5
	 */
	protected function process_delete( $delete_options ) {
		if ( array_get_bool( $_POST, 'smbd_' . $this->field_slug . '_cron', false ) ) {
			$freq = $_POST[ 'smbd_' . $this->field_slug . '_cron_freq' ];
			$time = strtotime( $_POST[ 'smbd_' . $this->field_slug . '_cron_start' ] ) - ( get_option( 'gmt_offset' ) * 60 * 60 );

			if ( -1 == $freq ) {
				wp_schedule_single_event( $time, $this->cron_hook, array( $delete_options ) );
			} else {
				wp_schedule_event( $time, $freq , $this->cron_hook, array( $delete_options ) );
			}

			$msg = $this->messages['scheduled'] . ' ' .
				sprintf( __( 'See the full list of <a href = "%s">scheduled tasks</a>' , 'bulk-delete' ), get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=' . Bulk_Delete::CRON_PAGE_SLUG );
		} else {
			$deleted_count = $this->delete( $delete_options );
			$msg = sprintf( _n( $this->messages['deleted_single'], $this->messages['deleted_plural'] , $deleted_count, 'bulk-delete' ), $deleted_count );
		}

		add_settings_error(
			$this->page_slug,
			'deleted-' . $this->item_type,
			$msg,
			'updated'
		);
	}

	/**
	 * Getter for cron_hook.
	 *
	 * @since 5.5
	 * @return string Cron Hook name.
	 */
	public function get_cron_hook() {
	    return $this->cron_hook;
	}

	/**
	 * Getter for field slug.
	 *
	 * @since 5.5
	 * @return string Field Slug.
	 */
	public function get_field_slug() {
	    return $this->field_slug;
	}
}
?>
