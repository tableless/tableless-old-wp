<?php

require_once plugin_dir_path( __FILE__ ) . '../class-sendgrid-tools.php';

class SendGrid_BuddyPress_Mailer implements BP_Email_Delivery {
  public function bp_email( BP_Email $email ) {
    $recipients = $email->get_to();
    $to = array();
    foreach ( $recipients as $recipient ) {
      $to[] = $recipient->get_address();
    }

    $subject = $email->get_subject( 'replace-tokens' );
    $message = normalize_whitespace( $email->get_content_plaintext( 'replace-tokens' ) );

    $filter_set = false;

    if ( 'plaintext' != Sendgrid_Tools::get_content_type() ) {
      add_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ), 100 );
      $filter_set = true;
      $message = $email->get_template( 'add-content' );
    }

    $result = wp_mail( $to, $subject, $message );

    if ( $filter_set ) {
      remove_filter( 'wp_mail_content_type', array( $this, 'set_html_content_type' ) );
    }

    return $result;
  }
  
  function set_html_content_type( $content_type ) {
    return 'text/html';
  }
}