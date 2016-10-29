<?php

require_once plugin_dir_path( __FILE__ ) . 'class-sendgrid-tools.php';
require_once plugin_dir_path( __FILE__ ) . 'class-sendgrid-nlvx.php';
require_once plugin_dir_path( __FILE__ ) . 'class-sendgrid-virtual-pages.php';

class Sendgrid_OptIn_API_Endpoint{
  /** 
   * Hook WordPress
   *
   * @return void
   */
  public function __construct() {
    add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
    add_action( 'parse_request', array( $this, 'sniff_requests' ), 0 );
  }
    
  /** 
   * Add public query vars
   *
   * @param array $vars List of current public query vars
   * @return array $vars 
   */
  public function add_query_vars( $vars ) {
    $vars[] = '__sg_api';
    $vars[] = 'token';

    return $vars;
  }
  
  /** 
   * Sniff Requests
   * This is where we hijack all API requests
   *  
   * @return die if API request
   */
  public function sniff_requests() {
    global $wp;

    if( isset( $wp->query_vars['__sg_api'] ) )
    {
      $this->handle_request();
      exit;
    }
  }
  
  /** 
   * Handle Requests
   * This is where compute the email from the token and subscribe the user_error()
   *
   * @return void 
   */
  protected function handle_request() {
    global $wp;

    $token = $wp->query_vars['token'];
    if ( ! $token )
    {
      wp_redirect( 'sg-subscription-missing-token' );
      exit();
    }
    
    $transient = ( is_multisite() ? get_site_transient( $token ) : get_transient( $token ) );

    if ( ! $transient or
      ! is_array( $transient ) or
      ! isset( $transient['email'] )  or
      ! isset( $transient['first_name'] ) or
      ! isset( $transient['last_name'] ) )
    {
      wp_redirect( 'sg-subscription-invalid-token' );
      exit();
    }

    $subscribed = Sendgrid_NLVX::create_and_add_recipient_to_list(
                    $transient['email'],
                    $transient['first_name'],
                    $transient['last_name'] );

    if ( $subscribed )
    {
      $page = Sendgrid_Tools::get_mc_signup_confirmation_page_url();

      if ( $page == false ) {
        if ( is_multisite() ) {
          set_site_transient( $token, null );
        } else {
          set_transient( $token, null );
        }
        wp_redirect( 'sg-subscription-success' );
        exit();
      } 
      else 
      {
        $page = add_query_arg( 'sg_token', $token, $page );

        wp_redirect( $page );
        exit();
      }

      return;
    }
    else
    {
      wp_redirect( 'sg-error' );
      exit();
    }
  }

  /** 
   * Send OptIn email
   *  
   * @param  string $email      Email of subscribed user
   * @param  string $first_name First Name of subscribed user
   * @param  string $last_name  Last Name of subscribed user
   * @return bool
   */
  public static function send_confirmation_email( $email, $first_name = '', $last_name = '', $from_settings = false ) {
    $subject = Sendgrid_Tools::get_mc_signup_email_subject();
    $content = Sendgrid_Tools::get_mc_signup_email_content();
    $content_text = Sendgrid_Tools::get_mc_signup_email_content_text();

    if ( false == $subject or false == $content or false == $content_text ) {
      return false;
    }

    $subject = stripslashes( $subject );
    $content = stripslashes( $content );
    $content_text = stripslashes( $content_text );
    $to = array( $email );

    $token = Sendgrid_OptIn_API_Endpoint::generate_email_token( $email, $first_name, $last_name );

    $transient = ( is_multisite() ? get_site_transient($token) : get_transient($token) );

    if ( $transient and isset( $transient['email'] ) and ! $from_settings ) {
      return false;
    }

    if ( is_multisite() ) {
      if ( false == set_site_transient( $token,
        array(
          'email' => $email,
          'first_name' => $first_name,
          'last_name' => $last_name ),
          24 * 60 * 60 ) and ! $from_settings and $transient ) {
        return false;
      }
    } elseif ( false == set_transient( $token,
      array(
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name ),
        24 * 60 * 60 ) and ! $from_settings and $transient ) {
      return false;
    }

    $confirmation_link = site_url() . '/?__sg_api=1&token=' . $token;
    $headers = new SendGrid\Email();
    $headers->addSubstitution( '%confirmation_link%', array( $confirmation_link ) )
            ->addCategory( 'wp_sendgrid_subscription_widget' );

    add_filter( 'sendgrid_mail_text', function() use ( &$content_text ) { return $content_text; } );
        
    $result = wp_mail( $to, $subject, $content, $headers );

    return $result;
  }

  /**
   * Generates a hash from an email address using sha1
   *
   * @return string hash from email address
   */
  private static function generate_email_token( $email ){
    return hash( "sha1", $email );
  }
}

/**
 * register first name as a shortcode
 *
 * @param  array  $atts   an associative array of attributes
 *
 * @return string         first name
 */
function register_shortcode_first_name($atts)
{
  if ( ! isset( $_GET['sg_token'] ) ) {
    return '';
  }

  $token = $_GET['sg_token'];
  $transient = get_transient( $token );

  if ( is_multisite() ) {
    $transient = get_site_transient( $token );
  }

  if ( ! $transient || 
    ! is_array( $transient ) || 
    ! isset( $transient['first_name'] ) )
  {
    return '';
  }

  return $transient['first_name'];
}

/**
 * register last name as a shortcode
 *
 * @param  array  $atts   an associative array of attributes
 *
 * @return string         last name
 */
function register_shortcode_last_name($atts)
{
  if ( ! isset( $_GET['sg_token'] ) ) {
    return '';
  }

  $token = $_GET['sg_token'];
  $transient = get_transient( $token );

  if ( is_multisite() ) {
    $transient = get_site_transient( $token );
  }

  if ( ! $transient or
    ! is_array( $transient ) or
    ! isset( $transient['last_name'] ) )
  {
    return '';
  }

  return $transient['last_name'];
}

/**
 * register email as a shortcode
 *
 * @param  array  $atts   an associative array of attributes
 *
 * @return string         email
 */
function register_shortcode_email($atts)
{
  if ( ! isset( $_GET['sg_token'] ) ) {
    return '';
  }

  $token = $_GET['sg_token'];
  $transient = get_transient( $token );

  if ( is_multisite() ) {
    $transient = get_site_transient( $token );
  }

  if ( ! $transient or 
    ! is_array( $transient ) or 
    ! isset( $transient['email'] ) )
  {
    return '';
  }

  return $transient['email'];
}

/**
 * register shortcodes
 *
 * @return void
 */
function sg_register_shortcodes()
{
  add_shortcode( 'sendgridSubscriptionFirstName', 'register_shortcode_first_name' );
  add_shortcode( 'sendgridSubscriptionLastName', 'register_shortcode_last_name' );
  add_shortcode( 'sendgridSubscriptionEmail', 'register_shortcode_email' );
}

function sg_invalidate_token() {
  if ( ! isset( $_GET['sg_token'] ) ) {
    return;
  }

  $token = $_GET['sg_token'];
  $transient = get_transient( $token );

  if ( is_multisite() ) {
    $transient = get_site_transient( $token );
  }

  if ( $token and $transient ) {
    if ( is_multisite() ) {
      set_site_transient( $token, null );
    } else {
      set_transient( $token, null );
    }
  }
}

// Initialize OptIn Endopint
new Sendgrid_OptIn_API_Endpoint();

add_action( 'init', 'sg_create_subscribe_general_error_page' );
add_action( 'init', 'sg_create_subscribe_missing_token_error_page' );
add_action( 'init', 'sg_create_subscribe_invalid_token_error_page' );
add_action( 'init', 'sg_create_subscribe_success_page' );
add_action( 'init', 'sg_register_shortcodes' );
add_action( 'wp_footer', 'sg_invalidate_token' );