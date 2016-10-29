<?php if ( $active_tab == 'marketing' ): ?>
  <form class="form-table" name="sendgrid_form" id="sendgrid_form_mc" method="POST" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ); ?>">
    <table class="form-table">
      <tbody>
        <tr valign="top">
          <td colspan="2">
            <h3 class="sendgrid-settings-top-header"><?php echo _e('SendGrid Credentials') ?></h3>
          </td>
        </tr>
        <tr valign="top" class="mc_apikey">
          <th scope="row"><?php _e("API Key: "); ?></th>
          <td>
            <input type="password" id="mc_apikey" name="sendgrid_mc_apikey" value="<?php echo ( $is_env_mc_api_key ? "************" : $mc_api_key );  ?>" size="50" <?php disabled( $is_env_mc_api_key ); ?>>
            <p class="description"><?php _e('An API Key to use for uploading contacts to SendGrid. This API Key needs to have full Marketing Campaigns permissions.') ?></p>
          </td>
        </tr>
        <tr valign="top" class="use_transactional">
          <th scope="row"><?php _e("Use same authentication as transactional: "); ?></th>
          <td>
            <input type="checkbox" id="use_transactional" name="sendgrid_mc_use_transactional" value="true" <?php echo $checked_use_transactional; disabled( $is_env_mc_opt_use_transactional ); ?>>
            <p class="description"><?php _e('If checked, the contacts will be uploaded using the same credentials that are used for sending emails.') ?></p>
          </td>
        </tr>

        <tr valign="top">
          <td colspan="2">
            <h3><?php echo _e('Subscription Options') ?></h3>
          </td>
        </tr>
        <tr valign="top" class="select_contact_list">
          <th scope="row"><?php _e("Contact list to upload to: "); ?></th>
          <td>
            <select id="select_contact_list" class="sengrid-settings-select" name="sendgrid_mc_contact_list" <?php disabled( $is_env_mc_list_id ); ?>>
            <?php
              if ( false != $contact_lists && $is_mc_api_key_valid ) {
                foreach ( $contact_lists as $key => $list ) {
                  if ( $mc_list_id == $list['id'] ) {
                    echo '<option value="' . $list['id'] . '" selected="selected">' . $list['name'] . '</option>';
                  } else {
                    echo '<option value="' . $list['id'] . '">' . $list['name'] . '</option>';
                  }           
                }
              }
            ?>
            </select>
            <p class="description"><?php _e('The contact details of a subscriber will be uploaded to the selected list.') ?></p>
          </td>
        </tr>

        <tr valign="top" class="include_fname_lname">
          <th scope="row"> <?php _e("Include First and Last Name fields:"); ?> </th>
          <td>
            <input type="checkbox" id="include_fname_lname" name="sendgrid_mc_incl_fname_lname" value="true" <?php echo $checked_incl_fname_lname; disabled( $is_env_mc_opt_incl_fname_lname ); ?>>
            <p class="description"><?php _e('If checked, the first and last name fields will be displayed in the widget.') ?></p>
          </td>
        </tr>

        <tr valign="top" class="req_fname_lname">
          <th scope="row"> <?php _e("First and Last Name are required:"); ?> </th>
          <td>
            <input type="checkbox" id="req_fname_lname" name="sendgrid_mc_req_fname_lname" value="true" <?php echo $checked_req_fname_lname; disabled( $is_env_mc_opt_req_fname_lname ); ?>>
            <p class="description"><?php _e('If checked, empty values for the first and last name fields will be rejected.') ?></p>
          </td>
        </tr>

        <tr valign="top" class="signup_email_subject">
          <th scope="row"> <?php _e("Signup email subject:"); ?></th>
          <td>
            <input type="text" id="signup_email_subject" name="sendgrid_mc_email_subject" size="50" value="<?php echo $mc_signup_email_subject; ?>" <?php disabled( $is_env_mc_signup_email_subject ); ?>>
            <p class="description"><?php _e('The subject for the confirmation email.') ?></p>
          </td>
        </tr>

        <tr valign="top" class="signup_email_content">
          <th scope="row"> <?php _e("Signup email content (HTML):"); ?></th>
          <td>
            <textarea rows="8" cols="48"  id="signup_email_content" name="sendgrid_mc_email_content" class="regular-text"  <?php disabled( $is_env_mc_signup_email_content ); ?>><?php echo $mc_signup_email_content; ?></textarea>
            <p class="description"><?php _e('Confirmation emails must contain a verification link to confirm the email address being added.') ?> <br/> <?php _e(' You can control the placement of this link by inserting a <b>&lt;a href="%confirmation_link%"&gt; &lt;/a&gt;</b> tag in your email content. This tag is required.') ?></p>
          </td>
        </tr>

        <tr valign="top" class="signup_email_content_text">
          <th scope="row"> <?php _e("Signup email content (Plain Text):"); ?></th>
          <td>
            <textarea rows="8" cols="48" id="signup_email_content_text" name="sendgrid_mc_email_content_text" class="regular-text"  <?php disabled( $is_env_mc_signup_email_content_text ); ?>><?php echo $mc_signup_email_content_text; ?></textarea>
            <p class="description"><?php _e('Confirmation emails must contain a verification link to confirm the email address being added.') ?> <br/> <?php _e(' You can control the placement of this link by inserting a <b>%confirmation_link%</b> tag in your email content. This tag is required.') ?></p>
          </td>
        </tr>

        <tr valign="top" class="signup_select_page">
          <th scope="row"> <?php _e("Signup confirmation page:"); ?></th>
          <td>
            <select id="signup_select_page" class="sengrid-settings-select" name="sendgrid_mc_signup_page" <?php disabled( $is_env_mc_signup_confirmation_page ); ?>>
            <?php
              if ( 'default' == $mc_signup_confirmation_page ) {
                echo '<option value="default" selected>Default Confirmation Page</option>';
              } else {
                echo '<option value="default">Default Confirmation Page</option>';
              }

              if ( false != $confirmation_pages ) {
                foreach ($confirmation_pages as $key => $page) {
                  if ( $mc_signup_confirmation_page == $page->ID ) {
                    echo '<option value="' . $page->ID . '" selected="selected">' . $page->post_title . '</option>';
                  } else {
                    echo '<option value="' . $page->ID . '">' . $page->post_title . '</option>';
                  }           
                }
              }
            ?>
            </select>
            <p class="description"><?php _e('If the user clicks the confirmation link received in the email, he will be redirected to this page after the contact details are uploaded successfully to SendGrid.') ?></p>
          </td>
        </tr>

        <tr valign="top">
          <td colspan="2">
            <h3><?php echo _e('Form Customization') ?></h3>
          </td>
        </tr>
        <tr valign="top" class="signup_email_label">
          <th scope="row"> <?php _e("Email Label:"); ?></th>
          <td>
            <input type="text" id="signup_email_label" name="sendgrid_mc_email_label" size="50" value="<?php echo $mc_signup_email_label; ?>" <?php disabled( $is_env_mc_email_label ); ?>>
            <p class="description"><?php _e('The label for \'Email\' field on the subscription form.') ?></p>
          </td>
        </tr>
        <tr valign="top" class="signup_first_name_label">
          <th scope="row"> <?php _e("First Name Label:"); ?></th>
          <td>
            <input type="text" id="signup_first_name_label" name="sendgrid_mc_first_name_label" size="50" value="<?php echo $mc_signup_first_name_label; ?>" <?php disabled( $is_env_mc_first_name_label ); ?>>
            <p class="description"><?php _e('The label for \'First Name\' field on the subscription form.') ?></p>
          </td>
        </tr>
        <tr valign="top" class="signup_last_name_label">
          <th scope="row"> <?php _e("Last Name Label:"); ?></th>
          <td>
            <input type="text" id="signup_last_name_label" name="sendgrid_mc_last_name_label" size="50" value="<?php echo $mc_signup_last_name_label; ?>" <?php disabled( $is_env_mc_last_name_label ); ?>>
            <p class="description"><?php _e('The label for \'Last Name\' field on the subscription form.') ?></p>
          </td>
        </tr>
        <tr valign="top" class="signup_subscribe_label">
          <th scope="row"> <?php _e("Subscribe Label:"); ?></th>
          <td>
            <input type="text" id="signup_subscribe_label" name="sendgrid_mc_subscribe_label" size="50" value="<?php echo $mc_signup_subscribe_label; ?>" <?php disabled( $is_env_mc_subscribe_label ); ?>>
            <p class="description"><?php _e('The label for \'Subscribe\' button on the subscription form.') ?></p>
          </td>
        </tr>

        <tr valign="top">
          <th scope="row"> <?php _e("Input Padding (in px):"); ?></th>
          <td>
            <label><?php _e("Top:"); ?></label>
            <input type="text" name="sendgrid_mc_input_padding_top" size="4" value="<?php echo $mc_signup_input_padding_top; ?>" />

            <label class="sendgrid_settings_mc_input_padding_label"><?php _e("Right:"); ?></label>
            <input type="text" name="sendgrid_mc_input_padding_right" size="4" value="<?php echo $mc_signup_input_padding_right; ?>" />

            <label class="sendgrid_settings_mc_input_padding_label"><?php _e("Bottom:"); ?></label>
            <input type="text" name="sendgrid_mc_input_padding_bottom" size="4" value="<?php echo $mc_signup_input_padding_bottom; ?>" />

            <label class="sendgrid_settings_mc_input_padding_label"><?php _e("Left:"); ?></label>
            <input type="text" name="sendgrid_mc_input_padding_left" size="4" value="<?php echo $mc_signup_input_padding_left; ?>" />
            <p class="description"><?php _e('The padding values for the input fields on the subscription form.') ?></p>
          </td>
        </tr>

        <tr valign="top">
          <th scope="row"> <?php _e("Button Padding (in px):"); ?></th>
          <td>
            <label><?php _e("Top:"); ?></label>
            <input type="text" name="sendgrid_mc_button_padding_top" size="4" value="<?php echo $mc_signup_button_padding_top; ?>" />

            <label class="sendgrid_settings_mc_input_padding_label"><?php _e("Right:"); ?></label>
            <input type="text" name="sendgrid_mc_button_padding_right" size="4" value="<?php echo $mc_signup_button_padding_right; ?>" />

            <label class="sendgrid_settings_mc_input_padding_label"><?php _e("Bottom:"); ?></label>
            <input type="text" name="sendgrid_mc_button_padding_bottom" size="4" value="<?php echo $mc_signup_button_padding_bottom; ?>" />

            <label class="sendgrid_settings_mc_input_padding_label"><?php _e("Left:"); ?></label>
            <input type="text" name="sendgrid_mc_button_padding_left" size="4" value="<?php echo $mc_signup_button_padding_left; ?>" />
            <p class="description"><?php _e('The padding values for the button on the subscription form.') ?></p>
          </td>
        </tr>

        <?php if ( $is_env_mc_api_key or $is_env_mc_opt_use_transactional or $is_env_mc_opt_incl_fname_lname or
                   $is_env_mc_opt_req_fname_lname or $is_env_mc_signup_email_subject or $is_env_mc_signup_email_content or  
                   $is_env_mc_signup_confirmation_page or $is_env_mc_email_label or $is_env_mc_first_name_label or
                   $is_env_mc_last_name_label or $is_env_mc_subscribe_label) : ?>
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
    <p class="submit">
      <input class="button button-primary" type="submit" name="Submit" value="<?php _e('Update Settings') ?>" />
    </p>
    <input type="hidden" name="mc_settings" value="true"/>
    <?php
      if ( $is_env_mc_api_key ) {
        echo '<input type="hidden" name="mc_api_key_defined_in_env" id="mc_api_key_defined_in_env" value="true"/>';
      }

      if ( $is_env_mc_list_id ) {
        echo '<input type="hidden" name="mc_list_id_defined_in_env" id="mc_list_id_defined_in_env" value="true"/>';
      }

      if ( $is_env_mc_signup_confirmation_page ) {
        echo '<input type="hidden" name="mc_signup_page_defined_in_env" id="mc_signup_page_defined_in_env" value="true"/>';
      }

      if ( $is_mc_api_key_valid ) {
        echo '<input type="hidden" name="mc_api_key_is_valid" id="mc_api_key_is_valid" value="true"/>';
      }
    ?>
  </form>
  <br />
<?php endif; ?>