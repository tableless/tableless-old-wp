<?php
/**
 * Class that encapsulates the deletion of posts based on days
 *
 * @author Sudar
 * @package BulkDelete
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

class Bulk_Delete_By_Days {
	var $days;
	var $op;

	/**
	 * Default constructor.
	 */
	public function __construct() {
		add_action( 'parse_query', array( $this, 'parse_query' ) );
	}

	/**
	 * Parse the query.
	 *
	 * @param array $query
	 */
	public function parse_query( $query ) {
		if ( isset( $query->query_vars['days'] ) ) {
			$this->days = $query->query_vars['days'];
			$this->op = $query->query_vars['op'];

			add_filter( 'posts_where', array( $this, 'filter_where' ) );
			add_filter( 'posts_selection', array( $this, 'remove_where' ) );
		}
	}

	/**
	 * Modify the where clause.
	 *
	 * @param string $where (optional)
	 * @return string
	 */
	public function filter_where( $where = '' ) {
		$where .= ' AND post_date ' . $this->op . " '" . date( 'y-m-d', strtotime( '-' . $this->days . ' days' ) ) . "'";
		return $where;
	}

	/**
	 * Remove the `posts_where` filter.
	 */
	public function remove_where() {
		remove_filter( 'posts_where', array( $this, 'filter_where' ) );
	}
}
?>
