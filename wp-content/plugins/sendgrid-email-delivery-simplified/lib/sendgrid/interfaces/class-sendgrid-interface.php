<?php

require_once plugin_dir_path( __FILE__ ) . '../../../vendor/autoload.php';

interface Sendgrid_Send {
  public function send(SendGrid\Email $email);
}