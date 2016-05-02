<?php

require_once plugin_dir_path( __FILE__ ) . 'interfaces/class-sendgrid-interface.php';
require_once plugin_dir_path( __FILE__ ) . '../../vendor/autoload.php';

class Sendgrid_SMTP implements Sendgrid_Send {
  //the available ports
  const TLS = 587;
  const TLS_ALTERNATIVE = 25;
  const SSL = 465;

  //the list of port instances, to be recycled
  private $swift_instances = array();
  private $port;
  private $username;
  private $password;

  public function __construct( $username, $password ) {
    /* check for SwiftMailer,
     * if it doesn't exist, try loading
     * it from Pear
     */
    if ( ! class_exists('Swift') ) {
      require_once 'swift_required.php';
    }
    $this->username = $username;
    $this->password = $password;

    //set the default port
    $this->port = self::TLS;
  }

  /* set_port
   * set the SMTP outgoing port number
   * @param Int $port - the port number to use
   * @return the SMTP object
   */
  public function set_port( $port ) {
    $this->port = $port;

    return $this;
  }

  /* send
   * Send the Mail Message
   * @param Mail $mail - the SendGridMailMessage to be sent
   * @return true if mail was sendable (not necessarily sent)
   */
  public function send( SendGrid\Email $mail ) {
    $swift = $this->get_swift_instance($this->port);

    $message = $this->map_to_swift( $mail );

    try
    {
      $sent = $swift->send( $message, $failures );
    }
    catch(Exception $e)
    {
      return false;
    }

    return ( $sent === 0 ) ? false : true;
  }

  /* get_swift_instance
   * initialize and return the swift transport instance
   * @return the Swift_Mailer instance
   */
  private function get_swift_instance( $port ) {
    if ( ! isset( $this->swift_instances[$port] ) ) {
      $transport = \Swift_SmtpTransport::newInstance( 'smtp.sendgrid.net', $port );
      $transport->setUsername( $this->username );
      $transport->setPassword( $this->password );

      $swift = \Swift_Mailer::newInstance( $transport );

      $this->swift_instances[$port] = $swift;
    }

    return $this->swift_instances[$port];
  }

  /* map_to_swift
   * Maps the SendGridMail Object to the SwiftMessage object
   * @param Mail $mail - the SendGridMail object
   * @return the SwiftMessage object
   */
  private function map_to_swift( SendGrid\Email $mail )  {
    $message = new \Swift_Message( $mail->getSubject() );

    /*
     * Since we're sending transactional email, we want the message to go to one person at a time, rather
     * than a bulk send on one message. In order to do this, we'll have to send the list of recipients through the headers
     * but Swift still requires a 'to' address. So we'll falsify it with the from address, as it will be 
     * ignored anyway.
     */
    $message->setTo( $mail->to );
    $message->setFrom( $mail->getFrom( true ) );
    $message->setCc( $mail->getCcs() );
    $message->setBcc( $mail->getBccs() );

    if ( $mail->getHtml() ) {
      $message->setBody($mail->getHtml(), 'text/html');
      if ( $mail->getText() ) $message->addPart( $mail->getText(), 'text/plain' );
    } else {
      $message->setBody( $mail->getText(), 'text/plain' );
    }

    if ( ( $replyto = $mail->getReplyTo() ) ) {
      $message->setReplyTo( $replyto );
    }
    
    $attachments = $mail->getAttachments();

    //add any attachments that were added
    if ( $attachments ) {
      foreach ( $attachments as $attachment ) {
        $message->attach( \Swift_Attachment::fromPath( $attachment['file'] ) );
      }
    }

    $message_headers  = $message->getHeaders();
    $message_headers->addTextHeader( "x-smtpapi", $mail->smtpapi->jsonString() );
    
    return $message;
  }
}
