<?php

$available_sizes = array_keys( $this->settings->get_sizes() );
$active_sizes = $this->settings->get_sizes();;
$active_tinify_sizes = $this->settings->get_active_tinify_sizes();
$error = $tiny_image->get_latest_error();
$total = $tiny_image->get_count( array( 'modified', 'missing', 'has_been_compressed', 'compressed' ) );
$active = $tiny_image->get_count( array( 'uncompressed', 'never_compressed' ), $active_tinify_sizes );
$image_statistics = $tiny_image->get_statistics();
$available_unoptimized_sizes = $image_statistics['available_unoptimized_sizes'];
$size_before = $image_statistics['initial_total_size'];
$size_after = $image_statistics['optimized_total_size'];

$size_active = array_fill_keys( $active_tinify_sizes, true );
$size_exists = array_fill_keys( $available_sizes, true );
ksort( $size_exists );

?>
<div class="details-container">
	<div class="details" >
		<?php if ( $error ) {
			// dashicons-warning available for WP 4.3+ ?>
			<span class="icon dashicons dashicons-no error"></span>
		<?php } else if ( $total['missing'] > 0 || $total['modified'] > 0 ) { ?>
			<span class="icon dashicons dashicons-yes alert"></span>
		<?php } else if ( $total['compressed'] > 0 && $available_unoptimized_sizes > 0 ) { ?>
			<span class="icon dashicons dashicons-yes alert"></span>
		<?php } else if ( $total['compressed'] > 0 ) { ?>
			<span class="icon dashicons dashicons-yes success"></span>
		<?php } ?>
		<span class="icon spinner hidden"></span>
		<?php if ( $total['has_been_compressed'] > 0 || (0 == $total['has_been_compressed'] && 0 == $available_unoptimized_sizes) ) { ?>
			<span class="message">
				<?php printf( wp_kses( _n( '<strong>%d</strong> size compressed', '<strong>%d</strong> sizes compressed', $total['has_been_compressed'], 'tiny-compress-images' ), array( 'strong' => array() ) ), $total['has_been_compressed'] ) ?>
			</span>
			<br/>
		<?php } ?>
		<?php if ( $available_unoptimized_sizes > 0 ) { ?>
			<span class="message">
				<?php printf( esc_html( _n( '%d size to be compressed', '%d sizes to be compressed', $available_unoptimized_sizes, 'tiny-compress-images' ) ), $available_unoptimized_sizes ) ?>
			</span>
			<br />
		<?php } ?>
		<?php if ( $size_before - $size_after ) { ?>
			<span class="message">
				<?php printf( esc_html__( 'Total savings %.0f%%', 'tiny-compress-images' ), (1 - $size_after / floatval( $size_before )) * 100 ) ?>
			</span>
			<br />
		<?php } ?>
		<?php if ( $error ) { ?>
			<span class="message error_message">
				<?php echo esc_html__( 'Latest error', 'tiny-compress-images' ) . ': '. esc_html__( $error, 'tiny-compress-images' ) ?>
			</span>
			<br/>
		<?php } ?>
		<a class="thickbox message" href="#TB_inline?width=700&amp;height=500&amp;inlineId=modal_<?php echo $tiny_image->get_id() ?>">
			<?php esc_html_e( 'Details', 'tiny-compress-images' ) ?>
		</a>
	</div>
	<?php if ( $active['uncompressed'] > 0 ) { ?>
		<button type="button" class="tiny-compress button button-small button-primary" data-id="<?php echo $tiny_image->get_id() ?>">
			<?php esc_html_e( 'Compress', 'tiny-compress-images' ) ?>
		</button>
	<?php } ?>
</div>

<div class="modal" id="modal_<?php echo $tiny_image->get_id() ?>">
	<div class="tiny-compression-details">
		<h3>
			<?php printf( esc_html__( 'Compression details for %s', 'tiny-compress-images' ), $tiny_image->get_name() ) ?>
		</h3>
		<table>
			<tr>
				<th><?php esc_html_e( 'Size' ) ?></th>
				<th><?php esc_html_e( 'Initial Size', 'tiny-compress-images' ) ?></th>
				<th><?php esc_html_e( 'Compressed', 'tiny-compress-images' ) ?></th>
				<th><?php esc_html_e( 'Date' ) ?></th>
			</tr>
			<?php
			$i = 0;
			$sizes = $tiny_image->get_image_sizes() + $size_exists;
			foreach ( $sizes as $size_name => $size ) {
				if ( ! is_object( $size ) ) {
					$size = new Tiny_Image_Size();
				}
				?>
				<tr class="<?php echo ( 0 == $i % 2 ) ? 'even' : 'odd' ?>">
					<?php
					echo '<td>';
					echo ( Tiny_Image::is_original( $size_name ) ? esc_html__( 'Original', 'tiny-compress-images' ) : esc_html__( ucfirst( rtrim( $size_name, '_wr2x' ) ) ) );
					echo ' ';
					if ( ! array_key_exists( $size_name, $active_sizes ) && ! Tiny_Image::is_retina( $size_name ) ) {
						echo '<em>' . esc_html__( '(not in use)', 'tiny-compress-images' ) . '</em>';
					} else if ( $size->missing() && ( Tiny_Settings::wr2x_active() || ! Tiny_Image::is_retina( $size_name ) ) ) {
						echo '<em>' . esc_html__( '(file removed)', 'tiny-compress-images' ) . '</em>';
					} else if ( $size->modified() ) {
						echo '<em>' . esc_html__( '(modified after compression)', 'tiny-compress-images' ) . '</em>';
					} else if ( Tiny_Image::is_retina( $size_name ) ) {
						echo '<em>' . esc_html__( '(WP Retina 2x)', 'tiny-compress-images' ) . '</em>';
					} else if ( $size->resized() ) {
						printf( '<em>' . esc_html__( '(resized to %dx%d)', 'tiny-compress-images' ) . '</em>', $size->meta['output']['width'], $size->meta['output']['height'] );
					}
					echo '</td>';

					if ( $size->is_duplicate() ) {
						echo '<td>-</td>';
						printf( '<td colspan=2><em>' . esc_html__( 'Same file as "%s"', 'tiny-compress-images' ) . '</em></td>', esc_html__( ucfirst( $size->duplicate_of_size() ) ) );
					} else if ( $size->has_been_compressed() ) {
						echo '<td>' . size_format( $size->meta['input']['size'], 1 ) . '</td>';
						echo '<td>' . size_format( $size->meta['output']['size'], 1 ) . '</td>';
						echo '<td>' . sprintf( esc_html__( '%s ago' ), human_time_diff( $size->end_time( $size_name ) ) ) . '</td>';
					} else if ( ! $size->exists() ) {
						echo '<td>-</td>';
						echo '<td colspan=2><em>' . esc_html__( 'Not present', 'tiny-compress-images' ) . '</em></td>';
					} else if ( isset( $size_active[ $size_name ] ) || Tiny_Image::is_retina( $size_name ) ) {
						echo '<td>' . size_format( $size->filesize(), 1 ) . '</td>';
						echo '<td colspan=2><em>' . esc_html__( 'Not compressed', 'tiny-compress-images' ) . '</em></td>';
					} else if ( isset( $size_exists[ $size_name ] ) ) {
						echo '<td>' . size_format( $size->filesize(), 1 ) . '</td>';
						echo '<td colspan=2><em>' . esc_html__( 'Not configured to be compressed', 'tiny-compress-images' ) . '</em></td>';
					} else if ( ! array_key_exists( $size_name, $active_sizes ) ) {
						echo '<td>' . size_format( $size->filesize(), 1 ) . '</td>';
						echo '<td colspan=2><em>' . esc_html__( 'Size is not in use', 'tiny-compress-images' ) . '</em></td>';
					} else {
						echo '<td>' . size_format( $size->filesize(), 1 ) . '</td>';
						echo '<td>-</td>';
					}
					?>
				</tr><?php
				$i++;
			}
			if ( $image_statistics['image_sizes_optimized'] > 0 ) { ?>
				<tfoot>
					<tr>
						<td><?php esc_html_e( 'Combined', 'tiny-compress-images' ) ?></td>
						<td><?php echo size_format( $size_before, 1 ) ?></td>
						<td><?php echo size_format( $size_after, 1 ) ?></td>
						<td></td>
					</tr>
				</tfoot><?php
			}
			?>
		</table>
		<p>
			<strong>
				<?php
				if ( $size_before - $size_after ) {
					printf( esc_html__( 'Total savings %.0f%% (%s)', 'tiny-compress-images' ),
						( 1 - $size_after / floatval( $size_before ) ) * 100,
						size_format( $size_before - $size_after, 1 )
					);
				} else {
					printf( esc_html__( 'Total savings %.0f%%', 'tiny-compress-images' ), 0 );
				}
				?>
			</strong>
		</p>
	</div>
</div>
