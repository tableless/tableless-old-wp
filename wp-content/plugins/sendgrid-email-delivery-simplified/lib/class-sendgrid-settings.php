<?php

require_once plugin_dir_path( __FILE__ ) . 'sendgrid/class-sendgrid-smtp.php';
require_once plugin_dir_path( __FILE__ ) . 'class-sendgrid-tools.php';
require_once plugin_dir_path( __FILE__ ) . 'class-sendgrid-nlvx.php';
require_once plugin_dir_path( __FILE__ ) . 'class-sendgrid-mc-optin.php';
require_once plugin_dir_path( __FILE__ ) . 'class-sendgrid-nlvx-widget.php';

class Sendgrid_Settings {
  const DEFAULT_SIGNUP_EMAIL_SUBJECT = 'Confirm your subscription to ';
  const DEFAULT_SIGNUP_EMAIL_CONTENT = '&lt;p&gt;Greetings!&lt;/p&gt;&#13;&#10;&#13;&#10;&lt;p&gt;Please click &lt;a href=&quot;%confirmation_link%&quot;&gt;here&lt;/a&gt; in order to subscribe to our newsletter!&lt;/p&gt;&#13;&#10;&#13;&#10;&lt;p&gt;Thank you,&lt;/p&gt;&#13;&#10;&lt;p&gt;';
  const DEFAULT_SIGNUP_EMAIL_CONTENT_TEXT = 'Greetings!&#13;&#10;&#13;&#10;Please open %confirmation_link% in order to subscribe to our newsletter!&#13;&#10;&#13;&#10;Thank you,&#13;&#10;';
  const DEFAULT_EMAIL_LABEL = 'Email';
  const DEFAULT_FIRST_NAME_LABEL = 'First Name';
  const DEFAULT_LAST_NAME_LABEL = 'Last Name';
  const DEFAULT_SUBSCRIBE_LABEL = 'SUBSCRIBE';

  public static $plugin_directory;

  /**
   * Settings class constructor
   *
   * @param  string   $plugin_directory   name of the plugin directory
   *
   * @return void
   */
  public function __construct( $plugin_directory )
  {
    self::$plugin_directory = $plugin_directory;
    add_action( 'init', array( __CLASS__, 'set_up_menu' ) );
  }

  /**
   * Method that is called to set up the settings menu
   *
   * @return void
   */
  public static function set_up_menu()
  {
    if ( ( ! is_multisite() and current_user_can('manage_options') ) || ( is_multisite() and ! is_main_site() and get_option( 'sendgrid_can_manage_subsite' ) ) ) {
      // Add SendGrid settings page in the menu
      add_action( 'admin_menu', array( __CLASS__, 'add_settings_menu' ) );
      // Add SendGrid settings page in the plugin list
      add_filter( 'plugin_action_links_' . self::$plugin_directory, array( __CLASS__, 'add_settings_link' ) );
    } elseif ( is_multisite() and is_main_site() ) {
      // Add SendGrid settings page in the network admin menu
      add_action( 'network_admin_menu', array( __CLASS__, 'add_network_settings_menu' ) );
    }
    // Add SendGrid Help contextual menu in the settings page
    add_filter( 'contextual_help', array( __CLASS__, 'show_contextual_help' ), 10, 3 );
    // Add SendGrid javascripts in header
    add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_headers' ) );
  }
  /**
   * Add SendGrid settings page in the menu
   *
   * @return void
   */
  public static function add_settings_menu() {
    add_options_page( __( 'SendGrid' ), __( 'SendGrid' ), 'manage_options', 'sendgrid-settings',
      array( __CLASS__, 'show_settings_page' ));
  }

  /**
   * Add SendGrid settings page in the network menu
   *
   * @return void
   */
  public static function add_network_settings_menu() {
    add_menu_page( __( 'SendGrid Settings' ), __( 'SendGrid Settings' ), 'manage_options', 'sendgrid-settings',
      array( __CLASS__, 'show_settings_page' ));
  }

  /**
   * Add SendGrid settings page in the plugin list
   *
   * @param  mixed   $links   links
   *
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
   *
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
   * @return void
   */
  public static function add_headers( $hook )
  {
    if ( strpos( $hook, 'sendgrid-settings' ) === false ) {
      return;
    }

    wp_enqueue_style( 'sendgrid', plugin_dir_url( __FILE__ ) . '../view/css/sendgrid.css' );
    wp_enqueue_style( 'select2', plugin_dir_url( __FILE__ ) . '../view/css/select2.min.css' );

    wp_enqueue_script( 'select2', plugin_dir_url( __FILE__ ) . '../view/js/select2.full.min.js', array('jquery') );
    wp_enqueue_script( 'sendgrid', plugin_dir_url( __FILE__ ) . '../view/js/sendgrid.settings-v1.7.3.js', array('jquery', 'select2') );
  }

  /**
   * Display SendGrid settings page content
   *
   * @return void
   */
  public static function show_settings_page()
  { 
    $response = null;
    $error_from_update = false;

    if ( 'POST' == $_SERVER['REQUEST_METHOD'] and ! isset( $_POST['sg_dismiss_widget_notice'] ) ) {
      $response = self::do_post( $_POST );
      if ( isset( $response['status'] ) and $response['status'] == 'error' ) {
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
    $unsubscribe_group_id = Sendgrid_Tools::get_unsubscribe_group();
    $stats_categories     = stripslashes( Sendgrid_Tools::get_stats_categories() );

    $mc_api_key                   = Sendgrid_Tools::get_mc_api_key();
    $mc_list_id                   = Sendgrid_Tools::get_mc_list_id();
    $mc_opt_use_transactional     = Sendgrid_Tools::get_mc_opt_use_transactional();
    $mc_opt_incl_fname_lname      = Sendgrid_Tools::get_mc_opt_incl_fname_lname();
    $mc_opt_req_fname_lname       = Sendgrid_Tools::get_mc_opt_req_fname_lname();
    $mc_signup_confirmation_page  = Sendgrid_Tools::get_mc_signup_confirmation_page();

    // input padding
    $mc_signup_input_padding_top      = Sendgrid_Tools::get_mc_input_padding_by_position( 'top' );
    $mc_signup_input_padding_right    = Sendgrid_Tools::get_mc_input_padding_by_position( 'right' );
    $mc_signup_input_padding_bottom   = Sendgrid_Tools::get_mc_input_padding_by_position( 'bottom' );
    $mc_signup_input_padding_left     = Sendgrid_Tools::get_mc_input_padding_by_position( 'left' );
    // button padding
    $mc_signup_button_padding_top     = Sendgrid_Tools::get_mc_button_padding_by_position( 'top' );
    $mc_signup_button_padding_right   = Sendgrid_Tools::get_mc_button_padding_by_position( 'right' );
    $mc_signup_button_padding_bottom  = Sendgrid_Tools::get_mc_button_padding_by_position( 'bottom' );
    $mc_signup_button_padding_left    = Sendgrid_Tools::get_mc_button_padding_by_position( 'left' );

    $mc_signup_email_subject = Sendgrid_Tools::get_mc_signup_email_subject();
    if ( false == $mc_signup_email_subject ) {
      $mc_signup_email_subject = self::DEFAULT_SIGNUP_EMAIL_SUBJECT . get_bloginfo('name');
    }

    $mc_signup_email_content = Sendgrid_Tools::get_mc_signup_email_content();
    if ( false == $mc_signup_email_content ) {
      $mc_signup_email_content = self::DEFAULT_SIGNUP_EMAIL_CONTENT . get_bloginfo('name') . '&lt;/p&gt;';
    }
    $mc_signup_email_content = stripslashes( $mc_signup_email_content );

    $mc_signup_email_content_text = Sendgrid_Tools::get_mc_signup_email_content_text();
    if ( false == $mc_signup_email_content_text ) {
      $mc_signup_email_content_text = self::DEFAULT_SIGNUP_EMAIL_CONTENT_TEXT . get_bloginfo('name');
    }
    $mc_signup_email_content_text = stripslashes( $mc_signup_email_content_text );

    $confirmation_pages = get_pages( array( 'parent' => 0 ) );

    $checked_use_transactional = '';
    if ( 'true' == $mc_opt_use_transactional ) {
      $checked_use_transactional = 'checked';
    }

    $checked_incl_fname_lname = '';
    if ( 'true' == $mc_opt_incl_fname_lname ) {
      $checked_incl_fname_lname = 'checked';
    }

    $checked_req_fname_lname = '';
    if ( 'true' == $mc_opt_req_fname_lname ) {
      $checked_req_fname_lname = 'checked';
    }

    $contact_lists = Sendgrid_NLVX::get_all_lists();
    $contact_list_id_is_valid = false;
    if ( false != $contact_lists ) {
      foreach ( $contact_lists as $key => $list ) {
        if ( $mc_list_id == $list['id'] ) {
          $contact_list_id_is_valid = true;
          break;
        }
      }
    }

    $allowed_send_methods = array( 'API' );
    if ( class_exists( 'Swift' ) ) {
      $allowed_send_methods[] = 'SMTP';
    }

    $is_mc_api_key_valid = true;
    if ( 'true' == $mc_opt_use_transactional and 'apikey' == $auth_method and ! empty( $api_key ) ) {
      if ( ! Sendgrid_Tools::check_api_key_mc( $api_key ) ) {
        $is_mc_api_key_valid = false;
      }
    } else if ( 'true' != $mc_opt_use_transactional ) {
      if ( ! Sendgrid_Tools::check_api_key_mc( $mc_api_key ) ) {
        $is_mc_api_key_valid = false;
      }
    }

    if ( $is_mc_api_key_valid ) {
      Sendgrid_Tools::set_mc_auth_valid( 'true' );
    } else {
      Sendgrid_Tools::set_mc_auth_valid( 'false' );
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
        } elseif ( 'true' == $mc_opt_use_transactional and ! $is_mc_api_key_valid ) {
          $message = 'The configured API Key for subscription widget is invalid, empty or without permissions.';
          $status  = 'error';  
        } elseif ( 'error' != $status ) {
          $status  = 'valid_auth';
        }
      } elseif ( 'credentials' == $auth_method and ! empty( $user ) and ! empty( $password ) ) {
        if ( ! Sendgrid_Tools::check_username_password( $user, $password, true ) ) {
          $message = 'Username and password are invalid.';
          $status  = 'error';
        } elseif ( 'error' != $status ) {
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

      if ( defined( 'SENDGRID_FROM_EMAIL' ) ) {
        if ( ! Sendgrid_Tools::is_valid_email( SENDGRID_FROM_EMAIL ) ) {
          $message = 'Sending email address is not valid in config file.';
          $status = 'error';
        }
      }

      if ( defined( 'SENDGRID_REPLY_TO' ) ) {
        if ( ! Sendgrid_Tools::is_valid_email( SENDGRID_REPLY_TO ) ) {
          $message = 'Reply email address is not valid in config file.';
          $status = 'error';
        }
      }
    }

    // get unsubscribe groups
    $unsubscribe_groups = Sendgrid_Tools::get_all_unsubscribe_groups();
    $no_permission_on_unsubscribe_groups = false;
    if ( ( 'apikey' == $auth_method ) and ( 'true' != Sendgrid_Tools::get_asm_permission() ) ) {
      $no_permission_on_unsubscribe_groups = true;  
    }

    // get form configuration
    $mc_signup_email_label = Sendgrid_Tools::get_mc_email_label();
    if ( false == $mc_signup_email_label ) {
      $mc_signup_email_label = self::DEFAULT_EMAIL_LABEL;
    }
    $mc_signup_first_name_label = Sendgrid_Tools::get_mc_first_name_label();
    if ( false == $mc_signup_first_name_label ) {
      $mc_signup_first_name_label = self::DEFAULT_FIRST_NAME_LABEL;
    }
    $mc_signup_last_name_label = Sendgrid_Tools::get_mc_last_name_label();
    if ( false == $mc_signup_last_name_label ) {
      $mc_signup_last_name_label = self::DEFAULT_LAST_NAME_LABEL;
    }
    $mc_signup_subscribe_label = Sendgrid_Tools::get_mc_subscribe_label();
    if ( false == $mc_signup_subscribe_label ) {
      $mc_signup_subscribe_label = self::DEFAULT_SUBSCRIBE_LABEL;
    }

    $is_env_auth_method                  = defined( 'SENDGRID_AUTH_METHOD' );
    $is_env_send_method                  = defined( 'SENDGRID_SEND_METHOD' );
    $is_env_username                     = defined( 'SENDGRID_USERNAME' );
    $is_env_password                     = defined( 'SENDGRID_PASSWORD' );
    $is_env_api_key                      = defined( 'SENDGRID_API_KEY' );
    $is_env_port                         = defined( 'SENDGRID_PORT' );
    $is_env_content_type                 = defined( 'SENDGRID_CONTENT_TYPE' );
    $is_env_unsubscribe_group            = defined( 'SENDGRID_UNSUBSCRIBE_GROUP' );
    $is_env_mc_api_key                   = defined( 'SENDGRID_MC_API_KEY' );
    $is_env_mc_list_id                   = defined( 'SENDGRID_MC_LIST_ID' );
    $is_env_mc_opt_use_transactional     = defined( 'SENDGRID_MC_OPT_USE_TRANSACTIONAL' );
    $is_env_mc_opt_incl_fname_lname      = defined( 'SENDGRID_MC_OPT_INCL_FNAME_LNAME' );
    $is_env_mc_opt_req_fname_lname       = defined( 'SENDGRID_MC_OPT_REQ_FNAME_LNAME' );
    $is_env_mc_signup_email_subject      = defined( 'SENDGRID_MC_SIGNUP_EMAIL_SUBJECT' );
    $is_env_mc_signup_email_content      = defined( 'SENDGRID_MC_SIGNUP_EMAIL_CONTENT' );
    $is_env_mc_signup_email_content_text = defined( 'SENDGRID_MC_SIGNUP_EMAIL_CONTENT_TEXT' );
    $is_env_mc_signup_confirmation_page  = defined( 'SENDGRID_MC_SIGNUP_CONFIRMATION_PAGE' );
    $is_env_mc_email_label               = defined( 'SENDGRID_MC_EMAIL_LABEL' );
    $is_env_mc_first_name_label          = defined( 'SENDGRID_MC_FIRST_NAME_LABEL' );
    $is_env_mc_last_name_label           = defined( 'SENDGRID_MC_LAST_NAME_LABEL' );
    $is_env_mc_subscribe_label           = defined( 'SENDGRID_MC_SUBSCRIBE_LABEL' );

    if ( $response and $status != 'error' ) {
      $message  = $response['message'];
      $status   = $response['status'];
      if( array_key_exists( 'error_type', $response ) ) {
        $error_type = $response['error_type'];
      }
    }

    require_once dirname( __FILE__ ) . '/../view/sendgrid_settings.php';
  }

  /**
   * Routes processing of request parameters depending on the source section of the settings page
   *
   * @param  mixed   $params    array of parameters from $_POST
   *
   * @return mixed              response array from the save or send functions
   */
  private static function do_post( $params ) {
    if ( isset( $params['mc_settings'] ) and $params['mc_settings'] ) {
      return self::save_mc_settings( $params );
    }

    if ( isset( $params['email_test'] ) and $params['email_test'] ) {
      return self::send_test_email( $params );
    }

    if ( isset( $params['contact_upload_test'] ) and $params['contact_upload_test'] ) {
      return self::send_contact_upload_test( $params );
    } 

    if ( isset( $params['subsite_settings'] ) and $params['subsite_settings'] ) {
      return self::save_subsite_settings( $params );
    } 

    return self::save_general_settings( $params );
  }

  /**
   * Saves the Subsite settings sent from the settings page
   *
   * @param  mixed   $params    array of parameters from $_POST
   *
   * @return mixed              response array with message and status
   */
  private static function save_subsite_settings( $params ) {
    $sites = get_sites();
    foreach( $sites as $site ) {
      if ( isset( $params['checked_sites'][$site->blog_id] ) and 'on' == $params['checked_sites'][$site->blog_id] ) {
        update_blog_option( $site->blog_id, 'sendgrid_can_manage_subsite', 1 );
      } else {
        update_blog_option( $site->blog_id, 'sendgrid_can_manage_subsite', 0 );
      }
    }
  }

  /**
   * Saves the Marketing Campaigns parameters sent from the settings page
   *
   * @param  mixed   $params    array of parameters from $_POST
   *
   * @return mixed              response array with message and status
   */
  private static function save_mc_settings( $params ) {
    // Use Transactional Option 
    $use_transactional_key = false;

    if ( ! defined( 'SENDGRID_MC_OPT_USE_TRANSACTIONAL' ) ) {
      if ( isset( $params['sendgrid_mc_use_transactional'] ) ) {
        $use_transactional_key = true;
        Sendgrid_Tools::set_mc_opt_use_transactional( 'true' );
      } else {
        Sendgrid_Tools::set_mc_opt_use_transactional( 'false' );
      }
    } else {
      $use_transactional_key = ( 'true' == SENDGRID_MC_OPT_USE_TRANSACTIONAL ? true : false );
    }

    // If Use Transactional Is Set and auth is not through credentials, check the API key for MC scopes.
    if ( $use_transactional_key and 'apikey' == Sendgrid_Tools::get_auth_method() ) {
      $apikey = Sendgrid_Tools::get_api_key();
      if( false == $apikey or empty( $apikey ) ) {
        $response = array(
          'message' => 'API Key is empty.',
          'status' => 'error'
        );

        return $response;
      }

      if ( ! Sendgrid_Tools::check_api_key_mc( $apikey ) ) {
        $response = array(
          'message' => 'API Key is invalid or without permissions.',
          'status' => 'error'
        );

        return $response;
      }
    }

    if ( false == $use_transactional_key and ! defined( 'SENDGRID_MC_API_KEY' ) ) {
      // MC API Key was set empty on purpose
      if ( ! isset( $params['sendgrid_mc_apikey'] ) or empty( $params['sendgrid_mc_apikey'] ) ) {
        $response = array(
          'message' => 'API Key is empty.',
          'status' => 'error'
        );

        Sendgrid_Tools::set_mc_api_key( '' );
      } else {
        // MC API Key was set, check scopes and save if correct
        $apikey = $params['sendgrid_mc_apikey'];

        if ( ! Sendgrid_Tools::check_api_key_mc( $apikey ) ) {
          $response = array(
            'message' => 'API Key is invalid or without permissions.',
            'status' => 'error'
          );
        } else {
          Sendgrid_Tools::set_mc_api_key( $apikey );
        }
      }
    }

    if ( ! defined( 'SENDGRID_MC_OPT_INCL_FNAME_LNAME' ) ) {
      if ( isset( $params['sendgrid_mc_incl_fname_lname'] ) ) {
        Sendgrid_Tools::set_mc_opt_incl_fname_lname( 'true' );
      } else {
        Sendgrid_Tools::set_mc_opt_incl_fname_lname( 'false' );
      }
    }

    if ( ! defined( 'SENDGRID_MC_OPT_REQ_FNAME_LNAME' ) ) {
      if ( isset( $params['sendgrid_mc_req_fname_lname'] ) ) {
        Sendgrid_Tools::set_mc_opt_req_fname_lname( 'true' );
      } else {
        Sendgrid_Tools::set_mc_opt_req_fname_lname( 'false' );
      }
    }

    if ( isset( $params['sendgrid_mc_contact_list'] ) and ! defined( 'SENDGRID_MC_LIST_ID' ) ) {
      Sendgrid_Tools::set_mc_list_id( $params['sendgrid_mc_contact_list'] );
    }

    if ( ! defined( 'SENDGRID_MC_SIGNUP_EMAIL_SUBJECT' ) ) {
      if ( ! isset( $params['sendgrid_mc_email_subject'] ) or empty( $params['sendgrid_mc_email_subject'] ) ) {
        $response = array(
          'message' => 'Signup email subject cannot be empty.',
          'status' => 'error'
        );
      } else {
        Sendgrid_Tools::set_mc_signup_email_subject( $params['sendgrid_mc_email_subject'] );
      }
    }

    if ( ! defined( 'SENDGRID_MC_SIGNUP_EMAIL_CONTENT' ) ) {
      if ( ! isset( $params['sendgrid_mc_email_content'] ) or empty( $params['sendgrid_mc_email_content'] ) ) {
        $response = array(
          'message' => 'Signup email content cannot be empty.',
          'status' => 'error'
        );
      } else {
        Sendgrid_Tools::set_mc_signup_email_content( $params['sendgrid_mc_email_content'] );
      }
    }

    if ( ! defined( 'SENDGRID_MC_SIGNUP_EMAIL_CONTENT_TEXT' ) ) {
      if ( ! isset( $params['sendgrid_mc_email_content_text'] ) or empty( $params['sendgrid_mc_email_content_text'] ) ) {
        $response = array(
          'message' => 'Signup email content plain/text cannot be empty.',
          'status' => 'error'
        );
      } else {
        Sendgrid_Tools::set_mc_signup_email_content_text( $params['sendgrid_mc_email_content_text'] );
      }
    }

    if ( isset( $params['sendgrid_mc_signup_page'] ) and ! defined( 'SENDGRID_MC_SIGNUP_CONFIRMATION_PAGE' ) ) {
      Sendgrid_Tools::set_mc_signup_confirmation_page( $params['sendgrid_mc_signup_page'] );
    }

    // form configuration
    // labels
    if ( isset( $params['sendgrid_mc_email_label'] ) and ! defined( 'SENDGRID_MC_EMAIL_LABEL' ) ) {
      Sendgrid_Tools::set_mc_email_label( $params['sendgrid_mc_email_label'] );
    }
    if ( isset( $params['sendgrid_mc_first_name_label'] ) and ! defined( 'SENDGRID_MC_FIRST_NAME_LABEL' ) ) {
      Sendgrid_Tools::set_mc_first_name_label( $params['sendgrid_mc_first_name_label'] );
    }
    if ( isset( $params['sendgrid_mc_last_name_label'] ) and ! defined( 'SENDGRID_MC_LAST_NAME_LABEL' ) ) {
      Sendgrid_Tools::set_mc_last_name_label( $params['sendgrid_mc_last_name_label'] );
    }
    if ( isset( $params['sendgrid_mc_subscribe_label'] ) and ! defined( 'SENDGRID_MC_SUBSCRIBE_LABEL' ) ) {
      Sendgrid_Tools::set_mc_subscribe_label( $params['sendgrid_mc_subscribe_label'] );
    }
    // input padding
    if ( isset( $params['sendgrid_mc_input_padding_top'] ) ) {
      Sendgrid_Tools::set_mc_input_padding( 'top', $params['sendgrid_mc_input_padding_top'] );
    }
    if ( isset( $params['sendgrid_mc_input_padding_right'] ) ) {
      Sendgrid_Tools::set_mc_input_padding( 'right', $params['sendgrid_mc_input_padding_right'] );
    }
    if ( isset( $params['sendgrid_mc_input_padding_bottom'] ) ) {
      Sendgrid_Tools::set_mc_input_padding( 'bottom', $params['sendgrid_mc_input_padding_bottom'] );
    }
    if ( isset( $params['sendgrid_mc_input_padding_left'] ) ) {
      Sendgrid_Tools::set_mc_input_padding( 'left', $params['sendgrid_mc_input_padding_left'] );
    }
    // button padding
    if ( isset( $params['sendgrid_mc_button_padding_top'] ) ) {
      Sendgrid_Tools::set_mc_button_padding( 'top', $params['sendgrid_mc_button_padding_top'] );
    }
    if ( isset( $params['sendgrid_mc_button_padding_right'] ) ) {
      Sendgrid_Tools::set_mc_button_padding( 'right', $params['sendgrid_mc_button_padding_right'] );
    }
    if ( isset( $params['sendgrid_mc_button_padding_bottom'] ) ) {
      Sendgrid_Tools::set_mc_button_padding( 'bottom', $params['sendgrid_mc_button_padding_bottom'] );
    }
    if ( isset( $params['sendgrid_mc_button_padding_left'] ) ) {
      Sendgrid_Tools::set_mc_button_padding( 'left', $params['sendgrid_mc_button_padding_left'] );
    }

    if ( isset( $response ) and $response['status'] == 'error' ) {
      return $response;
    }

    return array(
      'message' => 'Options are saved.',
      'status' => 'updated'
    );
  }

  /**
   * Saves the General Settings parameters sent from the settings page
   *
   * @param  mixed   $params    array of parameters from $_POST
   *
   * @return mixed              response array with message and status
   */
  private static function save_general_settings( $params ) {
    if ( ! isset( $params['auth_method'] ) ) {
      $params['auth_method'] = Sendgrid_Tools::get_auth_method();
    }

    switch ( $params['auth_method'] ) {
      case 'apikey':
        if ( ! defined( 'SENDGRID_API_KEY' ) ) {
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

          if ( 'true' == Sendgrid_Tools::get_mc_opt_use_transactional() and ! Sendgrid_Tools::check_api_key_mc( $params['sendgrid_apikey'] ) ) {
            $response = array(
              'message' => 'This API key is also used for the Subscription Widget but does not have Marketing Campaigns permissions.',
              'status' => 'error'
            );
          }

          Sendgrid_Tools::set_api_key( $params['sendgrid_apikey'] );
        }
       
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
      Sendgrid_Tools::set_from_name( $params['sendgrid_name'] );
    }

    if ( isset( $params['sendgrid_email'] ) ) {
      if ( ! empty( $params['sendgrid_email'] ) and ! Sendgrid_Tools::is_valid_email( $params['sendgrid_email'] ) ) {
        $response = array(
          'message' => 'Sending email address is not valid.',
          'status' => 'error'
        );
      } else {
        Sendgrid_Tools::set_from_email( $params['sendgrid_email'] );
      }
    }

    if ( isset( $params['sendgrid_reply_to'] ) ) {
      if ( ! empty( $params['sendgrid_reply_to'] ) and ! Sendgrid_Tools::is_valid_email( $params['sendgrid_reply_to'] ) ) {
        $response = array(
          'message' => 'Reply email address is not valid.',
          'status' => 'error'
        );
      } else {
        Sendgrid_Tools::set_reply_to( $params['sendgrid_reply_to'] );
      }
    }

    if ( isset( $params['sendgrid_categories'] ) ) {
      Sendgrid_Tools::set_categories( $params['sendgrid_categories'] );
    }

    if ( isset( $params['sendgrid_stats_categories'] ) ) {
      Sendgrid_Tools::set_stats_categories( $params['sendgrid_stats_categories'] );
    }

    if ( isset( $params['sendgrid_template'] ) ) {
      if ( ! Sendgrid_Tools::check_template( $params['sendgrid_template'] ) ) {
        $response = array(
          'message' => 'Template not found.',
          'status' => 'error'
        );
      } else {
        Sendgrid_Tools::set_template( $params['sendgrid_template'] );
      }
    }

    if ( isset( $params['send_method'] ) ) {
      Sendgrid_Tools::set_send_method( $params['send_method'] );
    }

    if ( isset( $params['auth_method'] ) and in_array( $params['auth_method'], Sendgrid_Tools::$allowed_auth_methods ) ) {
      Sendgrid_Tools::set_auth_method( $params['auth_method'] );
    }

    if ( isset( $params['sendgrid_port'] ) ) {
      Sendgrid_Tools::set_port( $params['sendgrid_port'] );
    }

    if ( isset( $params['content_type'] ) ) {
      Sendgrid_Tools::set_content_type( $params['content_type'] );
    }

    if ( isset( $params['unsubscribe_group'] ) ) {
      Sendgrid_Tools::set_unsubscribe_group( $params['unsubscribe_group'] );
    }

    if( isset( $response ) and $response['status'] == 'error') {
      return $response;
    }

    return array(
      'message' => 'Options are saved.',
      'status' => 'updated'
    );
  }

  /**
   * Sends a test email using the parameters specified in the settings page
   *
   * @param  mixed   $params    array of parameters from $_POST
   *
   * @return mixed              response array with message and status
   */
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

  /**
   * Uploads a contact using the parameters specified in the settings page
   *
   * @param  mixed   $params    array of parameters from $_POST
   *
   * @return mixed              response array with message and status
   */
  private static function send_contact_upload_test( $params ) {
    $email = $params['sendgrid_test_email'];
    if ( ! Sendgrid_Tools::is_valid_email( $email ) ) {
      return array(
        'message' => 'Email address provided is invalid.',
        'status' => 'error',
        'error_type' => 'upload'
      );
    }
    
    switch ( Sendgrid_Tools::get_auth_method() ) {
      case 'apikey':
        $apikey = Sendgrid_Tools::get_api_key();
        if ( ! Sendgrid_Tools::check_api_key( $apikey, true ) ) {
          return array(
            'message' => 'API Key used for mail send is invalid or without permissions.',
            'status' => 'error',
            'error_type' => 'upload'
          );
        }
        break;
      case 'credentials':
        $username = Sendgrid_Tools::get_username();
        $password = Sendgrid_Tools::get_password();
        if ( ! Sendgrid_Tools::check_username_password( $params['sendgrid_username'], $params['sendgrid_password'], true ) ) {
          return array(
            'message' => 'Credentials used for mail send are invalid.',
            'status' => 'error',
            'error_type' => 'upload'
          );
        }
        break;
      default:
        return array(
          'message' => 'An error occured when trying to check your transactional credentials. Please check that they are correct on the General Settings tab.',
          'status' => 'error',
          'error_type' => 'upload'
        );
    }

    if ( false == Sendgrid_OptIn_API_Endpoint::send_confirmation_email( $email, '', '', true ) ) {
      return array(
        'message' => 'An error occured when trying send the subscription email. Please make sure you have configured all settings properly.',
        'status' => 'error',
        'error_type' => 'upload'
      );
    }

    return array(
      'message' => 'Subscription confirmation email was sent.',
      'status' => 'updated'
    );
  }
}