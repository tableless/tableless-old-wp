<?php

require_once plugin_dir_path( __FILE__ ) . 'class-sendgrid-tools.php';

class Sendgrid_Statistics
{
  public function __construct()
  {
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
      // Add SendGrid widget in dashboard
      add_action( 'wp_dashboard_setup', array( __CLASS__, 'add_dashboard_widget' ) );

      // Add SendGrid stats page in menu
      add_action( 'admin_menu', array( __CLASS__, 'add_statistics_menu' ) );

      // Add SendGrid javascripts in header
      add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_headers' ) );

      // Add SendGrid page for get statistics through ajax
      add_action( 'wp_ajax_sendgrid_get_stats', array( __CLASS__, 'get_ajax_statistics' ) );
    } elseif ( is_multisite() and is_main_site() ) {
      // Add SendGrid stats page in menu
      add_action( 'network_admin_menu', array( __CLASS__, 'add_network_statistics_menu' ) );
      
      // Add SendGrid javascripts in header
      add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_headers' ) );

      // Add SendGrid page for get statistics through ajax
      add_action( 'wp_ajax_sendgrid_get_stats', array( __CLASS__, 'get_ajax_statistics' ) );
    }
  }

  /**
   * Verify if SendGrid username and password provided are correct and
   * initialize function for add widget in dashboard
   *
   * @return void
   */
  public static function add_dashboard_widget()
  {
    if ( ! current_user_can('manage_options') ) {
      return;
    }

    switch ( Sendgrid_Tools::get_auth_method() )
    {
      case "apikey":
        $apikey = Sendgrid_Tools::get_api_key();
        if ( ! Sendgrid_Tools::check_api_key( $apikey ) or ! Sendgrid_Tools::check_api_key_stats( $apikey ) ) {
          return;
        }
      break;

      case "credentials":
        if ( ! Sendgrid_Tools::check_username_password( Sendgrid_Tools::get_username(), Sendgrid_Tools::get_password() ) ) {
          return;
        }
      break;
    }

    add_meta_box( 'sendgrid_statistics_widget', 'SendGrid Wordpress Statistics', array( __CLASS__, 'show_dashboard_widget' ),
      'dashboard', 'normal', 'high' );
  }

  /**
   * Display SendGrid widget content
   *
   * @return void
   */
  public static function show_dashboard_widget()
  {
    require plugin_dir_path( __FILE__ ) . '../view/partials/sendgrid_stats_widget.php';
  }

  /**
   * Add SendGrid statistics page in the menu
   *
   * @return void
   */
  public static function add_statistics_menu()
  {
    switch ( Sendgrid_Tools::get_auth_method() )
    {
      case "apikey":
        $apikey = Sendgrid_Tools::get_api_key();
        if ( ! Sendgrid_Tools::check_api_key( $apikey ) ) {
          return;
        }
      break;

      case "credentials":
        if ( ! Sendgrid_Tools::check_username_password( Sendgrid_Tools::get_username(), Sendgrid_Tools::get_password() ) ) {
          return;
        }
      break;
    }

    add_dashboard_page( "SendGrid Statistics", "SendGrid Statistics", "manage_options", "sendgrid-statistics",
      array( __CLASS__, "show_statistics_page" ) );
  }

  /**
   * Add SendGrid statistics page in the network menu
   *
   * @return void
   */
  public static function add_network_statistics_menu() {
    switch ( Sendgrid_Tools::get_auth_method() )
    {
      case "apikey":
        $apikey = Sendgrid_Tools::get_api_key();
        if ( ! Sendgrid_Tools::check_api_key( $apikey ) ) {
          return;
        }
      break;

      case "credentials":
        if ( ! Sendgrid_Tools::check_username_password( Sendgrid_Tools::get_username(), Sendgrid_Tools::get_password() ) ) {
          return;
        }
      break;
    }

    add_menu_page( __( 'SendGrid Stats' ), __( 'SendGrid Stats' ), 'manage_options', 'sendgrid-statistics',
      array( __CLASS__, 'show_statistics_page' ));
  }

  /**
   * Display SendGrid statistics page
   *
   * @return void
   */
  public static function show_statistics_page()
  {
    $apikey = Sendgrid_Tools::get_api_key();
    if ( ( "apikey" == Sendgrid_Tools::get_auth_method() ) and isset( $apikey ) and ( $apikey != '' ) and ! Sendgrid_Tools::check_api_key_stats( $apikey, true ) )
    {
      $message = 'Your Api key does not have statistics permissions';
      $status  = 'error';
    }

    require plugin_dir_path( __FILE__ ) . '../view/sendgrid_stats.php';
  }

  /**
   * Include css & javascripts we need for SendGrid statistics page and widget
   *
   * @return void;
   */
  public static function add_headers( $hook )
  {
    if ( "index.php" != $hook and strpos( $hook, 'sendgrid-statistics' ) === false ) {
      return;
    }

    // Javascript
    wp_enqueue_script( 'sendgrid-stats', plugin_dir_url( __FILE__ ) . '../view/js/sendgrid.stats-v1.7.3.js', array('jquery') );
    wp_enqueue_script( 'jquery-flot', plugin_dir_url( __FILE__ ) . '../view/js/jquery.flot.js', array('jquery') );
    wp_enqueue_script( 'jquery-flot-time', plugin_dir_url( __FILE__ ) . '../view/js/jquery.flot.time.js', array('jquery') );
    wp_enqueue_script( 'jquery-flot-tofflelegend', plugin_dir_url( __FILE__ ) . '../view/js/jquery.flot.togglelegend.js', array('jquery') );
    wp_enqueue_script( 'jquery-flot-symbol', plugin_dir_url( __FILE__ ) . '../view/js/jquery.flot.symbol.js', array('jquery') );
    wp_enqueue_script( 'jquery-ui-datepicker', plugin_dir_url( __FILE__ ) . '../view/js/jquery.ui.datepicker.js', array('jquery', 'jquery-ui-core') );

    // CSS
    wp_enqueue_style( 'jquery-ui-datepicker', plugin_dir_url( __FILE__ ) . '../view/css/datepicker/smoothness/jquery-ui-1.10.3.custom.css' );
    wp_enqueue_style( 'sendgrid', plugin_dir_url( __FILE__ ) . '../view/css/sendgrid.css' );

    wp_localize_script( 'sendgrid-stats', 'sendgrid_vars',
      array(
        'sendgrid_nonce' => wp_create_nonce('sendgrid-nonce')
      )
    );
  }

  /**
   * Get SendGrid stats from API and return JSON response,
   * this function work like a page and is used for ajax request by javascript functions
   *
   * @return void;
   */
  public static function get_ajax_statistics()
  {
    if ( ! isset( $_POST['sendgrid_nonce'] ) || ! wp_verify_nonce( $_POST['sendgrid_nonce'], 'sendgrid-nonce') ) {
      die( 'Permissions check failed' );
    }

    $parameters = array();

    $parameters['auth_method'] = Sendgrid_Tools::get_auth_method();
    $parameters['api_username'] = Sendgrid_Tools::get_username();
    $parameters['api_password']  = Sendgrid_Tools::get_password();
    $parameters['apikey']   = Sendgrid_Tools::get_api_key();

    $parameters['data_type'] = 'global';

    if ( array_key_exists( 'days', $_POST ) ) {
      $parameters['days'] = $_POST['days'];
    } else {
      $parameters['start_date'] = $_POST['start_date'];
      $parameters['end_date']   = $_POST['end_date'];
    }

    $endpoint = 'v3/stats';
    
    if ( isset( $_POST['type'] ) && 'general' != $_POST['type'] ) {
      if( 'wordpress' == $_POST['type'] ) {
        $parameters['categories'] = 'wp_sendgrid_plugin';
      } else {
        $parameters['categories'] = urlencode( $_POST['type'] );
      }
      $endpoint = 'v3/categories/stats';
    }
    echo Sendgrid_Tools::do_request( $endpoint, $parameters );

    die();
  }

}
