<?php

class Sendgrid_Tools
{
  const CACHE_GROUP = "sendgrid";
  const CHECK_CREDENTIALS_CACHE_KEY = "sendgrid_credentials_check";
  const CHECK_API_KEY_CACHE_KEY = "sendgrid_api_key_check";
  const VALID_CREDENTIALS_STATUS = "valid";
  const INVALID_CREDENTIALS_STATUS = "invalid";

  // used static variable because php 5.3 doesn't support array as constant
  public static $allowed_ports = array( Sendgrid_SMTP::TLS, Sendgrid_SMTP::TLS_ALTERNATIVE, Sendgrid_SMTP::SSL );
  public static $allowed_auth_methods = array( 'apikey', 'credentials' );
  public static $allowed_content_type = array( 'plaintext', 'html' );

  /**
   * Check username/password
   *
   * @param   string  $username   sendgrid username
   * @param   string  $password   sendgrid password
   * @return  bool
   */
  public static function check_username_password( $username, $password, $clear_cache = false )
  {
    if ( !$username or !$password ) {
      return false;
    }

    if ( $clear_cache ) {
      wp_cache_delete( self::CHECK_CREDENTIALS_CACHE_KEY, self::CACHE_GROUP );
    }

    $valid_username_password = wp_cache_get( self::CHECK_CREDENTIALS_CACHE_KEY, self::CACHE_GROUP );
    if ( self::VALID_CREDENTIALS_STATUS == $valid_username_password ) {
      return true;
    } elseif ( self::INVALID_CREDENTIALS_STATUS == $valid_username_password ) {
      return false;
    }

    $url = 'https://api.sendgrid.com/api/profile.get.json?';
    $url .= "api_user=" . urlencode($username) . "&api_key=" . urlencode($password);

    $response = wp_remote_get( $url );
    
    if ( ! is_array( $response ) or ! isset( $response['body'] ) )
    {
      wp_cache_set( self::CHECK_CREDENTIALS_CACHE_KEY, self::INVALID_CREDENTIALS_STATUS, self::CACHE_GROUP, 60 );

      return false;
    }

    $response = json_decode( $response['body'], true );

    if ( isset( $response['error'] ) )
    {
      wp_cache_set( self::CHECK_CREDENTIALS_CACHE_KEY, self::INVALID_CREDENTIALS_STATUS, self::CACHE_GROUP, 60 );

      return false;
    }

    wp_cache_set( self::CHECK_CREDENTIALS_CACHE_KEY, self::VALID_CREDENTIALS_STATUS, self::CACHE_GROUP, 1800 );

    return true;
  }

  /**
   * Check apikey scopes
   *
   * @param   string  $apikey   sendgrid apikey
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
        'Authorization' => 'Bearer ' . $apikey )
    );

    $response = wp_remote_get( $url, $args );

    if ( ! is_array( $response ) or ! isset( $response['body'] ) ) {
      wp_cache_set( self::CHECK_API_KEY_CACHE_KEY, self::INVALID_CREDENTIALS_STATUS, self::CACHE_GROUP, 60 );

      return false;
    }

    $response = json_decode( $response['body'], true );

    if ( isset( $response['errors'] ) ) {
      return false;
    }

    if( ! isset( $response['scopes'] ) ) {
      return false;
    }

    foreach( $scopes as $scope ) {
        if( !in_array( $scope, $response['scopes'] ) ) {
          return false;
        }
    }

    return true;
  }

  /**
   * Check apikey
   *
   * @param   string  $apikey   sendgrid apikey
   * @return  bool
   */
  public static function check_api_key( $apikey, $clear_cache = false )
  {
    if ( ! $apikey ) {
      return false;
    }

    if ( $clear_cache ) {
      wp_cache_delete( self::CHECK_API_KEY_CACHE_KEY, self::CACHE_GROUP );
    }

    $valid_apikey = wp_cache_get( self::CHECK_API_KEY_CACHE_KEY, self::CACHE_GROUP );
    if ( self::VALID_CREDENTIALS_STATUS == $valid_apikey ) {
      return true;
    } elseif ( self::INVALID_CREDENTIALS_STATUS == $valid_apikey ) {
      return false;
    }

    if( ! Sendgrid_Tools::check_api_key_scopes( $apikey, array( "mail.send" ) ) ) {
      wp_cache_set( self::CHECK_API_KEY_CACHE_KEY, self::INVALID_CREDENTIALS_STATUS, self::CACHE_GROUP, 60 );

      return false;
    }

    wp_cache_set( self::CHECK_API_KEY_CACHE_KEY, self::VALID_CREDENTIALS_STATUS, self::CACHE_GROUP, 1800 );

    return true;
  }

  /**
   * Check template
   *
   * @param   string  $template   sendgrid template
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
   * @param type $api
   * @param type $parameters
   * @return json
   */
  public static function do_request( $api = 'v3/stats', $parameters = array() )
  {
    $args = array();
    if ( "credentials" == $parameters['auth_method'] ) {
      $creds = base64_encode( $parameters['api_username'] . ':' . $parameters['api_password'] );

      $args = array(
        'headers' => array(
          'Authorization' => 'Basic ' . $creds 
        )
      );

    } else {
      $args = array(
        'headers' => array(
          'Authorization' => 'Bearer ' . $parameters['apikey'] 
        )
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
   * @return string username
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
   * @param type string $username
   * @return bool
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
   * @return string password
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
   * @param type string $password
   * @return bool
   */
  public static function set_password( $password )
  {
    return update_option( 'sendgrid_pwd', $password );
  }

  /**
   * Return api_key from the database or global variable
   *
   * @return string api key
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
   * Sets api_key in the database
   * @param type string $apikey
   * @return bool
   */
  public static function set_api_key( $apikey )
  {
    return update_option( 'sendgrid_api_key', $apikey );
  }

  /**
   * Return send method from the database or global variable
   *
   * @return string send_method
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
   * @return string auth_method
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
   * @return string port
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
   * @return string from_name
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
   * @return string from_email
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
   * @return string reply_to
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
   * @return string categories
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
   * @return string categories
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
   * @return array categories
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
   * @return string template
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
   * @return string content_type
   */
  public static function get_content_type()
  {
    if ( defined( 'SENDGRID_CONTENT_TYPE' ) ) {
      return SENDGRID_CONTENT_TYPE;
    } else {
      return get_option('sendgrid_content_type');
    }
  }

  /**
   * Returns decrypted string using the key or empty string in case of error
   *
   * @return string template
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
   * @return  bool
   */
  public static function check_api_key_stats( $apikey )
  {
    $required_scopes = array( 'stats.read', 'categories.stats.read', 'categories.stats.sums.read' );

    return Sendgrid_Tools::check_api_key_scopes( $apikey, $required_scopes );
  }

  /**
   * Returns true if the email is valid, false otherwise
   *
   * @return bool
   */
  public static function is_valid_email( $email )
  {
    return filter_var( $email, FILTER_VALIDATE_EMAIL );
  }

  /**
   * Returns true if all the emails in the headers are valid, false otherwise
   *
   * @param   mixed  $headers   string or array of headers
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
   * @return string
   */
  public static function remove_all_tag_urls( $content )
  {
    return preg_replace('/<(https?:\/\/[^>]*)>/im', '$1', $content);
  }
}