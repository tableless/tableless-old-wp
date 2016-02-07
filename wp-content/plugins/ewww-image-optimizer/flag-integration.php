<?php 
if ( ! class_exists('ewwwflag')) {
class ewwwflag {
	/* initializes the flagallery integration functions */
	function ewwwflag() {
		add_action('admin_init', array(&$this, 'admin_init'));
		add_filter('flag_manage_images_columns', array(&$this, 'ewww_manage_images_columns'));
		add_action('flag_manage_gallery_custom_column', array(&$this, 'ewww_manage_image_custom_column'), 10, 2);
		if ( current_user_can( apply_filters( 'ewww_image_optimizer_bulk_permissions', '' ) ) ) {
			add_action('flag_manage_images_bulkaction', array(&$this, 'ewww_manage_images_bulkaction'));
			add_action('flag_manage_galleries_bulkaction', array(&$this, 'ewww_manage_galleries_bulkaction'));
			add_action('flag_manage_post_processor_images', array(&$this, 'ewww_flag_bulk'));
			add_action('flag_manage_post_processor_galleries', array(&$this, 'ewww_flag_bulk'));
		}
		//add_action('flag_thumbnail_created', array(&$this, 'ewww_added_new_image'));
		if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_noauto' ) ) {
			add_action('flag_image_optimized', array(&$this, 'ewww_added_new_image'));
			add_action('flag_image_resized', array(&$this, 'ewww_added_new_image'));
		}
		add_action('admin_action_ewww_flag_manual', array(&$this, 'ewww_flag_manual'));
		add_action('admin_menu', array(&$this, 'ewww_flag_bulk_menu'));
		add_action('admin_enqueue_scripts', array(&$this, 'ewww_flag_bulk_script'));
		add_action('wp_ajax_bulk_flag_init', array(&$this, 'ewww_flag_bulk_init'));
		add_action('wp_ajax_bulk_flag_filename', array(&$this, 'ewww_flag_bulk_filename'));
		add_action('wp_ajax_bulk_flag_loop', array(&$this, 'ewww_flag_bulk_loop'));
		add_action('wp_ajax_bulk_flag_cleanup', array(&$this, 'ewww_flag_bulk_cleanup'));
	}

	function admin_init() {
		register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_bulk_flag_resume');
		register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_bulk_flag_attachments');
	}

	/* adds the Bulk Optimize page to the menu */
	function ewww_flag_bulk_menu () {
		add_submenu_page('flag-overview', __('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN), __('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'FlAG Manage gallery', 'flag-bulk-optimize', array (&$this, 'ewww_flag_bulk'));
	}

	/* add bulk optimize action to image management page */
	function ewww_manage_images_bulkaction () {
		echo '<option value="bulk_optimize_images">' . __('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</option>';
	}

	/* add bulk optimize action to gallery management page */
	function ewww_manage_galleries_bulkaction () {
		echo '<option value="bulk_optimize_galleries">' . __('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</option>';
	}

	// Handles the bulk html output
	function ewww_flag_bulk () {
		// if there is POST data, make sure bulkaction and doaction are the values we want
		if (!empty($_POST) && empty($_REQUEST['ewww_reset'])) {
			// if there is no requested bulk action, do nothing
			if (empty($_REQUEST['bulkaction'])) {
				return;
			}
			// if there is no media to optimize, do nothing
			if (empty($_REQUEST['doaction']) || !is_array($_REQUEST['doaction'])) {
				return;
			}
			if (!preg_match('/^bulk_optimize/', $_REQUEST['bulkaction'])) {
				return;
			}
		}
		list($fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count) = ewww_image_optimizer_count_optimized ('flag');
		// bail-out if there aren't any images to optimize
		if ($fullsize_count < 1) {
			echo '<p>' . __('You do not appear to have uploaded any images yet.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</p>';
			return;
		}
//		ewww_image_optimizer_cloud_verify(false); 
		?>
		<div class="wrap"><div id="icon-upload" class="icon32"></div><h2>GRAND FlAGallery <?php _e('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></h2>
		<?php
		// Retrieve the value of the 'bulk resume' option and set the button text for the form to use
		$resume = get_option('ewww_image_optimizer_bulk_flag_resume');
		if (empty($resume)) {
			$button_text = __('Start optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN);
		} else {
			$button_text = __('Resume previous bulk operation', EWWW_IMAGE_OPTIMIZER_DOMAIN);
		}
		?>
		<div id="ewww-bulk-loading"></div>
		<div id="ewww-bulk-progressbar"></div>
		<div id="ewww-bulk-counter"></div>
		<form id="ewww-bulk-stop" style="display:none;" method="post" action="">
			<br /><input type="submit" class="button-secondary action" value="<?php _e('Stop Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?>" />
		</form>
		<div id="ewww-bulk-status"></div>
		<form class="ewww-bulk-form">
			<p><label for="ewww-force" style="font-weight: bold"><?php _e('Force re-optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label>&emsp;<input type="checkbox" id="ewww-force" name="ewww-force"></p>
			<p><label for="ewww-delay" style="font-weight: bold"><?php _e('Choose how long to pause between images (in seconds, 0 = disabled)', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></label>&emsp;<input type="text" id="ewww-delay" name="ewww-delay" value="<?php if ($delay = ewww_image_optimizer_get_option ( 'ewww_image_optimizer_delay' ) ) { echo $delay; } else { echo 0; } ?>"></p>
			<div id="ewww-delay-slider" style="width:50%"></div>
		</form>
		<div id="ewww-bulk-forms">
		<p class="ewww-bulk-info"><?php printf(__('%1$d images have been selected (%2$d unoptimized), with %3$d resizes (%4$d unoptimized).', EWWW_IMAGE_OPTIMIZER_DOMAIN), $fullsize_count, $unoptimized_count, $resize_count, $unoptimized_resize_count); ?><br />
		<?php _e('Previously optimized images will be skipped by default.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></p>
		<form id="ewww-bulk-start" class="ewww-bulk-form" method="post" action="">
			<input type="submit" class="button-secondary action" value="<?php echo $button_text; ?>" />
		</form>
		<?php
		// if there was a previous operation, offer the option to reset the option in the db
		if (!empty($resume)):
		?>
			<p class="ewww-bulk-info"><?php _e('If you would like to start over again, press the Reset Status button to reset the bulk operation status.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></p>
			<form method="post" class="ewww-bulk-form" action="">
				<?php wp_nonce_field( 'ewww-image-optimizer-bulk', 'ewww_wpnonce'); ?>
				<input type="hidden" name="ewww_reset" value="1">
				<button id="bulk-reset" type="submit" class="button-secondary action"><?php _e('Reset Status', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></button>
			</form>
		<?php
		endif;
		echo '</div></div>';
	}

	// prepares the bulk operation and includes the necessary javascript files
	function ewww_flag_bulk_script($hook) {
		// make sure we are being hooked from a valid location
		if ($hook != 'flagallery_page_flag-bulk-optimize' && $hook != 'flagallery_page_flag-manage-gallery')
			return;
		// if there is no requested bulk action, do nothing
		if ($hook == 'flagallery_page_flag-manage-gallery' && (empty($_REQUEST['bulkaction']) || !preg_match('/^bulk_optimize/', $_REQUEST['bulkaction']))) {
			return;
		}
		// if there is no media to optimize, do nothing
		if ($hook == 'flagallery_page_flag-manage-gallery' && (empty($_REQUEST['doaction']) || !is_array($_REQUEST['doaction']))) {
			return;
		}
			//print_r($_REQUEST);
		$ids = null;
		// reset the resume flag if the user requested it
		if (!empty($_REQUEST['ewww_reset'])) {
			update_option('ewww_image_optimizer_bulk_flag_resume', '');
		}
		// get the resume flag from the db
		$resume = get_option('ewww_image_optimizer_bulk_flag_resume');
		// check if we are being asked to optimize galleries or images rather than a full bulk optimize
		if (!empty($_REQUEST['doaction'])) {
			// see if the bulk operation requested is from the manage images page
			if ($_REQUEST['page'] == 'manage-images' && $_REQUEST['bulkaction'] == 'bulk_optimize_images') {
				// check the referring page and nonce
				check_admin_referer('flag_updategallery');
				// we don't allow previous operations to resume if the user is asking to optimize specific images
				update_option('ewww_image_optimizer_bulk_flag_resume', '');
				// retrieve the image IDs from POST
				$ids = array_map( 'intval', $_REQUEST['doaction']);
			}
			// see if the bulk operation requested is from the manage galleries page
			if ($_REQUEST['page'] == 'manage-galleries' && $_REQUEST['bulkaction'] == 'bulk_optimize_galleries') {
				// check the referring page and nonce
				check_admin_referer('flag_bulkgallery');
				global $flagdb;
				// we don't allow previous operations to resume if the user is asking to optimize specific galleries
				update_option('ewww_image_optimizer_bulk_flag_resume', '');
				$ids = array();
				// for each gallery ID, retrieve the image IDs within
				foreach ($_REQUEST['doaction'] as $gid) {
					$gallery_list = $flagdb->get_gallery($gid);
					// for each image ID found, put it onto the $ids array
					foreach ($gallery_list as $image) {
						$ids[] = $image->pid;
					}	
				}
			}
		// if there is an operation to resume, get those IDs from the db
		} elseif (!empty($resume)) {
			$ids = get_option('ewww_image_optimizer_bulk_flag_attachments');
		// otherwise, if we are on the main bulk optimize page, just get all the IDs available
		} elseif ($hook == 'flagallery_page_flag-bulk-optimize') {
			global $wpdb;
			$ids = $wpdb->get_col("SELECT pid FROM $wpdb->flagpictures ORDER BY sortorder ASC");
		}
		// store the IDs to optimize in the options table of the db
		update_option('ewww_image_optimizer_bulk_flag_attachments', $ids);
		// add the EWWW IO javascript
		wp_enqueue_script('ewwwbulkscript', plugins_url('/eio.js', __FILE__), array('jquery', 'jquery-ui-progressbar', 'jquery-ui-slider'));
		// add the styling for the progressbar
		wp_enqueue_style('jquery-ui-progressbar', plugins_url('jquery-ui-1.10.1.custom.css', __FILE__));
		// encode the IDs for javascript use
		$ids = json_encode($ids);
		// prepare a few variables to be used by the javascript code
		wp_localize_script('ewwwbulkscript', 'ewww_vars', array(
				'_wpnonce' => wp_create_nonce('ewww-image-optimizer-bulk'),
				'gallery' => 'flag',
				'attachments' => $ids,
				'license_exceeded' => __( 'License Exceeded', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
				'operation_stopped' => __( 'Optimization stopped, reload page to resume.', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
				'operation_interrupted' => __( 'Operation Interrupted', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
				'temporary_failure' => __( 'Temporary failure, seconds left to retry:', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
				'remove_failed' => __( 'Could not remove image from table.', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
			)
		);
	}
	/* flag_added_new_image hook - optimize newly uploaded images */
	function ewww_added_new_image ($image) {
		ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
		global $ewww_defer;
		// make sure the image path is set
		if (isset($image->imagePath)) {
			// get the image ID
			$pid = $image->pid;
			if ( $ewww_defer && ewww_image_optimizer_get_option( 'ewww_image_optimizer_defer' ) ) {
				ewww_image_optimizer_add_deferred_attachment( "flag,$pid" );
				return;
			}
			// optimize the full size
			$res = ewww_image_optimizer($image->imagePath, 3, false, false, true);
			// optimize the web optimized version
			$wres = ewww_image_optimizer($image->webimagePath, 3, false, true);
			// optimize the thumbnail
			$tres = ewww_image_optimizer($image->thumbPath, 3, false, true);
			// retrieve the metadata for the image ID
			$meta = new flagMeta( $pid );
			ewwwio_debug_message( print_r($meta->image->meta_data, TRUE) );
			$meta->image->meta_data['ewww_image_optimizer'] = $res[1];
			if ( ! empty( $meta->image->meta_data['webview'] ) ) {
				$meta->image->meta_data['webview']['ewww_image_optimizer'] = $wres[1];
			}
			$meta->image->meta_data['thumbnail']['ewww_image_optimizer'] = $tres[1];
			// update the image metadata in the db
			flagdb::update_image_meta($pid, $meta->image->meta_data);
		}
		ewww_image_optimizer_debug_log();
	}

	/* Manually process an image from the gallery */
	function ewww_flag_manual() {
		// make sure the current user has appropriate permissions
		$permissions = apply_filters( 'ewww_image_optimizer_manual_permissions', '' );
		if ( FALSE === current_user_can( $permissions ) ) {
			wp_die(__('You don\'t have permission to work with uploaded files.', EWWW_IMAGE_OPTIMIZER_DOMAIN));
		}
		// make sure we have an attachment ID
		if ( FALSE === isset($_GET['ewww_attachment_ID'])) {
			wp_die(__('No attachment ID was provided.', EWWW_IMAGE_OPTIMIZER_DOMAIN));
		}
		$id = intval($_GET['ewww_attachment_ID']);
		// retrieve the metadata for the image ID
		$meta = new flagMeta( $id );
		// determine the path of the image
		$file_path = $meta->image->imagePath;
		// optimize the full size
		$res = ewww_image_optimizer($file_path, 3, false, false, true);
		$meta->image->meta_data['ewww_image_optimizer'] = $res[1];
		if ( ! empty( $meta->image->meta_data['webview'] ) ) {
			// determine path of the webview
			$web_path = $meta->image->webimagePath;
			$wres = ewww_image_optimizer($web_path, 3, false, true);
			$meta->image->meta_data['webview']['ewww_image_optimizer'] = $wres[1];
		}
		// determine the path of the thumbnail
		$thumb_path = $meta->image->thumbPath;
		// optimize the thumbnail
		$tres = ewww_image_optimizer($thumb_path, 3, false, true);
		$meta->image->meta_data['thumbnail']['ewww_image_optimizer'] = $tres[1];
		// update the metadata for the full-size image
		flagdb::update_image_meta($id, $meta->image->meta_data);
		// get the referring page
		$sendback = wp_get_referer();
		// and clean it up a bit
		$sendback = preg_replace('|[^a-z0-9-~+_.?#=&;,/:]|i', '', $sendback);
		// send the user back where they came from
		wp_redirect($sendback);
		exit(0);
	}

	/* initialize bulk operation */
	function ewww_flag_bulk_init() {
		$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
		if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
			wp_die( __( 'Access denied.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
		}
		// set the resume flag to indicate the bulk operation is in progress
		update_option('ewww_image_optimizer_bulk_flag_resume', 'true');
		$loading_image = plugins_url('/wpspin.gif', __FILE__);
		// output the initial message letting the user know we are starting
		echo "<p>" . __('Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "&nbsp;<img src='$loading_image' alt='loading'/></p>";
		die();
	}

	/* output the filename of the currently optimizing image */
	function ewww_flag_bulk_filename() {
		$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
		if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
			wp_die( __( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
		}
		// need this file to work with flag meta
		require_once(WP_CONTENT_DIR . '/plugins/flash-album-gallery/lib/meta.php');
		$id = $_POST['ewww_attachment'];
		// retrieve the meta for the current ID
		$meta = new flagMeta($id);
		$loading_image = plugins_url('/wpspin.gif', __FILE__);
		// retrieve the filename for the current image ID
		$file_name = esc_html($meta->image->filename);
		// and let the user know which image we are working on currently
		echo "<p>" . __('Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <b>" . $file_name . "</b>&nbsp;<img src='$loading_image' alt='loading'/></p>";
		die();
	}
		
	/* process each image and it's thumbnail during the bulk operation */
	function ewww_flag_bulk_loop() {
		global $ewww_defer;
		$ewww_defer = false;
		$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
		if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
			wp_die( __( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
		}
		if (!empty($_REQUEST['ewww_sleep'])) {
			sleep($_REQUEST['ewww_sleep']);
		}
		// need this file to work with flag meta
		require_once(WP_CONTENT_DIR . '/plugins/flash-album-gallery/lib/meta.php');
		// record the starting time for the current image (in microseconds)
		$started = microtime(true);
		$id = $_POST['ewww_attachment'];
		// get the image meta for the current ID
		$meta = new flagMeta($id);
		$file_path = $meta->image->imagePath;
		// optimize the full-size version
		$fres = ewww_image_optimizer($file_path, 3, false, false, true);
		global $ewww_exceed;
		if ( ! empty ( $ewww_exceed ) ) {
			echo '-9exceeded';
			die();
		}
		$meta->image->meta_data['ewww_image_optimizer'] = $fres[1];
		// let the user know what happened
		printf( "<p>" . __('Optimized image:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <strong>%s</strong><br>", esc_html($meta->image->filename) );
		printf(__('Full size – %s', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "<br>", $fres[1]);
		if ( ! empty( $meta->image->meta_data['webview'] ) ) {
			// determine path of the webview
			$web_path = $meta->image->webimagePath;
			$wres = ewww_image_optimizer($web_path, 3, false, true);
			$meta->image->meta_data['webview']['ewww_image_optimizer'] = $wres[1];
			printf(__('Optimized size – %s', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "<br>", $wres[1]);
		}
		$thumb_path = $meta->image->thumbPath;
		// optimize the thumbnail
		$tres = ewww_image_optimizer($thumb_path, 3, false, true);
		$meta->image->meta_data['thumbnail']['ewww_image_optimizer'] = $tres[1];
		// and let the user know the results
		printf(__('Thumbnail – %s', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "<br>", $tres[1]);
		flagdb::update_image_meta($id, $meta->image->meta_data);
		// determine how much time the image took to process
		$elapsed = microtime(true) - $started;
		// and output it to the user
		printf(__('Elapsed: %.3f seconds', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>", $elapsed);
		// retrieve the list of attachments left to work on
		$attachments = get_option('ewww_image_optimizer_bulk_flag_attachments');
		// take the first image off the list
		if (!empty($attachments))
			array_shift($attachments);
		// and send the list back to the db
		update_option('ewww_image_optimizer_bulk_flag_attachments', $attachments);
		die();
	}

	/* finish the bulk operation, and clear out the bulk_flag options */
	function ewww_flag_bulk_cleanup() {
		$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
		if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
			wp_die( __( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
		}
		// reset the bulk flags in the db
		update_option('ewww_image_optimizer_bulk_flag_resume', '');
		update_option('ewww_image_optimizer_bulk_flag_attachments', '');
		// and let the user know we are done
		echo '<p><b>' . __('Finished Optimization!', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</b></p>';
		die();
	}

	/* flag_manage_images_columns hook - add a column on the gallery display */
	function ewww_manage_images_columns( $columns ) {
		$columns['ewww_image_optimizer'] = __('Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN);
		return $columns;
	}

	/* flag_manage_image_custom_column hook - output the EWWW IO information on the gallery display */
	function ewww_manage_image_custom_column( $column_name, $id ) {
		// check to make sure we're outputing our custom column
		if( $column_name == 'ewww_image_optimizer' ) {
			// get the metadata
			$meta = new flagMeta( $id );
			if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_debug' ) ) {
				$print_meta = print_r( $meta->image->meta_data, TRUE );
				$print_meta = preg_replace( array( '/ /', '/\n+/' ), array( '&nbsp;', '<br />' ), $print_meta );
				echo '<div style="background-color:#ffff99;font-size: 10px;padding: 10px;margin:-10px -10px 10px;line-height: 1.1em">' . $print_meta . '</div>';
			}
			// grab the image status from the meta
			$status = $meta->image->meta_data['ewww_image_optimizer'];
			$msg = '';
			// get the image path from the meta
			$file_path = $meta->image->imagePath;
			// get the mimetype
			$type = ewww_image_optimizer_mimetype( $file_path, 'i' );
			// get the file size
			$file_size = size_format( ewww_image_optimizer_filesize( $file_path ), 2 );
			$file_size = str_replace( 'B ', 'B', $file_size );
			$valid = true;
			// if we don't have a valid tool for the image type, output the appropriate message
	                switch( $type ) {
        	                case 'image/jpeg':
					if( ! EWWW_IMAGE_OPTIMIZER_JPEGTRAN && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_jpg' ) ) {
                        	                $valid = false;
	     	                                $msg = '<br>' . sprintf( __( '%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN ), '<em>jpegtran</em>' );
	                                }
					break;
				case 'image/png':
					if( ! EWWW_IMAGE_OPTIMIZER_PNGOUT && ! EWWW_IMAGE_OPTIMIZER_OPTIPNG && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_png' ) ) {
						$valid = false;
						$msg = '<br>' . sprintf( __( '%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN ), '<em>optipng/pngout</em>' );
					}
					break;
				case 'image/gif':
					if( ! EWWW_IMAGE_OPTIMIZER_GIFSICLE && ! ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_gif' ) ) {
						$valid = false;
						$msg = '<br>' . sprintf( __( '%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN ), '<em>gifsicle</em>' );
					}
					break;
				default:
					$valid = false;
			}
			// let user know if the file type is unsupported
			if( $valid == false ) {
				_e( 'Unsupported file type', EWWW_IMAGE_OPTIMIZER_DOMAIN );
				return;
			}
			// output the image status if we know it
			if ( ! empty( $status ) ) {
				echo $status;
				echo "<br>" . sprintf( __( 'Image Size: %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $file_size );
				if ( current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) )  {
					printf("<br><a href=\"admin.php?action=ewww_flag_manual&amp;ewww_force=1&amp;ewww_attachment_ID=%d\">%s</a>",
						$id,
						__('Re-optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN));
				}
			// otherwise, tell the user that they can optimize the image now
			} else {
				_e('Not processed', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				echo "<br>" . sprintf(__('Image Size: %s', EWWW_IMAGE_OPTIMIZER_DOMAIN), $file_size);
				if ( current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) )  {
					printf("<br><a href=\"admin.php?action=ewww_flag_manual&amp;ewww_attachment_ID=%d\">%s</a>",
						$id,
						__('Optimize now!', EWWW_IMAGE_OPTIMIZER_DOMAIN));
				}
			}
		}
	}
}
}
//add_action( 'init', 'ewwwflag' );

//function ewwwflag() {
	global $ewwwflag;
	$ewwwflag = new ewwwflag();
//}

