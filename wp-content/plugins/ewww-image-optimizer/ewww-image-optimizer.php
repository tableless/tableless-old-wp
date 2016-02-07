<?php
/**
 * Integrate image optimizers into WordPress.
 * @version 2.5.2
 * @package EWWW_Image_Optimizer
 */
/*
Plugin Name: EWWW Image Optimizer
Plugin URI: http://wordpress.org/extend/plugins/ewww-image-optimizer/
Description: Reduce file sizes for images within WordPress including NextGEN Gallery and GRAND FlAGallery. Uses jpegtran, optipng/pngout, and gifsicle.
Author: Shane Bishop
Text Domain: ewww-image-optimizer
Version: 2.5.2
Author URI: https://ewww.io/
License: GPLv3
*/

// TODO: make mention of wp-plugins/ewwwio repo on github

// Constants
define('EWWW_IMAGE_OPTIMIZER_DOMAIN', 'ewww-image-optimizer');
// this is the full path of the plugin file itself
define('EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE', __FILE__);
// this is the path of the plugin file relative to the plugins/ folder
define('EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL', 'ewww-image-optimizer/ewww-image-optimizer.php');
// the folder where we install optimization tools
define('EWWW_IMAGE_OPTIMIZER_TOOL_PATH', WP_CONTENT_DIR . '/ewww/');
// this is the full system path to the plugin folder
define('EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH', plugin_dir_path(__FILE__));

require_once(EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'common.php');

// Hooks
add_action('admin_action_ewww_image_optimizer_install_pngout', 'ewww_image_optimizer_install_pngout');

// check to see if the cloud constant is defined (which would mean we've already run init) and then set it properly if not
function ewww_image_optimizer_cloud_init() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_CLOUD' ) && ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_jpg' ) && ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_png' ) && ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_gif' ) ) {
		define( 'EWWW_IMAGE_OPTIMIZER_CLOUD', TRUE );
	} elseif ( ! defined( 'EWWW_IMAGE_OPTIMIZER_CLOUD' ) ) {
		define( 'EWWW_IMAGE_OPTIMIZER_CLOUD', FALSE );
	}
	ewwwio_memory( __FUNCTION__ );
//	ewww_image_optimizer_debug_log();
}

function ewww_image_optimizer_exec_init() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if ( ! function_exists( 'is_plugin_active_for_network' ) && is_multisite() ) {
		// need to include the plugin library for the is_plugin_active function
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
	if ( is_multisite() && is_plugin_active_for_network( EWWW_IMAGE_OPTIMIZER_PLUGIN_FILE_REL ) ) {
		// set the binary-specific network settings if they have been POSTed
		if (isset($_POST['ewww_image_optimizer_delay'])) {
			if (empty($_POST['ewww_image_optimizer_skip_check'])) $_POST['ewww_image_optimizer_skip_check'] = '';
			update_site_option('ewww_image_optimizer_skip_check', $_POST['ewww_image_optimizer_skip_check']);
			if (empty($_POST['ewww_image_optimizer_skip_bundle'])) $_POST['ewww_image_optimizer_skip_bundle'] = '';
			update_site_option('ewww_image_optimizer_skip_bundle', $_POST['ewww_image_optimizer_skip_bundle']);
			if ( ! empty( $_POST['ewww_image_optimizer_optipng_level'] ) ) {
				update_site_option('ewww_image_optimizer_optipng_level', $_POST['ewww_image_optimizer_optipng_level']);
				update_site_option('ewww_image_optimizer_pngout_level', $_POST['ewww_image_optimizer_pngout_level']);
			}
			if (empty($_POST['ewww_image_optimizer_disable_jpegtran'])) $_POST['ewww_image_optimizer_disable_jpegtran'] = '';
			update_site_option('ewww_image_optimizer_disable_jpegtran', $_POST['ewww_image_optimizer_disable_jpegtran']);
			if (empty($_POST['ewww_image_optimizer_disable_optipng'])) $_POST['ewww_image_optimizer_disable_optipng'] = '';
			update_site_option('ewww_image_optimizer_disable_optipng', $_POST['ewww_image_optimizer_disable_optipng']);
			if (empty($_POST['ewww_image_optimizer_disable_gifsicle'])) $_POST['ewww_image_optimizer_disable_gifsicle'] = '';
			update_site_option('ewww_image_optimizer_disable_gifsicle', $_POST['ewww_image_optimizer_disable_gifsicle']);
			if (empty($_POST['ewww_image_optimizer_disable_pngout'])) $_POST['ewww_image_optimizer_disable_pngout'] = '';
			update_site_option('ewww_image_optimizer_disable_pngout', $_POST['ewww_image_optimizer_disable_pngout']);
		}
	}
	// register all the binary-specific EWWW IO settings
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_skip_check');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_skip_bundle');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_optipng_level');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_pngout_level');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_jpegtran');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_optipng');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_gifsicle');
	register_setting('ewww_image_optimizer_options', 'ewww_image_optimizer_disable_pngout');
	if ( defined( 'WPE_PLUGIN_VERSION' ) ) {
		add_action('network_admin_notices', 'ewww_image_optimizer_notice_wpengine');
		add_action('admin_notices', 'ewww_image_optimizer_notice_wpengine');
	}
	// If cloud is fully enabled, we're going to skip all the checks related to the bundled tools
	if(EWWW_IMAGE_OPTIMIZER_CLOUD) {
		ewwwio_debug_message( 'cloud options enabled, shutting off binaries' );
		ewww_image_optimizer_disable_tools();
	// Check if this is an unsupported OS (not Linux or Mac OSX or FreeBSD or Windows or SunOS)
	} elseif('Linux' != PHP_OS && 'Darwin' != PHP_OS && 'FreeBSD' != PHP_OS && 'WINNT' != PHP_OS && 'SunOS' != PHP_OS) {
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
//		add_action( 'load-index.php', 'ewww_image_optimizer_tool_init' );
//		add_action( 'load-galleries_page_nggallery-manage-gallery', 'ewww_image_optimizer_tool_init' );
//		add_action( 'load-', 'ewww_image_optimizer_tool_init' );
	} 
	ewwwio_memory( __FUNCTION__ );
//	ewww_image_optimizer_debug_log();
}

// check for binary installation and availability
function ewww_image_optimizer_tool_init() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// make sure the bundled tools are installed
	if( ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_skip_bundle' ) ) {
		ewww_image_optimizer_install_tools ();
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
	// set a few defaults
	add_site_option('ewww_image_optimizer_disable_pngout', TRUE);
	add_site_option('ewww_image_optimizer_optipng_level', 2);
	add_site_option('ewww_image_optimizer_pngout_level', 2);
}

// tells the user they are on an unsupported operating system
function ewww_image_optimizer_notice_os() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	echo "<div id='ewww-image-optimizer-warning-os' class='error'><p><strong>" . __('EWWW Image Optimizer is supported on Linux, FreeBSD, Mac OSX, and Windows', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ".</strong> " . sprintf(__('Unfortunately, the EWWW Image Optimizer plugin does not work with %s', EWWW_IMAGE_OPTIMIZER_DOMAIN), htmlentities(PHP_OS)) . ".</p></div>";
}

// inform the user that only ewww-image-optimizer-cloud is permitted on WP Engine
function ewww_image_optimizer_notice_wpengine() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	echo "<div id='ewww-image-optimizer-warning-wpengine' class='error'><p>" . __('The regular version of the EWWW Image Optimizer plugin is not permitted on WP Engine sites. However, the cloud version has been approved by WP Engine. Please deactivate EWWW Image Optimizer and install EWWW Image Optimizer Cloud to optimize your images.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></div>";
}

// generates the source and destination paths for the executables that we bundle with the plugin based on the operating system
function ewww_image_optimizer_install_paths () {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if (PHP_OS == 'WINNT') {
		$gifsicle_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'gifsicle.exe';
		$optipng_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'optipng.exe';
		$jpegtran_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'jpegtran.exe';
		$pngquant_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngquant.exe';
		$webp_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'cwebp.exe';
		$gifsicle_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle.exe';
		$optipng_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng.exe';
		$jpegtran_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran.exe';
		$pngquant_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant.exe';
		$webp_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp.exe';
	}
	if (PHP_OS == 'Darwin') {
		$gifsicle_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'gifsicle-mac';
		$optipng_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'optipng-mac';
		$jpegtran_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'jpegtran-mac';
		$pngquant_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngquant-mac';
		$webp_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'cwebp-mac9';
		$gifsicle_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle';
		$optipng_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng';
		$jpegtran_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran';
		$pngquant_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant';
		$webp_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp';
	}
	if (PHP_OS == 'SunOS') {
		$gifsicle_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'gifsicle-sol';
		$optipng_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'optipng-sol';
		$jpegtran_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'jpegtran-sol';
		$pngquant_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngquant-sol';
		$webp_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'cwebp-sol';
		$gifsicle_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle';
		$optipng_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng';
		$jpegtran_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran';
		$pngquant_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant';
		$webp_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp';
	}
	if (PHP_OS == 'FreeBSD') {
		$arch_type = php_uname('m');
		ewwwio_debug_message( "CPU architecture: $arch_type" );
		$gifsicle_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'gifsicle-fbsd';
		$optipng_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'optipng-fbsd';
		if ($arch_type == 'amd64') {
			$jpegtran_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'jpegtran-fbsd64';
		} else {
			$jpegtran_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'jpegtran-fbsd';
		}
		$pngquant_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngquant-fbsd';
		if ($arch_type == 'amd64') {
			$webp_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'cwebp-fbsd64';
		} else {
			$webp_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'cwebp-fbsd';
		}
		$gifsicle_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle';
		$optipng_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng';
		$jpegtran_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran';
		$pngquant_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant';
		$webp_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp';
	}
	if (PHP_OS == 'Linux') {
		$arch_type = php_uname('m');
		ewwwio_debug_message( "CPU architecture: $arch_type" );
		$gifsicle_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'gifsicle-linux';
		$optipng_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'optipng-linux';
		if ($arch_type == 'x86_64') {
			$jpegtran_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'jpegtran-linux64';
			$webp_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'cwebp-linux864';
		} else {
			$jpegtran_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'jpegtran-linux';
			$webp_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'cwebp-linux8';
		}
		$pngquant_src = EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngquant-linux';
		$gifsicle_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle';
		$optipng_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng';
		$jpegtran_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran';
		$pngquant_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant';
		$webp_dst = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp';
	}
	ewwwio_debug_message( "generated paths:<br>$jpegtran_src<br>$optipng_src<br>$gifsicle_src<br>$pngquant_src<br>$webp_src<br>$jpegtran_dst<br>$optipng_dst<br>$gifsicle_dst<br>$pngquant_dst<br>$webp_dst" );
	ewwwio_memory( __FUNCTION__ );
	return array($jpegtran_src, $optipng_src, $gifsicle_src, $pngquant_src, $webp_src, $jpegtran_dst, $optipng_dst, $gifsicle_dst, $pngquant_dst, $webp_dst);
}

// installs the executables that are bundled with the plugin
function ewww_image_optimizer_install_tools () {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_debug_message( "Checking/Installing tools in " . EWWW_IMAGE_OPTIMIZER_TOOL_PATH );
	$toolfail = false;
	if (!is_dir(EWWW_IMAGE_OPTIMIZER_TOOL_PATH)) {
		ewwwio_debug_message( 'folder does not exist, creating...' );
		if ( ! mkdir( EWWW_IMAGE_OPTIMIZER_TOOL_PATH ) ) {
			echo "<div id='ewww-image-optimizer-warning-tool-install' class='error'><p><strong>" . __('EWWW Image Optimizer could not create the tool folder', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ": " . htmlentities(EWWW_IMAGE_OPTIMIZER_TOOL_PATH) . ".</strong> " . __('Please adjust permissions or create the folder', EWWW_IMAGE_OPTIMIZER_DOMAIN) . ".</p></div>";
			ewwwio_debug_message( 'could not create folder' );
			return;
		}
	} else {
		if ( ! is_writable(EWWW_IMAGE_OPTIMIZER_TOOL_PATH)) {
			ewwwio_debug_message( 'wp-content/ewww is not writable, not installing anything' );
			return;
		}
		$ewww_perms = substr(sprintf('%o', fileperms(EWWW_IMAGE_OPTIMIZER_TOOL_PATH)), -4);
		ewwwio_debug_message( "wp-content/ewww permissions: $ewww_perms" );
	}
	list ($jpegtran_src, $optipng_src, $gifsicle_src, $pngquant_src, $webp_src, $jpegtran_dst, $optipng_dst, $gifsicle_dst, $pngquant_dst, $webp_dst) = ewww_image_optimizer_install_paths();
	if ( ! file_exists( $jpegtran_dst ) || filesize( $jpegtran_dst ) != filesize( $jpegtran_src ) ) {
		ewwwio_debug_message( 'jpegtran not found or different size, installing' );
		if (!copy($jpegtran_src, $jpegtran_dst)) {
			$toolfail = true;
			ewwwio_debug_message( 'could not copy jpegtran' );
		}
	}
	// install 32-bit jpegtran at jpegtran-alt for some weird 64-bit hosts
	$arch_type = php_uname('m');
	if (PHP_OS == 'Linux' && $arch_type == 'x86_64') {
		ewwwio_debug_message( '64-bit linux detected while installing tools' );
		$jpegtran32_src = substr($jpegtran_src, 0, -2);
		$jpegtran32_dst = $jpegtran_dst . '-alt';
		if (!file_exists($jpegtran32_dst) || (ewww_image_optimizer_md5check($jpegtran32_dst) && filesize($jpegtran32_dst) != filesize($jpegtran32_src))) {
			ewwwio_debug_message( "copying $jpegtran32_src to $jpegtran32_dst" );
			if (!copy($jpegtran32_src, $jpegtran32_dst)) {
				// this isn't a fatal error, besides we'll see it in the debug if needed
				ewwwio_debug_message( 'could not copy 32-bit jpegtran to jpegtran-alt' );
			}
			$jpegtran32_perms = substr(sprintf('%o', fileperms($jpegtran32_dst)), -4);
			ewwwio_debug_message( "jpegtran-alt (32-bit) permissions: $jpegtran32_perms" );
			if ($jpegtran32_perms != '0755') {
				if (!chmod($jpegtran32_dst, 0755)) {
					ewwwio_debug_message( 'could not set jpegtran-alt permissions' );
				}
			}
		}
	}
	if (!file_exists($gifsicle_dst) || filesize($gifsicle_dst) != filesize($gifsicle_src)) {
		ewwwio_debug_message( 'gifsicle not found or different size, installing' );
		if (!copy($gifsicle_src, $gifsicle_dst)) {
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
	if (PHP_OS == 'Darwin') {
		$webp8_dst = $webp_dst . '-alt';
		$webp8_src = str_replace('mac9', 'mac8', $webp_src);
		if (!file_exists($webp8_dst) || (ewww_image_optimizer_md5check($webp8_dst) && filesize($webp8_dst) != filesize($webp8_src))) {
			ewwwio_debug_message( "copying $webp8_src to $webp8_dst" );
			if (!copy($webp8_src, $webp8_dst)) {
				// this isn't a fatal error, besides we'll see it in the debug if needed
				ewwwio_debug_message( 'could not copy OSX 10.8 cwebp to cwebp-alt' );
			}
			$webp8_perms = substr(sprintf('%o', fileperms($webp8_dst)), -4);
			ewwwio_debug_message( "cwebp8-alt (OSX 10.8) permissions: $webp8_perms" );
			if ($webp8_perms != '0755') {
				if (!chmod($webp8_dst, 0755)) {
					ewwwio_debug_message( 'could not set cwebp8-alt permissions' );
				}
			}
		}
	}

	// install libjpeg6 version of cwebp for older systems
	if (PHP_OS == 'Linux') {
		$webp6_dst = $webp_dst . '-alt';
		$webp6_src = str_replace('linux8', 'linux6', $webp_src);
		if (!file_exists($webp6_dst) || (ewww_image_optimizer_md5check($webp6_dst) && filesize($webp6_dst) != filesize($webp6_src))) {
			ewwwio_debug_message( "copying $webp6_src to $webp6_dst" );
			if (!copy($webp6_src, $webp6_dst)) {
				// this isn't a fatal error, besides we'll see it in the debug if needed
				ewwwio_debug_message( 'could not copy libjpeg6 cwebp to cwebp-alt' );
			}
			$webp6_perms = substr(sprintf('%o', fileperms($webp6_dst)), -4);
			ewwwio_debug_message( "cwebp6-alt (libjpeg6) permissions: $webp6_perms" );
			if ($webp6_perms != '0755') {
				if (!chmod($webp6_dst, 0755)) {
					ewwwio_debug_message( 'could not set cwebp6-alt permissions' );
				}
			}
		}
	}

	if ( PHP_OS != 'WINNT' && ! $toolfail ) {
		ewwwio_debug_message( 'Linux/UNIX style OS, checking permissions' );
		$jpegtran_perms = substr(sprintf('%o', fileperms($jpegtran_dst)), -4);
		ewwwio_debug_message( "jpegtran permissions: $jpegtran_perms" );
		if ($jpegtran_perms != '0755') {
			if (!chmod($jpegtran_dst, 0755)) {
				$toolfail = true;
				ewwwio_debug_message( 'could not set jpegtran permissions' );
			}
		}
		$gifsicle_perms = substr(sprintf('%o', fileperms($gifsicle_dst)), -4);
		ewwwio_debug_message( "gifsicle permissions: $gifsicle_perms" );
		if ($gifsicle_perms != '0755') {
			if (!chmod($gifsicle_dst, 0755)) {
				$toolfail = true;
				ewwwio_debug_message( 'could not set gifsicle permissions' );
			}
		}
		$optipng_perms = substr(sprintf('%o', fileperms($optipng_dst)), -4);
		ewwwio_debug_message( "optipng permissions: $optipng_perms" );
		if ($optipng_perms != '0755') {
			if (!chmod($optipng_dst, 0755)) {
				$toolfail = true;
				ewwwio_debug_message( 'could not set optipng permissions' );
			}
		}
		$pngquant_perms = substr(sprintf('%o', fileperms($pngquant_dst)), -4);
		ewwwio_debug_message( "pngquant permissions: $pngquant_perms" );
		if ($pngquant_perms != '0755') {
			if (!chmod($pngquant_dst, 0755)) {
				$toolfail = true;
				ewwwio_debug_message( 'could not set pngquant permissions' );
			}
		}
		$webp_perms = substr(sprintf('%o', fileperms($webp_dst)), -4);
		ewwwio_debug_message( "webp permissions: $webp_perms" );
		if ($webp_perms != '0755') {
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
		echo "<div id='ewww-image-optimizer-warning-tool-install' class='error'><p><strong>" . sprintf(__('EWWW Image Optimizer could not install tools in %s', EWWW_IMAGE_OPTIMIZER_DOMAIN), htmlentities(EWWW_IMAGE_OPTIMIZER_TOOL_PATH)) . ".</strong> " . sprintf(__('Please adjust permissions or create the folder. If you have installed the tools elsewhere on your system, check the option to %s.', EWWW_IMAGE_OPTIMIZER_DOMAIN), __('Use System Paths', EWWW_IMAGE_OPTIMIZER_DOMAIN)) . " " . sprintf(__('For more details, visit the %1$s or the %2$s.', EWWW_IMAGE_OPTIMIZER_DOMAIN), "<a href='$settings_page'>" . __('Settings Page', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a>", "<a href='http://wordpress.org/extend/plugins/ewww-image-optimizer/installation/'>" . __('Installation Instructions', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a>.</p></div>");
	}
	ewwwio_memory( __FUNCTION__ );
}

// we check for safe mode and exec, then also direct the user where to go if they don't have the tools installed
// this is another function called by hook usually
function ewww_image_optimizer_notice_utils() {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// Check if exec is disabled
	if(ewww_image_optimizer_exec_check()) {
		//display a warning if exec() is disabled, can't run much of anything without it
		echo "<div id='ewww-image-optimizer-warning-opt-png' class='error'><p>" . __('EWWW Image Optimizer requires exec(). Your system administrator has disabled this function.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></div>";
		define('EWWW_IMAGE_OPTIMIZER_NOEXEC', true);
		ewwwio_debug_message( 'exec seems to be disabled' );
		ewww_image_optimizer_disable_tools();
		return;
		// otherwise, query the php settings for safe mode
	} elseif (ewww_image_optimizer_safemode_check()) {
		// display a warning to the user
		echo "<div id='ewww-image-optimizer-warning-opt-png' class='error'><p>" . __('Safe Mode is turned on for PHP. This plugin cannot operate in Safe Mode.', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</p></div>";
		define('EWWW_IMAGE_OPTIMIZER_NOEXEC', true);
		ewwwio_debug_message( 'safe mode appears to be enabled' );
		ewww_image_optimizer_disable_tools();
		return;
	} else {
		define('EWWW_IMAGE_OPTIMIZER_NOEXEC', false);
	}

	// set the variables false otherwise
	$skip_jpegtran_check = false;
	$skip_optipng_check = false;
	$skip_gifsicle_check = false;
	$skip_pngout_check = false;
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
				ewwwio_debug_message( "defining EWWW_IMAGE_OPTIMIZER_$key" );
				define( 'EWWW_IMAGE_OPTIMIZER_' . $key, $req );
				break; 
			case 'OPTIPNG':
				if ( ! $skip_optipng_check && empty( $req ) ) {
					$missing[] = 'optipng';
					$req = false;
				}
				ewwwio_debug_message( "defining EWWW_IMAGE_OPTIMIZER_$key" );
				define( 'EWWW_IMAGE_OPTIMIZER_' . $key, $req );
				break;
			case 'GIFSICLE':
				if ( ! $skip_gifsicle_check && empty( $req ) ) {
					$missing[] = 'gifsicle';
					$req = false;
				}
				ewwwio_debug_message( "defining EWWW_IMAGE_OPTIMIZER_$key" );
				define( 'EWWW_IMAGE_OPTIMIZER_' . $key, $req );
				break;
			case 'PNGOUT':
				if ( ! $skip_pngout_check && empty( $req ) ) {
					$missing[] = 'pngout';
					$req = false;
				}
				ewwwio_debug_message( "defining EWWW_IMAGE_OPTIMIZER_$key" );
				define( 'EWWW_IMAGE_OPTIMIZER_' . $key, $req );
				break;
			case 'PNGQUANT':
				if ( ! $skip_pngquant_check && empty( $req ) ) {
					$missing[] = 'pngquant';
					$req = false;
				}
				ewwwio_debug_message( "defining EWWW_IMAGE_OPTIMIZER_$key" );
				define( 'EWWW_IMAGE_OPTIMIZER_' . $key, $req );
				break;
			case 'WEBP':
				if ( ! $skip_webp_check && empty( $req ) ) {
					$missing[] = 'webp';
					$req = false;
				}
				ewwwio_debug_message( "defining EWWW_IMAGE_OPTIMIZER_$key" );
				define( 'EWWW_IMAGE_OPTIMIZER_' . $key, $req );
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
		echo "<div id='ewww-image-optimizer-warning-opt-png' class='error'><p>" . sprintf(__('EWWW Image Optimizer uses %1$s, %2$s, %3$s, %4$s, %5$s, and %6$s. You are missing: %7$s. Please install via the %8$s or the %9$s.', EWWW_IMAGE_OPTIMIZER_DOMAIN), "<a href='http://jpegclub.org/jpegtran/'>jpegtran</a>", "<a href='http://optipng.sourceforge.net/'>optipng</a>", "<a href='http://advsys.net/ken/utils.htm'>pngout</a>", "<a href='http://pngquant.org/'>pngquant</a>", "<a href='http://www.lcdf.org/gifsicle/'>gifsicle</a>", "<a href='https://developers.google.com/speed/webp/'>cwebp</a>", $msg, "<a href='$settings_page'>" . __('Settings Page', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a>", "<a href='http://wordpress.org/extend/plugins/ewww-image-optimizer/installation/'>" . __('Installation Instructions', EWWW_IMAGE_OPTIMIZER_DOMAIN) . "</a>") . "</p></div>";
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
	// for Windows, everything must be in the wp-content/ewww folder, so that is all we check
	if ('WINNT' == PHP_OS) {
		if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran.exe') && $j) {
			$jpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran.exe';
			ewwwio_debug_message( "found $jpt, testing..." );
			if (ewww_image_optimizer_tool_found('"' . $jpt . '"', 'j') && ewww_image_optimizer_md5check($jpt)) {
				$jpegtran = '"' . $jpt . '"';
			}
		}
		if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng.exe') && $o) {
			$opt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng.exe';
			ewwwio_debug_message( "found $opt, testing..." );
			if (ewww_image_optimizer_tool_found('"' . $opt . '"', 'o') && ewww_image_optimizer_md5check($opt)) {
				$optipng = '"' . $opt . '"';
			}
		}
		if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle.exe') && $g) {
			$gpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle.exe';
			ewwwio_debug_message( "found $gpt, testing..." );
			if (ewww_image_optimizer_tool_found('"' . $gpt . '"', 'g') && ewww_image_optimizer_md5check($gpt)) {
				$gifsicle = '"' . $gpt . '"';
			}
		}
		if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout.exe') && $p) {
			$ppt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout.exe';
			ewwwio_debug_message( "found $ppt, testing..." );
			if (ewww_image_optimizer_tool_found('"' . $ppt . '"', 'p') && ewww_image_optimizer_md5check($ppt)) {
				$pngout = '"' . $ppt . '"';
			}
		}
		if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant.exe') && $q) {
			$qpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant.exe';
			ewwwio_debug_message( "found $qpt, testing..." );
			if (ewww_image_optimizer_tool_found('"' . $qpt . '"', 'q') && ewww_image_optimizer_md5check($qpt)) {
				$pngquant = '"' . $qpt . '"';
			}
		}
		if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp.exe') && $w) {
			$wpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp.exe';
			ewwwio_debug_message( "found $wpt, testing..." );
			if (ewww_image_optimizer_tool_found('"' . $wpt . '"', 'w') && ewww_image_optimizer_md5check($wpt)) {
				$webp = '"' . $wpt . '"';
			}
		}
	} else {
		// check to see if the user has disabled using bundled binaries
		$use_system = ewww_image_optimizer_get_option('ewww_image_optimizer_skip_bundle');
		if ($j) {
			// first check for the jpegtran binary in the ewww tool folder
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran') && !$use_system) {
				$jpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran';
				ewwwio_debug_message( "found $jpt, testing..." );
				if (ewww_image_optimizer_md5check($jpt) && ewww_image_optimizer_mimetype($jpt, 'b')) {
					$jpt = ewww_image_optimizer_escapeshellcmd ( $jpt );
					if (ewww_image_optimizer_tool_found($jpt, 'j')) {
						$jpegtran = $jpt;
					}
				}
			}
			// if the standard jpegtran binary didn't work, see if the user custom compiled one and check that
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran-custom') && !$jpegtran && !$use_system) {
				$jpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran-custom';
				ewwwio_debug_message( "found $jpt, testing..." );
				if (filesize($jpt) > 15000 && ewww_image_optimizer_mimetype($jpt, 'b')) {
					$jpt = ewww_image_optimizer_escapeshellcmd ( $jpt );
					if (ewww_image_optimizer_tool_found($jpt, 'j')) {
						$jpegtran = $jpt;
					}
				}
			}
			// see if the alternative binary works
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran-alt') && !$jpegtran && !$use_system) {
				$jpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'jpegtran-alt';
				ewwwio_debug_message( "found $jpt, testing..." );
				if (filesize($jpt) > 15000 && ewww_image_optimizer_mimetype($jpt, 'b')) {
					$jpt = ewww_image_optimizer_escapeshellcmd ( $jpt );
					if (ewww_image_optimizer_tool_found($jpt, 'j')) {
						$jpegtran = $jpt;
					}
				}
			}
			// if we still haven't found a usable binary, try a system-installed version
			if (!$jpegtran) {
				$jpegtran = ewww_image_optimizer_find_binary('jpegtran', 'j');
			}
		}
		if ($o) {
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng') && !$use_system) {
				$opt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng';
				ewwwio_debug_message( "found $opt, testing..." );
				if (ewww_image_optimizer_md5check($opt) && ewww_image_optimizer_mimetype($opt, 'b')) {
					$opt = ewww_image_optimizer_escapeshellcmd ( $opt );
					if (ewww_image_optimizer_tool_found($opt, 'o')) {
						$optipng = $opt;
					}
				}
			}
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng-custom') && !$optipng && !$use_system) {
				$opt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'optipng-custom';
				ewwwio_debug_message( "found $opt, testing..." );
				if (filesize($opt) > 15000 && ewww_image_optimizer_mimetype($opt, 'b')) {
					$opt = ewww_image_optimizer_escapeshellcmd ( $opt );
					if (ewww_image_optimizer_tool_found($opt, 'o')) {
						$optipng = $opt;
					}
				}
			}
			if (!$optipng) {
				$optipng = ewww_image_optimizer_find_binary('optipng', 'o');
			}
		}
		if ($g) {
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle') && !$use_system) {
				$gpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle';
				ewwwio_debug_message( "found $gpt, testing..." );
				if (ewww_image_optimizer_md5check($gpt) && ewww_image_optimizer_mimetype($gpt, 'b')) {
					$gpt = ewww_image_optimizer_escapeshellcmd ( $gpt );
					if (ewww_image_optimizer_tool_found($gpt, 'g')) {
						$gifsicle = $gpt;
					}
				}
			}
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle-custom') && !$gifsicle && !$use_system) {
				$gpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'gifsicle-custom';
				ewwwio_debug_message( "found $gpt, testing..." );
				if (filesize($gpt) > 15000 && ewww_image_optimizer_mimetype($gpt, 'b')) {
					$gpt = ewww_image_optimizer_escapeshellcmd ( $gpt );
					if (ewww_image_optimizer_tool_found($gpt, 'g')) {
						$gifsicle = $gpt;
					}
				}
			}
			if (!$gifsicle) {
				$gifsicle = ewww_image_optimizer_find_binary('gifsicle', 'g');
			}
		}
		if ( $p ) {
			// pngout is special and has a dynamic and static binary to check
			if ( file_exists( EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout-static' ) && ! $use_system ) {
				$ppt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout-static';
				ewwwio_debug_message( "found $ppt, testing..." );
				if (ewww_image_optimizer_md5check($ppt) && ewww_image_optimizer_mimetype($ppt, 'b')) {
					$ppt = ewww_image_optimizer_escapeshellcmd ( $ppt );
					if (ewww_image_optimizer_tool_found($ppt, 'p')) {
						$pngout = $ppt;
					}
				}
			}
			if (!$pngout) {
				$pngout = ewww_image_optimizer_find_binary('pngout', 'p');
			}
			if (!$pngout) {
				$pngout = ewww_image_optimizer_find_binary('pngout-static', 'p');
			}
		}
		if ($q) {
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant') && !$use_system) {
				$qpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant';
				ewwwio_debug_message( "found $qpt, testing..." );
				if (ewww_image_optimizer_md5check($qpt) && ewww_image_optimizer_mimetype($qpt, 'b')) {
					$qpt = ewww_image_optimizer_escapeshellcmd ( $qpt );
					if (ewww_image_optimizer_tool_found($qpt, 'q')) {
						$pngquant = $qpt;
					}
				}
			}
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant-custom') && !$pngquant && !$use_system) {
				$qpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngquant-custom';
				ewwwio_debug_message( "found $qpt, testing..." );
				if (filesize($qpt) > 15000 && ewww_image_optimizer_mimetype($qpt, 'b')) {
					$qpt = ewww_image_optimizer_escapeshellcmd ( $qpt );
					if (ewww_image_optimizer_tool_found($qpt, 'q')) {
						$pngquant = $qpt;
					}
				}
			}
			if (!$pngquant) {
				$pngquant = ewww_image_optimizer_find_binary('pngquant', 'q');
			}
		}
		if ($w) {
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp') && !$use_system) {
				$wpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp';
				ewwwio_debug_message( "found $wpt, testing..." );
				if (ewww_image_optimizer_md5check($wpt) && ewww_image_optimizer_mimetype($wpt, 'b')) {
					$wpt = ewww_image_optimizer_escapeshellcmd ( $wpt );
					if (ewww_image_optimizer_tool_found($wpt, 'w')) {
						$webp = $wpt;
					}
				}
			}
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp-custom') && !$webp && !$use_system) {
				$wpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp-custom';
				ewwwio_debug_message( "found $wpt, testing..." );
				if (filesize($wpt) > 5000 && ewww_image_optimizer_mimetype($wpt, 'b')) {
					$wpt = ewww_image_optimizer_escapeshellcmd ( $wpt );
					if (ewww_image_optimizer_tool_found($wpt, 'w')) {
						$webp = $wpt;
					}
				}
			}
			if (file_exists(EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp-alt') && !$webp && !$use_system) {
				$wpt = EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'cwebp-alt';
				ewwwio_debug_message( "found $wpt, testing..." );
				if (filesize($wpt) > 5000 && ewww_image_optimizer_mimetype($wpt, 'b')) {
					$wpt = ewww_image_optimizer_escapeshellcmd ( $wpt );
					if (ewww_image_optimizer_tool_found($wpt, 'w')) {
						$webp = $wpt;
					}
				}
			}
			if (!$webp) {
				$webp = ewww_image_optimizer_find_binary('cwebp', 'w');
			}
		}
	}
	if ($jpegtran) ewwwio_debug_message( "using: $jpegtran" );
	if ($optipng) ewwwio_debug_message( "using: $optipng" );
	if ($gifsicle) ewwwio_debug_message( "using: $gifsicle" );
	if ($pngout) ewwwio_debug_message( "using: $pngout" );
	if ($pngquant) ewwwio_debug_message( "using: $pngquant" );
	if ($webp) ewwwio_debug_message( "using: $webp" );
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

// checks the binary at $path against a list of valid md5sums
function ewww_image_optimizer_md5check($path) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_debug_message( "$path: " . md5_file($path) );
	$valid_md5sums = array(
		//jpegtran
		'e2ba2985107600ebb43f85487258f6a3',
		'67c1dbeab941255a4b2b5a99db3c6ef5',
		'4a78fdeac123a16d2b9e93b6960e80b1',
		'a3f65d156a4901226cb91790771ca73f',
		'98cca712e6c162f399e85aec740bf560',
		'2dab67e5f223b70c43b2fef355b39d3f',
		'4da4092708650ceb79df19d528e7956b',
		'9d482b93d4129f7e87ce36c5e650de0c',
		'1c251658834162b01913702db0013c08',
		'dabf8173725e15d866f192f77d9e3883',
		'e4f7809c84a0722abe2b1d003c98a181',
		'a9f10ee88569bd7e72a7c2437ea9c09b', // jpegtran.exe
		'63d3edb0807ac1adbaefd437da8602ef', // jpegtran-sol
		'298e79ec273dec5dc77b98b1578dab23', // jpegtran-fbsd
		'4e87350ccff7c483e764d479ad4f84ea', // jpegtran-fbsd64
		'8e4a09bb04ba001f5f16651ae8594f7f', // jpegtran-linux
		'47c39feae0712f2996c61e5ae639b706', // jpegtran-linux64
		'9df8764bfe6a0434d436ed0cadbec8f0', // jpegtran-mac
		//optipng
		'4eb91937291ce5038d0c68f5f2edbcfd', // optipng-linux .7.4
		'899e3c569080a55bcc5de06a01c8e23a',
		'0467bd0c73473221d21afbc5275503e4',
		'293e26924a274c6185a06226619d8e02',
		'bcb27d22377f8abf3e9fe88a60030885',
		'8359078a649aeec2bd472ec84a4f39e1', // optipng-sol .7.5
		'aa20003676d1a3321032fa550a73716a', // optipng-fbsd .7.5
		'9a9e86346590878d23ef663086ffae2b', // optipng-mac .7.5
		'e3d154829ea57a0bdd88b080f6851265', // optipng.exe .7.5
		'31698da4f5ca00b35e910c77acae65bb', // optipng-linux .7.5
		//gifsicle
		'2384f770d307c42b9c1e53cdc8dd662d',
		'24fc5f33b33c0d11fb2e88f5a93949d0',
		'e4a14bce92755261fe21798c295d06db',
		'9ddef564fed446700a3a7303c39610a3',
		'aad47bafdb2bc8a9f0755f57f94d6eaf',
		'46360c01622ccb514e9e7ef1ac5398f0',
		'44273fad7b3fd1145bfcf35189648f66',
		'4568ef450ec9cd73bab55d661fb167ec',
		'f8d8baa175977a23108c84603dbfcc78',
		'3b592b6398dd7f379740c0b63e83825c',
		'ce935b38b1fd7b3c47d5de57f05c8cd2',
		'fe94420e6c49c29861b95ad93a3a4805',
		'151e395e2efa0e7845b18984d0f092af',
		'7ae972062cf3f99218057b055a4e1e9c',
		'c0bf45a291b93fd0a52318eddeaf5791',
		'ac8fa17a7004fa216242af2367d1a838', // gifsicle-sol 1.84
		'040dd3e5cacb3a635e45a4241f854cae', // gifsicle-sol 1.87
		'db1037b1e5e42108b48da564b8598610', // gifsicle-fbsd 1.84
		'6574c83061c35ec71ee78c6714ee5a07', // gifsicle-fbsd 1.87
		'58f42368e86a4910d101d37fee748409', // gifsicle-linux 1.84
		'40ceed9c36838b5b9ccc505ab224d90f', // gifsicle-linux 1.87
		'39aca9edbb9495a241dc21fa678a09da', // gifsicle-mac 1.84
		'a474c8fa2237bb0b758bf646670a7d12', // gifsicle-mac 1.87
		'32a75a5122ff9b783ed7dd76d65f6297', // gifsicle.exe 1.84
		'dbbe27fff4373b744b514839797a1234', // gifsicle.exe 1.87
		//pngout
		'2b62778559e31bc750dc2dcfd249be32', 
		'ea8655d1a1ef98833b294fb74f349c3e',
		'a30517e045076cab1bb5b5f3a57e999e',
		'6e60aafca40ecc0e648c442f83fa9688',
		'1882ae8efb503c4abdd0d18d974d5fa3',
		'aad1f8107955876efb0b0d686450e611',
		'991f9e7d2c39cb1f658684971d583468',
		'5de47b8cc0943eeceaf1683cb544b4a0',
		'c30de32f31259b79ffb13ca0d9d7a77d',
		'670a0924e9d042be2c60cd4f3ce1d975',
		'c77c5c870755e9732075036a548d8e61',
		'37cdbfcdedc9079f23847f0349efa11c',
		'8bfc5e0e6f0f964c7571988b0e9e2017',
		'b8ead81e0ed860d6461f67d60224ab7b',
		'f712daee5048d5d70197c5f339ac0b02',
		'e006b880f9532af2af0811515218bbd4',
		'b175b4439b054a61e8a41eca9a6e3505',
		'eabcbabde6c7c568e95afd73b7ed096e',
		'17cd744ab90ebaa05a4e846487a582b5', // bsd amd64 dynamic
		'51a13a008425e429451321e7ff8bd329', // bsd i686 dynamic
		'b7100158f68ed16bf5f7f87cdabd55fe', // mac dynamic
		'64f6899d1b7cc8e87d2416b9608e9cde', // linux x86_64 dynamic
		'24cf084d7e0945c7975fc86ff5d02fb9', // linux i686 dynamic
		'63a4ee0a873a43f2e053fa85debcc526', // bsd amd64 static
		'c44ea5ea9f20e53237a713bd5f426717', // bsd i686 static
		'd760b7973ca81856cf2fc464637f8d77', // linux x86_64 static
		'354917afca95aacfb99aaab570f058e6', // linux i686 static
		'7154076fde19a421a7cca8029d4ec831', // windows
		//pngquant
		'6eff276339f9ad818eecd347a5fa99e2',
		'79d8c4f5ff2dbb36068c3e3de42fdb1e',
		'90ea1271c54ce010afba478c5830a75f',
		'3ad57a9c3c9093d65664f3260f44df60',
		'5d480fbe88ab5f732dbc5c9d8b76c2fd', // solaris
		'6fd8b12b542b9145d79925942551dbc8', // pngquant.exe
		'b3bbc013acc8bc04d3b531809abdadbb', // pngquant-sol
		'323246b9300362be24320dc72ba51af4', // pngquant-fbsd
		'46bb066d676bf94cbfd78bdc0227e74e', // pngquant-linux
		'3b94673f48a92cf034eb0095611966da', // pngquant-mac
		//cwebp
		'085ea7844800980c72fa30835d6f6044', // cwebp.exe 0.4.1
		'd36486979358245e8b1cd276f0077864', // cwebp.exe 0.4.3
		'4610c239ba00d515701c75e90efe5534', // cwebp-sol 0.4.1
		'cf7a155952105202f679d11beeef0364', // cwebp-sol 0.4.3
		'44acd143a8dac72bbf5795a10d96da98', // cwebp-fbsd 0.4.1
		'038b5acbbcd43e6811850be7d51236de', // cwebp-fbsd64 0.4.1
		'829f4eb1950c425e064fd780c492a94c', // cwebp-fbsd64 0.4.3
		'9429dd850cc2f976961de5fe61f05e97', // cwebp-linux6 0.4.1
		'fec01e585f6164f911b89a7bf5b3e7eb', // cwebp-linux6 0.4.3
		'eb3a5b6eae54140269ed6dcf6f792d37', // cwebp-linux664 0.4.1
		'361c019b0a31d208c64b54bd813a3f76', // cwebp-linux664 0.4.3
		'62272b2bd33218664b2355f516b6e8fc', // cwebp-linux8 0.4.1
		'c7ee80d24efdb6aa2b87700cf98d7cbc', // cwebp-linux8 0.4.3
		'9b6f13ce6ee5a028cbd2765e2d53a1d7', // cwebp-linux864 0.4.1
		'f9ab108c759d95d66123185c12de132d', // cwebp-linux864 0.4.3
		'7153ac42b8d8a2322853a408ec50fe0b', // cwebp-mac9 0.4.3
		'd43bf5eed775695d5ecfe4eafcbd7af7', // cwebp-mac8 0.4.1
		'4fdbf2eabdcb4261ddb4045f48cc8310', // cwebp-mac8 0.4.2
		'dab793f82cf6a3830898c75410583154', // cwebp-mac7 0.4.1
		);
	foreach ($valid_md5sums as $md5_sum) {
		if ($md5_sum == md5_file($path)) {
			ewwwio_debug_message( 'md5sum verified, binary is intact' );
			ewwwio_memory( __FUNCTION__ );
			return TRUE;
		}
	}
	ewwwio_memory( __FUNCTION__ );
	return FALSE;
}

// check the mimetype of the given file ($path) with various methods
// valid values for $type are 'b' for binary or 'i' for image
function ewww_image_optimizer_mimetype($path, $case) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_debug_message( "testing mimetype: $path" );
	if ( $case == 'i' && preg_match( '/^RIFF.+WEBPVP8/', file_get_contents( $path, NULL, NULL, 0, 16 ) ) ) {
			return 'image/webp';
	}
	if (function_exists('finfo_file') && defined('FILEINFO_MIME')) {
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
	if ((empty($type) || $type != 'application/x-executable') && $case == 'b') {
		// find the 'file' command
		if ($file = ewww_image_optimizer_find_binary('file', 'f')) {
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
function ewww_image_optimizer_escapeshellcmd ($path) {
	return (preg_replace('/ /', '\ ', $path));
}

// test the given path ($path) to see if it returns a valid version string
// returns: version string if found, FALSE if not
function ewww_image_optimizer_tool_found($path, $tool) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	ewwwio_debug_message( "testing case: $tool at $path" );
	switch($tool) {
		case 'j': // jpegtran
			exec($path . ' -v ' . EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'sample.jpg 2>&1', $jpegtran_version);
			if (!empty($jpegtran_version)) ewwwio_debug_message( "$path: {$jpegtran_version[0]}" );
			foreach ($jpegtran_version as $jout) { 
				if (preg_match('/Independent JPEG Group/', $jout)) {
					ewwwio_debug_message( 'optimizer found' );
					return $jout;
				}
			}
			break;
		case 'o': // optipng
			exec($path . ' -v 2>&1', $optipng_version);
			if (!empty($optipng_version)) ewwwio_debug_message( "$path: {$optipng_version[0]}" );
			if (!empty($optipng_version) && strpos($optipng_version[0], 'OptiPNG') === 0) {
				ewwwio_debug_message( 'optimizer found' );
				return $optipng_version[0];
			}
			break;
		case 'g': // gifsicle
			exec($path . ' --version 2>&1', $gifsicle_version);
			if (!empty($gifsicle_version)) ewwwio_debug_message( "$path: {$gifsicle_version[0]}" );
			if (!empty($gifsicle_version) && strpos($gifsicle_version[0], 'LCDF Gifsicle') === 0) {
				ewwwio_debug_message( 'optimizer found' );
				return $gifsicle_version[0];
			}
			break;
		case 'p': // pngout
			exec("$path 2>&1", $pngout_version);
			if (!empty($pngout_version)) ewwwio_debug_message( "$path: {$pngout_version[0]}" );
			if (!empty($pngout_version) && strpos($pngout_version[0], 'PNGOUT') === 0) {
				ewwwio_debug_message( 'optimizer found' );
				return $pngout_version[0];
			}
			break;
		case 'q': // pngquant
			exec($path . ' -V 2>&1', $pngquant_version);
			if ( ! empty( $pngquant_version ) ) ewwwio_debug_message( "$path: {$pngquant_version[0]}" );
			if ( ! empty( $pngquant_version ) && substr( $pngquant_version[0], 0, 3 ) >= 2.0 ) {
				ewwwio_debug_message( 'optimizer found' );
				return $pngquant_version[0];
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
			exec("$path -version 2>&1", $webp_version);
			if ( !empty( $webp_version ) ) ewwwio_debug_message( "$path: {$webp_version[0]}" );
			if ( !empty( $webp_version ) && preg_match( '/0.4.\d/', $webp_version[0] ) ) {
				ewwwio_debug_message( 'optimizer found' );
				return $webp_version[0];
			}
			break;
	}
	ewwwio_debug_message( 'tool not found' );
	ewwwio_memory( __FUNCTION__ );
	return FALSE;
}

// searches system paths for the given $binary and passes along the $switch
function ewww_image_optimizer_find_binary ($binary, $switch) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	if (ewww_image_optimizer_tool_found($binary, $switch)) {
		return $binary;
	} elseif (ewww_image_optimizer_tool_found('/usr/bin/' . $binary, $switch)) {
		return '/usr/bin/' . $binary;
	} elseif (ewww_image_optimizer_tool_found('/usr/local/bin/' . $binary, $switch)) {
		return '/usr/local/bin/' . $binary;
	} elseif (ewww_image_optimizer_tool_found('/usr/gnu/bin/' . $binary, $switch)) {
		return '/usr/gnu/bin/' . $binary;
	} elseif (ewww_image_optimizer_tool_found('/usr/syno/bin/' . $binary, $switch)) { // for synology diskstation OS
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
function ewww_image_optimizer($file, $gallery_type = 4, $converted = false, $new = false, $fullsize = false) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// if the plugin gets here without initializing, we need to run through some things first
	if ( ! defined( 'EWWW_IMAGE_OPTIMIZER_CLOUD' ) ) {
		ewww_image_optimizer_init();
	}
	$bypass_optimization = apply_filters( 'ewww_image_optimizer_bypass', false, $file );
	if (true === $bypass_optimization) {
		// tell the user optimization was skipped
		$msg = __( "Optimization skipped", EWWW_IMAGE_OPTIMIZER_DOMAIN );
		ewwwio_debug_message( "optimization bypassed: $file" );
		// send back the above message
		return array(false, $msg, $converted, $file);
	}
	// initialize the original filename 
	$original = $file;
	$result = '';
	// check that the file exists
	if ( FALSE === file_exists( $file ) ) {
		// tell the user we couldn't find the file
		$msg = sprintf( __( 'Could not find %s', EWWW_IMAGE_OPTIMIZER_DOMAIN ), "<span class='code'>$file</span>" );
		ewwwio_debug_message( "file doesn't appear to exist: $file" );
		// send back the above message
		return array( false, $msg, $converted, $original );
	}
	// check that the file is writable
	if ( FALSE === is_writable( $file ) ) {
		// tell the user we can't write to the file
		$msg = sprintf( __( '%s is not writable', EWWW_IMAGE_OPTIMIZER_DOMAIN ), "<span class='code'>$file</span>" );
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
		$nice = ewww_image_optimizer_find_binary( 'nice', 'n' );
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
	if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_skip_check' ) == TRUE || EWWW_IMAGE_OPTIMIZER_CLOUD) {
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
					ewww_image_optimizer_webp_create( $file, $new_size, 'image/png', null );
				} else {
					ewww_image_optimizer_webp_create( $file, $new_size, $type, null );
				}
				break;
			}
			if ($convert) {
				$tools = ewww_image_optimizer_path_check(true, true, false, true, ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy'), ewww_image_optimizer_get_option('ewww_image_optimizer_webp'));
			} else {
				$tools = ewww_image_optimizer_path_check(true, false, false, false, false, ewww_image_optimizer_get_option('ewww_image_optimizer_webp'));
			}
			// if jpegtran optimization is disabled
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_disable_jpegtran')) {
				// store an appropriate message in $result
				$result = sprintf(__('%s is disabled', EWWW_IMAGE_OPTIMIZER_DOMAIN), 'jpegtran');
			// otherwise, if we aren't skipping the utility verification and jpegtran doesn't exist
			} elseif (!$skip_jpegtran_check && !$tools['JPEGTRAN']) {
				// store an appropriate message in $result
				$result = sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>jpegtran</em>');
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
				ewww_image_optimizer_webp_create( $file, $orig_size, $type, $tools['WEBP'] );
				break;
			}
			// if the conversion process is turned ON, or if this is a resize and the full-size was converted
			if ( $convert && ! ewww_image_optimizer_get_option( 'ewww_image_optimizer_cloud_jpg' ) ) {
				ewwwio_debug_message( "attempting to convert JPG to PNG: $pngfile" );
				if ( empty( $new_size ) ) {
					$new_size = $orig_size;
				}
				// retrieve version info for ImageMagick
				$convert_path = ewww_image_optimizer_find_binary( 'convert', 'i' );
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
					if (file_exists($pngfile)) {
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
					if (file_exists($pngfile)) {
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
			ewww_image_optimizer_webp_create( $file, $new_size, $type, $tools['WEBP'] );
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
					ewww_image_optimizer_webp_create( $file, $new_size, 'image/jpeg', null );
				} else {
					ewww_image_optimizer_webp_create( $file, $new_size, $type, null );
				}
				break;
			}
			if ($convert) {
				$tools = ewww_image_optimizer_path_check(true, true, false, true, ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy'), ewww_image_optimizer_get_option('ewww_image_optimizer_webp'));
			} else {
				$tools = ewww_image_optimizer_path_check(false, true, false, true, ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy'), ewww_image_optimizer_get_option('ewww_image_optimizer_webp'));
			}
			// if pngout and optipng are disabled
			if (ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng') && ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout')) {
				// tell the user all PNG tools are disabled
				$result = __('png tools are disabled', EWWW_IMAGE_OPTIMIZER_DOMAIN);
			// if the utility checking is on, optipng is enabled, but optipng cannot be found
			} elseif (!$skip_optipng_check && !$tools['OPTIPNG'] && !ewww_image_optimizer_get_option('ewww_image_optimizer_disable_optipng')) {
				// tell the user optipng is missing
				$result = sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>optipng</em>');
			// if the utility checking is on, pngout is enabled, but pngout cannot be found
			} elseif (!$skip_pngout_check && !$tools['PNGOUT'] && !ewww_image_optimizer_get_option('ewww_image_optimizer_disable_pngout')) {
				// tell the user pngout is missing
				$result = sprintf(__('%s is missing', EWWW_IMAGE_OPTIMIZER_DOMAIN), '<em>pngout</em>');
			} else {
				// turn optimization on if we made it through all the checks
				$optimize = true;
			}
			// if optimization is turned on
			if ($optimize) {
				// if lossy optimization is ON and full-size exclusion is not active
				if ( ewww_image_optimizer_get_option( 'ewww_image_optimizer_png_lossy' ) && $tools['PNGQUANT'] && ! $skip_lossy ) {
					ewwwio_debug_message( 'attempting lossy reduction' );
					exec( "$nice " . $tools['PNGQUANT'] . " " . ewww_image_optimizer_escapeshellarg( $file ) );
					$quantfile = preg_replace( '/\.\w+$/', '-fs8.png', $file );
					if ( file_exists( $quantfile ) && filesize( $file ) > filesize( $quantfile ) && ewww_image_optimizer_mimetype($quantfile, 'i') == $type ) {
						ewwwio_debug_message( "lossy reduction is better: original - " . filesize( $file ) . " vs. lossy - " . filesize( $quantfile ) );
						rename( $quantfile, $file );
					} elseif ( file_exists( $quantfile ) ) {
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
				ewww_image_optimizer_webp_create( $file, $orig_size, $type, $tools['WEBP'] );
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
				$convert_path = ewww_image_optimizer_find_binary( 'convert', 'i' );
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
			ewww_image_optimizer_webp_create( $file, $new_size, $type, $tools['WEBP'] );
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
					ewww_image_optimizer_webp_create( $file, $new_size, 'image/png', null ); 
 				}
				break;
			}
			if ($convert) {
				$tools = ewww_image_optimizer_path_check(false, true, true, true, ewww_image_optimizer_get_option('ewww_image_optimizer_png_lossy'), ewww_image_optimizer_get_option('ewww_image_optimizer_webp'));
			} else {
				$tools = ewww_image_optimizer_path_check(false, false, true, false, false, false);
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
				exec( "$nice " . $tools['GIFSICLE'] . " -b -O3 --careful -o $tempfile " . ewww_image_optimizer_escapeshellarg( $file ) );
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
						ewww_image_optimizer_webp_create( $file, $new_size, $type, $tools['WEBP'] );
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
function ewww_image_optimizer_webp_create( $file, $orig_size, $type, $tool ) {
	ewwwio_debug_message( '<b>' . __FUNCTION__ . '()</b>' );
	// change the file extension
	$webpfile = $file . '.webp';
	if ( is_file( $webpfile ) || ! ewww_image_optimizer_get_option('ewww_image_optimizer_webp') ) {
		return;
	}
	if ( empty( $tool ) ) {
		ewww_image_optimizer_cloud_optimizer($file, $type, false, $webpfile, 'image/webp');
	} else {
		// check to see if 'nice' exists
		$nice = ewww_image_optimizer_find_binary('nice', 'n');
		switch($type) {
			case 'image/jpeg':
				exec( "$nice " . $tool . " -q  85 -quiet " . ewww_image_optimizer_escapeshellarg( $file ) . " -o " . ewww_image_optimizer_escapeshellarg( $webpfile ) . ' 2>&1', $cli_output );
				break;
			case 'image/png':
				exec( "$nice " . $tool . " -lossless -quiet " . ewww_image_optimizer_escapeshellarg( $file ) . " -o " . ewww_image_optimizer_escapeshellarg( $webpfile ) . ' 2>&1', $cli_output );
				break;
		}
	}
	if ( is_file( $webpfile ) && $orig_size < filesize( $webpfile ) ) {
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
		wp_die( __( 'You don\'t have permission to install image optimizer utilities.', EWWW_IMAGE_OPTIMIZER_DOMAIN ) );
	}
	if ( PHP_OS != 'WINNT' ) {
		$tar = ewww_image_optimizer_find_binary( 'tar', 't' );
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
				exec( "$tar xzf $download_result -C " . ewww_image_optimizer_escapeshellarg( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH ) . ' pngout-' . $latest . '-' . $os_string . '-static/' . $arch_type . '/pngout-static' );
				if ( file_exists( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngout-' . $latest . '-' . $os_string . '-static/' . $arch_type . '/pngout-static' ) ) {
					if ( ! rename( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngout-' . $latest . '-' . $os_string . '-static/' . $arch_type . '/pngout-static', EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout-static' ) ) {
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
				exec( "$tar xzf $download_result -C " . ewww_image_optimizer_escapeshellarg( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH ) . ' pngout-' . $latest . '-darwin/pngout' );
				if ( file_exists( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngout-' . $latest . '-' . $os_string . '-static/' . $arch_type . '/pngout-static' ) ) {
					if ( ! rename( EWWW_IMAGE_OPTIMIZER_PLUGIN_PATH . 'pngout-' . $latest . '-darwin/pngout', EWWW_IMAGE_OPTIMIZER_TOOL_PATH . 'pngout-static' ) ) {
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
