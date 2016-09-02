<?php

require_once plugin_dir_path( __FILE__ ) . 'interfaces/class-sendgrid-interface.php';
require_once plugin_dir_path( __FILE__ ) . '../../vendor/autoload.php';

class Sendgrid_API implements Sendgrid_Send {

  const URL = "https://api.sendgrid.com/api/mail.send.json";

  private $username;
  private $password;
  private $apikey;
  private $method;

  public function __construct( $username, $password_or_apikey ) {
    if ( "apikey" == $username ) {
      $this->method = "apikey";
      $this->apikey = $password_or_apikey;
    } else {
      $this->method = "credentials";
      $this->username = $username;
      $this->password = $password_or_apikey;
    }
  }

  public function send(SendGrid\Email $email) {
    $fields    = $email->toWebFormat();
    $headers = array();

    if ( "credentials" == $this->method ) {
      $fields['api_user'] = $this->username; 
      $fields['api_key']  = $this->password;
    } else {
      $headers = array(
        'Authorization' => 'Bearer ' . $this->apikey
      );
    }

    $files = preg_grep( '/files/', array_keys( $fields ) );
    foreach($files as $k => $file) {
      $fields[$file] = file_get_contents( substr( $fields[$file], 1 ) );
    }
    

    $data = array( 'body' => $fields, 'decompress' => false );
    if ( count( $headers ) ) {
      $data['headers'] = $headers;
    }

    $response = wp_remote_post( self::URL, $data );
    if ( !is_array( $response ) or !isset( $response['body'] ) )
      return false;

    if ( "success" == json_decode( $response['body'])->message )
      return true;

    return false;
  }
}