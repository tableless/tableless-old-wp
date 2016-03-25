<?php
// common functions for Standard and Cloud plugins

// TODO: can we possibly change settings for jpg/png/gif to say something like no compression/original quality/best compression...
// TODO: ajaxify one-click actions
// TODO: implement final phase of webp - forced webp with cdn url matching

define( 'EWWW_IMAGE_OPTIMIZER_VERSION', '261.0' );

// initialize a couple globals
$ewww_debug = '';
$ewww_defer = true;

$disabled = ini_get( 'disable_functions' );
if ( ! preg_match( '/get_current_user/', $disabled ) ) {
	ewwwio_debug_message( get_current_user() );
}

ewwwio_debug_message( 'EWWW IO version: ' . EWWW_IMAGE_OPTIMIZER_VERSION );

// check the WP version
global $wp_version;
$my_version = substr( $wp_version, 0, 3 );
ewwwio_debug_message( "WP version: $wp_version" );

// check the PHP version
if ( ! defined( 'PHP_VERSION_ID' ) ) {
	$php_version = explode( '.', PHP_VERSION );
	define( 'PHP_VERSION_ID', ( $version[0] * 10000 + $version[1] * 100 + $version[2] ) );
}
if ( defined( 'PHP_VERSION_ID' ) ) {
	ewwwio_debug_message( 'PHP version: ' . PHP_VERSION_ID );
}

if ( WP_DEBUG ) {
	$ewww_memory = 'plugin load: ' . memory_get_usage( true ) . "\n";
}

// setup custom $wpdb attribute for our image-tracking table
global $wpdb;
if ( ! isset( $wpdb->ewwwio_images ) ) {
	$wpdb->ewwwio_images = $wpdb->prefix . "ewwwio_images";
}

//add_action( 'contextual_help', 'wptuts_screen_help', 10, 3 );
function wptuts_screen_help( $contextual_help, $screen_id, $screen ) {
 
	global $hook_suffix;
 
    // List screen properties
    $variables = '<ul style="width:50%;float:left;"> <strong>Screen variables </strong>'
        . sprintf( '<li> Screen id : %s</li>', $screen_id )
        . sprintf( '<li> Screen base : %s</li>', $screen->base )
        . sprintf( '<li>Parent base : %s</li>', $screen->parent_base )
        . sprintf( '<li> Parent file : %s</li>', $screen->parent_file )
        . sprintf( '<li> Hook suffix : %s</li>', $hook_suffix )
        . '</ul>';
 
    // Append global $hook_suffix to the hook stems
    $hooks = array(
        "load-$hook_suffix",
        "admin_print_styles-$hook_suffix",
        "admin_print_scripts-$hook_suffix",
        "admin_head-$hook_suffix",
        "admin_footer-$hook_suffix"
    );
 
    // If add_meta_boxes or add_meta_boxes_{screen_id} is used, list these too
    if ( did_action( 'add_meta_boxes_' . $screen_id ) )
        $hooks[] = 'add_meta_boxes_' . $screen_id;
 
    if ( did_action( 'add_meta_boxes' ) )
        $hooks[] = 'add_meta_boxes';
 
    // Get List HTML for the hooks
    $hooks = '<ul style="width:50%;float:left;"> <strong>Hooks </strong> <li>' . implode( '</li><li>', $hooks ) . '</li></ul>';
 
    // Combine $variables list with $hooks list.
    $help_content = $variables . $hooks;
 
    // Add help panel
    $screen->add_help_tab( array(
        'id'      => 'wptuts-screen-help',
        'title'   => 'Screen Information',
        'content' => $help_content,
    ));
 
    return $contextual_help;
}

/**
 * Hooks
 */
if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_noauto' ) ) {
	// used to move the W3TC CDN uploads filter
	add_filter( 'wp_handle_upload', 'ewww_image_optimizer_handle_upload' );
	// used to turn off ewwwio_image_editor during uploads
	add_action( 'add_attachment', 'ewww_image_optimizer_add_attachment' );
	add_filter( 'wp_image_editors', 'ewww_image_optimizer_load_editor', 60 );
	add_filter( 'wp_generate_attachment_metadata', 'ewww_image_optimizer_resize_from_meta_data', 15, 2 );
}
// this filter turns off ewwwio_image_editor during save from the actual image editor
// and ensures that we parse the resizes list during the image editor save function
add_filter( 'load_image_to_edit_path', 'ewww_image_optimizer_editor_save_pre' );
// this hook is used to ensure we populate the metadata with webp images
add_filter( 'wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment_metadata', 8, 2 );
add_filter( 'manage_media_columns', 'ewww_image_optimizer_columns' );
// filters to set default permissions, anyone can override these if they wish
add_filter( 'ewww_image_optimizer_manual_permissions', 'ewww_image_optimizer_manual_permissions', 8 );
add_filter( 'ewww_image_optimizer_bulk_permissions', 'ewww_image_optimizer_admin_permissions', 8 );
add_filter( 'ewww_image_optimizer_admin_permissions', 'ewww_image_optimizer_admin_permissions', 8 );
add_filter( 'ewww_image_optimizer_superadmin_permissions', 'ewww_image_optimizer_superadmin_permissions', 8 );
// variable for plugin settings link
$plugin = plugin_basename ( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE );
add_filter( "plugin_action_links_$plugin", 'ewww_image_optimizer_settings_link' );
add_filter( 'intermediate_image_sizes_advanced', 'ewww_image_optimizer_image_sizes_advanced' );
add_filter( 'ewww_image_optimizer_settings', 'ewww_image_optimizer_filter_settings_page' );
add_filter( 'myarcade_filter_screenshot', 'ewww_image_optimizer_myarcade_thumbnail' );
add_filter( 'myarcade_filter_thumbnail', 'ewww_image_optimizer_myarcade_thumbnail' );
add_action( 'manage_media_custom_column', 'ewww_image_optimizer_custom_column', 10, 2 );
add_action( 'plugins_loaded', 'ewww_image_optimizer_preinit' );
add_action( 'init', 'ewww_image_optimizer_gallery_support' );
add_action( 'admin_init', 'ewww_image_optimizer_admin_init' );
add_action( 'admin_action_ewww_image_optimizer_manual_optimize', 'ewww_image_optimizer_manual' );
add_action( 'admin_action_ewww_image_optimizer_manual_restore', 'ewww_image_optimizer_manual' );
add_action( 'admin_action_ewww_image_optimizer_manual_convert', 'ewww_image_optimizer_manual' );
add_action( 'delete_attachment', 'ewww_image_optimizer_delete' );
add_action( 'admin_menu', 'ewww_image_optimizer_admin_menu', 60 );
add_action( 'network_admin_menu', 'ewww_image_optimizer_network_admin_menu' );
add_action( 'load-upload.php', 'ewww_image_optimizer_load_admin_js' );
add_action( 'admin_action_bulk_optimize', 'ewww_image_optimizer_bulk_action_handler' ); 
add_action( 'admin_action_-1', 'ewww_image_optimizer_bulk_action_handler' ); 
add_action( 'admin_enqueue_scripts', 'ewww_image_optimizer_media_scripts' );
add_action( 'admin_enqueue_scripts', 'ewww_image_optimizer_settings_script' );
add_action( 'ewww_image_optimizer_auto', 'ewww_image_optimizer_auto' );
add_action( 'ewww_image_optimizer_defer', 'ewww_image_optimizer_defer' );
add_action( 'wr2x_retina_file_added', 'ewww_image_optimizer_retina', 20, 2 );
add_action( 'wp_ajax_ewww_webp_rewrite', 'ewww_image_optimizer_webp_rewrite' );
register_deactivation_hook( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE, 'ewww_image_optimizer_network_deactivate' );
//add_action( 'shutdown', 'ewwwio_memory_output' );
if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp_for_cdn' ) ) {
	add_action( 'init', 'ewww_image_optimizer_buffer_start' );
	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		add_action( 'wp_enqueue_scripts', 'ewww_image_optimizer_webp_debug_script' );
	} else {
		add_action( 'wp_enqueue_scripts', 'ewww_image_optimizer_webp_load_jquery' );
		add_action( 'wp_print_footer_scripts', 'ewww_image_optimizer_webp_inline_script' );
	}
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'iocli.php' );
}

function ewww_image_optimizer_get_plugin_version( $plugin_file ) {
        $default_headers = array(
		'Version' => 'Version',
	);
	$plugin_data = get_file_data( $plugin_file, $default_headers, 'plugin' );
	return $plugin_data;
}

// functions to capture all page output, replace image urls with webp derivatives, and add webp fallback 
function ewww_image_optimizer_buffer_start() {
	//ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ob_start( 'ewww_image_optimizer_filter_page_output' );
}
function ewww_image_optimizer_buffer_end() {
	//ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ob_end_flush();
}

function ewww_image_optimizer_filter_page_output( $buffer, $admin_bypass ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( ( empty ( $buffer ) || is_admin() ) && $admin_bypass !== 293 ) {
		return $buffer;
	}
	$uri = $_SERVER['REQUEST_URI'];
	if ( strpos( $uri, '&cornerstone=1' ) || strpos( $uri, 'cornerstone-endpoint' ) !== false ) {
		return $buffer;
	}
	// modify buffer here, and then return the updated code
	if ( class_exists( 'DOMDocument' ) ) {
		if ( preg_match( '/<\?xml/', $buffer ) ) {
			return $buffer;
		}
		$expanded_head = false;
		preg_match( '/.+<head ?>/s', $buffer, $html_head );
		if ( empty( $html_head ) ) {
			ewwwio_debug_message( 'did not find head tag' );
			preg_match( '/.+<head [^>]*>/s', $buffer, $html_head );
			if ( empty( $html_head ) ) {
				ewwwio_debug_message( 'did not find expanded head tag either' );
				return $buffer;
			}
		}
		$html = new DOMDocument;
		$libxml_previous_error_reporting = libxml_use_internal_errors( true );
		$html->formatOutput = false;
		$html->encoding = 'utf-8';
		ewwwio_debug_message( 'libxml version: ' . LIBXML_VERSION );
		if ( defined( 'LIBXML_VERSION' ) && LIBXML_VERSION < 20800 ) {
			// converts the buffer from utf-8 to html-entities
			$buffer = mb_convert_encoding( $buffer, 'HTML-ENTITIES', 'UTF-8' );
		} elseif ( ! defined( 'LIBXML_VERSION' ) ) {
			ewwwio_debug_message( 'cannot detect libxml version' );
			return $buffer;
		}
		if ( preg_match( '/<.DOCTYPE.+xhtml/', $buffer ) ) {
			$html->recover = true;
			$xhtml_parse = $html->loadXML( $buffer );
			ewwwio_debug_message( 'parsing as xhtml' );
		} elseif ( empty( $xhtml_parse ) ) {
			$html->loadHTML( $buffer );
			ewwwio_debug_message( 'parsing as html' );
		}
		$html->encoding = 'utf-8';
		$home_url = get_site_url();
		$home_relative_url = preg_replace( '/https?:/', '', $home_url );
		$images = $html->getElementsByTagName( 'img' );
		foreach ( $images as $image ) {
			if ( $image->parentNode->tagName == 'noscript' ) {
				continue;
			}
			$srcset = '';
			ewwwio_debug_message( 'parsing an image' );
			$file = $image->getAttribute( 'src' );
			$filepath = str_replace( $home_url, ABSPATH, $file );
			$file_relative_path = str_replace( $home_relative_url, ABSPATH, $file );
			ewwwio_debug_message( "the image is at $filepath or $file_relative_path" );
			if ( file_exists( $filepath . '.webp' ) || file_exists( $file_relative_path . '.webp' ) ) {
				$nscript = $html->createElement( 'noscript' );
				$nscript->setAttribute( 'data-img', $file );
				$nscript->setAttribute( 'data-webp', $file . '.webp' );
				if ( $image->getAttribute( 'srcset' ) ) {
					$srcset = $image->getAttribute( 'srcset' );
					preg_match_all( '/http\S+/', $srcset, $srcset_urls );
					if ( ! empty( $srcset_urls ) ) {
						ewwwio_debug_message( 'found srcset urls' );
						$srcset_webp = $srcset;
						foreach ( $srcset_urls[0] as $srcurl ) {
							$srcfilepath = ABSPATH . str_replace( $home_url, '', $srcurl );
							ewwwio_debug_message( "looking for srcurl on disk: $srcfilepath" );
							if ( file_exists( $srcfilepath . '.webp' ) ) {
								$srcset_webp = preg_replace( "|$srcurl|", $srcurl . '.webp', $srcset_webp );
								ewwwio_debug_message( "replacing $srcurl in $srcset_webp" );
							}
						}
						$nscript->setAttribute( 'data-srcset-webp', $srcset_webp );
					}
					$nscript->setAttribute( 'data-srcset-img', $image->getAttribute( 'srcset' ) );
				}
				if ( $image->getAttribute( 'align' ) )
					$nscript->setAttribute( 'data-align', $image->getAttribute( 'align' ) );
				if ( $image->getAttribute( 'alt' ) )
					$nscript->setAttribute( 'data-alt', $image->getAttribute( 'alt' ) );
				if ( $image->getAttribute( 'border' ) )
					$nscript->setAttribute('data-border', $image->getAttribute( 'border' ) );
				if ( $image->getAttribute( 'crossorigin' ) )
					$nscript->setAttribute( 'data-crossorigin', $image->getAttribute( 'crossorigin' ) );
				if ( $image->getAttribute( 'height' ) )
					$nscript->setAttribute( 'data-height', $image->getAttribute( 'height' ) );
				if ( $image->getAttribute( 'hspace' ) )
					$nscript->setAttribute( 'data-hspace', $image->getAttribute( 'hspace' ) );
				if ( $image->getAttribute( 'ismap' ) )
					$nscript->setAttribute( 'data-ismap', $image->getAttribute( 'ismap' ) );
				if ( $image->getAttribute( 'longdesc' ) )
					$nscript->setAttribute( 'data-longdesc', $image->getAttribute( 'longdesc' ) );
				if ( $image->getAttribute( 'usemap' ) )
					$nscript->setAttribute( 'data-usemap', $image->getAttribute( 'usemap' ) );
				if ( $image->getAttribute( 'vspace' ) )
					$nscript->setAttribute( 'data-vspace', $image->getAttribute( 'vspace' ) );
				if ( $image->getAttribute('width') )
					$nscript->setAttribute('data-width', $image->getAttribute('width'));
				if ( $image->getAttribute('accesskey') )
					$nscript->setAttribute('data-accesskey', $image->getAttribute('accesskey'));
				if ( $image->getAttribute('class') )
					$nscript->setAttribute('data-class', $image->getAttribute('class'));
				if ( $image->getAttribute('contenteditable') )
					$nscript->setAttribute('data-contenteditable', $image->getAttribute('contenteditable'));
				if ( $image->getAttribute('contextmenu') )
					$nscript->setAttribute('data-contextmenu', $image->getAttribute('contextmenu'));
				if ( $image->getAttribute('dir') )
					$nscript->setAttribute('data-dir', $image->getAttribute('dir'));
				if ( $image->getAttribute('draggable') )
					$nscript->setAttribute('data-draggable', $image->getAttribute('draggable'));
				if ( $image->getAttribute('dropzone') )
					$nscript->setAttribute('data-dropzone', $image->getAttribute('dropzone'));
				if ( $image->getAttribute('hidden') )
					$nscript->setAttribute('data-hidden', $image->getAttribute('hidden'));
				if ( $image->getAttribute('id') )
					$nscript->setAttribute('data-id', $image->getAttribute('id'));
				if ( $image->getAttribute('lang') )
					$nscript->setAttribute('data-lang', $image->getAttribute('lang'));
				if ( $image->getAttribute('spellcheck') )
					$nscript->setAttribute('data-spellcheck', $image->getAttribute('spellcheck'));
				if ( $image->getAttribute('style') )
					$nscript->setAttribute('data-style', $image->getAttribute('style'));
				if ( $image->getAttribute('tabindex') )
					$nscript->setAttribute('data-tabindex', $image->getAttribute('tabindex'));
				if ( $image->getAttribute('title') )
					$nscript->setAttribute('data-title', $image->getAttribute('title'));
				if ( $image->getAttribute('translate') )
					$nscript->setAttribute('data-translate', $image->getAttribute('translate'));
				if ( $image->getAttribute('sizes') )
					$nscript->setAttribute('data-sizes', $image->getAttribute('sizes'));
				$nscript->setAttribute('class', 'ewww_webp');
				$image->parentNode->replaceChild($nscript, $image);
				$nscript->appendChild( $image );
			}
			if ( empty( $file ) && $image->getAttribute( 'data-src' ) && $image->getAttribute( 'data-thumbnail' ) ) {
				$file = $image->getAttribute('data-src');
				$thumb = $image->getAttribute('data-thumbnail');
				ewwwio_debug_message( "checking webp for ngg data-src: $file" );
				$filepath = ABSPATH . str_replace( $home_url, '', $file );
				ewwwio_debug_message( "checking webp for ngg data-thumbnail: $thumb" );
				$thumbpath = ABSPATH . str_replace( $home_url, '', $thumb );
				if (file_exists($filepath . '.webp')) {
					ewwwio_debug_message( "found webp for ngg data-src: $filepath" );
					$image->setAttribute('data-webp', $file . '.webp');
				}
				if (file_exists($thumbpath . '.webp')) {
					ewwwio_debug_message( "found webp for ngg data-thumbnail: $thumbpath" );
					$image->setAttribute( 'data-webp-thumbnail', $thumb . '.webp' );
				}
			}
			if ( $image->getAttribute( 'data-lazyload' ) ) {
				$lazyload = $image->getAttribute( 'data-lazyload' );
				if ( ! empty( $lazyload ) ) {
					$lazyloadpath = ABSPATH . str_replace( $home_url, '', $lazyload );
					if ( file_exists( $lazyloadpath . '.webp' ) ) {
						ewwwio_debug_message( "found webp for data-lazyload: $filepath" );
						$image->setAttribute( 'data-webp-lazyload', $lazyload . '.webp' );
					}
				}
			}
		}
		$links = $html->getElementsByTagName( 'a' );
		foreach ( $links as $link ) {
			ewwwio_debug_message( 'parsing a link' );
			if ( $link->getAttribute( 'data-src' ) && $link->getAttribute( 'data-thumbnail' ) ) {
				$file = $link->getAttribute( 'data-src' );
				$thumb = $link->getAttribute( 'data-thumbnail' );
				ewwwio_debug_message( "checking webp for ngg data-src: $file" );
				$filepath = ABSPATH . str_replace( $home_url, '', $file );
				ewwwio_debug_message( "checking webp for ngg data-thumbnail: $thumb" );
				$thumbpath = ABSPATH . str_replace( $home_url, '', $thumb );
				if ( file_exists( $filepath . '.webp' ) ) {
					ewwwio_debug_message( "found webp for ngg data-src: $filepath" );
					$link->setAttribute( 'data-webp', $file . '.webp' );
				}
				if ( file_exists( $thumbpath . '.webp' ) ) {
					ewwwio_debug_message( "found webp for ngg data-thumbnail: $thumbpath" );
					$link->setAttribute( 'data-webp-thumbnail', $thumb . '.webp' );
				}
				
			}
		}
		$listitems = $html->getElementsByTagName( 'li' );
		foreach ( $listitems as $listitem ) {
			ewwwio_debug_message( 'parsing a listitem' );
			if ( $listitem->getAttribute( 'data-title' ) === 'Slide' && ( $listitem->getAttribute( 'data-lazyload' ) || $listitem->getAttribute( 'data-thumb' ) ) ) {
				$thumb = $listitem->getAttribute( 'data-thumb' );
				ewwwio_debug_message( "checking webp for revslider data-thumb: $thumb" );
				$thumbpath = str_replace( $home_url, ABSPATH, $thumb );
				if ( file_exists( $thumbpath . '.webp' ) ) {
					ewwwio_debug_message( "found webp for revslider data-thumb: $thumbpath" );
					$listitem->setAttribute( 'data-webp-thumb', $thumb . '.webp' );
				}
				$param_num = 1;
				while( $param_num < 11 ) {
					$parameter = '';
					if ( $listitem->getAttribute( 'data-param' . $param_num ) ) {
						$parameter = $listitem->getAttribute( 'data-param' . $param_num );
						ewwwio_debug_message( "checking webp for revslider data-param$param_num: $parameter" );
						if ( ! empty( $parameter ) && strpos( $parameter, 'http' ) === 0 ) {
							$parameter_path = str_replace( $home_url, ABSPATH, $parameter );
							ewwwio_debug_message( "looking for $parameter_path" );
							if ( file_exists( $parameter_path . '.webp' ) ) {
								ewwwio_debug_message( "found webp for data-param$param_num: $parameter_path" );
								$listitem->setAttribute( 'data-webp-param' . $param_num, $parameter . '.webp' );
							}
						}
					}
					$param_num++;
				}
			}
		}
		ewwwio_debug_message( 'preparing to dump page back to $buffer' );
		if ( ! empty( $xhtml_parse ) ) {
			$buffer = $html->saveXML( $html->documentElement );
		} else {
			$buffer = $html->saveHTML( $html->documentElement );
		}
		libxml_clear_errors();
		libxml_use_internal_errors( $libxml_previous_error_reporting );
/*		ewwwio_debug_message( 'html head' );
		ewwwio_debug_message( $html_head[0] );
		ewwwio_debug_message( 'buffer beginning' );
		ewwwio_debug_message( substr( $buffer, 0, 500 ) );*/
		if ( ! empty( $html_head ) ) {
			$buffer = preg_replace( '/<html.+>\s.*<head>/', $html_head[0], $buffer );
		}
		// do some cleanup for the Easy Social Share Buttons for WordPress plugin (can't have <li> elements with newlines between them)
		$buffer = preg_replace( '/\s(<li class="essb_item)/', '$1', $buffer );
//		ewwwio_debug_message( 'buffer after replacement' );
//		ewwwio_debug_message( substr( $buffer, 0, 500 ) );
//		ewww_image_optimizer_debug_log();
	}
//	ewww_image_optimizer_debug_log();
	return $buffer;
}

// set permissions for various operations
function ewww_image_optimizer_manual_permissions( $permissions ) {
	if ( empty( $permissions ) ) {
		return 'edit_others_posts';
	}
	return $permissions;
}

function ewww_image_optimizer_admin_permissions( $permissions ) {
	if ( empty( $permissions ) ) {
		return 'activate_plugins';
	}
	return $permissions;
}

function ewww_image_optimizer_superadmin_permissions( $permissions ) {
	if ( empty( $permissions ) ) {
		return 'manage_network_options';
	}
	return $permissions;
}

function ewwwio_memory( $function ) {
	if ( WP_DEBUG ) {
		global $ewww_memory;
//		$ewww_memory .= $function . ': ' . memory_get_usage(true) . "\n";
	}
}

// function to check if set_time_limit() is disabled
function ewww_image_optimizer_stl_check() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$disabled = ini_get('disable_functions');
	ewwwio_debug_message( "disable_functions = $disabled" );
	if ( preg_match( '/set_time_limit/', $disabled ) ) {
		ewwwio_memory( __FUNCTION__ );
		return false;
	} else {
		ewwwio_memory( __FUNCTION__ );
		return true;
	}
}

function ewww_image_optimizer_preinit() {
	//ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	load_plugin_textdomain( EWWW_IMAGE_OPTIMIZER_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
	
function ewww_image_optimizer_gallery_support() {	
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$active_plugins = get_option( 'active_plugins' );
	if ( is_multisite() ) {
		$active_plugins = array_merge( $active_plugins, array_flip( get_site_option( 'active_sitewide_plugins' ) ) );
	}
	foreach ($active_plugins as $active_plugin) {
		if ( strpos( $active_plugin, '/nggallery.php' ) || strpos( $active_plugin, '\nggallery.php' ) ) {
			//$ngg = ewww_image_optimizer_get_plugin_version( $ewww_plugins_path . $active_plugin );
			$ngg = ewww_image_optimizer_get_plugin_version( trailingslashit( WP_PLUGIN_DIR ) . $active_plugin );
			// include the file that loads the nextgen gallery optimization functions
			ewwwio_debug_message( 'Nextgen version: ' . $ngg['Version'] );
			if (preg_match('/^2\./', $ngg['Version'])) { // for Nextgen 2
				ewwwio_debug_message( "loading nextgen2 support for $active_plugin" );
				require_once( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'nextgen2-integration.php' );
			} else {
				preg_match( '/\d+\.\d+\.(\d+)/', $ngg['Version'], $nextgen_minor_version);
				if ( ! empty( $nextgen_minor_version[1] ) && $nextgen_minor_version[1] < 14 ) {
					ewwwio_debug_message( "NOT loading nextgen legacy support for $active_plugin" );
				} elseif ( ! empty( $nextgen_minor_version[1] ) && $nextgen_minor_version[1] > 13 ) {
					ewwwio_debug_message( "loading nextcellent support for $active_plugin" );
					require_once( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'nextcellent-integration.php' );
				}
			}
		}
		if ( strpos( $active_plugin, '/flag.php' ) || strpos( $active_plugin, '\flag.php' ) ) {
			ewwwio_debug_message( "loading flagallery support for $active_plugin" );
			// include the file that loads the grand flagallery optimization functions
			require_once( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'flag-integration.php' );
		}
	}
}

/**
 * Plugin initialization function
 */
function ewww_image_optimizer_init() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_memory( __FUNCTION__ );
	if (get_option('ewww_image_optimizer_version') < EWWW_IMAGE_OPTIMIZER_VERSION) {
		ewww_image_optimizer_install_table();
		ewww_image_optimizer_set_defaults();
		update_option('ewww_image_optimizer_version', EWWW_IMAGE_OPTIMIZER_VERSION);
	}
	ewww_image_optimizer_cloud_init();
	ewwwio_memory( __FUNCTION__ );
//	ewww_image_optimizer_debug_log();
}

// Plugin initialization for admin area
function ewww_image_optimizer_admin_init() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_memory( __FUNCTION__ );
	ewww_image_optimizer_init();
	if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if ( is_multisite() && is_plugin_active_for_network(EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
		// set the common network settings if they have been POSTed
		if ( isset( $_POST['ewww_image_optimizer_delay'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'ewww_image_optimizer_options-options' ) ) {
			if ( empty( $_POST['ewww_image_optimizer_debug'] ) ) $_POST['ewww_image_optimizer_debug'] = '';
			update_site_option( 'ewww_image_optimizer_debug', $_POST['ewww_image_optimizer_debug'] );
			if ( empty( $_POST['ewww_image_optimizer_jpegtran_copy'] ) ) $_POST['ewww_image_optimizer_jpegtran_copy'] = '';
			update_site_option( 'ewww_image_optimizer_jpegtran_copy', $_POST['ewww_image_optimizer_jpegtran_copy'] );
			if (empty($_POST['ewww_image_optimizer_jpg_lossy'])) $_POST['ewww_image_optimizer_jpg_lossy'] = '';
			update_site_option('ewww_image_optimizer_jpg_lossy', $_POST['ewww_image_optimizer_jpg_lossy']);
			if (empty($_POST['ewww_image_optimizer_png_lossy'])) $_POST['ewww_image_optimizer_png_lossy'] = '';
			update_site_option('ewww_image_optimizer_png_lossy', $_POST['ewww_image_optimizer_png_lossy']);
			if (empty($_POST['ewww_image_optimizer_lossy_fast'])) $_POST['ewww_image_optimizer_lossy_fast'] = '';
			update_site_option('ewww_image_optimizer_lossy_fast', $_POST['ewww_image_optimizer_lossy_fast']);
			if (empty($_POST['ewww_image_optimizer_lossy_skip_full'])) $_POST['ewww_image_optimizer_lossy_skip_full'] = '';
			update_site_option('ewww_image_optimizer_lossy_skip_full', $_POST['ewww_image_optimizer_lossy_skip_full']);
			if (empty($_POST['ewww_image_optimizer_metadata_skip_full'])) $_POST['ewww_image_optimizer_metadata_skip_full'] = '';
			update_site_option('ewww_image_optimizer_metadata_skip_full', $_POST['ewww_image_optimizer_metadata_skip_full']);
			if (empty($_POST['ewww_image_optimizer_delete_originals'])) $_POST['ewww_image_optimizer_delete_originals'] = '';
			update_site_option('ewww_image_optimizer_delete_originals', $_POST['ewww_image_optimizer_delete_originals']);
			if (empty($_POST['ewww_image_optimizer_jpg_to_png'])) $_POST['ewww_image_optimizer_jpg_to_png'] = '';
			update_site_option('ewww_image_optimizer_jpg_to_png', $_POST['ewww_image_optimizer_jpg_to_png']);
			if (empty($_POST['ewww_image_optimizer_png_to_jpg'])) $_POST['ewww_image_optimizer_png_to_jpg'] = '';
			update_site_option('ewww_image_optimizer_png_to_jpg', $_POST['ewww_image_optimizer_png_to_jpg']);
			if (empty($_POST['ewww_image_optimizer_gif_to_png'])) $_POST['ewww_image_optimizer_gif_to_png'] = '';
			update_site_option('ewww_image_optimizer_gif_to_png', $_POST['ewww_image_optimizer_gif_to_png']);
			if (empty($_POST['ewww_image_optimizer_webp'])) $_POST['ewww_image_optimizer_webp'] = '';
			update_site_option('ewww_image_optimizer_webp', $_POST['ewww_image_optimizer_webp']);
			if (empty($_POST['ewww_image_optimizer_jpg_background'])) $_POST['ewww_image_optimizer_jpg_background'] = '';
			update_site_option('ewww_image_optimizer_jpg_background', ewww_image_optimizer_jpg_background($_POST['ewww_image_optimizer_jpg_background']));
			if (empty($_POST['ewww_image_optimizer_jpg_quality'])) $_POST['ewww_image_optimizer_jpg_quality'] = '';
			update_site_option('ewww_image_optimizer_jpg_quality', ewww_image_optimizer_jpg_quality($_POST['ewww_image_optimizer_jpg_quality']));
			if (empty($_POST['ewww_image_optimizer_disable_convert_links'])) $_POST['ewww_image_optimizer_disable_convert_links'] = '';
			update_site_option('ewww_image_optimizer_disable_convert_links', $_POST['ewww_image_optimizer_disable_convert_links']);
			if (empty($_POST['ewww_image_optimizer_cloud_key'])) $_POST['ewww_image_optimizer_cloud_key'] = '';
			update_site_option( 'ewww_image_optimizer_cloud_key', ewww_image_optimizer_cloud_key_sanitize( $_POST['ewww_image_optimizer_cloud_key'] ) );
			if (empty($_POST['ewww_image_optimizer_cloud_jpg'])) $_POST['ewww_image_optimizer_cloud_jpg'] = '';
			update_site_option('ewww_image_optimizer_cloud_jpg', $_POST['ewww_image_optimizer_cloud_jpg']);
			if (empty($_POST['ewww_image_optimizer_cloud_png'])) $_POST['ewww_image_optimizer_cloud_png'] = '';
			update_site_option('ewww_image_optimizer_cloud_png', $_POST['ewww_image_optimizer_cloud_png']);
			if (empty($_POST['ewww_image_optimizer_cloud_png_compress'])) $_POST['ewww_image_optimizer_cloud_png_compress'] = '';
			update_site_option('ewww_image_optimizer_cloud_png_compress', $_POST['ewww_image_optimizer_cloud_png_compress']);
			if (empty($_POST['ewww_image_optimizer_cloud_gif'])) $_POST['ewww_image_optimizer_cloud_gif'] = '';
			update_site_option('ewww_image_optimizer_cloud_gif', $_POST['ewww_image_optimizer_cloud_gif']);
			if (empty($_POST['ewww_image_optimizer_auto'])) $_POST['ewww_image_optimizer_auto'] = '';
			update_site_option('ewww_image_optimizer_auto', $_POST['ewww_image_optimizer_auto']);
			if (empty($_POST['ewww_image_optimizer_aux_paths'])) $_POST['ewww_image_optimizer_aux_paths'] = '';
			update_site_option('ewww_image_optimizer_aux_paths', ewww_image_optimizer_aux_paths_sanitize($_POST['ewww_image_optimizer_aux_paths']));
			if (empty($_POST['ewww_image_optimizer_enable_cloudinary'])) $_POST['ewww_image_optimizer_enable_cloudinary'] = '';
			update_site_option('ewww_image_optimizer_enable_cloudinary', $_POST['ewww_image_optimizer_enable_cloudinary']);
			if (empty($_POST['ewww_image_optimizer_delay'])) $_POST['ewww_image_optimizer_delay'] = '';
			update_site_option('ewww_image_optimizer_delay', intval($_POST['ewww_image_optimizer_delay']));
			if (empty($_POST['ewww_image_optimizer_disable_resizes'])) $_POST['ewww_image_optimizer_disable_resizes'] = array();
			update_site_option('ewww_image_optimizer_disable_resizes', $_POST['ewww_image_optimizer_disable_resizes']);
			if (empty($_POST['ewww_image_optimizer_disable_resizes_opt'])) $_POST['ewww_image_optimizer_disable_resizes_opt'] = array();
			update_site_option('ewww_image_optimizer_disable_resizes_opt', $_POST['ewww_image_optimizer_disable_resizes_opt']);
			if (empty($_POST['ewww_image_optimizer_skip_size'])) $_POST['ewww_image_optimizer_skip_size'] = '';
			update_site_option('ewww_image_optimizer_skip_size', intval($_POST['ewww_image_optimizer_skip_size']));
			if (empty($_POST['ewww_image_optimizer_skip_png_size'])) $_POST['ewww_image_optimizer_skip_png_size'] = '';
			update_site_option('ewww_image_optimizer_skip_png_size', intval($_POST['ewww_image_optimizer_skip_png_size']));
			if (empty($_POST['ewww_image_optimizer_noauto'])) $_POST['ewww_image_optimizer_noauto'] = '';
			update_site_option('ewww_image_optimizer_noauto', $_POST['ewww_image_optimizer_noauto']);
			if (empty($_POST['ewww_image_optimizer_defer'])) $_POST['ewww_image_optimizer_defer'] = '';
			update_site_option('ewww_image_optimizer_defer', $_POST['ewww_image_optimizer_defer']);
			if (empty($_POST['ewww_image_optimizer_include_media_paths'])) $_POST['ewww_image_optimizer_include_media_paths'] = '';
			update_site_option('ewww_image_optimizer_include_media_paths', $_POST['ewww_image_optimizer_include_media_paths']);
			if (empty($_POST['ewww_image_optimizer_webp_for_cdn'])) $_POST['ewww_image_optimizer_webp_for_cdn'] = '';
			update_site_option('ewww_image_optimizer_webp_for_cdn', $_POST['ewww_image_optimizer_webp_for_cdn']);
			//if (empty($_POST['ewww_image_optimizer_webp_cdn_path'])) $_POST['ewww_image_optimizer_webp_cdn_path'] = '';
			//update_site_option('ewww_image_optimizer_webp_cdn_path', $_POST['ewww_image_optimizer_webp_cdn_path']);
			add_action('network_admin_notices', 'ewww_image_optimizer_network_settings_saved');
		}
	}
	// register all the common EWWW IO settings
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_debug');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_jpegtran_copy');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_jpg_lossy');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_png_lossy');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_lossy_fast');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_lossy_skip_full');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_metadata_skip_full');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_delete_originals');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_jpg_to_png');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_png_to_jpg');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_gif_to_png');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_webp');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_jpg_background', 'ewww_image_optimizer_jpg_background');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_jpg_quality', 'ewww_image_optimizer_jpg_quality');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_convert_links');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_bulk_resume');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_bulk_attachments');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_aux_resume');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_aux_attachments');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_aux_type');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_key', 'ewww_image_optimizer_cloud_key_sanitize');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_jpg');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_png');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_png_compress');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_cloud_gif');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_auto');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_aux_paths', 'ewww_image_optimizer_aux_paths_sanitize');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_enable_cloudinary');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_delay', 'intval');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_resizes');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_resizes_opt');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_skip_size', 'intval');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_skip_png_size', 'intval');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_import_status');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_noauto');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_defer');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_include_media_paths');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_webp_for_cdn');
//	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_webp_cdn_path');
	ewww_image_optimizer_exec_init();
	ewww_image_optimizer_cron_setup( 'ewww_image_optimizer_auto' );
	ewww_image_optimizer_cron_setup( 'ewww_image_optimizer_defer' );
	// require the files that do the bulk processing 
	require_once( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'bulk.php' );
	require_once( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'aux-optimize.php' );
	require_once( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'mwebp.php' );
	// queue the function that contains custom styling for our progressbars, but only in wp 3.8+ 
	global $wp_version; 
	if ( substr($wp_version, 0, 3) >= 3.8 ) {  
		add_action('admin_enqueue_scripts', 'ewww_image_optimizer_progressbar_style'); 
	}
	ewwwio_memory( __FUNCTION__ );
//	ewww_image_optimizer_debug_log();
}

// setup wp_cron tasks for scheduled and deferred optimization
function ewww_image_optimizer_cron_setup( $event ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// setup scheduled optimization if the user has enabled it, and it isn't already scheduled
	if ( ewww_image_optimizer_get_option( $event ) == TRUE && ! wp_next_scheduled( $event ) ) {
		ewwwio_debug_message( "scheduling $event" );
		wp_schedule_event( time(), apply_filters( 'ewww_image_optimizer_schedule', 'hourly', $event ), $event );
	} elseif ( ewww_image_optimizer_get_option( $event ) == TRUE ) {
		ewwwio_debug_message( "$event already scheduled: " . wp_next_scheduled( $event ) );
	} elseif ( wp_next_scheduled( $event ) ) {
		ewwwio_debug_message( "un-scheduling $event" );
		wp_clear_scheduled_hook( $event );
		if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
			// need to include the plugin library for the is_plugin_active function
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
			global $wpdb;
			$query = $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE site_id = %d", $wpdb->siteid );
			$blogs = $wpdb->get_results( $query, ARRAY_A );
			foreach ( $blogs as $blog ) {
				switch_to_blog( $blog['blog_id'] );
				wp_clear_scheduled_hook( $event );
			}
			restore_current_blog();
		}
	}
}

// sets all the tool constants to false
function ewww_image_optimizer_disable_tools() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_JPEGTRAN' ) ) {
		define('EWWW_IMAGE_OPTIMIZER_JPEGTRAN', false);
	}
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_OPTIPNG' ) ) {
		define('EWWW_IMAGE_OPTIMIZER_OPTIPNG', false);
	}
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_PNGOUT' ) ) {
		define('EWWW_IMAGE_OPTIMIZER_PNGOUT', false);
	}
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_GIFSICLE' ) ) {
		define('EWWW_IMAGE_OPTIMIZER_GIFSICLE', false);
	}
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_PNGQUANT' ) ) {
		define('EWWW_IMAGE_OPTIMIZER_PNGQUANT', false);
	}
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_WEBP' ) ) {
		define('EWWW_IMAGE_OPTIMIZER_WEBP', false);
	}
	ewwwio_memory( __FUNCTION__ );
}

// generates css include for progressbars to match admin style
function ewww_image_optimizer_progressbar_style() {
	wp_add_inline_style( 'jquery-ui-progressbar', ".ui-widget-header { background-color: " . ewww_image_optimizer_admin_background() . "; }" );
	ewwwio_memory( __FUNCTION__ );
}

// determines the background color to use based on the selected theme
function ewww_image_optimizer_admin_background() {
	if ( function_exists( 'wp_add_inline_style' ) ) {
		$user_info = wp_get_current_user();
		switch( $user_info->admin_color ) {
			case 'midnight':
				return "#e14d43";
			case 'blue':
				return "#096484";
			case 'light':
				return "#04a4cc";
			case 'ectoplasm':
				return "#a3b745";
			case 'coffee':
				return "#c7a589";
			case 'ocean':
				return "#9ebaa0";
			case 'sunrise':
				return "#dd823b";
			default:
				return "#0073aa";
		}
	}
	ewwwio_memory( __FUNCTION__ );
}

// tells WP to ignore the 'large network' detection by filtering the results of wp_is_large_network()
function ewww_image_optimizer_large_network() {
	return false;
}

// adds table to db for storing status of auxiliary images that have been optimized
function ewww_image_optimizer_install_table() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;

	//see if the path column exists, and what collation it uses to determine the column index size
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->ewwwio_images'" ) == $wpdb->ewwwio_images ) {
		$current_collate = $wpdb->get_results( "SHOW FULL COLUMNS FROM $wpdb->ewwwio_images", ARRAY_A );
		if ( ! empty( $current_collate[1]['Field'] ) && $current_collate[1]['Field'] === 'path' && strpos( $current_collate[1]['Collation'], 'utf8mb4' ) === false ) {
			$path_index_size = 255;
		}
	}

	// get the current wpdb charset and collation
	$charset_collate = $wpdb->get_charset_collate();

	// if the path column doesn't yet exist, and the default collation is utf8mb4, then we need to lower the column index size
	if ( empty( $path_index_size ) && strpos( $charset_collate, 'utf8mb4' ) ) {
		$path_index_size = 191;
	} else {
		$path_index_size = 255;
	}

	// create a table with 4 columns: an id, the file path, the md5sum, and the optimization results
	$sql = "CREATE TABLE $wpdb->ewwwio_images (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		path text NOT NULL,
		results varchar(55) NOT NULL,
		image_size int(10) unsigned,
		orig_size int(10) unsigned,
		updates int(5) unsigned,
		updated timestamp DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
		trace blob,
		UNIQUE KEY id (id),
		KEY path_image_size (path($path_index_size),image_size)
	) $charset_collate;";
	
	// include the upgrade library to initialize a table
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$updates = dbDelta( $sql );
	
	// make sure some of our options are not autoloaded (since they can be huge)
	$bulk_attachments = get_option( 'ewww_image_optimizer_bulk_attachments', '' );
	delete_option( 'ewww_image_optimizer_bulk_attachments' );
	add_option( 'ewww_image_optimizer_bulk_attachments', $bulk_attachments, '', 'no' );
	$bulk_attachments = get_option( 'ewww_image_optimizer_flag_attachments', '' );
	delete_option( 'ewww_image_optimizer_flag_attachments' );
	add_option( 'ewww_image_optimizer_flag_attachments', $bulk_attachments, '', 'no' );
	$bulk_attachments = get_option( 'ewww_image_optimizer_ngg_attachments', '' );
	delete_option( 'ewww_image_optimizer_ngg_attachments' );
	add_option( 'ewww_image_optimizer_ngg_attachments', $bulk_attachments, '', 'no' );
	$bulk_attachments = get_option( 'ewww_image_optimizer_aux_attachments', '' );
	delete_option( 'ewww_image_optimizer_aux_attachments' );
	add_option( 'ewww_image_optimizer_aux_attachments', $bulk_attachments, '', 'no' );
	$bulk_attachments = get_option( 'ewww_image_optimizer_defer_attachments', '' );
	delete_option( 'ewww_image_optimizer_defer_attachments' );
	add_option( 'ewww_image_optimizer_defer_attachments', $bulk_attachments, '', 'no' );
}

// lets the user know their network settings have been saved
function ewww_image_optimizer_network_settings_saved() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	echo "<div id='ewww-image-optimizer-settings-saved' class='updated fade'><p><strong>" . esc_html__('Settings saved', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ".</strong></p></div>";
}   

// load the class to extend WP_Image_Editor
function ewww_image_optimizer_load_editor( $editors ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( ! class_exists( 'EWWWIO_GD_Editor' ) && ! class_exists( 'EWWWIO_Imagick_Editor' ) )
		include( plugin_dir_path( __FILE__ ) . '/image-editor.php' );
	if ( ! in_array( 'EWWWIO_GD_Editor', $editors ) )
		array_unshift( $editors, 'EWWWIO_GD_Editor' );
	if ( ! in_array( 'EWWWIO_Imagick_Editor', $editors ) )
		array_unshift( $editors, 'EWWWIO_Imagick_Editor' );
	if ( ! in_array( 'EWWWIO_Gmagick_Editor', $editors ) && class_exists( 'WP_Image_Editor_Gmagick' ) )
		array_unshift( $editors, 'EWWWIO_Gmagick_Editor' );
	ewwwio_debug_message( "loading image editors: " . print_r( $editors, true ) );
	ewwwio_memory( __FUNCTION__ );
	return $editors;
}

// register the filter that will remove the image_editor hooks when an attachment is added
function ewww_image_optimizer_add_attachment() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	add_filter( 'intermediate_image_sizes_advanced', 'ewww_image_optimizer_image_sizes', 200 );
}

// remove the image editor filter, and add a new filter that will restore it later
function ewww_image_optimizer_image_sizes( $sizes ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	remove_filter( 'wp_image_editors', 'ewww_image_optimizer_load_editor', 60 );
	add_filter( 'wp_generate_attachment_metadata', 'ewww_image_optimizer_restore_editor_hooks', 1 );
	return $sizes;
}

// restore the image editor filter after the resizes have completed
function ewww_image_optimizer_restore_editor_hooks( $metadata ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	add_filter( 'wp_image_editors', 'ewww_image_optimizer_load_editor', 60 );
	return $metadata;
}

// when an image has been edited, remove the image editor filter, and add a new filter that will restore it later
function ewww_image_optimizer_editor_save_pre( $image ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_noauto' ) ) {
		remove_filter( 'wp_image_editors', 'ewww_image_optimizer_load_editor', 60 );
		add_filter( 'wp_update_attachment_metadata', 'ewww_image_optimizer_restore_editor_hooks', 1 );
		add_filter( 'wp_update_attachment_metadata', 'ewww_image_optimizer_resize_from_meta_data', 15, 2 );
	}
	add_filter( 'intermediate_image_sizes', 'ewww_image_optimizer_image_sizes_advanced' );
	return $image;
}

// filter the image sizes generated by Wordpress, themes, and plugins allowing users to disable specific sizes
function ewww_image_optimizer_image_sizes_advanced( $sizes ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$disabled_sizes = ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_resizes' );
	$flipped = false;
	if ( ! empty( $disabled_sizes ) ) {
		if ( ! empty( $sizes[0] ) ) {
			$sizes = array_flip( $sizes );
			$flipped = true;
		}
		ewwwio_debug_message( print_r( $sizes, true ) );
		foreach ( $disabled_sizes as $size => $disabled ) {
			if ( ! empty( $disabled ) ) {
				ewwwio_debug_message( "size disabled: $size" );
				unset( $sizes[$size] );
			}
		}
		if ( $flipped ) {
			$sizes = array_flip( $sizes );
		}
	}
	return $sizes;
}

// during an upload, remove the W3TC CDN filter and add a new filter with our own wrapper around the W3TC function
function ewww_image_optimizer_handle_upload( $params ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( function_exists( 'w3_instance' ) ) {
		$w3_plugin_cdn = w3_instance( 'W3_Plugin_Cdn' );
		$removed = remove_filter( 'update_attached_file', array( $w3_plugin_cdn, 'update_attached_file' ) );
		add_filter( 'wp_generate_attachment_metadata', 'ewww_image_optimizer_update_attached_file_w3tc', 20, 2 );
	}
	return $params;
}

// this is the delayed wrapper for the W3TC CDN function that runs after optimization (priority 20)
function ewww_image_optimizer_update_attached_file_w3tc( $meta, $id ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	list( $file_path, $upload_path ) = ewww_image_optimizer_attachment_path( $meta, $id );
	$w3_plugin_cdn = w3_instance( 'W3_Plugin_Cdn' );
	$w3_plugin_cdn->update_attached_file( $file_path, $id );
	return $meta;
}

function ewww_image_optimizer_w3tc_update_files( $files ) {
	global $ewww_attachment;
	list( $file, $upload_path ) = ewww_image_optimizer_attachment_path( $ewww_attachment['meta'], $ewww_attachment['id'] );
	$file_info = array();
	if ( function_exists( 'w3_upload_info' ) ) {
		$upload_info = w3_upload_info();
	} else {
		$upload_info = ewww_image_optimizer_upload_info();
	}
	if ( $upload_info ) {
		$remote_file = ltrim( $upload_info['baseurlpath'] . $ewww_attachment['meta']['file'], '/' );
		$home_url = get_site_url();
		$original_url = $home_url . $file;
		$file_info[] = array( 'local_path' => $file,
			'remote_path' => $remote_file,
			'original_url' => $original_url );
		$files = array_merge( $files, $file_info );
	}
	return $files;
}

function ewww_image_optimizer_upload_info() {
	
	$upload_info = @wp_upload_dir();

	if ( empty( $upload_info['error'] ) ) {
		$parse_url = @parse_url( $upload_info['baseurl'] );

		if ( $parse_url ) {
			$baseurlpath = ( ! empty( $parse_url['path'] ) ? trim( $parse_url['path'], '/' ) : '' );
		} else {
			$baseurlpath = 'wp-content/uploads';
		}
		$upload_info['baseurlpath'] = '/' . $baseurlpath . '/';
	} else {
		$upload_info = false;
	}
	return $upload_info;
}

// runs scheduled optimization of various auxiliary images
function ewww_image_optimizer_auto() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $ewww_defer;
	$ewww_defer = false;
	require_once( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'bulk.php' );
	require_once( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'aux-optimize.php' );
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_auto' ) == TRUE ) {
		ewwwio_debug_message( 'running scheduled optimization' );
		ewww_image_optimizer_aux_images_script( 'ewww-image-optimizer-auto' );
		ewww_image_optimizer_aux_images_initialize(true);
		// generate our own nonce value, wp_create_nonce() will return the same value for 12-24 hours
		$nonce = wp_hash( time() . '|' . 'ewww-image-optimizer-auto' );
		update_option( 'ewww_image_optimizer_aux_resume', $nonce );
		$delay = ewww_image_optimizer_get_option( 'ewww_image_optimizer_delay' );		
		$attachments = get_option( 'ewww_image_optimizer_aux_attachments' );
		if ( ! empty( $attachments ) ) {
			global $wpdb;
			$query = "SELECT path,image_size FROM $wpdb->ewwwio_images";
			$already_optimized = $wpdb->get_results( $query, ARRAY_A );
			foreach ( $attachments as $attachment ) {
				// if the nonce has changed since we started, bail out, since that means another aux scan/optimize is running
				// we do a query using $wpdb, because get_option() is cached
				$current_nonce = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'ewww_image_optimizer_aux_resume'" );
				if ( $nonce !== $current_nonce ) {
					ewwwio_debug_message( 'detected another optimization, nonce changed, bailing' );
					ewww_image_optimizer_debug_log();
					return;
				} else {
					ewwwio_debug_message( "$nonce is fine, compared to $current_nonce" );
				}
				ewww_image_optimizer_aux_images_loop( $attachment, true );
				if ( ! empty( $delay ) ) {
					sleep( $delay );
				}
				ewww_image_optimizer_debug_log();
			}
		}
		ewww_image_optimizer_aux_images_cleanup( true );
	}
	ewwwio_memory( __FUNCTION__ );
	return;
}

// optimizes the images that have been deferred for later processing
function ewww_image_optimizer_defer() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $ewww_defer;
	$ewww_defer = false;
	$deferred_attachments = get_option( 'ewww_image_optimizer_defer_attachments' );
	if ( empty( $deferred_attachments ) ) {
		return;
	}
	$start_time = time();
	$delay = ewww_image_optimizer_get_option( 'ewww_image_optimizer_delay' );
	foreach ( $deferred_attachments as $image ) {
		list( $type, $id ) = explode( ',', $image, 2 );
		switch ( $type ) {
			case 'media':
				ewwwio_debug_message( "processing deferred $type: $id" );
				$meta = wp_get_attachment_metadata( $id, true );
				// do the optimization for the current attachment (including resizes)
				$meta = ewww_image_optimizer_resize_from_meta_data( $meta, $id, false );
				// update the metadata for the current attachment
				wp_update_attachment_metadata( $id, $meta );
				break;
			case 'nextgen2':
				ewwwio_debug_message( "processing deferred $type: $id" );
				// creating the 'registry' object for working with nextgen
				$registry = C_Component_Registry::get_instance();
				// creating a database storage object from the 'registry' object
				$storage  = $registry->get_utility( 'I_Gallery_Storage' );
				// get an image object
				$ngg_image = $storage->object->_image_mapper->find( $id );
				ewwwngg::ewww_added_new_image( $ngg_image, $storage );
				break;
			case 'nextcellent':
				ewwwio_debug_message( "processing deferred $type: $id" );
				ewwwngg::ewww_ngg_optimize( $id );
				break;
			case 'flag':
				ewwwio_debug_message( "processing deferred $type: $id" );
				$flag_image = flagdb::find_image( $id );
				ewwwflag::ewww_added_new_image ($flag_image);
				break;
			case 'file':
				ewwwio_debug_message( "processing deferred $type: $id" );
				ewww_image_optimizer($id);
				break;
			default:
				ewwwio_debug_message( "unknown type in deferrred queue: $type, $id" );
		}
		ewww_image_optimizer_remove_deferred_attachment( $image );
		$elapsed_time = time() - $start_time;
		ewwwio_debug_message( "time elapsed during deferred opt: $elapsed_time" );
		global $ewww_exceed;
		if ( ! empty ( $ewww_exceed ) ) {
			ewwwio_debug_message( 'Deferred opt aborted, license exceeded' );
			die();
		}
		ewww_image_optimizer_debug_log();
		if ( ! empty( $delay ) ) {
			sleep( $delay );
		}
		// prevent running longer than an hour
		if ( $elapsed_time > 3600 ) {
			return;
		}
	}
	ewwwio_memory( __FUNCTION__ );
	return;
}

// add an attachment/image to the queue
function ewww_image_optimizer_add_deferred_attachment( $id ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_debug_message( "adding $id to the queue" );
	$deferred_attachments = get_option( 'ewww_image_optimizer_defer_attachments' );
	$deferred_attachments[] = $id;
	update_option( 'ewww_image_optimizer_defer_attachments', $deferred_attachments, false );
	ewwwio_memory( __FUNCTION__ );
}

// remove a processed attachment/image from the queue
function ewww_image_optimizer_remove_deferred_attachment( $id ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_debug_message( "removing $id from the queue" );
	$deferred_attachments = get_option( 'ewww_image_optimizer_defer_attachments' );
	if ( ( $key = array_search( $id, $deferred_attachments ) ) !== false ) {
		unset( $deferred_attachments[$key] );
	}
	update_option( 'ewww_image_optimizer_defer_attachments', $deferred_attachments, false );
	ewwwio_memory( __FUNCTION__ );
}

// removes the network settings when the plugin is deactivated
function ewww_image_optimizer_network_deactivate( $network_wide ) {
	global $wpdb;
	wp_clear_scheduled_hook( 'ewww_image_optimizer_auto' );
	wp_clear_scheduled_hook( 'ewww_image_optimizer_defer' );
	if ( $network_wide ) {
		$query = $wpdb->prepare( "SELECT blog_id FROM $wpdb->blogs WHERE site_id = %d", $wpdb->siteid );
		$blogs = $wpdb->get_results( $query, ARRAY_A );
		foreach ( $blogs as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			wp_clear_scheduled_hook( 'ewww_image_optimizer_auto' );
			wp_clear_scheduled_hook( 'ewww_image_optimizer_defer' );
		}
		restore_current_blog();
	}
}

// adds a global settings page to the network admin settings menu
function ewww_image_optimizer_network_admin_menu() {
	if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
		// add options page to the settings menu
		$permissions = apply_filters( 'ewww_image_optimizer_superadmin_permissions', '' );
		$ewww_network_options_page = add_submenu_page(
			'settings.php',				//slug of parent
			'EWWW Image Optimizer',			//Title
			'EWWW Image Optimizer',			//Sub-menu title
			$permissions,				//Security
			EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE,	//File to open
			'ewww_image_optimizer_options'		//Function to call
		);
	} 
}

// adds the bulk optimize and settings page to the admin menu
function ewww_image_optimizer_admin_menu() {
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	// adds bulk optimize to the media library menu
	$ewww_bulk_page = add_media_page( esc_html__( 'Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ), esc_html__( 'Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $permissions, 'ewww-image-optimizer-bulk', 'ewww_image_optimizer_bulk_preview' );
	$ewww_unoptimized_page = add_media_page( esc_html__( 'Unoptimized Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ), esc_html__( 'Unoptimized Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $permissions, 'ewww-image-optimizer-unoptimized', 'ewww_image_optimizer_display_unoptimized_media' );
	$ewww_webp_migrate_page = add_submenu_page( null, esc_html__( 'Migrate WebP Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ), esc_html__( 'Migrate WebP Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $permissions, 'ewww-image-optimizer-webp-migrate', 'ewww_image_optimizer_webp_migrate_preview' );
	if ( ! function_exists( 'is_plugin_active' ) ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if ( ! is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
		// add options page to the settings menu
		$ewww_options_page = add_options_page(
			'EWWW Image Optimizer',		//Title
			'EWWW Image Optimizer',		//Sub-menu title
			apply_filters( 'ewww_image_optimizer_admin_permissions', '' ),		//Security
			EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE,			//File to open
			'ewww_image_optimizer_options'	//Function to call
		);
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_debug' ) ) {
		add_media_page( esc_html__( 'Dynamic Image Debugging', EWWW_IMAGE_OPTIMIZER_DOMAIN ), esc_html__( 'Dynamic Image Debugging', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $permissions, 'ewww-image-optimizer-dynamic-debug', 'ewww_image_optimizer_dynamic_image_debug' );
	}
	if ( is_plugin_active( 'image-store/ImStore.php' ) || is_plugin_active_for_network( 'image-store/ImStore.php' ) ) {
		$ims_menu ='edit.php?post_type=ims_gallery';
		$ewww_ims_page = add_submenu_page( $ims_menu, esc_html__( 'Image Store Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ), esc_html__( 'Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ), 'ims_change_settings', 'ewww-ims-optimize', 'ewww_image_optimizer_ims');
//		add_action( 'admin_footer-' . $ewww_ims_page, 'ewww_image_optimizer_debug' );
	}
}

// check WP Retina images, fixes filenames in the database, and makes sure all derivatives are optimized
function ewww_image_optimizer_retina( $id, $retina_path ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	$file_info = pathinfo( $retina_path );
	$extension = '.' . $file_info['extension'];
	preg_match ( '/-(\d+x\d+)@2x$/', $file_info['filename'], $fileresize );
	$dimensions = explode ( 'x', $fileresize[1]);
	$no_ext_path = $file_info['dirname'] . '/' . preg_replace( '/\d+x\d+@2x$/', '', $file_info['filename'] ) . $dimensions[0] * 2 . 'x' . $dimensions[1] * 2 . '-tmp';
	$temp_path = $no_ext_path . $extension;
	ewwwio_debug_message( "temp path: $temp_path" );
	// check for any orphaned webp retina images also, and fix their paths
	ewwwio_debug_message( "retina path: $retina_path" );
	$webp_path = $temp_path . '.webp';
	ewwwio_debug_message( "retina webp path: $webp_path" );
	if ( file_exists( $webp_path ) ) {
		rename( $webp_path, $retina_path . '.webp' );
	}
	$opt_size = ewww_image_optimizer_filesize( $retina_path );
	ewwwio_debug_message( "retina size: $opt_size" );
	$query = $wpdb->prepare( "SELECT id,path FROM $wpdb->ewwwio_images WHERE path = %s AND image_size = '$opt_size'", $temp_path );
	$optimized_query = $wpdb->get_results( $query, ARRAY_A );
	if ( ! empty( $optimized_query ) ) {
		foreach ( $optimized_query as $image ) {
			if ( $image['path'] != $temp_path ) {
				ewwwio_debug_message( "{$image['path']} does not match $temp_path, continuing our search" );
			} else {
				$already_optimized = $image;
			}
		}
	}
	if ( ! empty( $already_optimized ) ) {
		// store info on the current image for future reference
		$wpdb->update( $wpdb->ewwwio_images,
			array(
				'path' => $retina_path,
			),
			array(
				'id' => $already_optimized['id'],
			));
	} else {
		ewww_image_optimizer( $retina_path, 7, false, false );
	}
	ewwwio_memory( __FUNCTION__ );
}

// list IMS images and optimization status
function ewww_image_optimizer_ims() {
	global $wpdb;
	$ims_columns = get_column_headers( 'ims_gallery' );
	echo "<div class='wrap'><h1>" . esc_html__('Image Store Optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</h1>";
	if ( empty( $_REQUEST['ewww_gid'] ) ) {
		$galleries = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'ims_gallery' ORDER BY ID" );
		$gallery_string = implode( ',', $galleries );
		echo "<p>" . esc_html__('Choose a gallery or', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <a href='upload.php?page=ewww-image-optimizer-bulk&ids=$gallery_string'>" . esc_html__('optimize all galleries', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></p>";
		echo '<table class="wp-list-table widefat media" cellspacing="0"><thead><tr><th>' . esc_html__('Gallery ID', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th><th>' . esc_html__('Gallery Name', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th><th>' . esc_html__('Images', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th><th>' . esc_html__('Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th></tr></thead>';
			foreach ( $galleries as $gid ) {
				$image_count = $wpdb->get_var( "SELECT count(ID) FROM $wpdb->posts WHERE post_type = 'ims_image' AND post_mime_type LIKE '%%image%%' AND post_parent = $gid" );
				$gallery_name = get_the_title( $gid );
				echo "<tr><td>$gid</td>";
				echo "<td><a href='edit.php?post_type=ims_gallery&page=ewww-ims-optimize&ewww_gid=$gid'>$gallery_name</a></td>";
				echo "<td>$image_count</td>";
				echo "<td><a href='upload.php?page=ewww-image-optimizer-bulk&ids=$gid'>" . esc_html__('Optimize Gallery', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></td></tr>";
			}
			echo "</table>";
	} else {
		$gid = (int) $_REQUEST['ewww_gid'];
		$attachments = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'ims_image' AND post_mime_type LIKE '%%image%%' AND post_parent = $gid ORDER BY ID" );
		echo "<p><a href='upload.php?page=ewww-image-optimizer-bulk&ids=$gid'>" . esc_html__('Optimize Gallery', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></p>";
		echo '<table class="wp-list-table widefat media" cellspacing="0"><thead><tr><th>ID</th><th>&nbsp;</th><th>' . esc_html__('Title', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th><th>' . esc_html__('Gallery', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th><th>' . esc_html__('Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th></tr></thead>';
		$alternate = true;
		foreach ( $attachments as $ID ) {
			$meta = get_metadata( 'post', $ID );
			if ( empty( $meta['_wp_attachment_metadata'] ) ) {
				continue;
			}
			$meta = maybe_unserialize( $meta['_wp_attachment_metadata'][0] );
			$image_name = get_the_title( $ID );
			$gallery_name = get_the_title( $gid );
			$image_url = esc_url( $meta['sizes']['mini']['url'] );
?>			<tr<?php if( $alternate ) echo " class='alternate'"; ?>><td><?php echo $ID; ?></td>
<?php			echo "<td style='width:80px' class='column-icon'><img src='$image_url' /></td>";
			echo "<td class='title'>$image_name</td>";
			echo "<td>$gallery_name</td><td>";
			ewww_image_optimizer_custom_column( 'ewww-image-optimizer', $ID );
			echo "</td></tr>";
			$alternate = !$alternate;
		}
		echo '</table>';
	}
	echo '</div>';
	return;	
}

// optimize MyArcade screenshots and thumbs
function ewww_image_optimizer_myarcade_thumbnail( $url ) {
	ewwwio_debug_message( "thumb url passed: $url" );
	if ( ! empty( $url ) ) {
        	$thumb_path = str_replace( get_option('siteurl') . '/', ABSPATH, $url );
		ewwwio_debug_message( "myarcade thumb path generated: $thumb_path" );
		ewww_image_optimizer( $thumb_path );
	}
	return $url;
}

//load full webp script for debugging
function ewww_image_optimizer_webp_debug_script() {
	wp_enqueue_script( 'ewww-webp-load-script', plugins_url( '/includes/load_webp.js', __FILE__ ), array( 'jquery' ) );
}

// enqueue script dependency for alt webp rewriting
function ewww_image_optimizer_webp_load_jquery() {
	wp_enqueue_script('jquery');
}

// load minified inline version of webp script from jscompress.com
function ewww_image_optimizer_webp_inline_script() {
?>
<script>
function check_webp_feature(t,e){var a={lossy:"UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA",lossless:"UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==",alpha:"UklGRkoAAABXRUJQVlA4WAoAAAAQAAAAAAAAAAAAQUxQSAwAAAARBxAR/Q9ERP8DAABWUDggGAAAABQBAJ0BKgEAAQAAAP4AAA3AAP7mtQAAAA==",animation:"UklGRlIAAABXRUJQVlA4WAoAAAASAAAAAAAAAAAAQU5JTQYAAAD/////AABBTk1GJgAAAAAAAAAAAAAAAAAAAGQAAABWUDhMDQAAAC8AAAAQBxAREYiI/gcA"},n=!1,r=new Image;r.onload=function(){var t=r.width>0&&r.height>0;n=!0,e(t)},r.onerror=function(){n=!1,e(!1)},r.src="data:image/webp;base64,"+a[t],jQuery(document).arrive(".ewww_webp",function(){ewww_load_images(n)})}function ewww_load_images(t){!function(e){t&&(e(".batch-image img, .image-wrapper a, .ngg-pro-masonry-item a").each(function(){var t=e(this).attr("data-webp");"undefined"!=typeof t&&t!==!1&&e(this).attr("data-src",t);var t=e(this).attr("data-webp-thumbnail");"undefined"!=typeof t&&t!==!1&&e(this).attr("data-thumbnail",t)}),e(".image-wrapper a, .ngg-pro-masonry-item a").each(function(){var t=e(this).attr("data-webp");"undefined"!=typeof t&&t!==!1&&e(this).attr("href",t)}),e(".rev_slider ul li").each(function(){var t=e(this).attr("data-webp-thumb");"undefined"!=typeof t&&t!==!1&&e(this).attr("data-thumb",t);for(var a=1;11>a;){var t=e(this).attr("data-webp-param"+a);"undefined"!=typeof t&&t!==!1&&e(this).attr("data-param"+a,t),a++}}),e(".rev_slider img").each(function(){var t=e(this).attr("data-webp-lazyload");"undefined"!=typeof t&&t!==!1&&e(this).attr("data-lazyload",t)})),e(".ewww_webp").each(function(){var a=document.createElement("img");if(t){e(a).attr("src",e(this).attr("data-webp"));var n=e(this).attr("data-srcset-webp");"undefined"!=typeof n&&n!==!1&&e(a).attr("srcset",n)}else{e(a).attr("src",e(this).attr("data-img"));var n=e(this).attr("data-srcset-img");"undefined"!=typeof n&&n!==!1&&e(a).attr("srcset",n)}var n=e(this).attr("data-align");"undefined"!=typeof n&&n!==!1&&e(a).attr("align",n);var n=e(this).attr("data-alt");"undefined"!=typeof n&&n!==!1&&e(a).attr("alt",n);var n=e(this).attr("data-border");"undefined"!=typeof n&&n!==!1&&e(a).attr("border",n);var n=e(this).attr("data-crossorigin");"undefined"!=typeof n&&n!==!1&&e(a).attr("crossorigin",n);var n=e(this).attr("data-height");"undefined"!=typeof n&&n!==!1&&e(a).attr("height",n);var n=e(this).attr("data-hspace");"undefined"!=typeof n&&n!==!1&&e(a).attr("hspace",n);var n=e(this).attr("data-ismap");"undefined"!=typeof n&&n!==!1&&e(a).attr("ismap",n);var n=e(this).attr("data-longdesc");"undefined"!=typeof n&&n!==!1&&e(a).attr("longdesc",n);var n=e(this).attr("data-usemap");"undefined"!=typeof n&&n!==!1&&e(a).attr("usemap",n);var n=e(this).attr("data-vspace");"undefined"!=typeof n&&n!==!1&&e(a).attr("vspace",n);var n=e(this).attr("data-width");"undefined"!=typeof n&&n!==!1&&e(a).attr("width",n);var n=e(this).attr("data-accesskey");"undefined"!=typeof n&&n!==!1&&e(a).attr("accesskey",n);var n=e(this).attr("data-class");"undefined"!=typeof n&&n!==!1&&e(a).attr("class",n);var n=e(this).attr("data-contenteditable");"undefined"!=typeof n&&n!==!1&&e(a).attr("contenteditable",n);var n=e(this).attr("data-contextmenu");"undefined"!=typeof n&&n!==!1&&e(a).attr("contextmenu",n);var n=e(this).attr("data-dir");"undefined"!=typeof n&&n!==!1&&e(a).attr("dir",n);var n=e(this).attr("data-draggable");"undefined"!=typeof n&&n!==!1&&e(a).attr("draggable",n);var n=e(this).attr("data-dropzone");"undefined"!=typeof n&&n!==!1&&e(a).attr("dropzone",n);var n=e(this).attr("data-hidden");"undefined"!=typeof n&&n!==!1&&e(a).attr("hidden",n);var n=e(this).attr("data-id");"undefined"!=typeof n&&n!==!1&&e(a).attr("id",n);var n=e(this).attr("data-lang");"undefined"!=typeof n&&n!==!1&&e(a).attr("lang",n);var n=e(this).attr("data-spellcheck");"undefined"!=typeof n&&n!==!1&&e(a).attr("spellcheck",n);var n=e(this).attr("data-style");"undefined"!=typeof n&&n!==!1&&e(a).attr("style",n);var n=e(this).attr("data-tabindex");"undefined"!=typeof n&&n!==!1&&e(a).attr("tabindex",n);var n=e(this).attr("data-title");"undefined"!=typeof n&&n!==!1&&e(a).attr("title",n);var n=e(this).attr("data-translate");"undefined"!=typeof n&&n!==!1&&e(a).attr("translate",n);var n=e(this).attr("data-sizes");"undefined"!=typeof n&&n!==!1&&e(a).attr("sizes",n),e(this).after(a),e(this).removeClass("ewww_webp")})}(jQuery),jQuery.fn.isotope&&jQuery.fn.imagesLoaded&&(jQuery(".fusion-posts-container-infinite").imagesLoaded(function(){jQuery(".fusion-posts-container-infinite").hasClass("isotope")&&jQuery(".fusion-posts-container-infinite").isotope()}),jQuery(".fusion-portfolio:not(.fusion-recent-works) .fusion-portfolio-wrapper").imagesLoaded(function(){jQuery(".fusion-portfolio:not(.fusion-recent-works) .fusion-portfolio-wrapper").isotope()}))}var Arrive=function(t,e,a){"use strict";function n(t,e,a){o.addMethod(e,a,t.unbindEvent),o.addMethod(e,a,t.unbindEventWithSelectorOrCallback),o.addMethod(e,a,t.unbindEventWithSelectorAndCallback)}function r(t){t.arrive=f.bindEvent,n(f,t,"unbindArrive"),t.leave=u.bindEvent,n(u,t,"unbindLeave")}if(t.MutationObserver&&"undefined"!=typeof HTMLElement){var i=0,o=function(){var e=HTMLElement.prototype.matches||HTMLElement.prototype.webkitMatchesSelector||HTMLElement.prototype.mozMatchesSelector||HTMLElement.prototype.msMatchesSelector;return{matchesSelector:function(t,a){return t instanceof HTMLElement&&e.call(t,a)},addMethod:function(t,e,a){var n=t[e];t[e]=function(){return a.length==arguments.length?a.apply(this,arguments):"function"==typeof n?n.apply(this,arguments):void 0}},callCallbacks:function(t){for(var e,a=0;e=t[a];a++)e.callback.call(e.elem)},checkChildNodesRecursively:function(t,e,a,n){for(var r,i=0;r=t[i];i++)a(r,e,n)&&n.push({callback:e.callback,elem:r}),r.childNodes.length>0&&o.checkChildNodesRecursively(r.childNodes,e,a,n)},mergeArrays:function(t,e){var a,n={};for(a in t)n[a]=t[a];for(a in e)n[a]=e[a];return n},toElementsArray:function(e){return"undefined"==typeof e||"number"==typeof e.length&&e!==t||(e=[e]),e}}}(),d=function(){var t=function(){this._eventsBucket=[],this._beforeAdding=null,this._beforeRemoving=null};return t.prototype.addEvent=function(t,e,a,n){var r={target:t,selector:e,options:a,callback:n,firedElems:[]};return this._beforeAdding&&this._beforeAdding(r),this._eventsBucket.push(r),r},t.prototype.removeEvent=function(t){for(var e,a=this._eventsBucket.length-1;e=this._eventsBucket[a];a--)t(e)&&(this._beforeRemoving&&this._beforeRemoving(e),this._eventsBucket.splice(a,1))},t.prototype.beforeAdding=function(t){this._beforeAdding=t},t.prototype.beforeRemoving=function(t){this._beforeRemoving=t},t}(),s=function(e,n){var r=new d,i=this,s={fireOnAttributesModification:!1};return r.beforeAdding(function(a){var r,o=a.target;a.selector,a.callback;(o===t.document||o===t)&&(o=document.getElementsByTagName("html")[0]),r=new MutationObserver(function(t){n.call(this,t,a)});var d=e(a.options);r.observe(o,d),a.observer=r,a.me=i}),r.beforeRemoving(function(t){t.observer.disconnect()}),this.bindEvent=function(t,e,a){e=o.mergeArrays(s,e);for(var n=o.toElementsArray(this),i=0;i<n.length;i++)r.addEvent(n[i],t,e,a)},this.unbindEvent=function(){var t=o.toElementsArray(this);r.removeEvent(function(e){for(var n=0;n<t.length;n++)if(this===a||e.target===t[n])return!0;return!1})},this.unbindEventWithSelectorOrCallback=function(t){var e,n=o.toElementsArray(this),i=t;e="function"==typeof t?function(t){for(var e=0;e<n.length;e++)if((this===a||t.target===n[e])&&t.callback===i)return!0;return!1}:function(e){for(var r=0;r<n.length;r++)if((this===a||e.target===n[r])&&e.selector===t)return!0;return!1},r.removeEvent(e)},this.unbindEventWithSelectorAndCallback=function(t,e){var n=o.toElementsArray(this);r.removeEvent(function(r){for(var i=0;i<n.length;i++)if((this===a||r.target===n[i])&&r.selector===t&&r.callback===e)return!0;return!1})},this},l=function(){function t(t){var e={attributes:!1,childList:!0,subtree:!0};return t.fireOnAttributesModification&&(e.attributes=!0),e}function e(t,e){t.forEach(function(t){var a=t.addedNodes,r=t.target,i=[];null!==a&&a.length>0?o.checkChildNodesRecursively(a,e,n,i):"attributes"===t.type&&n(r,e,i)&&i.push({callback:e.callback,elem:node}),o.callCallbacks(i)})}function n(t,e,n){if(o.matchesSelector(t,e.selector)&&(t._id===a&&(t._id=i++),-1==e.firedElems.indexOf(t._id))){if(e.options.onceOnly){if(0!==e.firedElems.length)return;e.me.unbindEventWithSelectorAndCallback.call(e.target,e.selector,e.callback)}e.firedElems.push(t._id),n.push({callback:e.callback,elem:t})}}var r={fireOnAttributesModification:!1,onceOnly:!1,existing:!1};f=new s(t,e);var d=f.bindEvent;return f.bindEvent=function(t,e,a){"undefined"==typeof a?(a=e,e=r):e=o.mergeArrays(r,e);var n=o.toElementsArray(this);if(e.existing){for(var i=[],s=0;s<n.length;s++)for(var l=n[s].querySelectorAll(t),c=0;c<l.length;c++)i.push({callback:a,elem:l[c]});if(e.onceOnly&&i.length)return a.call(i[0].elem);setTimeout(o.callCallbacks,1,i)}d.call(this,t,e,a)},f},c=function(){function t(t){var e={childList:!0,subtree:!0};return e}function e(t,e){t.forEach(function(t){var n=t.removedNodes,r=(t.target,[]);null!==n&&n.length>0&&o.checkChildNodesRecursively(n,e,a,r),o.callCallbacks(r)})}function a(t,e){return o.matchesSelector(t,e.selector)}var n={};u=new s(t,e);var r=u.bindEvent;return u.bindEvent=function(t,e,a){"undefined"==typeof a?(a=e,e=n):e=o.mergeArrays(n,e),r.call(this,t,e,a)},u},f=new l,u=new c;e&&r(e.fn),r(HTMLElement.prototype),r(NodeList.prototype),r(HTMLCollection.prototype),r(HTMLDocument.prototype),r(Window.prototype);var A={};return n(f,A,"unbindAllArrive"),n(u,A,"unbindAllLeave"),A}}(window,"undefined"==typeof jQuery?null:jQuery,void 0);"undefined"!==jQuery&&check_webp_feature("alpha",ewww_load_images);
</script>
<?php
}

// enqueue custom jquery stylesheet for bulk optimizer
function ewww_image_optimizer_media_scripts( $hook ) {
	if ( $hook == 'upload.php' ) {
		wp_enqueue_script( 'jquery-ui-tooltip' );
		wp_enqueue_style( 'jquery-ui-tooltip-custom', plugins_url( '/includes/jquery-ui-1.10.1.custom.css', __FILE__ ) );
	}
}

// used to output debug messages to a logfile in the plugin folder in cases where output to the screen is a bad idea
function ewww_image_optimizer_debug_log() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $ewww_debug;
	if (! empty( $ewww_debug ) && ewww_image_optimizer_get_option( 'ewww_image_optimizer_debug' ) ) {
		$timestamp = date( 'y-m-d h:i:s.u' ) . "  ";
		if ( ! file_exists( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'debug.log' ) ) {
			touch( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'debug.log' );
		}
		$ewww_debug_log = str_replace( '<br>', "\n", $ewww_debug );
		file_put_contents( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'debug.log', $timestamp . $ewww_debug_log, FILE_APPEND );
	}
	$ewww_debug = '';
	ewwwio_memory( __FUNCTION__ );
}

// adds a link on the Plugins page for the EWWW IO settings
function ewww_image_optimizer_settings_link( $links ) {
	if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	// load the html for the settings link
	if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
		$settings_link = '<a href="network/settings.php?page=' . plugin_basename( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE ) . '">' . esc_html__( 'Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</a>';
	} else {
		$settings_link = '<a href="options-general.php?page=' . plugin_basename( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE ) . '">' . esc_html__( 'Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</a>';
	}
	// load the settings link into the plugin links array
	array_unshift( $links, $settings_link );
	// send back the plugin links array
	return $links;
}

// check for GD support of both PNG and JPG
function ewww_image_optimizer_gd_support() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( function_exists( 'gd_info' ) ) {
		$gd_support = gd_info();
		ewwwio_debug_message( "GD found, supports:" ); 
		foreach ( $gd_support as $supports => $supported ) {
			 ewwwio_debug_message( "$supports: $supported" );
		}
		ewwwio_memory( __FUNCTION__ );
		if ( ( ! empty( $gd_support["JPEG Support"] ) || ! empty( $gd_support["JPG Support"] ) ) && ! empty( $gd_support["PNG Support"] ) ) {
			return TRUE;
		}
	}
	return FALSE;
}

// check for IMagick support of both PNG and JPG
function ewww_image_optimizer_imagick_support() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( extension_loaded( 'imagick' ) ) {
		$imagick = new Imagick();
		$formats = $imagick->queryFormats();
		if ( in_array( 'PNG', $formats ) && in_array( 'JPG', $formats ) ) {
			return true;
		}
	}
	return false;
}

// check for IMagick support of both PNG and JPG
function ewww_image_optimizer_gmagick_support() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( extension_loaded( 'gmagick' ) ) {
		$gmagick = new Gmagick();
		$formats = $gmagick->queryFormats();
		if ( in_array( 'PNG', $formats ) && in_array( 'JPG', $formats ) ) {
			return true;
		}
	}
	return false;
}

function ewww_image_optimizer_aux_paths_sanitize( $input ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if (empty($input)) {
		return '';
	}
	$path_array = array();
	$paths = explode("\n", $input);
	foreach ($paths as $path) {
		$path = sanitize_text_field($path);
		ewwwio_debug_message( "validating auxiliary path: $path" );
		// retrieve the location of the wordpress upload folder
		$upload_dir = apply_filters( 'ewww_image_optimizer_folder_restriction', wp_upload_dir() );
		// retrieve the path of the upload folder
		$upload_path = trailingslashit($upload_dir['basedir']);
		if ( is_dir( $path ) && ( strpos( $path, ABSPATH ) === 0 || strpos( $path, $upload_path ) === 0 ) ) {
			$path_array[] = $path;
		}
	}
//	ewww_image_optimizer_debug_log();
	ewwwio_memory( __FUNCTION__ );
	return $path_array;
}

// replacement for escapeshellarg() that won't kill non-ASCII characters
function ewww_image_optimizer_escapeshellarg( $arg ) {
	//ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( PHP_OS === 'WINNT' ) {
		$safe_arg = '"' . $arg . '"';
	} else {
		$safe_arg = "'" . str_replace("'", "'\"'\"'", $arg) . "'";
	}
	ewwwio_memory( __FUNCTION__ );
	return $safe_arg;
}

// Retrieves/sanitizes jpg background fill setting or returns null for png2jpg conversions
function ewww_image_optimizer_jpg_background( $background = null ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( $background === null ) {
		// retrieve the user-supplied value for jpg background color
		$background = ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_background' );
	}
	//verify that the supplied value is in hex notation
	if ( preg_match( '/^\#*([0-9a-fA-F]){6}$/', $background ) ) {
		// we remove a leading # symbol, since we take care of it later
		preg_replace( '/#/', '', $background );
		// send back the verified, cleaned-up background color
		ewwwio_debug_message( "background: $background" );
		ewwwio_memory( __FUNCTION__ );
		return $background;
	} else {
		// send back a blank value
		ewwwio_memory( __FUNCTION__ );
		return NULL;
	}
}

// Retrieves/sanitizes the jpg quality setting for png2jpg conversion or returns null
function ewww_image_optimizer_jpg_quality( $quality = null ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( $quality === null ) {
		// retrieve the user-supplied value for jpg quality
		$quality = ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_quality' );
	}
	// verify that the quality level is an integer, 1-100
	if ( preg_match( '/^(100|[1-9][0-9]?)$/', $quality ) ) {
		ewwwio_debug_message( "quality: $quality" );
		// send back the valid quality level
		ewwwio_memory( __FUNCTION__ );
		return $quality;
	} else {
		// send back nothing
		ewwwio_memory( __FUNCTION__ );
		return NULL;
	}
}

// check filesize, and prevent errors by ensuring file exists, and that the cache has been cleared
function ewww_image_optimizer_filesize( $file ) {
	if ( is_file( $file ) ) {
		// flush the cache for filesize
		clearstatcache();
		// find out the size of the new PNG file
		return filesize( $file );
	} else {
		return 0;
	}
}

/**
 * Manually process an image from the Media Library
 */
function ewww_image_optimizer_manual() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $ewww_defer;
	$ewww_defer = false;
	// check permissions of current user
	$permissions = apply_filters( 'ewww_image_optimizer_manual_permissions', '' );
	if ( FALSE === current_user_can( $permissions ) ) {
		// display error message if insufficient permissions
		wp_die( esc_html__( 'You do not have permission to optimize images.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	// make sure we didn't accidentally get to this page without an attachment to work on
	if ( FALSE === isset($_GET['ewww_attachment_ID'])) {
		// display an error message since we don't have anything to work on
		wp_die( esc_html__('No attachment ID was provided.', EWWW_IMAGE_OPTIMIZER_DOMAIN));
	}
	// store the attachment ID value
	$attachment_ID = intval($_GET['ewww_attachment_ID']);
	if ( empty( $_REQUEST['ewww_manual_nonce'] ) || ! wp_verify_nonce( $_REQUEST['ewww_manual_nonce'], "ewww-manual-$attachment_ID" ) ) {
		wp_die( esc_html__( 'Access denied.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	// retrieve the existing attachment metadata
	$original_meta = wp_get_attachment_metadata( $attachment_ID );
	// if the call was to optimize...
	if ($_REQUEST['action'] === 'ewww_image_optimizer_manual_optimize') {
		// call the optimize from metadata function and store the resulting new metadata
		$new_meta = ewww_image_optimizer_resize_from_meta_data($original_meta, $attachment_ID);
	} elseif ($_REQUEST['action'] === 'ewww_image_optimizer_manual_restore') {
		$new_meta = ewww_image_optimizer_restore_from_meta_data($original_meta, $attachment_ID);
	}
	global $ewww_attachment;
	$ewww_attachment['id'] = $attachment_ID;
	$ewww_attachment['meta'] = $new_meta;
	add_filter( 'w3tc_cdn_update_attachment_metadata', 'ewww_image_optimizer_w3tc_update_files' );
	// update the attachment metadata in the database
	wp_update_attachment_metadata( $attachment_ID, $new_meta );
	// store the referring webpage location
	$sendback = wp_get_referer();
	// sanitize the referring webpage location
	$sendback = preg_replace('|[^a-z0-9-~+_.?#=&;,/:]|i', '', $sendback);
	// send the user back where they came from
	wp_redirect($sendback);
	// we are done, nothing to see here
	ewwwio_memory( __FUNCTION__ );
	exit(0);
}

/**
 * Manually restore a converted image
 */
function ewww_image_optimizer_restore_from_meta_data( $meta, $id ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// get the filepath
	list( $file_path, $upload_path ) = ewww_image_optimizer_attachment_path( $meta, $id );
	$file_path = get_attached_file( $id );
	if ( ! empty( $meta['converted'] ) ) {
		if ( file_exists( $meta['orig_file'] ) ) {
			// update the filename in the metadata
			$meta['file'] = $meta['orig_file'];
			// update the optimization results in the metadata
			$meta['ewww_image_optimizer'] = __( 'Original Restored', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			$meta['orig_file'] = $file_path;
			$meta['real_orig_file'] = $file_path;
			$meta['converted'] = 0;
			unlink( $meta['orig_file'] );
			unset( $meta['orig_file'] );
			$meta['file'] = str_replace($upload_path, '', $meta['file']);
			// if we don't already have the update attachment filter
			if (FALSE === has_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment'))
				// add the update attachment filter
				add_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment', 10, 2);
		} else {
			remove_filter('wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment', 10);
		}
	}
	if ( isset( $meta['sizes'] ) ) {
		// process each resized version
		$processed = array();
		// meta sizes don't contain a path, so we calculate one
		$base_dir = trailingslashit( dirname( $file_path ) );
		foreach( $meta['sizes'] as $size => $data ) {
			// check through all the sizes we've processed so far
			foreach( $processed as $proc => $scan ) {
				// if a previous resize had identical dimensions
				if ( $scan['height'] == $data['height'] && $scan['width'] == $data['width'] && isset( $meta['sizes'][ $proc ]['converted'] ) ) {
					// point this resize at the same image as the previous one
					$meta['sizes'][ $size ]['file'] = $meta['sizes'][ $proc ]['file'];
				}
			}
			if ( isset( $data['converted'] ) ) {
				// if this is a unique size
				if ( file_exists( $base_dir . $data['orig_file'] ) ) {
					// update the filename
					$meta['sizes'][ $size ]['file'] = $data['orig_file'];
					// update the optimization results
					$meta['sizes'][ $size ]['ewww_image_optimizer'] = __( 'Original Restored', EWWW_IMAGE_OPTIMIZER_DOMAIN );
					$meta['sizes'][ $size ]['orig_file'] = $data['file'];
					$meta['sizes'][ $size ]['real_orig_file'] = $data['file'];
					$meta['sizes'][ $size ]['converted'] = 0;
						// if we don't already have the update attachment filter
						if ( FALSE === has_filter( 'wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment' ) ) {
							// add the update attachment filter
							add_filter( 'wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment', 10, 2 );
						}
					unlink( $base_dir . $data['file'] );
					unset( $meta['sizes'][ $size ]['orig_file'] );
				}
				// store info on the sizes we've processed, so we can check the list for duplicate sizes
				$processed[$size]['width'] = $data['width'];
				$processed[$size]['height'] = $data['height'];
			}		
		}
	}
	ewwwio_memory( __FUNCTION__ );
	return $meta;
}

// deletes 'orig_file' when an attachment is being deleted
function ewww_image_optimizer_delete ($id) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	// retrieve the image metadata
	$meta = wp_get_attachment_metadata($id);
	// if the attachment has an original file set
	if (!empty($meta['orig_file'])) {
		unset($rows);
		// get the filepath from the metadata
		$file_path = $meta['orig_file'];
		// get the filename
		$filename = basename($file_path);
		// delete any residual webp versions
		$webpfile = $filename . '.webp';
		$webpfileold = preg_replace( '/\.\w+$/', '.webp', $filename );
		if ( file_exists( $webpfile) ) {
			unlink( $webpfile );
		}
		if ( file_exists( $webpfileold) ) {
			unlink( $webpfileold );
		}
		// retrieve any posts that link the original image
		$esql = "SELECT ID, post_content FROM $wpdb->posts WHERE post_content LIKE '%$filename%'";
		$rows = $wpdb->get_row($esql);
		// if the original file still exists and no posts contain links to the image
		if (file_exists($file_path) && empty($rows)) {
			unlink($file_path);
			$wpdb->delete($wpdb->ewwwio_images, array('path' => $file_path));
		}
	}
	// remove the regular image from the ewwwio_images tables
	list($file_path, $upload_path) = ewww_image_optimizer_attachment_path($meta, $id);
	$wpdb->delete($wpdb->ewwwio_images, array('path' => $file_path));
	// resized versions, so we can continue
	if (isset($meta['sizes']) ) {
		// one way or another, $file_path is now set, and we can get the base folder name
		$base_dir = dirname($file_path) . '/';
		// check each resized version
		foreach($meta['sizes'] as $size => $data) {
			// delete any residual webp versions
			$webpfile = $base_dir . $data['file'] . '.webp';
			$webpfileold = preg_replace( '/\.\w+$/', '.webp', $base_dir . $data['file'] );
			if ( file_exists( $webpfile) ) {
				unlink( $webpfile );
			}
			if ( file_exists( $webpfileold) ) {
				unlink( $webpfileold );
			}
			$wpdb->delete($wpdb->ewwwio_images, array('path' => $base_dir . $data['file']));
			// if the original resize is set, and still exists
			if (!empty($data['orig_file']) && file_exists($base_dir . $data['orig_file'])) {
				unset($srows);
				// retrieve the filename from the metadata
				$filename = $data['orig_file'];
				// retrieve any posts that link the image
				$esql = "SELECT ID, post_content FROM $wpdb->posts WHERE post_content LIKE '%$filename%'";
				$srows = $wpdb->get_row($esql);
				// if there are no posts containing links to the original, delete it
				if(empty($srows)) {
					unlink($base_dir . $data['orig_file']);
					$wpdb->delete($wpdb->ewwwio_images, array('path' => $base_dir . $data['orig_file']));
				}
			}
		}
	}
	ewwwio_memory( __FUNCTION__ );
	return;
}

function ewww_image_optimizer_cloud_key_sanitize ( $key ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$key = trim( $key );
	if ( ewww_image_optimizer_cloud_verify( false, $key ) ) {
		ewwwio_debug_message( 'sanitize (verification) successful' );
		ewwwio_memory( __FUNCTION__ );
		return $key;
	} else {
		ewwwio_debug_message( 'sanitize (verification) failed' );
		ewwwio_memory( __FUNCTION__ );
		return '';
	}
}

// turns on the cloud settings when they are all disabled
function ewww_image_optimizer_cloud_enable () {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewww_image_optimizer_set_option('ewww_image_optimizer_cloud_jpg', true);
	ewww_image_optimizer_set_option('ewww_image_optimizer_cloud_png', true);
	ewww_image_optimizer_set_option('ewww_image_optimizer_cloud_gif', true);
}

// adds our version to the useragent for http requests
function ewww_image_optimizer_cloud_useragent ( $useragent ) {
	$useragent .= ' EWWW/' . EWWW_IMAGE_OPTIMIZER_VERSION;
	ewwwio_memory( __FUNCTION__ );
	return $useragent;
}
 
// submits the api key for verification
function ewww_image_optimizer_cloud_verify ( $cache = true, $api_key = '' ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $ewww_cloud_ip;
	global $ewww_cloud_transport;
	if ( empty( $api_key ) ) {
		$api_key = ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' );
	}
	if ( empty( $api_key ) ) {
		update_site_option( 'ewww_image_optimizer_cloud_jpg', '' );
		update_site_option( 'ewww_image_optimizer_cloud_png', '' );
		update_site_option( 'ewww_image_optimizer_cloud_gif', '' );
		update_option( 'ewww_image_optimizer_cloud_jpg', '' );
		update_option( 'ewww_image_optimizer_cloud_png', '' );
		update_option( 'ewww_image_optimizer_cloud_gif', '' );
		return false;
	}
	add_filter( 'http_headers_useragent', 'ewww_image_optimizer_cloud_useragent' );
	$prev_verified = get_option( 'ewww_image_optimizer_cloud_verified' );
	$last_checked = get_option( 'ewww_image_optimizer_cloud_last' );
	$ewww_cloud_ip = get_option( 'ewww_image_optimizer_cloud_ip' );
/*	if ( $cache && $prev_verified && $last_checked + 86400 > time() && ! empty( $ewww_cloud_ip ) ) {
		ewwwio_debug_message( "using cached IP: $ewww_cloud_ip" );
		if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_jpg' )  && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_png' ) && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_gif' ) ) {
			ewww_image_optimizer_cloud_enable();
		}
		return $prev_verified;	
	} else {*/
		$servers = gethostbynamel( 'optimize.exactlywww.com' );
		if ( empty ( $servers ) ) {
			ewwwio_debug_message( 'unable to resolve servers' );
			return false;
		}
		$ewww_cloud_transport = 'https';
		foreach ($servers as $ip) {
			$url = "$ewww_cloud_transport://$ip/verify/";
			$result = wp_remote_post($url, array(
				'timeout' => 5,
				'sslverify' => false,
				'body' => array('api_key' => $api_key)
			));
			if ( is_wp_error( $result ) ) {
				$ewww_cloud_transport = 'http';
				$error_message = $result->get_error_message();
				ewwwio_debug_message( "verification failed: $error_message" );
			} elseif ( ! empty( $result['body'] ) && preg_match( '/(great|exceeded)/', $result['body'] ) ) {
				$verified = $result['body'];
				$ewww_cloud_ip = $ip;
				ewwwio_debug_message( "verification success via: $ewww_cloud_transport://$ip" );
				break;
			} else {
				ewwwio_debug_message( "verification failed via: $ip" );
				ewwwio_debug_message( print_r($result, true) );
			}
		}
//	}
	if ( empty( $verified ) ) {
		ewwwio_memory( __FUNCTION__ );
		return FALSE;
	} else {
		if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_jpg' )  && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_png' ) && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_gif' ) ) {
			ewww_image_optimizer_cloud_enable();
		}
		ewwwio_debug_message( "verification body contents: {$result['body']}" );
		ewwwio_memory( __FUNCTION__ );
		return $verified;
	}
}

// checks the provided api key for quota information
function ewww_image_optimizer_cloud_quota() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $ewww_cloud_ip;
	global $ewww_cloud_transport;
	$api_key = ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' );
	$url = "$ewww_cloud_transport://$ewww_cloud_ip/quota/";
	$result = wp_remote_post( $url, array(
		'timeout' => 5,
		'sslverify' => false,
		'body' => array( 'api_key' => $api_key )
	) );
	if ( is_wp_error( $result ) ) {
		$error_message = $result->get_error_message();
		ewwwio_debug_message( "quota request failed: $error_message" );
		ewwwio_memory( __FUNCTION__ );
		return '';
	} elseif ( ! empty( $result['body'] ) ) {
		ewwwio_debug_message( "quota data retrieved: {$result['body']}" );
		$quota = explode(' ', $result['body']);
		ewwwio_memory( __FUNCTION__ );
		if ( $quota[0] == 0 && $quota[1] > 0 ) {
			return esc_html( sprintf( _n( 'optimized %1$d images, usage will reset in %2$d day.', 'optimized %1$d images, usage will reset in %2$d days.', $quota[2], EWWW_IMAGE_OPTIMIZER_DOMAIN ), $quota[1], $quota[2] ) );
		} elseif ( $quota[0] == 0 && $quota[1] < 0 ) {
			return esc_html( sprintf( _n( '%1$d image credit remaining.', '%1$d image credits remaining.', abs( $quota[1] ), EWWW_IMAGE_OPTIMIZER_DOMAIN ), abs( $quota[1] ) ) );
		} elseif ( $quota[0] > 0 && $quota[1] < 0 ) {
			$real_quota = $quota[0] - $quota[1];
			return esc_html( sprintf( _n( '%1$d image credit remaining.', '%1$d image credits remaining.', $real_quota, EWWW_IMAGE_OPTIMIZER_DOMAIN ), $real_quota ) );
		} else {
			return esc_html( sprintf( _n( 'used %1$d of %2$d, usage will reset in %3$d day.', 'used %1$d of %2$d, usage will reset in %3$d days.', $quota[2], EWWW_IMAGE_OPTIMIZER_DOMAIN ), $quota[1], $quota[0], $quota[2] ) );
		}
	}
}

/* submits an image to the cloud optimizer and saves the optimized image to disk
 *
 * Returns an array of the $file, $results, $converted to tell us if an image changes formats, and the $original file if it did.
 *
 * @param   string $file		Full absolute path to the image file
 * @param   string $type		mimetype of $file
 * @param   boolean $convert		true says we want to attempt conversion of $file
 * @param   string $newfile		filename of new converted image
 * @param   string $newtype		mimetype of $newfile
 * @param   boolean $fullsize		is this the full-size original?
 * @param   array $jpg_params		r, g, b values and jpg quality setting for conversion
 * @returns array
*/
function ewww_image_optimizer_cloud_optimizer( $file, $type, $convert = false, $newfile = null, $newtype = null, $fullsize = false, $jpg_params = array( 'r' => '255', 'g' => '255', 'b' => '255', 'quality' => null ) ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $ewww_exceed;
	global $ewww_cloud_ip;
	global $ewww_cloud_transport;
	$started = microtime( true );
	if ( empty( $ewww_cloud_ip ) || empty( $ewww_cloud_transport ) ) {
		if ( ! ewww_image_optimizer_cloud_verify( false ) ) { 
			return array( $file, false, 'key verification failed', 0 );
		}
	}
	// calculate how much time has elapsed since we started
	$elapsed = microtime( true ) - $started;
	// output how much time has elapsed since we started
	ewwwio_debug_message( sprintf( 'Cloud verify took %.3f seconds', $elapsed ) );
	if ( $ewww_exceed ) {
		ewwwio_debug_message( 'license exceeded, image not processed' );
		return array($file, false, 'exceeded', 0);
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_metadata_skip_full' ) && $fullsize ) {
		$metadata = 1;
	} elseif ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpegtran_copy' ) ){
        	// don't copy metadata
                $metadata = 0;
        } else {
                // copy all the metadata
                $metadata = 1;
        }
	if (empty($convert)) {
		$convert = 0;
	} else {
		$convert = 1;
	}
	if ( ewww_image_optimizer_get_option('ewww_image_optimizer_lossy_skip_full') && $fullsize ) {
		$lossy = 0;
	} elseif ( $type == 'image/png' && ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_lossy' ) ) {
		$lossy = 1;
	} elseif ( $type == 'image/jpeg' && ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_lossy' ) ) {
		$lossy = 1;
	} else {
		$lossy = 0;
	}
	if ( $lossy && ewww_image_optimizer_get_option( 'ewww_image_optimizer_lossy_fast' ) ) {
		$lossy_fast = 1;
	} else {
		$lossy_fast = 0;
	}
	if ( $newtype == 'image/webp' ) {
		$webp = 1;
	} else {
		$webp = 0;
	}
	ewwwio_debug_message( "file: $file " );
	ewwwio_debug_message( "type: $type" );
	ewwwio_debug_message( "convert: $convert" );
	ewwwio_debug_message( "newfile: $newfile" );
	ewwwio_debug_message( "newtype: $newtype" );
	ewwwio_debug_message( "webp: $webp" );
	ewwwio_debug_message( "jpg_params: " . print_r($jpg_params, true) );
	$api_key = ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_key');
	$url = "$ewww_cloud_transport://$ewww_cloud_ip/";
	$boundary = wp_generate_password(24, false);

	$headers = array(
        	'content-type' => 'multipart/form-data; boundary=' . $boundary,
		'timeout' => 90,
		'httpversion' => '1.0',
		'blocking' => true
		);
	$post_fields = array(
		'oldform' => 1, 
		'convert' => $convert, 
		'metadata' => $metadata, 
		'api_key' => $api_key,
		'red' => $jpg_params['r'],
		'green' => $jpg_params['g'],
		'blue' => $jpg_params['b'],
		'quality' => $jpg_params['quality'],
		'compress' => ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png_compress'),
		'lossy' => $lossy,
		'lossy_fast' => $lossy_fast,
		'webp' => $webp,
	);

	$payload = '';
	foreach ($post_fields as $name => $value) {
        	$payload .= '--' . $boundary;
	        $payload .= "\r\n";
	        $payload .= 'Content-Disposition: form-data; name="' . $name .'"' . "\r\n\r\n";
	        $payload .= $value;
	        $payload .= "\r\n";
	}

	$payload .= '--' . $boundary;
	$payload .= "\r\n";
	$payload .= 'Content-Disposition: form-data; name="file"; filename="' . basename($file) . '"' . "\r\n";
	$payload .= 'Content-Type: ' . $type . "\r\n";
	$payload .= "\r\n";
	$payload .= file_get_contents($file);
	$payload .= "\r\n";
	$payload .= '--' . $boundary;
	$payload .= 'Content-Disposition: form-data; name="submitHandler"' . "\r\n";
	$payload .= "\r\n";
	$payload .= "Upload\r\n";
	$payload .= '--' . $boundary . '--';

	// retrieve the time when the optimizer starts
//	$started = microtime(true);
	$response = wp_remote_post( $url, array(
		'timeout' => 90,
		'headers' => $headers,
		'sslverify' => false,
		'body' => $payload,
		) );
//	$elapsed = microtime(true) - $started;
//	$ewww_debug .= "processing image via cloud took $elapsed seconds<br>";
	if ( is_wp_error( $response ) ) {
		$error_message = $response->get_error_message();
		ewwwio_debug_message( "optimize failed: $error_message" );
		return array( $file, false, 'cloud optimize failed', 0 );
	} else {
		$tempfile = $file . ".tmp";
		file_put_contents( $tempfile, $response['body'] );
		$orig_size = filesize( $file );
		$newsize = $orig_size;
		$converted = false;
		$msg = '';
		if ( preg_match( '/exceeded/', $response['body'] ) ) {
			ewwwio_debug_message( 'License Exceeded' );
			$ewww_exceed = true;
			$msg = 'exceeded';
			unlink( $tempfile );
		} elseif ( ewww_image_optimizer_mimetype( $tempfile, 'i' ) == $type ) {
			$newsize = filesize( $tempfile );
			ewwwio_debug_message( "cloud results: $newsize (new) vs. $orig_size (original)" );
			rename( $tempfile, $file );
		} elseif ( ewww_image_optimizer_mimetype( $tempfile, 'i' ) == 'image/webp' ) {
			$newsize = filesize( $tempfile );
			ewwwio_debug_message( "cloud results: $newsize (new) vs. $orig_size (original)" );
			rename( $tempfile, $newfile );
		} elseif ( ewww_image_optimizer_mimetype( $tempfile, 'i' ) == $newtype ) {
			$converted = true;
			$newsize = filesize( $tempfile );
			ewwwio_debug_message( "cloud results: $newsize (new) vs. $orig_size (original)" );
			rename( $tempfile, $newfile );
			$file = $newfile;
		} else {
			unlink( $tempfile );
		}
		ewwwio_memory( __FUNCTION__ );
		return array( $file, $converted, $msg, $newsize );
	}
}

// check the database to see if we've done this image before
function ewww_image_optimizer_check_table( $file, $orig_size ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	$already_optimized = false;
	ewwwio_debug_message( "checking for $file with size: $orig_size" );
	$query = $wpdb->prepare( "SELECT path,results FROM $wpdb->ewwwio_images WHERE path = %s AND image_size = '$orig_size'", $file );
//	ewwwio_debug_message( "using this search: $query" );
	$already_optimized = $wpdb->get_results( $query, ARRAY_A );
	if ( ! empty( $already_optimized ) ) { // && empty( $_REQUEST['ewww_force'] ) ) { the core function already checks for this, and overrides it if a new image needs converting
		$prev_string = " - " . __( 'Previously Optimized', EWWW_IMAGE_OPTIMIZER_DOMAIN );
		foreach ( $already_optimized as $image ) {
			if ( $image['path'] != $file ) {
				ewwwio_debug_message( "{$image['path']} does not match $file, continuing our search" );
			} else {
				if ( preg_match( '/' . __( 'License exceeded', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '/', $image['results'] ) ) {
					return;
				}
				$already_optimized = preg_replace( "/$prev_string/", '', $image['results'] );
				$already_optimized = $already_optimized . $prev_string;
				ewwwio_debug_message( "already optimized: {$image['path']} - $already_optimized" );
				ewwwio_memory( __FUNCTION__ );
				return $already_optimized;
			}
		}
	}
}

// receives a path, results, optimized size, and an original size to insert into ewwwwio_images table
// if this is a $new image, copy the result stored in the database
function ewww_image_optimizer_update_table( $attachment, $opt_size, $orig_size, $preserve_results = false ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	$query = $wpdb->prepare( "SELECT id,orig_size,results,path,updates FROM $wpdb->ewwwio_images WHERE path = %s", $attachment );
	$optimized_query = $wpdb->get_results( $query, ARRAY_A );
	if ( ! empty( $optimized_query ) ) {
		foreach ( $optimized_query as $image ) {
			if ( $image['path'] != $attachment ) {
				ewwwio_debug_message( "{$image['path']} does not match $attachment, continuing our search" );
			} else {
				$already_optimized = $image;
			}
		}
	}
	ewwwio_debug_message( "savings: $opt_size (new) vs. $orig_size (orig)" );
	if ( ! empty( $already_optimized['results'] ) && $preserve_results && $opt_size === $orig_size) {
		$results_msg = $already_optimized['results'];
	} elseif ( $opt_size >= $orig_size ) {
		ewwwio_debug_message( "original and new file are same size (or something weird made the new one larger), no savings" );
		$results_msg = __( 'No savings', EWWW_IMAGE_OPTIMIZER_DOMAIN );
	} else {
		// calculate how much space was saved
		$savings = intval( $orig_size ) - intval( $opt_size );
		// convert it to human readable format
		$savings_str = size_format( $savings, 1 );
		// replace spaces and extra decimals with proper html entity encoding
		$savings_str = preg_replace( '/\.0 B /', ' B', $savings_str );
		$savings_str = str_replace( ' ', '&nbsp;', $savings_str );
		// determine the percentage savings
		$percent = 100 - ( 100 * ( $opt_size / $orig_size ) );
		// use the percentage and the savings size to output a nice message to the user
		$results_msg = sprintf( __( "Reduced by %01.1f%% (%s)", EWWW_IMAGE_OPTIMIZER_DOMAIN ),
			$percent,
			$savings_str
		);
		ewwwio_debug_message( "original and new file are different size: $results_msg" );
	}
	if ( empty( $already_optimized ) ) {
		ewwwio_debug_message( "creating new record, path: $attachment, size: $opt_size" );
		// store info on the current image for future reference
		$wpdb->insert( $wpdb->ewwwio_images, array(
				'path' => $attachment,
				'image_size' => $opt_size,
				'orig_size' => $orig_size,
				'results' => $results_msg,
				'updated' => date( 'Y-m-d H:i:s' ),
				'updates' => 1,
			) );
	} else {
		if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_debug' ) ) {
			$trace = ewwwio_debug_backtrace();
		} else {
			$trace = '';
		}
		ewwwio_debug_message( "updating existing record ({$already_optimized['id']}), path: $attachment, size: $opt_size" );
		// store info on the current image for future reference
		$wpdb->update( $wpdb->ewwwio_images,
			array(
				'image_size' => $opt_size,
				'results' => $results_msg,
				'updates' => $already_optimized['updates'] + 1,
				'trace' => $trace,
			),
			array(
				'id' => $already_optimized['id'],
			)
		);
	}
	ewwwio_memory( __FUNCTION__ );
	$wpdb->flush();
	ewwwio_memory( __FUNCTION__ );
	return $results_msg;
}

// called to process each image in the loop for images outside of media library
function ewww_image_optimizer_aux_images_loop( $attachment = null, $auto = false ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $ewww_defer;
	$ewww_defer = false;
	$output = array();
	// verify that an authorized user has started the optimizer
	$permissions = apply_filters( 'ewww_image_optimizer_bulk_permissions', '' );
	if ( ! $auto && ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-bulk' ) || ! current_user_can( $permissions ) ) ) {
		$output['error'] = esc_html__( 'Access token has expired, please reload the page.', EWWW_IMAGE_OPTIMIZER_DOMAIN );
		echo json_encode( $output );
		die();
	}
	// retrieve the time when the optimizer starts
	$started = microtime( true );
	if ( ini_get( 'max_execution_time' ) < 60 ) {
		set_time_limit( 0 );
	}
	// get the 'aux attachments' with a list of attachments remaining
	$attachments = get_option( 'ewww_image_optimizer_aux_attachments' );
	if ( empty( $attachment ) ) {
		$attachment = array_shift( $attachments );
	}
	// do the optimization for the current image
	$results = ewww_image_optimizer( $attachment );
	global $ewww_exceed;
	if ( ! empty ( $ewww_exceed ) ) {
		if ( ! $auto ) {
			$output['error'] = esc_html__( 'License Exceeded', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			echo json_encode( $output );
		}
		die();
	}
	// store the updated list of attachment IDs back in the 'bulk_attachments' option
	update_option( 'ewww_image_optimizer_aux_attachments', $attachments );
	if ( ! $auto ) {
		// output the path
		$output['results'] = sprintf( "<p>" . esc_html__( 'Optimized image:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . " <strong>%s</strong><br>", esc_html( $attachment ) );
		// tell the user what the results were for the original image
		$output['results'] .= sprintf( "%s<br>", $results[1] );
		// calculate how much time has elapsed since we started
		$elapsed = microtime( true ) - $started;
		// output how much time has elapsed since we started
		$output['results'] .= sprintf( esc_html__( 'Elapsed: %.3f seconds', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</p>", $elapsed );
		if ( get_site_option( 'ewww_image_optimizer_debug' ) ) {
			global $ewww_debug;
			$output['results'] .= '<div style="background-color:#ffff99;">' . $ewww_debug . '</div>';
		}
		if ( ! empty( $attachments ) ) {
			$next_file = array_shift( $attachments );
			$loading_image = plugins_url( '/images/wpspin.gif', __FILE__ );
			$output['next_file'] = "<p>" . esc_html__( 'Optimizing', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . " <b>$next_file</b>&nbsp;<img src='$loading_image' alt='loading'/></p>";
		}
		echo json_encode( $output );
		ewwwio_memory( __FUNCTION__ );
		die();
	}
	ewwwio_memory( __FUNCTION__ );
}

// processes metadata and looks for any webp version to insert in the meta
function ewww_image_optimizer_update_attachment_metadata( $meta, $ID ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_debug_message( "attachment id: $ID" );
	list( $file_path, $upload_path ) = ewww_image_optimizer_attachment_path( $meta, $ID );
	// don't do anything else if the attachment path can't be retrieved
	if ( ! is_file( $file_path ) ) {
		ewwwio_debug_message( "could not retrieve path" );
		return $meta;
	}
	ewwwio_debug_message( "retrieved file path: $file_path" );
	if ( is_file( $file_path . '.webp' ) ) {
		$meta['sizes']['webp-full'] = array(
			'file' => pathinfo( $file_path, PATHINFO_BASENAME ) . '.webp',
			'width' => 0,
			'height' => 0,
			'mime-type' => 'image/webp',
		);
		
	}
	// if the file was converted
	// resized versions, so we can continue
	if ( isset( $meta['sizes'] ) ) {
		ewwwio_debug_message( 'processing resizes for webp updates' );
		// meta sizes don't contain a path, so we use the foldername from the original to generate one
		$base_dir = trailingslashit( dirname( $file_path ) );
		// process each resized version
		$processed = array();
		foreach( $meta['sizes'] as $size => $data ) {
			$resize_path = $base_dir . $data['file'];
			// update the webp paths
			if ( is_file( $resize_path . '.webp' ) ) {
				$meta['sizes'][ 'webp-' . $size ] = array(
					'file' => $data['file'] . '.webp',
					'width' => 0,
					'height' => 0,
					'mime-type' => 'image/webp',
				);
			}
		}
	}
	ewwwio_memory( __FUNCTION__ );
	// send back the updated metadata
	return $meta;
}

function ewww_image_optimizer_hidpi_optimize( $orig_path ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$hidpi_suffix = apply_filters( 'ewww_image_optimizer_hidpi_suffix', '@2x' );
	$pathinfo = pathinfo( $orig_path );
	$hidpi_path = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . $hidpi_suffix . '.' . $pathinfo['extension'];
	ewww_image_optimizer( $hidpi_path );
}

function ewww_image_optimizer_remote_fetch( $id, $meta ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $as3cf;
	if ( ! function_exists( 'download_url' ) ) {
		require_once( ABSPATH . '/wp-admin/includes/file.php' );
	}
	if ( class_exists( 'Amazon_S3_And_CloudFront' ) ) {
		$full_url = get_attached_file( $id );
		$filename = get_attached_file( $id, true );
		ewwwio_debug_message( "amazon s3 fullsize url: $full_url" );
		ewwwio_debug_message( "unfiltered fullsize path: $filename" );
		$temp_file = download_url( $full_url );
		if ( ! is_wp_error( $temp_file ) ) {
			rename( $temp_file, $filename );
		}
		// resized versions, so we'll grab those too
		if (isset($meta['sizes']) ) {
			$disabled_sizes = ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_resizes_opt' );
			ewwwio_debug_message( 'retrieving resizes' );
			// meta sizes don't contain a path, so we calculate one
			$base_dir = trailingslashit( dirname( $filename) );
			// process each resized version
			$processed = array();
			foreach($meta['sizes'] as $size => $data) {
				ewwwio_debug_message( "processing size: $size" );
				if ( preg_match('/webp/', $size) ) {
					continue;
				}
				if ( ! empty( $disabled_sizes[$size] ) ) {
					continue;
				}
				// initialize $dup_size
				$dup_size = false;
				// check through all the sizes we've processed so far
				foreach($processed as $proc => $scan) {
					// if a previous resize had identical dimensions
					if ($scan['height'] == $data['height'] && $scan['width'] == $data['width']) {
						// found a duplicate resize
						$dup_size = true;
					}
				}
				// if this is a unique size
				if (!$dup_size) {
					$resize_path = $base_dir . $data['file'];
					$resize_url = $as3cf->get_attachment_url( $id, null, $size, $meta );
					ewwwio_debug_message( "fetching $resize_url to $resize_path" );
					$temp_file = download_url( $resize_url );
					if ( ! is_wp_error( $temp_file ) ) {
						rename( $temp_file, $resize_path );
					}
				}
				// store info on the sizes we've processed, so we can check the list for duplicate sizes
				$processed[$size]['width'] = $data['width'];
				$processed[$size]['height'] = $data['height'];
			}
		}
	}
	if ( class_exists( 'WindowsAzureStorageUtil' ) && get_option( 'azure_storage_use_for_default_upload' ) ) {
		$full_url = $meta['url'];
		$filename = $meta['file'];
		ewwwio_debug_message( "azure fullsize url: $full_url" );
		ewwwio_debug_message( "fullsize path: $filename" );
		$temp_file = download_url( $full_url );
		if ( ! is_wp_error( $temp_file ) ) {
			rename( $temp_file, $filename );
		}
		// resized versions, so we'll grab those too
		if (isset($meta['sizes']) ) {
			$disabled_sizes = ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_resizes_opt' );
			ewwwio_debug_message( 'retrieving resizes' );
			// meta sizes don't contain a path, so we calculate one
			$base_dir = trailingslashit( dirname( $filename) );
			$base_url = trailingslashit( dirname( $full_url ) );
			// process each resized version
			$processed = array();
			foreach($meta['sizes'] as $size => $data) {
				ewwwio_debug_message( "processing size: $size" );
				if ( preg_match('/webp/', $size) ) {
					continue;
				}
				if ( ! empty( $disabled_sizes[$size] ) ) {
					continue;
				}
				// initialize $dup_size
				$dup_size = false;
				// check through all the sizes we've processed so far
				foreach($processed as $proc => $scan) {
					// if a previous resize had identical dimensions
					if ($scan['height'] == $data['height'] && $scan['width'] == $data['width']) {
						// found a duplicate resize
						$dup_size = true;
					}
				}
				// if this is a unique size
				if (!$dup_size) {
					$resize_path = $base_dir . $data['file'];
					$resize_url = $base_url . $data['file'];
					ewwwio_debug_message( "fetching $resize_url to $resize_path" );
					$temp_file = download_url( $resize_url );
					if ( ! is_wp_error( $temp_file ) ) {
						rename( $temp_file, $resize_path );
					}
				}
				// store info on the sizes we've processed, so we can check the list for duplicate sizes
				$processed[$size]['width'] = $data['width'];
				$processed[$size]['height'] = $data['height'];
			}
		}
	}
	if ( ! empty( $filename ) && file_exists( $filename ) ) {
		return $filename;
	} else {
		return false;
	}
}

function ewww_image_optimizer_check_table_as3cf( $meta, $ID, $s3_path ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$local_path = get_attached_file( $ID, true );
	ewwwio_debug_message( "unfiltered local path: $local_path" );
	if ( $local_path !== $s3_path ) {
		ewww_image_optimizer_update_table_as3cf( $local_path, $s3_path );
	}
	if ( isset( $meta['sizes'] ) ) {
		ewwwio_debug_message( 'updating s3 resizes' );
		// meta sizes don't contain a path, so we calculate one
		$local_dir = trailingslashit( dirname( $local_path ) );
		$s3_dir = trailingslashit( dirname( $s3_path ) );
		// process each resized version
		$processed = array();
		foreach ( $meta['sizes'] as $size => $data ) {
			if ( strpos( $size, 'webp') === 0 ) {
				continue;
			}
			// check through all the sizes we've processed so far
			foreach ( $processed as $proc => $scan ) {
				// if a previous resize had identical dimensions
				if ( $scan['height'] === $data['height'] && $scan['width'] === $data['width'] ) {
					// found a duplicate resize
					continue;
				}
			}
			// if this is a unique size
			$local_resize_path = $local_dir . $data['file'];
			$s3_resize_path = $s3_dir . $data['file'];
			if ( $local_resize_path !== $s3_resize_path ) {
				ewww_image_optimizer_update_table_as3cf( $local_resize_path, $s3_resize_path );
			}
			// store info on the sizes we've processed, so we can check the list for duplicate sizes
			$processed[ $size ]['width'] = $data['width'];
			$processed[ $size ]['height'] = $data['height'];
		}
	}
	global $wpdb;
	$wpdb->flush();
}

function ewww_image_optimizer_update_table_as3cf( $local_path, $s3_path ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	// first we need to see if anything matches the old local path
	$local_query = $wpdb->prepare( "SELECT id,path,orig_size,results FROM $wpdb->ewwwio_images WHERE path = %s", $local_path );
	$local_images = $wpdb->get_results( $local_query, ARRAY_A );
	ewwwio_debug_message( "looking for $local_path" );
	if ( ! empty( $local_images ) ) {
		foreach ( $local_images as $local_image ) {
			if ( $local_image['path'] !== $local_path ) {
				ewwwio_debug_message( "{$local_image['path']} does not match $local_path, continuing our search" );
			} else {
				ewwwio_debug_message( "found $local_path in db" );
				// when we find a match by the local path, we need to find out if there are already records for the s3 path
				$s3_query = $wpdb->prepare( "SELECT id,path,orig_size FROM $wpdb->ewwwio_images WHERE path = %s", $s3_path );
				$s3_images = $wpdb->get_results( $s3_query, ARRAY_A );
				ewwwio_debug_message( "looking for $s3_path" );
				foreach ( $s3_images as $s3_image ) {
					if ( $s3_image['path'] === $s3_path ) {
						$found_s3_image = $s3_image;
						break;
					}
				}
				// if we found records for both local and s3 paths, we delete the local record, but store the original size in the s3 record
				if ( ! empty( $found_s3_image ) && is_array( $found_s3_image ) ) {
					ewwwio_debug_message( "found $s3_path in db" );
					$wpdb->delete( $wpdb->ewwwio_images,
						array(
							'id' => $local_image['id'],
						),
						array(
							'%d'
						)
					);
					if ( $local_image['orig_size'] > $found_s3_image['orig_size'] ) {
						$wpdb->update( $wpdb->ewwwio_images,
							array(
								'orig_size' => $local_image['orig_size'],
								'results' => $local_image['results'],
							),
							array(
								'id' => $found_s3_image['id'],
							)
						);
					}
				// if we just found a local path and no s3 match, then we just update the path in the table to the s3 path
				} else {
					ewwwio_debug_message( "just updating local to s3" );
					$wpdb->update( $wpdb->ewwwio_images,
						array(
							'path' => $s3_path,
						),
						array(
							'id' => $local_image['id'],
						)
					);
				}
				break;
			}
		}
	}
}	

/**
 * Read the image paths from an attachment's meta data and process each image
 * with ewww_image_optimizer().
 *
 * This method also adds a `ewww_image_optimizer` meta key for use in the media library 
 * and may add a 'converted' and 'orig_file' key if conversion is enabled.
 *
 * Called after `wp_generate_attachment_metadata` is completed.
 */
function ewww_image_optimizer_resize_from_meta_data( $meta, $ID = null, $log = true ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( ! is_array( $meta ) ) {
		ewwwio_debug_message( 'attachment meta is not a usable array' );
		return $meta;
	}
	global $wpdb;
	global $ewww_defer;
	$gallery_type = 1;
	ewwwio_debug_message( "attachment id: $ID" );
	if ( ! metadata_exists( 'post', $ID, '_wp_attachment_metadata' ) ) {
		ewwwio_debug_message( 'this is a newly uploaded image with no metadata yet' );
		$new_image = true;
	} else {
		ewwwio_debug_message( 'this image already has metadata, so it is not new' );
		$new_image = false;
	}
	list( $file_path, $upload_path ) = ewww_image_optimizer_attachment_path( $meta, $ID );
	// if the attachment has been uploaded via the image store plugin
	if ( 'ims_image' == get_post_type( $ID ) ) {
		$gallery_type = 6;
	}
	// if the local file is missing and we have valid metadata, see if we can fetch via CDN
	if ( ! is_file( $file_path ) ) {
		$file_path = ewww_image_optimizer_remote_fetch( $ID, $meta );
		if ( ! $file_path ) {
			ewwwio_debug_message( 'could not retrieve path' );
			$meta['ewww_image_optimizer'] = __( 'Could not find image', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			return $meta;
		}
	}
	if ( ! $new_image && class_exists( 'Amazon_S3_And_CloudFront' ) && strpos( $file_path, 's3:' ) === 0 ) {
		ewww_image_optimizer_check_table_as3cf( $meta, $ID, $file_path );
	}
	ewwwio_debug_message( "retrieved file path: $file_path" );
	// see if this is a new image and Imsanity resized it (which means it could be already optimized)
	if ( ! empty( $new_image ) && function_exists( 'imsanity_get_max_width_height' ) ) {
		list( $maxW, $maxH ) = imsanity_get_max_width_height( IMSANITY_SOURCE_LIBRARY );
		list( $oldW, $oldH ) = getimagesize( $file_path );
		list( $newW, $newH ) = wp_constrain_dimensions( $oldW, $oldH, $maxW, $maxH );
		$path_parts = pathinfo( $file_path );
		$imsanity_path = trailingslashit( $path_parts['dirname'] ) . $path_parts['filename'] . '-' . $newW . 'x' . $newH . '.' . $path_parts['extension'];
		ewwwio_debug_message( "imsanity path: $imsanity_path" );
		$image_size = ewww_image_optimizer_filesize( $file_path );
		$query = $wpdb->prepare( "SELECT id,path FROM $wpdb->ewwwio_images WHERE path = %s AND image_size = '$image_size'", $imsanity_path );
		$optimized_query = $wpdb->get_results( $query, ARRAY_A );
		if ( ! empty( $optimized_query ) ) {
			foreach ( $optimized_query as $image ) {
				if ( $image['path'] != $imsanity_path ) {
					ewwwio_debug_message( "{$image['path']} does not match $imsanity_path, continuing our search" );
				} else {
					$already_optimized = $image;
				}
			}
		}
		if ( ! empty ( $already_optimized ) ) {
			ewwwio_debug_message( "updating existing record, path: $file_path, size: " . $image_size );
			// store info on the current image for future reference
			$wpdb->update( $wpdb->ewwwio_images,
				array(
					'path' => $file_path,
				),
				array(
					'id' => $already_optimized['id'],
				));
		}
	}
	if ( $ewww_defer && ewww_image_optimizer_get_option( 'ewww_image_optimizer_defer' ) ) {
		ewww_image_optimizer_add_deferred_attachment( "media,$ID" );
		return $meta;
	}
	list( $file, $msg, $conv, $original ) = ewww_image_optimizer( $file_path, $gallery_type, false, $new_image, true );
	// update the optimization results in the metadata
	$meta['ewww_image_optimizer'] = $msg;
	if ( $file === false ) {
		return $meta;
	}
	ewww_image_optimizer_hidpi_optimize( $file_path );
	$meta['file'] = str_replace( $upload_path, '', $file );
	// if the file was converted
	if ( $conv ) {
		// update the filename in the metadata
		$new_file = substr( $meta['file'], 0, -3 );
		// change extension
		$new_ext = substr($file, -3);
		$meta['file'] = $new_file . $new_ext;
		ewwwio_debug_message( 'image was converted' );
		// if we don't already have the update attachment filter
		if ( FALSE === has_filter( 'wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment' ) )
			// add the update attachment filter
			add_filter( 'wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment', 10, 2 );
		// store the conversion status in the metadata
		$meta['converted'] = 1;
		// store the old filename in the database
		$meta['orig_file'] = $original;
	} else {
		remove_filter( 'wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment', 10 );
	}
	// resized versions, so we can continue
	if ( isset( $meta['sizes'] ) ) {
		$disabled_sizes = ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_resizes_opt' );
//		ewwwio_debug_message( "disabled sizes: " . print_r( $disabled_sizes, true ) );
		ewwwio_debug_message( 'processing resizes' );
		// meta sizes don't contain a path, so we calculate one
		if ($gallery_type === 6) {
			$base_ims_dir = trailingslashit( dirname( $file_path ) ) . '_resized/';
		}
		$base_dir = trailingslashit( dirname( $file_path ) );
		// process each resized version
		$processed = array();
		foreach ( $meta['sizes'] as $size => $data ) {
			ewwwio_debug_message( "processing size: $size" );
			if ( strpos( $size, 'webp') === 0 ) {
				continue;
			}
			if ( ! empty( $disabled_sizes[ $size ] ) ) {
				continue;
			}
			if ( $gallery_type === 6 ) {
				$base_dir = dirname( $file_path ) . '/';
				$image_path = $base_dir . $data['file'];
				$ims_path = $base_ims_dir . $data['file'];
				if ( file_exists( $ims_path ) ) {
					ewwwio_debug_message( 'ims resize already exists, wahoo' );
					ewwwio_debug_message( "ims path: $ims_path" );
					$image_size = ewww_image_optimizer_filesize( $ims_path );
					$query = $wpdb->prepare( "SELECT id,path FROM $wpdb->ewwwio_images WHERE path = %s AND image_size = '$image_size'", $image_path );
					$optimized_query = $wpdb->get_results( $query, ARRAY_A );
					if ( ! empty( $optimized_query ) ) {
						foreach ( $optimized_query as $image ) {
							if ( $image['path'] != $image_path ) {
								ewwwio_debug_message( "{$image['path']} does not match $image_path, continuing our search" );
							} else {
								$already_optimized = $image;
							}
						}
					}
					if ( ! empty( $already_optimized ) ) {
						ewwwio_debug_message( "updating existing record, path: $ims_path, size: " . $image_size );
						// store info on the current image for future reference
						$wpdb->update( $wpdb->ewwwio_images,
							array(
								'path' => $ims_path,
							),
							array(
								'id' => $already_optimized['id'],
							));
					}
					$base_dir = $base_ims_dir;
				}
			}
			// initialize $dup_size
			$dup_size = false;
			// check through all the sizes we've processed so far
			foreach ( $processed as $proc => $scan ) {
				// if a previous resize had identical dimensions
				if ( $scan['height'] == $data['height'] && $scan['width'] == $data['width'] ) {
					// found a duplicate resize
					$dup_size = true;
					// point this resize at the same image as the previous one
					$meta['sizes'][ $size ]['file'] = $meta['sizes'][ $proc ]['file'];
					// and tell the user we didn't do any further optimization
					$meta['sizes'][ $size ]['ewww_image_optimizer'] = __( 'No savings', EWWW_IMAGE_OPTIMIZER_DOMAIN );
				}
			}
			// if this is a unique size
			if ( ! $dup_size ) {
				$resize_path = $base_dir . $data['file'];
				// run the optimization and store the results
				list( $optimized_file, $results, $resize_conv, $original ) = ewww_image_optimizer( $resize_path, $gallery_type, $conv, $new_image );
				// if the resize was converted, store the result and the original filename in the metadata for later recovery
				if ( $resize_conv ) {
					// if we don't already have the update attachment filter
					if ( FALSE === has_filter( 'wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment' ) ) {
						// add the update attachment filter
						add_filter( 'wp_update_attachment_metadata', 'ewww_image_optimizer_update_attachment', 10, 2 );
					}
					$meta['sizes'][ $size ]['converted'] = 1;
					$meta['sizes'][ $size ]['orig_file'] = str_replace($base_dir, '', $original);
					ewwwio_debug_message( "original filename: $original" );
					$meta['sizes'][ $size ]['real_orig_file'] = str_replace($base_dir, '', $resize_path);
					ewwwio_debug_message( "resize path: $resize_path" );
				}
				if ($optimized_file !== false) {
					// update the filename
					$meta['sizes'][ $size ]['file'] = str_replace($base_dir, '', $optimized_file);
				}
				// update the optimization results
				$meta['sizes'][ $size ]['ewww_image_optimizer'] = $results;
				// optimize retina images, if they exist
				if ( function_exists( 'wr2x_get_retina' ) && $retina_path = wr2x_get_retina( $resize_path ) ) {
					ewww_image_optimizer( $retina_path );
				} else {
					ewww_image_optimizer_hidpi_optimize( $resize_path );
				}
			}
			// store info on the sizes we've processed, so we can check the list for duplicate sizes
			$processed[ $size ]['width'] = $data['width'];
			$processed[ $size ]['height'] = $data['height'];
		}
	}

	// process size from a custom theme
	if ( isset( $meta['image_meta']['resized_images'] ) ) {
		$imagemeta_resize_pathinfo = pathinfo( $file_path );
		$imagemeta_resize_path = '';
		foreach ( $meta['image_meta']['resized_images'] as $imagemeta_resize ) {
			$imagemeta_resize_path = $imagemeta_resize_pathinfo['dirname'] . '/' . $imagemeta_resize_pathinfo['filename'] . '-' . $imagemeta_resize . '.' . $imagemeta_resize_pathinfo['extension'];
			ewww_image_optimizer( $imagemeta_resize_path );
		}
		
	}

	// and another custom theme
	if ( isset( $meta['custom_sizes'] ) ) {
		$custom_sizes_pathinfo = pathinfo( $file_path );
		$custom_size_path = '';
		foreach ( $meta['custom_sizes'] as $custom_size ) {
			$custom_size_path = $custom_sizes_pathinfo['dirname'] . '/' . $custom_size['file'];
			ewww_image_optimizer( $custom_size_path );
		}
	}
	
	if ( ! empty( $new_image) ) {
		$meta = ewww_image_optimizer_update_attachment_metadata($meta, $ID);
	}
	if ( ! preg_match( '/' . __( 'Previously Optimized', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '/', $meta['ewww_image_optimizer'] ) && class_exists( 'Amazon_S3_And_CloudFront' ) ) {
 		global $as3cf;
		if ( method_exists( $as3cf, 'wp_update_attachment_metadata' ) ) {
			$as3cf->wp_update_attachment_metadata( $meta, $ID );
		} elseif ( method_exists( $as3cf, 'wp_generate_attachment_metadata' ) ) {
			$as3cf->wp_generate_attachment_metadata( $meta, $ID );
		}
		ewwwio_debug_message( 'uploading to Amazon S3' );
	}
	if ( ! preg_match( '/' . __( 'Previously Optimized', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '/', $meta['ewww_image_optimizer'] ) && class_exists( 'DreamSpeed_Services' ) ) {
		global $dreamspeed;
		$dreamspeed->wp_generate_attachment_metadata( $meta, $ID );
		ewwwio_debug_message( 'uploading to Dreamspeed' );
	}
	if ( class_exists( 'Cloudinary' ) && Cloudinary::config_get( "api_secret" ) && ewww_image_optimizer_get_option( 'ewww_image_optimizer_enable_cloudinary' ) && ! empty( $new_image ) ) {
		try {
			$result = CloudinaryUploader::upload( $file, array( 'use_filename' => true ) );
		} catch( Exception $e ) {
			$error = $e->getMessage();
		}
		if ( ! empty( $error ) ) {
			ewwwio_debug_message( "Cloudinary error: $error" );
		} else {
			ewwwio_debug_message( 'successfully uploaded to Cloudinary' );
			// register the attachment in the database as a cloudinary attachment
			$old_url = wp_get_attachment_url($ID);
			wp_update_post(array('ID' => $ID,
				'guid' => $result['url']));
			update_attached_file($ID, $result['url']);
			$meta['cloudinary'] = TRUE;
			$errors = array();
			// update the image location for the attachment
			CloudinaryPlugin::update_image_src_all( $ID, $result, $old_url, $result['url'], TRUE, $errors );
			if ( count( $errors ) > 0 ) {
				ewwwio_debug_message( "Cannot migrate the following posts:" );
				foreach( $errors as $error ) {
					ewwwio_debug_message( $error );
				}
			}
		}
	}
	if ( $log ) {
		ewww_image_optimizer_debug_log();
	}
	ewwwio_memory( __FUNCTION__ );
	// send back the updated metadata
	return $meta;
}

/**
 * Update the attachment's meta data after being converted 
 */
function ewww_image_optimizer_update_attachment( $meta, $ID ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	// update the file location in the post metadata based on the new path stored in the attachment metadata
	update_attached_file( $ID, $meta['file'] );
	$guid = wp_get_attachment_url( $ID );
	if ( empty( $meta['real_orig_file'] ) ) {
		$old_guid = dirname( $guid ) . "/" . basename( $meta['orig_file'] );
	} else {
		$old_guid = dirname( $guid ) . "/" . basename( $meta['real_orig_file'] );
		unset( $meta['real_orig_file'] );
	}
	// construct the new guid based on the filename from the attachment metadata
	ewwwio_debug_message( "old guid: $old_guid" );
	ewwwio_debug_message( "new guid: $guid" );
	if ( substr( $old_guid, -1 ) == '/' || substr( $guid, -1 ) == '/' ) {
		ewwwio_debug_message( 'could not obtain full url for current and previous image, bailing' );
		return $meta;
	}
	// retrieve any posts that link the image
	$esql = $wpdb->prepare( "SELECT ID, post_content FROM $wpdb->posts WHERE post_content LIKE '%%%s%%'", $old_guid );
	ewwwio_debug_message( "using query: $esql" );
	// while there are posts to process
	$rows = $wpdb->get_results( $esql, ARRAY_A );
	foreach ( $rows as $row ) {
		// replace all occurences of the old guid with the new guid
		$post_content = str_replace( $old_guid, $guid, $row['post_content'] );
		ewwwio_debug_message( "replacing $old_guid with $guid in post " . $row['ID'] );
		// send the updated content back to the database
		$wpdb->update(
			$wpdb->posts,
			array( 'post_content' => $post_content ),
			array( 'ID' => $row['ID'] )
		);
	}
	if ( isset( $meta['sizes'] ) ) {
		// for each resized version
		foreach( $meta['sizes'] as $size => $data ) {
			// if the resize was converted
			if ( isset( $data['converted'] ) ) {
				// generate the url for the old image
				if ( empty( $data['real_orig_file'] ) ) {
					$old_sguid = dirname( $old_guid ) . "/" . basename( $data['orig_file'] );
				} else {
					$old_sguid = dirname( $old_guid) . "/" . basename($data['real_orig_file'] );
					unset ($meta['sizes'][$size]['real_orig_file'] );
				}
				ewwwio_debug_message( "processing: $size" );
				ewwwio_debug_message( "old sguid: $old_sguid" );
				// generate the url for the new image
				$sguid = dirname( $old_guid ) . "/" . basename( $data['file'] );
				ewwwio_debug_message( "new sguid: $sguid" );
				if ( substr( $old_sguid, -1 ) == '/' || substr( $sguid, -1 ) == '/' ) {
					ewwwio_debug_message( 'could not obtain full url for current and previous resized image, bailing' );
					continue;
				}
				// retrieve any posts that link the resize
				$ersql = $wpdb->prepare( "SELECT ID, post_content FROM $wpdb->posts WHERE post_content LIKE '%%%s%%'", $old_sguid );
				//ewwwio_debug_message( "using query: $ersql" );
				$rows = $wpdb->get_results( $ersql, ARRAY_A );
				// while there are posts to process
				foreach ( $rows as $row ) {
					// replace all occurences of the old guid with the new guid
					$post_content = str_replace( $old_sguid, $sguid, $row['post_content'] );
					ewwwio_debug_message( "replacing $old_sguid with $sguid in post " . $row['ID'] );
					// send the updated content back to the database
					$wpdb->update(
						$wpdb->posts,
						array( 'post_content' => $post_content ),
						array( 'ID' => $row['ID'] )
					);
				}
			}
		}
	}
	// if the new image is a JPG
	if ( preg_match( '/.jpg$/i', basename( $meta['file'] ) ) ) {
		// set the mimetype to JPG
		$mime = 'image/jpg';
	}
	// if the new image is a PNG
	if ( preg_match( '/.png$/i', basename( $meta['file'] ) ) ) {
		// set the mimetype to PNG
		$mime = 'image/png';
	}
	if ( preg_match( '/.gif$/i', basename( $meta['file'] ) ) ) {
		// set the mimetype to GIF
		$mime = 'image/gif';
	}
	// update the attachment post with the new mimetype and id
	wp_update_post( array(
		'ID' => $ID,
		'post_mime_type' => $mime 
		)
	);
	ewww_image_optimizer_debug_log();
	ewwwio_memory( __FUNCTION__ );
	return $meta;
}

// retrieves path of an attachment via the $id and the $meta
// returns a $file_path and $upload_path
function ewww_image_optimizer_attachment_path( $meta, $ID ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// retrieve the location of the wordpress upload folder
	$upload_dir = wp_upload_dir();
	// retrieve the path of the upload folder
	$upload_path = trailingslashit( $upload_dir['basedir'] );
	// get the filepath
	$file_path = get_attached_file( $ID );
	ewwwio_debug_message( "WP (filtered) thinks the file is at: $file_path" );
	if ( is_file( $file_path ) ) {
		return array( $file_path, $upload_path );
	}
	// first time did not work, let us retrieve the unfiltered path instead
	$file_path = get_attached_file( $ID, true );
	ewwwio_debug_message( "WP (unfiltered) thinks the file is at: $file_path" );
	if ( is_file( $file_path ) ) {
		return array( $file_path, $upload_path );
	}
	if ( 'ims_image' == get_post_type( $ID ) && !empty( $meta['file'] ) ) {
		$ims_options = ewww_image_optimizer_get_option( 'ims_front_options' );
		$ims_path = $ims_options['galleriespath'];
		if ( is_dir( $file_path ) ) {
			$upload_path = $file_path;
			$file_path = $meta['file'];
			// generate the absolute path
			$file_path =  $upload_path . $file_path;
		} elseif ( is_file( $meta['file'] ) ) {
			$file_path = $meta['file'];
			$upload_path = '';
		} else {
			$upload_path = WP_CONTENT_DIR;
			if ( strpos( $meta['file'], $ims_path ) === false ) {
				$upload_path = trailingslashit( WP_CONTENT_DIR );
			}
			$file_path = $upload_path . $meta['file'];
		}
		return array( $file_path, $upload_path );
	}
	if ( ! empty( $meta['file'] ) ) {
		$file_path = $meta['file'];
		ewwwio_debug_message( "looking for file at $file_path" );
		if ( is_file( $file_path ) ) {
			return array( $file_path, $upload_path );
		}
		$file_path = trailingslashit( $upload_path ) . $file_path;
		ewwwio_debug_message( "that did not work, try it with the upload_dir: $file_path" );
		if ( is_file( $file_path ) ) {
			return array( $file_path, $upload_path );
		}
		$upload_path = trailingslashit( WP_CONTENT_DIR ) . 'uploads/';
		$file_path = $upload_path . $meta['file'];
		ewwwio_debug_message( "one last shot, using the wp-content/ constant: $file_path" );
		if ( is_file( $file_path ) ) {
			return array( $file_path, $upload_path );
		}
	}
	ewwwio_memory( __FUNCTION__ );
	return array( '', $upload_path );
}

// takes a human-readable size, and generates an approximate byte-size
function ewww_image_optimizer_size_unformat( $formatted ) {
	$size_parts = explode( '&nbsp;', $formatted );
	switch ( $size_parts[1] ) {
		case 'B':
			return intval( $size_parts[0] );
		case 'kB':
			return intval( $size_parts[0] * 1024 );
		case 'MB':
			return intval( $size_parts[0] * 1048576 );
		case 'GB':
			return intval( $size_parts[0] * 1073741824 );
		case 'TB':
			return intval( $size_parts[0] * 1099511627776 );
		default:
			return 0;
	}
}

// generate a unique filename for a converted image
function ewww_image_optimizer_unique_filename( $file, $fileext ) {
	// strip the file extension
	$filename = preg_replace( '/\.\w+$/', '', $file );
	// set the increment to 1 (we always rename converted files with an increment)
	$filenum = 1;
	// while a file exists with the current increment
	while ( file_exists( $filename . '-' . $filenum . $fileext ) ) {
		// increment the increment...
		$filenum++;
	}
	// all done, let's reconstruct the filename
	ewwwio_memory( __FUNCTION__ );
	return array( $filename . '-' . $filenum . $fileext, $filenum );
}

/**
 * Check the submitted PNG to see if it has transparency
 */
function ewww_image_optimizer_png_alpha( $filename ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// determine what color type is stored in the file
	$color_type = ord( @file_get_contents( $filename, NULL, NULL, 25, 1 ) );
	ewwwio_debug_message( "color type: $color_type" );
	// if it is set to RGB alpha or Grayscale alpha
	if ( $color_type == 4 || $color_type == 6 ) {
		ewwwio_debug_message( 'transparency found' );
		return true;
	} elseif ( $color_type == 3 && ewww_image_optimizer_gd_support() ) {
		$image = imagecreatefrompng( $filename );
		if ( imagecolortransparent( $image ) >= 0 ) {
			ewwwio_debug_message( 'transparency found' );
			return true;
		}
		list( $width, $height ) = getimagesize( $filename );
		ewwwio_debug_message( "image dimensions: $width x $height" );
		ewwwio_debug_message( 'preparing to scan image' );
		for ( $y = 0; $y < $height; $y++ ) {
			for ( $x = 0; $x < $width; $x++ ) {
				$color = imagecolorat( $image, $x, $y );
				$rgb = imagecolorsforindex( $image, $color );
				if ( $rgb['alpha'] > 0 ) {
					ewwwio_debug_message( 'transparency found' );
					return true;
				}
			}
		}
	}
	ewwwio_debug_message( 'no transparency' );
	ewwwio_memory( __FUNCTION__ );
	return false;
}

/**
 * Check the submitted GIF to see if it is animated
 */
function ewww_image_optimizer_is_animated( $filename ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// if we can't open the file in read-only buffered mode
	if(!($fh = @fopen($filename, 'rb'))) {
		return false;
	}
	// initialize $count
	$count = 0;
   
	// We read through the file til we reach the end of the file, or we've found
	// at least 2 frame headers
	while(!feof($fh) && $count < 2) {
		$chunk = fread($fh, 1024 * 100); //read 100kb at a time
		$count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk, $matches);
	}
	fclose($fh);
	ewwwio_debug_message( "scanned GIF and found $count frames" );
	// return TRUE if there was more than one frame, or FALSE if there was only one
	ewwwio_memory( __FUNCTION__ );
	return $count > 1;
}

/**
 * Print column header for optimizer results in the media library using
 * the `manage_media_columns` hook.
 */
function ewww_image_optimizer_columns( $defaults ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$defaults['ewww-image-optimizer'] = esc_html__( 'Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN );
	ewwwio_memory( __FUNCTION__ );
	return $defaults;
}

/**
 * Print column data for optimizer results in the media library using
 * the `manage_media_custom_column` hook.
 */
function ewww_image_optimizer_custom_column( $column_name, $id ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// once we get to the EWWW IO custom column
	if ( $column_name == 'ewww-image-optimizer' ) {
		// retrieve the metadata
		$meta = wp_get_attachment_metadata( $id );
		if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_debug' ) ) {
			$print_meta = print_r( $meta, TRUE );
			$print_meta = preg_replace( array('/ /', '/\n+/' ), array( '&nbsp;', '<br />' ), $print_meta );
			echo '<div style="background-color:#ffff99;font-size: 10px;padding: 10px;margin:-10px -10px 10px;line-height: 1.1em">' . $print_meta . '</div>';
		}
		$ewww_cdn = false;
		if( ! empty( $meta['cloudinary'] ) ) {
			esc_html_e( 'Cloudinary image', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			return;
		}
		if ( class_exists( 'WindowsAzureStorageUtil' ) && ! empty( $meta['url'] ) ) {
			esc_html_e( 'Azure Storage image', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			$ewww_cdn = true;
		}
		if ( class_exists( 'Amazon_S3_And_CloudFront' ) && preg_match( '/^(http|s3):/', get_attached_file( $id ) ) ) {
			esc_html_e( 'Amazon S3 image', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			$ewww_cdn = true;
		}
		list( $file_path, $upload_path ) = ewww_image_optimizer_attachment_path( $meta, $id );
		// if the file does not exist
		if ( empty( $file_path ) && ! $ewww_cdn ) {
			esc_html_e( 'Could not retrieve file path.', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			return;
		}
		$msg = '';
		$convert_desc = '';
		$convert_link = '';
		if ( $ewww_cdn ) {
			$type = get_post_mime_type( $id );
		} else {
			// retrieve the mimetype of the attachment
			$type = ewww_image_optimizer_mimetype( $file_path, 'i' );
			// get a human readable filesize
			$file_size = size_format( filesize( $file_path ), 2 );
			$file_size = preg_replace( '/\.00 B /', ' B', $file_size );
		}
		// run the appropriate code based on the mimetype
		switch( $type ) {
			case 'image/jpeg':
				// if jpegtran is missing, tell them that
				if( ! EWWW_IMAGE_OPTIMIZER_JPEGTRAN && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_jpg' ) ) {
					$valid = false;
					$msg = '<br>' . wp_kses( sprintf( __( '%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>jpegtran</em>' ), array( 'em' => array() ) );
				} else {
					$convert_link = esc_html__('JPG to PNG', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$class_type = 'jpg';
					$convert_desc = esc_attr__( 'WARNING: Removes metadata. Requires GD or ImageMagick. PNG is generally much better than JPG for logos and other images with a limited range of colors.', EWWW_IMAGE_OPTIMIZER_DOMAIN );
				}
				break; 
			case 'image/png':
				// if pngout and optipng are missing, tell the user
				if( ! EWWW_IMAGE_OPTIMIZER_PNGOUT && ! EWWW_IMAGE_OPTIMIZER_OPTIPNG && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_png' ) ) {
					$valid = false;
					$msg = '<br>' . wp_kses( sprintf( __( '%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN ), '<em>optipng/pngout</em>' ), array( 'em' => array() ) );
				} else {
					$convert_link = esc_html__('PNG to JPG', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$class_type = 'png';
					$convert_desc = esc_attr__('WARNING: This is not a lossless conversion and requires GD or ImageMagick. JPG is much better than PNG for photographic use because it compresses the image and discards data. Transparent images will only be converted if a background color has been set.', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				}
				break;
			case 'image/gif':
				// if gifsicle is missing, tell the user
				if( ! EWWW_IMAGE_OPTIMIZER_GIFSICLE && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_gif' ) ) {
					$valid = false;
					$msg = '<br>' . wp_kses( sprintf( __( '%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN ), '<em>gifsicle</em>' ), array( 'em' => array() ) );
				} else {
					$convert_link = esc_html__('GIF to PNG', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$class_type = 'gif';
					$convert_desc = esc_attr__('PNG is generally better than GIF, but does not support animation. Animated images will not be converted.', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				}
				break;
			default:
				// not a supported mimetype
				esc_html_e( 'Unsupported file type', EWWW_IMAGE_OPTIMIZER_DOMAIN );
				return;
		}
		$ewww_manual_nonce = wp_create_nonce( "ewww-manual-$id" );
		if ( $ewww_cdn ) {
			// if the optimizer metadata exists
			if ( ! empty( $meta['ewww_image_optimizer'] ) ) {
				// output the optimizer results
				echo "<br>" . esc_html( $meta['ewww_image_optimizer'] );
			}
			if ( current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) ) {
				// and give the user the option to optimize the image right now
				printf( "<br><a href=\"admin.php?action=ewww_image_optimizer_manual_optimize&amp;ewww_manual_nonce=$ewww_manual_nonce&amp;ewww_attachment_ID=%d\">%s</a>", $id, esc_html__( 'Optimize now!', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
			}
			return;
		}
		// if the optimizer metadata exists
		if ( ! empty( $meta['ewww_image_optimizer'] ) ) {
			// output the optimizer results
			echo esc_html( $meta['ewww_image_optimizer'] );
			// output the filesize
			echo "<br>" . sprintf( esc_html__( 'Image Size: %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $file_size );
			if ( empty( $msg ) && current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) ) {
				// output a link to re-optimize manually
				printf("<br><a href=\"admin.php?action=ewww_image_optimizer_manual_optimize&amp;ewww_manual_nonce=$ewww_manual_nonce&amp;ewww_force=1&amp;ewww_attachment_ID=%d\">%s</a>",
					$id,
					esc_html__( 'Re-optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_convert_links' ) && 'ims_image' != get_post_type( $id ) ) {
					echo " | <a class='ewww-convert' title='$convert_desc' href='admin.php?action=ewww_image_optimizer_manual_optimize&amp;ewww_manual_nonce=$ewww_manual_nonce&amp;ewww_attachment_ID=$id&amp;ewww_convert=1&amp;ewww_force=1'>$convert_link</a>";
				}
			} else {
				echo $msg;
			}
			$restorable = false;
			if ( ! empty( $meta['converted'] ) ) {
				if ( ! empty( $meta['orig_file'] ) && file_exists( $meta['orig_file'] ) ) {
					$restorable = true;
				}
			}
			if ( isset( $meta['sizes'] ) ) {
				// meta sizes don't contain a path, so we calculate one
				$base_dir = trailingslashit( dirname( $file_path ) );
				foreach( $meta['sizes'] as $size => $data ) {
					if ( ! empty( $data['converted'] ) ) {
						if ( ! empty( $data['orig_file'] ) && file_exists( $base_dir . $data['orig_file'] ) ) {
							$restorable = true;
						}
					}		
				}
			}
			if ( $restorable && current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) ) {
				printf( "<br><a href=\"admin.php?action=ewww_image_optimizer_manual_restore&amp;ewww_manual_nonce=$ewww_manual_nonce&amp;ewww_attachment_ID=%d\">%s</a>",
					$id,
					esc_html__( 'Restore original', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
			}

			// link to webp upgrade script
			$oldwebpfile = preg_replace('/\.\w+$/', '.webp', $file_path);
			if ( file_exists( $oldwebpfile ) && current_user_can( apply_filters( 'ewww_image_optimizer_admin_permissions', '' ) ) ) {
				echo "<br><a href='options.php?page=ewww-image-optimizer-webp-migrate'>" . esc_html__( 'Run WebP upgrade', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a>";
			}

			// determine filepath for webp
			$webpfile = $file_path . '.webp';
			$webp_size = ewww_image_optimizer_filesize( $webpfile );
			if ( $webp_size ) {
				$webp_size = size_format( $webp_size, 2 );
				$webpurl = esc_url( wp_get_attachment_url( $id ) . '.webp' );
				// get a human readable filesize
				$webp_size = preg_replace( '/\.00 B /', ' B', $webp_size );
				echo "<br>WebP: <a href='$webpurl'>$webp_size</a>";
			}
		} else {
			// otherwise, this must be an image we haven't processed
			esc_html_e( 'Not processed', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			// tell them the filesize
			echo "<br>" . sprintf( esc_html__( 'Image Size: %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $file_size );
			if ( empty( $msg ) && current_user_can( apply_filters( 'ewww_image_optimizer_manual_permissions', '' ) ) ) {
				// and give the user the option to optimize the image right now
				printf( "<br><a href=\"admin.php?action=ewww_image_optimizer_manual_optimize&amp;ewww_manual_nonce=$ewww_manual_nonce&amp;ewww_attachment_ID=%d\">%s</a>", $id, esc_html__( 'Optimize now!', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
				if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_convert_links' ) && 'ims_image' != get_post_type( $id ) ) {
					echo " | <a class='ewww-convert' title='$convert_desc' href='admin.php?action=ewww_image_optimizer_manual_optimize&amp;ewww_manual_nonce=$ewww_manual_nonce&amp;ewww_attachment_ID=$id&amp;ewww_convert=1&amp;ewww_force=1'>$convert_link</a>";
				}
			} else {
				echo $msg;
			}
		}
	}
	ewwwio_memory( __FUNCTION__ );
}

function ewww_image_optimizer_load_admin_js() {
	add_action( 'admin_print_footer_scripts', 'ewww_image_optimizer_add_bulk_actions_via_javascript' ); 
}

// Borrowed from http://www.viper007bond.com/wordpress-plugins/regenerate-thumbnails/
// adds a bulk optimize action to the drop-down on the media library page
function ewww_image_optimizer_add_bulk_actions_via_javascript() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( ! current_user_can( apply_filters( 'ewww_image_optimizer_bulk_permissions', '' ) ) ) {
		return;
	}
?>
	<script type="text/javascript"> 
		jQuery(document).ready(function($){ 
			$('select[name^="action"] option:last-child').before('<option value="bulk_optimize"><?php esc_html_e('Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN); ?></option>');
			$('.ewww-convert').tooltip();
		}); 
	</script>
<?php } 

// Handles the bulk actions POST 
// Borrowed from http://www.viper007bond.com/wordpress-plugins/regenerate-thumbnails/ 
function ewww_image_optimizer_bulk_action_handler() { 
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// if the requested action is blank, or not a bulk_optimize, do nothing
	if ( ( empty( $_REQUEST['action'] ) || 'bulk_optimize' != $_REQUEST['action'] ) && ( empty( $_REQUEST['action2'] ) || 'bulk_optimize' != $_REQUEST['action2'] ) ) {
		return;
	}
	// if there is no media to optimize, do nothing
	if ( empty( $_REQUEST['media'] ) || ! is_array( $_REQUEST['media'] ) ) {
		return; 
	}
	// check the referring page
	check_admin_referer( 'bulk-media' ); 
	// prep the attachment IDs for optimization
	$ids = implode( ',', array_map( 'intval', $_REQUEST['media'] ) ); 
	wp_redirect( add_query_arg( array( 'page' => 'ewww-image-optimizer-bulk', '_wpnonce' => wp_create_nonce( 'ewww-image-optimizer-bulk' ), 'goback' => 1, 'ids' => $ids ), admin_url( 'upload.php' ) ) ); 
	ewwwio_memory( __FUNCTION__ );
	exit(); 
}

// display a page of unprocessed images from Media library
function ewww_image_optimizer_display_unoptimized_media() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$bulk_resume = get_option( 'ewww_image_optimizer_bulk_resume' );
	update_option( 'ewww_image_optimizer_bulk_resume', '' );
	$attachments = ewww_image_optimizer_count_optimized( 'media', true );
	update_option( 'ewww_image_optimizer_bulk_resume', $bulk_resume );
	echo "<div class='wrap'><h1>" . esc_html__( 'Unoptimized Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</h1>";
	printf( '<p>' . esc_html__( 'We have %d images to optimize.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</p>', count( $attachments ) );
	if ( count( $attachments ) != 0 ) {
		sort( $attachments, SORT_NUMERIC );
		$image_string = implode( ',', $attachments );
		echo '<form method="post" action="upload.php?page=ewww-image-optimizer-bulk">'
			. "<input type='hidden' name='ids' value='$image_string' />"
			. '<input type="submit" class="button-secondary action" value="' . esc_html__( 'Optimize All Images', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '" />'
			. '</form>';
		if ( count( $attachments ) < 500 ) {
			sort( $attachments, SORT_NUMERIC );
			$image_string = implode( ',', $attachments );
			echo '<table class="wp-list-table widefat media" cellspacing="0"><thead><tr><th>ID</th><th>&nbsp;</th><th>' . esc_html__('Title', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th><th>' . esc_html__('Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</th></tr></thead>';
			$alternate = true;
			foreach ( $attachments as $ID ) {
				$image_name = get_the_title( $ID );
?>				<tr<?php if( $alternate ) echo " class='alternate'"; ?>><td><?php echo $ID; ?></td>
<?php				echo "<td style='width:80px' class='column-icon'>" . wp_get_attachment_image( $ID, 'thumbnail' ) . "</td>";
				echo "<td class='title'>$image_name</td>";
				echo "<td>";
				ewww_image_optimizer_custom_column( 'ewww-image-optimizer', $ID );
				echo "</td></tr>";
				$alternate = ! $alternate;
			}
			echo '</table>';
		} else {
			echo '<p>' . esc_html__( 'There are too many images to display.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</p>'; 
		}
	}
	echo '</div>';
	return;	
}

// retrieve an option: use 'site' setting if plugin is network activated, otherwise use 'blog' setting
function ewww_image_optimizer_get_option( $option_name ) {
	if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
		$option_value = get_site_option( $option_name );
	} else {
		$option_value = get_option( $option_name );
	}
	return $option_value;
}

// set an option: use 'site' setting if plugin is network activated, otherwise use 'blog' setting
function ewww_image_optimizer_set_option( $option_name, $option_value ) {
	if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
		$success = update_site_option( $option_name, $option_value );
	} else {
		$success = update_option( $option_name, $option_value );
	}
	return $success;
}

function ewww_image_optimizer_settings_script( $hook ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	// make sure we are being called from the bulk optimization page
	if ( strpos( $hook,'settings_page_ewww-image-optimizer' ) !== 0 ) {
		return;
	}
	wp_enqueue_script( 'ewwwbulkscript', plugins_url( '/includes/eio.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_script( 'postbox' );
	wp_enqueue_script( 'dashboard' );
	wp_localize_script( 'ewwwbulkscript', 'ewww_vars', array(
			'_wpnonce' => wp_create_nonce( 'ewww-image-optimizer-settings' ),
		)
	);
	ewwwio_memory( __FUNCTION__ );
	return;
}

function ewww_image_optimizer_savings() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $wpdb;
	if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
		ewwwio_debug_message( 'querying savings for multi-site' );
		if ( function_exists( 'wp_get_sites' ) ) {
			ewwwio_debug_message( 'retrieving list of sites the easy way' );
			add_filter( 'wp_is_large_network', 'ewww_image_optimizer_large_network', 20, 0 );
			$blogs = wp_get_sites( array(
				'network_id' => $wpdb->siteid,
				'limit' => 10000
			) );
			remove_filter( 'wp_is_large_network', 'ewww_image_optimizer_large_network', 20, 0 );
		} else {
			ewwwio_debug_message( 'retrieving list of sites the hard way' );
			$query = "SELECT blog_id FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ";
			$blogs = $wpdb->get_results( $query, ARRAY_A );
		}
		$total_savings = 0;
		foreach ( $blogs as $blog ) {
			switch_to_blog( $blog['blog_id'] );
			ewwwio_debug_message( "getting savings for site: {$blog['blog_id']}" );
			$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'ewwwio_images WHERE image_size > orig_size' );
			$total_query = "SELECT SUM(orig_size-image_size) FROM $wpdb->ewwwio_images";
			ewwwio_debug_message( "query to be performed: $total_query" );
			$savings = $wpdb->get_var($total_query);
			ewwwio_debug_message( "savings found: $savings" );
			$total_savings += $savings;
		}
		restore_current_blog();
	} else {
		ewwwio_debug_message( 'querying savings for single site' );
		$total_savings = 0;
		$wpdb->query( 'DELETE FROM ' . $wpdb->prefix . 'ewwwio_images WHERE image_size > orig_size' );
		$total_query = "SELECT SUM(orig_size-image_size) FROM $wpdb->ewwwio_images";
		ewwwio_debug_message( "query to be performed: $total_query" );
		$total_savings = $wpdb->get_var($total_query);
		ewwwio_debug_message( "savings found: $total_savings" );
	}
	return $total_savings;
}

function ewww_image_optimizer_webp_rewrite() {
	// verify that the user is properly authorized
	if ( ! wp_verify_nonce( $_REQUEST['ewww_wpnonce'], 'ewww-image-optimizer-settings' ) ) {
		wp_die( esc_html__( 'Access denied.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	if ( $ewww_rules = ewww_image_optimizer_webp_rewrite_verify() ) {
		if ( insert_with_markers( get_home_path() . '.htaccess', 'EWWWIO', $ewww_rules ) && ! ewww_image_optimizer_webp_rewrite_verify() ) {
			esc_html_e( 'Insertion successful', EWWW_IMAGE_OPTIMIZER_DOMAIN );
		} else {
			esc_html_e( 'Insertion failed', EWWW_IMAGE_OPTIMIZER_DOMAIN );
		}
	}
	die();
}

// if rules are present, stay silent, otherwise, give us some rules to insert!
function ewww_image_optimizer_webp_rewrite_verify() {
	$current_rules = extract_from_markers( get_home_path() . '.htaccess', 'EWWWIO' ) ;
	$ewww_rules = array(
		"<IfModule mod_rewrite.c>",
		"RewriteEngine On",
		"RewriteCond %{HTTP_ACCEPT} image/webp",
		"RewriteCond %{REQUEST_FILENAME} (.*)\.(jpe?g|png)$",
		"RewriteCond %{REQUEST_FILENAME}.webp -f",
		"RewriteRule (.+)\.(jpe?g|png)$ %{REQUEST_FILENAME}.webp [T=image/webp,E=accept:1]",
		"</IfModule>",
		"<IfModule mod_headers.c>",
		"Header append Vary Accept env=REDIRECT_accept",
		"</IfModule>",
		"AddType image/webp .webp",
	);
	if ( array_diff( $ewww_rules, $current_rules ) ) {
		ewwwio_memory( __FUNCTION__ );
		return $ewww_rules;
	} else {
		ewwwio_memory( __FUNCTION__ );
		return;
	}
}

function ewww_image_optimizer_get_image_sizes() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	global $_wp_additional_image_sizes;
	$sizes = array();
	$image_sizes = get_intermediate_image_sizes();
	ewwwio_debug_message( print_r( $image_sizes, true ) );
//	ewwwio_debug_message( print_r( $_wp_additional_image_sizes, true ) );
	foreach( $image_sizes as $_size ) {
		if ( in_array( $_size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
			$sizes[ $_size ]['width'] = get_option( $_size . '_size_w' );
			$sizes[ $_size ]['height'] = get_option( $_size . '_size_h' );
			if ( $_size === 'medium_large' && $sizes[ $_size ]['width'] == 0 ) {
				$sizes[ $_size ]['width'] = '768';
			}
			if ( $_size === 'medium_large' && $sizes[ $_size ]['height'] == 0 ) {
				$sizes[ $_size ]['height'] = '9999';
			}
		} elseif ( isset( $_wp_additional_image_sizes[ $_size ] ) ) {
			$sizes[ $_size ] = array( 
				'width' => $_wp_additional_image_sizes[ $_size ]['width'],
				'height' => $_wp_additional_image_sizes[ $_size ]['height'],
			);
		}
	}
	ewwwio_debug_message( print_r( $sizes, true ) );
	return $sizes;
}

// displays the EWWW IO options and provides one-click install for the optimizer utilities
function ewww_image_optimizer_options () {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_debug_message( 'ABSPATH: ' . ABSPATH );
	ewwwio_debug_message( 'home url: ' . get_home_url() );
	ewwwio_debug_message( 'site url: ' . get_site_url() );

	if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	$output = array();
	if (isset($_REQUEST['ewww_pngout'])) {
		if ($_REQUEST['ewww_pngout'] == 'success') {
			$output[] = "<div id='ewww-image-optimizer-pngout-success' class='updated fade'>\n";
			$output[] = '<p>' . esc_html__('Pngout was successfully installed, check the Plugin Status area for version information.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>\n";
			$output[] = "</div>\n";
		}
		if ($_REQUEST['ewww_pngout'] == 'failed') {
			$output[] = "<div id='ewww-image-optimizer-pngout-failure' class='error'>\n";
			$output[] = '<p>' . sprintf( esc_html__('Pngout was not installed: %1$s. Make sure this folder is writable: %2$s', EWWW_IMAGE_OPTIMIZER_DOMAIN), sanitize_text_field( $_REQUEST['ewww_error'] ), EWWW_IMAGE_OPTIMIZER_TOOL_PATH) . "</p>\n";
			$output[] = "</div>\n";
		}
	}
	$output[] = "<script type='text/javascript'>\n" .
		'jQuery(document).ready(function($) {$(".fade").fadeTo(5000,1).fadeOut(3000);$(".updated").fadeTo(5000,1).fadeOut(3000);});' . "\n" .
		"</script>\n";
	$output[] = "<style>\n" .
		".ewww-tab a { font-size: 15px; font-weight: 700; color: #555; text-decoration: none; line-height: 36px; padding: 0 10px; }\n" .
		".ewww-tab a:hover { color: #464646; }\n" .
		".ewww-tab { margin: 0 0 0 5px; padding: 0px; border-width: 1px 1px 1px; border-style: solid solid none; border-image: none; border-color: #ccc; display: inline-block; background-color: #e4e4e4 }\n" .
		".ewww-tab:hover { background-color: #fff }\n" .
		".ewww-selected { background-color: #f1f1f1; margin-bottom: -1px; border-bottom: 1px solid #f1f1f1 }\n" .
		".ewww-selected a { color: #000; }\n" .
		".ewww-selected:hover { background-color: #f1f1f1; }\n" .
		".ewww-tab-nav { list-style: none; margin: 10px 0 0; padding-left: 5px; border-bottom: 1px solid #ccc; }\n" .
	"</style>\n";
	$output[] = "<div class='wrap'>\n";
	$output[] = "<h1>EWWW Image Optimizer</h1>\n";
	$output[] = "<div id='ewww-container-left' style='float: left; margin-right: 225px;'>\n";
	$output[] = "<p><a href='https://wordpress.org/extend/plugins/ewww-image-optimizer/'>" . esc_html__( 'Plugin Home Page', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a> | " .
		"<a href='https://wordpress.org/extend/plugins/ewww-image-optimizer/installation/'>" .  esc_html__( 'Installation Instructions', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a> | " .
		"<a href='https://wordpress.org/support/plugin/ewww-image-optimizer'>" . esc_html__( 'Plugin Support', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a> | " .
		"<a href='https://ewww.io/status/'>" . esc_html__( 'Cloud Status', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a> | " .
		"<a href='https://ewww.io/downloads/s3-image-optimizer/'>" . esc_html__( 'S3 Image Optimizer', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a></p>\n";
		if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
			$bulk_link = esc_html__( 'Media Library', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . ' -> ' . esc_html__( 'Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN );
		} else {
			$bulk_link = '<a href="upload.php?page=ewww-image-optimizer-bulk">' . esc_html__( 'Bulk Optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</a>';
		}
	$output[] = "<p>" . wp_kses( sprintf( __( 'New images uploaded to the Media Library will be optimized automatically. If you have existing images you would like to optimize, you can use the %s tool.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $bulk_link ), array( 'a' => array( 'href' => array() ) ) ) . " " . wp_kses( __( 'Images stored in an Amazon S3 bucket can be optimized using our <a href="https://ewww.io/downloads/s3-image-optimizer/">S3 Image Optimizer</a>.' ), array( 'a' => array( 'href' => array() ) ) ) . "</p>\n";
	if ( EWWW_IMAGE_OPTIMIZER_CLOUD ) {
		$collapsed = '';
	} else {
		$collapsed = "$('#ewww-status').toggleClass('closed');\n";
	}
	$output[] = "<div id='ewww-widgets' class='metabox-holder'><div class='meta-box-sortables'><div id='ewww-status' class='postbox'>\n" .
		"<button type='button' class='handlediv button-link' aria-expanded='true'>" .
			"<span class='screen-reader-text'>" . esc_html__( 'Click to toggle', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</span>" .
			"<span class='toggle-indicator' aria-hidden='true'></span>" .
		"</button>" .
		"<h2 class='hndle'>" . esc_html__('Plugin Status', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "&emsp;" .
			"<span id='ewww-status-ok' style='display: none; color: green;'>" . esc_html__('All Clear', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>" . 
			"<span id='ewww-status-attention' style='color: red;'>" . esc_html__('Requires Attention', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>"  .
		"</h2>\n" .
			"<div class='inside'>" .
			"<b>" . esc_html__('Total Savings:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> <span id='ewww-total-savings'>" . size_format( ewww_image_optimizer_savings(), 2 ) . "</span><br>";
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_key')) {
				$output[] = '<p><b>' . esc_html__('Cloud optimization API Key', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ":</b> ";
				$verify_cloud = ewww_image_optimizer_cloud_verify( false ); 
				if ( preg_match( '/great/', $verify_cloud ) ) {
					$output[] = '<span style="color: green">' . esc_html__('Verified,', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' </span>' . ewww_image_optimizer_cloud_quota();
				} elseif ( preg_match( '/exceeded/', $verify_cloud ) ) { 
					$output[] = '<span style="color: orange">' . esc_html__('Verified,', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' </span>' . ewww_image_optimizer_cloud_quota();
				} else { 
					$output[] = '<span style="color: red">' . esc_html__('Not Verified', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>';
				}
				$output[] = "</p>\n";
			}
			$collapsible = true;
			if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_skip_bundle' ) && ! EWWW_IMAGE_OPTIMIZER_CLOUD && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				$output[] = "<p>" . esc_html__( 'If updated versions are available below you may either download the newer versions and install them yourself, or uncheck "Use System Paths" and use the bundled tools. ', EWWW_IMAGE_OPTIMIZER_DOMAIN)  . "<br />\n" .
					"<i>*" . esc_html__('Updates are optional, but may contain increased optimization or security patches', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</i></p>\n";
			} elseif ( ! EWWW_IMAGE_OPTIMIZER_CLOUD && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				$output[] = "<p>" . sprintf( esc_html__( 'If updated versions are available below, you may need to enable write permission on the %s folder to use the automatic installs.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), '<i>' . EWWW_IMAGE_OPTIMIZER_TOOL_PATH . '</i>' ) . "<br />\n" .
					"<i>*" . esc_html__( 'Updates are optional, but may contain increased optimization or security patches', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</i></p>\n";
			}
			if ( ! EWWW_IMAGE_OPTIMIZER_CLOUD && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				list ( $jpegtran_src, $optipng_src, $gifsicle_src, $jpegtran_dst, $optipng_dst, $gifsicle_dst ) = ewww_image_optimizer_install_paths();
			}
			$output[] = "<p>\n";
			if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_jpegtran') && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg')  && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				$output[] = "<b>jpegtran:</b> ";
				$jpegtran_installed = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_JPEGTRAN, 'j' );
				if ( ! $jpegtran_installed ) {
					$jpegtran_installed = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_JPEGTRAN, 'jb' );
				}
				if (!empty($jpegtran_installed)) {
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;' . esc_html__('version', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $jpegtran_installed . "<br />\n"; 
				} else { 
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
					$collapsible = false;
				}
			}
			if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng') && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png') && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				$output[] = "<b>optipng:</b> ";
				$optipng_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_OPTIPNG, 'o' );
				if ( ! $optipng_version ) {
					$optipng_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_OPTIPNG, 'ob' );
				}
				if ( ! empty( $optipng_version ) ) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;' . esc_html__('version', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $optipng_version . "<br />\n"; 
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
					$collapsible = false;
				}
			}
			if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_gifsicle' ) && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_gif' ) && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				$output[] = "<b>gifsicle:</b> ";
				$gifsicle_version = ewww_image_optimizer_tool_found(EWWW_IMAGE_OPTIMIZER_GIFSICLE, 'g');
				if ( ! $gifsicle_version ) {
					$gifsicle_version = ewww_image_optimizer_tool_found(EWWW_IMAGE_OPTIMIZER_GIFSICLE, 'gb');
				}
				if ( ! empty( $gifsicle_version ) ) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;' . esc_html__('version', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $gifsicle_version . "<br />\n";
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
					$collapsible = false;
				}
			}
			if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout') && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png') && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				$output[] = "<b>pngout:</b> ";
				$pngout_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_PNGOUT, 'p' );
				if ( ! $pngout_version ) {
					$pngout_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_PNGOUT, 'pb' );
				}
				if ( ! empty( $pngout_version ) ) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__( 'Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</span>&emsp;' . esc_html__( 'version', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . ': ' . preg_replace( '/PNGOUT \[.*\)\s*?/', '', $pngout_version ) . "<br />\n";
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;<b>' . esc_html__('Install', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' <a href="admin.php?action=ewww_image_optimizer_install_pngout">' . esc_html__('automatically', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</a> | <a href="http://advsys.net/ken/utils.htm">' . esc_html__('manually', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</a></b> - ' . esc_html__('Pngout is free closed-source software that can produce drastically reduced filesizes for PNGs, but can be very time consuming to process images', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "<br />\n"; 
					$collapsible = false;
				}
			}
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy') && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png') && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				$output[] = "<b>pngquant:</b> ";
				$pngquant_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_PNGQUANT, 'q' );
				if ( ! $pngquant_version ) {
					$pngquant_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_PNGQUANT, 'qb' );
				}
				if ( !empty( $pngquant_version ) ) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;' . esc_html__('version', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $pngquant_version . "<br />\n"; 
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
					$collapsible = false;
				}
			}
			if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp' ) && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_png' ) && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_jpg' ) && ! EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
				$output[] = "<b>webp:</b> ";
				$webp_version = ewww_image_optimizer_tool_found(EWWW_IMAGE_OPTIMIZER_WEBP, 'w');
				if ( ! $webp_version ) {
					$webp_version = ewww_image_optimizer_tool_found( EWWW_IMAGE_OPTIMIZER_WEBP, 'wb' );
				}
				if ( ! empty( $webp_version ) ) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__( 'Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</span>&emsp;' . esc_html__( 'version', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . ': ' . $webp_version . "<br />\n"; 
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
					$collapsible = false;
				}
			}
			if (!EWWW_IMAGE_OPTIMIZER_CLOUD && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				if (ewww_image_optimizer_safemode_check()) {
					$output[] = 'safe mode: <span style="color: red; font-weight: bolder">' . esc_html__('On', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
					$collapsible = false;
				} else {
					$output[] = 'safe mode: <span style="color: green; font-weight: bolder">' . esc_html__('Off', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
				}
				if (ewww_image_optimizer_exec_check()) {
					$output[] = 'exec(): <span style="color: red; font-weight: bolder">' . esc_html__('Disabled', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
					$collapsible = false;
				} else {
					$output[] = 'exec(): <span style="color: green; font-weight: bolder">' . esc_html__('Enabled', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
				}
				$output[] = "<br />\n";
				$output[] = wp_kses( sprintf( __( "%s only need one, used for conversion, not optimization", EWWW_IMAGE_OPTIMIZER_DOMAIN ), '<b>' . __( 'Graphics libraries', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</b> - ' ), array( 'b' => array() ) );
				$output[] = '<br>';
				$toolkit_found = false;
				if ( ewww_image_optimizer_gd_support() ) {
					$output[] = 'GD: <span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$toolkit_found = true;
				} else {
					$output[] = 'GD: <span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				}
				$output[] = '</span>&emsp;&emsp;' .
					"Gmagick: ";
				if ( ewww_image_optimizer_gmagick_support() ) {
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$toolkit_found = true;
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				}
				$output[] = '</span>&emsp;&emsp;' .
					"Imagick: ";
				if ( ewww_image_optimizer_imagick_support() ) {
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN);
					$toolkit_found = true;
				} else {
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN);
				}
				$output[] = '</span>&emsp;&emsp;' .
					"Imagemagick 'convert': ";
				if ( ewww_image_optimizer_find_nix_binary( 'convert', 'i' ) ) { 
					$output[] = '<span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>';
					$toolkit_found = true;
				} else { 
					$output[] = '<span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>';
				}
				if ( ! $toolkit_found && ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_to_jpg' ) || ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_to_png' ) ) ) {
					$collapsible = false;
				}
				$output[] = "<br />\n";
			}
			$output[] = '<b>' . esc_html__('Only need one of these:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' </b><br>';
			// initialize this variable to check for the 'file' command if we don't have any php libraries we can use
			$file_command_check = true;
			if ( function_exists( 'finfo_file' ) ) {
				$output[] = 'finfo: <span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
				$file_command_check = false;
			} else {
				$output[] = 'finfo: <span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
			}
			if ( function_exists( 'getimagesize' ) ) {
				$output[] = 'getimagesize(): <span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
				if ( EWWW_IMAGE_OPTIMIZER_CLOUD || EWWW_IMAGE_OPTIMIZER_NOEXEC || PHP_OS == 'WINNT' ) {
					$file_command_check = false;
				}
			} else {
				$output[] = 'getimagesize(): <span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span>&emsp;&emsp;';
			}
			if (function_exists('mime_content_type')) {
				$output[] = 'mime_content_type(): <span style="color: green; font-weight: bolder">' . esc_html__('Installed', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
				$file_command_check = false;
			} else {
				$output[] = 'mime_content_type(): <span style="color: red; font-weight: bolder">' . esc_html__('Missing', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />' . "\n";
			}
			if (PHP_OS != 'WINNT' && !EWWW_IMAGE_OPTIMIZER_CLOUD && !EWWW_IMAGE_OPTIMIZER_NOEXEC) {
				if ($file_command_check && !ewww_image_optimizer_find_nix_binary('file', 'f')) {
					$output[] = '<span style="color: red; font-weight: bolder">file: ' . esc_html__('command not found on your system', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</span><br />';
					$collapsible = false;
				}
				if (!ewww_image_optimizer_find_nix_binary('nice', 'n')) {
					$output[] = '<span style="color: orange; font-weight: bolder">nice: ' . esc_html__('command not found on your system', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' (' . esc_html__('not required', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ')</span><br />';
				}
				if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout') && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png') && PHP_OS != 'SunOS' && !ewww_image_optimizer_find_nix_binary('tar', 't')) {
					$output[] = '<span style="color: red; font-weight: bolder">tar: ' . esc_html__('command not found on your system', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ' (' . esc_html__('required for automatic pngout installer', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ')</span><br />';
					$collapsible = false;
				}
			} elseif ( $file_command_check ) {
				$collapsible = false;
			}
			$output[] = '</div><!-- end .inside -->';
			if ( $collapsible ) {
				$output[] = "<script type='text/javascript'>\n" .
					"jQuery(document).ready(function($) {\n" .
					$collapsed .
					"$('#ewww-status-attention').hide();\n" .
					"$('#ewww-status-ok').show();\n" .
					"});\n" .
					"</script>\n";
			}
			$output[] = "</div></div></div>\n";
	$output[] = "<ul class='ewww-tab-nav'>\n" .
		"<li class='ewww-tab ewww-cloud-nav'><span class='ewww-tab-hidden'><a class='ewww-cloud-nav' href='#'>" . esc_html__('Cloud Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></span></li>\n" .
		"<li class='ewww-tab ewww-general-nav'><span class='ewww-tab-hidden'><a class='ewww-general-nav' href='#'>" . esc_html__('Basic Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></span></li>\n" .
		"<li class='ewww-tab ewww-optimization-nav'><span class='ewww-tab-hidden'><a class='ewww-optimization-nav' href='#'>" .  esc_html__('Advanced Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></span></li>\n" .
		"<li class='ewww-tab ewww-conversion-nav'><span class='ewww-tab-hidden'><a class='ewww-conversion-nav' href='#'>" . esc_html__('Conversion Settings', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></span></li>\n" .
	"</ul>\n";
			if ( is_multisite() && is_plugin_active_for_network(EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
				$output[] = "<form method='post' action=''>\n";
			} else {
				$output[] = "<form method='post' action='options.php'>\n";
			}
			$output[] = "<input type='hidden' name='option_page' value='ewww_image_optimizer_options' />\n";
		        $output[] = "<input type='hidden' name='action' value='update' />\n";
		        $output[] = wp_nonce_field( "ewww_image_optimizer_options-options", '_wpnonce', true, false ) . "\n";
			$output[] = "<div id='ewww-cloud-settings'>\n";
			$output[] = "<p>" . esc_html__('If exec() is disabled for security reasons (and enabling it is not an option), or you would like to offload image optimization to a third-party server, you may purchase an API key for our cloud optimization service. The API key should be entered below, and cloud optimization must be enabled for each image format individually.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <a href='https://ewww.io/plans/'>" . esc_html__('Purchase an API key.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></p>\n";
			$output[] = "<table class='form-table'>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_cloud_key'>" . esc_html__( 'Cloud optimization API Key', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</label></th><td><input type='password' id='ewww_image_optimizer_cloud_key' name='ewww_image_optimizer_cloud_key' value='" . ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_key' ) . "' size='32' /> " . esc_html__('API Key will be validated when you save your settings.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <a href='https://ewww.io/plans/'>" . esc_html__( 'Purchase an API key.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a></td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_cloud_jpg'>" . esc_html__( 'JPG cloud optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_cloud_jpg' name='ewww_image_optimizer_cloud_jpg' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
				ewwwio_debug_message( "cloud JPG: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_cloud_png'>" . esc_html__('PNG cloud optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_cloud_png' name='ewww_image_optimizer_cloud_png' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png') == TRUE ? "checked='true'" : "" ) . " /></td></tr>";
				ewwwio_debug_message( "cloud PNG: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png') == TRUE ? "on" : "off" ) );
					$output[] = "<tr><td>&nbsp;</td><td><input type='checkbox' id='ewww_image_optimizer_cloud_png_compress' name='ewww_image_optimizer_cloud_png_compress' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png_compress') == TRUE ? "checked='true'" : "" ) . " /><label for='ewww_image_optimizer_cloud_png_compress'>" . esc_html__( 'extra PNG compression (slower)', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</label></td></tr>\n";
				ewwwio_debug_message( "PNG extra compress: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png_compress') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_cloud_gif'>" . esc_html__('GIF cloud optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_cloud_gif' name='ewww_image_optimizer_cloud_gif' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_gif') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
				ewwwio_debug_message( "cloud GIF: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_gif') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_lossy_fast'>" . esc_html__('Faster lossy optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_lossy_fast' name='ewww_image_optimizer_lossy_fast' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_lossy_fast') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('Speed up the lossy operations by performing less compression.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "cloud fast lossy: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_lossy_fast') == TRUE ? "on" : "off" ) );
			$output[] = "</table>\n</div>\n";
			$output[] = "<div id='ewww-general-settings'>\n";
			$output[] = "<table class='form-table'>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_debug'>" . esc_html__('Debugging', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_debug' name='ewww_image_optimizer_debug' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_debug') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('Use this to provide information for support purposes, or if you feel comfortable digging around in the code to fix a problem you are experiencing.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_jpegtran_copy'>" . esc_html__('Remove metadata', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th>\n" .
				"<td><input type='checkbox' id='ewww_image_optimizer_jpegtran_copy' name='ewww_image_optimizer_jpegtran_copy' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpegtran_copy') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('This will remove ALL metadata: EXIF and comments.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "remove metadata: " . ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpegtran_copy' ) == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_jpg_lossy'>" . esc_html__('Lossy JPG optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_jpg_lossy' name='ewww_image_optimizer_jpg_lossy' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_lossy') == TRUE ? "checked='true'" : "" ) . " /> <b>" . esc_html__( 'WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</b> " . esc_html__('While most users will not notice a difference in image quality, lossy means there IS a loss in image quality.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "\n" .
				"<p class='description'>" . esc_html__('Requires an EWWW Image Optimizer Cloud Subscription.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <a href='https://ewww.io/plans/'>" . esc_html__('Purchase an API key.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></p></td></tr>\n";
				ewwwio_debug_message( "lossy JPG: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_lossy') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_png_lossy'>" . esc_html__('Lossy PNG optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_png_lossy' name='ewww_image_optimizer_png_lossy' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy') == TRUE ? "checked='true'" : "" ) . " /> <b>" . esc_html__('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> " . esc_html__('While most users will not notice a difference in image quality, lossy means there IS a loss in image quality.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "\n" .
				"<p class='description'>" . esc_html__('Uses pngquant locally. Use EWWW I.O. Cloud for even better lossy compression.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <a href='https://ewww.io/plans/'>" . esc_html__('Purchase an API key.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></p></td></tr>\n";
				ewwwio_debug_message( "lossy PNG: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_delay'>" . esc_html__('Bulk Delay', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='text' id='ewww_image_optimizer_delay' name='ewww_image_optimizer_delay' size='5' value='" . ewww_image_optimizer_get_option('ewww_image_optimizer_delay') . "'> " . esc_html__('Choose how long to pause between images (in seconds, 0 = disabled)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "bulk delay: " . ewww_image_optimizer_get_option('ewww_image_optimizer_delay') );
	if (class_exists('Cloudinary') && Cloudinary::config_get("api_secret")) {
				$output[] = "<tr><th><label for='ewww_image_optimizer_enable_cloudinary'>" . esc_html__('Automatic Cloudinary upload', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_enable_cloudinary' name='ewww_image_optimizer_enable_cloudinary' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_enable_cloudinary') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('When enabled, uploads to the Media Library will be transferred to Cloudinary after optimization. Cloudinary generates resizes, so only the full-size image is uploaded.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "cloudinary upload: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_enable_cloudinary') == TRUE ? "on" : "off" ) );
	}
			$output[] = "</table>\n</div>\n";
			$output[] = "<div id='ewww-optimization-settings'>\n";
			$output[] = "<table class='form-table'>\n";
			if ( EWWW_IMAGE_OPTIMIZER_CLOUD ) {
				$output[] = "<input id='ewww_image_optimizer_optipng_level' name='ewww_image_optimizer_optipng_level' type='hidden' value='2'>\n" .
					"<input id='ewww_image_optimizer_pngout_level' name='ewww_image_optimizer_pngout_level' type='hidden' value='2'>\n";
			} else {
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_optipng_level'>" . esc_html__('optipng optimization level', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th>\n" .
				"<td><span><select id='ewww_image_optimizer_optipng_level' name='ewww_image_optimizer_optipng_level'>\n" .
				"<option value='1'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 1 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 1) . ': ' . sprintf(esc_html__('%d trial', EWWW_IMAGE_OPTIMIZER_DOMAIN), 1) . "</option>\n" .
				"<option value='2'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 2 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 2) . ': ' . sprintf(esc_html__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 8) . "</option>\n" .
				"<option value='3'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 3 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 3) . ': ' . sprintf(esc_html__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 16) . "</option>\n" .
				"<option value='4'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 4 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 4) . ': ' . sprintf(esc_html__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 24) . "</option>\n" .
				"<option value='5'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 5 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 5) . ': ' . sprintf(esc_html__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 48) . "</option>\n" .
//				"<option value='6'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 6 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 6) . ': ' . sprintf(esc_html__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 120) . "</option>\n" .
//				"<option value='7'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') == 7 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 7) . ': ' . sprintf(esc_html__('%d trials', EWWW_IMAGE_OPTIMIZER_DOMAIN), 240) . "</option>\n" .
				"</select> (" . esc_html__('default', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "=2)</span>\n" .
				"<p class='description'>" . esc_html__( 'Levels 4 and above are unlikely to yield any additional savings.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></td></tr>\n";
				ewwwio_debug_message( "optipng level: " . ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level') );
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_pngout_level'>" . esc_html__('pngout optimization level', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th>\n" .
				"<td><span><select id='ewww_image_optimizer_pngout_level' name='ewww_image_optimizer_pngout_level'>\n" .
				"<option value='0'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level') == 0 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 0) . ': ' . esc_html__('Xtreme! (Slowest)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n" .
				"<option value='1'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level') == 1 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 1) . ': ' . esc_html__('Intense (Slow)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n" .
				"<option value='2'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level') == 2 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 2) . ': ' . esc_html__('Longest Match (Fast)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n" .
				"<option value='3'" . ( ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level') == 3 ? " selected='selected'" : "" ) . '>' . sprintf(esc_html__('Level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 3) . ': ' . esc_html__('Huffman Only (Faster)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</option>\n" .
				"</select> (" . esc_html__('default', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "=2)</span>\n" .
				"<p class='description'>" . sprintf(esc_html__('If you have CPU cycles to spare, go with level %d', EWWW_IMAGE_OPTIMIZER_DOMAIN), 0) . "</p></td></tr>\n";
				ewwwio_debug_message( "pngout level: " . ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level') );
			}
				$output[] = "<tr><th><label for='ewww_image_optimizer_auto'>" . esc_html__('Scheduled optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_auto' name='ewww_image_optimizer_auto' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_auto') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('This will enable scheduled optimization of unoptimized images for your theme, buddypress, and any additional folders you have configured below. Runs hourly: wp_cron only runs when your site is visited, so it may be even longer between optimizations.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "scheduled optimization: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_auto') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_aux_paths'>" . esc_html__('Folders to optimize', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td>" . sprintf(esc_html__('One path per line, must be within %s. Use full paths, not relative paths.', EWWW_IMAGE_OPTIMIZER_DOMAIN), ABSPATH) . "<br>\n";
				$output[] = "<textarea id='ewww_image_optimizer_aux_paths' name='ewww_image_optimizer_aux_paths' rows='3' cols='60'>" . ( ( $aux_paths = ewww_image_optimizer_get_option( 'ewww_image_optimizer_aux_paths' ) ) ? implode( "\n", $aux_paths ) : "" ) . "</textarea>\n";
				$output[] = "<p class='description'>" . esc_html__( 'Provide paths containing images to be optimized using "Scan and Optimize" on the Bulk Optimize page or by Scheduled Optimization.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</p></td></tr>\n";
				if ( ! empty( $aux_paths ) ) {
					ewwwio_debug_message( "folders to optimize:" );
					foreach ( $aux_paths as $aux_path ) {
						ewwwio_debug_message( $aux_path );
					}
				}
				ewwwio_debug_message( "deferred optimization: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_defer') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_defer'>" . esc_html__( 'Deferred Optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_defer' name='ewww_image_optimizer_defer' value='true' " . ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_defer' ) == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__( 'Optimize images later via wp_cron, after image upload or generation is complete.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_noauto'>" . esc_html__( 'Disable Automatic Optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_noauto' name='ewww_image_optimizer_noauto' value='true' " . ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_noauto' ) == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__( 'Images will not be optimized on upload. Images may be optimized with the Bulk Optimize tools or with Scheduled optimization.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</td></tr>\n";
				ewwwio_debug_message( "disable auto-optimization: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_noauto') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_include_media_paths'>" . esc_html__('Include Media Library Folders', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_include_media_paths' name='ewww_image_optimizer_include_media_paths' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_include_media_paths') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('If you have disabled automatic optimization, enable this if you want Scheduled Optimization to include the latest two folders from the Media Library.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "include media library: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_include_media_paths') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th>" . esc_html__( 'Disable Resizes', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</th><td><p>" . esc_html__( 'Wordpress, your theme, and other plugins generate various image sizes. You may disable optimization for certain sizes, or completely prevent those sizes from being created.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</p>\n";
				$image_sizes = ewww_image_optimizer_get_image_sizes();
				$disabled_sizes = ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_resizes' );
				$disabled_sizes_opt = ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_resizes_opt' );
				$output[] = '<table><tr><th>' . esc_html__( 'Disable Optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</th><th>' . esc_html__( 'Disable Creation', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</th></tr>\n";
				ewwwio_debug_message( 'disabled resizes:' );
				foreach ( $image_sizes as $size => $dimensions ) {
					if ( $size != 'thumbnail' ) {
						$output [] = "<tr><td><input type='checkbox' id='ewww_image_optimizer_disable_resizes_opt_$size' name='ewww_image_optimizer_disable_resizes_opt[$size]' value='true' " . ( ! empty( $disabled_sizes_opt[$size] ) ? "checked='true'" : "" ) . " /></td><td><input type='checkbox' id='ewww_image_optimizer_disable_resizes_$size' name='ewww_image_optimizer_disable_resizes[$size]' value='true' " . ( ! empty( $disabled_sizes[$size] ) ? "checked='true'" : "" ) . " /></td><td><label for='ewww_image_optimizer_disable_resizes_$size'>$size - {$dimensions['width']}x{$dimensions['height']}</label></td></tr>\n";
					} else {
						$output [] = "<tr><td><input type='checkbox' id='ewww_image_optimizer_disable_resizes_opt_$size' name='ewww_image_optimizer_disable_resizes_opt[$size]' value='true' " . ( ! empty( $disabled_sizes_opt[$size] ) ? "checked='true'" : "" ) . " /></td><td><input type='checkbox' id='ewww_image_optimizer_disable_resizes_$size' name='ewww_image_optimizer_disable_resizes[$size]' value='true' disabled /></td><td><label for='ewww_image_optimizer_disable_resizes_$size'>$size - {$dimensions['width']}x{$dimensions['height']}</label></td></tr>\n";
					}
					ewwwio_debug_message( $size . ': ' . ( ! empty( $disabled_sizes_opt[$size] ) ? "optimization=disabled " : "optimization=enabled " ) . ( ! empty( $disabled_sizes[$size] ) ? "creation=disabled" : "creation=enabled" ) );
				}
				$output[] = "</table>\n";
				$output[] = "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_skip_size'>" . esc_html__('Skip Small Images', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='text' id='ewww_image_optimizer_skip_size' name='ewww_image_optimizer_skip_size' size='8' value='" . ewww_image_optimizer_get_option('ewww_image_optimizer_skip_size') . "'> " . esc_html__('Do not optimize images smaller than this (in bytes)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "skip images smaller than: " . ewww_image_optimizer_get_option('ewww_image_optimizer_skip_size') . " bytes" );
				$output[] = "<tr><th><label for='ewww_image_optimizer_skip_png_size'>" . esc_html__('Skip Large PNG Images', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='text' id='ewww_image_optimizer_skip_png_size' name='ewww_image_optimizer_skip_png_size' size='8' value='" . ewww_image_optimizer_get_option('ewww_image_optimizer_skip_png_size') . "'> " . esc_html__('Do not optimize PNG images larger than this (in bytes)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				ewwwio_debug_message( "skip PNG images larger than: " . ewww_image_optimizer_get_option('ewww_image_optimizer_skip_png_size') . " bytes" );
				$output[] = "<tr><th><label for='ewww_image_optimizer_lossy_skip_full'>" . esc_html__('Exclude full-size images from lossy optimization', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_lossy_skip_full' name='ewww_image_optimizer_lossy_skip_full' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_lossy_skip_full') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
				ewwwio_debug_message( "exclude originals from lossy: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_lossy_skip_full') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_metadata_skip_full'>" . esc_html__('Exclude full-size images from metadata removal', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_metadata_skip_full' name='ewww_image_optimizer_metadata_skip_full' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_metadata_skip_full') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
				ewwwio_debug_message( "exclude originals from metadata removal: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_metadata_skip_full') == TRUE ? "on" : "off" ) );
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_skip_bundle'>" . esc_html__('Use System Paths', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_skip_bundle' name='ewww_image_optimizer_skip_bundle' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_skip_bundle') == TRUE ? "checked='true'" : "" ) . " /> " . sprintf(esc_html__('If you have already installed the utilities in a system location, such as %s or %s, use this to force the plugin to use those versions and skip the auto-installers.', EWWW_IMAGE_OPTIMIZER_DOMAIN), '/usr/local/bin', '/usr/bin') . "</td></tr>\n";
				ewwwio_debug_message( "use system binaries: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_skip_bundle') == TRUE ? "yes" : "no" ) );
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_disable_jpegtran'>" . esc_html__('disable', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " jpegtran</label></th><td><input type='checkbox' id='ewww_image_optimizer_disable_jpegtran' name='ewww_image_optimizer_disable_jpegtran' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_jpegtran') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
				ewwwio_debug_message( "jpegtran disabled: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_jpegtran') == TRUE ? "yes" : "no" ) );
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_disable_optipng'>" . esc_html__('disable', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " optipng</label></th><td><input type='checkbox' id='ewww_image_optimizer_disable_optipng' name='ewww_image_optimizer_disable_optipng' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
				ewwwio_debug_message( "optipng disabled: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng') == TRUE ? "yes" : "no" ) );
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_disable_pngout'>" . esc_html__('disable', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " pngout</label></th><td><input type='checkbox' id='ewww_image_optimizer_disable_pngout' name='ewww_image_optimizer_disable_pngout' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout') == TRUE  ? "checked='true'" : "" ) . " /></td><tr>\n";
				ewwwio_debug_message( "pngout disabled: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout') == TRUE ? "yes" : "no" ) );
				$output[] = "<tr class='nocloud'><th><label for='ewww_image_optimizer_disable_gifsicle'>" . esc_html__('disable', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " gifsicle</label></th><td><input type='checkbox' id='ewww_image_optimizer_disable_gifsicle' name='ewww_image_optimizer_disable_gifsicle' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_gifsicle') == TRUE ? "checked='true'" : "" ) . " /></td></tr>\n";
				ewwwio_debug_message( "gifsicle disabled: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_gifsicle') == TRUE ? "yes" : "no" ) );
			$output[] = "</table>\n</div>\n";
			$output[] = "<div id='ewww-conversion-settings'>\n";
			$output[] = "<p>" . esc_html__('Conversion is only available for images in the Media Library (except WebP). By default, all images have a link available in the Media Library for one-time conversion. Turning on individual conversion operations below will enable conversion filters any time an image is uploaded or modified.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "<br />\n" .
				"<b>" . esc_html__('NOTE:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> " . esc_html__('The plugin will attempt to update image locations for any posts that contain the images. You may still need to manually update locations/urls for converted images.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "\n" .
			"</p>\n";
			$output[] = "<table class='form-table'>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_disable_convert_links'>" . esc_html__('Hide Conversion Links', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label</th><td><input type='checkbox' id='ewww_image_optimizer_disable_convert_links' name='ewww_image_optimizer_disable_convert_links' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_disable_convert_links') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('Site or Network admins can use this to prevent other users from using the conversion links in the Media Library which bypass the settings below.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_delete_originals'>" . esc_html__('Delete originals', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><input type='checkbox' id='ewww_image_optimizer_delete_originals' name='ewww_image_optimizer_delete_originals' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_delete_originals') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('This will remove the original image from the server after a successful conversion.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</td></tr>\n";
				$output[] = "<tr><th><label for='ewww_image_optimizer_webp'>" . esc_html__('JPG/PNG to WebP', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_webp' name='ewww_image_optimizer_webp' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_webp') == TRUE ? "checked='true'" : "" ) . " /> <b>" . esc_html__('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . '</b> ' . esc_html__('JPG to WebP conversion is lossy, but quality loss is minimal. PNG to WebP conversion is lossless.', EWWW_IMAGE_OPTIMIZER_DOMAIN) .  "</span>\n" .
				"<p class='description'>" . esc_html__('Originals are never deleted, and WebP images should only be served to supported browsers.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . " <a href='#webp-rewrite'>" .  esc_html__('You can use the rewrite rules below to serve WebP images with Apache.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a></td></tr>\n";
				ewwwio_debug_message( "webp conversion: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_webp') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_webp_for_cdn'>" . esc_html__('Alternative WebP Rewriting', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_webp_for_cdn' name='ewww_image_optimizer_webp_for_cdn' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_webp_for_cdn') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('Uses output buffering and libxml functionality from PHP. Use this if the Apache rewrite rules do not work, or if your images are served from a CDN.', EWWW_IMAGE_OPTIMIZER_DOMAIN) .  "</span></td></tr>";
				ewwwio_debug_message( "alt webp rewriting: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_webp_for_cdn') == TRUE ? "on" : "off" ) );
//				$output[] = "<tr><th><label for='ewww_image_optimizer_webp_cdn_path'>" . esc_html__('WebP CDN URL', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_webp_cdn_path' name='ewww_image_optimizer_webp_cdn_path' value='true' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_webp_for_cdn') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('Uses output buffering and libxml functionality from PHP. Use this if the Apache rewrite rules do not work, or if your images are served from a CDN.', EWWW_IMAGE_OPTIMIZER_DOMAIN) .  "</span></td></tr>";
				$output[] = "<tr><th><label for='ewww_image_optimizer_jpg_to_png'>" . sprintf(esc_html__('enable %s to %s conversion', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'JPG', 'PNG') . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_jpg_to_png' name='ewww_image_optimizer_jpg_to_png' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_to_png') == TRUE ? "checked='true'" : "" ) . " /> <b>" . esc_html__('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> " . esc_html__('Removes metadata and increases cpu usage dramatically.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>\n" .
				"<p class='description'>" . esc_html__('PNG is generally much better than JPG for logos and other images with a limited range of colors. Checking this option will slow down JPG processing significantly, and you may want to enable it only temporarily.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></td></tr>\n";
				ewwwio_debug_message( "jpg2png: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_jpg_to_png') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_png_to_jpg'>" . sprintf(esc_html__('enable %s to %s conversion', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'PNG', 'JPG') . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_png_to_jpg' name='ewww_image_optimizer_png_to_jpg' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_png_to_jpg') == TRUE ? "checked='true'" : "" ) . " /> <b>" . esc_html__('WARNING:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b> " . esc_html__('This is not a lossless conversion.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>\n" .
				"<p class='description'>" . esc_html__('JPG is generally much better than PNG for photographic use because it compresses the image and discards data. PNGs with transparency are not converted by default.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>\n" .
				"<span><label for='ewww_image_optimizer_jpg_background'> " . esc_html__('JPG background color:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label> #<input type='text' id='ewww_image_optimizer_jpg_background' name='ewww_image_optimizer_jpg_background' size='6' value='" . ewww_image_optimizer_jpg_background() . "' /> <span style='padding-left: 12px; font-size: 12px; border: solid 1px #555555; background-color: #" . ewww_image_optimizer_jpg_background() . "'>&nbsp;</span> " . esc_html__('HEX format (#123def)', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ".</span>\n" .
				"<p class='description'>" . esc_html__('Background color is used only if the PNG has transparency. Leave this value blank to skip PNGs with transparency.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>\n" .
				"<span><label for='ewww_image_optimizer_jpg_quality'>" . esc_html__('JPG quality level:', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</label> <input type='text' id='ewww_image_optimizer_jpg_quality' name='ewww_image_optimizer_jpg_quality' class='small-text' value='" . ewww_image_optimizer_jpg_quality() . "' /> " . esc_html__('Valid values are 1-100.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>\n" .
				"<p class='description'>" . esc_html__('If JPG quality is blank, the plugin will attempt to set the optimal quality level or default to 92. Remember, this is a lossy conversion, so you are losing pixels, and it is not recommended to actually set the level here unless you want noticable loss of image quality.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></td></tr>\n";
				ewwwio_debug_message( "png2jpg: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_png_to_jpg') == TRUE ? "on" : "off" ) );
				$output[] = "<tr><th><label for='ewww_image_optimizer_gif_to_png'>" . sprintf(esc_html__('enable %s to %s conversion', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'GIF', 'PNG') . "</label></th><td><span><input type='checkbox' id='ewww_image_optimizer_gif_to_png' name='ewww_image_optimizer_gif_to_png' " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_gif_to_png') == TRUE ? "checked='true'" : "" ) . " /> " . esc_html__('No warnings here, just do it.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</span>\n" .
				"<p class='description'> " . esc_html__('PNG is generally better than GIF, but animated images cannot be converted.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></td></tr>\n";
				ewwwio_debug_message( "gif2png: " . ( ewww_image_optimizer_get_option('ewww_image_optimizer_gif_to_png') == TRUE ? "on" : "off" ) );
			$output[] = "</table>\n</div>\n";
			$output[] = "<p class='submit'><input type='submit' class='button-primary' value='" . esc_attr__('Save Changes', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "' /></p>\n";
		$output[] = "</form>\n";
		if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp' ) && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp_for_cdn' ) ) {
		$output[] = "<form id='ewww-webp-rewrite'>\n";
			$output[] = "<p>" . esc_html__('There are many ways to serve WebP images to visitors with supported browsers. You may choose any you wish, but it is recommended to serve them with an .htaccess file using mod_rewrite and mod_headers. The plugin can insert the rules for you if the file is writable, or you can edit .htaccess yourself.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>\n";
			if ( ! ewww_image_optimizer_webp_rewrite_verify() ) {
				$output[] = "<img id='webp-image' src='" . plugins_url('/images/test.png', __FILE__) . "' style='float: right; padding: 0 0 10px 10px;'>\n" .
				"<p id='ewww-webp-rewrite-status'><b>" . esc_html__('Rules verified successfully', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</b></p>\n";
				ewwwio_debug_message( 'webp .htaccess rewriting enabled' );
			} else {
				$output[] = "<pre id='webp-rewrite-rules' style='background: white; font-color: black; border: 1px solid black; clear: both; padding: 10px;'>\n" .
					"&lt;IfModule mod_rewrite.c&gt;\n" .
					"RewriteEngine On\n" .
					"RewriteCond %{HTTP_ACCEPT} image/webp\n" .
					"RewriteCond %{REQUEST_FILENAME} (.*)\.(jpe?g|png)$\n" .
					"RewriteCond %{REQUEST_FILENAME}\.webp -f\n" .
					"RewriteRule (.+)\.(jpe?g|png)$ %{REQUEST_FILENAME}.webp [T=image/webp,E=accept:1]\n" .
					"&lt;/IfModule&gt;\n" .
					"&lt;IfModule mod_headers.c&gt;\n" .
					"Header append Vary Accept env=REDIRECT_accept\n" .
					"&lt;/IfModule&gt;\n" .
					"AddType image/webp .webp</pre>\n" .
					"<img id='webp-image' src='" . plugins_url('/images/test.png', __FILE__) . "' style='float: right; padding-left: 10px;'>\n" .
					"<p id='ewww-webp-rewrite-status'>" . esc_html__('The image to the right will display a WebP image with WEBP in white text, if your site is serving WebP images and your browser supports WebP.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p>\n" .
					"<button type='submit' class='button-secondary action'>" . esc_html__('Insert Rewrite Rules', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</button>\n";
				ewwwio_debug_message( 'webp .htaccess rules not detected' );

			}
		$output[] = "</form>\n";
		}
		$output[] = "</div><!-- end container left -->\n";
		$output[] = "<div id='ewww-container-right' style='border: 1px solid #e5e5e5; float: right; margin-left: -215px; padding: 0em 1.5em 1em; background-color: #fff; box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04); display: inline-block; width: 174px;'>\n" .
			"<h2>" . esc_html__( 'Support EWWW I.O.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</h2>\n" .
			"<p>" . esc_html__( 'Would you like to help support development of this plugin?', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</p>\n" .
			"<p><a href='https://translate.wordpress.org/projects/wp-plugins/ewww-image-optimizer/'>" . esc_html__( 'Help translate EWWW I.O.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a></p>\n" .
			"<p><a href='https://wordpress.org/support/view/plugin-reviews/ewww-image-optimizer#postform'>" . esc_html__( 'Write a review.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a></p>\n" .
			"<p>" . wp_kses( sprintf( __( 'Contribute directly via %s.',  EWWW_IMAGE_OPTIMIZER_DOMAIN ), "<a href='https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&amp;hosted_button_id=MKMQKCBFFG3WW'>Paypal</a>" ), array( 'a' => array( 'href' => array() ) ) ) . "</p>\n" .
//			"<b>" . __( 'OR', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</b><br />\n" .
			"<p>" . esc_html__( 'Use any of these referral links to show your appreciation:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</p>\n" .
			"<p><b>" . esc_html__( 'Web Hosting:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</b><br>\n" .
				"<a href='https://partners.a2hosting.com/solutions.php?id=5959&url=638'>A2 Hosting:</a> " . esc_html_x( 'with automatic EWWW IO setup', 'A2 Hosting:', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "<br>\n" .
				"<a href='http://www.bluehost.com/track/nosilver4u'>Bluehost</a><br>\n" .
				"<a href='http://www.dreamhost.com/r.cgi?132143'>Dreamhost</a><!-- <br>\n" .
				"<a href='http://www.liquidweb.com/?RID=nosilver4u'>liquidweb</a><br>\n" .
				"<a href='http://www.stormondemand.com/?RID=nosilver4u'>Storm on Demand</a>-->\n" .
			"</p>\n" .
			"<p><b>" . esc_html_x( 'VPS:', 'abbreviation for Virtual Private Server', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</b><br>\n" .
				"<a href='https://www.digitalocean.com/?refcode=89ef0197ec7e'>DigitalOcean</a><br>\n" .
				"<a href='https://clientarea.ramnode.com/aff.php?aff=1469'>RamNode</a><br>\n" .
	//			"<a href='http://www.vultr.com/?ref=6814210'>VULTR</a>\n" .
			"</p>\n" .
			"<p><b>" . esc_html_x( 'CDN:', 'abbreviation for Content Delivery Network', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</b><br><a target='_blank' href='http://tracking.maxcdn.com/c/91625/36539/378'>" . esc_html__( 'Add MaxCDN to increase website speeds dramatically! Sign Up Now and Save 25%.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a> " . esc_html__( 'Integrate MaxCDN within Wordpress using the W3 Total Cache plugin.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</p>\n" .
		"</div>\n" .
	"</div>\n";
	ewwwio_debug_message( 'max_execution_time: ' . ini_get('max_execution_time') );
	echo apply_filters( 'ewww_image_optimizer_settings', $output );
	if ( ewww_image_optimizer_get_option ( 'ewww_image_optimizer_debug' ) ) {
		?>
<script type="text/javascript">
    function selectText(containerid) {
        if (document.selection) {
            var range = document.body.createTextRange();
            range.moveToElementText(document.getElementById(containerid));
            range.select();
        } else if (window.getSelection) {
            var range = document.createRange();
            range.selectNode(document.getElementById(containerid));
            window.getSelection().addRange(range);
        }
    }
</script>
		<?php
		global $ewww_debug;
		echo '<p style="clear:both"><b>' . esc_html__( 'Debugging Information', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . ':</b> <button onclick="selectText(' . "'ewww-debug-info'" . ')">' . esc_html__( 'Select All', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</button></p>';
		echo '<div id="ewww-debug-info" style="background:#ffff99;margin-left:-20px;padding:10px" contenteditable="true">' . $ewww_debug . '</div>';
	}
	ewwwio_memory( __FUNCTION__ );
}

function ewww_image_optimizer_filter_settings_page($input) {
	$output = '';
	foreach ($input as $line) {
		if ( EWWW_IMAGE_OPTIMIZER_CLOUD && preg_match( "/class='nocloud'/", $line ) ) {
			continue;
		} else {
			$output .= $line;
		}
	}
	ewwwio_memory( __FUNCTION__ );
	return $output;
}

function ewwwio_debug_message( $message ) {
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_debug' ) ) {
		global $ewww_debug;
		$ewww_debug .= "$message<br>";
	}
}

function ewwwio_debug_backtrace() {
	if ( defined( 'DEBUG_BACKTRACE_IGNORE_ARGS' ) ) {
		$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
	} else {
		$backtrace = debug_backtrace( false );
	}
	array_shift( $backtrace );
	array_shift( $backtrace );
	return maybe_serialize( $backtrace );
}

function ewww_image_optimizer_dynamic_image_debug() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	echo "<div class='wrap'><h1>" . esc_html__( 'Dynamic Image Debugging', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</h1>";
	global $wpdb;
	$debug_images = $wpdb->get_results( "SELECT path,updates,updated,trace FROM $wpdb->ewwwio_images WHERE trace IS NOT NULL ORDER BY updated DESC LIMIT 100" );
	if ( count( $debug_images ) != 0 ) {
			foreach ( $debug_images as $image ) {
				$trace = unserialize( $image->trace );
				echo '<p><b>' . esc_html__( 'File path', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $image->path . '</b><br>';
				echo esc_html__( 'Number of attempted optimizations', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $image->updates . '<br>';
				echo esc_html__( 'Last attempted', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ': ' . $image->updated . '<br>';
				echo esc_html__( 'PHP trace', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ':<br>';
				$i = 0;
				foreach ( $trace as $function ) {
					if ( ! empty( $function['file'] ) && ! empty( $function['line'] ) ) {
						echo "#$i {$function['function']}() called at {$function['file']}:{$function['line']}<br>";
					} else {
						echo "#$i {$function['function']}() called<br>";
					}
					$i++;
				}
				echo '</p>';
			}
	}
	echo '</div>';
	return;
}

function ewwwio_memory_output() {
	if ( WP_DEBUG ) {
		global $ewww_memory;
		$timestamp = date('y-m-d h:i:s.u') . "  ";
		if (!file_exists(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'memory.log'))
			touch(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'memory.log');
		file_put_contents(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'memory.log', $timestamp . $ewww_memory, FILE_APPEND);
	}
}
?>
