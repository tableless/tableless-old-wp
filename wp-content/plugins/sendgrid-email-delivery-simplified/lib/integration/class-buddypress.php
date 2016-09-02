<?php
class Sendgrid_BuddyPress_Integration {
    public function __construct() {
    }

    public function register() {
      if ( ! defined( 'SENDGRID_DISABLE_BUDDYPRESS' ) ) {
        add_filter( 'bp_email_use_wp_mail', array( $this, 'buddypress_email_use_wp_mail' ), 10, 1 );
        add_filter( 'bp_send_email_delivery_class', array( $this, 'buddypress_email_delivery_class' ), 10, 4 );
      }
    }

    public function buddypress_email_use_wp_mail( $bool ) {
      return false;
    }

    public function buddypress_email_delivery_class( $class, $email_type, $to, $args ) {
      // This will cause an error if BP_Email_Delivery is not defined.
      // But the only one to apply this filter will be buddypress, and we need to declare the override before giving the class name.
      if ( ! defined( 'SENDGRID_DISABLE_BUDDYPRESS' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'class-buddypress-override.php';
      }
      return 'SendGrid_BuddyPress_Mailer'; 
    }
}