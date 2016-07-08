<?php
/**
 * Helper functions for settings API.
 * Most of these functions are copied from Easy Digital Downloads
 *
 * @since 5.3
 * @author     Sudar
 * @package    BulkDelete\Settings
 */


/**
 * Header Callback
 *
 * Renders the header.
 *
 * @since  5.3
 * @param array   $args Arguments passed by the setting
 * @return void
 */
function bd_header_callback( $args ) {
	echo '<hr/>';
}

/**
 * Text Callback
 *
 * Renders text fields.
 *
 * @since  5.3
 * @param array   $args Arguments passed by the setting
 * @return void
 */
function bd_text_callback( $args ) {
	$option_name = $args['option'];
	$bd_options = get_option( $option_name );

	if ( isset( $bd_options[ $args['id'] ] ) ) {
		$value = $bd_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . $size . '-text" id="' . $option_name . '[' . $args['id'] . ']" name="' . $option_name . '[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '">';
	$html .= '<label for="' . $option_name . '[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Rich Editor Callback
 *
 * Renders rich editor fields.
 *
 * @since 5.3
 * @param array   $args Arguments passed by the setting
 */
function bd_rich_editor_callback( $args ) {
	$option_name = $args['option'];
	$bd_options = get_option( $option_name );

	if ( isset( $bd_options[ $args['id'] ] ) ) {
		$value = $bd_options[ $args['id'] ];
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	ob_start();
	wp_editor( stripslashes( $value ), $option_name . '_' . $args['id'], array( 'textarea_name' => $option_name . '[' . $args['id'] . ']', 'media_buttons' => false ) );
	$html = ob_get_clean();

	$html .= '<br/><label for="' . $option_name . '[' . $args['id'] . ']"> ' . $args['desc'] . '</label>';

	echo $html;
}
?>
