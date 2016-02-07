<?php
/**
 * implements wp-cli extension for bulk optimizing
 */
class EWWWIO_CLI extends WP_CLI_Command {
	/**
	 * Bulk Optimize Images
	 *
	 * ## OPTIONS
	 *
	 * <library>
	 * : valid values are 'all' (default), 'media', 'nextgen', 'flagallery', and 'other'
	 * : media: Media Library only
	 * : nextgen: Nextcellent and NextGEN 2.x
	 * : flagallery: Grand FlaGallery
	 * : other: everything else including theme images and other specified folders
	 *
	 * <delay>
	 * : optional, number of seconds to pause between images
	 *
	 * <force>
	 * : optional, should the plugin re-optimize images that have already been processed.
	 *
	 * <reset>
	 * : optional, start the optimizer back at the beginning instead of resuming from last position
	 *
	 * <noprompt>
	 * : do not prompt, just start optimizing
	 *
	 * ## EXAMPLES
	 *
	 *     wp-cli ewwwio optimize media 5 --force --reset --noprompt
	 *
	 * @synopsis <library> [<delay>] [--force] [--reset] [--noprompt]
	 */
	function optimize( $args, $assoc_args ) {
		global $ewww_defer;
		$ewww_defer = false;
		// because NextGEN hasn't flushed it's buffers...
		while( @ob_end_flush() );
		$library = $args[0];
		if ( empty( $args[1] ) ) {
			$delay = ewww_image_optimizer_get_option ( 'ewww_image_optimizer_delay' );
		} else {
			$delay = $args[1];
		}
		$ewww_reset = false;
		if ( ! empty( $assoc_args['reset'] ) ) {
			$ewww_reset = true;
		}
		if ( ! empty( $assoc_args['force'] ) ) {
			WP_CLI::line( __('Forcing re-optimization of previously processed images.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
			$_REQUEST['ewww_force'] = true;
		}
		WP_CLI::line( sprintf( _x('Optimizing %1$s with a %2$d second pause between images.', 'string will be something like "media" or "nextgen"', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $library, $delay ) );
		// let's get started, shall we?
		ewww_image_optimizer_admin_init();
		// and what shall we do?
		switch( $library ) {
			case 'all':
				if ( $ewww_reset ) {
					update_option('ewww_image_optimizer_bulk_resume', '');
					update_option('ewww_image_optimizer_aux_resume', '');
					update_option('ewww_image_optimizer_bulk_ngg_resume', '');
					update_option('ewww_image_optimizer_bulk_flag_resume', '');
					WP_CLI::line( __('Bulk status has been reset, starting from the beginning.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				}
				WP_CLI::line( __( 'Scanning, this could take a while', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				list( $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count ) = ewww_image_optimizer_count_optimized ('media');
				WP_CLI::line( sprintf( __( '%1$d images in the Media Library have been selected (%2$d unoptimized), with %3$d resizes (%4$d unoptimized).', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count ) );
				if ( class_exists( 'ewwwngg' ) ) {
					global $ngg;
					if ( preg_match( '/^2/', $ngg->version ) ) {
						list( $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count ) = ewww_image_optimizer_count_optimized ('ngg');
						WP_CLI::line( 'Nextgen: ' . sprintf( __( '%1$d images have been selected (%2$d unoptimized), with %3$d resizes (%4$d unoptimized).', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count ) );
					} else {
						$attachments = ewww_image_optimizer_scan_next();
						WP_CLI::line( 'Nextgen: ' . sprintf( __( 'We have %d images to optimize.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), count( $attachments ) ) );
					}
				}
				if ( class_exists( 'ewwwflag' ) ) {
					list( $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count ) = ewww_image_optimizer_count_optimized ('flag');
					WP_CLI::line( 'Flagallery: ' . sprintf( __( '%1$d images have been selected (%2$d unoptimized), with %3$d resizes (%4$d unoptimized).', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count ) );
				}
				$other_attachments = ewww_image_optimizer_scan_other();
				if ( empty( $assoc_args['noprompt'] ) ) {
					WP_CLI::confirm( sprintf( __( '%1$d images in other folders need optimizing.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), count($other_attachments) ) );
				}
				ewww_image_optimizer_bulk_media( $delay );
				if ( class_exists( 'ewwwngg' ) ) {
					global $ngg;
					if ( preg_match( '/^2/', $ngg->version ) ) {
						ewww_image_optimizer_bulk_ngg( $delay );
					} else {
						$attachments = ewww_image_optimizer_scan_next();
						ewww_image_optimizer_bulk_next( $delay, $attachments );
					}
				}
				if ( class_exists( 'ewwwflag' ) ) {
					ewww_image_optimizer_bulk_flag( $delay );
				}
				ewww_image_optimizer_bulk_other( $delay, $other_attachments );
				break;
			case 'media':
				if ( $ewww_reset ) {
					update_option('ewww_image_optimizer_bulk_resume', '');
					WP_CLI::line( __('Bulk status has been reset, starting from the beginning.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				}
				list( $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count ) = ewww_image_optimizer_count_optimized ('media');
				if ( empty( $assoc_args['noprompt'] ) ) {
					WP_CLI::confirm( sprintf( __( '%1$d images in the Media Library have been selected (%2$d unoptimized), with %3$d resizes (%4$d unoptimized).', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count ) );
				}
				ewww_image_optimizer_bulk_media( $delay );
				break;
			case 'nextgen':
				if ( $ewww_reset ) {
					update_option('ewww_image_optimizer_bulk_ngg_resume', '');
					WP_CLI::line( __('Bulk status has been reset, starting from the beginning.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				}
				if ( class_exists( 'ewwwngg' ) ) {
					global $ngg;
					if ( preg_match( '/^2/', $ngg->version ) ) {
						list( $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count ) = ewww_image_optimizer_count_optimized ('ngg');
						if ( empty( $assoc_args['noprompt'] ) ) {
							WP_CLI::confirm( sprintf( __( '%1$d images have been selected (%2$d unoptimized), with %3$d resizes (%4$d unoptimized).', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count ) );
						}
						ewww_image_optimizer_bulk_ngg( $delay );
					} else {
						$attachments = ewww_image_optimizer_scan_next();
						if ( empty( $assoc_args['noprompt'] ) ) {
							WP_CLI::confirm( sprintf( __( 'We have %d images to optimize.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), count( $attachments ) ) );
						}
						ewww_image_optimizer_bulk_next( $delay, $attachments );
					}
				} else {
					WP_CLI::error( __( 'NextGEN/Nextcellent not installed.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				}
				break;
			case 'flagallery':
				if ( $ewww_reset ) {
					update_option('ewww_image_optimizer_bulk_flag_resume', '');
					WP_CLI::line( __('Bulk status has been reset, starting from the beginning.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				}
				if ( class_exists( 'ewwwflag' ) ) {
					list( $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count ) = ewww_image_optimizer_count_optimized ('flag');
					if ( empty( $assoc_args['noprompt'] ) ) {
						WP_CLI::confirm( sprintf( __( '%1$d images have been selected (%2$d unoptimized), with %3$d resizes (%4$d unoptimized).', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count ) );
					}
					ewww_image_optimizer_bulk_flag( $delay );
				} else {
					WP_CLI::error( __( 'Grand Flagallery not installed.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				}
				break;
			case 'other':
				if ( $ewww_reset ) {
					update_option('ewww_image_optimizer_aux_resume', '');
					WP_CLI::line( __('Bulk status has been reset, starting from the beginning.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				}
				WP_CLI::line( __( 'Scanning, this could take a while', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				$other_attachments = ewww_image_optimizer_scan_other();
				if ( empty( $assoc_args['noprompt'] ) ) {
					WP_CLI::confirm( sprintf( __( '%1$d images in other folders need optimizing.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), count($other_attachments) ) );
				}
				ewww_image_optimizer_bulk_other( $delay, $other_attachments );
				break;
			default:
				if ( $ewww_reset ) {
					update_option('ewww_image_optimizer_bulk_resume', '');
					update_option('ewww_image_optimizer_aux_resume', '');
					update_option('ewww_image_optimizer_bulk_ngg_resume', '');
					update_option('ewww_image_optimizer_bulk_flag_resume', '');
					WP_CLI::success( __('Bulk status has been reset, the next bulk operation will start from the beginning.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				} else {
					WP_CLI::line( __('Please specify a valid library option, see "wp-cli help ewwwio optimize" for more information.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				}
		}
	}
}

WP_CLI::add_command( 'ewwwio', 'EWWWIO_CLI' );

// prepares the bulk operation and includes the javascript functions
function ewww_image_optimizer_bulk_media( $delay = 0 ) {
        $attachments = null;
        // check if there is a previous bulk operation to resume
        if ( get_option('ewww_image_optimizer_bulk_resume') ) {
		// retrieve the attachment IDs that have not been finished from the 'bulk attachments' option
		$attachments = get_option('ewww_image_optimizer_bulk_attachments');
	// since we aren't resuming, and weren't given a list of IDs, we will optimize everything
        } else {
                // load up all the image attachments we can find
                $attachments = get_posts( array(
                        'numberposts' => -1,
                        'post_type' => array('attachment', 'ims_image'),
			'post_status' => 'any',
                        'post_mime_type' => 'image',
			'fields' => 'ids'
                ));
        }
	// store the attachment IDs we retrieved in the 'bulk_attachments' option so we can keep track of our progress in the database
	update_option('ewww_image_optimizer_bulk_attachments', $attachments);
	// update the 'bulk resume' option to show that an operation is in progress
	update_option('ewww_image_optimizer_bulk_resume', 'true');
	foreach ($attachments as $attachment_ID) {
		// get the 'bulk attachments' with a list of IDs remaining
		$attachments_left = get_option('ewww_image_optimizer_bulk_attachments');
		$meta = wp_get_attachment_metadata( $attachment_ID );
		if ( ! empty( $meta['file'] ) ) {
			// let the user know the file we are currently optimizing
			WP_CLI::line( __('Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " {$meta['file']}:" );
		}
		sleep( $delay );
		// retrieve the time when the optimizer starts
		$started = microtime(true);
		// do the optimization for the current attachment (including resizes)
		$meta = ewww_image_optimizer_resize_from_meta_data ( $meta, $attachment_ID, false );
		if ( empty ( $meta['file'] ) ) {
			WP_CLI::warning( __( 'Skipped image, ID:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . " $attachment" );
		}
		if ( ! empty( $meta['ewww_image_optimizer'] ) ) {
			// tell the user what the results were for the original image
			WP_CLI::line( sprintf( __( 'Full size – %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), html_entity_decode( $meta['ewww_image_optimizer'] ) ) );
		}
		// check to see if there are resized version of the image
		if ( isset($meta['sizes']) && is_array( $meta['sizes'] ) ) {
			// cycle through each resize
			foreach ( $meta['sizes'] as $size ) {
				if ( ! empty( $size['ewww_image_optimizer'] ) ) {
					// output the results for the current resized version
					WP_CLI::line( "{$size['file']} – " . html_entity_decode( $size['ewww_image_optimizer'] ) );
				}
			}
		}
		// calculate how much time has elapsed since we started
		$elapsed = microtime(true) - $started;
		// output how much time has elapsed since we started
		WP_CLI::line( sprintf( __( 'Elapsed: %.3f seconds', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $elapsed ) );
		// update the metadata for the current attachment
		wp_update_attachment_metadata( $attachment_ID, $meta );
		// remove the first element from the $attachments array
		if ( ! empty( $attachments_left ) ) {
			array_shift( $attachments_left );
		}
		// store the updated list of attachment IDs back in the 'bulk_attachments' option
		update_option('ewww_image_optimizer_bulk_attachments', $attachments_left);
	}
	// all done, so we can update the bulk options with empty values
	update_option('ewww_image_optimizer_bulk_resume', '');
	update_option('ewww_image_optimizer_bulk_attachments', '');
	// and let the user know we are done
	WP_CLI::success( __('Finished Optimization!', EWWW_IMAGE_OPTIMIZER_DOMAIN) );
}

// displays the 'Optimize Everything Else' section of the Bulk Optimize page
function ewww_image_optimizer_scan_other () {
	global $wpdb;
//	$aux_resume = get_option('ewww_image_optimizer_aux_resume');
	// initialize the $attachments variable for auxiliary images
	$attachments = null;
	// check the 'bulk resume' option
//	$resume = get_option('ewww_image_optimizer_aux_resume');
        // check if there is a previous bulk operation to resume
        if ( get_option( 'ewww_image_optimizer_aux_resume' ) ) {
		// retrieve the attachment IDs that have not been finished from the 'bulk attachments' option
		$attachments = get_option('ewww_image_optimizer_aux_attachments');
	} else {
		// collect a list of images from the current theme
		$child_path = get_stylesheet_directory();
		$parent_path = get_template_directory();
		$attachments = ewww_image_optimizer_image_scan($child_path); 
		if ($child_path !== $parent_path) {
			$attachments = array_merge($attachments, ewww_image_optimizer_image_scan($parent_path));
		}
		// collect a list of images for buddypress
		if ( ! function_exists( 'is_plugin_active' ) ) {
			// need to include the plugin library for the is_plugin_active function
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		if (is_plugin_active('buddypress/bp-loader.php') || is_plugin_active_for_network('buddypress/bp-loader.php')) {
			// get the value of the wordpress upload directory
		        $upload_dir = wp_upload_dir();
			// scan the 'avatars' and 'group-avatars' folders for images
			$attachments = array_merge($attachments, ewww_image_optimizer_image_scan($upload_dir['basedir'] . '/avatars'), ewww_image_optimizer_image_scan($upload_dir['basedir'] . '/group-avatars'));
		}
		if (is_plugin_active('buddypress-activity-plus/bpfb.php') || is_plugin_active_for_network('buddypress-activity-plus/bpfb.php')) {
			// get the value of the wordpress upload directory
		        $upload_dir = wp_upload_dir();
			// scan the 'avatars' and 'group-avatars' folders for images
			$attachments = array_merge($attachments, ewww_image_optimizer_image_scan($upload_dir['basedir'] . '/bpfb'));
		}
		if (is_plugin_active('grand-media/grand-media.php') || is_plugin_active_for_network('grand-media/grand-media.php')) {
			// scan the grand media folder for images
			$attachments = array_merge($attachments, ewww_image_optimizer_image_scan(WP_CONTENT_DIR . '/grand-media'));
		}
		if (is_plugin_active('wp-symposium/wp-symposium.php') || is_plugin_active_for_network('wp-symposium/wp-symposium.php')) {
			$attachments = array_merge($attachments, ewww_image_optimizer_image_scan(get_option('symposium_img_path')));
		}
		if (is_plugin_active('ml-slider/ml-slider.php') || is_plugin_active_for_network('ml-slider/ml-slider.php')) {
			$slide_paths = array();
	                $sliders = get_posts(array(
	                        'numberposts' => -1,
	                        'post_type' => 'ml-slider',
				'post_status' => 'any',
				'fields' => 'ids'
	                ));
			foreach ($sliders as $slider) {
				$slides = get_posts(array(
	                        	'numberposts' => -1,
					'orderby' => 'menu_order',
					'order' => 'ASC',
					'post_type' => 'attachment',
					'post_status' => 'inherit',
					'fields' => 'ids',
					'tax_query' => array(
							array(
								'taxonomy' => 'ml-slider',
								'field' => 'slug',
								'terms' => $slider
							)
						)
					)
				);
				foreach ($slides as $slide) {
					$backup_sizes = get_post_meta($slide, '_wp_attachment_backup_sizes', true);
					$type = get_post_meta($slide, 'ml-slider_type', true);
					$type = $type ? $type : 'image'; // backwards compatibility, fall back to 'image'
					if ($type === 'image') {
						foreach ($backup_sizes as $backup_size => $meta) {
							if (preg_match('/resized-/', $backup_size)) {
								$path = $meta['path'];
								$image_size = filesize($path);
								$query = $wpdb->prepare("SELECT id FROM $wpdb->ewwwio_images WHERE path LIKE %s AND image_size LIKE '$image_size'", $path);
								$optimized_query = $wpdb->get_results( $query, ARRAY_A );
								if (!empty($optimized_query)) {
									foreach ( $optimized_query as $image ) {
										if ( $image['path'] == $path ) {
										//	$ewww_debug .= "{$image['path']} does not match $path, continuing our search<br>";
											$already_optimized = $image;
										}
									}
								}
								$mimetype = ewww_image_optimizer_mimetype($path, 'i');
								if (preg_match('/^image\/(jpeg|png|gif)/', $mimetype) && empty($already_optimized)) {
									$slide_paths[] = $path;
								}
							}
						}
					}
				}
			}
			$attachments = array_merge($attachments, $slide_paths);
		}
		// collect a list of images in auxiliary folders provided by user
		if ( $aux_paths = ewww_image_optimizer_get_option( 'ewww_image_optimizer_aux_paths' ) ) {
			foreach ($aux_paths as $aux_path) {
				$attachments = array_merge($attachments, ewww_image_optimizer_image_scan($aux_path));
			}
		}
		// store the filenames we retrieved in the 'bulk_attachments' option so we can keep track of our progress in the database
		update_option('ewww_image_optimizer_aux_attachments', $attachments);
	}
	return $attachments;
}

function ewww_image_optimizer_bulk_other( $delay = 0, $attachments ) {
	// update the 'aux resume' option to show that an operation is in progress
	update_option('ewww_image_optimizer_aux_resume', 'true');
	// store the time and number of images for later display
	$count = count( $attachments );
	update_option('ewww_image_optimizer_aux_last', array(time(), $count));
	foreach ( $attachments as $attachment ) {
		sleep($delay);
		// retrieve the time when the optimizer starts
		$started = microtime(true);
		// get the 'aux attachments' with a list of attachments remaining
		$attachments_left = get_option('ewww_image_optimizer_aux_attachments');
		// do the optimization for the current image
		$results = ewww_image_optimizer($attachment, 4, false, false);
		// remove the first element fromt the $attachments array
		if (!empty($attachments_left)) {
			array_shift($attachments_left);
		}
		// store the updated list of attachment IDs back in the 'bulk_attachments' option
		update_option('ewww_image_optimizer_aux_attachments', $attachments_left);
		// output the path
		WP_CLI::line( __('Optimized image:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' ' . esc_html($attachment) );
		// tell the user what the results were for the original image
		WP_CLI::line( html_entity_decode( $results[1] ) );
		// calculate how much time has elapsed since we started
		$elapsed = microtime(true) - $started;
		// output how much time has elapsed since we started
		WP_CLI::line( sprintf( __( 'Elapsed: %.3f seconds', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $elapsed) );
	} 
	$stored_last = get_option('ewww_image_optimizer_aux_last');
	update_option('ewww_image_optimizer_aux_last', array(time(), $stored_last[1]));
	// all done, so we can update the bulk options with empty values
	update_option('ewww_image_optimizer_aux_resume', '');
	update_option('ewww_image_optimizer_aux_attachments', '');
	// and let the user know we are done
	WP_CLI::success( __('Finished Optimization!', EWWW_IMAGE_OPTIMIZER_DOMAIN) );
}

function ewww_image_optimizer_bulk_flag( $delay = 0 ) {
	$ids = null;
	// if there is an operation to resume, get those IDs from the db
	if ( get_option('ewww_image_optimizer_bulk_flag_resume') ) {
		$ids = get_option('ewww_image_optimizer_bulk_flag_attachments');
	// otherwise, if we are on the main bulk optimize page, just get all the IDs available
	} else {
		global $wpdb;
		$ids = $wpdb->get_col("SELECT pid FROM $wpdb->flagpictures ORDER BY sortorder ASC");
	}
	// store the IDs to optimize in the options table of the db
	update_option('ewww_image_optimizer_bulk_flag_attachments', $ids);
	// set the resume flag to indicate the bulk operation is in progress
	update_option('ewww_image_optimizer_bulk_flag_resume', 'true');
	// need this file to work with flag meta
	require_once(WP_CONTENT_DIR . '/plugins/flash-album-gallery/lib/meta.php');
	foreach ( $ids as $id ) {
		sleep( $delay );
		// record the starting time for the current image (in microseconds)
		$started = microtime(true);
		// retrieve the meta for the current ID
		$meta = new flagMeta($id);
		$file_path = $meta->image->imagePath;
		// optimize the full-size version
		$fres = ewww_image_optimizer($file_path, 3, false, false, true);
		$meta->image->meta_data['ewww_image_optimizer'] = $fres[1];
		// let the user know what happened
		WP_CLI::line( __( 'Optimized image:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . " " . esc_html($meta->image->filename) );
		WP_CLI::line( sprintf( __( 'Full size – %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), html_entity_decode( $fres[1] ) ) );
		if ( ! empty( $meta->image->meta_data['webview'] ) ) {
			// determine path of the webview
			$web_path = $meta->image->webimagePath;
			$wres = ewww_image_optimizer($web_path, 3, false, true);
			$meta->image->meta_data['webview']['ewww_image_optimizer'] = $wres[1];
			WP_CLI::line( sprintf( __( 'Optimized size – %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), html_entity_decode( $wres[1] ) ) );
		}
		$thumb_path = $meta->image->thumbPath;
		// optimize the thumbnail
		$tres = ewww_image_optimizer($thumb_path, 3, false, true);
		$meta->image->meta_data['thumbnail']['ewww_image_optimizer'] = $tres[1];
		// and let the user know the results
		WP_CLI::line( sprintf( __( 'Thumbnail – %s', EWWW_IMAGE_OPTIMIZER_DOMAIN), html_entity_decode( $tres[1] ) ) );
		flagdb::update_image_meta($id, $meta->image->meta_data);
		// determine how much time the image took to process
		$elapsed = microtime(true) - $started;
		// and output it to the user
		WP_CLI::line( sprintf( __( 'Elapsed: %.3f seconds', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $elapsed ) );
		// retrieve the list of attachments left to work on
		$attachments = get_option('ewww_image_optimizer_bulk_flag_attachments');
		// take the first image off the list
		if (!empty($attachments))
			array_shift($attachments);
		// and send the list back to the db
		update_option('ewww_image_optimizer_bulk_flag_attachments', $attachments);
	}
	// reset the bulk flags in the db
	update_option('ewww_image_optimizer_bulk_flag_resume', '');
	update_option('ewww_image_optimizer_bulk_flag_attachments', '');
	// and let the user know we are done
	WP_CLI::success( __('Finished Optimization!', EWWW_IMAGE_OPTIMIZER_DOMAIN) );
}

function ewww_image_optimizer_scan_ngg() {
	$images = null;
	// see if there is a previous operation to resume
//	$resume = get_option('ewww_image_optimizer_bulk_ngg_resume');
	// if we've been given a bulk action to perform
	// otherwise, if we have an operation to resume
	if ( get_option('ewww_image_optimizer_bulk_ngg_resume') ) {
		// get the list of attachment IDs from the db
		$images = get_option('ewww_image_optimizer_bulk_ngg_attachments');
	// otherwise, get all the images in the db
	} else {
		global $wpdb;
		$images = $wpdb->get_col("SELECT pid FROM $wpdb->nggpictures ORDER BY sortorder ASC");
	}
	// store the image IDs to process in the db
	update_option('ewww_image_optimizer_bulk_ngg_attachments', $images);
	return $images;
}

function ewww_image_optimizer_bulk_ngg( $delay = 0 ) {
	if ( get_option('ewww_image_optimizer_bulk_ngg_resume') ) {
		// get the list of attachment IDs from the db
		$images = get_option('ewww_image_optimizer_bulk_ngg_attachments');
	// otherwise, get all the images in the db
	} else {
		global $wpdb;
		$images = $wpdb->get_col("SELECT pid FROM $wpdb->nggpictures ORDER BY sortorder ASC");
	}
	// store the image IDs to process in the db
	update_option('ewww_image_optimizer_bulk_ngg_attachments', $images);
	// toggle the resume flag to indicate an operation is in progress
	update_option('ewww_image_optimizer_bulk_ngg_resume', 'true');
	global $ewwwngg;
	foreach ( $images as $id ) {
		sleep( $delay );
		// find out what time we started, in microseconds
		$started = microtime(true);
		// creating the 'registry' object for working with nextgen
		$registry = C_Component_Registry::get_instance();
		// creating a database storage object from the 'registry' object
		$storage  = $registry->get_utility('I_Gallery_Storage');
		// get an image object
		$image = $storage->object->_image_mapper->find($id);
		$image = $ewwwngg->ewww_added_new_image ($image, $storage);
		// output the results of the optimization
		WP_CLI::line( __('Optimized image:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " " . basename($storage->object->get_image_abspath($image, 'full')));
		// get an array of sizes available for the $image
		$sizes = $storage->get_image_sizes();
		// output the results for each $size
		foreach ($sizes as $size) {
			if ($size === 'full') {
				WP_CLI::line( sprintf( __( 'Full size - %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), html_entity_decode( $image->meta_data['ewww_image_optimizer'] ) ) );
			} elseif ($size === 'thumbnail') {
				// output the results of the thumb optimization
				WP_CLI::line( sprintf( __( 'Thumbnail - %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), html_entity_decode( $image->meta_data[$size]['ewww_image_optimizer'] ) ) );
			} else {
				// output savings for any other sizes, if they ever exist...
				WP_CLI::line( ucfirst($size) . " - " . html_entity_decode( $image->meta_data[$size]['ewww_image_optimizer'] ) );
			}
		}
		// outupt how much time we spent
		$elapsed = microtime(true) - $started;
		WP_CLI::line( sprintf( __( 'Elapsed: %.3f seconds', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $elapsed ) );
		// get the list of attachments remaining from the db
		$attachments = get_option('ewww_image_optimizer_bulk_ngg_attachments');
		// remove the first item
		if (!empty($attachments))
			array_shift($attachments);
		// and store the list back in the db
		update_option('ewww_image_optimizer_bulk_ngg_attachments', $attachments);
	}

	// reset all the bulk options in the db
	update_option('ewww_image_optimizer_bulk_ngg_resume', '');
	update_option('ewww_image_optimizer_bulk_ngg_attachments', '');
	// and let the user know we are done
	WP_CLI::success( __('Finished Optimization!', EWWW_IMAGE_OPTIMIZER_DOMAIN) );
}

function ewww_image_optimizer_scan_next() {
		$images = null;
		// see if there is a previous operation to resume
//		$resume = get_option('ewww_image_optimizer_bulk_ngg_resume');
		// otherwise, if we have an operation to resume
		if ( get_option('ewww_image_optimizer_bulk_ngg_resume') ) {
			// get the list of attachment IDs from the db
			$images = get_option('ewww_image_optimizer_bulk_ngg_attachments');
		// otherwise, if we are on the standard bulk page, get all the images in the db
		} else {
			//$ewww_debug .= "starting from scratch, grabbing all the images<br />";
			global $wpdb;
			$images = $wpdb->get_col("SELECT pid FROM $wpdb->nggpictures ORDER BY sortorder ASC");
		}
		
		// store the image IDs to process in the db
		update_option('ewww_image_optimizer_bulk_ngg_attachments', $images);
	return $images;
}

function ewww_image_optimizer_bulk_next( $delay, $attachments ) {
	// toggle the resume flag to indicate an operation is in progress
	update_option('ewww_image_optimizer_bulk_ngg_resume', 'true');
	// need this file to work with metadata
	require_once(WP_CONTENT_DIR . '/plugins/nextcellent-gallery-nextgen-legacy/lib/meta.php');
	foreach ( $attachments as $id ) {
		sleep( $delay );
		// find out what time we started, in microseconds
		$started = microtime(true);
		// get the metadata
		$meta = new nggMeta($id);
		// retrieve the filepath
		$file_path = $meta->image->imagePath;
		// run the optimizer on the current image
		$fres = ewww_image_optimizer($file_path, 2, false, false, true);
		// update the metadata of the optimized image
		nggdb::update_image_meta($id, array('ewww_image_optimizer' => $fres[1]));
		// output the results of the optimization
		WP_CLI::line( __( 'Optimized image:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . $meta->image->filename );
		WP_CLI::line( sprintf( __( 'Full size - %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), html_entity_decode( $fres[1] ) ) );
		// get the filepath of the thumbnail image
		$thumb_path = $meta->image->thumbPath;
		// run the optimization on the thumbnail
		$tres = ewww_image_optimizer($thumb_path, 2, false, true);
		// output the results of the thumb optimization
		WP_CLI::line( sprintf( __( 'Thumbnail - %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), html_entity_decode( $tres[1] ) ) );
		// outupt how much time we spent
		$elapsed = microtime(true) - $started;
		WP_CLI::line( sprintf( __( 'Elapsed: %.3f seconds', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $elapsed ) );
		// get the list of attachments remaining from the db
		$attachments = get_option('ewww_image_optimizer_bulk_ngg_attachments');
		// remove the first item
		if (!empty($attachments))
			array_shift($attachments);
		// and store the list back in the db
		update_option('ewww_image_optimizer_bulk_ngg_attachments', $attachments);
	}
	// reset all the bulk options in the db
	update_option('ewww_image_optimizer_bulk_ngg_resume', '');
	update_option('ewww_image_optimizer_bulk_ngg_attachments', '');
	// and let the user know we are done
	WP_CLI::success( __('Finished Optimization!', EWWW_IMAGE_OPTIMIZER_DOMAIN) );
}

