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
	global $user_identity, $user_email, $user_ID;
    global $mailusers_mf, $mailusers_mv, $mailusers_mc;

	if (!current_user_can(MAILUSERS_EMAIL_SINGLE_USER_CAP)
		&& 	!current_user_can(MAILUSERS_EMAIL_MULTIPLE_USERS_CAP)) {
        wp_die(printf('<div class="error fade"><p>%s</p></div>',
            __('You are not allowed to send emails to users.', MAILUSERS_I18N_DOMAIN)));
	}

	if (!isset($send_users)) {
		$send_users = array();
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

	wp_get_current_user();

	$from_name = $user_identity;
	$from_address = $user_email;
    $override_name = mailusers_get_from_sender_name_override() ;
    $override_address = mailusers_get_from_sender_address_override() ;
    
?>
<div class="wrap">
	<div id="icon-users" class="icon32"><br/></div>
	<h2><?php _e('Send an Email to a Custom Filtered list of Users', MAILUSERS_I18N_DOMAIN); ?></h2>

	<?php 	if (isset($err_msg) && $err_msg!='') { ?>
			<div class="error fade"><p><?php echo $err_msg; ?></p></div>
			<p><?php _e('Please correct the errors displayed above and try again.', MAILUSERS_I18N_DOMAIN); ?></p>
	<?php	} ?>

	<form name="SendEmail" action="" method="post">
		<input type="hidden" name="send" value="true" />
		<input type="hidden" name="fromName" value="<?php echo $from_name;?>" />
		<input type="hidden" name="fromAddress" value="<?php echo $from_address;?>" />

		<table class="form-table" width="100%" cellspacing="2" cellpadding="5">
		<tr>
			<th scope="row" valign="top"><?php _e('Mail format', MAILUSERS_I18N_DOMAIN); ?></th>
			<td><select class="mailusers-select" name="mail_format" style="width: 158px;">
				<option value="html" <?php if ($mail_format=='html') echo 'selected="selected"'; ?>><?php _e('HTML', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="plaintext" <?php if ($mail_format=='plaintext') echo 'selected="selected"'; ?>><?php _e('Plain text', MAILUSERS_I18N_DOMAIN); ?></option>
			</select></td>
		</tr>
		<tr>
			<th scope="row" valign="top"><label for="fromName"><?php _e('Sender', MAILUSERS_I18N_DOMAIN); ?></label></th>
            <?php if (empty($override_address)) { ?>
			<td><?php echo $from_name;?> &lt;<?php echo $from_address;?>&gt;</td>
            <?php } else { ?>
            <td><input name="from_sender" type="radio" value="0" checked/><?php echo $from_name;?> &lt;<?php echo $from_address;?>&gt;<br/><input name="from_sender" type="radio" value="1"/><?php echo $override_name;?> &lt;<?php echo $override_address;?>&gt;</td>
            <?php }?>
        </tr>
        <tr>
			<th scope="row" valign="top"><label for="send_users"><?php _e('Recipients', MAILUSERS_I18N_DOMAIN); ?>
			<br/><br/>
			<small><?php
				if (!current_user_can(MAILUSERS_EMAIL_MULTIPLE_USERS_CAP))
					_e('You are only allowed to select one user at a time.', MAILUSERS_I18N_DOMAIN);
				else
					_e('You can select multiple users by pressing the CTRL key.  When selecting multiple users, any user who should not receive Mass Email will be filtered from the recipient list.', MAILUSERS_I18N_DOMAIN);
				?>
			</small></label></th>
			<td>
				<select data-placeholder="<?php _e('Choose Recipients ...', MAILUSERS_I18N_DOMAIN);?>" class="mailusers-select" id="send_users" name="send_users[]" size="8" style="width: 654px; height: 250px;" <?php if (current_user_can(MAILUSERS_EMAIL_MULTIPLE_USERS_CAP)) echo 'multiple="multiple"'; ?> >
				<?php
					//  Display of users is based on plugin setting
					$na = __('N/A', MAILUSERS_I18N_DOMAIN);
					$sortby = mailusers_get_default_sort_users_by();
	
                    $users = mailusers_get_recipients_from_custom_meta_filter(null, '', $mailusers_mf, $mailusers_mv, $mailusers_mc);
                    
					foreach ($users as $user) {
						switch ($sortby) {
							case 'fl' :  //  First Last
								$name = sprintf('%s %s',
									is_null($user->first_name) ? $na : $user->first_name,
									is_null($user->last_name) ? $na : $user->last_name);
								break;

							case 'flul' :  //  First Last User Login
								$name = sprintf('%s %s (%s)',
									is_null($user->first_name) ? $na : $user->first_name,
									is_null($user->last_name) ? $na : $user->last_name,
									$user->user_login);
								break;

							case 'lf' :
								$name = sprintf('%s, %s',
									is_null($user->last_name) ? $na : $user->last_name,
									is_null($user->first_name) ? $na : $user->first_name);
								break;

							case 'lful' :
								$name = sprintf('%s, %s (%s)',
									is_null($user->last_name) ? $na : $user->last_name,
									is_null($user->first_name) ? $na : $user->first_name,
									$user->user_login);
								break;

							case 'ul' :
								$name = sprintf('%s', $user->user_login);
								break;

							case 'uldn' :
								$name = sprintf('%s (%s)',
									$user->user_login, $user->display_name);
								break;

							case 'ulfl' :
								$name = sprintf('%s (%s %s)', $user->user_login,
									is_null($user->first_name) ? $na : $user->first_name,
									is_null($user->last_name) ? $na : $user->last_name);
								break;

							case 'ullf' :
								$name = sprintf('%s (%s, %s)', $user->user_login,
									is_null($user->last_name) ? $na : $user->last_name,
									is_null($user->first_name) ? $na : $user->first_name);
								break;

							case 'dnul' :
								$name = sprintf('%s (%s)',
									$user->display_name, $user->user_login);
								break;

							case 'dn' :
							case 'none' :
							default:
								$name = $user->display_name;
								break;
						}
				?>
					<option value="<?php echo $user->ID; ?>" <?php
						echo (in_array($user->ID, $send_users) ? ' selected="yes"' : '');?>>
						<?php printf('%s - %s', __('User', MAILUSERS_I18N_DOMAIN), $name); ?>
					</option>
				<?php
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
