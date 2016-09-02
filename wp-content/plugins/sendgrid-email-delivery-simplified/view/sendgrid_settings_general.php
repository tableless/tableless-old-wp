<?php if ( $active_tab == 'general' ): ?>
  <form class="form-table" name="sendgrid_form" id="sendgrid_general_settings_form" method="POST" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ); ?>">
    <table class="form-table">
      <tbody>
        <tr valign="top">
          <td colspan="2">
            <h3 class="sendgrid-settings-top-header"><?php echo _e('SendGrid Credentials') ?></h3>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><?php _e("Authentication method: "); ?></th>
          <td>
            <select name="auth_method" class="sendgrid-settings-select" id="auth_method" <?php disabled( $is_env_auth_method ); ?> >
              <option value="apikey" id="apikey" <?php echo ( 'apikey' == $auth_method ) ? 'selected' : '' ?>><?php _e('Api Key') ?></option>
              <option value="credentials" id="credentials" <?php echo ( 'credentials' == $auth_method ) ? 'selected' : '' ?>><?php _e('Username&Password') ?></option>
              <?php if ( ! in_array( $auth_method, Sendgrid_Tools::$allowed_auth_methods ) ) { ?>
                <option value="<?php echo $auth_method; ?>" id="<?php echo $auth_method; ?>" selected><?php echo $auth_method; ?></option>
              <?php } ?>
            </select>
          </td>
        </tr>
        <tr valign="top" class="apikey" style="display: none;">
          <th scope="row"><?php _e("API Key: "); ?></th>
          <td>
            <input type="password" id="sendgrid_general_apikey" name="sendgrid_apikey" class="sendgrid-settings-key" value="<?php echo ( $is_env_api_key ? "************" : $api_key );  ?>" <?php disabled( $is_env_api_key ); ?>>
          </td>
        </tr>
        <tr valign="top" class="credentials" style="display: none;">
          <th scope="row"><?php _e("Username: "); ?></th>
          <td>
            <input type="text" name="sendgrid_username" value="<?php echo $user; ?>" size="20" class="regular-text" <?php disabled( $is_env_username ); ?>>
          </td>
        </tr>
        <tr valign="top" class="credentials" style="display: none;">
          <th scope="row"><?php _e("Password: "); ?></th>
          <td>
            <input type="password" name="sendgrid_password" value="<?php echo ( $is_env_password ? "******" : $password );  ?>" size="20" class="regular-text" <?php disabled( $is_env_password ); ?>>
          </td>
        </tr>
        <tr valign="top" class="send_method" style="display: none;">
          <th scope="row"><?php _e("Send Mail with: "); ?></th>
          <td>
            <select name="send_method" class="sendgrid-settings-select" id="send_method" <?php disabled( defined('SENDGRID_SEND_METHOD') ); ?>>
              <?php foreach ( $allowed_send_methods as $method ): ?>
                <option value="<?php echo strtolower( $method ); ?>" <?php echo ( strtolower( $method ) == $send_method ) ? 'selected' : '' ?>><?php _e( $method ) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ( ! in_array( "SMTP", $allowed_send_methods ) ): ?>
              <p>
                <?php _e('Swift is required in order to be able to send via SMTP.'); ?>
              </p>
            <?php endif; ?>
          </td>
        </tr>
        <tr valign="top" class="port" style="display: none;">
          <th scope="row"><?php _e("Port: "); ?></th>
          <td>
            <select name="sendgrid_port" id="sendgrid_port" <?php disabled( $is_env_port ); ?>>
              <option value="<?php echo SendGrid_SMTP::TLS ?>" id="tls" <?php echo ( ( SendGrid_SMTP::TLS == $port ) or (! $port ) ) ? 'selected' : '' ?>><?php _e( SendGrid_SMTP::TLS ) ?></option>
              <option value="<?php echo SendGrid_SMTP::TLS_ALTERNATIVE ?>" id="tls_alt" <?php echo ( SendGrid_SMTP::TLS_ALTERNATIVE == $port ) ? 'selected' : '' ?>><?php _e( SendGrid_SMTP::TLS_ALTERNATIVE ) ?></option>
              <option value="<?php echo SendGrid_SMTP::SSL ?>" id="ssl" <?php echo ( SendGrid_SMTP::SSL == $port ) ? 'selected' : '' ?>><?php _e( SendGrid_SMTP::SSL ) ?></option>
            </select>
          </td>
        </tr>
        <?php if ( $is_env_auth_method or $is_env_send_method or $is_env_api_key or $is_env_username or $is_env_password or $is_env_port ) : ?>
          <tr valign="top">
            <td colspan="2">
              <p>
                <?php _e('Disabled fields are already configured in the config file.'); ?>
              </p>
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
    <br />
    <table class="form-table">
      <tbody>
        <tr valign="top">
          <td colspan="2">
            <h3><?php echo _e('Mail settings') ?></h3>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><?php _e("Name: "); ?></th>
          <td>
            <input type="text" name="sendgrid_name" value="<?php echo $name; ?>" size="20" class="regular-text" <?php disabled( defined('SENDGRID_FROM_NAME') ); ?>>
            <p class="description"><?php _e('Name as it will appear in recipient clients.') ?></p>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><?php _e("Sending Address: "); ?></th>
          <td>
            <input type="text" name="sendgrid_email" value="<?php echo $email; ?>" size="20" class="regular-text" <?php disabled( defined('SENDGRID_FROM_EMAIL') ); ?>>
            <p class="description"><?php _e('Email address from which the message will be sent.') ?></p>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><?php _e("Reply Address: "); ?></th>
          <td>
            <input type="text" name="sendgrid_reply_to" value="<?php echo $reply_to; ?>" size="20" class="regular-text" <?php disabled( defined('SENDGRID_REPLY_TO') ); ?>>
            <span><small><em><?php _e('Leave blank to use Sending Address.') ?></em></small></span>
            <p class="description"><?php _e('Email address where replies will be returned.') ?></p>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><?php _e("Categories: "); ?></th>
          <td>
            <input type="text" name="sendgrid_categories" value="<?php echo $categories; ?>" size="20" class="regular-text" <?php disabled( defined('SENDGRID_CATEGORIES') ); ?>>
            <span><small><em><?php _e('Leave blank to send without categories.') ?></em></small></span>
            <p class="description"><?php _e('Associates the category of the email this should be logged as. <br />
            Categories must be separated by commas (Example: category1,category2).') ?></p>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><?php _e("Template: "); ?></th>
          <td>
            <input type="text" name="sendgrid_template" value="<?php echo $template; ?>" size="20" class="regular-text" <?php disabled( defined('SENDGRID_TEMPLATE') ); ?>>
            <span><small><em><?php _e('Leave blank to send without template.') ?></em></small></span>
            <p class="description"><?php _e('The template ID used to send emails. <br />
            Example: 0b1240a5-188d-4ea7-93c1-19a7a89466b2.') ?></p>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><?php _e("Content-type: "); ?></th>
          <td>
            <select name="content_type" class="sendgrid-settings-select" id="content_type" <?php disabled( $is_env_content_type ); ?> >
              <option value="plaintext" id="plaintext" <?php echo ( 'plaintext' == $content_type ) ? 'selected' : '' ?>><?php _e('text/plain') ?></option>
              <option value="html" id="html" <?php echo ( 'html' == $content_type ) ? 'selected' : '' ?>><?php _e('text/html') ?></option>
            </select>
          </td>
        </tr>
        <tr valign="top">
          <th scope="row"><?php _e("Unsubscribe Group: "); ?></th>
          <td>
            <select id="select_unsubscribe_group" class="sendgrid-settings-select" name="unsubscribe_group" <?php disabled( $is_env_unsubscribe_group ); ?> <?php disabled( $no_permission_on_unsubscribe_groups ); ?>>
              <option value="0"><?php _e("Global Unsubscribe"); ?></option>
              <?php
                if ( false != $unsubscribe_groups ) {
                  foreach ( $unsubscribe_groups as $key => $group ) {
                    if ( $unsubscribe_group_id == $group['id'] ) {
                      echo '<option value="' . $group['id'] . '" selected="selected">' . $group['name'] . '</option>';
                    } else {
                      echo '<option value="' . $group['id'] . '">' . $group['name'] . '</option>';
                    }           
                  }
                }
              ?>
            </select>
            <p class="description"><?php _e("User will have the option to unsubscribe from the selected group. <br /> The API Key needs to have 'Unsubscribe Groups' permissions to be able to select a group.") ?></p>
          </td>
        </tr>
      </tbody>
    </table>
  <br />
  <table class="form-table">
    <tbody>
      <tr valign="top">
            <td colspan="2">
              <h3><?php echo _e('Statistics settings') ?></h3>
            </td>
        </tr>
      <tr valign="top">
        <th scope="row"><?php _e("Categories: "); ?></th>
        <td>
          <input type="text" name="sendgrid_stats_categories" value="<?php echo $stats_categories; ?>" size="20" class="regular-text" <?php disabled( defined('SENDGRID_STATS_CATEGORIES') ); ?>>
          <span><small><em><?php _e('Leave blank for not showing category stats.') ?></em></small></span>
          <p class="description"><?php _e('Add some categories for which you would like to see your stats. <br />
          Categories must be separated by commas (Example: category1,category2).') ?></p>
        </td>
      </tr>
      <tr valign="top">
        <td colspan="2">
          <p>
            <?php _e('Disabled fields in this form means that they are already configured in the config file.'); ?>
          </p>
        </td>
      </tr>
    </tbody>
  </table>
  <p class="submit">
    <input class="button button-primary" type="submit" name="Submit" value="<?php _e('Update Settings') ?>" />
  </p>
  <input type="hidden" name="general_settings" value="true"/>
</form>  
<br />
<?php endif; ?>