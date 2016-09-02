<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/*  Copyright 2006 Vincent Prat  

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
?>

<?php
	global $user_identity, $user_email, $user_ID, $mailusers_send_to_group_mode ;

	if (!current_user_can(MAILUSERS_EMAIL_USER_GROUPS_CAP)) {
        wp_die(printf('<div class="error fade"><p>%s</p></div>',
            __('You are not allowed to send emails to user groups.', MAILUSERS_I18N_DOMAIN)));
	}

	if (!isset($send_targets)) {
		$send_targets = array();
	}

	if (!isset($mail_format)) {
		$mail_format = mailusers_get_default_mail_format();
	}

	if (!isset($subject)) {
		$subject = '';
	}

	if (!isset($mail_content)) {
		$mail_content = '';
	}

	if (!isset($mail_content)) {
		$mail_content = '';
	}

	if (!isset($group_mode)) {
		$group_mode = $mailusers_send_to_group_mode;
	}

	wp_get_current_user();

	$from_name = $user_identity;
	$from_address = $user_email;
    $override_name = mailusers_get_from_sender_name_override() ;
    $override_address = mailusers_get_from_sender_address_override() ;

?>

<div class="wrap">
	<div id="icon-users" class="icon32"><br/></div>
	<h2><?php if ($group_mode == 'meta') _e('Send an Email to User Groups Filtered by User Meta Data', MAILUSERS_I18N_DOMAIN); else _e('Send an Email to a Group of Users', MAILUSERS_I18N_DOMAIN); ?></h2>

	<?php 	if (isset($err_msg) && $err_msg!='') { ?>
			<div class="error fade"><p><?php echo $err_msg; ?><p></div>
			<p><?php _e('Please correct the errors displayed above and try again.', MAILUSERS_I18N_DOMAIN); ?></p>
	<?php	} ?>

	<!--<form name="SendEmail" action="admin.php?page=mailusers-send-group-mail-page" method="post">-->
	<form name="SendEmail" action="" method="post">
        <?php wp_nonce_field( 'mailusers_send_to_group', 'mailusers_send_to_group_nonce' ); ?>
		<input type="hidden" name="send" value="true" />
		<input type="hidden" name="fromName" value="<?php echo $from_name;?>" />
		<input type="hidden" name="fromAddress" value="<?php echo $from_address;?>" />
		<input type="hidden" name="group_mode" value="<?php echo $mailusers_send_to_group_mode;?>" />

		<table class="form-table" width="100%" cellspacing="2" cellpadding="5">
		<tr>
			<th scope="row" valign="top"><?php _e('Mail format', MAILUSERS_I18N_DOMAIN); ?></th>
			<td><select class="mailusers-select" name="mail_format" style="width: 158px;">
				<option value="html" <?php if ($mail_format=='html') echo 'selected="selected"'; ?>><?php _e('HTML', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="plaintext" <?php if ($mail_format=='plaintext') echo 'selected="selected"'; ?>><?php _e('Plain text', MAILUSERS_I18N_DOMAIN); ?></option>
			</select></td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label><?php _e('Sender', MAILUSERS_I18N_DOMAIN); ?></label></th>
            <?php if (empty($override_address)) { ?>
			<td><?php echo $from_name;?> &lt;<?php echo $from_address;?>&gt;</td>
            <?php } else { ?>
            <td><input name="from_sender" type="radio" value="0" checked/><?php echo $from_name;?> &lt;<?php echo $from_address;?>&gt;<br/><input name="from_sender" type="radio" value="1"/><?php echo $override_name;?> &lt;<?php echo $override_address;?>&gt;</td>
            <?php }?>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="send_targets"><?php _e('Recipients', MAILUSERS_I18N_DOMAIN); ?>
			<br/><br/>
			<small><?php _e('You can select multiple groups by pressing the CTRL key.', MAILUSERS_I18N_DOMAIN); ?></small>
			<br/><br/>
			<small><?php _e('Only the groups having at least one user that accepts group mails appear here.', MAILUSERS_I18N_DOMAIN); ?></small></label></th>
			<td>
                <select data-placeholder="<?php _e('Choose Group Recipients ...', MAILUSERS_I18N_DOMAIN);?>" class="mailusers-select" id="send_targets" name="send_targets[]" multiple="multiple" size="8" style="width: 654px; height: 250px;">
                <?php 

                    $prefix = __('Filter', MAILUSERS_I18N_DOMAIN) ;
                    $targets = mailusers_get_group_meta_filters($user_ID, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META);

                    foreach ($targets as $key => $value)
                    {
                        $index = strtolower(MAILUSERS_CM_FILTER_PREFIX . '-' . $key); ?>
                        <option value="<?php echo $index; ?>"
                        <?php echo (in_array($index, $send_targets) ? ' selected="yes"' : '');?>>
                        <?php printf('%s - %s', $prefix, $value); ?>
                        </option>
                        <?php
                    }

                    $prefix = __('Role', MAILUSERS_I18N_DOMAIN) ;
                    $targets = mailusers_get_roles($user_ID, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META);

                    foreach ($targets as $key => $value)
                    {
                        $index = strtolower($prefix . '-' . $key); ?>
                        <option value="<?php echo $index; ?>"
                        <?php echo (in_array($index, $send_targets) ? ' selected="yes"' : '');?>>
                        <?php printf('%s - %s', $prefix, __($value)); ?>
                        </option>
                        <?php 
                    }

                    //  Is the User Groups plugin active?
                    if (class_exists(MAILUSERS_USER_GROUPS_CLASS))
                    {
                        $prefix = __('User Group', MAILUSERS_I18N_DOMAIN) ;
                        $targets = mailusers_get_user_groups($user_ID, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META);

                        foreach ($targets as $key => $value)
                        {
                            $index = MAILUSERS_USERS_GROUPS_PREFIX . '-' . strtolower($key); ?>
                            <option value="<?php echo $index; ?>"
                            <?php echo (in_array($index, $send_targets) ? ' selected="yes"' : '');?>>
                            <?php printf('%s - %s', $prefix, __($value)); ?>
                            </option>
                            <?php
                        }
                    }

                    //  Is the User Access Manager plugin active?
                    if (class_exists(MAILUSERS_USER_ACCESS_MANAGER_CLASS))
                    {
                        $prefix = __('UAM', MAILUSERS_I18N_DOMAIN) ;
                        $targets = mailusers_get_uam_groups($user_ID, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META);

                        foreach ($targets as $key => $value)
                        {
                            $index = MAILUSERS_USER_ACCESS_MANAGER_PREFIX . '-' . strtolower($key); ?>
                            <option value="<?php echo $index; ?>"
                            <?php echo (in_array($index, $send_targets) ? ' selected="yes"' : '');?>>
                            <?php printf('%s - %s', $prefix, __($value)); ?>
                            </option>
                            <?php
                        }
                    }

                    //  Is the ItThinx Groups plugin active?
                    if (class_exists(MAILUSERS_ITTHINX_GROUPS_CLASS))
                    {
                        $prefix = __('Groups', MAILUSERS_I18N_DOMAIN) ;
                        $targets = mailusers_get_itthinx_groups($user_ID, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META);

                        foreach ($targets as $key => $value)
                        {
                            $index = MAILUSERS_ITTHINX_GROUPS_PREFIX . '-' . strtolower($key); ?>
                            <option value="<?php echo $index; ?>"
                            <?php echo (in_array($index, $send_targets) ? ' selected="yes"' : '');?>>
                            <?php printf('%s - %s', $prefix, __($value)); ?>
                            </option>
                            <?php
                        }
                    }                
                    


                    //  Is the PMPro plugin active?
                    if (class_exists(MAILUSERS_PMPRO_CLASS))
                    {
                        $prefix = __('PMPro', MAILUSERS_I18N_DOMAIN) ;
                        $targets = mailusers_get_membership_levels($user_ID, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META);

                        foreach ($targets as $key => $value)
                        {
                            $index = MAILUSERS_PMPRO_PREFIX . '-' . strtolower($key); ?>
                            <option value="<?php echo $index; ?>"
                            <?php echo (in_array($index, $send_targets) ? ' selected="yes"' : '');?>>
                            <?php printf('%s - %s', $prefix, __($value)); ?>
                            </option>
                            <?php
                        }
                    }              
                    ?>
				</select>
			</td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="subject"><?php _e('Subject', MAILUSERS_I18N_DOMAIN); ?></label></th>
			<td><input type="text" id="subject" name="subject" value="<?php echo format_to_edit($subject);?>" style="width: 647px;" /></td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="mailcontent"><?php _e('Message', MAILUSERS_I18N_DOMAIN); ?></label></th>
			<td>
				<div id="mail-content-editor" style="width: 647px;">
				<?php
					if ($mail_format=='html') {
						//the_editor(stripslashes($mail_content), "mailcontent", "subject", true);
						wp_editor(stripslashes($mail_content), "mailcontent");
					} else {
				?>
					<textarea rows="10" cols="80" name="mailcontent" id="mailcontent" style="width: 647px;"><?php echo stripslashes($mail_content);?></textarea>
				<?php 
					}
				?>
				</div>
			</td>
		</tr>
		</table>

		<p class="submit">
			<input class="button-primary" type="submit" name="Submit" value="<?php _e('Send Email', MAILUSERS_I18N_DOMAIN); ?> &raquo;" />
		</p>
	</form>
</div>
