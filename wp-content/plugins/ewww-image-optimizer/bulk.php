<?php
// presents the bulk optimize form with the number of images, and runs it once they submit the button
function ewww_image_optimizer_bulk_preview() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// retrieve the attachment IDs that were pre-loaded in the database
	list( $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count ) = ewww_image_optimizer_count_optimized( 'media' );
?>
	<div class="wrap"> 
	<h1>
<?php 		esc_html_e( 'Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN );
		if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' ) ) {
			$verify_cloud = ewww_image_optimizer_cloud_verify( false ); 
			echo '<span><a id="ewww-bulk-credits-available" target="_blank" class="page-title-action" style="float:right;" href="https://ewww.io/my-account/">' . esc_html__( 'Image credits available:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . ' ' . ewww_image_optimizer_cloud_quota() . '</a></span>';
		}
	echo '</h1>';
	// Retrieve the value of the 'bulk resume' option and set the button text for the form to use
	$resume = get_option( 'ewww_image_optimizer_bulk_resume' );
	if ( empty( $resume ) ) {
		$button_text = esc_attr__( 'Start optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN );
	} else {
		$button_text = esc_attr__( 'Resume previous optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN );
	}
	$loading_image = plugins_url( '/images/wpspin.gif', __FILE__ );
	// create the html for the bulk optimize form and status divs
?>
		<div id="ewww-bulk-loading">
			<p id="ewww-loading" class="ewww-bulk-info" style="display:none"><?php esc_html_e( 'Importing', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?>&nbsp;<img src='<?php echo $loading_image; ?>' /></p>
		</div>
		<div id="ewww-bulk-progressbar"></div>
		<div id="ewww-bulk-counter"></div>
		<form id="ewww-bulk-stop" style="display:none;" method="post" action="">
			<br /><input type="submit" class="button-secondary action" value="<?php esc_attr_e( 'Stop Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?>" />
		</form>
		<div id="ewww-bulk-widgets" class="metabox-holder" style="display:none">
			<div class="meta-box-sortables">
				<div id="ewww-bulk-last" class="postbox">
					<button type="button" class="handlediv button-link" aria-expanded="true">
						<span class="screen-reader-text"><?php esc_html_e( 'Click to toggle', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
					<h2 class="hndle"><span><?php esc_html_e( 'Last Image Optimized', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></span></h2>
					<div class="inside"></div>
				</div>
			</div>
			<div class="meta-box-sortables">
				<div id="ewww-bulk-status" class="postbox">
					<button type="button" class="handlediv button-link" aria-expanded="true">
						<span class="screen-reader-text"><?php esc_html_e( 'Click to toggle', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
					<h2 class="hndle"><span><?php esc_html_e( 'Optimization Log', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></span></h2>
					<div class="inside"></div>
				</div>
			</div>
		</div>
		<form class="ewww-bulk-form">
			<p><label for="ewww-force" style="font-weight: bold"><?php esc_html_e( 'Force re-optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></label>&emsp;<input type="checkbox" id="ewww-force" name="ewww-force"></p>
			<p><label for="ewww-delay" style="font-weight: bold"><?php esc_html_e( 'Choose how long to pause between images (in seconds, 0 = disabled)', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></label>&emsp;<input type="text" id="ewww-delay" name="ewww-delay" value="<?php if ( $delay = ewww_image_optimizer_get_option ( 'ewww_image_optimizer_delay' ) ) { echo $delay; } else { echo 0; } ?>"></p>
			<div id="ewww-delay-slider" style="width:50%"></div>
		</form>
		<h2><?php esc_html_e( 'Optimize Media Library', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></h3>
<?php		if ( $fullsize_count < 1 ) {
			echo '<p>' . esc_html__( 'You do not appear to have uploaded any images yet.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</p>';
		} else { ?>
			<div id="ewww-bulk-forms">
<?php			if ( ! $resize_count && ! $unoptimized_count && ! $unoptimized_resize_count ) { 
				if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' ) ) {
					$credits_needed = $fullsize_count * ( count( get_intermediate_image_sizes() ) + 1 );
				} ?>
				<p class="ewww-media-info ewww-bulk-info"><?php printf( esc_html__( '%1$d images in the Media Library have been selected, unable to determine how many resizes and how many are unoptimized.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $fullsize_count ); ?> <?php if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' ) && $credits_needed > 0 ) { printf( esc_html__( 'This could require approximately %d image credits to complete.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $credits_needed ); } ?><br />
<?php			} else { 
				if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' ) ) {
					$credits_needed = $unoptimized_count + $unoptimized_resize_count;
				} ?>
				<p class="ewww-media-info ewww-bulk-info"><?php printf( esc_html__( '%1$d images in the Media Library have been selected (%2$d unoptimized), with %3$d resizes (%4$d unoptimized).', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count ); ?>  <?php if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' ) && $credits_needed > 0 ) { printf( esc_html__( 'This could require approximately %d image credits to complete.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $credits_needed ); } ?><br />
<?php			} ?>
			<?php esc_html_e( 'Previously optimized images will be skipped by default.', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></p>
			<form id="ewww-bulk-start" class="ewww-bulk-form" method="post" action="">
				<input id="ewww-bulk-first" type="submit" class="button-secondary action" value="<?php echo $button_text; ?>" />
				<input id="ewww-bulk-again" type="submit" class="button-secondary action" style="display:none" value="<?php esc_attr_e( 'Optimize Again', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?>" />
			</form>
<?php		}
		// if the 'bulk resume' option was not empty, offer to reset it so the user can start back from the beginning
		if ( ! empty( $resume ) ): 
?>
			<p class="ewww-media-info ewww-bulk-info"><?php esc_html_e( 'If you would like to start over again, press the Reset Status button to reset the bulk operation status.', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></p>
			<form class="ewww-bulk-form" method="post" action="">
				<?php wp_nonce_field( 'ewww-image-optimizer-bulk', 'ewww_wpnonce' ); ?>
				<input type="hidden" name="ewww_reset" value="1">
				<button id="ewww-bulk-reset" type="submit" class="button-secondary action"><?php esc_html_e( 'Reset Status', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></button>
			</form>
<?php		endif;
	echo '</div>';
	ewwwio_memory( __FUNCTION__ );
	ewww_image_optimizer_aux_images();
}

// retrieve image counts for the bulk process
function ewww_image_optimizer_count_optimized( $gallery, $return_ids = false ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	$full_count = 0;
	$unoptimized_full = 0;
	$unoptimized_re = 0;
	$resize_count = 0;
	$attachment_query = '';
	ewwwio_debug_message( "scanning for $gallery" );
	// retrieve the time when the optimizer starts
	$started = microtime(true);
	if ( ewww_image_optimizer_stl_check() ) {
		set_time_limit( 0 );
	}
	$max_query = apply_filters( 'ewww_image_optimizer_count_optimized_queries', 3000 );
	$max_query = (int) $max_query;
	$attachment_query_count = 0;
	switch ( $gallery ) {
		case 'media':
			$ids = array();
			// see if we were given attachment IDs to work with via GET/POST
		        if ( ! empty( $_REQUEST['ids'] ) || get_option( 'ewww_image_optimizer_bulk_resume' ) ) {
				ewwwio_debug_message( 'we have received attachment ids via $_REQUEST' );
				// retrieve the attachment IDs that were pre-loaded in the database
				$attachment_ids = get_option( 'ewww_image_optimizer_bulk_attachments' );
				if ( ! empty( $attachment_ids ) ) {
					while ( $attachment_ids && $attachment_query_count < $max_query ) {
						$attachment_query .= "'" . array_pop( $attachment_ids ) . "',";
						$attachment_query_count++;
					}
					$attachment_query = 'AND metas.post_id IN (' . substr( $attachment_query, 0, -1 ) . ')';
				}
			}
			$offset = 0;
			// retrieve all the image attachment metadata from the database
			while ( $attachments = $wpdb->get_results( "SELECT metas.meta_value,post_id FROM $wpdb->postmeta metas INNER JOIN $wpdb->posts posts ON posts.ID = metas.post_id WHERE posts.post_mime_type LIKE '%%image%%' AND metas.meta_key = '_wp_attachment_metadata' $attachment_query LIMIT $offset,$max_query", ARRAY_N ) ) {
				ewwwio_debug_message( "fetched " . count( $attachments ) . " attachments starting at $offset" );
				$disabled_sizes = ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_resizes' );
				foreach ( $attachments as $attachment ) {
					$meta = unserialize( $attachment[0] );
					if ( empty( $meta ) ) {
						continue;
					}
					if ( empty( $meta['ewww_image_optimizer'] ) ) {
						$unoptimized_full++;
						$ids[] = $attachment[1];
					}
					if ( ! empty( $meta['ewww_image_optimizer'] ) && preg_match( '/' . __('License exceeded', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '/', $meta['ewww_image_optimizer'] ) ) {
						$unoptimized_full++;
						$ids[] = $attachment[1];
					}
					// resized versions, so we can continue
					if ( isset( $meta['sizes'] ) ) {
						foreach( $meta['sizes'] as $size => $data ) {
							if ( ! empty( $disabled_sizes[ $size ] ) ) {
								continue;
							}
							$resize_count++;
							if ( empty( $meta['sizes'][ $size ]['ewww_image_optimizer'] ) ) {
								$unoptimized_re++;
							}
						}
					}
				}
				$full_count += count( $attachments );
				$offset += $max_query;
				if ( ! empty( $attachment_ids ) ) {
					$attachment_query = '';
					$attachment_query_count = 0;
					$offset = 0;
					while ( $attachment_ids && $attachment_query_count < $max_query ) {
						$attachment_query .= "'" . array_pop( $attachment_ids ) . "',";
						$attachment_query_count++;
					}
					$attachment_query = 'AND metas.post_id IN (' . substr( $attachment_query, 0, -1 ) . ')';
				}
			}
			break;
		case 'ngg':
			// see if we were given attachment IDs to work with via GET/POST
		        if ( ! empty($_REQUEST['ewww_inline']) || get_option('ewww_image_optimizer_bulk_ngg_resume')) {
				// retrieve the attachment IDs that were pre-loaded in the database
				$attachment_ids = get_option('ewww_image_optimizer_bulk_ngg_attachments');
				while ( $attachment_ids && $attachment_query_count < $max_query ) {
					$attachment_query .= "'" . array_pop( $attachment_ids ) . "',";
					$attachment_query_count++;
				}
				$attachment_query = 'WHERE pid IN (' . substr( $attachment_query, 0, -1 ) . ')';
			}
			// creating the 'registry' object for working with nextgen
			$registry = C_Component_Registry::get_instance();
			// creating a database storage object from the 'registry' object
			$storage  = $registry->get_utility('I_Gallery_Storage');
			// get an array of sizes available for the $image
			$sizes = $storage->get_image_sizes();
			$offset = 0;
			while ( $attachments = $wpdb->get_col( "SELECT meta_data FROM $wpdb->nggpictures $attachment_query LIMIT $offset, $max_query" ) ) {
				foreach ( $attachments as $attachment ) {
					if ( class_exists( 'Ngg_Serializable' ) ) {
				        	$serializer = new Ngg_Serializable();
				        	$meta = $serializer->unserialize( $attachment );
					} else {
						$meta = unserialize( $attachment );
					}
					if ( ! is_array( $meta ) ) {
						continue;
					}
					if ( empty( $meta['ewww_image_optimizer'] ) ) {
							$unoptimized_full++;
					}
					foreach ( $sizes as $size ) {
						if ( $size !== 'full' ) {
							$resize_count++;
							if ( empty( $meta[ $size ]['ewww_image_optimizer'] ) ) {
								$unoptimized_re++;
							}
						}
					}
				}
				$full_count += count($attachments);
				$offset += $max_query;
				if ( ! empty( $attachment_ids ) ) {
					$attachment_query = '';
					$attachment_query_count = 0;
					$offset = 0;
					while ( $attachment_ids && $attachment_query_count < $max_query ) {
						$attachment_query .= "'" . array_pop( $attachment_ids ) . "',";
						$attachment_query_count++;
					}
					$attachment_query = 'WHERE pid IN (' . substr( $attachment_query, 0, -1 ) . ')';
				}
			}
			break;
		case 'flag':
			if ( ! empty( $_REQUEST['doaction'] ) || get_option( 'ewww_image_optimizer_bulk_flag_resume' ) ) {
				// retrieve the attachment IDs that were pre-loaded in the database
				$attachment_ids = get_option('ewww_image_optimizer_bulk_flag_attachments');
				while ( $attachment_ids && $attachment_query_count < $max_query ) {
					$attachment_query .= "'" . array_pop( $attachment_ids ) . "',";
					$attachment_query_count++;
				}
				$attachment_query = 'WHERE pid IN (' . substr( $attachment_query, 0, -1 ) . ')';
			}
			$offset = 0;
			while ( $attachments = $wpdb->get_col( "SELECT meta_data FROM $wpdb->flagpictures $attachment_query LIMIT $offset, $max_query" ) ) {
				foreach ($attachments as $attachment) {
					$meta = unserialize( $attachment );
					if ( ! is_array( $meta ) ) {
						continue;
					}
					if (empty($meta['ewww_image_optimizer'])) {
						$unoptimized_full++;
					}
					if (!empty($meta['webview'])) {
						$resize_count++;
						if(empty($meta['webview']['ewww_image_optimizer'])) {
							$unoptimized_re++;
						}
					}
					if (!empty($meta['thumbnail'])) {
						$resize_count++;
						if(empty($meta['thumbnail']['ewww_image_optimizer'])) {
							$unoptimized_re++;
						}
					}
				}
				$full_count += count($attachments);
				$offset += $max_query;
				if ( ! empty( $attachment_ids ) ) {
					$attachment_query = '';
					$attachment_query_count = 0;
					$offset = 0;
					while ( $attachment_ids && $attachment_query_count < $max_query ) {
						$attachment_query .= "'" . array_pop( $attachment_ids ) . "',";
						$attachment_query_count++;
					}
					$attachment_query = 'WHERE pid IN (' . substr( $attachment_query, 0, -1 ) . ')';
				}
			}
			break;
	}
	if ( empty( $full_count ) && ! empty( $attachment_ids ) ) {
		ewwwio_debug_message( 'query appears to have failed, just counting total images instead' );
		$full_count = count( $attachment_ids );
	}
	$elapsed = microtime( true ) - $started;
	ewwwio_debug_message( "counting images took $elapsed seconds" );
	ewwwio_debug_message( "found $full_count fullsize ($unoptimized_full unoptimized), and $resize_count resizes ($unoptimized_re unoptimized)" );
	ewwwio_memory( __FUNCTION__ );
	if ( $return_ids ) {
		return $ids;
	} else {
		return array( $full_count, $unoptimized_full, $resize_count, $unoptimized_re );
	}
}

// prepares the bulk operation and includes the javascript functions
function ewww_image_optimizer_bulk_script( $hook ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// make sure we are being called from the bulk optimization page
	if ( 'media_page_ewww-image-optimizer-bulk' != $hook ) {
		return;
	}
        // initialize the $attachments variable
        $attachments = array();
        // check to see if we are supposed to reset the bulk operation and verify we are authorized to do so
	if ( ! empty( $_REQUEST['ewww_reset'] ) && wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) ) {
		// set the 'bulk resume' option to an empty string to reset the bulk operation
		update_option( 'ewww_image_optimizer_bulk_resume', '' );
	}
        // check to see if we are supposed to reset the bulk operation and verify we are authorized to do so
	if ( ! empty( $_REQUEST['ewww_reset_aux'] ) && wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-aux-images' ) ) {
		// set the 'bulk resume' option to an empty string to reset the bulk operation
		update_option( 'ewww_image_optimizer_aux_resume', '' );
	}
        // check to see if we are supposed to convert the auxiliary images table and verify we are authorized to do so
	if ( ! empty( $_REQUEST['ewww_convert'] ) && wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-aux-images' ) ) {
		ewww_image_optimizer_aux_images_convert();
	}
	global $wpdb;
	// check the 'bulk resume' option
	$resume = get_option('ewww_image_optimizer_bulk_resume');
	// see if we were given attachment IDs to work with via GET/POST
        if ( ! empty( $_REQUEST['ids'] ) && preg_match( '/^[\d,]+$/', $_REQUEST['ids'], $request_ids ) ) {
		$ids = explode( ',', $request_ids[0] );
		$sample_post_type = get_post_type( $ids[0] );
		//ewwwio_debug_message( "ids: " . $request_ids[0] );
		ewwwio_debug_message( "post type (checking for ims_gallery): $sample_post_type" );
		if ( 'ims_gallery' == $sample_post_type ) {
			$attachments = array();
			foreach ( $ids as $gid ) {
				ewwwio_debug_message( "gallery id: $gid" );
				$ims_images = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'ims_image' AND post_mime_type LIKE '%%image%%' AND post_parent = $gid ORDER BY ID DESC" );
				$attachments = array_merge( $attachments, $ims_images );
			}
		} else {
	                // retrieve post IDs correlating to the IDs submitted to make sure they are all valid
			$attachments = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE (post_type = 'attachment' OR post_type = 'ims_image') AND post_mime_type LIKE '%%image%%' AND ID IN ({$request_ids[0]}) ORDER BY ID DESC" );
		}
		// unset the 'bulk resume' option since we were given specific IDs to optimize
		update_option( 'ewww_image_optimizer_bulk_resume', '' );
        // check if there is a previous bulk operation to resume
        } elseif ( ! empty( $resume ) ) {
		// retrieve the attachment IDs that have not been finished from the 'bulk attachments' option
		$attachments = get_option( 'ewww_image_optimizer_bulk_attachments' );
	// since we aren't resuming, and weren't given a list of IDs, we will optimize everything
        } else {
                // load up all the image attachments we can find
		$attachments = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE (post_type = 'attachment' OR post_type = 'ims_image') AND post_mime_type LIKE '%%image%%' ORDER BY ID DESC" );
        }
	// store the attachment IDs we retrieved in the 'bulk_attachments' option so we can keep track of our progress in the database
	update_option( 'ewww_image_optimizer_bulk_attachments', $attachments );
	wp_enqueue_script( 'ewwwbulkscript', plugins_url( '/includes/eio.js', __FILE__ ), array( 'jquery', 'jquery-ui-slider', 'jquery-ui-progressbar', 'postbox', 'dashboard' ) );
	$image_count = ewww_image_optimizer_aux_images_table_count();
	// submit a couple variables to the javascript to work with
	wp_localize_script('ewwwbulkscript', 'ewww_vars', array(
			'_wpnonce' => wp_create_nonce( 'ewww-image-optimizer-bulk' ),
			'attachments' => count( $attachments ),
			'image_count' => $image_count,
			'count_string' => sprintf( esc_html__( '%d images', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $image_count ),
			'scan_fail' => esc_html__( 'Operation timed out, you may need to increase the max_execution_time for PHP', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
			'operation_stopped' => esc_html__( 'Optimization stopped, reload page to resume.', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
			'operation_interrupted' => esc_html__( 'Operation Interrupted', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
			'temporary_failure' => esc_html__( 'Temporary failure, seconds left to retry:', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
			'remove_failed' => esc_html__( 'Could not remove image from table.', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
			/* translators: used for Bulk Optimize progress bar, like so: Optimized 32/346 */
			'optimized' => esc_html__( 'Optimized', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
		)
	);
	// load the stylesheet for the jquery progressbar
	wp_enqueue_style( 'jquery-ui-progressbar', plugins_url( '/includes/jquery-ui-1.10.1.custom.css', __FILE__ ) );
	ewwwio_memory( __FUNCTION__ );
}

// find the number of images in the ewwwio_images table
function ewww_image_optimizer_aux_images_table_count() {
	global $wpdb;
	$count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->ewwwio_images" );
	if (!empty($_REQUEST['ewww_inline'])) {
		echo $count;
		ewwwio_memory( __FUNCTION__ );
		die();
	}
	ewwwio_memory( __FUNCTION__ );
	return $count;
	
}

function ewww_image_optimizer_bulk_quota_update() {
	// verify that an authorized user has started the optimizer
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
		wp_die( esc_html__( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' ) ) {
		$verify_cloud = ewww_image_optimizer_cloud_verify( false ); 
		echo esc_html__( 'Image credits available:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . ' ' . ewww_image_optimizer_cloud_quota();
	}
	ewwwio_memory( __FUNCTION__ );
	die();
}

// called by javascript to initialize some output
function ewww_image_optimizer_bulk_initialize() {
	// verify that an authorized user has started the optimizer
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
		wp_die( esc_html__( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	// update the 'bulk resume' option to show that an operation is in progress
	update_option('ewww_image_optimizer_bulk_resume', 'true');
	$attachments = get_option( 'ewww_image_optimizer_bulk_attachments' );
	$attachment = array_shift( $attachments );
	$file = ewww_image_optimizer_bulk_filename( $attachment );
	// generate the WP spinner image for display
	$loading_image = plugins_url('/images/wpspin.gif', __FILE__);
	// let the user know that we are beginning
	if ( $file ) {
		echo "<p>" . esc_html__('Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <b>$file</b>&nbsp;<img src='$loading_image' /></p>";
	} else {
		echo "<p>" . esc_html__('Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "&nbsp;<img src='$loading_image' /></p>";
	}
	ewwwio_memory( __FUNCTION__ );
	die();
}

// called by javascript to output filename of attachment in progress
function ewww_image_optimizer_bulk_filename( $attachment_ID = null ) {
	$meta = wp_get_attachment_metadata( $attachment_ID );
	ewwwio_memory( __FUNCTION__ );
	if ( ! empty( $meta['file'] ) ) {
		return $meta['file'];
	} else {
		return false;
	}
}
 
// called by javascript to process each image in the loop
function ewww_image_optimizer_bulk_loop() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $ewww_exceed;
	global $ewww_defer;
	$ewww_defer = false;
	$output = array();
	// verify that an authorized user has started the optimizer
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
		$output['error'] = esc_html__( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN );
		echo json_encode( $output );
		die();
	}
	// retrieve the time when the optimizer starts
	$started = microtime( true );
	if ( ini_get( 'max_execution_time' ) ) {
		set_time_limit( 0 );
	}
	// get the 'bulk attachments' with a list of IDs remaining
	$attachments = get_option( 'ewww_image_optimizer_bulk_attachments' );
	$attachment = array_shift( $attachments );
	$meta = wp_get_attachment_metadata( $attachment, true );
	// do the optimization for the current attachment (including resizes)
	$meta = ewww_image_optimizer_resize_from_meta_data( $meta, $attachment, false );
	if ( ! empty ( $ewww_exceed ) ) {
		$output['error'] = esc_html__( 'License Exceeded', EWWW_IMAGE_OPTIMIZER_DOMAIN );
		echo json_encode( $output );
		die();
	}
	if ( ! empty ( $meta['file'] ) ) {
		// output the filename (and path relative to 'uploads' folder)
		$output['results'] = sprintf( "<p>" . esc_html__( 'Optimized image:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . " <strong>%s</strong><br>", esc_html( $meta['file']) );
	} else {
		$output['results'] = sprintf( "<p>" . esc_html__( 'Skipped image, ID:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . " <strong>%s</strong><br>", esc_html( $attachment ) );
	}
	if ( ! empty( $meta['ewww_image_optimizer'] ) ) {
		// tell the user what the results were for the original image
		$output['results'] .= sprintf( esc_html__( 'Full size – %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "<br>", esc_html( $meta['ewww_image_optimizer'] ) );
	}
	// check to see if there are resized version of the image
	if ( isset( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
		// cycle through each resize
		foreach ( $meta['sizes'] as $size ) {
			if ( ! empty( $size['ewww_image_optimizer'] ) ) {
				// output the results for the current resized version
				$output['results'] .= sprintf( "%s – %s<br>", esc_html( $size['file'] ), esc_html( $size['ewww_image_optimizer'] ) );
			}
		}
	}
	// calculate how much time has elapsed since we started
	$elapsed = microtime( true ) - $started;
	// output how much time has elapsed since we started
	$output['results'] .= sprintf( esc_html__( 'Elapsed: %.3f seconds', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>", $elapsed );
	global $ewww_attachment;
	$ewww_attachment['id'] = $attachment;
	$ewww_attachment['meta'] = $meta;
	add_filter( 'w3tc_cdn_update_attachment_metadata', 'ewww_image_optimizer_w3tc_update_files' );
	// update the metadata for the current attachment
	wp_update_attachment_metadata( $attachment, $meta );
	// store the updated list of attachment IDs back in the 'bulk_attachments' option
	update_option( 'ewww_image_optimizer_bulk_attachments', $attachments );
	if ( ewww_image_optimizer_get_option ( 'ewww_image_optimizer_debug' ) ) {
		global $ewww_debug;
		$output['results'] .= '<div style="background-color:#ffff99;">' . $ewww_debug . '</div>';
	}
	if ( ! empty( $attachments ) ) {
		$next_attachment = array_shift( $attachments );
		$next_file = ewww_image_optimizer_bulk_filename( $next_attachment );
		// generate the WP spinner image for display
		$loading_image = plugins_url('/images/wpspin.gif', __FILE__);
		if ( $next_file ) {
			$output['next_file'] = "<p>" . esc_html__('Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <b>$next_file</b>&nbsp;<img src='$loading_image' /></p>";
		} else {
			$output['next_file'] = "<p>" . esc_html__('Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "&nbsp;<img src='$loading_image' /></p>";
		}
	}
	echo json_encode( $output );
	ewww_image_optimizer_debug_log();
	ewwwio_memory( __FUNCTION__ );
	die();
}

// called by javascript to cleanup after ourselves
function ewww_image_optimizer_bulk_cleanup() {
	// verify that an authorized user has started the optimizer
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
		wp_die( esc_html__( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	} 
	// all done, so we can update the bulk options with empty values
	update_option('ewww_image_optimizer_bulk_resume', '');
	update_option('ewww_image_optimizer_bulk_attachments', '');
	// and let the user know we are done
	echo '<p><b>' . esc_html__('Finished', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</b> - <a href="upload.php">' . esc_html__('Return to Media Library', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</a></p>';
	ewwwio_memory( __FUNCTION__ );
	die();
}
add_action('admin_enqueue_scripts', 'ewww_image_optimizer_bulk_script');
add_action('wp_ajax_bulk_init', 'ewww_image_optimizer_bulk_initialize');
add_action('wp_ajax_bulk_filename', 'ewww_image_optimizer_bulk_filename');
add_action('wp_ajax_bulk_loop', 'ewww_image_optimizer_bulk_loop');
add_action('wp_ajax_bulk_cleanup', 'ewww_image_optimizer_bulk_cleanup');
add_action('wp_ajax_bulk_quota_update', 'ewww_image_optimizer_bulk_quota_update');
?>
