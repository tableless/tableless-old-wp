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
	if (!current_user_can(MAILUSERS_EMAIL_SINGLE_USER_CAP)
		&& 	!current_user_can(MAILUSERS_EMAIL_MULTIPLE_USERS_CAP)) {	
        wp_die(printf('<div class="error fade"><p>%s</p></div>',
            __('You are not allowed to send emails to users.', MAILUSERS_I18N_DOMAIN)));
	} 
?>


<?php
        //printf('<div class="error nag"><p>Max Input Vars:  %s</p></div>', ini_get('max_input_vars')) ;
        //printf('<div class="updated nag" style="border-left: 4px solid #89deee;"><p>Max Input Vars:  %s</p></div>', ini_get('max_input_vars')) ;
//error_log(print_r($_POST, true)) ;
	global $user_identity, $user_email, $user_ID;

	$err_msg = '';
	wp_get_current_user();

	$from_sender = 0;
    $from_address = empty($user_email) ? get_bloginfo('email') : $user_email;
    $from_name = empty($user_identity) ? get_bloginfo('name') : $user_identity;

	
	// Send the email if it has been requested
	if (array_key_exists('send', $_POST) && $_POST['send']=='true') {
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

    // Replace the template variables concerning the blog and sender details
    // --

    $subject = mailusers_replace_sender_templates($subject, $from_name);
    $mail_content = mailusers_replace_sender_templates($mail_content, $from_name);
    $subject = mailusers_replace_blog_templates($subject);
    $mail_content = mailusers_replace_blog_templates($mail_content);

	// If error, we simply show the form again
	if (array_key_exists('send', $_POST) && ($_POST['send']=='true') && ($err_msg == '')) {
        //  Verify WordPress nonce before proceeding ...
        if (! isset( $_POST['mailusers_send_to_user_nonce'] ) 
            || ! wp_verify_nonce( $_POST['mailusers_send_to_user_nonce'], 'mailusers_send_to_user' ) ) {

            wp_die(printf('<div class="error fade"><p>%s</p></div>',
                __('WordPress nonce failed to verify, requested action terminated.', MAILUSERS_I18N_DOMAIN)));
        }
		// No error and nonce ok, send the mail
		
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

        //  Don't want to spam people so if more than one user was selected,
        //  drop all of the users who don't want to receive Mass Email!

        if (count($send_users) == 1) {
		    $recipients = mailusers_get_recipients_from_ids($send_users, $exclude_id);
            $filtered_recipients_message = '';
        }
        else {
		    $recipients = mailusers_get_recipients_from_ids($send_users, $exclude_id, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META);
            $filtered_recipients_message = sprintf(__('<br/>%d users who should not to receive Mass Email were filtered from the recipient list.', MAILUSERS_I18N_DOMAIN), count($send_users) - count($recipients));
        }
        

		if (empty($recipients)) {
	?>
			<div class="error fade"><p><strong><?php _e('No recipients were found.', MAILUSERS_I18N_DOMAIN) . $filtered_recipients_message ; ?></strong></p></div>
	<?php
		    include('email_users_user_mail_form.php');
		} else {
            $useheader = mailusers_get_header_usage() != 'notification' ;
            $usefooter = mailusers_get_footer_usage() != 'notification' ;
			$num_sent = mailusers_send_mail($recipients, $subject, $mail_content, $mail_format, $from_name, $from_address, $useheader, $usefooter);
			if (false === $num_sent) {
                echo '<div class="error fade"><p><strong>' . __('There was a problem trying to send email to users.', MAILUSERS_I18N_DOMAIN) . $filtered_recipients_message . '</strong></p></div>';
			} else if (0 === $num_sent) {
                echo '<div class="error fade"><p><strong>' . __('No email has been sent to other users. This may be because no valid email addresses were found.', MAILUSERS_I18N_DOMAIN) . $filtered_recipients_message . '</strong></p></div>';
			} else if ($num_sent > 0 && $num_sent == count($recipients)){
	?>
			<div class="updated fade">
				<p><strong><?php echo sprintf(__('Email sent to %s user(s).', MAILUSERS_I18N_DOMAIN), $num_sent) . $filtered_recipients_message; ?></strong></p>
			</div>
	<?php
			} else if ($num_sent > count($recipients)) {
                echo '<div class="error fade"><p><strong>' . __('WARNING: More email has been sent than the number of recipients found.', MAILUSERS_I18N_DOMAIN) . '</strong></p></div>';
			} else {
				echo '<div class="updated fade"><p><strong>' . sprintf(__('Email has been sent to %d users, but %d recipients were originally found. Perhaps some users don\'t have valid email addresses?', MAILUSERS_I18N_DOMAIN), $num_sent, count($recipients)) . $filtered_recipients_message . '</strong></p></div>';
			}
			include('email_users_user_mail_form.php');
		}
	?>
	</div>
	
<?php
	} else {
		include('email_users_user_mail_form.php');
		// No error, send the mail
	}
?>
