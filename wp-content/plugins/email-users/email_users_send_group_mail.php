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
	global $user_identity, $user_email, $user_ID, $mailusers_send_to_group_mode;

    // Update Custom Meta Filters
    do_action('mailusers_update_custom_meta_filters') ;

	$err_msg = '';
	wp_get_current_user();

	$from_sender = 0;
    $from_address = empty($user_email) ? get_bloginfo('email') : $user_email;
    $from_name = empty($user_identity) ? get_bloginfo('name') : $user_identity;


	// Send the email if it has been requested
	if (array_key_exists('send', $_POST) && $_POST['send']=='true') {
        if (! isset( $_POST['mailusers_send_to_group_nonce'] ) 
            || ! wp_verify_nonce( $_POST['mailusers_send_to_group_nonce'], 'mailusers_send_to_group' ) ) {

            wp_die(printf('<div class="error fade"><p>%s</p></div>',
                __('WordPress nonce failed to verify, requested action terminated.', MAILUSERS_I18N_DOMAIN)));
        }
		// No error and nonce ok, send the mail

	    // Use current user info only if from name and address has not been set by the form
	    if (!isset($_POST['fromName']) || !isset($_POST['fromAddress']) || empty($_POST['fromName']) || empty($_POST['fromAddress'])) {
	        $from_name = empty($user_identity) ? get_bloginfo('name') : $user_identity;
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
	
		if ( !isset($_POST['send_targets']) || !is_array($_POST['send_targets']) || empty($_POST['send_targets']) ) {
			$err_msg = $err_msg . __('You must select at least a role.', MAILUSERS_I18N_DOMAIN) . '<br/>';
		} else {
			$send_targets = $_POST['send_targets'];
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
		
		if ( !isset( $_POST['group_mode'] ) || trim($_POST['group_mode'])=='' ) {
			$group_mode = $mailusers_send_to_group_mode;
		} else {
			$group_mode = $_POST['group_mode'];
		}
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

	if (!isset($group_mode)) {
		$group_mode = $mailusers_send_to_group_mode;
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

        $recipients = array() ;

        $send_ug = array() ;
        $send_filters = array() ;
        $send_uam = array() ;
        $send_groups = array() ;

        //  Loop through the various types of potential recipients
        //  and extract the 
        foreach ($send_targets as $target)
        {
            //  Decompose the target value so we know what we're dealing with
            list($key, $value) = explode('-', $target, 2) ;

            //  Once known, put the target value in the proper pile
            switch ($key)
            {
                case MAILUSERS_CM_FILTER_PREFIX:
                    $send_filters[] = $value ;
                    break ;

                case MAILUSERS_USERS_GROUPS_PREFIX:
                    $send_ug[] = $value ;
                    break ;

                case MAILUSERS_USER_ACCESS_MANAGER_PREFIX:
                    $send_uam[] = $value ;
                    break ;

                case MAILUSERS_ITTHINX_GROUPS_PREFIX:
                    $send_groups[] = $value ;
                    break ;

                case MAILUSERS_PMPRO_PREFIX:
                    $send_pmpro[] = $value ;
                    break ;

                default:
                    $send_roles[] = $value ;
                    break ;
            }
        }

        //  Extract the recipinents from the various target sources
        $recipients = array() ;

        if (!empty($send_filters))
            $recipients = array_merge($recipients, mailusers_get_recipients_from_custom_meta_filters($send_filters, $exclude_id, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META));

        if (class_exists(MAILUSERS_USER_GROUPS_CLASS) && !empty($send_ug))
            $recipients = array_merge($recipients, mailusers_get_recipients_from_user_groups($send_ug, $exclude_id, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META));

        if (class_exists(MAILUSERS_USER_ACCESS_MANAGER_CLASS) && !empty($send_uam))
            $recipients = array_merge($recipients, mailusers_get_recipients_from_uam_group($send_uam, $exclude_id, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META));

        if (class_exists(MAILUSERS_ITTHINX_GROUPS_CLASS) && !empty($send_groups))
            $recipients = array_merge($recipients, mailusers_get_recipients_from_itthinx_groups_group($send_groups, $exclude_id, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META));

        if (class_exists(MAILUSERS_PMPRO_CLASS) && !empty($send_pmpro))
            $recipients = array_merge($recipients, mailusers_get_recipients_from_membership_levels($send_pmpro, $exclude_id, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META));

        if (!empty($send_roles))
            $recipients = array_merge($recipients, mailusers_get_recipients_from_roles($send_roles, $exclude_id, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META));

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
			include('email_users_group_mail_form.php');
		}
	?>
	</div>

<?php
	} else {
		// Redirect to the form page
		include('email_users_group_mail_form.php');
	}
?>
