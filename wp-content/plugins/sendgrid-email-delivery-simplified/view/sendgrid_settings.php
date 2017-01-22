<div class="wrap">
  <a href="http://sendgrid.com" target="_blank">
    <img src="<?php echo plugins_url( '/images/logo.png', __FILE__ ) ?>" width="100" alt="" />
  </a>
  <?php if ( isset( $status ) and ( 'updated' == $status or 'error' == $status ) ): ?>
    <div id="message" class="<?php echo $status ?>">
      <p>
        <strong><?php echo $message ?></strong>
      </p>
    </div>
  <?php endif; ?>

  <?php 
    $tabs = array( 'general' => 'General', 'marketing' => 'Subscription Widget' );

    // If network settings display settings for subsites
    if ( is_multisite() and is_main_site() ) {
      $tabs['multisite'] = 'Multisite Settings';
    }

    $active_tab = current( array_keys( $tabs ) );
    if ( isset( $_GET[ 'tab' ] ) ) {
      $selected_tab = $_GET[ 'tab' ];
      if ( array_key_exists( $selected_tab, $tabs ) ) {
        $active_tab = $selected_tab;
      }
    }
  ?>

  <?php
    require_once plugin_dir_path( __FILE__ ) . 'sendgrid_settings_nav.php';
    require_once plugin_dir_path( __FILE__ ) . 'sendgrid_settings_general.php';
    require_once plugin_dir_path( __FILE__ ) . 'sendgrid_settings_test_email.php';
    require_once plugin_dir_path( __FILE__ ) . 'sendgrid_settings_nlvx.php';
    require_once plugin_dir_path( __FILE__ ) . 'sendgrid_settings_test_contact.php';
    require_once plugin_dir_path( __FILE__ ) . 'sendgrid_settings_multisite.php';
  ?>
</div>  
