<?php

require_once plugin_dir_path( __FILE__ ) . 'class-sendgrid-api.php';
require_once plugin_dir_path( __FILE__ ) . 'class-sendgrid-smtp.php';
require_once plugin_dir_path( __FILE__ ) . '../class-sendgrid-tools.php';

class Sendgrid_WP {
  private function __construct() {}

  public static $error;

  public static function get_instance() {
    $send_method = Sendgrid_Tools::get_send_method();
    $auth_method = Sendgrid_Tools::get_auth_method();

    switch ( $send_method ) {
      case 'api':
        return self::api_instance( $auth_method );
        break;

      case 'smtp':
        return self::smtp_instance( $auth_method );
        break;
    }

    return self::api_instance( $auth_method );
  }

  private static function api_instance( $auth_method ) {
    switch ( $auth_method ) {
      case 'apikey':
        return new Sendgrid_API( "apikey", Sendgrid_Tools::get_api_key() );
        break;
      
      case 'credentials':
        return new Sendgrid_API( Sendgrid_Tools::get_username(), Sendgrid_Tools::get_password() );
        break;
    }

    return null;
  }

  private static function smtp_instance( $auth_method ) 
  {
    if ( ! class_exists('Swift') ) {
      self::$error = array(
        "success" => false,
        "message" => "Swift Class not loaded. Please activate Swift plugin or use API."
      );

      return null;
    } 

    switch ( $auth_method ) {
      case 'apikey':
        $smtp = new Sendgrid_SMTP( "apikey", Sendgrid_Tools::get_api_key() );
        break;

      case 'credentials':
        $smtp = new Sendgrid_SMTP( Sendgrid_Tools::get_username(), Sendgrid_Tools::get_password() );
        break;

      default:
        return null;
        break;
    }

    if ( Sendgrid_Tools::get_port() ) {
      if ( in_array( Sendgrid_Tools::get_port(), Sendgrid_Tools::$allowed_ports ) ) {
        $smtp->set_port( Sendgrid_Tools::get_port() );
      } else {
        $smtp->set_port( Sendgrid_SMTP::TLS );
      }
    }

    return $smtp;
  }
}