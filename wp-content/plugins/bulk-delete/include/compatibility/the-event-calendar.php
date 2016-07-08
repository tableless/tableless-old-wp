<?php
/**
 * Code to enable compatibility with 'The Event Calendar' plugin.
 *
 * @since 5.5.1
 * @author Sudar
 * @package BulkDelete\Util\Compatibility
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Remove actions used by The Event Calendar plugin.
 *
 * @since 5.5.1
 */
function bd_remove_event_calendar_filter() {
	if ( class_exists( 'Tribe__Events__Query' ) ) {
		remove_action( 'pre_get_posts', array( 'Tribe__Events__Query', 'pre_get_posts' ), 50 );
	}
}
add_action( 'bd_before_query', 'bd_remove_event_calendar_filter' );
