<?php
/*
Plugin Name: SendGrid
Plugin URI: http://wordpress.org/plugins/sendgrid-email-delivery-simplified/
Description: Email Delivery. Simplified. SendGrid's cloud-based email infrastructure relieves businesses of the cost and complexity of maintaining custom email systems. SendGrid provides reliable delivery, scalability and real-time analytics along with flexible APIs that make custom integration a breeze.
Version: 1.10.2
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

// Load plugin files
require_once plugin_dir_path( __FILE__ ) . 'lib/class-sendgrid-tools.php';
require_once plugin_dir_path( __FILE__ ) . 'lib/class-sendgrid-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'lib/class-sendgrid-mc-optin.php';
require_once plugin_dir_path( __FILE__ ) . 'lib/class-sendgrid-statistics.php';
require_once plugin_dir_path( __FILE__ ) . 'lib/sendgrid/sendgrid-wp-mail.php';
require_once plugin_dir_path( __FILE__ ) . 'lib/class-sendgrid-nlvx-widget.php';
require_once plugin_dir_path( __FILE__ ) . 'lib/class-sendgrid-virtual-pages.php';
require_once plugin_dir_path( __FILE__ ) . 'lib/class-sendgrid-filters.php';

// Widget Registration
if ( 'true' == Sendgrid_Tools::get_mc_auth_valid() ) {
  add_action( 'widgets_init', 'register_sendgrid_widgets' );
} else {
  add_action( 'widgets_init', 'unregister_sendgrid_widgets' );
}

// Widget notice dismissed
if ( isset( $_POST['sg_dismiss_widget_notice'] ) ) {
  Sendgrid_Tools::set_mc_widget_notice_dismissed( 'true' );
}

// Display widget notice
if ( 'true' != Sendgrid_Tools::get_mc_widget_notice_dismissed() ) {
  add_action( 'admin_notices', 'sg_subscription_widget_admin_notice' );
}

// Initialize SendGrid Settings
new Sendgrid_Settings( plugin_basename( __FILE__ ) );

// Initialize SendGrid Statistics
new Sendgrid_Statistics();

// Initialize SendGrid Filters
new Sendgrid_Filters();