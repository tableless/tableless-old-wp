<?php
/**
 * Plugin Name: Compress JPEG & PNG images
 * Description: Speed up your website. Optimize your JPEG and PNG images automatically with TinyPNG.
 * Version: 1.7.2
 * Author: TinyPNG
 * Author URI: https://tinypng.com
 * Text Domain: tiny-compress-images
 * License: GPLv2 or later
 */

require dirname(__FILE__) . '/src/config/tiny-config.php';
require dirname(__FILE__) . '/src/class-tiny-php.php';
require dirname(__FILE__) . '/src/class-tiny-wp-base.php';
require dirname(__FILE__) . '/src/class-tiny-exception.php';
require dirname(__FILE__) . '/src/class-tiny-compress.php';
require dirname(__FILE__) . '/src/class-tiny-compress-curl.php';
require dirname(__FILE__) . '/src/class-tiny-compress-fopen.php';
require dirname(__FILE__) . '/src/class-tiny-metadata-image.php';
require dirname(__FILE__) . '/src/class-tiny-metadata.php';
require dirname(__FILE__) . '/src/class-tiny-settings.php';
require dirname(__FILE__) . '/src/class-tiny-plugin.php';
require dirname(__FILE__) . '/src/class-tiny-notices.php';

$tiny_plugin = new Tiny_Plugin();

if ( !defined( 'TINY_DEBUG' ) ) {
    define( 'TINY_DEBUG', null );
}
