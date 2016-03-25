<?php
/**
 * Integrate image optimizers into WordPress.
 * @version 2.6.1
 * @package EWWW_Image_Optimizer
 */
/*
Plugin Name: EWWW Image Optimizer
Plugin URI: https://wordpress.org/extend/plugins/ewww-image-optimizer/
Description: Reduce file sizes for images within WordPress including NextGEN Gallery and GRAND FlAGallery. Uses jpegtran, optipng/pngout, and gifsicle.
Author: Shane Bishop
Text Domain: ewww-image-optimizer
Version: 2.6.1
Author URI: https://ewww.io/
License: GPLv3
*/

// Constants
define( 'EWWW_IMAGE_OPTIMIZER_DOMAIN', 'ewww-image-optimizer' );
// this is the full path of the plugin file itself
define( 'EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE', __FILE__ );
// this is the path of the plugin file relative to the plugins/ folder
define( 'EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL', 'ewww-image-optimizer/ewww-image-optimizer.php' );
// the folder where we install optimization tools
define( 'EWWW_IMAGE_OPTIMIZER_TOOL_PATH', WP_CONTENT_DIR . '/ewww/' );
// this is the full system path to the plugin folder
define( 'EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
// this is the full system path to the bundled binaries
define( 'EWWW_IMAGE_OPTIMIZER_BINARY_PATH', plugin_dir_path( __FILE__ ) . 'binaries/' );
// this is the full system path to the plugin images for testing
define( 'EWWW_IMAGE_OPTIMIZER_IMAGES_PATH', plugin_dir_path( __FILE__ ) . 'images/' );

require_once( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'common.php' );

// Hooks
add_action( 'admin_action_ewww_image_optimizer_install_pngout', 'ewww_image_optimizer_install_pngout' );

// check to see if the cloud constant is defined (which would mean we've already run init) and then set it properly if not
function ewww_image_optimizer_cloud_init() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_CLOUD' ) && ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_jpg' ) && ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_png' ) && ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_gif' ) ) {
		define( 'EWWW_IMAGE_OPTIMIZER_CLOUD', TRUE );
	} elseif ( ! defined( 'EWWW_IMAGE_OPTIMIZER_CLOUD' ) ) {
		define( 'EWWW_IMAGE_OPTIMIZER_CLOUD', FALSE );
	}
	ewwwio_memory( __FUNCTION__ );
}

function ewww_image_optimizer_exec_init() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
		// set the binary-specific network settings if they have been POSTed
		if ( isset( $_POST['ewww_image_optimizer_delay'] ) ) {
			if ( empty( $_POST['ewww_image_optimizer_skip_bundle'] ) ) $_POST['ewww_image_optimizer_skip_bundle'] = '';
			update_site_option( 'ewww_image_optimizer_skip_bundle', $_POST['ewww_image_optimizer_skip_bundle'] );
			if ( ! empty( $_POST['ewww_image_optimizer_optipng_level'] ) ) {
				update_site_option( 'ewww_image_optimizer_optipng_level', $_POST['ewww_image_optimizer_optipng_level'] );
				update_site_option( 'ewww_image_optimizer_pngout_level', $_POST['ewww_image_optimizer_pngout_level'] );
			}
			if ( empty( $_POST['ewww_image_optimizer_disable_jpegtran'] ) ) $_POST['ewww_image_optimizer_disable_jpegtran'] = '';
			update_site_option( 'ewww_image_optimizer_disable_jpegtran', $_POST['ewww_image_optimizer_disable_jpegtran'] );
			if (empty($_POST['ewww_image_optimizer_disable_optipng'])) $_POST['ewww_image_optimizer_disable_optipng'] = '';
			update_site_option('ewww_image_optimizer_disable_optipng', $_POST['ewww_image_optimizer_disable_optipng']);
			if (empty($_POST['ewww_image_optimizer_disable_gifsicle'])) $_POST['ewww_image_optimizer_disable_gifsicle'] = '';
			update_site_option('ewww_image_optimizer_disable_gifsicle', $_POST['ewww_image_optimizer_disable_gifsicle']);
			if (empty($_POST['ewww_image_optimizer_disable_pngout'])) $_POST['ewww_image_optimizer_disable_pngout'] = '';
			update_site_option('ewww_image_optimizer_disable_pngout', $_POST['ewww_image_optimizer_disable_pngout']);
		}
	}
	// register all the binary-specific EWWW IO settings
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_skip_bundle');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_optipng_level');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_pngout_level');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_jpegtran');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_optipng');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_gifsicle');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_pngout');
	if ( defined( 'WPE_PLUGIN_VERSION' ) ) {
		add_action( 'network_admin_notices', 'ewww_image_optimizer_notice_wpengine' );
		add_action( 'admin_notices', 'ewww_image_optimizer_notice_wpengine' );
	}
	// If cloud is fully enabled, we're going to skip all the checks related to the bundled tools
	if ( EWWW_IMAGE_OPTIMIZER_CLOUD ) {
		ewwwio_debug_message( 'cloud options enabled, shutting off binaries' );
		ewww_image_optimizer_disable_tools();
	// Check if this is an unsupported OS (not Linux or Mac OSX or FreeBSD or Windows or SunOS)
	} elseif ( 'Linux' != PHP_OS && 'Darwin' != PHP_OS && 'FreeBSD' != PHP_OS && 'WINNT' != PHP_OS && 'SunOS' != PHP_OS ) {
		// call the function to display a notice
		add_action('network_admin_notices', 'ewww_image_optimizer_notice_os');
		add_action('admin_notices', 'ewww_image_optimizer_notice_os');
		// turn off all the tools
		ewwwio_debug_message( 'unsupported OS, disabling tools: ' . PHP_OS );
		ewww_image_optimizer_disable_tools();
	} else {
		add_action( 'load-upload.php', 'ewww_image_optimizer_tool_init', 9 );
		add_action( 'load-media-new.php', 'ewww_image_optimizer_tool_init' );
		add_action( 'load-media_page_ewww-image-optimizer-bulk', 'ewww_image_optimizer_tool_init' );
		add_action( 'load-settings_page_ewww-image-optimizer/ewww-image-optimizer', 'ewww_image_optimizer_tool_init' );
		add_action( 'load-plugins.php', 'ewww_image_optimizer_tool_init' );
		add_action( 'load-ims_gallery_page_ewww-ims-optimize', 'ewww_image_optimizer_tool_init' );
		add_action( 'load-media_page_ewww-image-optimizer-unoptimized', 'ewww_image_optimizer_tool_init' );
		add_action( 'load-flagallery_page_flag-manage-gallery', 'ewww_image_optimizer_tool_init' );
//		add_action( 'load-', 'ewww_image_optimizer_tool_init' );
	} 
	ewwwio_memory( __FUNCTION__ );
}

// check for binary installation and availability
function ewww_image_optimizer_tool_init() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// make sure the bundled tools are installed
	if( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_skip_bundle' ) ) {
		ewww_image_optimizer_install_tools();
	}
	// check for optimization utilities and register a notice if something is missing
	add_action( 'network_admin_notices', 'ewww_image_optimizer_notice_utils' );
	add_action( 'admin_notices', 'ewww_image_optimizer_notice_utils' );
	if ( EWWW_IMAGE_OPTIMIZER_CLOUD ) {
		ewwwio_debug_message( 'cloud options enabled, shutting off binaries' );
		ewww_image_optimizer_disable_tools();
	}
//	ewww_image_optimizer_debug_log();
}

// set some default option values
function ewww_image_optimizer_set_defaults() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	add_site_option( 'ewww_image_optimizer_disable_pngout', TRUE );
	add_site_option( 'ewww_image_optimizer_optipng_level', 2 );
	add_site_option( 'ewww_image_optimizer_pngout_level', 2 );
	add_site_option( 'ewww_image_optimizer_jpegtran_copy', TRUE );
}

// tells the user they are on an unsupported operating system
function ewww_image_optimizer_notice_os() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	echo "<div id='ewww-image-optimizer-warning-os' class='error'><p><strong>" . esc_html__('EWWW Image Optimizer is supported on Linux, FreeBSD, Mac OSX, and Windows', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ".</strong> " . sprintf(esc_html__('Unfortunately, the EWWW Image Optimizer plugin does not work with %s', EWWW_IMAGE_OPTIMIZER_DOMAIN), htmlentities(PHP_OS)) . ".</p></div>";
}

// inform the user that only ewww-image-optimizer-cloud is permitted on WP Engine
function ewww_image_optimizer_notice_wpengine() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	echo "<div id='ewww-image-optimizer-warning-wpengine' class='error'><p>" . esc_html__('The regular version of the EWWW Image Optimizer plugin is not permitted on WP Engine sites. However, the cloud version has been approved by WP Engine. Please deactivate EWWW Image Optimizer and install EWWW Image Optimizer Cloud to optimize your images.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></div>";
}

// generates the source and destination paths for the executables that we bundle with the plugin based on the operating system
function ewww_image_optimizer_install_paths () {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if (PHP_OS == 'WINNT') {
		$gifsicle_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'gifsicle.exe';
		$optipng_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'optipng.exe';
		$jpegtran_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'jpegtran.exe';
		$pngquant_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'pngquant.exe';
		$webp_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'cwebp.exe';
		$gifsicle_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle.exe';
		$optipng_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng.exe';
		$jpegtran_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran.exe';
		$pngquant_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant.exe';
		$webp_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp.exe';
	}
	if (PHP_OS == 'Darwin') {
		$gifsicle_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'gifsicle-mac';
		$optipng_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'optipng-mac';
		$jpegtran_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'jpegtran-mac';
		$pngquant_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'pngquant-mac';
		$webp_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'cwebp-mac9';
		$gifsicle_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle';
		$optipng_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng';
		$jpegtran_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran';
		$pngquant_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant';
		$webp_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp';
	}
	if (PHP_OS == 'SunOS') {
		$gifsicle_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'gifsicle-sol';
		$optipng_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'optipng-sol';
		$jpegtran_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'jpegtran-sol';
		$pngquant_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'pngquant-sol';
		$webp_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'cwebp-sol';
		$gifsicle_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle';
		$optipng_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng';
		$jpegtran_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran';
		$pngquant_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant';
		$webp_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp';
	}
	if (PHP_OS == 'FreeBSD') {
		$arch_type = php_uname('m');
		ewwwio_debug_message( "CPU architecture: $arch_type" );
		$gifsicle_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'gifsicle-fbsd';
		$optipng_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'optipng-fbsd';
	/*	if ($arch_type == 'amd64') {
			$jpegtran_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'jpegtran-fbsd64';
		} else {*/
			$jpegtran_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'jpegtran-fbsd';
	//	}
		$pngquant_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'pngquant-fbsd';
	/*	if ($arch_type == 'amd64') {
			$webp_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'cwebp-fbsd64';
		} else {*/
			$webp_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'cwebp-fbsd';
	//	}
		$gifsicle_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle';
		$optipng_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng';
		$jpegtran_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran';
		$pngquant_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant';
		$webp_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp';
	}
	if (PHP_OS == 'Linux') {
		$arch_type = php_uname('m');
		ewwwio_debug_message( "CPU architecture: $arch_type" );
		$gifsicle_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'gifsicle-linux';
		$optipng_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'optipng-linux';
		$jpegtran_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'jpegtran-linux';
		$pngquant_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'pngquant-linux';
		$webp_src = EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'cwebp-linux';
		$gifsicle_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle';
		$optipng_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng';
		$jpegtran_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran';
		$pngquant_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant';
		$webp_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp';
	}
	ewwwio_debug_message( "generated paths:<br>$jpegtran_src<br>$optipng_src<br>$gifsicle_src<br>$pngquant_src<br>$webp_src<br>$jpegtran_dst<br>$optipng_dst<br>$gifsicle_dst<br>$pngquant_dst<br>$webp_dst" );
	ewwwio_memory( __FUNCTION__ );
	return array( $jpegtran_src, $optipng_src, $gifsicle_src, $pngquant_src, $webp_src, $jpegtran_dst, $optipng_dst, $gifsicle_dst, $pngquant_dst, $webp_dst );
}

function ewww_image_optimizer_check_permissions( $file, $minimum ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$perms = fileperms( $file );
	ewwwio_debug_message( "permissions for $file: " . substr( sprintf( '%o', $perms ), -4 ) );
	$perms_pass = true;
	if ( ( $perms & 0x8000 ) != 0x8000 ) {
		$perms_pass = false;
	}
	$minimum = str_split( $minimum );
	if ( $minimum[0] == 'r' && ! ( $perms & 0x0100 ) ) {
		$perms_pass = false;
	}
	if ( $minimum[1] == 'w' && ! ( $perms & 0x0080 ) ) {
		$perms_pass = false;
	}
	if ( $minimum[2] == 'x' && ! ( $perms & 0x0040 ) ) {
		$perms_pass = false;
	}
	if ( $minimum[3] == 'r' && ! ( $perms & 0x0020 ) ) {
		$perms_pass = false;
	}
	if ( $minimum[4] == 'w' && ! ( $perms & 0x0010 ) ) {
		$perms_pass = false;
	}
	if ( $minimum[5] == 'x' && ! ( $perms & 0x0008 ) ) {
		$perms_pass = false;
	}
	if ( $minimum[6] == 'r' && ! ( $perms & 0x0004 ) ) {
		$perms_pass = false;
	}
	if ( $minimum[7] == 'w' && ! ( $perms & 0x0002 ) ) {
		$perms_pass = false;
	}
	if ( $minimum[8] == 'x' && ! ( $perms & 0x0001 ) ) {
		$perms_pass = false;
	}
	if ( $perms_pass ) {
		ewwwio_debug_message( 'permissions ok' );
	} else {
		ewwwio_debug_message( 'permissions insufficient' );
	}
	return $perms_pass;
}

function ewww_image_optimizer_tool_folder_notice() {
	echo "<div id='ewww-image-optimizer-warning-tool-install' class='error'><p><strong>" . esc_html__('EWWW Image Optimizer could not create the tool folder', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ": " . htmlentities(EWWW_IMAGE_OPTIMIZER_TOOL_PATH) . ".</strong> " . esc_html__('Please adjust permissions or create the folder', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ".</p></div>";
}

function ewww_image_optimizer_tool_folder_permissions_notice() {
	if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
		$settings_page = 'settings.php?page=' . EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL;
	} else {
		$settings_page = 'options-general.php?page=' . EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL;
	}
	echo "<div id='ewww-image-optimizer-warning-tool-install' class='error'><p><strong>" . sprintf( esc_html__( 'EWWW Image Optimizer could not install tools in %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), htmlentities( EWWW_IMAGE_OPTIMIZER_TOOL_PATH ) ) . ".</strong> " . sprintf( esc_html__( 'Please adjust permissions or create the folder. If you have installed the tools elsewhere on your system, check the option to %s.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), esc_html__( 'Use System Paths', EWWW_IMAGE_OPTIMIZER_DOMAIN ) ) . " " . wp_kses( sprintf( __( 'For more details, visit the %1$s or the %2$s.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), "<a href='$settings_page'>" . __('Settings Page', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a>", "<a href='https://wordpress.org/extend/plugins/ewww-image-optimizer/installation/'>" . __('Installation Instructions', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a>" ), array( 'a' => array( 'href' => array() ) ) ) . '</p></div>';
}

// installs the executables that are bundled with the plugin
function ewww_image_optimizer_install_tools () {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_debug_message( "Checking/Installing tools in " . EWWW_IMAGE_OPTIMIZER_TOOL_PATH );
	$toolfail = false;
	if ( ! is_dir( EWWW_IMAGE_OPTIMIZER_TOOL_PATH ) && is_writable( WP_CONTENT_DIR ) ) {
		ewwwio_debug_message( 'folder does not exist, creating...' );
		if ( ! mkdir( EWWW_IMAGE_OPTIMIZER_TOOL_PATH ) ) {
			ewwwio_debug_message( 'could not create folder' );
			return;
		}
	} elseif ( is_dir( EWWW_IMAGE_OPTIMIZER_TOOL_PATH ) ) {
		if ( ! is_writable( EWWW_IMAGE_OPTIMIZER_TOOL_PATH ) ) {
			ewwwio_debug_message( 'wp-content/ewww is not writable, not installing anything' );
			return;
		}
		$ewww_perms = substr( sprintf( '%o', fileperms( EWWW_IMAGE_OPTIMIZER_TOOL_PATH ) ), -4 );
		ewwwio_debug_message( "wp-content/ewww permissions: $ewww_perms" );
	} else {
		ewwwio_debug_message( 'wp-content is not writable, and the ewww folder does not exist' );
		return;
	}
	list ( $jpegtran_src, $optipng_src, $gifsicle_src, $pngquant_src, $webp_src, $jpegtran_dst, $optipng_dst, $gifsicle_dst, $pngquant_dst, $webp_dst ) = ewww_image_optimizer_install_paths();
	if ( ! file_exists( $jpegtran_dst ) || filesize( $jpegtran_dst ) != filesize( $jpegtran_src ) ) {
		ewwwio_debug_message( 'jpegtran not found or different size, installing' );
		if ( ! copy( $jpegtran_src, $jpegtran_dst ) ) {
			$toolfail = true;
			ewwwio_debug_message( 'could not copy jpegtran' );
		}
	}
	// install 32-bit jpegtran at jpegtran-alt for some weird 64-bit hosts
	$arch_type = php_uname( 'm' );
/*	if ( PHP_OS == 'Linux' && $arch_type == 'x86_64' ) {
		ewwwio_debug_message( '64-bit linux detected while installing tools' );
		$jpegtran32_src = substr( $jpegtran_src, 0, -2 );
		$jpegtran32_dst = $jpegtran_dst . '-alt';
		
		if ( ! file_exists( $jpegtran32_dst ) || ( ewww_image_optimizer_md5check( $jpegtran32_dst ) && filesize( $jpegtran32_dst ) != filesize( $jpegtran32_src ) ) ) {
			ewwwio_debug_message( "copying $jpegtran32_src to $jpegtran32_dst" );
			if ( ! copy( $jpegtran32_src, $jpegtran32_dst ) ) {
				// this isn't a fatal error, besides we'll see it in the debug if needed
				ewwwio_debug_message( 'could not copy 32-bit jpegtran to jpegtran-alt' );
			}
			if ( ! ewww_image_optimizer_check_permissions( $jpegtran32_dst, 'rwxr-xr-x' ) ) {
				if ( ! chmod( $jpegtran32_dst, 0755 ) ) {
					ewwwio_debug_message( 'could not set jpegtran-alt permissions' );
				}
			}
		}
	}*/
	if ( ! file_exists( $gifsicle_dst ) || filesize( $gifsicle_dst ) != filesize( $gifsicle_src ) ) {
		ewwwio_debug_message( 'gifsicle not found or different size, installing' );
		if ( ! copy( $gifsicle_src, $gifsicle_dst ) ) {
			$toolfail = true;
			ewwwio_debug_message( 'could not copy gifsicle' );
		}
	}
	if (!file_exists($optipng_dst) || filesize($optipng_dst) != filesize($optipng_src)) {
		ewwwio_debug_message( 'optipng not found or different size, installing' );
		if (!copy($optipng_src, $optipng_dst)) {
			$toolfail = true;
			ewwwio_debug_message( 'could not copy optipng' );
		}
	}
	if (!file_exists($pngquant_dst) || filesize($pngquant_dst) != filesize($pngquant_src)) {
		ewwwio_debug_message( 'pngquant not found or different size, installing' );
		if (!copy($pngquant_src, $pngquant_dst)) {
			$toolfail = true;
			ewwwio_debug_message( 'could not copy pngquant' );
		}
	}
	if (!file_exists($webp_dst) || filesize($webp_dst) != filesize($webp_src)) {
		ewwwio_debug_message( 'webp not found or different size, installing' );
		if (!copy($webp_src, $webp_dst)) {
			$toolfail = true;
			ewwwio_debug_message( 'could not copy webp' );
		}
	}
	// install special version of cwebp for Mac OSX 10.8 systems
	if ( PHP_OS == 'Darwin' ) {
		$webp8_dst = $webp_dst . '-alt';
		$webp8_src = str_replace( 'mac9', 'mac8', $webp_src );
		if ( ! file_exists( $webp8_dst ) || ( ewww_image_optimizer_md5check( $webp8_dst ) && filesize( $webp8_dst ) != filesize( $webp8_src ) ) ) {
			ewwwio_debug_message( "copying $webp8_src to $webp8_dst" );
			if ( ! copy( $webp8_src, $webp8_dst ) ) {
				// this isn't a fatal error, besides we'll see it in the debug if needed
				ewwwio_debug_message( 'could not copy OSX 10.8 cwebp to cwebp-alt' );
			}
			if ( ! ewww_image_optimizer_check_permissions( $webp8_dst, 'rwxr-xr-x' ) ) {
				if ( ! chmod( $webp8_dst, 0755 ) ) {
					ewwwio_debug_message( 'could not set cwebp8-alt permissions' );
				}
			}
		}
	}

	// install libjpeg6 version of cwebp for older systems
/*	if ( PHP_OS == 'Linux' ) {
		$webp6_dst = $webp_dst . '-alt';
		$webp6_src = str_replace( 'linux8', 'linux6', $webp_src );
		if ( ! file_exists( $webp6_dst ) || ( ewww_image_optimizer_md5check( $webp6_dst ) && filesize( $webp6_dst ) != filesize( $webp6_src ) ) ) {
			ewwwio_debug_message( "copying $webp6_src to $webp6_dst" );
			if ( ! copy( $webp6_src, $webp6_dst ) ) {
				// this isn't a fatal error, besides we'll see it in the debug if needed
				ewwwio_debug_message( 'could not copy libjpeg6 cwebp to cwebp-alt' );
			}
			if ( file_exists( $webp6_dst ) && ! ewww_image_optimizer_check_permissions( $webp6_dst, 'rwxr-xr-x' ) ) {
				if ( ! chmod( $webp6_dst, 0755 ) ) {
					ewwwio_debug_message( 'could not set cwebp6-alt permissions' );
				}
			}
		}
	}*/

	if ( PHP_OS != 'WINNT' && ! $toolfail ) {
		ewwwio_debug_message( 'Linux/UNIX style OS, checking permissions' );
		if ( ! ewww_image_optimizer_check_permissions( $jpegtran_dst, 'rwxr-xr-x' ) ) {
			if ( ! chmod( $jpegtran_dst, 0755 ) ) {
				$toolfail = true;
				ewwwio_debug_message( 'could not set jpegtran permissions' );
			}
		}
		if ( ! ewww_image_optimizer_check_permissions( $gifsicle_dst, 'rwxr-xr-x' ) ) {
			if (!chmod($gifsicle_dst, 0755)) {
				$toolfail = true;
				ewwwio_debug_message( 'could not set gifsicle permissions' );
			}
		}
		if ( ! ewww_image_optimizer_check_permissions( $optipng_dst, 'rwxr-xr-x' ) ) {
			if (!chmod($optipng_dst, 0755)) {
				$toolfail = true;
				ewwwio_debug_message( 'could not set optipng permissions' );
			}
		}
		if ( ! ewww_image_optimizer_check_permissions( $pngquant_dst, 'rwxr-xr-x' ) ) {
			if (!chmod($pngquant_dst, 0755)) {
				$toolfail = true;
				ewwwio_debug_message( 'could not set pngquant permissions' );
			}
		}
		if ( ! ewww_image_optimizer_check_permissions( $webp_dst, 'rwxr-xr-x' ) ) {
			if (!chmod($webp_dst, 0755)) {
				$toolfail = true;
				ewwwio_debug_message( 'could not set webp permissions' );
			}
		}
	}
	if ( $toolfail ) {
		if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
			// need to include the plugin library for the is_plugin_active function
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
			$settings_page = 'settings.php?page=' . EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL;
		} else {
			$settings_page = 'options-general.php?page=' . EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL;
		}
		echo "<div id='ewww-image-optimizer-warning-tool-install' class='error'><p><strong>" . sprintf( esc_html__( 'EWWW Image Optimizer could not install tools in %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), htmlentities( EWWW_IMAGE_OPTIMIZER_TOOL_PATH ) ) . ".</strong> " . sprintf( esc_html__( 'Please adjust permissions or create the folder. If you have installed the tools elsewhere on your system, check the option to %s.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), esc_html__( 'Use System Paths', EWWW_IMAGE_OPTIMIZER_DOMAIN ) ) . " " . wp_kses( sprintf( __( 'For more details, visit the %1$s or the %2$s.', EWWW_IMAGE_OPTIMIZER_DOMAIN ), "<a href='$settings_page'>" . __( 'Settings Page', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . "</a>", "<a href='https://wordpress.org/extend/plugins/ewww-image-optimizer/installation/'>" . __( 'Installation Instructions', EWWW_IMAGE_OPTIMIZER_DOMAIN ) . '</a>'), array( 'a' => array( 'href' => array() ) ) ) . '</p></div>';
	}
	ewwwio_memory( __FUNCTION__ );
}

// we check for safe mode and exec, then also direct the user where to go if they don't have the tools installed
// this is another function called by hook usually
function ewww_image_optimizer_notice_utils() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// Check if exec is disabled
	if ( ewww_image_optimizer_exec_check() ) {
		//display a warning if exec() is disabled, can't run much of anything without it
		echo "<div id='ewww-image-optimizer-warning-opt-png' class='error'><p>" . esc_html__('EWWW Image Optimizer requires exec(). Your system administrator has disabled this function.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></div>";
		if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_NOEXEC' ) ) {
			define( 'EWWW_IMAGE_OPTIMIZER_NOEXEC', true );
		}
		ewwwio_debug_message( 'exec seems to be disabled' );
		ewww_image_optimizer_disable_tools();
		return;
		// otherwise, query the php settings for safe mode
	} elseif ( ewww_image_optimizer_safemode_check() ) {
		// display a warning to the user
		echo "<div id='ewww-image-optimizer-warning-opt-png' class='error'><p>" . esc_html__('Safe Mode is turned on for PHP. This plugin cannot operate in Safe Mode.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></div>";
		if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_NOEXEC' ) ) {
			define( 'EWWW_IMAGE_OPTIMIZER_NOEXEC', true );
		}
		ewwwio_debug_message( 'safe mode appears to be enabled' );
		ewww_image_optimizer_disable_tools();
		return;
	} else {
		if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_NOEXEC' ) ) {
			define( 'EWWW_IMAGE_OPTIMIZER_NOEXEC', false );
		}
	}

	// set the variables false otherwise
	$skip_jpegtran_check = false;
	$skip_optipng_check = false;
	$skip_gifsicle_check = false;
	$skip_pngout_check = false;
	// except these which are off by default
	$skip_pngquant_check = true;
	$skip_webp_check = true;
	// if the user has disabled a variable, we aren't going to bother checking to see if it is there
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_jpegtran' ) || ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_jpg' ) ) {
		$skip_jpegtran_check = true;
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_optipng' ) || ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_png' ) ) {
		$skip_optipng_check = true;
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_gifsicle' ) || ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_gif' ) ) {
		$skip_gifsicle_check = true;
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_pngout' ) || ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_png' ) ) {
		$skip_pngout_check = true;
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_lossy' ) && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_png' ) ) {
		$skip_pngquant_check = false;
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp' ) && ! ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_jpg' ) && ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_png' ) ) ) {
		$skip_webp_check = false;
	}
	// attempt to retrieve values for utility paths, and store them in the appropriate variables
	$required = ewww_image_optimizer_path_check( ! $skip_jpegtran_check, ! $skip_optipng_check, ! $skip_gifsicle_check, ! $skip_pngout_check, ! $skip_pngquant_check, ! $skip_webp_check );
	// we are going to store our validation results in $missing
	$missing = array();
	// go through each of the required tools
	foreach( $required as $key => $req ) {
		// if the tool wasn't found, add it to the $missing array if we are supposed to check the tool in question
		switch( $key ) {
			case 'JPEGTRAN':
				if ( ! $skip_jpegtran_check && empty( $req ) ) {
					$missing[] = 'jpegtran';
					$req = false;
				}
				if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_' . $key ) ) {
					ewwwio_debug_message( "defining EWWW_IMAGE_OPTIMIZER_$key" );
					define( 'EWWW_IMAGE_OPTIMIZER_' . $key, $req );
				}
				break; 
			case 'OPTIPNG':
				if ( ! $skip_optipng_check && empty( $req ) ) {
					$missing[] = 'optipng';
					$req = false;
				}
				if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_' . $key ) ) {
					ewwwio_debug_message( "defining EWWW_IMAGE_OPTIMIZER_$key" );
					define( 'EWWW_IMAGE_OPTIMIZER_' . $key, $req );
				}
				break;
			case 'GIFSICLE':
				if ( ! $skip_gifsicle_check && empty( $req ) ) {
					$missing[] = 'gifsicle';
					$req = false;
				}
				if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_' . $key ) ) {
					ewwwio_debug_message( "defining EWWW_IMAGE_OPTIMIZER_$key" );
					define( 'EWWW_IMAGE_OPTIMIZER_' . $key, $req );
				}
				break;
			case 'PNGOUT':
				if ( ! $skip_pngout_check && empty( $req ) ) {
					$missing[] = 'pngout';
					$req = false;
				}
				if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_' . $key ) ) {
					ewwwio_debug_message( "defining EWWW_IMAGE_OPTIMIZER_$key" );
					define( 'EWWW_IMAGE_OPTIMIZER_' . $key, $req );
				}
				break;
			case 'PNGQUANT':
				if ( ! $skip_pngquant_check && empty( $req ) ) {
					$missing[] = 'pngquant';
					$req = false;
				}
				if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_' . $key ) ) {
					ewwwio_debug_message( "defining EWWW_IMAGE_OPTIMIZER_$key" );
					define( 'EWWW_IMAGE_OPTIMIZER_' . $key, $req );
				}
				break;
			case 'WEBP':
				if ( ! $skip_webp_check && empty( $req ) ) {
					$missing[] = 'webp';
					$req = false;
				}
				if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_' . $key ) ) {
					ewwwio_debug_message( "defining EWWW_IMAGE_OPTIMIZER_$key" );
					define( 'EWWW_IMAGE_OPTIMIZER_' . $key, $req );
				}
				break;
		}
	}
	// expand the missing utilities list for use in the error message
	$msg = implode( ', ', $missing );
	// if there is a message, display the warning
	if( ! empty( $msg ) ){
		if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
			// need to include the plugin library for the is_plugin_active function
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
			$settings_page = 'settings.php?page=' . EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL;
		} else {
			$settings_page = 'options-general.php?page=' . EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL;
		}
		if ( ! is_dir( EWWW_IMAGE_OPTIMIZER_TOOL_PATH ) ) {
			ewww_image_optimizer_tool_folder_notice();
		} elseif ( ! is_writable( EWWW_IMAGE_OPTIMIZER_TOOL_PATH ) ) {
			ewww_image_optimizer_tool_folder_permissions_notice();
		}
		echo "<div id='ewww-image-optimizer-warning-opt-png' class='error'><p>" . wp_kses( sprintf(__('EWWW Image Optimizer uses %1$s, %2$s, %3$s, %4$s, %5$s, and %6$s. You are missing: %7$s. Please install via the %8$s or the %9$s.', EWWW_IMAGE_OPTIMIZER_DOMAIN), "<a href='http://jpegclub.org/jpegtran/'>jpegtran</a>", "<a href='http://optipng.sourceforge.net/'>optipng</a>", "<a href='http://advsys.net/ken/utils.htm'>pngout</a>", "<a href='http://pngquant.org/'>pngquant</a>", "<a href='http://www.lcdf.org/gifsicle/'>gifsicle</a>", "<a href='https://developers.google.com/speed/webp/'>cwebp</a>", $msg, "<a href='$settings_page'>" . __('Settings Page', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a>", "<a href='https://wordpress.org/extend/plugins/ewww-image-optimizer/installation/'>" . __('Installation Instructions', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a>"), array( 'a' => array( 'href' => array() ) ) ) . "</p></div>";
	ewwwio_memory( __FUNCTION__ );
	}
}

// function to check if exec() is disabled
function ewww_image_optimizer_exec_check() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$disabled = ini_get('disable_functions');
	ewwwio_debug_message( "disable_functions: $disabled" );
	$suhosin_disabled = ini_get('suhosin.executor.func.blacklist');
	ewwwio_debug_message( "suhosin_blacklist: $suhosin_disabled" );
	if(preg_match('/([\s,]+exec|^exec)/', $disabled) || preg_match('/([\s,]+exec|^exec)/', $suhosin_disabled)) {
	ewwwio_memory( __FUNCTION__ );
		return true;
	} else {
	ewwwio_memory( __FUNCTION__ );
		return false;
	}
}

// function to check if safe mode is on
function ewww_image_optimizer_safemode_check() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$safe_mode = ini_get('safe_mode');
	ewwwio_debug_message( "safe_mode = $safe_mode" );
	switch (strtolower($safe_mode)) {
		case 'off':
	ewwwio_memory( __FUNCTION__ );
			return false;
		case 'on':
		case true:
	ewwwio_memory( __FUNCTION__ );
			return true;
		default:
	ewwwio_memory( __FUNCTION__ );
			return false;
	}
}

// If the utitilites are in the content folder, we use that. Otherwise, we check system paths. We also do a basic check to make sure we weren't given a malicious path.
function ewww_image_optimizer_path_check ( $j = true, $o = true, $g = true, $p = true, $q = true, $w = true) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$jpegtran = false;
	$optipng = false;
	$gifsicle = false;
	$pngout = false;
	$pngquant = false;
	$webp = false;
	if ( EWWW_IMAGE_OPTIMIZER_NOEXEC ) {
		return array(
			'JPEGTRAN' => false,
			'OPTIPNG' => false,
			'GIFSICLE' => false,
			'PNGOUT' => false,
			'PNGQUANT' => false,
			'WEBP' => false,
		);
	}
	if ( 'WINNT' == PHP_OS ) {
		if ( $j ) {
			$jpegtran = ewww_image_optimizer_find_win_binary( 'jpegtran', 'j' );
		}
		if ( $o ) {
			$optipng = ewww_image_optimizer_find_win_binary( 'optipng', 'o' ); 
		}
		if ( $g ) {
			$gifsicle = ewww_image_optimizer_find_win_binary( 'gifsicle', 'g' ); 
		}
		if ( $p ) {
			$pngout = ewww_image_optimizer_find_win_binary( 'pngout', 'p' ); 
		}
		if ( $q ) {
			$pngquant = ewww_image_optimizer_find_win_binary( 'pngquant', 'q' ); 
		}
		if ( $w ) {
			$webp = ewww_image_optimizer_find_win_binary( 'cwebp', 'w' ); 
		}
	} else {
		if ( $j ) {
			$jpegtran = ewww_image_optimizer_find_nix_binary( 'jpegtran', 'j' );
			if ( ! $jpegtran ) {
				$jpegtran = ewww_image_optimizer_find_nix_binary( 'jpegtran', 'jb' );
			}
		}
		if ( $o ) {
			$optipng = ewww_image_optimizer_find_nix_binary( 'optipng', 'o' );
			if ( ! $optipng ) {
				$optipng = ewww_image_optimizer_find_nix_binary( 'optipng', 'ob' );
			}
		}
		if ( $g ) {
			$gifsicle = ewww_image_optimizer_find_nix_binary( 'gifsicle', 'g' );
			if ( ! $gifsicle ) {
				$gifsicle = ewww_image_optimizer_find_nix_binary( 'gifsicle', 'gb' );
			}
		}
		if ( $p ) {
			// pngout is special and has a dynamic and static binary to check
			$pngout = ewww_image_optimizer_find_nix_binary( 'pngout-static', 'p' );
			if ( ! $pngout ) {
				$pngout = ewww_image_optimizer_find_nix_binary( 'pngout', 'p' );
			}
			if ( ! $pngout ) {
				$pngout = ewww_image_optimizer_find_nix_binary( 'pngout-static', 'pb' );
			}
			if ( ! $pngout ) {
				$pngout = ewww_image_optimizer_find_nix_binary( 'pngout', 'pb' );
			}
		}
		if ( $q ) {
			$pngquant = ewww_image_optimizer_find_nix_binary( 'pngquant', 'q' );
			if ( ! $pngquant ) {
				$pngquant = ewww_image_optimizer_find_nix_binary( 'pngquant', 'qb' );
			}
		}
		if ( $w ) {
			$webp = ewww_image_optimizer_find_nix_binary( 'cwebp', 'w' );
			if ( ! $webp ) {
				$webp = ewww_image_optimizer_find_nix_binary( 'cwebp', 'wb' );
			}
		}
	}
	if ( $jpegtran ) ewwwio_debug_message( "using: $jpegtran" );
	if ( $optipng ) ewwwio_debug_message( "using: $optipng" );
	if ( $gifsicle ) ewwwio_debug_message( "using: $gifsicle" );
	if ( $pngout ) ewwwio_debug_message( "using: $pngout" );
	if ( $pngquant ) ewwwio_debug_message( "using: $pngquant" );
	if ( $webp ) ewwwio_debug_message( "using: $webp" );
	ewwwio_memory( __FUNCTION__ );
	return array(
		'JPEGTRAN' => $jpegtran,
		'OPTIPNG' => $optipng,
		'GIFSICLE' => $gifsicle,
		'PNGOUT' => $pngout,
		'PNGQUANT' => $pngquant,
		'WEBP' => $webp,
	);
}

// checks the binary at $path against a list of valid sha256 checksums (formerly md5sums)
function ewww_image_optimizer_md5check( $path ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$binary_sum = hash_file( 'sha256', $path );
	ewwwio_debug_message( "$path: $binary_sum" );
	$valid_sums = array(
		'463de9ba684d54d27185cb6487a0b22b7571a87419abde4dee72c9b107f23315', // jpegtran-mac 9, EWWW 1.3.0
		'0b94f82e3d740d1853281e9aaee5cc7122c27fd63da9d6d62ed3398997cbed1e', // jpegtran-linux 9, EWWW 1.4.0
		'f5f079bfe6f3f48c17738679292f35cdee44afe8f8413cdbc4f555cee7de4173', // jpegtran-linux64 9, EWWW 1.4.0
		'ec71f638d2101f08fab66f4d139746d4042352bc75d55bd093aa446081892e0c', // jpegtran-fbsd 9, EWWW 1.4.0
		'356532227fce51fcb9df29f143ab9d202fbd40f18e2b8234aee95937c93bd67e', // jpegtran-fbsd64 9, EWWW 1.4.0
		'7be857837764dff4f0d7d2c5d546bf4d2573af7f326ced908ac229d60fd054c6', // jpegtran.exe 9, EWWW 1.4.0
		'bce5205bb240532c01273b5442a44244a8a27a74fb47e2ce467c18b91fabea6b', // jpegtran-sol 9, EWWW 1.7.4
		'cadc7be4688632bf2860562a1596f1b2b54b9a9c8b27df7ecabca49b1dcd8a5f', // jpegtran-fbsd 9a, EWWW 2.0.0
		'bab4aa853c143534503464eeb35893d16799cf859ff22f9a4e62aa383f4fc99c', // jpegtran-fbsd64 9a, EWWW 2.0.0
		'deb7e0f579fac767196611aa110052864e3093017970ff74de709b41e265e8b1', // jpegtran-linux 9a, EWWW 2.0.0
		'b991fde396ebcc0e4f805df44b1797fe369f7f19e9392684dd4052e3f23c441e', // jpegtran-linux64 9a, EWWW 2.0.0
		'436835bd42b27d2f05440bc5dc5174f2a896d38f8a550d96704d39969951d9ac', // jpegtran-mac 9a, EWWW 2.0.0
		'bdf3c6b6cb16287a3f62e7cde8f69f8bda5d310abca28e00068c526f9f37cc89', // jpegtran-sol 9a, EWWW 2.0.0
		'3c2746d0b1ae150b13b767715af45ff601e394c01ada929cbe16e6dcd18fb199', // jpegtran.exe 9a, EWWW 2.0.0
		'8e11f7df5735b36d3ecc95c84b0e355355a766d3ccafbf751bcf343a8952432c', // jpegtran-fbsd 9b, EWWW 2.6.0
		'21d8046e07cb298dfd2f3b1e321c67c378a4d35fa8adc3521acc42b5b8088d64', // jpegtran-linux 9b, EWWW 2.6.0
		'4d1a1c601d291f96dc03ea7e42ab9137a17f93ebc391353db65b4e32c1e9fbdb', // jpegtran-mac 9b, EWWW 2.6.0
		'7e8719703d31e1ab9bf2b2ad7ab633649012ab6aae46ea40462365b9c00876d5', // jpegtran-sol 9b, EWWW 2.6.0
		'9767f05ae1b59d4fea25a73b276dcd1245f5281b53386dc03784539265bffbea', // jpegtran.exe 9b, EWWW 2.6.0

		'6deddb5562ac13ffc3e46a0af79b592e92fb4553c5df294b6e0052bc890fd0e3', // optipng-linux 0.7.4, EWWW 1.2.0
		'51df81fa8c765efbe0aa4c1cf5293e25e7e2e7f6962f5161615239c54aec4c01', // optipng-linux 0.7.4, EWWW 1.3.0
		'7a56cca66471ce2b6cdff4460db0d75258ef05de8da1eda0448e4d4ad9ae252f', // optipng-mac 0.7.4, EWWW 1.3.0
		'2f9140cdc3ef1f7687baa710f0bba84c5f7f11e3f62c3ce43124e23b849ac5ff', // optipng-linux 0.7.4, EWWW 1.3.7
		'5d59467363c457bf743f4df121c365dd43365357f1cdea5f3752a7ca1b3e315a', // optipng-fbsd 0.7.4, EWWW 1.4.0
		'1af8077958a88a3064a71903841f901179e27fe137774085565619fb199c653a', // optipng.exe 0.7.4, EWWW 1.4.0
		'f692fef395b8689de033b9f2ce80c867c8a229c52e948df733377e20b62773a9', // optipng-sol 0.7.4, EWWW 1.7.4
		'e17d327cd89ab34eff7f994806fe9f2c124d6cc6cd309fa4c3911d5ce90312c9', // optipng-fbsd 0.7.5, EWWW 2.0.0
		'd263ecfb5b29ed08920e26cf604a86d3484daee5b80605e445cf97aa14d8aebc', // optipng-linux 0.7.5, EWWW 2.0.0
		'6f15cb2e8d25e51037efa7bcec7499c96eb11e576536a478edfee500207655ae', // optipng-mac 0.7.5, EWWW 2.0.0
		'1d2de40b009f16e9c709f9b0c15a47abb8da57668a918ac9a0723ddc6de6c30a', // optipng-sol 0.7.5, EWWW 2.0.0
		'fad3a0fd95706d53576f72593bf13d3e31d1c896c852bfd5b9ba602eca0bd2b6', // optipng.exe 0.7.5, EWWW 2.0.0

		'a2292c0085863a65c99cb41ff8418ce63033e162906df72e8fdde52f0633579b', // gifsicle linux 1.67, EWWW 1.2.0
		'd7f9609b6fd0000b2eaad2bd0c3cb85476988b18705762e915bda3f2e6007801', // gifsicle-linux 1.68, EWWW 1.3.0
		'204a839a50367adb8cd23fae5d1913a5ca8b41307f054156ed152748d3e7934d', // gifsicle-linux 1.68, EWWW 1.3.7
		'23e208099fa7ce75a3f98144190d6362d69b90c6f0a534ffa45dbbf789f7d99c', // gifsicle-mac 1.68, EWWW 1.3.0
		'8b08243a7cc655512a03403f6c3814176e28bbd140df7c059bd321a9a0151c18', // gifsicle-fbsd 1.70, EWWW 1.4.0
		'fd074673967ee9d387208f047c081a6331663b4076f4a6a608d6f646622af718', // gifsicle-linux 1.70, EWWW 1.4.0 - 1.7.4
		'bc32a390e86d2d8f40e970b2dc059015b51afe26794d92a936c1fe7216db805d', // gifsicle-mac 1.70, EWWW 1.4.0
		'41e67a35cd178f781b5224d196185e4243e6c2b3bece43277130fe07cdda402f', // gifsicle-sol 1.70, EWWW 1.7.4
		'3c6d9fabd1ea1014b8f58063dd00a653980c06bc1b45e96a47d866247263a1e1', // gifsicle.exe 1.70, EWWW 1.4.0
		'decba7a95b637bee53847af680fd37bde8bd568528412c514b7bd794056fd4ff', // gifsicle-fbsd 1.78, EWWW 1.7.5
		'c28e5e4b5344f77f415973d013e4cb393fc550e8de44117b090d534e98b30d1c', // gifsicle-linux 1.78, EWWW 1.7.5 - 1.9.3
		'fc2de863e8579b0d540003300e918cee450bc8e026018c631dffc0ed851a8c1c', // gifsicle-mac 1.78, EWWW 1.7.5
		'74d011ee1b6d9fe6d5d8bdb4cd17db0c5987fa6e3d495b42439cd70b0763c07a', // gifsicle-sol 1.78, EWWW 1.7.5
		'7c10da38f4afb28373779d40a30710aa9fb369e82f7f29363554bea965d132df', // gifsicle.exe 1.78, EWWW 1.7.5
		'e75acedd0725fba64ee72855b796cdfa8dac9959d63e89a9e0e5ba059ae013c2', // gifsicle-fbsd 1.84, EWWW 2.0.0
		'a4f0f21bc4bea51f5d304fe944262c12f671d70a3e5f688061da7bb036e84ff8', // gifsicle-linux 1.84, EWWW 2.0.0 - 2.4.3
		'5f4176b3fe69f975563d2ce7e76615ab558f5f1839b9bfa6f6de1b3c3fa11c02', // gifsicle-mac 1.84, EWWW 2.0.0
		'9f0027bed22d4be60012488ab726c3a131d9f3e1e276e9400c578173347a9a48', // gifsicle-sol 1.84, EWWW 2.0.0
		'72f0077e8591292d09efee09a181458b34fb3c0e9a6ac7e8e11cec574bf619ac', // gifsicle.exe 1.84, EWWW 2.0.0
		'c64936b429e46b6a75339df00eb8daa39d335844c906fa16d4d0af481851e91e', // gifsicle-fbsd 1.87, EWWW 2.4.4
		'deea065a91c8429edecf42ccef78636065f7ae0dad867df7696128c6711e4735', // gifsicle-linux 1.87, EWWW 2.4.4
		'2e0d8b7413173555bbec6e019c3cd7c55f7d582a017a0af7b14cfd24a6921f51', // gifsicle-mac 1.87, EWWW 2.4.4
		'3966e01474601059c6a13aefbe4f313c6cb6d49c799f7850966950892a9ab45a', // gifsicle-sol 1.87, EWWW 2.4.4
		'40b86b2ea6642f4c921152923af1e631922b624f7d23189f53c659506c7179b5', // gifsicle.exe 1.87, EWWW 2.4.4
		'3da9e1a764a459d78dc1468ba60d882ff042050a86f82d895777b172b50f2f19', // gifsicle.exe 1.87, EWWW 2.4.5

		'bdea95497d6e60aae8938cae8e999ef74a255ad603531bf523dcdb531f61fc8f', // 20110722-bsd/i686/pngout
		'57c09b3ebd7d4623d16f6056efd7951e8f98e2362a27993a7d865af677875c00', // 20110722-bsd-static/i686/pngout-static
		'17960599ca28a61aeb883a68b2eb52c513b730a410a0db75a7c2c22e0a3f925a', // 20110722-linux/i686/pngout
		'689f68bcbf39e68cdf0f0a350d59c0acafdbcf7ff122e25b5a8b58ed3a8f18ef', // 20110722-linux/x86_64/pngout
		'2028eea62f04b074b7693e5ce625c848ff6521206782616c893ca93637644a51', // 20110722-linux-static/i686/pngout-static
		'7d071c3a6ac9c4e8077f029dbba1cde49008d38adf897401e951f9c2e7ce8bb1', // 20110722-linux-static/x86_64/pngout-static
		'89c510b551718d263433bb37e67364cab582a71bf7f5558213a121bb86cb5f98', // 20110722-mac/pngout
		'e383a5293e3b1934c87367799f6eaefbd6714cfa004262f273fb7f2f4d15930b', // 20130221-bsd/i686/pngout
		'd2b70c882be527543818d84552cc4e6faf40da3cec45286e5c36ed73e9611b7b', // 20130221-bsd/amd64/pngout
		'bc08e1f883ba92a04e44fe4e756e1afc3b77fc1d072519adff6ce2f7787109bb', // 20130221-bsd-static/i686/pngout-static
		'860779de32c1fe34f211da036471d6e4ecc0d35527727d476f29623785cf6f82', // 20130221-bsd-static/amd64/pngout-static
		'edd8e6173bf3b862c6c40c4b5aad6514169a58ee9b0b34d8c37e475005889592', // 20130221-linux/i686/pngout
		'f6a053d1c03b69e2ac4435aaa5b5e13ea5169d9a262286595f9f455d8da5caf1', // 20130221-linux/x86_64/pngout
		'9591669b3984a19f0aab3a8e8fad98c5274b3c30daecf46b35d22df934546618', // 20130221-linux-static/i686/pngout-static
		'25d2aab99796c26f1e9cf1f2a9713920be40ce1b99e02c2c50b67fa6e3da06be', // 20130221-linux-static/x86_64/pngout-static
		'57fd225f3ae921309ee4570f1970629d31cb02946983405d1b1f648aeaab30a1', // 20130221-mac/pngout
		'3dfeb927e96853d2470350b0a916dc263cd4ebe878b402773dba105a6644e796', // 20150319-bsd/i686/pngout
		'60a2848c79551a3e79ffcea7f54964767e25bb05c2255b0ea6a1eb03605661d2', // 20150319-bsd/amd64/pngout
		'52dd45f15221f2ff30739151f30aedb5e3377dd6bccd350d4bce9429d7fa5e8b', // 20150319-bsd-static/i686/pngout-static
		'12ffa454936e1d35dc96749208d740695fea26d07267b6a17b9890db0f156026', // 20150319-bsd-static/amd64/pngout-static
		'5b97595c2b4e5f47ba797b105b3b56dbb769437bdc9092f07f6c57bc457ee667', // 20150319-linux/i686/pngout
		'a496985d02c785c05f21f653fc4d61a5a171a68f691119448bc3c3152246f0d7', // 20150319-linux/x86_64/pngout
		'b6641cb01b684c42e40076b91f98485dd115f6200d3f0baf989f1a4ae43c603a', // 20150319-linux-static/i686/pngout-static
		'2b8245fe21a648101b8e7399a9dfcc4cf42a39dafa7aab673a7c47901bf82e4a', // 20150319-linux-static/x86_64/pngout-static
		'12afd90e04387d4c3be985042c1eada89e0c4504f84c0b4739c459c7b3831774', // 20150319-mac/pngout
		'843f0be42e86680c1663c4ef58eb0677ace15fc29ab23897c83f4b7e5af3ef36', // 20150319-windows/pngout.exe 20150319
		'aa3993937455094c0f66ac77d60bf53be441fdf8f14618520c2af68f2253085d', // 20150920-mac/pngout

		'8417d5d60bc66442ecc666e31ec7b9e1b7c55f48291e74b4b81f35703e2aef2e', // pngquant-fbsd 2.0.2, EWWW 1.8.3
		'78668c38d0be70764b18f3f4e0ea2b647df2ae87cedb2216d0ef69c8c55b688a', // pngquant-linux 2.0.2, EWWW 1.8.3
		'7df1b7f6ed73a189083dd931fb3380d236d34790318f00233b59c8f26f90665f', // pngquant-mac 2.0.2, EWWW 1.8.3
		'56d2c6212eb595f5eab8a7469e56fa8d3d0e6ffc231aef27742134fba4a39298', // pngquant-sol 2.0.2, EWWW 1.8.3
		'd3851c962cd59d74a35174bf3ce71d876dfcd8bdf76f81cd428b2ab7e53c0515', // pngquant.exe 2.0.2, EWWW 1.8.3
		'0ee6f1dbf4fa168b11ce60860e5700ca0e5125323a43540a78c76644835abc84', // pngquant-fbsd 2.3.0, EWWW 2.0.0
		'85d8a70930a554f50181a1d061577cf67ef2e76e2cbd5bcb1b7f006064ff1444', // pngquant-linux 2.3.0, EWWW 2.0.0
		'a807f769922fdad0ba07307c548df8cf8eeced649d04237d13dfc95757161459', // pngquant-mac 2.3.0, EWWW 2.0.0
		'cf2cc40274c438b35e93bd0346c2a6d871bd7a7bdd90c52f4e79f369cb8ded74', // pngquant-sol 2.3.0, EWWW 2.0.0
		'7454aba77b1a2b63a42d8a5870d3c2d733c7efb2d828643d5e64784af1f65f2a', // pngquant.exe 2.3.0, EWWW 2.0.0
		'6287f1bb7179c7b6d71a41112222347ed97b6eae4e79b180d7e1e332a4bde3e2', // pngquant-fbsd 2.5.2, EWWW 2.5.4
		'fcfe4d3a602e7b491f4126a2707144f5f9cc9359d13f443575d7ea6a74e85ddb', // pngquant-linux 2.5.2, EWWW 2.5.4
		'35794819a35e949dc0c0d6f90d0bb675791fa9bc3f405eb19f48ea31bb6456a8', // pngquant-mac 2.5.2, EWWW 2.5.4
		'c242586c70d83af544334f1846b838ef68c6ab4fc247b2cff9ad4b714f825866', // pngquant-sol 2.5.2, EWWW 2.5.4
		'ad79d9b3395d41404b28362972bd68db3c58d5be5f063884df3a595fc38c6a98', // pngquant.exe 2.5.2, EWWW 2.5.4
		
		'bf0e12f996802dc114a864e5150647ce41089a5a2b5e36c3a270ac848b655c26', // cwebp-fbsd 0.4.1, EWWW 2.0.0
		'5349646072c3ef5f8b4588bbee8635e882c245439e2d86b863f04b7e27f4fafe', // cwebp-fbsd64 0.4.1, EWWW 2.0.0
		'3044c02cfef53f4361f7b2db49c5679f894ed346f665d4c8d91c6675d84dbf67', // cwebp-linux6 0.4.1, EWWW 2.0.0
		'c9899718a5e272a082fd7c9d93d7c23d8a50f49d1b739a9aa1ef404f78cd7baf', // cwebp-linux664 0.4.1, EWWW 2.0.0
		'2a0dff5c80fd5fa170babd0c0571f4499606f8d09bf820938da41a311d6dec6f', // cwebp-linux8 0.4.1, EWWW 2.0.0
		'c1dfbbad935e31bde2e517dff43911c0651a8e5f78c022a252a864278065ae11', // cwebp-linux864 0.4.1, EWWW 2.0.0
		'bae23f1614d391b136e8618a21590e4a9f0614c8716b86a6a7067527e9950d87', // cwebp-mac7 0.4.1, EWWW 2.0.0
		'bc72654fead42c6d4fd841cecdee6ccbf21b2407292593ec982f31d39b566955', // cwebp-mac8 0.4.1, EWWW 2.0.0
		'7fa005dc6a18563e4f6574bec83c92cabf786d8ee845503d80fa52e370dc4903', // cwebp-sol 0.4.1, EWWW 2.0.0
		'6486779c8e1e9cc7c63ae03c416fc6d5dc7598c58a6cddbe9a41e70d804410f9', // cwebp.exe 0.4.1, EWWW 2.0.0
		'814a168f190c4712df231b1f7d1910185ef823953b54c9fb8b354f415172a371', // cwebp-fbsd64 0.4.3, EWWW 2.4.4
		'0f867ea2db0db895612bd15916ad31bc71c89ef2ad74552b7e878df09b843da5', // cwebp-linux6 0.4.3, EWWW 2.4.4
		'179c7b9a2fbc1af542b3653bff58ca4dcb35bebf346687c12bb667ab49e9e21b', // cwebp-linux664 0.4.3, EWWW 2.4.4
		'212e59654bbb6147ee8a554bf8eb7b5c11f75b9ef14ac3e6ee92ad726a47339a', // cwebp-linux8 0.4.3, EWWW 2.4.4
		'b491509221f7c97e8dcc3bdd6f7fc201f40bc93062618bfba06f84aac7704558', // cwebp-linux864 0.4.3, EWWW 2.4.4
		'2e8c5f53f44656ec80f11cca3c985200f502c88ea47bb34063e09eb6313e04a6', // cwebp-mac8 0.4.3, EWWW 2.4.4
		'963a09a2c45ba036291b32ecb665541e40c232bb0f2474810ac2a9ddf8837fe4', // cwebp-mac9 0.4.3, EWWW 2.4.4
		'2642d98bb75bc2fd2d969ba1d27b8628fd7fa73a7a204ed8f71a65e124abcac0', // cwebp-sol 0.4.3, EWWW 2.4.4
		'64cd62e33201b0d14ec4823b64d93f92825f2e8f5239726f5b00ed9ff944a581', // cwebp.exe 0.4.3, EWWW 2.4.4
		'7d7329671d445924dafcaacee7f2db6f4ce33567ffca41aa5b5818ebff806bc5', // cwebp-fbsd64 0.4.4, EWWW 2.5.4
		'f1a48031d0ab602080f5646695ce8a3e84d5470f1be99d1b8fc20aded9c7839b', // cwebp-linux6 0.4.4, EWWW 2.5.4
		'b2bef90b62d80b35d4c5a41f793454e95e5159bf0aec2e4bd8c19fc3de3556bd', // cwebp-linux664 0.4.4, EWWW 2.5.4
		'd3c358524efd50f6e078362733870229ca1e1db8885580b6814c2535b4d20612', // cwebp-linux8 0.4.4, EWWW 2.5.4
		'271deeec579c252e364495addad03d9c1f3248c2177a01638002b25eee787ded', // cwebp-linux864 0.4.4, EWWW 2.5.4
		'379e2b95e20dd33f4667c134099df358e178f6a6bf32f3a5b6b78bbb6950b4b5', // cwebp-mac9 0.4.4, EWWW 2.5.4
		'118ea3f0bcdcce6128d64e34159c93c3324cb038c9e5a51efaf530ea52af7070', // cwebp-sol 0.4.4, EWWW 2.5.4
		'43941c1d7169e66fb1fd62a1950286b230d3e5bec3bbb14fdb4ac091ca7a0f9f', // cwebp.exe 0.4.4, EWWW 2.5.4
		'26d5d88dee2993d1d0e16f5e60318cd8adec485614facd6c7f9c22c71eb7b2e5', // cwebp-fbsd 0.5.0 EWWW 2.6.0
		'60b1738d6502691227a46658cd7656b4a52702680f169e8e04d72077e967aeed', // cwebp-linux 0.5.0, EWWW 2.6.0
		'276a0221a4c978825903572c2b68b3010399375d6b9dc7429286caf625cae95a', // cwebp-mac9 0.5.0, EWWW 2.6.0
		'be3e81ec7267e7878ddd4ee01df1553966952f74bbfd30a5523d12d53f019ecb', // cwebp-sol 0.5.0, EWWW 2.6.0
		'b41123ec06f21765f50ec1b017839f99ab4f28497d87da722817a6023e4a3b32', // cwebp.exe 0.5.0, EWWW 2.6.0
	);
	foreach ( $valid_sums as $checksum ) {
		if ( $checksum === $binary_sum ) {
			ewwwio_debug_message( 'checksum verified, binary is intact' );
			ewwwio_memory( __FUNCTION__ );
			return TRUE;
		}
	}
	ewwwio_memory( __FUNCTION__ );
	return FALSE;
}

// check the mimetype of the given file ($path) with various methods
// valid values for $type are 'b' for binary or 'i' for image
function ewww_image_optimizer_mimetype( $path, $case ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_debug_message( "testing mimetype: $path" );
	if ( $case == 'i' && preg_match( '/^RIFF.+WEBPVP8/', file_get_contents( $path, NULL, NULL, 0, 16 ) ) ) {
			return 'image/webp';
	}
	if ( function_exists( 'finfo_file' ) && defined( 'FILEINFO_MIME' ) ) {
		// create a finfo resource
		$finfo = finfo_open(FILEINFO_MIME);
		// retrieve the mimetype
		$type = explode(';', finfo_file($finfo, $path));
		$type = $type[0];
		finfo_close($finfo);
		ewwwio_debug_message( "finfo_file: $type" );
	}
	// see if we can use the getimagesize function
	if (empty($type) && function_exists('getimagesize') && $case === 'i') {
		// run getimagesize on the file
		$type = getimagesize($path);
		// make sure we have results
		if(false !== $type){
			// store the mime-type
			$type = $type['mime'];
		}
		ewwwio_debug_message( "getimagesize: $type" );
	}
	// see if we can use mime_content_type
	if (empty($type) && function_exists('mime_content_type')) {
		// retrieve and store the mime-type
		$type = mime_content_type($path);
		ewwwio_debug_message( "mime_content_type: $type" );
	}
	// if nothing else has worked, try the 'file' command
	if ( (empty( $type ) || $type != 'application/x-executable' ) && $case == 'b' ) {
		// find the 'file' command
		if ($file = ewww_image_optimizer_find_nix_binary('file', 'f')) {
			// run 'file' on the file in question
			exec("$file $path", $filetype);
			ewwwio_debug_message( "file command: {$filetype[0]}" );
			// if we've found a proper binary
			if ((strpos($filetype[0], 'ELF') && strpos($filetype[0], 'executable')) || strpos($filetype[0], 'Mach-O universal binary')) {
				$type = 'application/x-executable';
			}
		}
	}
	// if we are dealing with a binary, and found an executable
	if ($case == 'b' && preg_match('/executable|octet-stream/', $type)) {
		ewwwio_memory( __FUNCTION__ );
		return $type;
	// otherwise, if we are dealing with an image
	} elseif ($case == 'i') {
		ewwwio_memory( __FUNCTION__ );
		return $type;
	// if all else fails, bail
	} else {
		ewwwio_memory( __FUNCTION__ );
		return false;
	}
}

// escape any spaces in the filename, not sure any more than that is necessary for unixy systems
function ewww_image_optimizer_escapeshellcmd( $path ) {
	return (preg_replace('/ /', '\ ', $path));
}

// test the given path ($path) to see if it returns a valid version string
// returns: version string if found, FALSE if not
function ewww_image_optimizer_tool_found( $path, $tool ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_debug_message( "testing case: $tool at $path" );
	// '*b' cases are 'blind' testing in case we can't get at the version string, but the binaries are actually working, we run a test compression, and compare the results with what they should be
	switch( $tool ) {
		case 'j': // jpegtran
			exec( $path . ' -v ' . EWWW_IMAGE_OPTIMIZER_IMAGES_PATH . 'sample.jpg 2>&1', $jpegtran_version );
			if ( ! empty( $jpegtran_version ) ) ewwwio_debug_message( "$path: {$jpegtran_version[0]}" );
			foreach ( $jpegtran_version as $jout ) { 
				if ( preg_match( '/Independent JPEG Group/', $jout ) ) {
					ewwwio_debug_message( 'optimizer found' );
					return $jout;
				}
			}
			break;
		case 'jb':
			$upload_dir = wp_upload_dir();
			$testjpg = trailingslashit( $upload_dir['basedir'] ) . 'testopti.jpg';
			exec( $path . ' -copy none -optimize -outfile ' . ewww_image_optimizer_escapeshellarg( $testjpg ) . ' ' . ewww_image_optimizer_escapeshellarg( EWWW_IMAGE_OPTIMIZER_IMAGES_PATH . 'testorig.jpg' ) );
			$testjpgsize = ewww_image_optimizer_filesize( $testjpg );
			ewwwio_debug_message( "blind testing jpegtran, is $testjpgsize smaller than 5700?" );
			if ( $testjpgsize ) {
				unlink( $testjpg );
			}
			if ( 0 < $testjpgsize && $testjpgsize < 5700 ) {
				ewwwio_debug_message( 'optimizer found' );
				return esc_html__( 'unknown', EWWW_IMAGE_OPTIMIZER_DOMAIN ) ;
			}
			break;
		case 'o': // optipng
			exec( $path . ' -v 2>&1', $optipng_version );
			if ( ! empty( $optipng_version ) ) ewwwio_debug_message( "$path: {$optipng_version[0]}" );
			if ( ! empty( $optipng_version ) && strpos( $optipng_version[0], 'OptiPNG' ) === 0 ) {
				ewwwio_debug_message( 'optimizer found' );
				return $optipng_version[0];
			}
			break;
		case 'ob':
			$upload_dir = wp_upload_dir();
			$testpng = trailingslashit( $upload_dir['basedir'] ) . 'testopti.png';
			exec( $path . ' -out ' . ewww_image_optimizer_escapeshellarg( $testpng ) . ' -o1 -quiet -strip all ' . ewww_image_optimizer_escapeshellarg( EWWW_IMAGE_OPTIMIZER_IMAGES_PATH . 'testorig.png' ) );
			$testpngsize = ewww_image_optimizer_filesize( $testpng );
			ewwwio_debug_message( "blind testing optipng, is $testpngsize smaller than 110?" );
			if ( $testpngsize ) {
				unlink( $testpng );
			}
			if ( 0 < $testpngsize && $testpngsize < 110 ) {
				ewwwio_debug_message( 'optimizer found' );
				return esc_html__( 'unknown', EWWW_IMAGE_OPTIMIZER_DOMAIN ) ;
			}
			break;
		case 'g': // gifsicle
			exec( $path . ' --version 2>&1', $gifsicle_version );
			if ( ! empty( $gifsicle_version ) ) ewwwio_debug_message( "$path: {$gifsicle_version[0]}" );
			if ( ! empty( $gifsicle_version ) && strpos( $gifsicle_version[0], 'LCDF Gifsicle' ) === 0 ) {
				ewwwio_debug_message( 'optimizer found' );
				return $gifsicle_version[0];
			}
			break;
		case 'gb':
			$upload_dir = wp_upload_dir();
			$testgif = trailingslashit( $upload_dir['basedir'] ) . 'testopti.gif';
			exec( $path . ' -O3 -o ' . ewww_image_optimizer_escapeshellarg( $testgif ) . ' ' . ewww_image_optimizer_escapeshellarg( EWWW_IMAGE_OPTIMIZER_IMAGES_PATH . 'testorig.gif' ) );
			$testgifsize = ewww_image_optimizer_filesize( $testgif );
			ewwwio_debug_message( "blind testing gifsicle, is $testgifsize smaller than 12000?" );
			if ( $testgifsize ) {
				unlink( $testgif );
			}
			if ( 0 < $testgifsize && $testgifsize < 12000 ) {
				ewwwio_debug_message( 'optimizer found' );
				return esc_html__( 'unknown', EWWW_IMAGE_OPTIMIZER_DOMAIN ) ;
			}
			break;
		case 'p': // pngout
			exec( "$path 2>&1", $pngout_version );
			if ( ! empty( $pngout_version ) ) ewwwio_debug_message( "$path: {$pngout_version[0]}" );
			if ( ! empty( $pngout_version ) && strpos( $pngout_version[0], 'PNGOUT' ) === 0 ) {
				ewwwio_debug_message( 'optimizer found' );
				return $pngout_version[0];
			}
			break;
		case 'pb':
			$upload_dir = wp_upload_dir();
			$testpng = trailingslashit( $upload_dir['basedir'] ) . 'testopti.png';
			exec( $path . ' -s3 -q ' . ewww_image_optimizer_escapeshellarg( EWWW_IMAGE_OPTIMIZER_IMAGES_PATH . 'testorig.png' ) . ' ' . ewww_image_optimizer_escapeshellarg( $testpng ) );
			$testpngsize = ewww_image_optimizer_filesize( $testpng );
			ewwwio_debug_message( "blind testing pngout, is $testpngsize smaller than 110?" );
			if ( $testpngsize ) {
				unlink( $testpng );
			}
			if ( 0 < $testpngsize && $testpngsize < 110 ) {
				ewwwio_debug_message( 'optimizer found' );
				return esc_html__( 'unknown', EWWW_IMAGE_OPTIMIZER_DOMAIN ) ;
			}
			break;
		case 'q': // pngquant
			exec( $path . ' -V 2>&1', $pngquant_version );
			if ( ! empty( $pngquant_version ) ) ewwwio_debug_message( "$path: {$pngquant_version[0]}" );
			if ( ! empty( $pngquant_version ) && substr( $pngquant_version[0], 0, 3 ) >= 2.0 ) {
				ewwwio_debug_message( 'optimizer found' );
				return $pngquant_version[0];
			}
			break;
		case 'qb':
			$upload_dir = wp_upload_dir();
			$testpng = trailingslashit( $upload_dir['basedir'] ) . 'testopti.png';
			exec( $path . ' -o ' . ewww_image_optimizer_escapeshellarg( $testpng ) . ' ' . ewww_image_optimizer_escapeshellarg( EWWW_IMAGE_OPTIMIZER_IMAGES_PATH . 'testorig.png' ) );
			$testpngsize = ewww_image_optimizer_filesize( $testpng );
			ewwwio_debug_message( "blind testing pngquant, is $testpngsize smaller than 114?" );
			if ( $testpngsize ) {
				unlink( $testpng );
			}
			if ( 0 < $testpngsize && $testpngsize < 114 ) {
				ewwwio_debug_message( 'optimizer found' );
				return esc_html__( 'unknown', EWWW_IMAGE_OPTIMIZER_DOMAIN ) ;
			}
			break;
		case 'i': // ImageMagick
			exec("$path -version 2>&1", $convert_version);
			if (!empty($convert_version)) ewwwio_debug_message( "$path: {$convert_version[0]}" );
			if (!empty($convert_version) && strpos($convert_version[0], 'ImageMagick')) {
				ewwwio_debug_message( 'imagemagick found' );
				return $convert_version[0];
			}
			break;
		case 'f': // file
			exec("$path -v 2>&1", $file_version);
			if (!empty($file_version[1])) ewwwio_debug_message( "$path: {$file_version[1]}" );
			if (!empty($file_version[1]) && preg_match('/magic/', $file_version[1])) {
				ewwwio_debug_message( 'file binary found' );
				return $file_version[0];
			} elseif (!empty($file_version[1]) && preg_match('/usage: file/', $file_version[1])) {
				ewwwio_debug_message( 'file binary found' );
				return $file_version[0];
			}
			break;
		case 'n': // nice
			exec("$path 2>&1", $nice_output);
			if (isset($nice_output)) ewwwio_debug_message( "$path: {$nice_output[0]}" );
			if (isset($nice_output) && preg_match('/usage/', $nice_output[0])) {
				ewwwio_debug_message( 'nice found' );
				return TRUE;
			} elseif (isset($nice_output) && preg_match('/^\d+$/', $nice_output[0])) {
				ewwwio_debug_message( 'nice found' );
				return TRUE;
			}
			break;
		case 't': // tar
			exec("$path --version 2>&1", $tar_version);
			if (!empty($tar_version[0])) ewwwio_debug_message( "$path: {$tar_version[0]}" );
			if (!empty($tar_version[0]) && preg_match('/bsdtar/', $tar_version[0])) {
				ewwwio_debug_message( 'tar found' );
				return $tar_version[0];
			} elseif (!empty($tar_version[0]) && preg_match('/GNU tar/i', $tar_version[0])) {
				ewwwio_debug_message( 'tar found' );
				return $tar_version[0];
			}
			break;
		case 'w': //cwebp
			exec( "$path -version 2>&1", $webp_version );
			if ( ! empty( $webp_version ) ) ewwwio_debug_message( "$path: {$webp_version[0]}" );
			if ( ! empty( $webp_version ) && preg_match( '/0.\d.\d/', $webp_version[0] ) ) {
				ewwwio_debug_message( 'optimizer found' );
				return $webp_version[0];
			}
			break;
		case 'wb':
			$upload_dir = wp_upload_dir();
			$testpng = trailingslashit( $upload_dir['basedir'] ) . 'testopti.png';
			exec( $path . ' -lossless -quiet ' . ewww_image_optimizer_escapeshellarg( EWWW_IMAGE_OPTIMIZER_IMAGES_PATH . 'testorig.png' ) . ' -o ' . ewww_image_optimizer_escapeshellarg( $testpng ) );
			$testpngsize = ewww_image_optimizer_filesize( $testpng );
			ewwwio_debug_message( "blind testing cwebp, is $testpngsize smaller than 114?" );
			if ( $testpngsize ) {
				unlink( $testpng );
			}
			if ( 0 < $testpngsize && $testpngsize < 114 ) {
				ewwwio_debug_message( 'optimizer found' );
				return esc_html__( 'unknown', EWWW_IMAGE_OPTIMIZER_DOMAIN ) ;
			}
			break;
	}
	ewwwio_debug_message( 'tool not found' );
	ewwwio_memory( __FUNCTION__ );
	return FALSE;
}

// searches for the given $binary on a Windows system and passes along the $switch
function ewww_image_optimizer_find_win_binary( $binary, $switch ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$use_system = ewww_image_optimizer_get_option( 'ewww_image_optimizer_skip_bundle' );
	if ( file_exists( EWWW_IMAGE_OPTIMIZER_TOOL_PATH . $binary . '.exe' ) ) {
		$binary_path = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . $binary . '.exe';
		ewwwio_debug_message( "found $binary_path, testing..." );
		if ( ewww_image_optimizer_md5check( $binary_path ) && ewww_image_optimizer_tool_found( '"' . $binary_path . '"', $switch ) ) {
			return '"' . $binary_path . '"';
		}
	}
	if ( file_exists( EWWW_IMAGE_OPTIMIZER_TOOL_PATH . $binary . '-custom.exe' ) && $j ) {
		$binary_path = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . $binary . '-custom.exe';
		ewwwio_debug_message( "found $binary_path, testing..." );
		if ( ewww_image_optimizer_tool_found( '"' . $binary_path . '"', $switch ) ) {
			return '"' . $binary_path . '"';
		}
	}
	if ( file_exists( EWWW_IMAGE_OPTIMIZER_TOOL_PATH . $binary . '-alt.exe' ) && $j ) {
		$binary_path = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . $binary . '-alt.exe';
		ewwwio_debug_message( "found $binary_path, testing..." );
		if ( ewww_image_optimizer_tool_found( '"' . $binary_path . '"', $switch ) ) {
			return '"' . $binary_path . '"';
		}
	}
	// if we still haven't found a usable binary, try a system-installed version
	if ( ewww_image_optimizer_tool_found( $binary . '.exe', $switch ) ) {
		return $binary . '.exe';
	} else {
		return '';
	}
}

// searches for the given $binary on a *nix system and passes along the $switch
function ewww_image_optimizer_find_nix_binary( $binary, $switch ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$use_system = ewww_image_optimizer_get_option( 'ewww_image_optimizer_skip_bundle' );
	// first check for the binary in the ewww tool folder
	if ( file_exists( EWWW_IMAGE_OPTIMIZER_TOOL_PATH . $binary ) && ! $use_system ) {
		$binary_path = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . $binary;
		ewwwio_debug_message( "found $binary_path, testing..." );
		if ( ewww_image_optimizer_md5check( $binary_path ) && ewww_image_optimizer_mimetype( $binary_path, 'b') ) {
			$binary_path = ewww_image_optimizer_escapeshellcmd( $binary_path );
			if ( ewww_image_optimizer_tool_found( $binary_path, $switch ) ) {
				return $binary_path;
			}
		}
	}
	// if the standard binary didn't work, see if the user custom compiled one and check that
	if ( file_exists( EWWW_IMAGE_OPTIMIZER_TOOL_PATH . $binary . '-custom') && ! $use_system ) {
		$binary_path = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . $binary . '-custom';
		ewwwio_debug_message( "found $binary_path, testing..." );
		if ( filesize( $binary_path ) > 15000 && ewww_image_optimizer_mimetype( $binary_path, 'b') ) {
			$binary_path = ewww_image_optimizer_escapeshellcmd( $binary_path );
			if ( ewww_image_optimizer_tool_found( $binary_path, $switch ) ) {
				return $binary_path;
			}
		}
	}
	// see if the alternative binary works
	if ( file_exists( EWWW_IMAGE_OPTIMIZER_TOOL_PATH . $binary . '-alt' ) && ! $use_system ) {
		$binary_path = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . $binary . '-alt';
		ewwwio_debug_message( "found $binary_path, testing..." );
		if ( filesize( $binary_path) > 15000 && ewww_image_optimizer_mimetype( $binary_path, 'b' ) ) {
			$binary_path = ewww_image_optimizer_escapeshellcmd( $binary_path );
			if (ewww_image_optimizer_tool_found( $binary_path, $switch ) ) {
				return $binary_path;
			}
		}
	}
	// if we still haven't found a usable binary, try a system-installed version
	if ( ewww_image_optimizer_tool_found( $binary, $switch ) ) {
		return $binary;
	} elseif ( ewww_image_optimizer_tool_found( '/usr/bin/' . $binary, $switch ) ) {
		return '/usr/bin/' . $binary;
	} elseif ( ewww_image_optimizer_tool_found( '/usr/local/bin/' . $binary, $switch ) ) {
		return '/usr/local/bin/' . $binary;
	} elseif ( ewww_image_optimizer_tool_found( '/usr/gnu/bin/' . $binary, $switch ) ) {
		return '/usr/gnu/bin/' . $binary;
	} elseif ( ewww_image_optimizer_tool_found( '/usr/syno/bin/' . $binary, $switch ) ) { // for synology diskstation OS
		return '/usr/syno/bin/' . $binary;
	} else {
		return '';
	}
}

/**
 * Process an image.
 *
 * Returns an array of the $file, $results, $converted to tell us if an image changes formats, and the $original file if it did.
 *
 * @param   string $file		Full absolute path to the image file
 * @param   int $gallery_type		1=wordpress, 2=nextgen, 3=flagallery, 4=aux_images, 5=image editor, 6=imagestore, 7=retina
 * @param   boolean $converted		tells us if this is a resize and the full image was converted to a new format
 * @param   boolean $new		tells the optimizer that this is a new image, so it should attempt conversion regardless of previous results
 * @param   boolean $fullsize		tells the optimizer this is a full size image
 * @returns array
 */
function ewww_image_optimizer( $file, $gallery_type = 4, $converted = false, $new = false, $fullsize = false ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// if the plugin gets here without initializing, we need to run through some things first
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_CLOUD' ) ) {
		ewww_image_optimizer_init();
	}
	$bypass_optimization = apply_filters( 'ewww_image_optimizer_bypass', false, $file );
	if ( true === $bypass_optimization ) {
		// tell the user optimization was skipped
		$msg = __( "Optimization skipped", EWWW_IMAGE_OPTIMIZER_DOMAIN );
		ewwwio_debug_message( "optimization bypassed: $file" );
		// send back the above message
		return array( false, $msg, $converted, $file );
	}
	// initialize the original filename 
	$original = $file;
	$result = '';
	// check that the file exists
	if ( FALSE === file_exists( $file ) ) {
		// tell the user we couldn't find the file
		$msg = sprintf( __( 'Could not find %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $file );
		ewwwio_debug_message( "file doesn't appear to exist: $file" );
		// send back the above message
		return array( false, $msg, $converted, $original );
	}
	// check that the file is writable
	if ( FALSE === is_writable( $file ) ) {
		// tell the user we can't write to the file
		$msg = sprintf( __( '%s is not writable', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $file );
		ewwwio_debug_message( "couldn't write to the file $file" );
		// send back the above message
		return array( false, $msg, $converted, $original );
	}
	if ( function_exists( 'fileperms' ) )
		$file_perms = substr( sprintf( '%o', fileperms( $file ) ), -4 );
	$file_owner = 'unknown';
	$file_group = 'unknown';
	if (function_exists('posix_getpwuid')) {
		$file_owner = posix_getpwuid(fileowner($file));
		$file_owner = $file_owner['name'];
	}
	if (function_exists('posix_getgrgid')) {
		$file_group = posix_getgrgid(filegroup($file));
		$file_group = $file_group['name'];
	}
	ewwwio_debug_message( "permissions: $file_perms, owner: $file_owner, group: $file_group" );
	$type = ewww_image_optimizer_mimetype($file, 'i');
	if ( strpos( $type, 'image' ) === FALSE ) {
		ewwwio_debug_message( 'could not find any functions for mimetype detection' );
		//otherwise we store an error message since we couldn't get the mime-type
		return array( false, __( 'Unknown type: ' . $type, EWWW_IMAGE_OPTIMIZER_DOMAIN ), $converted, $original );
		$msg = __('Missing finfo_file(), getimagesize() and mime_content_type() PHP functions', EWWW_IMAGE_OPTIMIZER_DOMAIN);
		return array(false, $msg, $converted, $original);
	}
	if ( ! EWWW_IMAGE_OPTIMIZER_CLOUD ) {
		// check to see if 'nice' exists
		$nice = ewww_image_optimizer_find_nix_binary( 'nice', 'n' );
		if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_NOEXEC' ) ) {
			// Check if exec is disabled
			if( ewww_image_optimizer_exec_check() ) {
				define( 'EWWW_IMAGE_OPTIMIZER_NOEXEC', true );
				ewwwio_debug_message( 'exec seems to be disabled' );
				ewww_image_optimizer_disable_tools();
				// otherwise, query the php settings for safe mode
			} elseif ( ewww_image_optimizer_safemode_check() ) {
				define( 'EWWW_IMAGE_OPTIMIZER_NOEXEC', true );
				ewwwio_debug_message( 'safe mode appears to be enabled' );
				ewww_image_optimizer_disable_tools();
			} else {
				define( 'EWWW_IMAGE_OPTIMIZER_NOEXEC', false );
			}
		}
	}
	// if the user has disabled the utility checks
	if ( EWWW_IMAGE_OPTIMIZER_CLOUD ) {
		$skip_jpegtran_check = true;
		$skip_optipng_check = true;
		$skip_gifsicle_check = true;
		$skip_pngout_check = true;
	} else {
		// otherwise we set the variables to false
		$skip_jpegtran_check = false;
		$skip_optipng_check = false;
		$skip_gifsicle_check = false;
		$skip_pngout_check = false;
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_jpg' ) ) {
		$skip_jpegtran_check = true;
	}	
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_png' ) ) {
		$skip_optipng_check = true;
		$skip_pngout_check = true;
	}	
	if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_gif')) {
		$skip_gifsicle_check = true;
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_metadata_skip_full' ) && $fullsize ) {
		$keep_metadata = true;
	} else {
		$keep_metadata = false;
	}
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_lossy_skip_full' ) && $fullsize ) {
		$skip_lossy = true;
	} else {
		$skip_lossy = false;
	}
	if ( ini_get( 'max_execution_time' ) < 90 && ewww_image_optimizer_stl_check() ) {
		set_time_limit( 0 );
	}
	// if the full-size image was converted
	if ($converted) {
		ewwwio_debug_message( 'full-size image was converted, need to rebuild filename for meta' );
		$filenum = $converted;
		// grab the file extension
		preg_match('/\.\w+$/', $file, $fileext);
		// strip the file extension
		$filename = str_replace($fileext[0], '', $file);
		// grab the dimensions
		preg_match('/-\d+x\d+(-\d+)*$/', $filename, $fileresize);
		// strip the dimensions
		$filename = str_replace($fileresize[0], '', $filename);
		// reconstruct the filename with the same increment (stored in $converted) as the full version
		$refile = $filename . '-' . $filenum . $fileresize[0] . $fileext[0];
		// rename the file
		rename($file, $refile);
		ewwwio_debug_message( "moved $file to $refile" );
		// and set $file to the new filename
		$file = $refile;
		$original = $file;
	}
	// get the original image size
	$orig_size = filesize( $file );
	ewwwio_debug_message( "original filesize: $orig_size" );
	if ( $orig_size < ewww_image_optimizer_get_option( 'ewww_image_optimizer_skip_size' ) ) {
		// tell the user optimization was skipped
		$msg = __( "Optimization skipped", EWWW_IMAGE_OPTIMIZER_DOMAIN );
		ewwwio_debug_message( "optimization bypassed due to filesize: $file" );
		// send back the above message
		return array(false, $msg, $converted, $file);
	}
	if ( $type == 'image/png' && ewww_image_optimizer_get_option( 'ewww_image_optimizer_skip_png_size' ) && $orig_size > ewww_image_optimizer_get_option( 'ewww_image_optimizer_skip_png_size' ) ) {
		// tell the user optimization was skipped
		$msg = __( "Optimization skipped", EWWW_IMAGE_OPTIMIZER_DOMAIN );
		ewwwio_debug_message( "optimization bypassed due to filesize: $file" );
		// send back the above message
		return array($file, $msg, $converted, $file);
	}
	// initialize $new_size with the original size, HOW ABOUT A ZERO...
	//$new_size = $orig_size;
	$new_size = 0;
	// set the optimization process to OFF
	$optimize = false;
	// toggle the convert process to ON
	$convert = true;
	// allow other plugins to mangle the image however they like prior to optimization
	do_action( 'ewww_image_optimizer_pre_optimization', $file, $type );
	// run the appropriate optimization/conversion for the mime-type
	switch( $type ) {
		case 'image/jpeg':
			$png_size = 0;
			// if jpg2png conversion is enabled, and this image is in the wordpress media library
			if ( ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpg_to_png' ) && $gallery_type == 1 ) || ! empty( $_GET['ewww_convert'] ) ) {
				// generate the filename for a PNG
				// if this is a resize version
				if ( $converted ) {
					// just change the file extension
					$pngfile = preg_replace( '/\.\w+$/', '.png', $file );
				// if this is a full size image
				} else {
					// get a unique filename for the png image
					list( $pngfile, $filenum ) = ewww_image_optimizer_unique_filename( $file, '.png' );
				}
			} else {
				// otherwise, set it to OFF
				$convert = false;
				$pngfile = '';
			}
			// check for previous optimization, so long as the force flag is on and this isn't a new image that needs converting
			if ( empty( $_REQUEST['ewww_force'] ) && ! ( $new && $convert ) ) {
				if ( $results_msg = ewww_image_optimizer_check_table( $file, $orig_size ) ) {
					return array( $file, $results_msg, $converted, $original );
				}
			}
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg')) {
				list($file, $converted, $result, $new_size) = ewww_image_optimizer_cloud_optimizer($file, $type, $convert, $pngfile, 'image/png', $skip_lossy);
				if ($converted) {
					$converted = $filenum;
					ewww_image_optimizer_webp_create( $file, $new_size, 'image/png', null, $orig_size != $new_size );
				} else {
					ewww_image_optimizer_webp_create( $file, $new_size, $type, null, $orig_size != $new_size );
				}
				break;
			}
			if ( $convert ) {
				$tools = ewww_image_optimizer_path_check(
					! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_jpegtran' ),
					! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_optipng' ),
					false,
					! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_pngout' ),
					ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_lossy' ),
					ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp' )
				);
			} else {
				//$tools = ewww_image_optimizer_path_check( true, false, false, false, false, ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp' ) );
				$tools = ewww_image_optimizer_path_check(
					! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_jpegtran' ),
					false,
					false,
					false,
					false,
					ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp' )
				);
			}
			// if jpegtran optimization is disabled
			if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_jpegtran' ) ) {
				// store an appropriate message in $result
				$result = sprintf( __( '%s is disabled', EWWW_IMAGE_OPTIMIZER_DOMAIN ), 'jpegtran' );
			// otherwise, if we aren't skipping the utility verification and jpegtran doesn't exist
			} elseif (!$skip_jpegtran_check && !$tools['JPEGTRAN']) {
				// store an appropriate message in $result
				$result = sprintf( __( '%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN ), '<em>jpegtran</em>' );
			// otherwise, things should be good, so...
			} else {
				// set the optimization process to ON
				$optimize = true;
			}
			// if optimization is turned ON
			if ($optimize && !ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_jpg')) {
				ewwwio_debug_message( 'attempting to optimize JPG...' );
				// generate temporary file-names:
				$tempfile = $file . ".tmp"; //non-progressive jpeg
				$progfile = $file . ".prog"; // progressive jpeg
				// check to see if we are supposed to strip metadata (badly named)
				if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpegtran_copy' ) && ! $keep_metadata){
					// don't copy metadata
					$copy_opt = 'none';
				} else {
					// copy all the metadata
					$copy_opt = 'all';
				}
				// run jpegtran - non-progressive
				exec( "$nice " . $tools['JPEGTRAN'] . " -copy $copy_opt -optimize -outfile " . ewww_image_optimizer_escapeshellarg( $tempfile ) . " " . ewww_image_optimizer_escapeshellarg( $file ) );
				// run jpegtran - progressive
				exec( "$nice " . $tools['JPEGTRAN'] . " -copy $copy_opt -optimize -progressive -outfile " . ewww_image_optimizer_escapeshellarg( $progfile ) . " " . ewww_image_optimizer_escapeshellarg( $file ) );
				// check the filesize of the non-progressive JPG
				$non_size = ewww_image_optimizer_filesize( $tempfile );
				// check the filesize of the progressive JPG
				$prog_size = ewww_image_optimizer_filesize( $progfile );
				ewwwio_debug_message( "optimized JPG (non-progresive) size: $non_size" );
				ewwwio_debug_message( "optimized JPG (progresive) size: $prog_size" );
				if ( $non_size === false || $prog_size === false ) {
					$result = __( 'Unable to write file', EWWW_IMAGE_OPTIMIZER_DOMAIN );
					$new_size = 0;
				} elseif ( ! $non_size || ! $prog_size) {
					$result = __( 'Optimization failed', EWWW_IMAGE_OPTIMIZER_DOMAIN );
					$new_size = 0;
				} else {
					// if the progressive file is bigger
					if ( $prog_size > $non_size ) {
						// store the size of the non-progessive JPG
						$new_size = $non_size;
						if ( is_file( $progfile ) ) {
							// delete the progressive file
							unlink( $progfile );
						}
					// if the progressive file is smaller or the same
					} else {
						// store the size of the progressive JPG
						$new_size = $prog_size;
						// replace the non-progressive with the progressive file
						rename($progfile, $tempfile);
					}
				}
				ewwwio_debug_message( "optimized JPG size: $new_size" );
				// if the best-optimized is smaller than the original JPG, and we didn't create an empty JPG
				if ( $orig_size > $new_size && $new_size != 0 && ewww_image_optimizer_mimetype($tempfile, 'i') == $type ) {
					// replace the original with the optimized file
					rename($tempfile, $file);
					// store the results of the optimization
					$result = "$orig_size vs. $new_size";
				// if the optimization didn't produce a smaller JPG
				} else {
					if ( is_file( $tempfile ) ) {
						// delete the optimized file
						unlink($tempfile);
					}
					// store the results
					$result = 'unchanged';
					$new_size = $orig_size;
				}
			// if conversion and optimization are both turned OFF, finish the JPG processing
			} elseif (!$convert) {
				ewww_image_optimizer_webp_create( $file, $orig_size, $type, $tools['WEBP'], $orig_size != $new_size );
				break;
			}
			// if the conversion process is turned ON, or if this is a resize and the full-size was converted
			if ( $convert && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_jpg' ) ) {
				ewwwio_debug_message( "attempting to convert JPG to PNG: $pngfile" );
				if ( empty( $new_size ) ) {
					$new_size = $orig_size;
				}
				// retrieve version info for ImageMagick
				$convert_path = ewww_image_optimizer_find_nix_binary( 'convert', 'i' );
				// convert the JPG to PNG
				if ( ewww_image_optimizer_gmagick_support() ) {
					try {
						$gmagick = new Gmagick( $file );
						$gmagick->stripimage();
						$gmagick->setimageformat( 'PNG' );
						$gmagick->writeimage( $pngfile );
					} catch ( Exception $gmagick_error ) {
						ewwwio_debug_message( $gmagick_error->getMessage() );
					}
					$png_size = ewww_image_optimizer_filesize( $pngfile );
				}
				if ( ! $png_size && ewww_image_optimizer_imagick_support() ) {
					try {
						$imagick = new Imagick( $file );
						$imagick->stripImage();
						$imagick->setImageFormat( 'PNG' );
						$imagick->writeImage( $pngfile );
					} catch ( Exception $imagick_error ) {
						ewwwio_debug_message( $imagick_error->getMessage() );
					}
					$png_size = ewww_image_optimizer_filesize( $pngfile );
				}
				if ( ! $png_size && ! empty( $convert_path ) ) {
					ewwwio_debug_message( 'converting with ImageMagick' );
					exec( $convert_path . " " . ewww_image_optimizer_escapeshellarg( $file ) . " -strip " . ewww_image_optimizer_escapeshellarg( $pngfile ) );
					$png_size = ewww_image_optimizer_filesize( $pngfile );
				}
				if ( ! $png_size && ewww_image_optimizer_gd_support() ) {
					ewwwio_debug_message( 'converting with GD' );
					imagepng( imagecreatefromjpeg( $file ), $pngfile );
					$png_size = ewww_image_optimizer_filesize( $pngfile );
				}
				// if lossy optimization is ON and full-size exclusion is not active
				if (ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy') && $tools['PNGQUANT'] && !$skip_lossy ) {
					ewwwio_debug_message( 'attempting lossy reduction' );
					exec( "$nice " . $tools['PNGQUANT'] . " " . ewww_image_optimizer_escapeshellarg( $pngfile ) );
					$quantfile = preg_replace('/\.\w+$/', '-fs8.png', $pngfile);
					if ( is_file( $quantfile ) && filesize( $pngfile ) > filesize( $quantfile ) ) {
						ewwwio_debug_message( "lossy reduction is better: original - " . filesize( $pngfile ) . " vs. lossy - " . filesize( $quantfile ) );
						rename( $quantfile, $pngfile );
					} elseif ( is_file( $quantfile ) ) {
						ewwwio_debug_message( "lossy reduction is worse: original - " . filesize( $pngfile ) . " vs. lossy - " . filesize( $quantfile ) );
						unlink( $quantfile );
					} else {
						ewwwio_debug_message( 'pngquant did not produce any output' );
					}
				}
				// if optipng isn't disabled
				if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng')) {
					// retrieve the optipng optimization level
					$optipng_level = ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level');
					if (ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpegtran_copy' ) && preg_match( '/0.7/', ewww_image_optimizer_tool_found( $tools['OPTIPNG'], 'o' ) ) && ! $keep_metadata ) {
						$strip = '-strip all ';
					} else {
						$strip = '';
					}
					// if the PNG file was created
					if ( file_exists( $pngfile ) ) {
						ewwwio_debug_message( 'optimizing converted PNG with optipng' );
						// run optipng on the new PNG
						exec( "$nice " . $tools['OPTIPNG'] . " -o$optipng_level -quiet $strip " . ewww_image_optimizer_escapeshellarg( $pngfile ) );
					}
				}
				// if pngout isn't disabled
				if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_pngout' ) ) {
					// retrieve the pngout optimization level
					$pngout_level = ewww_image_optimizer_get_option( 'ewww_image_optimizer_pngout_level' );
					// if the PNG file was created
					if ( file_exists( $pngfile ) ) {
						ewwwio_debug_message( 'optimizing converted PNG with pngout' );
						// run pngout on the new PNG
						exec( "$nice " . $tools['PNGOUT'] . " -s$pngout_level -q " . ewww_image_optimizer_escapeshellarg( $pngfile ) );
					}
				}
				$png_size = ewww_image_optimizer_filesize( $pngfile );
				ewwwio_debug_message( "converted PNG size: $png_size" );
				// if the PNG is smaller than the original JPG, and we didn't end up with an empty file
				if ( $new_size > $png_size && $png_size != 0 && ewww_image_optimizer_mimetype($pngfile, 'i') == 'image/png' ) {
					ewwwio_debug_message( "converted PNG is better: $png_size vs. $new_size" );
					// store the size of the converted PNG
					$new_size = $png_size;
					// check to see if the user wants the originals deleted
					if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_delete_originals' ) == TRUE ) {
						// delete the original JPG
						unlink( $file );
					}
					// store the location of the PNG file
					$file = $pngfile;
					// let webp know what we're dealing with now
					$type = 'image/png';
					// successful conversion and we store the increment
					$converted = $filenum;
				} else {
					ewwwio_debug_message( 'converted PNG is no good' );
					// otherwise delete the PNG
					$converted = FALSE;
					if ( is_file( $pngfile ) ) {
						unlink ( $pngfile );
					}
				}
			}
			ewww_image_optimizer_webp_create( $file, $new_size, $type, $tools['WEBP'], $orig_size != $new_size );
			break;
		case 'image/png':
			$jpg_size = 0;
			// png2jpg conversion is turned on, and the image is in the wordpress media library
			if ( ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_to_jpg' ) || ! empty( $_GET['ewww_convert'] ) ) && $gallery_type == 1 && ! $skip_lossy ) {
				ewwwio_debug_message( 'PNG to JPG conversion turned on' );
				// if the user set a fill background for transparency
				$background = '';
				if ($background = ewww_image_optimizer_jpg_background()) {
					// set background color for GD
					$r = hexdec('0x' . strtoupper(substr($background, 0, 2)));
                                        $g = hexdec('0x' . strtoupper(substr($background, 2, 2)));
					$b = hexdec('0x' . strtoupper(substr($background, 4, 2)));
					// set the background flag for 'convert'
					$background = "-background " . '"' . "#$background" . '"';
				} else {
					$r = '';
					$g = '';
					$b = '';
				}
				// if the user manually set the JPG quality
				if ($quality = ewww_image_optimizer_jpg_quality()) {
					// set the quality for GD
					$gquality = $quality;
					// set the quality flag for 'convert'
					$cquality = "-quality $quality";
				} else {
					$cquality = '';
					$gquality = '92';
				}
				// if this is a resize version
				if ($converted) {
					// just replace the file extension with a .jpg
					$jpgfile = preg_replace('/\.\w+$/', '.jpg', $file);
				// if this is a full version
				} else {
					// construct the filename for the new JPG
					list($jpgfile, $filenum) = ewww_image_optimizer_unique_filename($file, '.jpg');
				}
			} else {
				ewwwio_debug_message( 'PNG to JPG conversion turned off' );
				// turn the conversion process OFF
				$convert = false;
				$jpgfile = '';
				$r = null;
				$g = null;
				$b = null;
				$gquality = null;
			}
			// check for previous optimization, so long as the force flag is on and this isn't a new image that needs converting
			if ( empty( $_REQUEST['ewww_force'] ) && ! ( $new && $convert ) ) {
				if ( $results_msg = ewww_image_optimizer_check_table( $file, $orig_size ) ) {
					return array( $file, $results_msg, $converted, $original );
				}
			}
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_png')) {
				list($file, $converted, $result, $new_size) = ewww_image_optimizer_cloud_optimizer($file, $type, $convert, $jpgfile, 'image/jpeg', $skip_lossy, array('r' => $r, 'g' => $g, 'b' => $b, 'quality' => $gquality));
				if ($converted) {
					$converted = $filenum;
					ewww_image_optimizer_webp_create( $file, $new_size, 'image/jpeg', null, $orig_size != $new_size );
				} else {
					ewww_image_optimizer_webp_create( $file, $new_size, $type, null, $orig_size != $new_size );
				}
				break;
			}
			if ( $convert ) {
				$tools = ewww_image_optimizer_path_check(
					! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_jpegtran' ),
					! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_optipng' ),
					false,
					! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_pngout' ),
					ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_lossy' ),
					ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp' )
				);
//				$tools = ewww_image_optimizer_path_check(true, true, false, true, ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy'), ewww_image_optimizer_get_option('ewww_image_optimizer_webp'));
			} else {
				$tools = ewww_image_optimizer_path_check(
					false,
					! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_optipng' ),
					false,
					! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_pngout' ),
					ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_lossy' ),
					ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp' )
				);
//				$tools = ewww_image_optimizer_path_check(false, true, false, true, ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy'), ewww_image_optimizer_get_option('ewww_image_optimizer_webp'));
			}
			// if pngout and optipng are disabled
			if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_optipng' ) && ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_pngout' ) ) {
				// tell the user all PNG tools are disabled
				$result = __( 'png tools are disabled', EWWW_IMAGE_OPTIMIZER_DOMAIN );
			// if the utility checking is on, optipng is enabled, but optipng cannot be found
			} elseif ( ! $skip_optipng_check && ! $tools['OPTIPNG'] && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_optipng' ) ) {
				// tell the user optipng is missing
				$result = sprintf( __( '%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN ), '<em>optipng</em>' );
			// if the utility checking is on, pngout is enabled, but pngout cannot be found
			} elseif ( ! $skip_pngout_check && ! $tools['PNGOUT'] && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_pngout' ) ) {
				// tell the user pngout is missing
				$result = sprintf( __( '%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN ), '<em>pngout</em>' );
			} else {
				// turn optimization on if we made it through all the checks
				$optimize = true;
			}
			// if optimization is turned on
			if ( $optimize ) {
				// if lossy optimization is ON and full-size exclusion is not active
				if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_lossy' ) && $tools['PNGQUANT'] && ! $skip_lossy ) {
					ewwwio_debug_message( 'attempting lossy reduction' );
					exec( "$nice " . $tools['PNGQUANT'] . " " . ewww_image_optimizer_escapeshellarg( $file ) );
					$quantfile = preg_replace( '/\.\w+$/', '-fs8.png', $file );
					if ( is_file( $quantfile ) && filesize( $file ) > filesize( $quantfile ) && ewww_image_optimizer_mimetype($quantfile, 'i') == $type ) {
						ewwwio_debug_message( "lossy reduction is better: original - " . filesize( $file ) . " vs. lossy - " . filesize( $quantfile ) );
						rename( $quantfile, $file );
					} elseif ( is_file( $quantfile ) ) {
						ewwwio_debug_message( "lossy reduction is worse: original - " . filesize( $file ) . " vs. lossy - " . filesize( $quantfile ) );
						unlink( $quantfile );
					} else {
						ewwwio_debug_message( 'pngquant did not produce any output' );
					}
				}
				$tempfile = $file . '.tmp.png';
				copy( $file, $tempfile );
				// if optipng is enabled
				if( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_optipng' ) ) {
					// retrieve the optimization level for optipng
					$optipng_level = ewww_image_optimizer_get_option( 'ewww_image_optimizer_optipng_level' );
					if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpegtran_copy' ) && preg_match( '/0.7/', ewww_image_optimizer_tool_found( $tools['OPTIPNG'], 'o' ) ) && ! $keep_metadata ) {
						$strip = '-strip all ';
					} else {
						$strip = '';
					}
					// run optipng on the PNG file
					exec( "$nice " . $tools['OPTIPNG'] . " -o$optipng_level -quiet $strip " . ewww_image_optimizer_escapeshellarg( $tempfile ) );
				}
				// if pngout is enabled
				if( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_pngout' ) ) {
					// retrieve the optimization level for pngout
					$pngout_level = ewww_image_optimizer_get_option( 'ewww_image_optimizer_pngout_level' );
					// run pngout on the PNG file
					exec( "$nice " . $tools['PNGOUT'] . " -s$pngout_level -q " . ewww_image_optimizer_escapeshellarg( $tempfile ) );
				}
				// retrieve the filesize of the temporary PNG
				$new_size = ewww_image_optimizer_filesize( $tempfile );
				// if the new PNG is smaller
				if ( $orig_size > $new_size && $new_size != 0 && ewww_image_optimizer_mimetype( $tempfile, 'i' ) == $type ) {
					// replace the original with the optimized file
					rename( $tempfile, $file );
					// store the results of the optimization
					$result = "$orig_size vs. $new_size";
				// if the optimization didn't produce a smaller PNG
				} else {
					if ( is_file( $tempfile ) ) {
						// delete the optimized file
						unlink( $tempfile );
					}
					// store the results
					$result = 'unchanged';
					$new_size = $orig_size;
				}
			// if conversion and optimization are both disabled we are done here
			} elseif (!$convert) {
				ewwwio_debug_message( 'calling webp, but neither convert or optimize' );
				ewww_image_optimizer_webp_create( $file, $orig_size, $type, $tools['WEBP'], $orig_size != $new_size );
				break;
			}
			// retrieve the new filesize of the PNG
			$new_size = ewww_image_optimizer_filesize( $file );
			// if conversion is on and the PNG doesn't have transparency or the user set a background color to replace transparency
			if ( $convert && ( ! ewww_image_optimizer_png_alpha( $file ) || ewww_image_optimizer_jpg_background() ) ) {
				ewwwio_debug_message( "attempting to convert PNG to JPG: $jpgfile" );
				if ( empty( $new_size ) ) {
					$new_size = $orig_size;
				}
				// retrieve version info for ImageMagick
				$convert_path = ewww_image_optimizer_find_nix_binary( 'convert', 'i' );
				$magick_background = ewww_image_optimizer_jpg_background();
				if ( empty( $magick_background ) ) {
					$magick_background = '000000';
				}
				// convert the PNG to a JPG with all the proper options
				if ( ewww_image_optimizer_gmagick_support() ) {
					try {
						if ( ewww_image_optimizer_png_alpha( $file ) ) {
							$gmagick_overlay = new Gmagick( $file );
							$gmagick = new Gmagick();
							$gmagick->newimage( $gmagick_overlay->getimagewidth(), $gmagick_overlay->getimageheight(), '#' . $magick_background );
							$gmagick->compositeimage( $gmagick_overlay, 1, 0, 0 );
						} else {
							$gmagick = new Gmagick( $file );
						}
						$gmagick->setimageformat( 'JPG' );
						$gmagick->setcompressionquality( $gquality );
						$gmagick->writeimage( $jpgfile );
					} catch ( Exception $gmagick_error ) {
						ewwwio_debug_message( $gmagick_error->getMessage() );
					}
					$jpg_size = ewww_image_optimizer_filesize( $jpgfile );
				}
				if ( ! $jpg_size && ewww_image_optimizer_imagick_support() ) {
					try {
						$imagick = new Imagick( $file );
						if ( ewww_image_optimizer_png_alpha( $file ) ) {
							$imagick->setImageBackgroundColor( new ImagickPixel( '#' . $magick_background ) );
							$imagick->setImageAlphaChannel( 11 );
						}
						$imagick->setImageFormat( 'JPG' );
						$imagick->setCompressionQuality( $gquality );
						$imagick->writeImage( $jpgfile );
					} catch ( Exception $imagick_error ) {
						ewwwio_debug_message( $imagick_error->getMessage() );
					}
					$jpg_size = ewww_image_optimizer_filesize( $jpgfile );
				} 
				if ( ! $jpg_size && ! empty( $convert_path ) ) { 
					ewwwio_debug_message( 'converting with ImageMagick' );
					ewwwio_debug_message( "using command: $convert_path $background -alpha remove $cquality $file $jpgfile" );
					exec ( "$convert_path $background -alpha remove $cquality " . ewww_image_optimizer_escapeshellarg( $file ) . " " . ewww_image_optimizer_escapeshellarg( $jpgfile ) );
					$jpg_size = ewww_image_optimizer_filesize( $jpgfile );
				}
				if ( ! $jpg_size && ewww_image_optimizer_gd_support() ) {
					ewwwio_debug_message( 'converting with GD' );
					// retrieve the data from the PNG
					$input = imagecreatefrompng($file);
					// retrieve the dimensions of the PNG
					list($width, $height) = getimagesize($file);
					// create a new image with those dimensions
					$output = imagecreatetruecolor($width, $height);
					if ($r === '') {
						$r = 255;
						$g = 255;
						$b = 255;
					}
					// allocate the background color
					$rgb = imagecolorallocate($output, $r, $g, $b);
					// fill the new image with the background color 
					imagefilledrectangle($output, 0, 0, $width, $height, $rgb);
					// copy the original image to the new image
					imagecopy($output, $input, 0, 0, 0, 0, $width, $height);
					// output the JPG with the quality setting
					imagejpeg($output, $jpgfile, $gquality);
				}
				$jpg_size = ewww_image_optimizer_filesize( $jpgfile );
				if ($jpg_size) {
					ewwwio_debug_message( "converted JPG filesize: $jpg_size" );
				} else {
					ewwwio_debug_message( 'unable to convert to JPG' );
				}
				// next we need to optimize that JPG if jpegtran is enabled
				if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_jpegtran' ) && file_exists( $jpgfile ) ) {
					// generate temporary file-names:
					$tempfile = $jpgfile . ".tmp"; //non-progressive jpeg
					$progfile = $jpgfile . ".prog"; // progressive jpeg
					// check to see if we are supposed to strip metadata (badly named)
					if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpegtran_copy' ) && ! $keep_metadata ){
						// don't copy metadata
						$copy_opt = 'none';
					} else {
						// copy all the metadata
						$copy_opt = 'all';
					}
					// run jpegtran - non-progressive
					exec( "$nice " . $tools['JPEGTRAN'] . " -copy $copy_opt -optimize -outfile " . ewww_image_optimizer_escapeshellarg( $tempfile ) . " " . ewww_image_optimizer_escapeshellarg( $jpgfile ) );
					// run jpegtran - progressive
					exec( "$nice " . $tools['JPEGTRAN'] . " -copy $copy_opt -optimize -progressive -outfile " . ewww_image_optimizer_escapeshellarg( $progfile ) . " " . ewww_image_optimizer_escapeshellarg( $jpgfile ) );
					// check the filesize of the non-progressive JPG
					$non_size = ewww_image_optimizer_filesize( $tempfile );
					ewwwio_debug_message( "non-progressive JPG filesize: $non_size" );
					// check the filesize of the progressive JPG
					$prog_size = ewww_image_optimizer_filesize( $progfile );
					ewwwio_debug_message( "progressive JPG filesize: $prog_size" );
					// if the progressive file is bigger
					if ($prog_size > $non_size) {
						// store the size of the non-progessive JPG
						$opt_jpg_size = $non_size;
						if (is_file($progfile)) {
							// delete the progressive file
							unlink($progfile);
						}
						ewwwio_debug_message( 'keeping non-progressive JPG' );
					// if the progressive file is smaller or the same
					} else {
						// store the size of the progressive JPG
						$opt_jpg_size = $prog_size;
						// replace the non-progressive with the progressive file
						rename( $progfile, $tempfile );
						ewwwio_debug_message( 'keeping progressive JPG' );
					}
					// if the best-optimized is smaller than the original JPG, and we didn't create an empty JPG
					if ( $jpg_size > $opt_jpg_size && $opt_jpg_size != 0 ) {
						// replace the original with the optimized file
						rename( $tempfile, $jpgfile );
						// store the size of the optimized JPG
						$jpg_size = $opt_jpg_size;
						ewwwio_debug_message( 'optimized JPG was smaller than un-optimized version' );
					// if the optimization didn't produce a smaller JPG
					} elseif ( is_file( $tempfile ) ) {
						// delete the optimized file
						unlink( $tempfile );
					}
				} 
				ewwwio_debug_message( "converted JPG size: $jpg_size" );
				// if the new JPG is smaller than the original PNG
				if ( $new_size > $jpg_size && $jpg_size != 0 && ewww_image_optimizer_mimetype($jpgfile, 'i') == 'image/jpeg' ) {
					// store the size of the JPG as the new filesize
					$new_size = $jpg_size;
					// if the user wants originals delted after a conversion
					if (ewww_image_optimizer_get_option('ewww_image_optimizer_delete_originals') == TRUE) {
						// delete the original PNG
						unlink($file);
					}
					// update the $file location to the new JPG
					$file = $jpgfile;
					// let webp know what we're dealing with now
					$type = 'image/jpeg';
					// successful conversion, so we store the increment
					$converted = $filenum;
				} else {
					$converted = FALSE;
					if (is_file($jpgfile)) {
						// otherwise delete the new JPG
						unlink ($jpgfile);
					}
				}
			}
			ewww_image_optimizer_webp_create( $file, $new_size, $type, $tools['WEBP'], $orig_size != $new_size );
			break;
		case 'image/gif':
			// if gif2png is turned on, and the image is in the wordpress media library
			if ((ewww_image_optimizer_get_option('ewww_image_optimizer_gif_to_png') && $gallery_type == 1) || !empty($_GET['ewww_convert'])) {
				// generate the filename for a PNG
				// if this is a resize version
				if ($converted) {
					// just change the file extension
					$pngfile = preg_replace('/\.\w+$/', '.png', $file);
				// if this is the full version
				} else {
					// construct the filename for the new PNG
					list($pngfile, $filenum) = ewww_image_optimizer_unique_filename($file, '.png');
				}
			} else {
				// turn conversion OFF
				$convert = false;
				$pngfile = '';
			}
			// check for previous optimization, so long as the force flag is on and this isn't a new image that needs converting
			if ( empty( $_REQUEST['ewww_force'] ) && ! ( $new && $convert ) ) {
				if ( $results_msg = ewww_image_optimizer_check_table( $file, $orig_size ) ) {
					return array( $file, $results_msg, $converted, $original );
				}
			}
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_cloud_gif')) {
				list($file, $converted, $result, $new_size) = ewww_image_optimizer_cloud_optimizer($file, $type, $convert, $pngfile, 'image/png', $skip_lossy);
				if ($converted) {
					$converted = $filenum;
					ewww_image_optimizer_webp_create( $file, $new_size, 'image/png', null, $orig_size != $new_size ); 
 				}
				break;
			}
			if ( $convert ) {
				$tools = ewww_image_optimizer_path_check(
					false,
					! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_optipng' ),
					! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_gifsicle' ),
					! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_pngout' ),
					ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_lossy' ),
					ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp' )
				);
//				$tools = ewww_image_optimizer_path_check(false, true, true, true, ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy'), ewww_image_optimizer_get_option('ewww_image_optimizer_webp'));
			} else {
				$tools = ewww_image_optimizer_path_check(
					false,
					false,
					! ewww_image_optimizer_get_option( 'ewww_image_optimizer_disable_gifsicle' ),
					false,
					false,
					false
				);
//				$tools = ewww_image_optimizer_path_check(false, false, true, false, false, false);
			}
			// if gifsicle is disabled
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_disable_gifsicle')) {
				// return an appropriate message
				$result = sprintf(__('%s is disabled', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'gifsicle');
			// if utility checking is on, and gifsicle is not installed
			} elseif (!$skip_gifsicle_check && !$tools['GIFSICLE']) {
				// return an appropriate message
				$result = sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>gifsicle</em>');
			} else {
				// otherwise, turn optimization ON
				$optimize = true;
			}
			// if optimization is turned ON
			if ($optimize) {
				$tempfile = $file . '.tmp'; //temporary GIF output
				// run gifsicle on the GIF
				exec( "$nice " . $tools['GIFSICLE'] . " -O3 --careful -o $tempfile " . ewww_image_optimizer_escapeshellarg( $file ) );
					// retrieve the filesize of the temporary GIF
					$new_size = ewww_image_optimizer_filesize( $tempfile );
					// if the new GIF is smaller
					if ($orig_size > $new_size && $new_size != 0 && ewww_image_optimizer_mimetype($tempfile, 'i') == $type ) {
						// replace the original with the optimized file
						rename($tempfile, $file);
						// store the results of the optimization
						$result = "$orig_size vs. $new_size";
					// if the optimization didn't produce a smaller GIF
					} else {
						if (is_file($tempfile)) {
							// delete the optimized file
							unlink($tempfile);
						}
						// store the results
						$result = 'unchanged';
						$new_size = $orig_size;
					}
			// if conversion and optimization are both turned OFF, we are done here
			} elseif (!$convert) {
				break;
			}
			// get the new filesize for the GIF
			$new_size = ewww_image_optimizer_filesize($file);
			// if conversion is ON and the GIF isn't animated
			if ($convert && !ewww_image_optimizer_is_animated($file)) {
				if (empty($new_size)) {
					$new_size = $orig_size;
				}
				// if optipng is enabled
				if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng') && $tools['OPTIPNG']) {
					// retrieve the optipng optimization level
					$optipng_level = ewww_image_optimizer_get_option('ewww_image_optimizer_optipng_level');
					if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_jpegtran_copy' ) && preg_match( '/0.7/', ewww_image_optimizer_tool_found( $tools['OPTIPNG'], 'o' ) ) && ! $keep_metadata ) {
						$strip = '-strip all ';
					} else {
						$strip = '';
					}
					// run optipng on the GIF file
					exec( "$nice " . $tools['OPTIPNG'] . " -out " . ewww_image_optimizer_escapeshellarg( $pngfile ) . " -o$optipng_level -quiet $strip " . ewww_image_optimizer_escapeshellarg( $file ) );
				}
				// if pngout is enabled
				if (!ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout') && $tools['PNGOUT']) {
					// retrieve the pngout optimization level
					$pngout_level = ewww_image_optimizer_get_option('ewww_image_optimizer_pngout_level');
					// if $pngfile exists (which means optipng was run already)
					if (file_exists($pngfile)) {
						// run pngout on the PNG file
						exec( "$nice " . $tools['PNGOUT'] . " -s$pngout_level -q " . ewww_image_optimizer_escapeshellarg( $pngfile ) );
					} else {
						// run pngout on the GIF file
						exec( "$nice " . $tools['PNGOUT'] . " -s$pngout_level -q " . ewww_image_optimizer_escapeshellarg( $file ) . " " . ewww_image_optimizer_escapeshellarg( $pngfile ) );
					}
				}
					// retrieve the filesize of the PNG
					$png_size = ewww_image_optimizer_filesize($pngfile);
					// if the new PNG is smaller than the original GIF
					if ( $new_size > $png_size && $png_size != 0 && ewww_image_optimizer_mimetype( $pngfile, 'i' ) == 'image/png' ) {
						// store the PNG size as the new filesize
						$new_size = $png_size;
						// if the user wants original GIFs deleted after successful conversion
						if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_delete_originals' ) == TRUE ) {
							// delete the original GIF
							unlink( $file );
						}
						// update the $file location with the new PNG
						$file = $pngfile;
						// let webp know what we're dealing with now
						$type = 'image/png';
						// normally this would be at the end of the section, but we only want to do webp if the image was successfully converted to a png
						ewww_image_optimizer_webp_create( $file, $new_size, $type, $tools['WEBP'], $orig_size != $new_size );
						// successful conversion (for now), so we store the increment
						$converted = $filenum;
					} else {
						$converted = FALSE;
						if ( is_file( $pngfile ) ) {
							unlink( $pngfile );
						}
					}
			}
			break;
		default:
			// if not a JPG, PNG, or GIF, tell the user we don't work with strangers
			return array( $file, __( 'Unknown type: ' . $type, EWWW_IMAGE_OPTIMIZER_DOMAIN ), $converted, $original );
	}
	// allow other plugins to run operations on the images after optimization.
	// NOTE: it is recommended to do any image modifications prior to optimization, otherwise you risk un-optimizing your images here.
	do_action( 'ewww_image_optimizer_post_optimization', $file, $type );
	// if their cloud api license limit has been exceeded
	if ( $result == 'exceeded' ) {
		return array( $file, __( 'License exceeded', EWWW_IMAGE_OPTIMIZER_DOMAIN ), $converted, $original );
	}
	if ( ! empty( $new_size ) ) {
		$results_msg = ewww_image_optimizer_update_table( $file, $new_size, $orig_size, $new );
		ewwwio_memory( __FUNCTION__ );
		return array( $file, $results_msg, $converted, $original );
	}
	ewwwio_memory( __FUNCTION__ );
	// otherwise, send back the filename, the results (some sort of error message), the $converted flag, and the name of the original image
	return array( $file, $result, $converted, $original );
}

// creates webp images alongside JPG and PNG files
// needs a filename, the filesize, mimetype, and the path to the cwebp binary
function ewww_image_optimizer_webp_create( $file, $orig_size, $type, $tool, $recreate = false ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// change the file extension
	$webpfile = $file . '.webp';
	if ( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_webp' ) ) { 
		return;
	} elseif ( is_file( $webpfile ) && empty( $_REQUEST['ewww_force'] ) && ! $recreate ) {
		ewwwio_debug_message( 'webp file exists, not forcing or recreating' );
		return;
	}
	if ( empty( $tool ) ) {
		ewww_image_optimizer_cloud_optimizer( $file, $type, false, $webpfile, 'image/webp' );
	} else {
		// check to see if 'nice' exists
		$nice = ewww_image_optimizer_find_nix_binary( 'nice', 'n' );
		switch( $type ) {
			case 'image/jpeg':
				exec( "$nice " . $tool . " -q  85 -quiet " . ewww_image_optimizer_escapeshellarg( $file ) . " -o " . ewww_image_optimizer_escapeshellarg( $webpfile ) . ' 2>&1', $cli_output );
				break;
			case 'image/png':
				exec( "$nice " . $tool . " -lossless -quiet " . ewww_image_optimizer_escapeshellarg( $file ) . " -o " . ewww_image_optimizer_escapeshellarg( $webpfile ) . ' 2>&1', $cli_output );
				break;
		}
	}
	$webp_size = ewww_image_optimizer_filesize( $webpfile );
	ewwwio_debug_message( "webp is $webp_size vs. $type is $orig_size" );
	if ( is_file( $webpfile ) && $orig_size < $webp_size ) {
		ewwwio_debug_message( 'webp file was too big, deleting' );
		unlink( $webpfile );
	}
	ewwwio_memory( __FUNCTION__ );
}

// retrieves the pngout linux package with wget, unpacks it with tar, 
// copies the appropriate version to the plugin folder, and sends the user back where they came from
function ewww_image_optimizer_install_pngout() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	$permissions = apply_filters( 'ewww_image_optimizer_admin_permissions', '' );
	if ( FALSE === current_user_can( $permissions ) ) {
		wp_die( esc_html__( 'You do not have permission to install image optimizer utilities.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	if ( PHP_OS != 'WINNT' ) {
		$tar = ewww_image_optimizer_find_nix_binary( 'tar', 't' );
	}
	if ( empty( $tar ) && PHP_OS != 'WINNT' ) {
		$pngout_error = __( 'tar command not found', EWWW_IMAGE_OPTIMIZER_DOMAIN );
	}
	if ( PHP_OS == 'Linux' ) {
		$os_string = 'linux';
	}
	if ( PHP_OS == 'FreeBSD' ) {
		$os_string = 'bsd';
	}
	$latest = '20150319';
	if ( empty( $pngout_error ) ) {
		if ( PHP_OS == 'Linux' || PHP_OS == 'FreeBSD' ) {
			$download_result = ewww_image_optimizer_escapeshellarg( download_url( 'http://static.jonof.id.au/dl/kenutils/pngout-' . $latest . '-' . $os_string . '-static.tar.gz' ) );
			if ( is_wp_error( $download_result ) ) {
				$pngout_error = $download_result->get_error_message();
			} else {
				$arch_type = php_uname( 'm' );
				exec( "$tar xzf $download_result -C " . ewww_image_optimizer_escapeshellarg( EWWW_IMAGE_OPTIMIZER_BINARY_PATH ) . ' pngout-' . $latest . '-' . $os_string . '-static/' . $arch_type . '/pngout-static' );
				if ( file_exists( EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'pngout-' . $latest . '-' . $os_string . '-static/' . $arch_type . '/pngout-static' ) ) {
					if ( ! rename( EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'pngout-' . $latest . '-' . $os_string . '-static/' . $arch_type . '/pngout-static', EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout-static' ) ) {
						if ( empty( $pngout_error ) ) { $pngout_error = __( "could not move pngout", EWWW_IMAGE_OPTIMIZER_DOMAIN ); }
					}
					if ( ! chmod( EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout-static', 0755 ) ) {
						if ( empty( $pngout_error ) ) { $pngout_error = __( "could not set permissions", EWWW_IMAGE_OPTIMIZER_DOMAIN ); }
					}
					$pngout_version = ewww_image_optimizer_tool_found( ewww_image_optimizer_escapeshellarg( EWWW_IMAGE_OPTIMIZER_TOOL_PATH ) . 'pngout-static', 'p' );
				} else {
					$pngout_error = __( 'extraction of files failed', EWWW_IMAGE_OPTIMIZER_DOMAIN );
				}
			}
		}
		if ( PHP_OS == 'Darwin' ) {
			$download_result = ewww_image_optimizer_escapeshellarg( download_url( 'http://static.jonof.id.au/dl/kenutils/pngout-' . $latest . '-darwin.tar.gz' ) );
			if ( is_wp_error( $download_result ) ) {
				$pngout_error = $download_result->get_error_message();
			} else {
				exec( "$tar xzf $download_result -C " . ewww_image_optimizer_escapeshellarg( EWWW_IMAGE_OPTIMIZER_BINARY_PATH ) . ' pngout-' . $latest . '-darwin/pngout' );
				if ( file_exists( EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'pngout-' . $latest . '-' . $os_string . '-static/' . $arch_type . '/pngout-static' ) ) {
					if ( ! rename( EWWW_IMAGE_OPTIMIZER_BINARY_PATH . 'pngout-' . $latest . '-darwin/pngout', EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout-static' ) ) {
						if ( empty( $pngout_error ) ) { $pngout_error = __( 'could not move pngout', EWWW_IMAGE_OPTIMIZER_DOMAIN ); }
					}
					if ( ! chmod( EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout-static', 0755 ) ) {
						if ( empty( $pngout_error ) ) { $pngout_error = __( 'could not set permissions', EWWW_IMAGE_OPTIMIZER_DOMAIN ); }
					}
					$pngout_version = ewww_image_optimizer_tool_found( ewww_image_optimizer_escapeshellarg( EWWW_IMAGE_OPTIMIZER_TOOL_PATH ) . 'pngout-static', 'p' );
				} else {
					$pngout_error = __( 'extraction of files failed', EWWW_IMAGE_OPTIMIZER_DOMAIN );
				}
			}
		}
	}
	if (PHP_OS == 'WINNT') {
		$download_result = download_url( 'http://advsys.net/ken/util/pngout.exe' );
		if ( is_wp_error( $download_result ) ) {
			$pngout_error = $download_result->get_error_message();
		} else {
			if ( ! rename( $download_result, EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout.exe' ) ) {
				if ( empty( $pngout_error ) ) { $pngout_error = __( "could not move pngout", EWWW_IMAGE_OPTIMIZER_DOMAIN ); }
			}
			$pngout_version = ewww_image_optimizer_tool_found ( '"' . EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout.exe"', 'p' );
		}
	}
	if ( ! empty( $pngout_version ) ) {
		$sendback = add_query_arg( 'ewww_pngout', 'success', remove_query_arg( array( 'ewww_pngout', 'ewww_error' ), wp_get_referer() ) );
	}
	if ( ! isset( $sendback ) ) {
		$sendback = add_query_arg( array( 'ewww_pngout' => 'failed', 'ewww_error' => urlencode( $pngout_error ) ), remove_query_arg( array( 'ewww_pngout', 'ewww_error' ), wp_get_referer() ) );
	}
	wp_redirect( esc_url_raw( $sendback) );
	ewwwio_memory( __FUNCTION__ );
	exit( 0 );
}
