<?php
// displays the 'Optimize Everything Else' section of the Bulk Optimize page
function ewww_image_optimizer_aux_images () {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	// Retrieve the value of the 'aux resume' option and set the button text for the form to use
	$aux_resume = get_option( 'ewww_image_optimizer_aux_resume' );
	if ( empty( $aux_resume ) ) {
		$button_text = __( 'Scan and optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN );
	} else {
		$button_text = __( 'Resume previous optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN );
	}
	// find out if the auxiliary image table has anything in it
	$already_optimized = ewww_image_optimizer_aux_images_table_count();
	// see if the auxiliary image table needs converting from md5sums to image sizes
	$convert_query = "SELECT image_md5 FROM $wpdb->ewwwio_images WHERE image_md5 <> ''";
	$db_convert = $wpdb->get_results( $convert_query, ARRAY_N );
	// generate the WP spinner image for display
	$loading_image = plugins_url( '/wpspin.gif', __FILE__ );
	// check the last time the auxiliary optimizer was run
	$lastaux = get_option( 'ewww_image_optimizer_aux_last' );
	// set the timezone according to the blog settings
	$site_timezone = get_option( 'timezone_string' );
	if ( empty( $site_timezone ) ) {
		$site_timezone = 'UTC';
	}
	date_default_timezone_set( $site_timezone );
	?>
	<h3><?php _e( 'Optimize Everything Else', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></h3>
		<div id="ewww-aux-forms"><p class="ewww-bulk-info"><?php _e( 'Use this tool to optimize images outside of the Media Library and galleries where we have full integration. Examples: theme images, BuddyPress, WP Symposium, and any folders that you have specified on the settings page.', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></p>
		<?php if ( ! empty( $db_convert ) ) { ?>
			<p class="ewww-bulk-info"><?php _e( 'The database schema has changed, you need to convert to the new format.', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></p>
			<form method="post" id="ewww-aux-convert" class="ewww-bulk-form" action="">
				<?php wp_nonce_field( 'ewww-image-optimizer-aux-images', 'ewww_wpnonce' ); ?>
				<input type="hidden" name="ewww_convert" value="1">
				<button id="ewww-table-convert" type="submit" class="button-secondary action"><?php _e( 'Convert Table', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></button>
			</form>
		<?php } ?>	
			<p id="ewww-nothing" class="ewww-bulk-info" style="display:none"><?php _e( 'There are no images to optimize.', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></p>
			<p id="ewww-scanning" class="ewww-bulk-info" style="display:none"><?php _e( 'Scanning, this could take a while', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?>&nbsp;<img src='<?php echo $loading_image; ?>' alt='loading'/></p>
		<?php if ( ! empty( $lastaux ) ) { ?>
			<p id="ewww-lastaux" class="ewww-bulk-info"><?php printf( __( 'Last optimization was completed on %1$s at %2$s and optimized %3$d images', EWWW_IMAGE_OPTIMIZER_DOMAIN ), date( get_option( 'date_format' ), $lastaux[0] ), date( get_option( 'time_format' ), $lastaux[0] ), $lastaux[1] ); ?></p>
		<?php } ?>
			<form id="ewww-aux-start" class="ewww-bulk-form" method="post" action="">
				<input id="ewww-aux-first" type="submit" class="button-secondary action" value="<?php echo $button_text; ?>" />
				<input id="ewww-aux-again" type="submit" class="button-secondary action" style="display:none" value="<?php _e( 'Optimize Again', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?>" />
			</form>
<?php		// if the 'bulk resume' option was not empty, offer to reset it so the user can start back from the beginning
		if ( ! empty( $aux_resume ) ) {
?>
			<p class="ewww-bulk-info"><?php _e( 'If you would like to start over again, press the Reset Status button to reset the bulk operation status.', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></p>
			<form id="ewww-aux-reset" class="ewww-bulk-form" method="post" action="">
				<?php wp_nonce_field( 'ewww-image-optimizer-aux-images', 'ewww_wpnonce' ); ?>
				<input type="hidden" name="ewww_reset_aux" value="1">
				<button type="submit" class="button-secondary action"><?php _e( 'Reset Status', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></button>
			</form>
<?php		} 
		if ( empty( $already_optimized ) ) {
			$display = ' style="display:none"';
		} else {
			$display = '';
		}
?>
			<p id="ewww-table-info" class="ewww-bulk-info"<?php echo "$display>"; printf( __( 'The plugin keeps track of already optimized images to prevent re-optimization. There are %d images that have been optimized so far.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $already_optimized ); ?></p>
			<form id="ewww-show-table" class="ewww-bulk-form" method="post" action=""<?php echo $display; ?>>
				<button type="submit" class="button-secondary action"><?php _e( 'Show Optimized Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></button>
			</form>
			<div class="tablenav ewww-aux-table" style="display:none">
			<div class="tablenav-pages ewww-aux-table">
			<span class="displaying-num ewww-aux-table"></span>
			<span id="paginator" class="pagination-links ewww-aux-table">
				<a id="first-images" class="first-page" style="display:none">&laquo;</a>
				<a id="prev-images" class="prev-page" style="display:none">&lsaquo;</a>
				<?php _e( 'page', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?> <span class="current-page"></span> <?php _e( 'of', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?> 
				<span class="total-pages"></span>
				<a id="next-images" class="next-page" style="display:none">&rsaquo;</a>
				<a id="last-images" class="last-page" style="display:none">&raquo;</a>
			</span>
			</div>
			</div>
			<div id="ewww-bulk-table" class="ewww-aux-table"></div>
			<span id="ewww-pointer" style="display:none">0</span>
		</div>
	</div>
<?php
	if ( ewww_image_optimizer_get_option ( 'ewww_image_optimizer_debug' ) ) {
		global $ewww_debug;
		echo '<div id="ewww-debug-info" style="clear:both;background:#ffff99;margin-left:-20px;padding:10px">' . $ewww_debug . '</div>';
	}
	ewwwio_memory( __FUNCTION__ );
}

function ewww_image_optimizer_import_init() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// verify that an authorized user has started the optimizer
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) ) {
		wp_die( __( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	global $wpdb;
	$import_todo = 0;
	$import_status['media'] = 0;
	$import_todo += $wpdb->get_var( "SELECT COUNT(posts.ID) FROM $wpdb->postmeta metas INNER JOIN $wpdb->posts posts ON posts.ID = metas.post_id WHERE posts.post_mime_type LIKE '%image%' AND metas.meta_key = '_wp_attachment_metadata' AND metas.meta_value LIKE '%ewww_image_optimizer%'" );
	if ( ! function_exists( 'is_plugin_active' ) ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if ( is_plugin_active( 'nextgen-gallery/nggallery.php' ) || is_plugin_active_for_network( 'nextgen-gallery/nggallery.php' ) ) {
		$nextgen_data = ewww_image_optimizer_get_plugin_version( trailingslashit( WP_PLUGIN_DIR ) . 'nextgen-gallery/nggallery.php' );
		//$nextgen_data = get_plugin_data( trailingslashit( WP_PLUGIN_DIR ) . 'nextgen-gallery/nggallery.php', false, false );
		ewwwio_debug_message( 'Nextgen version: ' . $nextgen_data['Version'] );
		if ( preg_match( '/^2\./', $nextgen_data['Version'] ) ) { // for Nextgen 2
			$import_todo += $wpdb->get_var( "SELECT COUNT(pid) FROM $wpdb->nggpictures WHERE meta_data LIKE '%ewww_image_optimizer%'" );
			$import_status['nextgen'] = 0;
		}
	}
	if ( is_plugin_active( 'flash-album-gallery/flag.php' ) || is_plugin_active_for_network( 'flash-album-gallery/flag.php' ) ) {
		$import_todo += $wpdb->get_var( "SELECT COUNT(pid) FROM $wpdb->flagpictures WHERE meta_data LIKE '%ewww_image_optimizer%'" );
		$import_status['flag'] = 0;
	}
	update_option( 'ewww_image_optimizer_import_status', $import_status );
	$temp_column = $wpdb->get_var( "SELECT * FROM information_schema.COLUMNS WHERE TABLE_NAME = '$wpdb->ewwwio_images' AND COLUMN_NAME = 'temp'" );
	if ( ! $temp_column ) {
		$wpdb->query( "ALTER TABLE $wpdb->ewwwio_images ADD temp BOOLEAN NOT NULL" );
	}
	echo $import_todo;
	die();
}

// import image status from the media library and other galleries
function ewww_image_optimizer_import_loop() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// verify that an authorized user has started the optimizer
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) ) {
		wp_die( __( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	} 
	global $wpdb;
	// retrieve the time when the optimizer starts
//	$started = microtime(true);
	$import_finished = false;
	$import_status = get_option( 'ewww_image_optimizer_import_status' );
	$attachments = $wpdb->get_results( "SELECT posts.ID,metas.meta_value FROM $wpdb->postmeta metas INNER JOIN $wpdb->posts posts ON posts.ID = metas.post_id WHERE posts.post_mime_type LIKE '%image%' AND metas.meta_key = '_wp_attachment_metadata' AND metas.meta_value LIKE '%ewww_image_optimizer%' LIMIT {$import_status['media']}, 100", ARRAY_N );
	if ( count( $attachments ) === 0 ) {
		$import_finished = true;
	} else {
		$import_status['media'] += count( $attachments );
	}
	$already_optimized = array();
	ewwwio_debug_message( "importing " . count( $attachments ) . " attachments" );
	$insert_query = "INSERT INTO $wpdb->ewwwio_images (path, image_size, orig_size, results, temp) VALUES ";
	$rows = array();
	foreach ( $attachments as $attachment ) {
		$record = array();
		$gallery_type = 0;
		$id = $attachment[0]; 
		$meta = unserialize( $attachment[1] );
		if ( empty( $attachment ) || empty( $attachment[1] ) ) {
			continue;
		}
		list( $attachment, $upload_path ) = ewww_image_optimizer_attachment_path( $meta, $id );
		if ( 'ims_image' == get_post_type( $id ) ) {
			$gallery_type = 6;
		}
		// make sure the meta actually contains data for ewww_image_optimizer
		if ( empty( $meta['ewww_image_optimizer'] ) ) {
			$prev_results = '';
		} else {
			$prev_results = $meta['ewww_image_optimizer'];
		}
		$record = ewww_image_optimizer_import_file( $attachment, $prev_results, $already_optimized );
		if ( ! empty( $record ) ) {
			$rows[] = "('$record[0]', '$record[1]', '$record[2]', '$record[3]', true)";
		}
		// resized versions, so we can continue
		if ( isset( $meta['sizes'] ) ) {
			$record = array();
			ewwwio_debug_message( "processing resizes" );
			// meta sizes don't contain a path, so we calculate one
			if ( $gallery_type === 6 ) {
				$base_dir = trailingslashit( dirname( $attachment ) ) . '_resized/';
			} else {
				$base_dir = trailingslashit( dirname( $attachment ) );
			}
			foreach( $meta['sizes'] as $size => $data ) {
				$resize_path = $base_dir . $data['file'];
				ewwwio_debug_message( "current resize: $resize_path" );
				// make sure the meta actually contains data for ewww_image_optimizer
				if ( empty( $data['ewww_image_optimizer'] ) ) {
					$prev_results = '';
				} else {
					$prev_results = $data['ewww_image_optimizer'];
				}
				$record = ewww_image_optimizer_import_file( $resize_path, $prev_results, $already_optimized );
				if ( ! empty( $record ) ) {
					$rows[] =  "('$record[0]', '$record[1]', '$record[2]', '$record[3]', true)";
				}
			}
		}
		ewww_image_optimizer_debug_log();
	}
	$import_count = $import_status['media'];
	//nextgen import
	if ( $import_finished && isset( $import_status['nextgen'] ) && class_exists( 'C_Component_Registry' ) ) {
		$import_finished = false;
		$images = $wpdb->get_results( "SELECT pid,meta_data,filename,galleryid FROM $wpdb->nggpictures WHERE meta_data LIKE '%ewww_image_optimizer%' LIMIT {$import_status['nextgen']}, 100", ARRAY_N );
		if ( count( $images ) === 0 ) {
			$import_finished = true;
		} else {
			$import_status['nextgen'] += count( $images );
		}
		$galleries = $wpdb->get_results( "SELECT gid,path FROM $wpdb->nggallery", ARRAY_N );
		// creating the 'registry' object for working with nextgen
		$registry = C_Component_Registry::get_instance();
		// creating a database storage object from the 'registry' object
		$storage  = $registry->get_utility( 'I_Gallery_Storage' );
		$sizes = $storage->get_image_sizes();
		foreach ( $images as $image ) {
			$record = array();
			$gallery_path = '';
			foreach ( $galleries as $gallery ) {
				if ( $gallery[0] == $image[3] ) {
					$gallery_path = trailingslashit( $gallery[1] );
				}
			}
			if ( class_exists( 'Ngg_Serializable' ) ) {
		        	$serializer = new Ngg_Serializable();
		        	$meta = $serializer->unserialize( $image[1] );
			} else {
				$meta = unserialize( $image[1] );
			}
			// get an array of sizes available for the $image
			foreach ( $sizes as $size ) {
				// get the absolute path
				if ( $size === 'full' || $size === 'original' || $size === 'image' ) {
					if ( ! empty( $meta['ewww_image_optimizer'] ) ) {
						$file_path = ABSPATH . $gallery_path . $image[2];
						ewwwio_debug_message( "nextgen path generated: $file_path" );
						$record = ewww_image_optimizer_import_file( $file_path, $meta['ewww_image_optimizer'], $already_optimized );
					}
				} elseif ( ! empty( $meta[$size]['ewww_image_optimizer'] ) ) {
					if ( isset( $meta[$size]['filename'] ) ) {
						$file_path = ABSPATH . $gallery_path . trailingslashit( 'thumbs' ) . $meta[$size]['filename'];
					} else {
						$file_path = ABSPATH . $gallery_path . trailingslashit( 'thumbs' ) . 'thumbs_' . $image[2];
					}
					ewwwio_debug_message( "nextgen path generated: $file_path" );
					$record = ewww_image_optimizer_import_file( $file_path, $meta[$size]['ewww_image_optimizer'], $already_optimized );
				} elseif ( ! empty( $meta['ewww_image_optimizer'] ) ) {
					if ( isset( $meta[$size]['filename'] ) ) {
						$file_path = ABSPATH . $gallery_path . trailingslashit( 'thumbs' ) . $meta[$size]['filename'];
					} else {
						$file_path = ABSPATH . $gallery_path . trailingslashit( 'thumbs' ) . 'thumbs_' . $image[2];
					}
					ewwwio_debug_message( "nextgen path generated: $file_path" );
					$meta[$size]['ewww_image_optimizer'] = __( 'Unknown Savings', EWWW_IMAGE_OPTIMIZER_DOMAIN );
					nggdb::update_image_meta( $image[0], $meta );
					$record = ewww_image_optimizer_import_file( $file_path, $meta[$size]['ewww_image_optimizer'], $already_optimized );
				}
				if ( ! empty( $record ) ) {
					$rows[] = "('$record[0]', '$record[1]', '$record[2]', '$record[3]', true)";
				}
			}
			ewww_image_optimizer_debug_log();
		}
		$import_count += $import_status['nextgen'];
	}
	// fla gallery import
	if ( $import_finished && isset( $import_status['flag'] ) ) {
		$import_finished = false;
		$images = $wpdb->get_results( "SELECT pid,meta_data,filename,galleryid FROM $wpdb->flagpictures WHERE meta_data LIKE '%ewww_image_optimizer%' LIMIT {$import_status['flag']}, 100", ARRAY_N );
		$galleries = $wpdb->get_results( "SELECT gid,path FROM $wpdb->flaggallery", ARRAY_N );
		if ( count( $images ) === 0 ) {
			$import_finished = true;
		} else {
			$import_status['flag'] += count( $images );
		}
		// need this file to work with flag meta
		foreach ( $images as $image ) {
			$record = array();
			$gallery_path = '';
			foreach ( $galleries as $gallery ) {
				if ( $gallery[0] == $image[3] ) {
					$gallery_path = trailingslashit($gallery[1]);
				}
			}
			// get the image meta for the current ID
			$meta = unserialize( $image[1] );
			$file_path = ABSPATH . $gallery_path . $image[2];
			ewwwio_debug_message( "flagallery path generated: $file_path" );
			if ( ! empty( $meta['ewww_image_optimizer'] ) ) {
				$record = ewww_image_optimizer_import_file( $file_path, $meta['ewww_image_optimizer'] );
				if ( ! empty( $record ) ) {
					$rows[] = "('$record[0]', '$record[1]', '$record[2]', '$record[3]', true)";
				}
				$thumb_path = ABSPATH . $gallery_path . 'thumbs/thumbs_' . $image[2];
				if ( empty( $meta['thumbnail']['ewww_image_optimizer'] ) ) {
					$meta['thumbnail']['ewww_image_optimizer'] = __( 'Unknown Savings', EWWW_IMAGE_OPTIMIZER_DOMAIN );
					// update the image metadata in the db
					flagdb::update_image_meta( $id, $meta );
					$record = ewww_image_optimizer_import_file( $thumb_path, __( 'Unknown Savings', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				} else {
					$record = ewww_image_optimizer_import_file( $thumb_path, $meta['thumbnail']['ewww_image_optimizer'] );
				}
				if ( ! empty( $record ) ) {
					$rows[] = "('$record[0]', '$record[1]', '$record[2]', '$record[3]', true)";
				}
			}
			ewww_image_optimizer_debug_log();
		}
		$import_count += $import_status['flag'];
	}
	if ( ! empty( $rows ) ) {
		$wpdb->query( $insert_query . implode(', ', $rows) );
		$rows = array();
	}
	if ( $import_finished ) {
		update_option( 'ewww_image_optimizer_imported', true );
		update_option( 'ewww_image_optimizer_import_status', '' );
		$wpdb->query( "ALTER TABLE $wpdb->ewwwio_images DROP temp" );
		echo "<b>" . __( 'Finished importing', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</b>";
	} else {
		update_option( 'ewww_image_optimizer_import_status', $import_status );
		echo $import_count;
	}
//	$elapsed = microtime(true) - $started;
//	echo "<br>importing images took $elapsed seconds<br>";
	die();
}

// needs the previous result (if any), and the file path
function ewww_image_optimizer_import_file( $attachment, $prev_result, $already_optimized_images ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	if ( empty( $prev_result ) ) {
		return;
	}
	$prev_string = " - " . __( 'Previously Optimized', EWWW_IMAGE_OPTIMIZER_DOMAIN );
	$results = preg_replace( "/$prev_string/", '', $prev_result );
	preg_match( '/\((.+)\)/', $results, $savings_size );
	if ( preg_match( '/' . __( 'No savings', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '/', $prev_result ) ) {
		$savings_size = 0;
	} elseif ( empty( $savings_size ) ) {
		return array();
	} else {
		$savings_size = ewww_image_optimizer_size_unformat( $savings_size[1] );
	}
	if ( ! file_exists( $attachment ) || preg_match( '/' . __( 'License exceeded', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '/', $prev_result ) ) {
		return array();
	}
	$query = $wpdb->prepare( "SELECT id,image_size FROM $wpdb->ewwwio_images WHERE BINARY path = %s", $attachment );
	$already_optimized = $wpdb->get_row( $query, ARRAY_A );
	$image_size = filesize( $attachment );
	$orig_size = $image_size + $savings_size;
	ewwwio_debug_message( "current attachment: $attachment" );
	ewwwio_debug_message( "current size: $image_size" );
	ewwwio_debug_message( "original size: $orig_size" );
	if ( ! empty( $already_optimized ) ) {
		ewwwio_debug_message( "stored size:  " . $already_optimized['image_size'] );
	}
	if ( empty( $already_optimized ) ) {
		ewwwio_debug_message( 'creating record' );
		// return info on the current image
		return array( $attachment, $image_size, $orig_size, $results );
	} elseif ( $image_size != $already_optimized['image_size'] ) {
		ewwwio_debug_message( 'updating record' );
		// store info on the current image for future reference
		$wpdb->update( $wpdb->ewwwio_images,
			array(
				'image_size' => $image_size,
				'orig_size' => $orig_size,
				'results' => $results,
			),
			array(
				'id' => $already_optimized->id
			));
	}
	return array();
}

// displays 50 records from the auxiliary images table
function ewww_image_optimizer_aux_images_table() {
	// verify that an authorized user has called function
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) ) {
		wp_die( __( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	} 
	global $wpdb;
	$offset = 50 * $_POST['ewww_offset'];
	$query = "SELECT path,results,gallery,id FROM $wpdb->ewwwio_images ORDER BY id DESC LIMIT $offset,50";
	$already_optimized = $wpdb->get_results( $query, ARRAY_N );
        $upload_info = wp_upload_dir();
	$upload_path = $upload_info['basedir'];
	echo '<br /><table class="wp-list-table widefat media" cellspacing="0"><thead><tr><th>&nbsp;</th><th>' . __( 'Filename', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</th><th>' . __( 'Image Type', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</th><th>' . __( 'Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</th></tr></thead>';
	$alternate = true;
	foreach ( $already_optimized as $optimized_image ) {
		$image_name = str_replace( ABSPATH, '', $optimized_image[0] );
		$image_url = trailingslashit( get_site_url() ) . $image_name;
		$savings = $optimized_image[1];
		// if the path given is not the absolute path
		if ( file_exists( $optimized_image[0] ) ) {
			// retrieve the mimetype of the attachment
			$type = ewww_image_optimizer_mimetype( $optimized_image[0], 'i' );
			// get a human readable filesize
			$file_size = size_format( filesize( $optimized_image[0] ), 2 );
			$file_size = str_replace( '.00 B ', ' B', $file_size );
?>			<tr<?php if ( $alternate ) { echo " class='alternate'"; } ?> id="ewww-image-<?php echo $optimized_image[3]; ?>">
				<td style='width:80px' class='column-icon'><img width='50' height='50' src="<?php echo $image_url; ?>" /></td>
				<td class='title'>...<?php echo $image_name; ?></td>
				<td><?php echo $type; ?></td>
				<td><?php echo "$savings <br>" . sprintf( __( 'Image Size: %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $file_size ); ?><br><a class="removeimage" onclick="ewwwRemoveImage( <?php echo $optimized_image[3]; ?> )"><?php _e( 'Remove from table', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></a></td>
			</tr>
<?php			$alternate = ! $alternate;
		}
	}
	echo '</table>';
	ewwwio_memory( __FUNCTION__ );
	die();
}

// removes an image from the auxiliary images table
function ewww_image_optimizer_aux_images_remove() {
	// verify that an authorized user has called function
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) ) {
		wp_die( __( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	} 
	global $wpdb;
	if ( $wpdb->delete( $wpdb->ewwwio_images, array( 'id' => $_POST['ewww_image_id'] ) ) ) {
		echo "1";
	}
	ewwwio_memory( __FUNCTION__ );
	die();
}

// scan a folder for images and return them as an array
function ewww_image_optimizer_image_scan( $dir ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	$images = array();
	if ( ! is_dir( $dir ) ) {
		return $images;
	}
	ewwwio_debug_message( "scanning folder for images: $dir" );
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir ), RecursiveIteratorIterator::CHILD_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD );
	$start = microtime( true );
	$query = "SELECT path,image_size FROM $wpdb->ewwwio_images";
	$already_optimized = $wpdb->get_results( $query, ARRAY_A );
	$optimized_list = array();
	foreach( $already_optimized as $optimized ) {
		$optimized_path = $optimized['path'];
		$optimized_list[$optimized_path] = $optimized['image_size'];
	}
	$file_counter = 0;
	if ( ewww_image_optimizer_stl_check() ) {
		set_time_limit( 0 );
	}
	foreach ( $iterator as $file ) {
		$file_counter++;
		$skip_optimized = false;
		if ( $file->isFile() ) {
			$path = $file->getPathname();
			if ( preg_match( '/\.(conf|crt|css|docx|eot|exe|git|gitignore|gitmodules|gz|hgignore|hgsub|hgsubstate|hgtags|htaccess|htm|html|ico|ini|js|json|key|less|lock|log|map|md|mo|mp3|mp4|otf|pdf|pem|php|po|pot|scss|sh|svg|svnignore|swf|template|tiff|tmp|tpl|ttf|txt|vcl|woff|woff2|webp|xap|xml|yml|zip)$/', $path ) ) {
				ewwwio_debug_message( "not a usable extension: $path" );
				continue;
			}
/*			$mimetype = ewww_image_optimizer_mimetype( $path, 'i' );
			if ( empty( $mimetype ) || ! preg_match( '/^image\/(jpeg|png|gif)/', $mimetype ) ) {
				ewwwio_debug_message( "not a usable mimetype: $path" );
				continue;
			}*/
			if ( isset( $optimized_list[$path] ) ) {
				$image_size = $file->getSize();
				if ( $optimized_list[ $path ] == $image_size ) {
					ewwwio_debug_message( "match found for $path" );
					$skip_optimized = true;
				} else {
					ewwwio_debug_message( "mismatch found for $path, db says " . $optimized_list[ $path ] . " vs. current $image_size" );
				}
			}
			/*foreach( $already_optimized as $optimized ) {
				if ( $optimized['path'] === $path ) {
					//$image_size = filesize( $path );
					$image_size = $file->getSize();
					if ( $optimized['image_size'] == $image_size ) {
						ewwwio_debug_message( "match found for $path" );
						$skip_optimized = true;
						break;
					} else {
						ewwwio_debug_message( "mismatch found for $path, db says " . $optimized['image_size'] . " vs. current $image_size" );
					}
				}
			}*/
			if ( empty( $skip_optimized ) || ! empty( $_REQUEST['ewww_force'] ) ) {
			//	if ( ! preg_match( '/\.(png|jpg|gif|jpeg)$/', $path ) ) {
				ewwwio_debug_message( "queued $path" );
			//	}
				$images[] = $path;
			}
		}
	}
	$end = microtime( true ) - $start;
        ewwwio_debug_message( "query time for $file_counter files (seconds): $end" );
	ewwwio_memory( __FUNCTION__ );
	return $images;
}

// convert all records in table to use filesize rather than md5sum
function ewww_image_optimizer_aux_images_convert() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	$query = "SELECT id,path,image_md5 FROM $wpdb->ewwwio_images";
	$old_records = $wpdb->get_results( $query, ARRAY_A );
	foreach ( $old_records as $record ) {
		if ( empty( $record['image_md5'] ) ) {
			continue;
		}
		$image_md5 = md5_file( $record['path'] );
		if ( $image_md5 === $record['image_md5'] ) {
			ewwwio_debug_message( 'converting record for: ' . $record['path'] );
			$filesize = filesize( $record['path'] );
			ewwwio_debug_message( 'using size: ' . $filesize );
			$wpdb->update( $wpdb->ewwwio_images,
				array(
					'image_md5' => null,
					'image_size' => $filesize,
				),
				array(
					'id' => $record['id'],
				) );
		} else {
			ewwwio_debug_message( 'deleting record for: ' . $record['path'] );
			$wpdb->delete( $wpdb->ewwwio_images,
				array(
					'id' => $record['id'],
				) );
		}
	}
	ewwwio_memory( __FUNCTION__ );
}

// prepares the bulk operation and includes the javascript functions
function ewww_image_optimizer_aux_images_script( $hook ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// make sure we are being called from the proper page
	if ( 'ewww-image-optimizer-auto' !== $hook && empty( $_REQUEST['ewww_scan'] ) ) {
		return;
	}
	global $wpdb;
	if ( ! empty( $_REQUEST['ewww_force'] ) ) {
		ewwwio_debug_message( 'forcing re-optimize: true' );
	}
	// initialize the $attachments variable for auxiliary images
	$attachments = null;
	// check the 'bulk resume' option
	$resume = get_option( 'ewww_image_optimizer_aux_resume' );
        // check if there is a previous bulk operation to resume
        if ( ! empty( $resume ) ) {
		// retrieve the attachment IDs that have not been finished from the 'bulk attachments' option
		$attachments = get_option( 'ewww_image_optimizer_aux_attachments' );
	} else {
		$attachments = array();
		// collect a list of images from the current theme
		$child_path = get_stylesheet_directory();
		$parent_path = get_template_directory();
		$attachments = ewww_image_optimizer_image_scan( $child_path ); 
		if ( $child_path !== $parent_path ) {
			$attachments = array_merge( $attachments, ewww_image_optimizer_image_scan( $parent_path ) );
		}
		if ( ! function_exists( 'is_plugin_active' ) ) {
			// need to include the plugin library for the is_plugin_active function
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		// collect a list of images for buddypress
		if ( is_plugin_active( 'buddypress/bp-loader.php' ) || is_plugin_active_for_network( 'buddypress/bp-loader.php' ) ) {
			// get the value of the wordpress upload directory
		        $upload_dir = wp_upload_dir();
			// scan the 'avatars' and 'group-avatars' folders for images
			$attachments = array_merge( $attachments, ewww_image_optimizer_image_scan( $upload_dir['basedir'] . '/avatars' ), ewww_image_optimizer_image_scan( $upload_dir['basedir'] . '/group-avatars' ) );
		}
		if ( is_plugin_active( 'buddypress-activity-plus/bpfb.php' ) || is_plugin_active_for_network( 'buddypress-activity-plus/bpfb.php' ) ) {
			// get the value of the wordpress upload directory
		        $upload_dir = wp_upload_dir();
			// scan the 'avatars' and 'group-avatars' folders for images
			$attachments = array_merge( $attachments, ewww_image_optimizer_image_scan( $upload_dir['basedir'] . '/bpfb' ) );
		}
		if ( is_plugin_active( 'grand-media/grand-media.php' ) || is_plugin_active_for_network( 'grand-media/grand-media.php' ) ) {
			// scan the grand media folder for images
			$attachments = array_merge( $attachments, ewww_image_optimizer_image_scan( WP_CONTENT_DIR . '/grand-media' ) );
		}
		if ( is_plugin_active( 'wp-symposium/wp-symposium.php' ) || is_plugin_active_for_network( 'wp-symposium/wp-symposium.php' ) ) {
			$attachments = array_merge( $attachments, ewww_image_optimizer_image_scan( get_option( 'symposium_img_path' ) ) );
		}
		if ( is_plugin_active( 'ml-slider/ml-slider.php' ) || is_plugin_active_for_network( 'ml-slider/ml-slider.php' ) ) {
			$slide_paths = array();
	                $sliders = get_posts( array(
	                        'numberposts' => -1,
	                        'post_type' => 'ml-slider',
				'post_status' => 'any',
				'fields' => 'ids'
	                ) );
			foreach ( $sliders as $slider ) {
				$slides = get_posts( array(
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
				foreach ( $slides as $slide ) {
					$backup_sizes = get_post_meta( $slide, '_wp_attachment_backup_sizes', true );
					$type = get_post_meta( $slide, 'ml-slider_type', true );
					$type = $type ? $type : 'image'; // backwards compatibility, fall back to 'image'
					if ( $type === 'image' ) {
						foreach ( $backup_sizes as $backup_size => $meta ) {
							if ( preg_match( '/resized-/', $backup_size ) ) {
								$path = $meta['path'];
								$image_size = filesize( $path );
								$query = $wpdb->prepare( "SELECT id FROM $wpdb->ewwwio_images WHERE path LIKE %s AND image_size LIKE '$image_size'", $path );
								$optimized_query = $wpdb->get_results( $query, ARRAY_A );
								if ( ! empty( $optimized_query ) ) {
									foreach ( $optimized_query as $image ) {
										if ( $image['path'] != $path ) {
											ewwwio_debug_message( "{$image['path']} does not match $path, continuing our search" );
										} else {
											$already_optimized = $image;
										}
									}
								}
								$mimetype = ewww_image_optimizer_mimetype( $path, 'i' );
								if ( preg_match( '/^image\/(jpeg|png|gif)/', $mimetype ) && empty( $already_optimized ) ) {
									$slide_paths[] = $path;
								}
							}
						}
					}
				}
			}
			$attachments = array_merge( $attachments, $slide_paths );
		}
		// collect a list of images in auxiliary folders provided by user
		if ( $aux_paths = ewww_image_optimizer_get_option( 'ewww_image_optimizer_aux_paths' ) ) {
			foreach ( $aux_paths as $aux_path ) {
				$attachments = array_merge( $attachments, ewww_image_optimizer_image_scan( $aux_path ) );
			}
		}
		// scan images in two most recent media library folders if the option is enabled, and this is a scheduled optimization
		if ( 'ewww-image-optimizer-auto' == $hook && ewww_image_optimizer_get_option( 'ewww_image_optimizer_include_media_paths' ) ) {
			// retrieve the location of the wordpress upload folder
			$upload_dir = wp_upload_dir();
			// retrieve the path of the upload folder
			$upload_path = $upload_dir['basedir'];
			$this_month = date( 'm' );
			$this_year = date( 'Y' );
			$attachments = array_merge( $attachments, ewww_image_optimizer_image_scan( "$upload_path/$this_year/$this_month/" ) );
			if ( class_exists( 'DateTime' ) ) {
				$date = new DateTime();
				$date->sub( new DateInterval( 'P1M' ) );
				$last_year = $date->format( 'Y' );
				$last_month = $date->format( 'm' );
				$attachments = array_merge( $attachments, ewww_image_optimizer_image_scan( "$upload_path/$last_year/$last_month/" ) );
			}

		}
		// store the filenames we retrieved in the 'bulk_attachments' option so we can keep track of our progress in the database
		update_option( 'ewww_image_optimizer_aux_attachments', $attachments );
	}
	ewww_image_optimizer_debug_log();
//	if ( empty( $attachments ) ) {
//		$attachments = '';
//	} else {
		// submit a couple variables to the javascript to work with
		$attachments = json_encode( $attachments );
//	}
	if ( ! empty( $_REQUEST['ewww_scan'] ) ) {
//		if ( empty( $attachments ) ) {
//			_e( 'Nothing to optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN );
//		} else {
			echo $attachments;
//		}
		ewwwio_memory( __FUNCTION__ );
		die();
	} else {
		ewwwio_memory( __FUNCTION__ );
		return;
	}
}

// called by javascript to initialize some output
function ewww_image_optimizer_aux_images_initialize( $auto = false ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// verify that an authorized user has started the optimizer
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	if ( ! $auto && ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) ) {
		wp_die( __( 'Access denied.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	} 
	// update the 'aux resume' option to show that an operation is in progress
	update_option( 'ewww_image_optimizer_aux_resume', 'true' );
	// store the time and number of images for later display
	$count = count( get_option( 'ewww_image_optimizer_aux_attachments' ) );
	update_option( 'ewww_image_optimizer_aux_last', array( time(), $count ) );
	// let the user know that we are beginning
	if ( ! $auto ) {
		// generate the WP spinner image for display
		$loading_image = plugins_url( '/wpspin.gif', __FILE__ );
		echo "<p>" . __( 'Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "&nbsp;<img src='$loading_image' alt='loading'/></p>";
		ewwwio_memory( __FUNCTION__ );
		die();
	}
}

// called by javascript to output filename of attachment in progress
function ewww_image_optimizer_aux_images_filename() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// verify that an authorized user has started the optimizer
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
		wp_die( __( 'Access denied.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	// generate the WP spinner image for display
	$loading_image = plugins_url( '/wpspin.gif', __FILE__ );
	// let the user know that we are beginning
	echo "<p>" . __( 'Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . " <b>" . preg_replace( ":\\\':", "'", $_POST['ewww_attachment'] ) . "</b>&nbsp;<img src='$loading_image' alt='loading'/></p>";
	ewwwio_memory( __FUNCTION__ );
	die();
}
 
// called by javascript to cleanup after ourselves
function ewww_image_optimizer_aux_images_cleanup( $auto = false ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// verify that an authorized user has started the optimizer
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	if ( ! $auto && ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) ) {
		wp_die( __( 'Access denied.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	$stored_last = get_option( 'ewww_image_optimizer_aux_last' );
	update_option( 'ewww_image_optimizer_aux_last', array( time(), $stored_last[1] ) );
	// all done, so we can update the bulk options with empty values
	update_option( 'ewww_image_optimizer_aux_resume', '' );
	update_option( 'ewww_image_optimizer_aux_attachments', '' );
	if ( ! $auto ) {
		// and let the user know we are done
		echo '<p><b>' . __( 'Finished', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</b></p>';
		ewwwio_memory( __FUNCTION__ );
		die();
	}
}

add_action( 'admin_enqueue_scripts', 'ewww_image_optimizer_aux_images_script' );
add_action( 'wp_ajax_bulk_aux_images_scan', 'ewww_image_optimizer_aux_images_script' );
add_action( 'wp_ajax_bulk_aux_images_table', 'ewww_image_optimizer_aux_images_table' );
add_action( 'wp_ajax_bulk_aux_images_table_count', 'ewww_image_optimizer_aux_images_table_count' );
add_action( 'wp_ajax_bulk_aux_images_remove', 'ewww_image_optimizer_aux_images_remove' );
add_action( 'wp_ajax_bulk_aux_images_loading', 'ewww_image_optimizer_aux_images_loading' );
add_action( 'wp_ajax_bulk_aux_images_init', 'ewww_image_optimizer_aux_images_initialize' );
add_action( 'wp_ajax_bulk_aux_images_filename', 'ewww_image_optimizer_aux_images_filename' );
add_action( 'wp_ajax_bulk_aux_images_loop', 'ewww_image_optimizer_aux_images_loop' );
add_action( 'wp_ajax_bulk_aux_images_cleanup', 'ewww_image_optimizer_aux_images_cleanup' );
add_action( 'wp_ajax_bulk_import_init', 'ewww_image_optimizer_import_init' );
add_action( 'wp_ajax_bulk_import_loop', 'ewww_image_optimizer_import_loop' );
?>
