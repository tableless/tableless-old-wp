<?php

class Sendgrid_Tools
{
  const CACHE_GROUP = "sendgrid";
  const CHECK_CREDENTIALS_CACHE_KEY = "sendgrid_credentials_check";
  const CHECK_API_KEY_CACHE_KEY = "sendgrid_api_key_check";
  const VALID_CREDENTIALS_STATUS = "valid";

  // used static variable because php 5.3 doesn't support array as constant
  public static $allowed_ports = array( Sendgrid_SMTP::TLS, Sendgrid_SMTP::TLS_ALTERNATIVE, Sendgrid_SMTP::SSL );
  public static $allowed_auth_methods = array( 'apikey', 'credentials' );
  public static $allowed_content_type = array( 'plaintext', 'html' );

  /**
   * Check username/password
   *
   * @param   string  $username   sendgrid username
   * @param   string  $password   sendgrid password
   *
   * @return  bool
   */
  public static function check_username_password( $username, $password, $clear_cache = false )
  {
    if ( ! $username or ! $password ) {
      return false;
    }

    if ( $clear_cache and is_multisite() ) {
      set_site_transient( self::CHECK_CREDENTIALS_CACHE_KEY, null );
    } elseif ( $clear_cache ) {
      set_transient( self::CHECK_CREDENTIALS_CACHE_KEY, null );
    }

    $valid_username_password = get_transient( self::CHECK_CREDENTIALS_CACHE_KEY );
    if ( is_multisite() ) {
      $valid_username_password = get_site_transient( self::CHECK_CREDENTIALS_CACHE_KEY );
    }

    if ( self::VALID_CREDENTIALS_STATUS == $valid_username_password ) {
      return true;
    }

    $url = 'https://api.sendgrid.com/api/profile.get.json?';
    $url .= "api_user=" . urlencode( $username ) . "&api_key=" . urlencode( $password );

    $response = wp_remote_get( $url, array( 'decompress' => false ) );
    
    if ( ! is_array( $response ) or ! isset( $response['body'] ) ) {
      return false;
    }

    $response = json_decode( $response['body'], true );

    if ( isset( $response['error'] ) ) {
      return false;
    }

    if ( is_multisite() ) {
      set_site_transient( self::CHECK_CREDENTIALS_CACHE_KEY, self::VALID_CREDENTIALS_STATUS, 2 * 60 * 60 );
    } else {
      set_transient( self::CHECK_CREDENTIALS_CACHE_KEY, self::VALID_CREDENTIALS_STATUS, 2 * 60 * 60 );
    }

    return true;
  }

  /**
   * Check apikey scopes
   *
   * @param   string  $apikey   sendgrid apikey
   *
   * @return  bool
   */
  public static function check_api_key_scopes( $apikey, $scopes )
  {
    if ( ! $apikey or ! is_array( $scopes ) ) {
      return false;
    }

    $url = 'https://api.sendgrid.com/v3/scopes';

    $args = array(
      'headers' => array(
        'Authorization' => 'Bearer ' . $apikey ),
      'decompress' => false
    );

    $response = wp_remote_get( $url, $args );

    if ( ! is_array( $response ) or ! isset( $response['body'] ) ) {
      return false;
    }

    $response = json_decode( $response['body'], true );

    if ( isset( $response['errors'] ) ) {
      return false;
    }

    if ( ! isset( $response['scopes'] ) ) {
      return false;
    }

    foreach ( $scopes as $scope ) {
      if ( ! in_array( $scope, $response['scopes'] ) ) {
        return false;
      }
    }

    return true;
  }

  /**
   * Check apikey
   *
   * @param   string  $apikey   sendgrid apikey
   *
   * @return  bool
   */
  public static function check_api_key( $apikey, $clear_cache = false )
  {
    if ( ! $apikey ) {
      return false;
    }

    if ( $clear_cache and is_multisite() ) {
      set_site_transient( self::CHECK_API_KEY_CACHE_KEY, null );
    } elseif ( $clear_cache ) {
      set_transient( self::CHECK_API_KEY_CACHE_KEY, null );
    }

    $valid_apikey = get_transient( self::CHECK_API_KEY_CACHE_KEY );
    if ( is_multisite() ) {
      $valid_apikey = get_site_transient( self::CHECK_API_KEY_CACHE_KEY );
    }

    if ( self::VALID_CREDENTIALS_STATUS == $valid_apikey ) {
      return true;
    }

    // check unsubscribe group permission
    if ( Sendgrid_Tools::check_api_key_scopes( $apikey, array( "asm.groups.read" ) ) ) {
      Sendgrid_Tools::set_asm_permission( 'true' );
    } else {
      Sendgrid_Tools::set_asm_permission( 'false' ); 
    }

    if ( ! Sendgrid_Tools::check_api_key_scopes( $apikey, array( "mail.send" ) ) ) {
      return false;
    }

    if ( is_multisite() ) {
      set_site_transient( self::CHECK_API_KEY_CACHE_KEY, self::VALID_CREDENTIALS_STATUS, 2 * 60 * 60 );
    } else {
      set_transient( self::CHECK_API_KEY_CACHE_KEY, self::VALID_CREDENTIALS_STATUS, 2 * 60 * 60 );
    }

    return true;
  }

  /**
   * Check template
   *
   * @param   string  $template   sendgrid template
   *
   * @return  bool
   */
  public static function check_template( $template )
  {
    if ( '' == $template ) {
      return true;
    }

    $url = 'v3/templates/' . $template;

    $parameters['auth_method']    = Sendgrid_Tools::get_auth_method();
    $parameters['api_username']   = Sendgrid_Tools::get_username();
    $parameters['api_password']   = Sendgrid_Tools::get_password();
    $parameters['apikey']         = Sendgrid_Tools::get_api_key();

    $response = Sendgrid_Tools::do_request( $url, $parameters );

    if ( ! $response ) {
      return false;
    }

    $response = json_decode( $response, true );
    if ( isset( $response['error'] ) or ( isset( $response['errors'] ) and isset( $response['errors'][0]['message'] ) ) ) {
      return false;
    }

    return true;
  }

  /**
   * Make request to SendGrid API
   *
   * @param   type  $api
   * @param   type  $parameters
   *
   * @return  json
   */
  public static function do_request( $api = 'v3/stats', $parameters = array() )
  {
    $args = array();
    if ( "credentials" == $parameters['auth_method'] ) {
      $creds = base64_encode( $parameters['api_username'] . ':' . $parameters['api_password'] );

      $args = array(
        'headers' => array(
          'Authorization' => 'Basic ' . $creds 
        ),
        'decompress' => false
      );

    } else {
      $args = array(
        'headers' => array(
          'Authorization' => 'Bearer ' . $parameters['apikey'] 
        ),
        'decompress' => false
      );
    }

    unset( $parameters['auth_method'] );
    unset( $parameters['api_username'] );
    unset( $parameters['api_password'] );
    unset( $parameters['apikey'] );

    $data = urldecode( http_build_query( $parameters ) );
    $url = "https://api.sendgrid.com/$api?$data";

    $response = wp_remote_get( $url, $args );

    if ( ! is_array( $response ) or ! isset( $response['body'] ) ) {
      return false;
    }

    return $response['body'];
  }

  /**
   * Return username from the database or global variable
   *
   * @return  mixed   username, false if the value is not found
   */
  public static function get_username()
  {
    if ( defined( 'SENDGRID_USERNAME' ) ) {
      return SENDGRID_USERNAME;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_username' );
    } else {
      $username = get_option( 'sendgrid_user' );
      if( $username ) {
        delete_option( 'sendgrid_user' );
        update_option( 'sendgrid_username', $username );
      }

      return get_option( 'sendgrid_username' );
    }
  }

  /**
   * Sets username in the database
   *
   * @param   type  string  $username
   *
   * @return  bool
   */
  public static function set_username( $username )
  {
    if ( is_multisite() ) {
      if( ! isset( $username ) ) {
        return update_site_option( 'sendgrid_username', '' );
      }

      return update_site_option( 'sendgrid_username', $username );
    } else {
      if ( ! isset( $username ) ) {
        return update_option( 'sendgrid_username', '' );
      }

      return update_option( 'sendgrid_username', $username );
    }
  }

  /**
   * Return password from the database or global variable
   *
   * @return  mixed  password, false if the value is not found
   */
  public static function get_password()
  {
    if ( defined( 'SENDGRID_PASSWORD' ) ) {
      return SENDGRID_PASSWORD;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_pwd' );
    } else {
      $password     = get_option( 'sendgrid_pwd' );
      $new_password = get_option( 'sendgrid_password' );
      if ( $new_password and ! $password ) {
        update_option( 'sendgrid_pwd', self::decrypt( $new_password, AUTH_KEY ) );
        delete_option( 'sendgrid_password' );
      }

      $password = get_option( 'sendgrid_pwd' );
      return $password;
    }
  }

  /**
   * Sets password in the database
   *
   * @param   type  string  $password
   *
   * @return  bool
   */
  public static function set_password( $password )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_pwd', $password );
    } else {
      return update_option( 'sendgrid_pwd', $password );
    }
  }

  /**
   * Return api_key from the database or global variable
   *
   * @return  mixed   api key, false if the value is not found
   */
  public static function get_api_key()
  {
    if ( defined( 'SENDGRID_API_KEY' ) ) {
      return SENDGRID_API_KEY;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_api_key' );
    } else {
      $apikey     = get_option( 'sendgrid_api_key' );
      $new_apikey = get_option( 'sendgrid_apikey' );
      if ( $new_apikey and ! $apikey ) {
        update_option( 'sendgrid_api_key', self::decrypt( $new_apikey, AUTH_KEY ) );
        delete_option( 'sendgrid_apikey' );
      }

      $apikey = get_option( 'sendgrid_api_key' );
      return $apikey;
    }
  }

  /**
   * Return MC api_key from the database or global variable
   *
   * @return  mixed   api key, false if the value is not found
   */
  public static function get_mc_api_key()
  {
    if ( defined( 'SENDGRID_MC_API_KEY' ) ) {
      return SENDGRID_MC_API_KEY;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_api_key' );
    } else {
      return get_option( 'sendgrid_mc_api_key' );
    }
  }

  /**
   * Return list_id from the database or global variable
   *
   * @return  mixed   list id, false if the value is not found
   */
  public static function get_mc_list_id()
  {
    if ( defined( 'SENDGRID_MC_LIST_ID' ) ) {
      return SENDGRID_MC_LIST_ID;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_list_id' );
    } else {
      return get_option( 'sendgrid_mc_list_id' );
    }
  }

  /**
   * Return the value for the option to use the transactional credentials from the database or global variable
   *
   * @return  mixed   'true' or 'false', false if the value is not found
   */
  public static function get_mc_opt_use_transactional()
  {
    if ( defined( 'SENDGRID_MC_OPT_USE_TRANSACTIONAL' ) ) {
      return SENDGRID_MC_OPT_USE_TRANSACTIONAL;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_opt_use_transactional' );
    } else {
      return get_option( 'sendgrid_mc_opt_use_transactional' );
    }
  }

  /**
   * Return the value for the option to require first name and last name on subscribe from the database or global variable
   *
   * @return  mixed  'true' or 'false', false if the value is not found
   */
  public static function get_mc_opt_req_fname_lname()
  {
    if ( defined( 'SENDGRID_MC_OPT_REQ_FNAME_LNAME' ) ) {
      return SENDGRID_MC_OPT_REQ_FNAME_LNAME;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_opt_req_fname_lname' );
    } else {
      return get_option( 'sendgrid_mc_opt_req_fname_lname' );
    }
  }

  /**
   * Return the value for the option to include first name and last name on subscribe from the database or global variable
   *
   * @return  mixed  'true' or 'false', false if the value is not found
   */
  public static function get_mc_opt_incl_fname_lname()
  {
    if ( defined( 'SENDGRID_MC_OPT_INCL_FNAME_LNAME' ) ) {
      return SENDGRID_MC_OPT_INCL_FNAME_LNAME;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_opt_incl_fname_lname' );
    } else {
      return get_option( 'sendgrid_mc_opt_incl_fname_lname' );
    }
  }

  /**
   * Return the value for the signup email subject from the database or global variable
   *
   * @return  mixed   signup email subject, false if the value is not found
   */
  public static function get_mc_signup_email_subject()
  {
    if ( defined( 'SENDGRID_MC_SIGNUP_EMAIL_SUBJECT' ) ) {
      return SENDGRID_MC_SIGNUP_EMAIL_SUBJECT;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_signup_email_subject' );
    } else {
      return get_option( 'sendgrid_mc_signup_email_subject' );
    }
  }

  /**
   * Return the value for the signup email contents from the database or global variable
   *
   * @return  mixed   signup email contents, false if the value is not found
   */
  public static function get_mc_signup_email_content()
  {
    if ( defined( 'SENDGRID_MC_SIGNUP_EMAIL_CONTENT' ) ) {
      return SENDGRID_MC_SIGNUP_EMAIL_CONTENT;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_signup_email_content' );
    } else {
      return get_option( 'sendgrid_mc_signup_email_content' );
    }
  }

  /**
   * Return the value for the signup email contents (plain text) from the database or global variable
   *
   * @return  mixed   signup email contents - plain text, false if the value is not found
   */
  public static function get_mc_signup_email_content_text()
  {
    if ( defined( 'SENDGRID_MC_SIGNUP_EMAIL_CONTENT_TEXT' ) ) {
      return SENDGRID_MC_SIGNUP_EMAIL_CONTENT_TEXT;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_signup_email_content_text' );
    } else {
      return get_option( 'sendgrid_mc_signup_email_content_text' );
    }
  }

  /**
   * Return the value for the signup confirmation page from the database or global variable
   *
   * @return  mixed   signup confirmation page, false if the value is not found
   */
  public static function get_mc_signup_confirmation_page()
  {
    if ( defined( 'SENDGRID_MC_SIGNUP_CONFIRMATION_PAGE' ) ) {
      return SENDGRID_MC_SIGNUP_CONFIRMATION_PAGE;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_signup_confirmation_page' );
    } else {
      return get_option( 'sendgrid_mc_signup_confirmation_page' );
    }
  }

  /**
   * Return the value for the signup confirmation page url
   *
   * @return  mixed   signup confirmation page url, false if the value is not found
   */
  public static function get_mc_signup_confirmation_page_url()
  {
    $page_id = self::get_mc_signup_confirmation_page();
    if ( false == $page_id or 'default' == $page_id ) {
      return false;
    }

    $confirmation_pages = get_pages( array( 'parent' => 0 ) );
    foreach ( $confirmation_pages as $key => $page ) {
      if ( $page->ID == $page_id ) {
        return $page->guid;
      }
    }

    return false;
  }

  /**
   * Return the value for flag that signifies if the MC authentication settings are valid
   *
   * @return  mixed  'true' or 'false', false if the value is not found
   */
  public static function get_mc_auth_valid()
  {
    if ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_auth_valid' );
    } else {
      return get_option( 'sendgrid_mc_auth_valid' );
    }
  }

  /**
   * Return the value for flag that signifies if the widget notice has been dismissed
   *
   * @return  mixed  'true' or 'false', false if the value is not found
   */
  public static function get_mc_widget_notice_dismissed()
  {
    if ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_widget_notice_dismissed' );
    } else {
      return get_option( 'sendgrid_mc_widget_notice_dismissed' );
    }
  }

  /**
   * Sets api_key in the database
   *
   * @param   type  string  $apikey
   *
   * @return  bool
   */
  public static function set_api_key( $apikey )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_api_key', $apikey );
    } else {
      return update_option( 'sendgrid_api_key', $apikey );
    }
  }

  /**
   * Sets MC api_key in the database
   *
   * @param   type  string  $apikey
   *
   * @return  bool
   */
  public static function set_mc_api_key( $apikey )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_api_key', $apikey );
    } else {
      return update_option( 'sendgrid_mc_api_key', $apikey );
    }
  }

  /**
   * Sets list id in the database
   *
   * @param   type  string  $list_id
   *
   * @return  bool
   */
  public static function set_mc_list_id( $list_id )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_list_id', $list_id );
    } else {
      return update_option( 'sendgrid_mc_list_id', $list_id );
    }
  }

  /**
   * Sets the value for the option to use the transactional credentials in the database
   *
   * @param   type  string  $use_transactional ( 'true' or 'false' )
   *
   * @return  bool
   */
  public static function set_mc_opt_use_transactional( $use_transactional )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_opt_use_transactional', $use_transactional );
    } else {
      return update_option( 'sendgrid_mc_opt_use_transactional', $use_transactional );
    }
  }

  /**
   * Sets the option for fname and lname requirement in the database
   *
   * @param   type  string  $req_fname_lname ( 'true' or 'false' )
   *
   * @return  bool
   */
  public static function set_mc_opt_req_fname_lname( $req_fname_lname )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_opt_req_fname_lname', $req_fname_lname );
    } else {
      return update_option( 'sendgrid_mc_opt_req_fname_lname', $req_fname_lname );
    }
  }

  /**
   * Sets the option for fname and lname inclusion in the database
   *
   * @param   type  string  $incl_fname_lname ( 'true' or 'false' )
   *
   * @return  bool
   */
  public static function set_mc_opt_incl_fname_lname( $incl_fname_lname )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_opt_incl_fname_lname', $incl_fname_lname );
    } else {
      return update_option( 'sendgrid_mc_opt_incl_fname_lname', $incl_fname_lname );
    }
  }

  /**
   * Sets the signup email subject in the database
   *
   * @param   type  string  $email_subject
   *
   * @return  bool
   */
  public static function set_mc_signup_email_subject( $email_subject )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_signup_email_subject', $email_subject );
    } else {
      return update_option( 'sendgrid_mc_signup_email_subject', $email_subject );
    }
  }

  /**
   * Sets the signup email contents in the database
   *
   * @param   type  string  $email_content
   *
   * @return  bool
   */
  public static function set_mc_signup_email_content( $email_content )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_signup_email_content', $email_content );
    } else {
      return update_option( 'sendgrid_mc_signup_email_content', $email_content );
    }
  }

  /**
   * Sets the signup email contents (plain text) in the database
   *
   * @param   type  string  $email_content
   *
   * @return  bool
   */
  public static function set_mc_signup_email_content_text( $email_content )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_signup_email_content_text', $email_content );
    } else {
      return update_option( 'sendgrid_mc_signup_email_content_text', $email_content );
    }
  }

  /**
   * Sets the signup confirmation page in the database
   *
   * @param   type  string  $confirmation_page
   *
   * @return  bool
   */
  public static function set_mc_signup_confirmation_page( $confirmation_page )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_signup_confirmation_page', $confirmation_page );
    } else {
      return update_option( 'sendgrid_mc_signup_confirmation_page', $confirmation_page );
    }
  }

  /**
   * Sets a flag that signifies that the authentication for MC is valid
   *
   * @param   type  string  $auth_valid ( 'true' or 'false' )
   *
   * @return  bool
   */
  public static function set_mc_auth_valid( $auth_valid )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_auth_valid', $auth_valid );
    } else {
      return update_option( 'sendgrid_mc_auth_valid', $auth_valid );
    }
  }

  /**
   * Sets a flag that signifies that the subscription widget notice has been dismissed
   *
   * @param   type  string  $notice_dismissed ( 'true' or 'false' )
   *
   * @return  bool
   */
  public static function set_mc_widget_notice_dismissed( $notice_dismissed )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_widget_notice_dismissed', $notice_dismissed );
    } else {
      return update_option( 'sendgrid_mc_widget_notice_dismissed', $notice_dismissed );
    }
  }

  /**
   * Return send method from the database or global variable
   *
   * @return  string  send_method
   */
  public static function get_send_method()
  {
    if ( defined( 'SENDGRID_SEND_METHOD' ) ) {
      return SENDGRID_SEND_METHOD;
    } elseif ( is_multisite() and get_site_option( 'sendgrid_api' ) ) {
      return get_site_option( 'sendgrid_api' );
    } elseif ( get_option( 'sendgrid_api' ) ) {
      return get_option( 'sendgrid_api' );
    } else {
      return 'api';
    }
  }

  /**
   * Sets the send method in the database
   *
   * @param   type  string  $method
   *
   * @return  bool
   */
  public static function set_send_method( $method )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_api', $method );
    } else {
      return update_option( 'sendgrid_api', $method );
    }
  }

  /**
   * Return auth method from the database or global variable
   *
   * @return  string  auth_method
   */
  public static function get_auth_method()
  {
    if ( defined( 'SENDGRID_AUTH_METHOD' ) ) {
      return SENDGRID_AUTH_METHOD;
    } elseif ( is_multisite() and get_site_option( 'sendgrid_auth_method' ) ) {
      return get_site_option( 'sendgrid_auth_method' );
    } elseif ( get_option( 'sendgrid_auth_method' ) ) {
      $auth_method = get_option( 'sendgrid_auth_method' );
      if ( 'username' == $auth_method ) {
        $auth_method = 'credentials';
        update_option( 'sendgrid_auth_method', $auth_method );
      }

      return $auth_method;
    } elseif ( Sendgrid_Tools::get_api_key() ) {
      return 'apikey';
    } elseif ( Sendgrid_Tools::get_username() and Sendgrid_Tools::get_password() ) {
      return 'credentials';
    } else {
      return 'apikey';
    }
  }

  /**
   * Sets the send method in the database
   *
   * @param   type  string  $method
   *
   * @return  bool
   */
  public static function set_auth_method( $method )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_auth_method', $method );
    } else {
      return update_option( 'sendgrid_auth_method', $method );
    }
  }

  /**
   * Return port from the database or global variable
   *
   * @return  mixed   port, false if the value is not found
   */
  public static function get_port()
  {
    if ( defined( 'SENDGRID_PORT' ) ) {
      return SENDGRID_PORT;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_port', Sendgrid_SMTP::TLS );
    } else {
      return get_option( 'sendgrid_port', Sendgrid_SMTP::TLS );
    }
  }

  /**
   * Sets the port in the database
   *
   * @param   type  string  $port
   *
   * @return  bool
   */
  public static function set_port( $port )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_port', $port );
    } else {
      return update_option( 'sendgrid_port', $port );
    }
  }

  /**
   * Return from name from the database or global variable
   *
   * @return  mixed   from_name, false if the value is not found
   */
  public static function get_from_name()
  {
    if ( defined( 'SENDGRID_FROM_NAME' ) ) {
      return SENDGRID_FROM_NAME;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_from_name' );
    } else {
      return get_option( 'sendgrid_from_name' );
    }
  }

  /**
   * Sets from name in the database
   *
   * @param   type  string  $name
   *
   * @return  bool
   */
  public static function set_from_name( $name )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_from_name', $name );
    } else {
      return update_option( 'sendgrid_from_name', $name );
    }
  }

  /**
   * Return from email address from the database or global variable
   *
   * @return  mixed  from_email, false if the value is not found
   */
  public static function get_from_email()
  {
    if ( defined( 'SENDGRID_FROM_EMAIL' ) ) {
      return SENDGRID_FROM_EMAIL;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_from_email' );
    } else {
      return get_option( 'sendgrid_from_email' );
    }
  }

  /**
   * Sets from email in the database
   *
   * @param   type  string  $email
   *
   * @return  bool
   */
  public static function set_from_email( $email )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_from_email', $email );
    } else {
      return update_option( 'sendgrid_from_email', $email );
    }
  }

  /**
   * Return reply to email address from the database or global variable
   *
   * @return  mixed  reply_to, false if the value is not found
   */
  public static function get_reply_to()
  {
    if ( defined( 'SENDGRID_REPLY_TO' ) ) {
      return SENDGRID_REPLY_TO;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_reply_to' );
    } else {
      return get_option( 'sendgrid_reply_to' );
    }
  }

  /**
   * Sets reply to email in the database
   *
   * @param   type  string  $email
   *
   * @return  bool
   */
  public static function set_reply_to( $email )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_reply_to', $email );
    } else {
      return update_option( 'sendgrid_reply_to', $email );
    }
  }

  /**
   * Return categories from the database or global variable
   *
   * @return  mixed  categories, false if the value is not found
   */
  public static function get_categories()
  {
    if ( defined( 'SENDGRID_CATEGORIES' ) ) {
      return SENDGRID_CATEGORIES;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_categories' );
    } else {
      return get_option( 'sendgrid_categories' );
    }
  }

  /**
   * Sets categories in the database
   *
   * @param   type  string  $categories
   *
   * @return  bool
   */
  public static function set_categories( $categories )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_categories', $categories );
    } else {
      return update_option( 'sendgrid_categories', $categories );
    }
  }

  /**
   * Return stats categories from the database or global variable
   *
   * @return  mixed  categories, false if the value is not found
   */
  public static function get_stats_categories()
  {
    if ( defined( 'SENDGRID_STATS_CATEGORIES' ) ) {
      return SENDGRID_STATS_CATEGORIES;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_stats_categories' );
    } else {
      return get_option( 'sendgrid_stats_categories' );
    }
  }

  /**
   * Sets stats categories in the database
   *
   * @param   type  string  $categories
   *
   * @return  bool
   */
  public static function set_stats_categories( $categories )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_stats_categories', $categories );
    } else {
      return update_option( 'sendgrid_stats_categories', $categories );
    }
  }

  /**
   * Return categories array
   *
   * @return  array   categories
   */
  public static function get_categories_array()
  {
    $general_categories       = Sendgrid_Tools::get_categories();
    $stats_categories         = Sendgrid_Tools::get_stats_categories();
    $general_categories_array = $general_categories? explode( ',', trim( $general_categories ) ):array();
    $stats_categories_array   = $stats_categories? explode( ',', trim( $stats_categories ) ):array();
    return array_unique( array_merge( $general_categories_array, $stats_categories_array ) );
  }

  /**
   * Return template from the database or global variable
   *
   * @return  mixed  template string, false if the value is not found
   */
  public static function get_template()
  {
    if ( defined( 'SENDGRID_TEMPLATE' ) ) {
      return SENDGRID_TEMPLATE;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_template' );
    } else {
      return get_option( 'sendgrid_template' );
    }
  }

  /**
   * Sets the template in the database
   *
   * @param   type  string  $template
   *
   * @return  bool
   */
  public static function set_template( $template )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_template', $template );
    } else {
      return update_option( 'sendgrid_template', $template );
    }
  }

  /**
   * Return content type from the database or global variable
   *
   * @return  mixed  content_type string, false if the value is not found
   */
  public static function get_content_type()
  {
    if ( defined( 'SENDGRID_CONTENT_TYPE' ) ) {
      return SENDGRID_CONTENT_TYPE;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_content_type' );
    } else {
      return get_option( 'sendgrid_content_type' );
    }
  }

  /**
   * Sets the unsubscribe group in the database
   *
   * @param   type  string  $unsubscribe_group
   *
   * @return  bool
   */
  public static function set_unsubscribe_group( $unsubscribe_group )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_unsubscribe_group', $unsubscribe_group );
    } else {
      return update_option( 'sendgrid_unsubscribe_group', $unsubscribe_group );
    }
  }

  /**
   * Return unsubscribe group from the database or global variable
   *
   * @return  mixed  unsubscribe group string, false if the value is not found
   */
  public static function get_unsubscribe_group()
  {
    if ( defined( 'SENDGRID_UNSUBSCRIBE_GROUP' ) ) {
      return SENDGRID_UNSUBSCRIBE_GROUP;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_unsubscribe_group' );
    } else {
      return get_option( 'sendgrid_unsubscribe_group' );
    }
  }

  /**
   * Set asm_permission value in db
   *
   * @param   type  string  $permission
   *
   * @return  bool
   */
  public static function set_asm_permission( $permission )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_asm_permission', $permission );
    } else {
      return update_option( 'sendgrid_asm_permission', $permission );
    }
  }

  /**
   * Get asm_permission value from db
   *
   * @return  mixed  asm_permission value
   */
  public static function get_asm_permission()
  {
    if ( is_multisite() ) {
      return get_site_option( 'sendgrid_asm_permission' );
    } else {
      return get_option( 'sendgrid_asm_permission' );
    }
  }

  /**
   * Returns the unsubscribe groups from SendGrid
   *
   * @return  mixed   an array of groups if the request is successful, false otherwise.
   */
  public static function get_all_unsubscribe_groups()
  {
    $url = 'v3/asm/groups';

    $parameters['auth_method']    = Sendgrid_Tools::get_auth_method();
    $parameters['api_username']   = Sendgrid_Tools::get_username();
    $parameters['api_password']   = Sendgrid_Tools::get_password();
    $parameters['apikey']         = Sendgrid_Tools::get_api_key();

    if ( ( 'apikey' == $parameters['auth_method'] ) and ( 'true' != self::get_asm_permission() ) ) {
      return false;  
    }

    $response = Sendgrid_Tools::do_request( $url, $parameters );

    if ( ! $response ) {
      return false;
    }

    $response = json_decode( $response, true );
    if ( isset( $response['error'] ) or ( isset( $response['errors'] ) and isset( $response['errors'][0]['message'] ) ) ) {
      return false;
    }

    return $response;
  }

  /**
   * Sets the content-type in the database
   *
   * @param   type  string  $content_type
   *
   * @return  bool
   */
  public static function set_content_type( $content_type )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_content_type', $content_type );
    } else {
      return update_option( 'sendgrid_content_type', $content_type );
    }
  }

  /**
   * Sets email label in the database
   *
   * @param   type  string  $email_label
   *
   * @return  bool
   */
  public static function set_mc_email_label( $email_label )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_email_label', $email_label );
    } else {
      return update_option( 'sendgrid_mc_email_label', $email_label );
    }
  }

  /**
   * Return email label from the database or global variable
   *
   * @return  mixed   email, false if the value is not found
   */
  public static function get_mc_email_label()
  {
    if ( defined( 'SENDGRID_MC_EMAIL_LABEL' ) ) {
      return SENDGRID_MC_EMAIL_LABEL;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_email_label' );
    } else {
      return get_option( 'sendgrid_mc_email_label' );
    }
  }

  /**
   * Sets first name label in the database
   *
   * @param   type  string  $first_name_label
   *
   * @return  bool
   */
  public static function set_mc_first_name_label( $first_name_label )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_first_name_label', $first_name_label );
    } else {
      return update_option( 'sendgrid_mc_first_name_label', $first_name_label );
    }
  }

  /**
   * Return first name label from the database or global variable
   *
   * @return  mixed   label, false if the value is not found
   */
  public static function get_mc_first_name_label()
  {
    if ( defined( 'SENDGRID_MC_FIRST_NAME_LABEL' ) ) {
      return SENDGRID_MC_FIRST_NAME_LABEL;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_first_name_label' );
    } else {
      return get_option( 'sendgrid_mc_first_name_label' );
    }
  }

  /**
   * Sets last name label in the database
   *
   * @param   type  string  $last_name_label
   *
   * @return  bool
   */
  public static function set_mc_last_name_label( $last_name_label )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_last_name_label', $last_name_label );
    } else {
      return update_option( 'sendgrid_mc_last_name_label', $last_name_label );
    }
  }

  /**
   * Return last name label from the database or global variable
   *
   * @return  mixed   label, false if the value is not found
   */
  public static function get_mc_last_name_label()
  {
    if ( defined( 'SENDGRID_MC_LAST_NAME_LABEL' ) ) {
      return SENDGRID_MC_LAST_NAME_LABEL;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_last_name_label' );
    } else {
      return get_option( 'sendgrid_mc_last_name_label' );
    }
  }

  /**
   * Sets subscribe label in the database
   *
   * @param   type  string  $subscribe_label
   *
   * @return  bool
   */
  public static function set_mc_subscribe_label( $subscribe_label )
  {
    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_subscribe_label', $subscribe_label );
    } else {
      return update_option( 'sendgrid_mc_subscribe_label', $subscribe_label );
    }
  }

  /**
   * Return subscribe label from the database or global variable
   *
   * @return  mixed   label, false if the value is not found
   */
  public static function get_mc_subscribe_label()
  {
    if ( defined( 'SENDGRID_MC_SUBSCRIBE_LABEL' ) ) {
      return SENDGRID_MC_SUBSCRIBE_LABEL;
    } elseif ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_subscribe_label' );
    } else {
      return get_option( 'sendgrid_mc_subscribe_label' );
    }
  }

  /**
   * Sets input padding in the database
   *
   * @param   type  string  $position
   * @param   type  int     $value
   *
   * @return  bool
   */
  public static function set_mc_input_padding( $position, $value = 0 )
  {
    if ( "" == $value ) {
      $value = 0;
    }
    $values = json_decode( self::get_mc_input_padding(), true ) ;
    if ( !isset( $values ) or !is_array($values) ) {
      $values = array(
        'top'     => 10,
        'right'   => 0,
        'bottom'  => 0,
        'left'    => 0
      );
    }

    // set the new value
    $values[$position] = $value;

    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_input_padding', json_encode( $values ) );
    } else {
      return update_option( 'sendgrid_mc_input_padding', json_encode( $values ) );
    }
  }

  /**
   * Return input padding by from the database
   *
   * @return  mixed   json with the padding value, false if the value is not found
   */
  public static function get_mc_input_padding()
  {
    if ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_input_padding' );
    } else {
      return get_option( 'sendgrid_mc_input_padding' );
    }
  }

  /**
   * Return input padding by position from the database
   *
   * @param   string    $position       position   
   * @return  integer                   padding value
   */
  public static function get_mc_input_padding_by_position( $position )
  {
    if ( is_multisite() ) {
      $padding = get_site_option( 'sendgrid_mc_input_padding' );
    } else {
      $padding = get_option( 'sendgrid_mc_input_padding' );
    }

    if ( false == $padding ) {
      if ( $position == "top" )
      {
        return 10;
      }

      return 0;
    }
    $padding = json_decode( $padding, true );
    if ( !isset( $padding[$position] ) ) {
      return 0;
    }

    return $padding[$position];
  }

  /**
   * Sets button padding in the database
   *
   * @param   type  string  $position
   * @param   type  int     $value
   *
   * @return  bool
   */
  public static function set_mc_button_padding( $position, $value = 0 )
  {
    if ( "" == $value ) {
      $value = 0;
    }
    $values = json_decode( self::get_mc_button_padding(), true );
    if ( !isset( $values ) or !is_array($values) ) {
      $values = array(
        'top'     => 10,
        'right'   => 0,
        'bottom'  => 0,
        'left'    => 0
      );
    }

    // set the new value
    $values[$position] = $value;

    if ( is_multisite() ) {
      return update_site_option( 'sendgrid_mc_button_padding', json_encode( $values ) );
    } else {
      return update_option( 'sendgrid_mc_button_padding', json_encode( $values ) );
    }
  }

  /**
   * Return button padding by from the database
   *
   * @return  mixed   json with the padding value, false if the value is not found
   */
  public static function get_mc_button_padding()
  {
    if ( is_multisite() ) {
      return get_site_option( 'sendgrid_mc_button_padding' );
    } else {
      return get_option( 'sendgrid_mc_button_padding' );
    }
  }

  /**
   * Return button padding by position from the database
   *
   * @param   string    $position   position   
   * @return  integer               padding value
   */
  public static function get_mc_button_padding_by_position( $position )
  {
    if ( is_multisite() ) {
      $padding = get_site_option( 'sendgrid_mc_button_padding' );
    } else {
      $padding = get_option( 'sendgrid_mc_button_padding' );
    }

    if ( false == $padding ) {
      if ( $position == "top" )
      {
        return 10;
      }

      return 0;
    }
    $padding = json_decode( $padding, true );
    if ( !isset( $padding[$position] ) ) {
      return 0;
    }

    return $padding[$position];
  }

  /**
   * Returns decrypted string using the key or empty string in case of error
   *
   * @return  string
   */
  private static function decrypt( $encrypted_input_string, $key ) {
    if ( ! extension_loaded( 'mcrypt' ) ) {
      return '';
    }

    $iv_size = mcrypt_get_iv_size( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB );
    if( false === $iv_size ) {
      return '';
    }

    $iv = mcrypt_create_iv( $iv_size, MCRYPT_RAND );
    if( false === $iv ) {
      return '';
    }

    $h_key = hash( 'sha256', $key, TRUE );
    $decoded = base64_decode( $encrypted_input_string );
    if( false === $decoded ) {
      return '';
    }

    $decrypted = mcrypt_decrypt( MCRYPT_RIJNDAEL_256, $h_key, $decoded, MCRYPT_MODE_ECB, $iv );
    if( false === $decrypted ) {
      return '';
    }

    return trim( $decrypted );
  }

  /**
   * Check apikey stats permissions
   *
   * @param   string  $apikey   sendgrid apikey
   *
   * @return  bool
   */
  public static function check_api_key_stats( $apikey )
  {
    $required_scopes = array( 'stats.read', 'categories.stats.read', 'categories.stats.sums.read' );

    return Sendgrid_Tools::check_api_key_scopes( $apikey, $required_scopes );
  }

  /**
   * Check apikey marketing campaigns permissions
   *
   * @param   string  $apikey   sendgrid apikey
   *
   * @return  bool
   */
  public static function check_api_key_mc( $apikey )
  {
    $required_scopes = array( 'marketing_campaigns.create', 'marketing_campaigns.read', 'marketing_campaigns.update', 'marketing_campaigns.delete' );

    return Sendgrid_Tools::check_api_key_scopes( $apikey, $required_scopes );
  }

  /**
   * Returns true if the email is valid, false otherwise
   *
   * @return bool
   */
  public static function is_valid_email( $email )
  {
    if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) and ( SendGrid_ThirdParty::is_email( $email ) ) ) {
      return true;
    }

    return false;
  }

  /**
   * Returns true if all the emails in the headers are valid, false otherwise
   *
   * @param   mixed  $headers   string or array of headers
   *
   * @return  bool
   */
  public static function valid_emails_in_headers( $headers )
  {
    if ( ! is_array( $headers ) ) {
      // Explode the headers out, so this function can take both
      // string headers and an array of headers.
      $tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
    } else {
      $tempheaders = $headers;
    }

    // If it's actually got contents
    if ( ! empty( $tempheaders ) ) {
      // Iterate through the raw headers
      foreach ( (array) $tempheaders as $header ) {
        if ( false === strpos( $header, ':' ) ) {
          continue;
        }
        // Explode them out
        list( $name, $content ) = explode( ':', trim( $header ), 2 );

        // Cleanup crew
        $name    = trim( $name );
        $content = trim( $content );

        switch ( strtolower( $name ) ) {
          // Mainly for legacy -- process a From: header if it's there
          case 'from':
            if ( false !== strpos( $content, '<' ) ) {
              $from_email = substr( $content, strpos( $content, '<' ) + 1 );
              $from_email = str_replace( '>', '', $from_email );
              $from_email = trim( $from_email );
            } else {
              $from_email = trim( $content );
            }

            if( ! Sendgrid_Tools::is_valid_email( $from_email ) ) {
              return false;
            }

            break;
          case 'cc':
            $cc = explode( ',', $content );
            foreach ( $cc as $key => $recipient ) {
              if( ! Sendgrid_Tools::is_valid_email( trim( $recipient ) ) ) {
                return false;
              }
            }

            break;
          case 'bcc':
            $bcc = explode( ',', $content );
            foreach ( $bcc as $key => $recipient ) {
              if( ! Sendgrid_Tools::is_valid_email( trim( $recipient ) ) ) {
                return false;
              }
            }

            break;
          case 'reply-to':
            if( ! Sendgrid_Tools::is_valid_email( $content ) ) {
              return false;
            }

            break;
          case 'x-smtpapi-to':
            $xsmtpapi_tos = explode( ',', trim( $content ) );
            foreach ( $xsmtpapi_tos as $xsmtpapi_to ) {
              if( ! Sendgrid_Tools::is_valid_email( $xsmtpapi_to ) ) {
                return false;
              }
            }

            break;
          default:
            break;
        }
      }
    }

    return true;
  }

  /**
   * Returns the string content of the input with "<url>" replaced by "url"
   *
   * @return  string
   */
  public static function remove_all_tag_urls( $content )
  {
    return preg_replace('/<(https?:\/\/[^>]*)>/im', '$1', $content);
  }
}

/**
 * Function that registers the SendGrid plugin widgets
 *
 * @return void
 */
function register_sendgrid_widgets() {
  register_widget( 'SendGrid_NLVX_Widget' );
}

/**
 * Function that unregisters the SendGrid plugin widgets
 *
 * @return void
 */
function unregister_sendgrid_widgets() {
  unregister_widget( 'SendGrid_NLVX_Widget' );
}

/**
 * Function that outputs the SendGrid widget notice
 *
 * @return void
 */
function sg_subscription_widget_admin_notice() {
  if( ! current_user_can('manage_options') ) {
    return;
  }

  echo '<div class="notice notice-success">';
  echo '<p>';
  echo _e( 'Check out the new SendGrid Subscription Widget! See the SendGrid Plugin settings page in order to configure it.' );
  echo '<form method="post" id="sendgrid_mc_email_form" class="mc_email_form" action="#">';
  echo '<input type="hidden" name="sg_dismiss_widget_notice" id="sg_dismiss_widget_notice" value="true"/>';
  echo '<input type="submit" id="sendgrid_mc_email_submit" value="Dismiss this notice" style="padding: 0!important; font-size: small; background: none; border: none; color: #0066ff; text-decoration: underline; cursor: pointer;" />';
  echo '</form>';
  echo '</p>';
  echo '</div>';
}