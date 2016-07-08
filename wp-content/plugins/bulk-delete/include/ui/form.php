<?php
/**
 * Utility functions for displaying form.
 *
 * @since      5.5
 * @author     Sudar
 * @package    BulkDelete\Ui
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

/**
 * Render filtering table header.
 *
 * @since 5.5
 */
function bd_render_filtering_table_header() {
?>
	<tr>
		<td colspan="2">
			<h4><?php _e( 'Choose your filtering options', 'bulk-delete' ); ?></h4>
		</td>
	</tr>
<?php
}

/**
 * Render "restrict by created date" dropdown.
 *
 * @since 5.5
 * @param string $slug The slug to be used in field names.
 * @param string $item (optional) Item for which form is displayed. Default is 'posts'.
 */
function bd_render_restrict_settings( $slug, $item = 'posts' ) {
?>
	<tr>
		<td scope="row">
			<input name="smbd_<?php echo $slug; ?>_restrict" id="smbd_<?php echo $slug; ?>_restrict" value="true" type="checkbox">
		</td>
		<td>
			<?php printf( __( 'Only restrict to %s which are ', 'bulk-delete' ), $item );?>
			<select name="smbd_<?php echo $slug; ?>_op" id="smbd_<?php echo $slug; ?>_op" disabled>
				<option value="before"><?php _e( 'older than', 'bulk-delete' );?></option>
				<option value="after"><?php _e( 'posted within last', 'bulk-delete' );?></option>
			</select>
			<input type="number" name="smbd_<?php echo $slug; ?>_days" id="smbd_<?php echo $slug; ?>_days" class="screen-per-page" disabled value="0" min="0"><?php _e( 'days', 'bulk-delete' );?>
		</td>
	</tr>
<?php
}

/**
 * Render "force delete" setting fields.
 *
 * @since 5.5
 * @param string $slug The slug to be used in field names.
 */
function bd_render_delete_settings( $slug ) {
?>
	<tr>
		<td scope="row" colspan="2">
			<input name="smbd_<?php echo $slug; ?>_force_delete" value="false" type="radio" checked> <?php _e( 'Move to Trash', 'bulk-delete' ); ?>
			<input name="smbd_<?php echo $slug; ?>_force_delete" value="true" type="radio"> <?php _e( 'Delete permanently', 'bulk-delete' ); ?>
		</td>
	</tr>
<?php
}

/**
 * Render the "private post" setting fields.
 *
 * @since 5.5
 * @param string $slug The slug to be used in field names.
 */
function bd_render_private_post_settings( $slug ) {
?>
	<tr>
		<td scope="row" colspan="2">
			<input name="smbd_<?php echo $slug; ?>_private" value="false" type="radio" checked> <?php _e( 'Public posts', 'bulk-delete' ); ?>
			<input name="smbd_<?php echo $slug; ?>_private" value="true" type="radio"> <?php _e( 'Private Posts', 'bulk-delete' ); ?>
		</td>
	</tr>
<?php
}

/**
 * Render the "limit" setting fields.
 *
 * @since 5.5
 * @param string $slug The slug to be used in field names.
 * @param string $item (Optional) Item type. Possible values are 'posts', 'pages', 'users'
 */
function bd_render_limit_settings( $slug, $item = 'posts' ) {
?>
	<tr>
		<td scope="row">
			<input name="smbd_<?php echo $slug; ?>_limit" id="smbd_<?php echo $slug; ?>_limit" value="true" type="checkbox">
		</td>
		<td>
			<?php _e( 'Only delete first ', 'bulk-delete' );?>
			<input type="number" name="smbd_<?php echo $slug; ?>_limit_to" id="smbd_<?php echo $slug; ?>_limit_to" class="screen-per-page" disabled value="0" min="0"> <?php echo $item;?>.
			<?php printf( __( 'Use this option if there are more than 1000 %s and the script timesout.', 'bulk-delete' ), $item ); ?>
		</td>
	</tr>
<?php
}

/**
 * Render cron setting fields.
 *
 * @since 5.5
 * @param string $slug The slug to be used in field names.
 * @param string $addon_url Url for the pro addon.
 */
function bd_render_cron_settings( $slug, $addon_url ) {
	$pro_class = 'bd-' . str_replace( '_', '-', $slug ) . '-pro';
?>
	<tr>
		<td scope="row" colspan="2">
			<input name="smbd_<?php echo $slug; ?>_cron" value="false" type="radio" checked="checked"> <?php _e( 'Delete now', 'bulk-delete' ); ?>
			<input name="smbd_<?php echo $slug; ?>_cron" value="true" type="radio" id="smbd_<?php echo $slug; ?>_cron" disabled > <?php _e( 'Schedule', 'bulk-delete' ); ?>
			<input name="smbd_<?php echo $slug; ?>_cron_start" id="smbd_<?php echo $slug; ?>_cron_start" value="now" type="text" disabled><?php _e( 'repeat ', 'bulk-delete' );?>
			<select name="smbd_<?php echo $slug; ?>_cron_freq" id="smbd_<?php echo $slug; ?>_cron_freq" disabled>
				<option value="-1"><?php _e( "Don't repeat", 'bulk-delete' ); ?></option>
<?php
	$schedules = wp_get_schedules();
	foreach ( $schedules as $key => $value ) {
?>
				<option value="<?php echo $key; ?>"><?php echo $value['display']; ?></option>
<?php } ?>
			</select>
			<span class="<?php echo sanitize_html_class( apply_filters( 'bd_pro_class', $pro_class, $slug ) ); ?>" style="color:red"><?php _e( 'Only available in Pro Addon', 'bulk-delete' ); ?> <a href="<?php echo $addon_url; ?>">Buy now</a></span>
		</td>
	</tr>

	<tr>
		<td scope="row" colspan="2">
			<?php _e( 'Enter time in <strong>Y-m-d H:i:s</strong> format or enter <strong>now</strong> to use current time', 'bulk-delete' );?>
		</td>
	</tr>
<?php
}

/**
 * Render the submit button.
 *
 * @since 5.5
 * @param string $action The action attribute of the submit button.
 */
function bd_render_submit_button( $action ) {
?>
	<p class="submit">
		<button type="submit" name="bd_action" value="<?php echo esc_attr( $action ); ?>" class="button-primary"><?php _e( 'Bulk Delete ', 'bulk-delete' ); ?>&raquo;</button>
	</p>
<?php
}

/**
 * Render the post type dropdown.
 *
 * @since 5.5
 * @param string $slug The slug to be used in field names.
 */
function bd_render_post_type_dropdown( $slug ) {
	$types = get_post_types( array( '_builtin' => false ), 'names' );
	array_unshift( $types, 'page' );
	array_unshift( $types, 'post' );
?>
	<tr>
		<td scope="row" >
			<select class="select2" name="smbd_<?php echo $slug; ?>_post_type">
				<?php foreach ( $types as $type ) { ?>
					<option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $type ); ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
<?php
}

/**
 * Render sidebar iframe.
 *
 * @since 5.5.4
 */
function bd_render_sidebar_iframe() {
?>
	<div id="postbox-container-1" class="postbox-container">
		<iframe frameBorder="0" height="1500" src="http://sudarmuthu.com/projects/wordpress/bulk-delete/sidebar.php?color=<?php echo esc_attr( get_user_option( 'admin_color' ) ); ?>&version=<?php echo esc_attr( Bulk_Delete::VERSION ); ?>"></iframe>
	</div>
<?php
}
