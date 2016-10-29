<style>

/* Admin color scheme colors */

div.tiny-bulk-optimization div.available div.tooltip span.dashicons {
	color: <?php echo $admin_colors[3] ?>;
}
div.tiny-bulk-optimization div.savings div.chart div.value {
	color: <?php echo $admin_colors[2] ?>;
}
div.tiny-bulk-optimization div.savings div.chart svg circle.main {
	stroke: <?php echo $admin_colors[2] ?>;
}
div.tiny-bulk-optimization div.savings table td.emphasize {
	color: <?php echo $admin_colors[2] ?>;
}
div.tiny-bulk-optimization div.dashboard div.optimize div.progressbar div.progress {
	background-color: <?php echo $admin_colors[0] ?>;
	background-image: linear-gradient(
		-63deg,
		<?php echo $admin_colors[0] ?> 0%,
		<?php echo $admin_colors[0] ?> 25%,
		<?php echo $admin_colors[1] ?> 25%,
		<?php echo $admin_colors[1] ?> 50%,
		<?php echo $admin_colors[0] ?> 50%,
		<?php echo $admin_colors[0] ?> 75%,
		<?php echo $admin_colors[1] ?> 75%,
		<?php echo $admin_colors[1] ?> 100%
	);
}

</style>

<div class="wrap tiny-bulk-optimization tiny-compress-images" id="tiny-bulk-optimization">
	<div class="icon32" id="icon-upload"><br></div>
	<h2><?php esc_html_e( 'Bulk Optimization', 'tiny-compress-images' ) ?></h2>
	<div class="dashboard">
		<div class="statistics">
			<div class="available">
				<div class="inner">
					<h3><?php esc_html_e( 'Available Images', 'tiny-compress-images' ) ?></h3>
					<p>
						<?php
						if ( 0 == $stats['optimized-image-sizes'] + $stats['available-unoptimised-sizes'] ) {
							$percentage = 0;
						} else {
							$percentage_of_files = round( $stats['optimized-image-sizes'] / ( $stats['optimized-image-sizes'] + $stats['available-unoptimised-sizes'] ) * 100, 2 );
						}
						if ( 0 == $stats['uploaded-images'] + $stats['available-unoptimised-sizes'] ) {
							esc_html_e( 'This page is designed to bulk optimize all your images. You don\'t seem to have uploaded any JPEG or PNG images yet.' );
						} elseif ( 0 == sizeof( $active_tinify_sizes ) ) {
							esc_html_e( 'Based on your current settings, nothing will be optimized. There are no active sizes selected for optimization.' );
						} elseif ( 0 == $stats['available-unoptimised-sizes'] ) {
							printf( esc_html__( '%s, this is great! Your entire library is optimized!' ), $this->friendly_user_name() );
						} elseif ( $stats['optimized-image-sizes'] > 0 ) {
							if ( $percentage_of_files > 75 ) {
								printf( esc_html__( '%s, you are doing great!', 'tiny-compress-images' ), $this->friendly_user_name() );
							} else {
								printf( esc_html__( '%s, you are doing good.', 'tiny-compress-images' ), $this->friendly_user_name() );
							}
							echo ' ';
							printf( esc_html__( '%d%% of your image library is optimized.', 'tiny-compress-images' ), $percentage_of_files );
							echo ' ';
							printf( esc_html__( 'Start the bulk optimization to optimize the remainder of your library.', 'tiny-compress-images' ) );
						} else {
							esc_html_e( 'Here you can start optimizing your entire library. Press the big button to start improving your website speed instantly!', 'tiny-compress-images' );
						}
						?>
					</p>
					<p>
						<?php
						if ( Tiny_Settings::wr2x_active() ) {
							esc_html_e( 'Notice that the WP Retina 2x sizes will not be compressed using this page. You will need to bulk generate the retina sizes separately from the WP Retina 2x page.', 'tiny-compress-images' );
						}
						?>
					</p>
					<table class="totals">
						<tr>
							<td class="item">
								<h3>
									<?php echo wp_kses( __( 'Uploaded <br> images', 'tiny-compress-images' ), array( 'br' => array() ) ) ?>
								</h3>
								<span id="uploaded-images">
									<?php echo $stats['uploaded-images']; ?>
								</span>
							</td>
							<td class="item">
								<h3>
									<?php echo wp_kses( __( 'Uncompressed image sizes', 'tiny-compress-images' ), array( 'br' => array() ) ) ?>
								</h3>
								<span id="optimizable-image-sizes">
									<?php echo $stats['available-unoptimised-sizes'] ?>
								</span>
								<div class="tooltip">
									<span class="dashicons dashicons-info"></span>
									<div class="tip">
										<?php if ( $stats['uploaded-images'] > 0 && sizeof( $active_tinify_sizes ) > 0 && $stats['available-unoptimised-sizes'] > 0 ) { ?>
											<p>
												<?php
												printf( esc_html__( 'With your current settings you can still optimize %d images sizes from your %d uploaded JPEG and PNG images.',
												'tiny-compress-images'), $stats['available-unoptimised-sizes'], $stats['uploaded-images'] );
												?>
											</p>
										<?php } ?>
										<p>
											<?php
											if ( 0 == sizeof( $active_tinify_sizes ) ) {
												esc_html_e( 'Based on your current settings, nothing will be optimized. There are no active sizes selected for optimization.', 'tiny-compress-images' );
											} else {
												esc_html_e( 'These sizes are currently activated for compression:', 'tiny-compress-images' );
												echo '<ul>';
												for ( $i = 0; $i < sizeof( $active_tinify_sizes ); ++$i ) {
													$name = $active_tinify_sizes[ $i ];
													if ( '0' == $name ) {
														echo '<li>- ' . esc_html__( 'Original image', 'tiny-compress-images' ) . '</li>';
													} else {
														echo '<li>- ' . esc_html__( ucfirst( $name ) ) . '</li>';
													}
												}
												echo '</ul>';
											}
											?>
										</p>
										<p>
											<?php esc_html_e( 'For each uploaded image, ', 'tiny-compress-images' ) ?>
											<strong>
												<?php echo sizeof( $active_tinify_sizes ) ?>
												<?php sizeof( $active_tinify_sizes ) > 1 ? esc_html_e( 'sizes', 'tiny-compress-images' ) : esc_html_e( 'size', 'tiny-compress-images' ) ?>
											</strong>
											<?php sizeof( $active_tinify_sizes ) > 1 ? esc_html_e( 'are compressed.', 'tiny-compress-images' ) : esc_html_e( 'is compressed.', 'tiny-compress-images' ) ?>
											<?php esc_html_e( 'You can changed these settings', 'tiny-compress-images' ) ?>
											<a href="/wp-admin/options-media.php#tiny-compress-images"><?php esc_html_e( 'here', 'tiny-compress-images' )?></a>.
										</p>
									</div>
								</div>
							</td>
							<td class="item costs">
								<h3>
									<?php echo wp_kses( __( 'Estimated <br> cost', 'tiny-compress-images' ), array( 'br' => array() ) ) ?>
								</h3>
								<span id="estimated-cost">$ <?php echo number_format( $estimated_costs, 2 ) ?></span>
								<div class="cost-currency">USD</div>
								<?php if ( $estimated_costs > 0 ) { ?>
									<div class="tooltip">
										<span class="dashicons dashicons-info"></span>
										<div class="tip">
											<p>
												<?php esc_html_e( 'If you wish to compress more than ', 'tiny-compress-images' ) ?>
												<strong>
													<?php echo Tiny_Config::MONTHLY_FREE_COMPRESSIONS ?>
													<?php esc_html_e( 'image sizes', 'tiny-compress-images' ) ?>
												</strong>
												<?php esc_html_e( 'a month and you are still on a free account', 'tiny-compress-images' ) ?>
												<a href="https://tinypng.com/developers"><?php esc_html_e( 'upgrade here.', 'tiny-compress-images' ) ?></a>
											</p>
										</div>
									</div>
								<?php } ?>
							</td>
						</tr>
					</table>
					<div class="notes">
						<h4><?php esc_html_e( 'Remember', 'tiny-compress-images' ) ?></h4>
						<p>
							<?php esc_html_e( 'For the plugin to do the work, you need to keep this page open. But no worries: when stopped, you can continue where you left off!', 'tiny-compress-images' ); ?>
						</p>
					</div>
				</div>
			</div>
			<div class="savings">
				<div class="inner">
					<h3><?php esc_html_e( 'Total Savings', 'tiny-compress-images' ) ?></h3>
					<p>
						<?php esc_html_e( 'Statistics based on all available JPEG and PNG images in your media library.', 'tiny-compress-images' ); ?>
					</p>
					<?php
						require_once dirname( __FILE__ ) . '/bulk-optimization-chart.php';
					?>
					<div class="legend">
						<table>
							<tr>
								<td id="optimized-image-sizes" class="value emphasize">
									<?php echo $stats['optimized-image-sizes']; ?>
								</td>
								<td class="description">
									<?php echo _n( 'image size optimized', 'image sizes optimized', $stats['optimized-image-sizes'], 'tiny-compress-images' ) ?>
								</td>
							</tr>
							<tr>
								<td id="unoptimized-library-size" class="value" data-bytes="<?php echo $stats['unoptimized-library-size']; ?>" >
									<?php echo ( $stats['unoptimized-library-size'] ? size_format( $stats['unoptimized-library-size'], 2 ) : '-'); ?>
								</td>
								<td class="description">
									<?php esc_html_e( 'initial size', 'tiny-compress-images' ) ?>
								</td>
							</tr>
							<tr>
								<td id="optimized-library-size" class="value emphasize" data-bytes="<?php echo $stats['optimized-library-size'] ?>" class="green">
									<?php echo ($stats['optimized-library-size'] ? size_format( $stats['optimized-library-size'], 2 ) : '-') ?>
								</td>
								<td class="description">
									<?php esc_html_e( 'current size', 'tiny-compress-images' ) ?>
								</td>
							</tr>
						</table>
					</div>
				</div>
			</div>
		</div>
		<div class="optimize">
			<div class="progressbar" id="compression-progress-bar" data-number-to-optimize="<?php echo $stats['optimized-image-sizes'] + $stats['available-unoptimised-sizes'] ?>" data-amount-optimized="0">
				<div id="progress-size" class="progress">
				</div>
				<div class="numbers" >
					<span id="optimized-so-far"><?php echo $stats['optimized-image-sizes'] ?></span>
					/
					<span><?php echo $stats['optimized-image-sizes'] + $stats['available-unoptimised-sizes'] ?></span>
					<span id="percentage"></span>
				</div>
			</div>
			<?php
			if ( $stats['available-unoptimised-sizes'] > 0 ) {
				require_once dirname( __FILE__ ) . '/bulk-optimization-form.php';
			}
			?>
		</div>
	</div>
	<script type="text/javascript">
	<?php
	if ( $auto_start_bulk ) {
		echo 'jQuery(function() { bulkOptimizationAutorun(' . json_encode( $this->get_ids_to_compress() ) . ')})';
	} else {
		echo 'jQuery(function() { bulkOptimization(' . json_encode( $stats['available-for-optimization'] ) . ')})';
	}
	?>
	</script>
	<table class="wp-list-table widefat fixed striped media whitebox" id="optimization-items" >
		<thead>
			<tr>
				<?php // column-author WP 3.8-4.2 mobile view ?>
				<th class="thumbnail"></th>
				<th class="column-primary" ><?php esc_html_e( 'File', 'tiny-compress-images' ) ?></th>
				<th class="column-author"><?php esc_html_e( 'Sizes Optimized', 'tiny-compress-images' ) ?></th>
				<th class="column-author"><?php esc_html_e( 'Initial Size', 'tiny-compress-images' ) ?></th>
				<th class="column-author"><?php esc_html_e( 'Current Size', 'tiny-compress-images' ) ?></th>
				<th class="column-author savings" ><?php esc_html_e( 'Savings', 'tiny-compress-images' ) ?></th>
				<th class="status" ><?php esc_html_e( 'Status', 'tiny-compress-images' ) ?></th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
</div>
