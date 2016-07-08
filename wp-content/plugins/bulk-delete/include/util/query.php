<?php
/**
 * Utility and wrapper functions for WP_Query.
 *
 * @since      5.5
 * @author     Sudar
 * @package    BulkDelete\Util
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Process delete options array and build query.
 *
 * @param array $delete_options Delete Options
 * @param array $options        (optional) Options query
 */
function bd_build_query_options( $delete_options, $options = array() ) {
	// private posts
	if ( isset( $delete_options['private'] ) ) {
		if ( $delete_options['private'] ) {
			$options['post_status'] = 'private';
		} else {
			if ( ! isset( $options['post_status'] ) ) {
				$options['post_status'] = 'publish';
			}
		}
	}

	// limit to query
	if ( $delete_options['limit_to'] > 0 ) {
		$options['showposts'] = $delete_options['limit_to'];
	} else {
		$options['nopaging']  = 'true';
	}

	// post type
	if ( isset( $delete_options['post_type'] ) ) {
		$options['post_type'] = $delete_options['post_type'];
	}

	// date query
	if ( $delete_options['restrict'] ) {
		if ( 'before' === $delete_options['date_op'] || 'after' === $delete_options['date_op'] ) {
			$options['date_query'] = array(
				array(
					'column'                   => 'post_date',
					$delete_options['date_op'] => "{$delete_options['days']} day ago",
				),
			);
		}
	}

	return $options;
}

/**
 * Wrapper for WP_query.
 *
 * Adds some performance enhancing defaults.
 *
 * @since  5.5
 * @param  array $options List of options
 * @return array          Result array
 */
function bd_query( $options ) {
	$defaults = array(
		'cache_results'          => false, // don't cache results
		'update_post_meta_cache' => false, // No need to fetch post meta fields
		'update_post_term_cache' => false, // No need to fetch taxonomy fields
		'no_found_rows'          => true,  // No need for pagination
		'fields'                 => 'ids', // retrieve only ids
	);
	$options = wp_parse_args( $options, $defaults );

	$wp_query = new WP_Query();

	/**
	 * This action runs before the query happens.
	 *
	 * @since 5.5
	 */
	do_action( 'bd_before_query' );

	$posts = $wp_query->query( $options );

	/**
	 * This action runs after the query happens.
	 *
	 * @since 5.5
	 */
	do_action( 'bd_after_query' );

	return $posts;
}
?>
