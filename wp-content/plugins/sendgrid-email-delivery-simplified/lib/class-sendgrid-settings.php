<?php

require_once plugin_dir_path( __FILE__ ) . 'sendgrid/class-sendgrid-smtp.php';
require_once plugin_dir_path( __FILE__ ) . 'class-sendgrid-tools.php';

class Sendgrid_Settings {
  public function __construct( $plugin_directory )
  {
    // Add SendGrid settings page in the menu
    add_action( 'admin_menu', array( __CLASS__, 'add_settings_menu' ) );

    // Add SendGrid settings page in the plugin list
    add_filter( 'plugin_action_links_' . $plugin_directory, array( __CLASS__, 'add_settings_link' ) );

    // Add SendGrid Help contextual menu in the settings page
    add_filter( 'contextual_help', array( __CLASS__, 'show_contextual_help' ), 10, 3 );

    // Add SendGrid javascripts in header
    add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_headers' ) );
  }

  /**
   * Add SendGrid settings page in the menu
   */
  public static function add_settings_menu() {
    add_options_page( __( 'SendGrid' ), __( 'SendGrid' ), 'manage_options', 'sendgrid-settings',
      array( __CLASS__, 'show_settings_page' ));
  }

  /**
   * Add SendGrid settings page in the plugin list
   *
   * @param  mixed   $links   links
   * @return mixed            links
   */
  public static function add_settings_link( $links )
  {
    $settings_link = '<a href="options-general.php?page=sendgrid-settings.php">Settings</a>';
    array_unshift( $links, $settings_link );

    return $links;
  }

  /**
   * Add SendGrid Help contextual menu in the settings page
   *
   * @param   mixed   $contextual_help    contextual help
   * @param   integer $screen_id          screen id
   * @param   integer $screen             screen
   * @return  string
   */
  public static function show_contextual_help( $contextual_help, $screen_id, $screen )
  {
    if ( SENDGRID_PLUGIN_STATISTICS == $screen_id or SENDGRID_PLUGIN_SETTINGS == $screen_id )
    {
      $contextual_help = file_get_contents( dirname( __FILE__ ) . '/../view/sendgrid_contextual_help.php' );
    }

    return $contextual_help;
  }

  /**
   * Include css & javascripts we need for SendGrid settings page and widget
   *
   * @return void;
   */
  public static function add_headers( $hook )
  {
    if ( SENDGRID_PLUGIN_SETTINGS != $hook ) {
      return;
    }

    wp_enqueue_style( 'sendgrid', plugin_dir_url( __FILE__ ) . '../view/css/sendgrid.css' );

    wp_enqueue_script( 'sendgrid', plugin_dir_url( __FILE__ ) . '../view/js/sendgrid.settings-v1.7.3.js', array('jquery') );
  }

  /**
   * Display SendGrid settings page content
   */
  public static function show_settings_page()
  { 
    $response = null;
    $error_from_update = false;

    if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
      $response = self::do_post( $_POST );
      if( isset( $response['status'] ) and $response['status'] == 'error' ) {
        $error_from_update = true;
      }
    }

    $status = '';
    $message = '';

    $user                 = Sendgrid_Tools::get_username();
    $password             = Sendgrid_Tools::get_password();
    $api_key              = Sendgrid_Tools::get_api_key();
    $send_method          = Sendgrid_Tools::get_send_method();
    $auth_method          = Sendgrid_Tools::get_auth_method();
    $name                 = stripslashes( Sendgrid_Tools::get_from_name() );
    $email                = Sendgrid_Tools::get_from_email();
    $reply_to             = Sendgrid_Tools::get_reply_to();
    $categories           = stripslashes( Sendgrid_Tools::get_categories() );
    $template             = stripslashes( Sendgrid_Tools::get_template() );
    $port                 = Sendgrid_Tools::get_port();
    $content_type         = Sendgrid_Tools::get_content_type();
    $stats_categories     = stripslashes( Sendgrid_Tools::get_stats_categories() );

    $allowed_send_methods = array( 'API' );
    if ( class_exists( 'Swift' ) ) {
      $allowed_send_methods[] = 'SMTP';
    }

    if ( ! $error_from_update ) {
      if ( ! in_array( strtoupper( $send_method ), $allowed_send_methods ) ) {
        $message = 'Invalid send method configured in the config file, available methods are: ' . join( ", ", $allowed_send_methods );
        $status = 'error';
      }

      if ( 'apikey' == $auth_method and ! empty( $api_key ) ) {
        if ( ! Sendgrid_Tools::check_api_key( $api_key, true ) ) {
          $message = 'API Key is invalid or without permissions.';
          $status  = 'error';
        } else {
          $status  = 'valid_auth';
        }
      } elseif ( 'credentials' == $auth_method and ! empty( $user ) and ! empty( $password ) ) {
        if ( ! Sendgrid_Tools::check_username_password( $user, $password, true ) ) {
          $message = 'Username and password are invalid.';
          $status  = 'error';
        } else {
          $status  = 'valid_auth';
        }
      }

      if ( $template and ! Sendgrid_Tools::check_template( $template ) ) {
        $message = 'Template not found.';
        $status  = 'error';
      }
   
      if ( ! in_array( $port, Sendgrid_Tools::$allowed_ports ) ) {
        $message = 'Invalid port configured in the config file, available ports are: ' . join( ",", Sendgrid_Tools::$allowed_ports );
        $status = 'error';
      }

      if ( ! in_array( $auth_method, Sendgrid_Tools::$allowed_auth_methods ) ) {
        $message = 'Invalid authentication method configured in the config file, available options are: ' . join( ", ", Sendgrid_Tools::$allowed_auth_methods );
        $status = 'error';
      }

      if ( defined( 'SENDGRID_CONTENT_TYPE' ) ) {
        if ( ! in_array( SENDGRID_CONTENT_TYPE, Sendgrid_Tools::$allowed_content_type ) ) {
          $message = 'Invalid content type, available content types are: "plaintext" or "html".';
          $status = 'error';
        }
      }

      if( defined( 'SENDGRID_FROM_EMAIL' ) ) {
        if ( ! Sendgrid_Tools::is_valid_email( SENDGRID_FROM_EMAIL ) ) {
          $message = 'Sending email address is not valid in config file.';
          $status = 'error';
        }
      }

      if( defined( 'SENDGRID_REPLY_TO' ) ) {
        if ( ! Sendgrid_Tools::is_valid_email( SENDGRID_REPLY_TO ) ) {
          $message = 'Reply email address is not valid in config file.';
          $status = 'error';
        }
      }
    }

    $is_env_auth_method   = defined( 'SENDGRID_AUTH_METHOD' );
    $is_env_send_method   = defined( 'SENDGRID_SEND_METHOD' );
    $is_env_username      = defined( 'SENDGRID_USERNAME' );
    $is_env_password      = defined( 'SENDGRID_PASSWORD' );
    $is_env_api_key       = defined( 'SENDGRID_API_KEY' );
    $is_env_port          = defined( 'SENDGRID_PORT' );
    $is_env_content_type  = defined( 'SENDGRID_CONTENT_TYPE' );
    
    if ( $response && $status != 'error' ) {
      $message  = $response['message'];
      $status   = $response['status'];
      if( array_key_exists( 'error_type', $response ) ) {
        $error_type = $response['error_type'];
      }
    }

    require_once dirname( __FILE__ ) . '/../view/sendgrid_settings.php';
  }

  private static function do_post( $params ) {
    if ( isset($params['email_test'] ) and $params['email_test'] ) {
      return self::send_test_email( $params );
    } 
    
    return self::save_settings( $params );
  }

  private static function save_settings( $params ) {
    if ( ! isset( $params['auth_method'] ) ) {
      $params['auth_method'] = Sendgrid_Tools::get_auth_method();
    }

    switch ( $params['auth_method'] ) {
      case 'apikey':
        if ( ! isset( $params['sendgrid_apikey'] ) or empty( $params['sendgrid_apikey'] ) ) {
          $response = array(
            'message' => 'API Key is empty.',
            'status' => 'error'
          );

          Sendgrid_Tools::set_api_key( '' );

          break;
        }

        if ( ! Sendgrid_Tools::check_api_key( $params['sendgrid_apikey'], true ) ) {
          $response = array(
            'message' => 'API Key is invalid or without permissions.',
            'status' => 'error'
          );

          break;
        }

        Sendgrid_Tools::set_api_key( $params['sendgrid_apikey'] );

        break;
      
      case 'credentials':
        if ( ! isset( $params['sendgrid_username'] ) and ! isset( $params['sendgrid_password'] ) )
          break;

        $save_username = true;
        $save_password = true;

        if ( ! isset ( $params['sendgrid_username'] ) ) {
          $save_username = false;
          $params['sendgrid_username'] = Sendgrid_Tools::get_username();
        }

        if ( ! isset ( $params['sendgrid_password'] ) ) {
          $save_password = false;
          $params['sendgrid_password'] = Sendgrid_Tools::get_username();
        }

        if ( ( isset( $params['sendgrid_username'] ) and ! $params['sendgrid_username'] ) or ( isset( $params['sendgrid_password'] ) and ! $params['sendgrid_password'] ) ) {
          $response = array(
            'message' => 'Username or password is empty.',
            'status' => 'error'
          );
        } elseif ( ! Sendgrid_Tools::check_username_password( $params['sendgrid_username'], $params['sendgrid_password'], true ) ) {
          $response = array(
            'message' => 'Username and password are invalid.',
            'status' => 'error'
          );

          break;
        }

        if ( $save_username ) {
          Sendgrid_Tools::set_username( $params['sendgrid_username'] );
        }
        
        if ( $save_password ) {
          Sendgrid_Tools::set_password( $params['sendgrid_password'] );
        }

        break;
    }

    if ( isset( $params['sendgrid_name'] ) ) {
      update_option( 'sendgrid_from_name', $params['sendgrid_name'] );
    }

    if ( isset( $params['sendgrid_email'] ) ) {
      if ( ! empty( $params['sendgrid_email'] ) and ! Sendgrid_Tools::is_valid_email( $params['sendgrid_email'] ) ) {
        $response = array(
          'message' => 'Sending email address is not valid.',
          'status' => 'error'
        );
      } else {
        update_option( 'sendgrid_from_email', $params['sendgrid_email'] );
      }
    }

    if ( isset( $params['sendgrid_reply_to'] ) ) {
      if ( ! empty( $params['sendgrid_reply_to'] ) and ! Sendgrid_Tools::is_valid_email( $params['sendgrid_reply_to'] ) ) {
        $response = array(
          'message' => 'Reply email address is not valid.',
          'status' => 'error'
        );
      } else {
        update_option( 'sendgrid_reply_to', $params['sendgrid_reply_to'] );
      }
    }

    if ( isset( $params['sendgrid_categories'] ) ) {
      update_option( 'sendgrid_categories', $params['sendgrid_categories'] );
    }

    if ( isset( $params['sendgrid_stats_categories'] ) ) {
      update_option( 'sendgrid_stats_categories', $params['sendgrid_stats_categories'] );
    }

    if ( isset( $params['sendgrid_template'] ) ) {
      if ( ! Sendgrid_Tools::check_template( $params['sendgrid_template'] ) ) {
        $response = array(
          'message' => 'Template not found.',
          'status' => 'error'
        );
      } else {
        update_option( 'sendgrid_template', $params['sendgrid_template'] );
      }
    }

    if ( isset( $params['send_method'] ) ) {
      update_option( 'sendgrid_api', $params['send_method'] );
    }

    if ( isset( $params['auth_method'] ) && in_array( $params['auth_method'], Sendgrid_Tools::$allowed_auth_methods ) ) {
      update_option( 'sendgrid_auth_method', $params['auth_method'] );
    }

    if ( isset( $params['sendgrid_port'] ) ) {
      update_option( 'sendgrid_port', $params['sendgrid_port'] );
    }

    if ( isset( $params['content_type'] ) ) {
      update_option( 'sendgrid_content_type', $params['content_type'] );
    }

    if( isset( $response ) and $response['status'] == 'error') {
      return $response;
    }

    return array(
      'message' => 'Options are saved.',
      'status' => 'updated'
    );
  }

  private static function send_test_email( $params ) {
    $to = $params['sendgrid_to'];
    if ( ! Sendgrid_Tools::is_valid_email( $to ) ) {
      return array(
        'message' => 'Email address in field "To" is not valid.',
        'status' => 'error',
        'error_type' => 'sending'
      );
    }

    $subject = stripslashes( $params['sendgrid_subj'] );
    $body    = stripslashes( $params['sendgrid_body'] );
    $headers = $params['sendgrid_headers'];
    if ( ! Sendgrid_Tools::valid_emails_in_headers( $headers ) ) {
      return array(
        'message' => 'One or more email addresses in field "headers" are not valid.',
        'status' => 'error',
        'error_type' => 'sending'
      );
    }

    if ( preg_match( '/content-type:\s*text\/html/i', $headers ) ) {
      $body_br = nl2br( $body );
    } else {
      $body_br = $body;
    }

    $sent = wp_mail( $to, $subject, $body_br, $headers );
    if ( true === $sent ) {
      return array(
        'message' => 'Email was sent.',
        'status' => 'updated'
      );
    }

    return array(
      'message' => 'Email wasn\'t sent.',
      'status' => 'error',
      'error_type' => 'sending'
    );
  }
}