<?php
// displays the 'Optimize Everything Else' section of the Bulk Optimize page
function ewww_image_optimizer_aux_images () {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	// Retrieve the value of the 'aux resume' option and set the button text for the form to use
	$aux_resume = get_option( 'ewww_image_optimizer_aux_resume' );
	if ( empty( $aux_resume ) ) {
		$button_text = esc_attr__( 'Scan and optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN );
	} else {
		$button_text = esc_attr__( 'Resume previous optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN );
	}
	// find out if the auxiliary image table has anything in it
	$already_optimized = ewww_image_optimizer_aux_images_table_count();
	// see if the auxiliary image table needs converting from md5sums to image sizes
	$column_query = "SHOW COLUMNS FROM $wpdb->ewwwio_images LIKE 'image_md5'";
	$column = $wpdb->get_row( $column_query, ARRAY_N );
	if ( ! empty( $column ) ) {
		ewwwio_debug_message( "image_md5 column exists, checking for image_md5 values"  );
		$convert_query = "SELECT image_md5 FROM $wpdb->ewwwio_images WHERE image_md5 <> ''";
		$db_convert = $wpdb->get_results( $convert_query, ARRAY_N );
	}
//	ewwwio_debug_message( print_r( $column, true ) );
	// generate the WP spinner image for display
	$loading_image = plugins_url( '/images/wpspin.gif', __FILE__ );
	// check the last time the auxiliary optimizer was run
	$lastaux = get_option( 'ewww_image_optimizer_aux_last' );
	// set the timezone according to the blog settings
	$site_timezone = get_option( 'timezone_string' );
	if ( empty( $site_timezone ) ) {
		$site_timezone = 'UTC';
	}
	date_default_timezone_set( $site_timezone );
	?>
	<h2><?php esc_html_e( 'Optimize Everything Else', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></h2>
		<div id="ewww-aux-forms"><p class="ewww-bulk-info"><?php esc_html_e( 'Use this tool to optimize images outside of the Media Library and galleries where we have full integration. Examples: theme images, BuddyPress, WP Symposium, and any folders that you have specified on the settings page.', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></p>
		<?php if ( ! empty( $db_convert ) ) { ?>
			<p class="ewww-bulk-info"><?php esc_html_e( 'The database schema has changed, you need to convert to the new format.', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></p>
			<form method="post" id="ewww-aux-convert" class="ewww-bulk-form" action="">
				<?php wp_nonce_field( 'ewww-image-optimizer-aux-images', 'ewww_wpnonce' ); ?>
				<input type="hidden" name="ewww_convert" value="1">
				<button id="ewww-table-convert" type="submit" class="button-secondary action"><?php esc_html_e( 'Convert Table', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></button>
			</form>
		<?php } ?>	
			<p id="ewww-nothing" class="ewww-bulk-info" style="display:none"><?php esc_html_e( 'There are no images to optimize.', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></p>
			<p id="ewww-scanning" class="ewww-bulk-info" style="display:none"><?php esc_html_e( 'Scanning, this could take a while', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?>&nbsp;<img src='<?php echo $loading_image; ?>' alt='loading'/></p>
		<?php if ( ! empty( $lastaux ) ) { ?>
			<p id="ewww-lastaux" class="ewww-bulk-info"><?php printf( esc_html__( 'Last optimization was completed on %1$s at %2$s and optimized %3$d images', EWWW_IMAGE_OPTIMIZER_DOMAIN ), date( get_option( 'date_format' ), $lastaux[0] ), date( get_option( 'time_format' ), $lastaux[0] ), $lastaux[1] ); ?></p>
		<?php } ?>
			<form id="ewww-aux-start" class="ewww-bulk-form" method="post" action="">
				<input id="ewww-aux-first" type="submit" class="button-secondary action" value="<?php echo $button_text; ?>" />
				<input id="ewww-aux-again" type="submit" class="button-secondary action" style="display:none" value="<?php esc_attr_e( 'Optimize Again', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?>" />
			</form>
<?php		// if the 'bulk resume' option was not empty, offer to reset it so the user can start back from the beginning
		if ( ! empty( $aux_resume ) ) {
?>
			<p id="ewww-aux-reset-desc" class="ewww-bulk-info"><?php esc_html_e( 'If you would like to start over again, press the Reset Status button to reset the bulk operation status.', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></p>
			<form id="ewww-aux-reset" class="ewww-bulk-form" method="post" action="">
				<?php wp_nonce_field( 'ewww-image-optimizer-aux-images', 'ewww_wpnonce' ); ?>
				<input type="hidden" name="ewww_reset_aux" value="1">
				<button type="submit" class="button-secondary action"><?php esc_html_e( 'Reset Status', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></button>
			</form>
<?php		} 
		if ( empty( $already_optimized ) ) {
			$display = ' style="display:none"';
		} else {
			$display = '';
		}
?>
			<p id="ewww-table-info" class="ewww-bulk-info"<?php echo "$display>"; printf( esc_html__( 'The plugin keeps track of already optimized images to prevent re-optimization. There are %d images that have been optimized so far.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $already_optimized ); ?></p>
			<form id="ewww-show-table" class="ewww-bulk-form" method="post" action=""<?php echo $display; ?>>
				<button type="submit" class="button-secondary action"><?php esc_html_e( 'Show Optimized Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></button>
			</form>
			<div class="tablenav ewww-aux-table" style="display:none">
			<div class="tablenav-pages ewww-aux-table">
			<span class="displaying-num ewww-aux-table"></span>
			<span id="paginator" class="pagination-links ewww-aux-table">
				<a id="first-images" class="first-page" style="display:none">&laquo;</a>
				<a id="prev-images" class="prev-page" style="display:none">&lsaquo;</a>
				<?php esc_html_e( 'page', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?> <span class="current-page"></span> <?php esc_html_e( 'of', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?> 
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

// displays 50 records from the auxiliary images table
function ewww_image_optimizer_aux_images_table() {
	// verify that an authorized user has called function
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) ) {
		wp_die( esc_html__( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	} 
	global $wpdb;
	$offset = 50 * (int) $_POST['ewww_offset'];
	$query = "SELECT path,results,image_size,id FROM $wpdb->ewwwio_images ORDER BY id DESC LIMIT $offset,50";
	$already_optimized = $wpdb->get_results( $query, ARRAY_N );
        $upload_info = wp_upload_dir();
	$upload_path = $upload_info['basedir'];
	echo '<br /><table class="wp-list-table widefat media" cellspacing="0"><thead><tr><th>&nbsp;</th><th>' . esc_html__( 'Filename', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</th><th>' . esc_html__( 'Image Type', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</th><th>' . esc_html__( 'Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</th></tr></thead>';
	$alternate = true;
	foreach ( $already_optimized as $optimized_image ) {
		$image_name = str_replace( ABSPATH, '', $optimized_image[0] );
		$image_url = esc_url( trailingslashit( get_site_url() ) . $image_name );
		$savings = esc_html( $optimized_image[1] );
		// if the path given is not the absolute path
		if ( file_exists( $optimized_image[0] ) ) {
			// retrieve the mimetype of the attachment
			$type = ewww_image_optimizer_mimetype( $optimized_image[0], 'i' );
			// get a human readable filesize
			$file_size = size_format( $optimized_image[2], 2 );
			$file_size = str_replace( '.00 B ', ' B', $file_size );
?>			<tr<?php if ( $alternate ) { echo " class='alternate'"; } ?> id="ewww-image-<?php echo $optimized_image[3]; ?>">
				<td style='width:80px' class='column-icon'><img width='50' height='50' src="<?php echo $image_url; ?>" /></td>
				<td class='title'>...<?php echo $image_name; ?></td>
				<td><?php echo $type; ?></td>
				<td><?php echo "$savings <br>" . sprintf( esc_html__( 'Image Size: %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $file_size ); ?><br><a class="removeimage" onclick="ewwwRemoveImage( <?php echo $optimized_image[3]; ?> )"><?php esc_html_e( 'Remove from table', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></a></td>
			</tr>
<?php			$alternate = ! $alternate;
		} elseif ( strpos( $optimized_image[0], 's3' ) === 0 ) {
			// retrieve the mimetype of the attachment
			$type = esc_html__( 'Amazon S3 image', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			// get a human readable filesize
			$file_size = size_format( $optimized_image[2], 2 );
			$file_size = str_replace( '.00 B ', ' B', $file_size );
?>			<tr<?php if ( $alternate ) { echo " class='alternate'"; } ?> id="ewww-image-<?php echo $optimized_image[3]; ?>">
				<td style='width:80px' class='column-icon'>&nbsp;</td>
				<td class='title'><?php echo $image_name; ?></td>
				<td><?php echo $type; ?></td>
				<td><?php echo "$savings <br>" . sprintf( esc_html__( 'Image Size: %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $file_size ); ?><br><a class="removeimage" onclick="ewwwRemoveImage( <?php echo $optimized_image[3]; ?> )"><?php esc_html_e( 'Remove from table', EWWW_IMAGE_OPTIMIZER_DOMAIN ); ?></a></td>
			</tr>
<?php			$alternate = ! $alternate;
		}
	}
	echo '</table>';
	ewwwio_memory( __FUNCTION__ );
	ewww_image_optimizer_debug_log();
	die();
}

// removes an image from the auxiliary images table
function ewww_image_optimizer_aux_images_remove() {
	// verify that an authorized user has called function
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) ) {
		wp_die( esc_html__( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
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
		$optimized_list[ $optimized_path ] = $optimized['image_size'];
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
			if ( preg_match( '/(\/|\\\\)\./', $path ) && apply_filters( 'ewww_image_optimizer_ignore_hidden_files', true ) ) {
				continue;
			}
			if ( preg_match( '/\.(conf|crt|css|docx|eot|exe|git|gitignore|gitmodules|gz|hgignore|hgsub|hgsubstate|hgtags|htaccess|htm|html|ico|ini|js|json|key|less|lock|log|map|md|mo|mp3|mp4|otf|pdf|pem|php|po|pot|sample|scss|sh|svg|svnignore|swf|template|tiff|tmp|tpl|ttf|txt|url|vcl|woff|woff2|webp|xap|xml|yml|zip)$/', $path ) ) {
				continue;
			}
			if ( ! preg_match( '/\./', $path ) ) {
				continue;
			}
			if ( isset( $optimized_list[$path] ) ) {
				$image_size = $file->getSize();
				if ( $optimized_list[ $path ] == $image_size ) {
					ewwwio_debug_message( "match found for $path" );
					$skip_optimized = true;
				} else {
					ewwwio_debug_message( "mismatch found for $path, db says " . $optimized_list[ $path ] . " vs. current $image_size" );
				}
			}
			if ( empty( $skip_optimized ) || ! empty( $_REQUEST['ewww_force'] ) ) {
				ewwwio_debug_message( "queued $path" );
				$images[] = $path;
			}
		}
		ewww_image_optimizer_debug_log();
	}
	$end = microtime( true ) - $start;
        ewwwio_debug_message( "query time for $file_counter files (seconds): $end" );
	ewwwio_memory( __FUNCTION__ );
	return $images;
}

// convert all records in table to use filesize rather than md5sum
function ewww_image_optimizer_aux_images_convert() {
	global $wpdb;
	$query = "SELECT id,path,image_md5 FROM $wpdb->ewwwio_images";
	$old_records = $wpdb->get_results( $query, ARRAY_A );
	foreach ( $old_records as $record ) {
		if ( empty( $record['image_md5'] ) ) {
			continue;
		}
		$image_md5 = md5_file( $record['path'] );
		if ( $image_md5 === $record['image_md5'] ) {
			$filesize = filesize( $record['path'] );
			$wpdb->update( $wpdb->ewwwio_images,
				array(
					'image_md5' => null,
					'image_size' => $filesize,
				),
				array(
					'id' => $record['id'],
				) );
		} else {
			$wpdb->delete( $wpdb->ewwwio_images,
				array(
					'id' => $record['id'],
				) );
		}
	}
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
		ewwwio_debug_message( 'resuming from where we left off, no scanning needed' );
		// retrieve the attachment IDs that have not been finished from the 'bulk attachments' option
		$attachments = get_option( 'ewww_image_optimizer_aux_attachments' );
	} else {
		ewwwio_debug_message( 'getting fresh list of files to optimize' );
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
			$slides = $wpdb->get_col( 
				"
				SELECT wpposts.ID 
				FROM $wpdb->posts wpposts 
				INNER JOIN $wpdb->term_relationships term_relationships
						ON wpposts.ID = term_relationships.object_id
				INNER JOIN $wpdb->terms wpterms 
						ON term_relationships.term_taxonomy_id = wpterms.term_id
				INNER JOIN $wpdb->term_taxonomy term_taxonomy
						ON wpterms.term_id = term_taxonomy.term_id
				WHERE 	term_taxonomy.taxonomy = 'ml-slider'
					AND wpposts.post_type = 'attachment'
				"
			);
			foreach ( $slides as $slide ) {
				$backup_sizes = get_post_meta( $slide, '_wp_attachment_backup_sizes', true );
				$type = get_post_meta( $slide, 'ml-slider_type', true );
				$type = $type ? $type : 'image'; // backwards compatibility, fall back to 'image'
				if ( $type === 'image' ) {
					foreach ( $backup_sizes as $backup_size => $meta ) {
						if ( preg_match( '/resized-/', $backup_size ) ) {
							$path = $meta['path'];
							$image_size = ewww_image_optimizer_filesize( $path );
							if ( ! $image_size ) {
								continue;
							}
							$query = $wpdb->prepare( "SELECT id,path FROM $wpdb->ewwwio_images WHERE path LIKE %s AND image_size LIKE '$image_size'", $path );
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
		ewwwio_debug_message( 'found ' . count( $attachments ) . ' images to optimize while scanning' );
	}
	ewww_image_optimizer_debug_log();
	if ( ! empty( $_REQUEST['ewww_scan'] ) ) {
		echo count( $attachments );
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
		wp_die( esc_html__( 'Access denied.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	} 
	// update the 'aux resume' option to show that an operation is in progress
	update_option( 'ewww_image_optimizer_aux_resume', 'true' );
	// store the time and number of images for later display
	$count = count( get_option( 'ewww_image_optimizer_aux_attachments' ) );
	update_option( 'ewww_image_optimizer_aux_last', array( time(), $count ) );
	// let the user know that we are beginning
	if ( ! $auto ) {
		// generate the WP spinner image for display
		$loading_image = plugins_url( '/images/wpspin.gif', __FILE__ );
		$attachments = get_option( 'ewww_image_optimizer_aux_attachments' );
		$file = array_shift( $attachments );
		echo "<p>" . esc_html__( 'Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . " <b>$file</b>&nbsp;<img src='$loading_image' alt='loading'/></p>";
		ewwwio_memory( __FUNCTION__ );
		die();
	}
}

// called by javascript to cleanup after ourselves
function ewww_image_optimizer_aux_images_cleanup( $auto = false ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// verify that an authorized user has started the optimizer
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	if ( ! $auto && ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) ) {
		wp_die( esc_html__( 'Access denied.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	$stored_last = get_option( 'ewww_image_optimizer_aux_last' );
	update_option( 'ewww_image_optimizer_aux_last', array( time(), $stored_last[1] ) );
	// all done, so we can update the bulk options with empty values
	update_option( 'ewww_image_optimizer_aux_resume', '' );
	update_option( 'ewww_image_optimizer_aux_attachments', '' );
	if ( ! $auto ) {
		// and let the user know we are done
		echo '<p><b>' . esc_html__( 'Finished', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</b></p>';
		ewwwio_memory( __FUNCTION__ );
		die();
	}
}

add_action( 'admin_enqueue_scripts', 'ewww_image_optimizer_aux_images_script' );
add_action( 'wp_ajax_bulk_aux_images_scan', 'ewww_image_optimizer_aux_images_script' );
add_action( 'wp_ajax_bulk_aux_images_table', 'ewww_image_optimizer_aux_images_table' );
add_action( 'wp_ajax_bulk_aux_images_table_count', 'ewww_image_optimizer_aux_images_table_count' );
add_action( 'wp_ajax_bulk_aux_images_remove', 'ewww_image_optimizer_aux_images_remove' );
add_action( 'wp_ajax_bulk_aux_images_init', 'ewww_image_optimizer_aux_images_initialize' );
add_action( 'wp_ajax_bulk_aux_images_loop', 'ewww_image_optimizer_aux_images_loop' );
add_action( 'wp_ajax_bulk_aux_images_cleanup', 'ewww_image_optimizer_aux_images_cleanup' );
?>
