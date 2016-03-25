<?php 
if ( ! class_exists('ewwwngg')) {
class ewwwngg {
	/* initializes the nextgen integration functions */
	function ewwwngg() {
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'ngg_manage_images_columns', array( &$this, 'ewww_manage_images_columns' ) );
		add_action( 'ngg_manage_image_custom_column', array( &$this, 'ewww_manage_image_custom_column' ), 10, 2 );
		if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_noauto' ) ) {
			add_action( 'ngg_added_new_image', array( &$this, 'ewww_added_new_image' ) );
		}
		add_action( 'admin_action_ewww_ngg_manual', array( &$this, 'ewww_ngg_manual' ) );
		add_action( 'admin_menu', array( &$this, 'ewww_ngg_bulk_menu' ) );
		add_action( 'admin_head-galleries_page_nggallery-manage-gallery', array( &$this, 'ewww_ngg_bulk_actions_script' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'ewww_ngg_bulk_script' ), 9 );
		add_action( 'wp_ajax_bulk_ngg_preview', array( &$this, 'ewww_ngg_bulk_preview' ) );
		add_action( 'wp_ajax_bulk_ngg_init', array( &$this, 'ewww_ngg_bulk_init' ) );
		add_action( 'wp_ajax_bulk_ngg_filename', array( &$this, 'ewww_ngg_bulk_filename' ) );
		add_action( 'wp_ajax_bulk_ngg_loop', array( &$this, 'ewww_ngg_bulk_loop' ) );
		add_action( 'wp_ajax_bulk_ngg_cleanup', array( &$this, 'ewww_ngg_bulk_cleanup' ) );
		add_action( 'wp_ajax_ewww_ngg_thumbs', array( &$this, 'ewww_ngg_thumbs_only' ) );
		add_action( 'ngg_ajax_image_save', array( &$this, 'ewww_ngg_image_save' ) );
	}

	function admin_init() {
		register_setting( 'ewww_image_optimizer_options', 'ewww_image_optimizer_bulk_ngg_resume' );
		register_setting( 'ewww_image_optimizer_options', 'ewww_image_optimizer_bulk_ngg_attachments' );
	}

	/* adds the Bulk Optimize page to the tools menu, and a hidden page for optimizing thumbnails */
	function ewww_ngg_bulk_menu () {
			add_submenu_page( NGGFOLDER, esc_html__( 'Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ), esc_html__( 'Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ), 'NextGEN Manage gallery', 'ewww-ngg-bulk', array( &$this, 'ewww_ngg_bulk_preview' ) );
	}

	/* ngg_added_new_image hook */
	function ewww_added_new_image( $image ) {
		// query the filesystem path of the gallery from the database
		global $ewww_defer;
		global $wpdb;
		$q = $wpdb->prepare( "SELECT path FROM {$wpdb->prefix}ngg_gallery WHERE gid = %d LIMIT 1", $image['galleryID'] );
		$gallery_path = $wpdb->get_var( $q );
		// if we have a path to work with
		if ( $gallery_path ) {
			// construct the absolute path of the current image
			$file_path = trailingslashit( $gallery_path ) . $image['filename'];
			if ( $ewww_defer && ewww_image_optimizer_get_option( 'ewww_image_optimizer_defer' ) ) {
				ewww_image_optimizer_add_deferred_attachment( "nextcellent,{$image['id']}" );
				return;
			}
			// run the optimizer on the current image
			$res = ewww_image_optimizer(ABSPATH . $file_path, 2, false, false, true);
			// update the metadata for the optimized image
			nggdb::update_image_meta($image['id'], array('ewww_image_optimizer' => $res[1]));
		}
	}

	function ewww_ngg_image_save( $filename ) {
		ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
		global $ewww_defer;
		if ( file_exists( $filename ) ) {
			if ( $ewww_defer && ewww_image_optimizer_get_option( 'ewww_image_optimizer_defer' ) ) {
				ewww_image_optimizer_add_deferred_attachment( "file,$filename" );
				return $saved;
			}
			ewww_image_optimizer($filename);
			ewwwio_debug_message( "ngg_Thumbnail saved: $filename" );
			$image_size = ewww_image_optimizer_filesize( $filename );
			ewwwio_debug_message( "image editor size: $image_size" );
		}
		ewww_image_optimizer_debug_log();
		ewwwio_memory( __FUNCTION__ );
	}

	/* Manually process an image from the NextGEN Gallery */
	function ewww_ngg_manual() {
		// check permission of current user
		$permissions = apply_filters( 'ewww_image_optimizer_manual_permissions', '' );
		if ( FALSE === current_user_can( $permissions ) ) {
			wp_die( esc_html__( "You do not have permission to work with uploaded files.", EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
		}
		// make sure function wasn't called without an attachment to work with
		if ( empty( $_GET['ewww_attachment_ID'] ) ) {
			wp_die( esc_html__( 'No attachment ID was provided.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
		}
		// store the attachment $id
		$id = intval( $_GET['ewww_attachment_ID'] );
		if ( empty( $_REQUEST['ewww_manual_nonce'] ) || ! wp_verify_nonce( $_REQUEST['ewww_manual_nonce'], "ewww-manual-$id" ) ) {
			wp_die( esc_html__( 'Access denied.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
		}
		$this->ewww_ngg_optimize( $id );
		ewww_image_optimizer_debug_log();
		// get the referring page, and send the user back there
		$sendback = wp_get_referer();
		$sendback = preg_replace( '|[^a-z0-9-~+_.?#=&;,/:]|i', '', $sendback );
		wp_redirect( $sendback );
		exit(0);
	}

	/* optimize an image by ID */
	function ewww_ngg_optimize( $id ) {
		// retrieve the metadata for the image
		$meta = new nggMeta( $id );
		// retrieve the image path
		$file_path = $meta->image->imagePath;
		// run the optimizer on the current image
		$fres = ewww_image_optimizer($file_path, 2, false, false, true);
		// update the metadata for the optimized image
		nggdb::update_image_meta($id, array('ewww_image_optimizer' => $fres[1]));
		// get the filepath of the thumbnail image
		$thumb_path = $meta->image->thumbPath;
		// run the optimization on the thumbnail
		$tres = ewww_image_optimizer($thumb_path, 2, false, true);
		return array( $fres, $tres );
	}

	/* ngg_manage_images_columns hook */
	function ewww_manage_images_columns( $columns ) {
		$columns['ewww_image_optimizer'] = esc_html__('Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN);
		return $columns;
	}

	/* ngg_manage_image_custom_column hook */
	function ewww_manage_image_custom_column( $column_name, $id ) {
		// once we've found our custom column
		if( $column_name == 'ewww_image_optimizer' ) {    
			// get the metadata for the image
			$meta = new nggMeta( $id );
			// get the optimization status for the image
			$status = $meta->get_META( 'ewww_image_optimizer' );
			$msg = '';
			// get the file path of the image
			$file_path = $meta->image->imagePath;
			// get the mimetype of the image
			$type = ewww_image_optimizer_mimetype($file_path, 'i');
			// retrieve the human-readable filesize of the image
			$file_size = size_format( ewww_image_optimizer_filesize( $file_path ), 2 );
			$file_size = str_replace( 'B ', 'B', $file_size );
			//$file_size = ewww_image_optimizer_format_bytes(filesize($file_path));
			$valid = true;
			// check to see if we have a tool to handle the mimetype detected
	                switch($type) {
        	                case 'image/jpeg':
					// if jpegtran is missing, tell the user
					if( ! EWWW_IMAGE_OPTIMIZER_JPEGTRAN && ! ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg')) {
                        	                $valid = false;
	     	                                $msg = '<br>' . wp_kses( sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>jpegtran</em>'), array( 'em' => array() ) );
	                                }
					break;
				case 'image/png':
					// if the PNG tools are missing, tell the user
					if( ! EWWW_IMAGE_OPTIMIZER_PNGOUT && ! EWWW_IMAGE_OPTIMIZER_OPTIPNG && ! ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png')) {
						$valid = false;
						$msg = '<br>' . wp_kses( sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>optipng/pngout</em>'), array( 'em' => array() ) );
					}
					break;
				case 'image/gif':
					// if gifsicle is missing, tell the user
					if(!EWWW_IMAGE_OPTIMIZER_GIFSICLE && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_gif')) {
						$valid = false;
						$msg = '<br>' . wp_kses( sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>gifsicle</em>'), array( 'em' => array() ) );
					}
					break;
				default:
					$valid = false;
			}
			// file isn't in a format we can work with, we don't work with strangers
			if ( $valid == false ) {
				echo esc_html__( 'Unsupported file type', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . $msg;
				return;
			}
			$ewww_manual_nonce = wp_create_nonce( "ewww-manual-" . $id );
			// if we have a valid status, display it, the image size, and give a re-optimize link
			if ( ! empty ( $status ) ) {
				echo esc_html( $status );
				echo "<br>" . sprintf(esc_html__('Image Size: %s', EWWW_IMAGE_OPTIMIZER_DOMAIN), $file_size);
				if ( current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) )  {
					printf("<br><a href=\"admin.php?action=ewww_ngg_manual&amp;ewww_manual_nonce=$ewww_manual_nonce&amp;ewww_force=1&amp;ewww_attachment_ID=%d\">%s</a>",
						$id,
						esc_html__('Re-optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN));
				}
			// otherwise, give the image size, and a link to optimize right now
			} else {
				esc_html_e('Not processed', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				echo "<br>" . sprintf(esc_html__('Image Size: %s', EWWW_IMAGE_OPTIMIZER_DOMAIN), $file_size);
				if ( current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) )  {
					printf("<br><a href=\"admin.php?action=ewww_ngg_manual&amp;ewww_manual_nonce=$ewww_manual_nonce&amp;ewww_attachment_ID=%d\">%s</a>",
						$id,
						esc_html__('Optimize now!', EWWW_IMAGE_OPTIMIZER_DOMAIN));
				}
			}
		}
	}

	/* output the html for the bulk optimize page */
	function ewww_ngg_bulk_preview() {
		if ( ! empty( $_REQUEST['doaction'] ) ) {
                        // if there is no requested bulk action, do nothing
                        if ( empty( $_REQUEST[ 'bulkaction'] ) ) {
                                return;
                        }
                        // if there is no media to optimize, do nothing
                        if ( empty( $_REQUEST['doaction'] ) || ! is_array( $_REQUEST['doaction'] ) ) {
                              return;
                        }
                }
		// retrieve the attachments array from the db
                $attachments = get_option('ewww_image_optimizer_bulk_ngg_attachments');
		// make sure there are some attachments to process
                if (count($attachments) < 1) {
                        echo '<p>' . esc_html__('You do not appear to have uploaded any images yet.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</p>';
                        return;
                }
                ?>
		<div class="wrap">
                <h1><?php esc_html_e('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN);
			if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' ) ) {
				$verify_cloud = ewww_image_optimizer_cloud_verify( false ); 
				echo '<a id="ewww-bulk-credits-available" target="_blank" class="page-title-action" style="float:right;" href="https://ewww.io/my-account/">' . esc_html__( 'Image credits available:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . ' ' . ewww_image_optimizer_cloud_quota() . '</a>';
			}
		echo '</h1>';
                // Retrieve the value of the 'bulk resume' option and set the button text for the form to use
                $resume = get_option('ewww_image_optimizer_bulk_ngg_resume');
                if (empty($resume)) {
                        $button_text = esc_attr__('Start optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN);
                } else {
                        $button_text = esc_attr__('Resume previous bulk operation', EWWW_IMAGE_OPTIMIZER_DOMAIN);
                }
                ?>
                <div id="ewww-bulk-loading"></div>
                <div id="ewww-bulk-progressbar"></div>
                <div id="ewww-bulk-counter"></div>
		<form id="ewww-bulk-stop" style="display:none;" method="post" action="">
			<br /><input type="submit" class="button-secondary action" value="<?php esc_attr_e('Stop Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?>" />
		</form>
		<div id="ewww-bulk-widgets" class="metabox-holder" style="display:none">
			<div class="meta-box-sortables">
				<div id="ewww-bulk-last" class="postbox">
					<button type="button" class="handlediv button-link" aria-expanded="true">
						<span class="screen-reader-text"><?php esc_html_e( 'Click to toggle', EWWW_IMAGE_OPTIMIZER_DOMAIN ) ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
					<h2 class="hndle"><span><?php esc_html_e( 'Last Image Optimized', EWWW_IMAGE_OPTIMIZER_DOMAIN ) ?></span></h2>
					<div class="inside"></div>
				</div>
			</div>
			<div class="meta-box-sortables">
				<div id="ewww-bulk-status" class="postbox">
					<button type="button" class="handlediv button-link" aria-expanded="true">
						<span class="screen-reader-text"><?php esc_html_e( 'Click to toggle', EWWW_IMAGE_OPTIMIZER_DOMAIN ) ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
					<h2 class="hndle"><span><?php esc_html_e( 'Optimization Log', EWWW_IMAGE_OPTIMIZER_DOMAIN ) ?></span></h2>
					<div class="inside"></div>
				</div>
			</div>
		</div>
                <div id="ewww-bulk-forms">
                <p class="ewww-bulk-info"><?php printf(esc_html__('We have %d images to optimize.', EWWW_IMAGE_OPTIMIZER_DOMAIN), count($attachments)); ?><br />
		<?php esc_html_e('Previously optimized images will be skipped by default.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></p>
                <form id="ewww-bulk-start" class="ewww-bulk-form" method="post" action="">
			<input type="hidden" id="ewww-delay" name="ewww-delay" value="0">
                        <input type="submit" class="button-secondary action" value="<?php echo $button_text; ?>" />
                </form>
                <?php
		// if there is a previous bulk operation to resume, give the user the option to reset the resume flag
                if ( ! empty( $resume ) ) { ?>
                        <p class="ewww-bulk-info"><?php esc_html_e('If you would like to start over again, press the Reset Status button to reset the bulk operation status.', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></p>
                        <form id="ewww-bulk-reset" class="ewww-bulk-form" method="post" action="">
                                <?php wp_nonce_field( 'ewww-image-optimizer-bulk-reset', 'ewww_wpnonce'); ?>
                                <input type="hidden" name="ewww_reset" value="1">
                                <input type="submit" class="button-secondary action" value="<?php esc_attr_e('Reset Status', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?>" />
                        </form>
<?php           }
	        echo '</div></div>';
		if ( ! empty( $_REQUEST['ewww_inline'] ) ) {
			die();
		}
		return;
	}

	/* prepares the javascript for a bulk operation */
	function ewww_ngg_bulk_script( $hook ) {
		ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
		$i18ngg = strtolower ( __( 'Galleries', 'nggallery' ) );
		ewwwio_debug_message( "i18n string for galleries: $i18ngg" );
		// make sure we are on a legitimate page and that we have the proper POST variables if necessary
		if ( $hook != $i18ngg . '_page_ewww-ngg-bulk' && $hook != $i18ngg . '_page_nggallery-manage-gallery' )
				return;
		if ( $hook == $i18ngg . '_page_nggallery-manage-gallery' && ( empty( $_REQUEST['bulkaction'] ) || $_REQUEST['bulkaction'] != 'bulk_optimize') )
				return;
		if ( $hook == $i18ngg . '_page_nggallery-manage-gallery' && ( empty( $_REQUEST['doaction'] ) || ! is_array( $_REQUEST['doaction'] ) ) )
				return;
		$images = null;
		// see if the user wants to reset the previous bulk status
		if (!empty($_REQUEST['ewww_reset']) && wp_verify_nonce($_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk-reset'))
			update_option('ewww_image_optimizer_bulk_ngg_resume', '');
		// see if there is a previous operation to resume
		$resume = get_option('ewww_image_optimizer_bulk_ngg_resume');
		// if we've been given a bulk action to perform
		if ( ! empty( $_REQUEST['doaction'] ) ) {
			// if we are optimizing a specific group of images
			if ($_REQUEST['page'] == 'manage-images' && $_REQUEST['bulkaction'] == 'bulk_optimize') {
				ewwwio_debug_message( 'optimizing a group of images' );
				check_admin_referer('ngg_updategallery');
				// reset the resume status, not allowed here
				update_option('ewww_image_optimizer_bulk_ngg_resume', '');
				// retrieve the image IDs from POST
				$images = array_map( 'intval', $_REQUEST['doaction']);
			}
			// if we are optimizing a specific group of galleries
			if ($_REQUEST['page'] == 'manage-galleries' && $_REQUEST['bulkaction'] == 'bulk_optimize') {
				ewwwio_debug_message( 'optimizing a group of galleries' );
				check_admin_referer('ngg_bulkgallery');
				global $nggdb;
				// reset the resume status, not allowed here
				update_option('ewww_image_optimizer_bulk_ngg_resume', '');
				$ids = array();
				$gids = array_map( 'intval', $_REQUEST['doaction']);
				// for each gallery we are given
				foreach ($gids as $gid) {
					// get a list of IDs
					$gallery_list = $nggdb->get_gallery($gid);
					// for each ID
					foreach ($gallery_list as $image) {
						// add it to the array
						$images[] = $image->pid;
					}
				}
			}
		// otherwise, if we have an operation to resume
		} elseif (!empty($resume)) {
			ewwwio_debug_message( 'resuming a previous operation (maybe)' );
			// get the list of attachment IDs from the db
			$images = get_option( 'ewww_image_optimizer_bulk_ngg_attachments' );
		// otherwise, if we are on the standard bulk page, get all the images in the db
		} elseif ($hook == $i18ngg . '_page_ewww-ngg-bulk') {
			ewwwio_debug_message( 'starting from scratch, grabbing all the images' );
			global $wpdb;
			$images = $wpdb->get_col("SELECT pid FROM $wpdb->nggpictures ORDER BY sortorder ASC");
		} else {
			ewwwio_debug_message( $hook );
		}
		
		// store the image IDs to process in the db
		update_option('ewww_image_optimizer_bulk_ngg_attachments', $images);
		// add the EWWW IO script
		wp_enqueue_script( 'ewwwbulkscript', plugins_url( '/includes/eio.js', __FILE__ ), array( 'jquery', 'jquery-ui-progressbar', 'jquery-ui-slider', 'postbox', 'dashboard' ) );
		// replacing the built-in nextgen styling rules for progressbar
		wp_register_style( 'ngg-jqueryui', plugins_url( '/includes/jquery-ui-1.10.1.custom.css', __FILE__ ) ); 
		// enqueue the progressbar styling
		wp_enqueue_style( 'ngg-jqueryui' ); //, plugins_url('jquery-ui-1.10.1.custom.css', __FILE__));
		// include all the vars we need for javascript
		wp_localize_script( 'ewwwbulkscript', 'ewww_vars', array(
				'_wpnonce' => wp_create_nonce( 'ewww-image-optimizer-bulk' ),
				'gallery' => 'nextgen',
				'attachments' => count( $images ),
				'operation_stopped' => esc_html__( 'Optimization stopped, reload page to resume.', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
				'operation_interrupted' => esc_html__( 'Operation Interrupted', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
				'temporary_failure' => esc_html__( 'Temporary failure, seconds left to retry:', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
				'remove_failed' => esc_html__( 'Could not remove image from table.', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
				'optimized' => esc_html__( 'Optimized', EWWW_IMAGE_OPTIMIZER_DOMAIN ),
			)
		);
	}

	/* start the bulk operation */
	function ewww_ngg_bulk_init() {
		$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
                if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
			wp_die( esc_html__( 'Access denied.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
                }
		// toggle the resume flag to indicate an operation is in progress
                update_option('ewww_image_optimizer_bulk_ngg_resume', 'true');
		// get the list of attachments remaining from the db
		$attachments = get_option('ewww_image_optimizer_bulk_ngg_attachments');
		$id = array_shift( $attachments );
		$file_name = $this->ewww_ngg_bulk_filename( $id );
		// let the user know we are starting
                $loading_image = plugins_url('/images/wpspin.gif', __FILE__);
                if ( empty( $file_name ) ) {
                	echo "<p>" . esc_html__('Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "&nbsp;<img src='$loading_image' alt='loading'/></p>";
		} else {
			echo "<p>" . esc_html__('Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <b>" . $file_name . "</b>&nbsp;<img src='$loading_image' alt='loading'/></p>";
		}
                die();
        }

	/* output the filename of the image being optimized */
	function ewww_ngg_bulk_filename( $id ) {
		// need this file to work with metadata
		require_once( WP_CONTENT_DIR . '/plugins/nextcellent-gallery-nextgen-legacy/lib/meta.php' );
		// get the meta for the image
		$meta = new nggMeta($id);
		// get the filename for the image, and output our current status
		$file_name = esc_html($meta->image->filename);
		if ( $file_name ) {
			return $file_name;
		} else {
			return false;
		}
	}

	/* process each image in the bulk loop */
	function ewww_ngg_bulk_loop() {
		global $ewww_defer;
		$ewww_defer = false;
		$output = array();
		$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
                if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
			$outupt['error'] = esc_html__( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN );
                	echo json_encode( $output );
                        die();
                }
		// need this file to work with metadata
		require_once( WP_CONTENT_DIR . '/plugins/nextcellent-gallery-nextgen-legacy/lib/meta.php' );
		// find out what time we started, in microseconds
		$started = microtime( true );
		// get the list of attachments remaining from the db
		$attachments = get_option('ewww_image_optimizer_bulk_ngg_attachments');
		//$id = (int) $_POST['ewww_attachment'];
		$id = array_shift( $attachments );
		list( $fres, $tres ) = $this->ewww_ngg_optimize( $id );
		global $ewww_exceed;
		if ( ! empty ( $ewww_exceed ) ) {
			$output['error'] = esc_html__( 'License Exceeded', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			echo json_encode( $output );
			die();
		}
		// output the results of the optimization
		$output['results'] = sprintf( "<p>" . esc_html__('Optimized image:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <strong>%s</strong><br>", esc_html( $fres[0] ) );
		$output['results'] .= sprintf( esc_html__( 'Full size - %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "<br>", esc_html( $fres[1] ) );
		// output the results of the thumb optimization
		$output['results'] .= sprintf( esc_html__( 'Thumbnail - %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "<br>", esc_html( $tres[1] ) );
		// outupt how much time we spent
		$elapsed = microtime( true ) - $started;
		$output['results'] .= sprintf( esc_html__( 'Elapsed: %.3f seconds', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</p>", $elapsed);
		//store the list back in the db
		update_option( 'ewww_image_optimizer_bulk_ngg_attachments', $attachments );
		if ( ! empty( $attachments ) ) {
                        $next_attachment = array_shift( $attachments );
                        $next_file = $this->ewww_ngg_bulk_filename( $next_attachment );
                        $loading_image = plugins_url( '/images/wpspin.gif', __FILE__ );
                        if ( $next_file ) {
                                $output['next_file'] =  "<p>" . esc_html__( 'Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . " <b>$next_file</b>&nbsp;<img src='$loading_image' alt='loading'/></p>";
                        } else {
                                $output['next_file'] =  "<p>" . esc_html__('Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "&nbsp;<img src='$loading_image' alt='loading'/></p>";
                        }
                }
                echo json_encode( $output );
		die();
	}

	/* finish the bulk operation */
	function ewww_ngg_bulk_cleanup() {
		$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
                if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) {
			wp_die( esc_html__( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
                }
		// reset all the bulk options in the db
		update_option('ewww_image_optimizer_bulk_ngg_resume', '');
		update_option('ewww_image_optimizer_bulk_ngg_attachments', '');
		// and let the user know we are done
		echo '<p><b>' . esc_html__('Finished Optimization!', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</b></p>';
		die();
	}

	// insert a bulk optimize option in the actions list for the gallery and image management pages (via javascript, since we have no hooks)
	function ewww_ngg_bulk_actions_script() {
		if ( ! current_user_can( apply_filters( 'ewww_image_optimizer_bulk_permissions', '' ) ) ) {
			return;
		}
?>		<script type="text/javascript">
			jQuery(document).ready(function($){
				$('select[name^="bulkaction"] option:last-child').after('<option value="bulk_optimize">Bulk Optimize</option>');
			});
		</script>
<?php	}
}

global $ewwwngg;
$ewwwngg = new ewwwngg();
}
