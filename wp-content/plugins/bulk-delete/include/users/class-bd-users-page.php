<?php
/**
 * Bulk Delete Users Page.
 * Shows the list of modules that allows you to delete users.
 *
 * @since   5.5
 * @author  Sudar
 * @package BulkDelete\Users
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Delete Users Page.
 *
 * @since 5.5
 */
class BD_Users_Page extends BD_Page  {
	/**
	 * Make this class a "hybrid Singleton".
	 *
	 * @static
	 * @since 5.5
	 */
	public static function factory() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * Initialize and setup variables.
	 *
	 * @since 5.5
	 */
	protected function initialize() {
		$this->page_slug  = 'bulk-delete-users';
		$this->item_type  = 'users';
		$this->capability = 'delete_users';

		$this->label = array(
			'page_title' => __( 'Bulk Delete Users', 'bulk-delete' ),
			'menu_title' => __( 'Bulk Delete Users', 'bulk-delete' ),
		);

		$this->messages = array(
			'warning_message'      => __( 'WARNING: Users deleted once cannot be retrieved back. Use with caution.', 'bulk-delete' ),
		);

		add_filter( 'plugin_action_links', array( $this, 'add_plugin_action_links' ), 10, 2 );
	}

	/**
	 * Adds setting links in plugin listing page.
	 * Based on http://striderweb.com/nerdaphernalia/2008/06/wp-use-action-links/
	 *
	 * @param array   $links List of current links
	 * @param string  $file  Plugin filename
	 * @return array  $links Modified list of links
	 */
	public function add_plugin_action_links( $links, $file ) {
		$this_plugin = plugin_basename( Bulk_Delete::$PLUGIN_FILE );

		if ( $file == $this_plugin ) {
			$delete_users_link = '<a href="admin.php?page=' . $this->page_slug . '">' . __( 'Bulk Delete Users', 'bulk-delete' ) . '</a>';
			array_unshift( $links, $delete_users_link ); // before other links
		}

		return $links;
	}

	/**
	 * Add Help tabs.
	 *
	 * @since 5.5
	 */
	protected function add_help_tab( $help_tabs ) {
		$overview_tab = array(
			'title'    => __( 'Overview', 'bulk-delete' ),
			'id'       => 'overview_tab',
			'content'  => '<p>' . __( 'This screen contains different modules that allows you to delete users or schedule them for deletion.', 'bulk-delete' ) . '</p>',
			'callback' => false,
		);
		$help_tabs['overview_tab'] = $overview_tab;

		return $help_tabs;
	}
}

BD_Users_Page::factory();
?>
