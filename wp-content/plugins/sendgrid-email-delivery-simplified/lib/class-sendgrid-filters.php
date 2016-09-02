<?php

require_once plugin_dir_path( __FILE__ ) . 'integration/class-buddypress.php';

class Sendgrid_Filters {
  public $integrations;

  public function __construct() {
    $integrations = array();
    $integrations[] = new Sendgrid_BuddyPress_Integration();

    foreach ( $integrations as $integration ) {
      add_action( 'init', array( $integration, 'register' ) );
    }
  }
}