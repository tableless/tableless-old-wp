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
      update_option( 'sendgrid_asm_permission', 'true' );
    } else {
      update_option( 'sendgrid_asm_permission', 'false' ); 
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
    if( ! isset( $username ) ) {
      return update_option( 'sendgrid_username', '' );
    }

    return update_option( 'sendgrid_username', $username );
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
    } else {
      $password     = get_option( 'sendgrid_pwd' );
      $new_password = get_option( 'sendgrid_password' );
      if( $new_password and ! $password ) {
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
    return update_option( 'sendgrid_pwd', $password );
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
    } else {
      $apikey     = get_option( 'sendgrid_api_key' );
      $new_apikey = get_option( 'sendgrid_apikey' );
      if( $new_apikey and ! $apikey ) {
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
    foreach ($confirmation_pages as $key => $page) {
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
    return get_option( 'sendgrid_mc_auth_valid' );
  }

  /**
   * Return the value for flag that signifies if the widget notice has been dismissed
   *
   * @return  mixed  'true' or 'false', false if the value is not found
   */
  public static function get_mc_widget_notice_dismissed()
  {
    return get_option( 'sendgrid_mc_widget_notice_dismissed' );
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
    return update_option( 'sendgrid_api_key', $apikey );
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
    return update_option( 'sendgrid_mc_api_key', $apikey );
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
    return update_option( 'sendgrid_mc_list_id', $list_id );
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
    return update_option( 'sendgrid_mc_opt_use_transactional', $use_transactional );
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
    return update_option( 'sendgrid_mc_opt_req_fname_lname', $req_fname_lname );
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
    return update_option( 'sendgrid_mc_opt_incl_fname_lname', $incl_fname_lname );
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
    return update_option( 'sendgrid_mc_signup_email_subject', $email_subject );
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
    return update_option( 'sendgrid_mc_signup_email_content', $email_content );
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
    return update_option( 'sendgrid_mc_signup_email_content_text', $email_content );
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
    return update_option( 'sendgrid_mc_signup_confirmation_page', $confirmation_page );
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
    return update_option( 'sendgrid_mc_auth_valid', $auth_valid );
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
    return update_option( 'sendgrid_mc_widget_notice_dismissed', $notice_dismissed );
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
    } elseif ( get_option( 'sendgrid_api' ) ) {
      return get_option( 'sendgrid_api' );
    } else {
      return 'api';
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
   * Return port from the database or global variable
   *
   * @return  mixed   port, false if the value is not found
   */
  public static function get_port()
  {
    if ( defined( 'SENDGRID_PORT' ) ) {
      return SENDGRID_PORT;
    } else {
      return get_option( 'sendgrid_port', Sendgrid_SMTP::TLS );
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
    } else {
      return get_option( 'sendgrid_from_name' );
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
    } else {
      return get_option( 'sendgrid_from_email' );
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
    } else {
      return get_option( 'sendgrid_reply_to' );
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
    } else {
      return get_option( 'sendgrid_categories' );
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
    } else {
      return get_option( 'sendgrid_stats_categories' );
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
    } else {
      return get_option( 'sendgrid_template' );
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
    return update_option( 'sendgrid_unsubscribe_group', $unsubscribe_group );
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
    } else {
      return get_option( 'sendgrid_unsubscribe_group' );
    }
  }

  /**
   * Get asm_permission value from db
   *
   * @return  mixed  asm_permission value
   */
  public static function get_asm_permission()
  {
    return get_option( 'sendgrid_asm_permission' );
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