<?php
/**
 * Table to show the list of addon licenses
 *
 * @since      5.0
 * @author     Sudar
 * @package    BulkDelete\Addon
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class License_List_Table extends WP_List_Table {

	/**
	 * Constructor, setup labels
	 *
	 * @since 5.0
	 */
	public function __construct() {
		parent::__construct( array(
				'singular' => 'license_list',  // Singular label
				'plural'   => 'license_lists', // plural label, also this well be one of the table css class
				'ajax'     => false,           // We won't support Ajax for this table
			)
		);
	}

	/**
	 * Add extra markup in the toolbars before or after the list
	 *
	 * @since 5.0
	 * @param string  $which Whether the markup should be after (bottom) or before (top) the list
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' == $which ) {
			echo '<p>';
			_e( 'This is the list of addon license that are currently registered with the plugin.', 'bulk-delete' );
			echo '</p>';
		}
	}

	/**
	 * Define the list of columns that will be used in the table.
	 *
	 * @since  5.0
	 * @return array The list of columns in the table
	 */
	public function get_columns() {
		return array(
			'col_addon_name'       => __( 'Addon Name', 'bulk-delete' ),
			'col_license'          => __( 'License Code', 'bulk-delete' ),
			'col_license_validity' => __( 'Validity', 'bulk-delete' ),
			'col_expires'          => __( 'Expires', 'bulk-delete' )
		);
	}

	/**
	 * Defines columns that can be sorted
	 *
	 * @since  5.0
	 * @return array List of columns that can be sorted
	 */
	public function get_sortable_columns() {
		return array(
			'col_addon_name' => array( 'addon_name', false )
		);
	}

	/**
	 * Prepare the table
	 *
	 * @since 5.0
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = BD_License::get_licenses();
	}

	/**
	 * Display the col_addon_name column
	 *
	 * @since 5.0
	 * @param array   $item Single row of data
	 * @return string
	 */
	public function column_col_addon_name( $item ) {
		$validity     = $item['validity'];
		$action_label = __( 'Delete', 'bulk-delete' );
		$action_name  = 'delete_license';

		if ( 'valid' == $validity ) {
			$action_label = __( 'Deactivate', 'bulk-delete' );
			$action_name  = 'deactivate_license';
		}

		// Build row actions
		$actions = array(
			'deactivate' => sprintf( '<a href="?page=%s&bd_action=%s&addon-code=%s&%s=%s">%s</a>',
				$_REQUEST['page'],
				$action_name,
				$item['addon-code'],
				"bd-{$action_name}-nonce",
				wp_create_nonce( "bd-{$action_name}" ),
				$action_label
			),
		);

		// Return the title contents
		return sprintf( '%1$s%2$s',
			/*$1%s*/ $item['addon-name'],
			/*$2%s*/ $this->row_actions( $actions )
		);
	}

	/**
	 * Display the col_license column
	 *
	 * @since 5.0
	 * @param array   $item Single row of data
	 * @return string
	 */
	public function column_col_license( $item ) {
		return $item['license'];
	}

	/**
	 * Display the col_license_validity column
	 *
	 * @since 5.0
	 * @param array   $item Single row of data
	 * @return string
	 */
	public function column_col_license_validity( $item ) {
		$validity = $item['validity'];
		if ( 'valid' == $validity ) {
			return '<span style="color:green;">' . $validity . '</span>';
		} else {
			return '<span style="color:red;">' . $validity . '</span>';
		}
	}

	/**
	 * Display the col_expires column
	 *
	 * @since 5.0
	 * @param array   $item Single row of data
	 * @return string
	 */
	public function column_col_expires( $item ) {
		if ( key_exists( 'expires', $item ) ) {
			return $item['expires'];
		} else {
			return __( 'N/A', 'bulk-delete' );
		}
	}

	/**
	 * Define the message that will be shown when the table is empty
	 *
	 * @since 5.0
	 */
	public function no_items() {
		_e( "You don't have any valid addon license yet.", 'bulk-delete' );
	}
}
?>
