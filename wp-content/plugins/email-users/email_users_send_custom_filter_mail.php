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
	if (!current_user_can(MAILUSERS_EMAIL_USER_GROUPS_CAP)) {
        wp_die(printf('<div class="error fade"><p>%s</p></div>',
            __('You are not allowed to send emails to user groups.', MAILUSERS_I18N_DOMAIN)));
	}
?>

<?php
	global $user_identity, $user_email, $user_ID;
    global $mailusers_mf, $mailusers_mv, $mailusers_mc;

	$err_msg = '';
	$from_sender = 0;

	// Send the email if it has been requested
		if (array_key_exists('send', $_POST) && $_POST['send']=='true') {
			wp_get_current_user();
		// Use current user info only if from name and address has not been set by the form
		if (!isset($_POST['fromName']) || !isset($_POST['fromAddress']) || empty($_POST['fromName']) || empty($_POST['fromAddress'])) {
			$from_name = $user_identity;
			$from_address = $user_email;
		} else {
			$from_name = $_POST['fromName'];
			$from_address = $_POST['fromAddress'];
		}
	    $override_name = mailusers_get_from_sender_name_override() ;
        $override_address = mailusers_get_from_sender_address_override() ;
        $exclude_sender = mailusers_get_from_sender_exclude() ;
        $exclude_id = ($exclude_sender) ? '' : $user_ID ;
	
		// Analyse form input, check for blank fields
		if ( !isset( $_POST['mail_format'] ) || trim($_POST['mail_format'])=='' ) {
			$err_msg = $err_msg . __('You must specify the mail format.', MAILUSERS_I18N_DOMAIN) . '<br/>';
		} else {
			$mail_format = $_POST['mail_format'];
		}
	
		if ( !isset($_POST['send_users']) || !is_array($_POST['send_users']) || empty($_POST['send_users']) ) {
			$err_msg = $err_msg . __('You must enter at least a recipient.', MAILUSERS_I18N_DOMAIN) . '<br/>';
		} else {
			$send_users = $_POST['send_users'];
		}
		
        /*
		if ( !isset($_POST['send_roles']) || !is_array($_POST['send_roles']) || empty($_POST['send_roles']) ) {
			$err_msg = $err_msg . __('You must select at least a role.', MAILUSERS_I18N_DOMAIN) . '<br/>';
		} else {
			$send_roles = $_POST['send_roles'];
		}
         */
        		
		if ( !isset( $_POST['subject'] ) || trim($_POST['subject'])=='' ) {
			$err_msg = $err_msg . __('You must enter a subject.', MAILUSERS_I18N_DOMAIN) . '<br/>';
		} else {
			$subject = $_POST['subject'];
		}
	
		if ( !isset( $_POST['mailcontent'] ) || trim($_POST['mailcontent'])=='' ) {
			$err_msg = $err_msg . __('You must enter some content.', MAILUSERS_I18N_DOMAIN) . '<br/>';
		} else {
			$mail_content = $_POST['mailcontent'];
		}
		
		if ( !isset( $_POST['from_sender'] ) || trim($_POST['from_sender'])=='' ) {
			$from_sender = 0;
		} else {
			$from_sender = $_POST['from_sender'];
		}
	}
	if (!isset($send_roles)) {
		$send_roles = array();
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

    //  Override the send from address?
    if (($from_sender == 1) && !empty($override_address) && is_email($override_address)) {
        $from_address = $override_address ;
        if (!empty($override_name)) $from_name = $override_name ;
    }

	// If error, we simply show the form again
	if (array_key_exists('send', $_POST) && ($_POST['send']=='true') && ($err_msg == '')) {
		// No error, send the mail
		
		// Do some HTML homework if needed
		//--
		if ($mail_format=='html') {
			$mail_content = wpautop($mail_content);
		}		
	?>
	<div class="wrap">
	<?php
		// Fetch users
		// --
		//$recipients = mailusers_get_recipients_from_roles($send_roles, $exclude_id, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META);

        if (!empty($send_users)) {
		    $recipients = mailusers_get_recipients_from_custom_meta_filter($send_users, $exclude_id, $mailusers_mf, $mailusers_mv, $mailusers_mc);
        }
        else if (!empty($send_roles)) {
		    $recipients = mailusers_get_recipients_from_custom_meta_filter($send_roles, $exclude_id, $mailusers_mf, $mailusers_mv, $mailusers_mc);
        }
        else {
            $recipients = array() ;
        }

		if (empty($recipients)) {
	?>
			<p><strong><?php _e('No recipients were found.', MAILUSERS_I18N_DOMAIN); ?></strong></p>
	<?php
		} else {
            $useheader = mailusers_get_header_usage() != 'notification' ;
            $usefooter = mailusers_get_footer_usage() != 'notification' ;

			$num_sent = mailusers_send_mail($recipients, $subject, $mail_content, $mail_format, $from_name, $from_address, $useheader, $usefooter);
			if (false === $num_sent) {
				print '<div class="error fade"><p> ' . __('There was a problem trying to send email to users.', MAILUSERS_I18N_DOMAIN) . '</p></div>';

			} else if (0 === $num_sent) {
				print '<div class="error fade"><p>' . __('No email has been sent to other users. This may be because no valid email addresses were found.', MAILUSERS_I18N_DOMAIN) . '</p></div>';
			} else if ($num_sent > 0 && $num_sent == count($recipients)){
	?>
			<div class="updated fade">
				<p><?php echo sprintf(__('Email sent to %s user(s).', MAILUSERS_I18N_DOMAIN), $num_sent); ?></p>
			</div>
	<?php
			} else if ($num_sent > count($recipients)) {
				print '<div class="error fade"><p>' . __('WARNING: More email has been sent than the number of recipients found.', MAILUSERS_I18N_DOMAIN) . '</p></div>';
			} else {
				echo '<div class="updated fade"><p>' . sprintf(__('Email has been sent to %s users, but %s recipients were originally found. Perhaps some users don\'t have valid email addresses?', MAILUSERS_I18N_DOMAIN), $num_sent, count($recipients)) . '</p></div>';
			}
			include('email_users_custom_filter_mail_form.php');
		}
	?>
	</div>

<?php
	} else {
		// Redirect to the form page
		include('email_users_custom_filter_mail_form.php');
	}
?>
