<?php
/**
 * Plugin Name: Compress JPEG & PNG images
 * Description: Speed up your website. Optimize your JPEG and PNG images automatically with TinyPNG.
 * Version: 2.1.0
 * Author: TinyPNG
 * Author URI: https://tinypng.com
 * Text Domain: tiny-compress-images
 * License: GPLv2 or later
 */

require dirname( __FILE__ ) . '/src/config/tiny-config.php';
require dirname( __FILE__ ) . '/src/class-tiny-php.php';
require dirname( __FILE__ ) . '/src/class-tiny-wp-base.php';
require dirname( __FILE__ ) . '/src/class-tiny-exception.php';
require dirname( __FILE__ ) . '/src/class-tiny-compress.php';
require dirname( __FILE__ ) . '/src/class-tiny-image-size.php';
require dirname( __FILE__ ) . '/src/class-tiny-image.php';
require dirname( __FILE__ ) . '/src/class-tiny-settings.php';
require dirname( __FILE__ ) . '/src/class-tiny-plugin.php';
require dirname( __FILE__ ) . '/src/class-tiny-notices.php';

if ( Tiny_PHP::client_supported() ) {
	require dirname( __FILE__ ) . '/src/class-tiny-compress-client.php';
} else {
	require dirname( __FILE__ ) . '/src/class-tiny-compress-fopen.php';
}

$tiny_plugin = new Tiny_Plugin();
