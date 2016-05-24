<?php
/*
Plugin Name: SendGrid
Plugin URI: http://wordpress.org/plugins/sendgrid-email-delivery-simplified/
Description: Email Delivery. Simplified. SendGrid's cloud-based email infrastructure relieves businesses of the cost and complexity of maintaining custom email systems. SendGrid provides reliable delivery, scalability and real-time analytics along with flexible APIs that make custom integration a breeze.
Version: 1.8.1
Author: SendGrid
Author URI: http://sendgrid.com
Text Domain: sendgrid-email-delivery-simplified
License: GPLv2
*/

// SendGrid configurations
define( 'SENDGRID_CATEGORY', 'wp_sendgrid_plugin' );
define( 'SENDGRID_PLUGIN_SETTINGS', 'settings_page_sendgrid-settings' );
define( 'SENDGRID_PLUGIN_STATISTICS', 'dashboard_page_sendgrid-statistics' );

if ( version_compare( phpversion(), '5.3.0', '<' ) ) {
  add_action( 'admin_notices', 'php_version_error' );
  
  /**
  * Display the notice if PHP version is lower than plugin need
  *
  * return void
  */
  function php_version_error()
  {
    echo '<div class="error"><p>' . __('SendGrid: Plugin requires PHP >= 5.3.0.') . '</p></div>';
  }

  return;
} 

if ( function_exists('wp_mail') )
{
  /**
   * wp_mail has been declared by another process or plugin, so you won't be able to use SENDGRID until the problem is solved.
   */
  add_action( 'admin_notices', 'wp_mail_already_declared_notice' );
  
  /**
  * Display the notice that wp_mail function was declared by another plugin
  *
  * return void
  */
  function wp_mail_already_declared_notice()
  {
    echo '<div class="error"><p>' . __( 'SendGrid: wp_mail has been declared by another process or plugin, so you won\'t be able to use SendGrid until the conflict is solved.' ) . '</p></div>';
  }

  return;
}

require_once plugin_dir_path( __FILE__ ) . 'lib/class-sendgrid-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'lib/class-sendgrid-statistics.php';
require_once plugin_dir_path( __FILE__ ) . 'lib/sendgrid/sendgrid-wp-mail.php';

// Initialize SendGrid Settings
new Sendgrid_Settings( plugin_basename( __FILE__ ) );

// Initialize SendGrid Statistics
new Sendgrid_Statistics();