<h2 class="nav-tab-wrapper sengrid-settings-nav-bar">
  <?php 
    foreach ( $tabs as $tab_key => $tab_description ) {
      if ( $active_tab == $tab_key ) {
        echo '<a href="?page=sendgrid-settings&tab=' . $tab_key . '" class="nav-tab nav-tab-active">' . $tab_description . '</a>';
      } else {
        echo '<a href="?page=sendgrid-settings&tab=' . $tab_key . '" class="nav-tab">' . $tab_description . '</a>';
      }
    }
  ?>
</h2>