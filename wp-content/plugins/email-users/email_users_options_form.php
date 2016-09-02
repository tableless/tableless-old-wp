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
	wp_enqueue_script('postbox');
    wp_enqueue_script('dashboard');
    wp_enqueue_style('dashboard');

	if (!current_user_can('manage_options')) {
        wp_die(printf('<div class="error fade"><p>%s</p></div>',
            __('You are not allowed to change the options of this plugin.', MAILUSERS_I18N_DOMAIN)));

	} 
	
	if ( mailusers_get_installed_version() != mailusers_get_current_version() ) {
?>
	<div class="error fade">
		<p><?php _e('It looks like you have an old version of the plugin activated. Please deactivate the plugin and activate it again to complete the installation of the new version.', MAILUSERS_I18N_DOMAIN); ?>
	</p>		
	<p>
		<?php _e('Installed Version:', MAILUSERS_I18N_DOMAIN); ?> <?php echo mailusers_get_installed_version(); ?> <br/>
		<?php _e('Current Version:', MAILUSERS_I18N_DOMAIN); ?> <?php echo mailusers_get_current_version(); ?>
	</p>
	</div>		
<?php
	}

    $bounce = mailusers_get_send_bounces_to_address_override() ;
    if (!empty($bounce)) {
?>
    <div class="update-nag fade">
    <p><?php printf(__('Setting a bounce email address is no longer recommended due to it casuing delivery problems with some servers and unreliabilty.<br />If you experience delivery problems, please remove the current setting:  %s', MAILUSERS_I18N_DOMAIN), $bounce) ; ?></p>
    </div>
<?php
    }

    //  Check to see if wp_mail() has been overloaded

    if (class_exists('ReflectionFunction'))
    {
        $wp_mail = new ReflectionFunction('wp_mail') ;
        $actual = realpath($wp_mail->getFileName()) ;
        $expected = realpath(sprintf('%s%swp-includes%spluggable.php', ABSPATH, DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR)) ;

        if ($actual != $expected) 
        {
            printf('<div class="updated fade"><h3>%s</h3></div>', 
                __('Warning:  wp_mail() appears to be overloaded.', MAILUSERS_I18N_DOMAIN)) ;
        }
    }

    //  Check the number of users who accept notifications and mass emails

    $massemails = mailusers_get_users('', MAILUSERS_ACCEPT_MASS_EMAIL_USER_META) ;
    $notifications = mailusers_get_users('', MAILUSERS_ACCEPT_NOTIFICATION_USER_META) ;

    if (count($massemails) == 0 || count($notifications) == 0)
    {
?>
	<div class="updated fade">
    <div class="table table_content">
    <p class="sub"><?php _e('Current settings show post or page notification and/or mass email may not be received by any users.', MAILUSERS_I18N_DOMAIN); ?></p>
    <table style="text-align: left; width: auto;">
   	<tr>
    <th><?php _e('Number of Users who accept post or page notification emails:', MAILUSERS_I18N_DOMAIN); ?></th>
	<td<?php if ( count($notifications) == 0) echo ' style="color: red;"' ; ?>><?php echo count($notifications) ; ?></td>
	</tr>
   	<tr>
    <th><?php _e('Number of Users who accept emails sent to multiple recipients:', MAILUSERS_I18N_DOMAIN); ?></th>
	<td<?php if ( count($massemails) == 0) echo ' style="color: red;"' ; ?>><?php echo count($massemails) ; ?></td>
	</tr>
<tr>
<td colspan="2">
<?php
$reflection = new ReflectionFunction( 'wp_mail' );
print $reflection->getFileName();
?>        
</td>
</tr>
	</table>
    </div>
    </div>
<?php
    }

?>

<div class="wrap"><!-- wrap -->


<?php if (function_exists('screen_icon')) screen_icon(); ?>
<h2><?php _e('Email Users Settings', MAILUSERS_I18N_DOMAIN); ?></h2>

<?php 	
	if (isset($err_msg) && $err_msg!='') { ?>
		<div class="error fade"><p><?php echo $err_msg;?></p></div>
		<p><?php _e('Please correct the errors displayed above and try again.', MAILUSERS_I18N_DOMAIN); ?></p>
<?php	
	} ?>

<div> <!-- Postbox Containers -->
<div class="postbox-container" style="width:65%; border: 0px dashed blue;"><!-- 65% Postbox Container -->
<div class="metabox-holder">
<div class="meta-box-sortables">
<div id="email-users-plugin-settings" class="postbox email-users-postbox">
<div class="handlediv" title="Click to toggle"><br /></div>
<h3 class="hndle"><span><?php _e('Email-Users Plugin Settings', MAILUSERS_I18N_DOMAIN);?></span></h3>
<div class="inside">

<form name="EmailUsersOptions" action="options.php" method="post">		
	<?php settings_fields('email_users') ;?>
    <?php wp_nonce_field( 'mailusers_plugin_settings', 'mailusers_plugin_settings_nonce' ); ?>
	<input type="hidden" name="mailusers_version" value="<?php echo mailusers_get_current_version(); ?>" />
	<table class="form-table" style="clear:none;" width="100%" cellspacing="2" cellpadding="5">
	<tr>
		<th scope="row" valign="top">
			<label for="mail_format"><?php _e('Mail Format', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<select class="mailusers-select" name="mailusers_default_mail_format" style="width: 235px;">
				<option value="html" <?php if (mailusers_get_default_mail_format()=='html') echo 'selected="true"';?>><?php _e('HTML', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="plaintext" <?php if (mailusers_get_default_mail_format()=='plaintext') echo 'selected="true"';?>><?php _e('Plain text', MAILUSERS_I18N_DOMAIN); ?></option>
			</select><br/><i><small><?php _e('Send mail as plain text or HTML by default?', MAILUSERS_I18N_DOMAIN); ?></small></i></td>
	</tr>
	<tr>
		<th scope="row" valign="top">
			<label for="sort_users_by"><?php _e('Sort Users By', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<select class="mailusers-select" name="mailusers_default_sort_users_by" style="width: 235px;">
				<option value="none" <?php if (mailusers_get_default_sort_users_by()=='none') echo 'selected="true"';?>><?php _e('None', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="dn" <?php if (mailusers_get_default_sort_users_by()=='dn') echo 'selected="true"';?>><?php _e('Display Name', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="dnul" <?php if (mailusers_get_default_sort_users_by()=='dnul') echo 'selected="true"';?>><?php _e('Display Name (User Login)', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="dnue" <?php if (mailusers_get_default_sort_users_by()=='dnue') echo 'selected="true"';?>><?php _e('Display Name (User Email)', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="fl" <?php if (mailusers_get_default_sort_users_by()=='fl') echo 'selected="true"';?>><?php _e('First Name Last Name', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="flul" <?php if (mailusers_get_default_sort_users_by()=='flul') echo 'selected="true"';?>><?php _e('First Name Last Name (User Login)', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="flue" <?php if (mailusers_get_default_sort_users_by()=='flue') echo 'selected="true"';?>><?php _e('First Name Last Name (User Email)', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="lf" <?php if (mailusers_get_default_sort_users_by()=='lf') echo 'selected="true"';?>><?php _e('Last Name, First Name', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="lful" <?php if (mailusers_get_default_sort_users_by()=='lful') echo 'selected="true"';?>><?php _e('Last Name, First Name (User Login)', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="lfue" <?php if (mailusers_get_default_sort_users_by()=='lfue') echo 'selected="true"';?>><?php _e('Last Name, First Name (User Email)', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="ul" <?php if (mailusers_get_default_sort_users_by()=='ul') echo 'selected="true"';?>><?php _e('User Login', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="ue" <?php if (mailusers_get_default_sort_users_by()=='ue') echo 'selected="true"';?>><?php _e('User Email', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="uldn" <?php if (mailusers_get_default_sort_users_by()=='uldn') echo 'selected="true"';?>><?php _e('User Login (Display Name)', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="uedn" <?php if (mailusers_get_default_sort_users_by()=='uedn') echo 'selected="true"';?>><?php _e('User Email (Display Name)', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="ulfl" <?php if (mailusers_get_default_sort_users_by()=='ulfl') echo 'selected="true"';?>><?php _e('User Login (First Name Last Name)', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="uefl" <?php if (mailusers_get_default_sort_users_by()=='uefl') echo 'selected="true"';?>><?php _e('User Email (First Name Last Name)', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="ullf" <?php if (mailusers_get_default_sort_users_by()=='ullf') echo 'selected="true"';?>><?php _e('User Login (Last Name, First Name)', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="uelf" <?php if (mailusers_get_default_sort_users_by()=='uelf') echo 'selected="true"';?>><?php _e('User Email (Last Name, First Name)', MAILUSERS_I18N_DOMAIN); ?></option>
			</select><br/><i><small><?php _e('Determine how to sort and display names in the User selection list?', MAILUSERS_I18N_DOMAIN); ?></small></i></td>
	</tr>
	<tr>
		<th scope="row" valign="top">
			<label for="max_bcc_recipients"><?php _e('BCC Limit', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<select class="mailusers-select" name="mailusers_max_bcc_recipients" style="width: 235px;">
				<option value="0" <?php if (mailusers_get_max_bcc_recipients()=='0') echo 'selected="true"';?>><?php _e('None', MAILUSERS_I18N_DOMAIN); ?></option>
                <option value="-1" <?php if (mailusers_get_max_bcc_recipients()=='-1') echo 'selected="true"';?>><?php _e('1 (use To: field)', MAILUSERS_I18N_DOMAIN);?></option>
				<option value="1" <?php if (mailusers_get_max_bcc_recipients()=='1') echo 'selected="true"';?>><?php _e('1 (use Bcc: field)', MAILUSERS_I18N_DOMAIN);?></option>
                <option value="2" <?php if (mailusers_get_max_bcc_recipients()=='2') echo 'selected="true"';?>><?php _e('2', MAILUSERS_I18N_DOMAIN);?></option>
                <option value="3" <?php if (mailusers_get_max_bcc_recipients()=='3') echo 'selected="true"';?>><?php _e('3', MAILUSERS_I18N_DOMAIN);?></option>
                <option value="5" <?php if (mailusers_get_max_bcc_recipients()=='5') echo 'selected="true"';?>><?php _e('5', MAILUSERS_I18N_DOMAIN);?></option>
                <option value="10" <?php if (mailusers_get_max_bcc_recipients()=='10') echo 'selected="true"';?>><?php _e('10', MAILUSERS_I18N_DOMAIN);?></option>
                <option value="30" <?php if (mailusers_get_max_bcc_recipients()=='30') echo 'selected="true"';?>><?php _e('30', MAILUSERS_I18N_DOMAIN);?></option>
                <option value="100" <?php if (mailusers_get_max_bcc_recipients()=='100') echo 'selected="true"';?>><?php _e('100', MAILUSERS_I18N_DOMAIN);?></option>
                <option value="250" <?php if (mailusers_get_max_bcc_recipients()=='250') echo 'selected="true"';?>><?php _e('250', MAILUSERS_I18N_DOMAIN);?></option>
                <option value="500" <?php if (mailusers_get_max_bcc_recipients()=='500') echo 'selected="true"';?>><?php _e('500', MAILUSERS_I18N_DOMAIN);?></option>
                <option value="1000" <?php if (mailusers_get_max_bcc_recipients()=='1000') echo 'selected="true"';?>><?php _e('1000', MAILUSERS_I18N_DOMAIN);?></option>
			</select><br/><i><small><?php _e('Try 30 if you have problems sending emails to many users (some providers forbid too many recipients in BCC field).', MAILUSERS_I18N_DOMAIN); ?></i></small>
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top">
            <label for="default_subject"><?php _e('Default<br/>Notification Subject', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input type="text" name="mailusers_default_subject" style="width: 100%;" 
				value="<?php echo format_to_edit(mailusers_get_default_subject()); ?>" 
				size="80" /></td>
	</tr>
	<tr>
        <th><?php _e('Enhanced Recipient Selection', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input 	type="checkbox" name="mailusers_enhanced_recipient_selection" id="mailusers_enhanced_recipient_selection" value="true"
					<?php if (mailusers_get_enhanced_recipient_selection()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Use jQuery enhanced Select boxes for recipient selection.', MAILUSERS_I18N_DOMAIN); ?><br/>
            <i><small><?php _e('When enabled the <a href="http://harvesthq.github.io/chosen/">Chosen jQuery plugin</a> is applied to recipient selection lists.', MAILUSERS_I18N_DOMAIN)?></small></i>
		</td>
	</tr>
	<tr>
        <th><?php _e('Display Names', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input 	type="checkbox" name="mailusers_omit_display_names" id="mailusers_omit_display_names" value="true"
					<?php if (mailusers_get_omit_display_names()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Omit Display Names when sending email.', MAILUSERS_I18N_DOMAIN); ?><br/>
            <i><small><?php _e('Use "john.doe@example.com" instead of "John Doe &lt;john.doe@example.com&gt;"', MAILUSERS_I18N_DOMAIN)?></small></i>
		</td>
	</tr>
	<tr>
        <th><?php _e('Copy Sender', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input 	type="checkbox" name="mailusers_copy_sender" id="mailusers_copy_sender" value="true"
					<?php if (mailusers_get_copy_sender()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Copy sender (add sender email to Cc: header) when sending email.', MAILUSERS_I18N_DOMAIN); ?><br/>
		</td>
	</tr>
	<tr>
        <th><?php _e('From Sender<br/>Exclude', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input 	type="checkbox" name="mailusers_from_sender_exclude" id="mailusers_from_sender_exclude" value="true"
					<?php if (mailusers_get_from_sender_exclude()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Exclude sender from email recipient list.', MAILUSERS_I18N_DOMAIN); ?><br/>
		</td>
	</tr>
	<tr>
 		<th scope="row" valign="top">
            <label for="from_sender_name_override"><?php _e('From Sender<br/>Name Override', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input type="text" name="mailusers_from_sender_name_override" style="width: 235px;" 
				value="<?php echo format_to_edit(mailusers_get_from_sender_name_override()); ?>" 
				size="80" id="from_sender_name_override"/><br/><i><small><?php _e('A name that can be used in place of the logged in user\'s name when sending email or notifications.', MAILUSERS_I18N_DOMAIN); ?></small></i></td>
	</tr>
	<tr>
		<th scope="row" valign="top">
            <label for="from_sender_address_override"><?php _e('From Sender Email<br/>Address Override', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input type="text" name="mailusers_from_sender_address_override" style="width: 235px;" 
				value="<?php echo format_to_edit(mailusers_get_from_sender_address_override()); ?>" 
                size="80" id="from_sender_address_override"/><br/><div style="width: 90%;"><i><small><?php _e('An email address that can be used in place of the logged in user\'s email address when sending email or notifications.', MAILUSERS_I18N_DOMAIN); ?><br /><b><i><?php _e('Note:  Invalid email addresses are not saved.', MAILUSERS_I18N_DOMAIN); ?></i></b></small></i></div></td>
	</tr>
	<tr>
		<th scope="row" valign="top">
            <label for="send_bounces_to_address_override"><?php _e('Send Bounces To Email<br/>Address Override', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
        <input placeholder="<?php _e('Not recommended, potential delivery issues.', MAILUSERS_I18N_DOMAIN) ?>" type="text" name="mailusers_send_bounces_to_address_override" style="width: 235px;" 
				value="<?php echo format_to_edit(mailusers_get_send_bounces_to_address_override()); ?>" 
                size="80" id="from_sender_address_override"/><br/><div style="width: 90%;"><i><small><?php _e('An email address that can be used in place of the logged in user\'s email address to receive bounced email notifications.', MAILUSERS_I18N_DOMAIN); ?><br /><b><i><?php _e('Note:  Invalid email addresses are not saved.', MAILUSERS_I18N_DOMAIN); ?></i></b></small></i></div></td>
	</tr>
	<tr>
		<th scope="row" valign="top">
            <label for="mailusers_default_body"><?php _e('Default<br/>Notification Body', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
            <?php wp_editor(stripslashes(mailusers_get_default_body()), "mailusers_default_body", array('editor_css' => '<style>div.wp-editor-wrap { width: 90%; }</style>'));?>

		</td>
	</tr>
	<tr>
		<th scope="row" valign="top">
			<label for="mailusers_header_usage"><?php _e('Email Header<br/>Usage', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<select class="mailusers-select" name="mailusers_header_usage" style="width: 235px;">
				<option value="notification" <?php if (mailusers_get_header_usage()=='notification') echo 'selected="true"';?>><?php _e('Notification Email Only', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="email" <?php if (mailusers_get_header_usage()=='email') echo 'selected="true"';?>><?php _e('User and Group Email Only', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="both" <?php if (mailusers_get_header_usage()=='both') echo 'selected="true"';?>><?php _e('User, Group and Notification Email', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="none" <?php if (mailusers_get_header_usage()=='none') echo 'selected="true"';?>><?php _e('Not Used', MAILUSERS_I18N_DOMAIN); ?></option>
			</select><br/><i><small><?php _e('Add Email Header Text', MAILUSERS_I18N_DOMAIN); ?></small></i></td>
	</tr>
	<tr>
		<th scope="row" valign="top">
            <label for="mailusers_header"><?php _e('Email Header<br/>Content', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
            <?php wp_editor(stripslashes(mailusers_get_header()), "mailusers_header", array('textarea_rows' => 4));?>
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top">
			<label for="mailusers_footer_usage"><?php _e('Email Footer<br/>Usage', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<select class="mailusers-select" name="mailusers_footer_usage" style="width: 235px;">
				<option value="notification" <?php if (mailusers_get_footer_usage()=='notification') echo 'selected="true"';?>><?php _e('Notification Email Only', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="email" <?php if (mailusers_get_footer_usage()=='email') echo 'selected="true"';?>><?php _e('User and Group Email Only', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="both" <?php if (mailusers_get_footer_usage()=='both') echo 'selected="true"';?>><?php _e('User, Group and Notification Email', MAILUSERS_I18N_DOMAIN); ?></option>
				<option value="none" <?php if (mailusers_get_footer_usage()=='none') echo 'selected="true"';?>><?php _e('Not Used', MAILUSERS_I18N_DOMAIN); ?></option>
			</select><br/><i><small><?php _e('Add Email Footer Text', MAILUSERS_I18N_DOMAIN); ?></small></i></td>
	</tr>
	<tr>
		<th scope="row" valign="top">
            <label for="mailusers_footer"><?php _e('Email Footer<br/>Content', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
            <?php wp_editor(stripslashes(mailusers_get_footer()), "mailusers_footer", array('textarea_rows' => 4));?>
		</td>
	</tr>
	<tr>
        <th><?php _e('Short Code<br/>Processing', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input 	type="checkbox" name="mailusers_shortcode_processing" id="mailusers_shortcode_processing" value="true"
					<?php if (mailusers_get_shortcode_processing()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Process short codes embedded in posts or pages.', MAILUSERS_I18N_DOMAIN); ?><br/>
		</td>
	</tr>
	<tr>
        <th><?php _e('Process Content<br/>with wpautop', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input 	type="checkbox" name="mailusers_wpautop_processing" id="mailusers_wpautop_processing" value="true"
					<?php if (mailusers_get_wpautop_processing()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Changes double line-breaks in the text into HTML paragraphs (&lt;p&gt;...&lt;/p&gt;).', MAILUSERS_I18N_DOMAIN); ?><br/>
		</td>
	</tr>
	<tr>
		<th scope="row" valign="top">
            <label for="user_settings_table_rows"><?php _e('User Settings<br/>Table Rows', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<select class="mailusers-select" name="mailusers_user_settings_table_rows" style="width: 100px;">
                <option value="10" <?php if (mailusers_get_user_settings_table_rows()=='10') echo 'selected="true"'; ?>><?php _e('10', MAILUSERS_I18N_DOMAIN); ?></option>
                <option value="20" <?php if (mailusers_get_user_settings_table_rows()=='20') echo 'selected="true"'; ?>><?php _e('20', MAILUSERS_I18N_DOMAIN); ?></option>
                <option value="40" <?php if (mailusers_get_user_settings_table_rows()=='40') echo 'selected="true"'; ?>><?php _e('40', MAILUSERS_I18N_DOMAIN); ?></option>
                <option value="50" <?php if (mailusers_get_user_settings_table_rows()=='50') echo 'selected="true"'; ?>><?php _e('50', MAILUSERS_I18N_DOMAIN); ?></option>
                <option value="75" <?php if (mailusers_get_user_settings_table_rows()=='75') echo 'selected="true"'; ?>><?php _e('75', MAILUSERS_I18N_DOMAIN); ?></option>
                <option value="100" <?php if (mailusers_get_user_settings_table_rows()=='100') echo 'selected="true"'; ?>><?php _e('100', MAILUSERS_I18N_DOMAIN); ?></option>
                <option value="200" <?php if (mailusers_get_user_settings_table_rows()=='200') echo 'selected="true"'; ?>><?php _e('200', MAILUSERS_I18N_DOMAIN); ?></option>
                <option value="500" <?php if (mailusers_get_user_settings_table_rows()=='500') echo 'selected="true"'; ?>><?php _e('500', MAILUSERS_I18N_DOMAIN); ?></option>
			</select><br/><i><small><?php _e('By default the table will display 20 rows.', MAILUSERS_I18N_DOMAIN); ?></small></i>
		</td>
	</tr>
	<tr>
    <th><?php _e('Default<br/>User Settings', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input 	type="checkbox" name="mailusers_default_notifications" id="mailusers_default_notifications" value="true"
					<?php if (mailusers_get_default_notifications()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Receive post or page notification emails.', MAILUSERS_I18N_DOMAIN); ?><br/>
			<input 	type="checkbox"
					name="mailusers_default_mass_email" id="mailusers_default_mass_email" value="true"
					<?php if (mailusers_get_default_mass_email()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Receive emails sent to multiple recipients.', MAILUSERS_I18N_DOMAIN); ?><br/>
			<input 	type="checkbox"
					name="mailusers_default_user_control" id="mailusers_default_user_control" value="true"
					<?php if (mailusers_get_default_user_control()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Allow Users to control their own Email Users settings.', MAILUSERS_I18N_DOMAIN); ?><br />
			<input 	type="checkbox"
					name="mailusers_no_role_filter" id="mailusers_no_role_filter" value="true"
					<?php if (mailusers_get_no_role_filter()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Filter Users with <a href="https://codex.wordpress.org/Roles_and_Capabilities#Roles">no role</a> from Recipient List.', MAILUSERS_I18N_DOMAIN); ?>
		</td>
	</tr>
	<tr>
    <th><?php _e('Additional<br/>Mail Headers', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input 	type="checkbox"
					name="mailusers_add_x_mailer_header" id="mailusers_add_x_mailer_header" value="true"
					<?php if (mailusers_get_add_x_mailer_header()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Add <b>X-Mailer</b> mail header record.<br/><small><i>Not recommended for typical WordPress installations.</i></small>', MAILUSERS_I18N_DOMAIN); ?><br/>
			<input 	type="checkbox"
					name="mailusers_add_mime_version_header" id="mailusers_add_mime_version_header" value="true"
					<?php if (mailusers_get_add_mime_version_header()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Add <a href="http://en.wikipedia.org/wiki/MIME-Version#MIME-Version">MIME-Version</a> mail header record.<br/><small><i>Not recommended for typical WordPress installations.</i></small>', MAILUSERS_I18N_DOMAIN); ?><br/>
		</td>
	</tr>
	<tr>
    <th><?php _e('Dashboard Widgets', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input 	type="checkbox"
					name="mailusers_dashboard_widgets" id="mailusers_dashboard_widgets" value="true"
					<?php if (mailusers_get_dashboard_widgets()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Display Dashboard Widgets<br/><small><i>Note:  Email Users can show informational widgets on the Dashboard.</i></small>', MAILUSERS_I18N_DOMAIN); ?><br/>
		</td>
	</tr>
	<tr>
    <th><?php _e('Notification Widget', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input 	type="checkbox"
					name="mailusers_notification_widget" id="mailusers_notification_widget" value="true"
					<?php if (mailusers_get_notification_widget()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Display Notification Widget<br/><small><i>Note:  Email Users can show a notifcation widget on the Post/Page editing screen.</i></small>', MAILUSERS_I18N_DOMAIN); ?><br/>
		</td>
	</tr>
	<tr>
    <th><?php _e('Notification Menus', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input 	type="checkbox"
					name="mailusers_notification_menus" id="mailusers_notification_menus" value="true"
					<?php if (mailusers_get_notification_menus()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Display Notification Menus<br/><small><i>Note:  Email Users can show a notifcation menu on the Post/Page sections of the Dashboard menu.</i></small>', MAILUSERS_I18N_DOMAIN); ?><br/>
		</td>
	</tr>
	<tr>
    <th><?php _e('Debug', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input 	type="checkbox"
					name="mailusers_debug" id="mailusers_debug" value="true"
					<?php if (mailusers_get_debug()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Enable Debug Mode<br/><small><i>Note:  Email is not sent when in debug mode.</i></small>', MAILUSERS_I18N_DOMAIN); ?><br/>
		</td>
	</tr>
	<tr style="display:none;">
    <th><?php _e('Base64 Encode Email', MAILUSERS_I18N_DOMAIN); ?></th>
		<td>
			<input 	type="checkbox" disabled
					name="mailusers_base64_encode" id="mailusers_base64_encode" value="true"
					<?php if (mailusers_get_base64_encode()=='true') echo 'checked="checked"';?> ></input>
			<?php _e('Enable Base64 Encoding<br/><small><i>Note:  All email will be Base64 encoded when enabled.</i></small>', MAILUSERS_I18N_DOMAIN); ?><br/>
		</td>
	</tr>
	</table>

	<p class="submit">
		<input class="button-primary" type="submit" name="Submit" value="<?php _e('Save Changes', MAILUSERS_I18N_DOMAIN); ?> &raquo;" />
	</p>
</form>	

<br class="clear"/>

</div><!-- inside -->
</div><!-- postbox -->

<div id="email-users-defaults" class="postbox email-users-postbox">
<div class="handlediv" title="Click to toggle"><br /></div>
<h3 class="hndle"><span><?php _e('Email-Users Defaults', MAILUSERS_I18N_DOMAIN); ?></span></h3>
<div class="inside">
<p><?php _e('Email-Users has default values for all settings.  When reset, the following values are used.', MAILUSERS_I18N_DOMAIN); ?></p>
<table class="widefat">
	<thead>
	<tr>
		<th><?php _e('Settings', MAILUSERS_I18N_DOMAIN); ?></th>
		<th><?php _e('Default Value', MAILUSERS_I18N_DOMAIN); ?></th>
	</tr>
	</thead>
	<tbody>
<?php
	$default_settings = mailusers_get_default_plugin_settings();
	foreach ($default_settings as $key => $value) {
?>
	<tr>
		<td width="200px"><b><?php echo ucwords(preg_replace(array('/mailusers_/', '/_/'), array('', ' '), $key)); ?></b></td>
		<td><?php echo htmlentities($value); ?></td>
	</tr>
<?php
	}
?>
	</tbody>
</table>
<form name="ResetPluginSettings" action="" method="post">
    <?php wp_nonce_field( 'mailusers_plugin_settings', 'mailusers_plugin_settings_nonce' ); ?>
	<p class="submit">
		<input type="hidden" name="resetpluginsettings" value="true" />
		<input class="button-primary" type="submit" name="Submit" value="<?php _e('Apply Default Settings', MAILUSERS_I18N_DOMAIN); ?> &raquo;" />
	</p>
</form>	

<br class="clear"/>

</div><!-- inside -->
</div><!-- postbox -->

<div id="email-users-test-notification" class="postbox email-users-postbox">
<div class="handlediv" title="Click to toggle"><br /></div>
<h3 class="hndle"><span><?php _e('Email-Users Test Notification Mail', MAILUSERS_I18N_DOMAIN); ?></span></h3>
<div class="inside">
<p><?php _e('Use this Test Notification to verify proper operation of Email-Users.', MAILUSERS_I18N_DOMAIN);?></p>
<table class="widefat">
	<thead>
	<tr>
		<th colspan="2"><?php _e('Notification Mail Preview (updated when the options are saved)', MAILUSERS_I18N_DOMAIN); ?></th>
	</tr>
	</thead>
	<tbody>
<?php
	global $wpdb;
	$post_id = $wpdb->get_var("select max(id) from $wpdb->posts where post_type='post'");
	if (!isset($post_id)) {
?>
	<tr>
		<td colspan="2"><?php _e('No post found in the blog in order to build a notification preview.', MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
<?php
	} else {						
		$subject = mailusers_get_default_subject();
		$mail_content = mailusers_get_default_body();
		$mail_footer = mailusers_get_footer();

		// Replace the template variables concerning the blog details
		// --
		$subject = mailusers_replace_blog_templates($subject);
		$mail_content = mailusers_replace_blog_templates($mail_content);
			
		// Replace the template variables concerning the sender details
		// --	
		wp_get_current_user();
		global $post, $user_identity, $user_email ;

        $from_sender = 0;
        $from_address = empty($user_email) ? get_bloginfo('email') : $user_email;
        $from_name = empty($user_identity) ? get_bloginfo('name') : $user_identity;
        

        $override_name = mailusers_get_from_sender_name_override();
        $override_address = mailusers_get_from_sender_address_override();

        //  Override the send from address?
        if (($from_sender == 1) && !empty($override_address) && is_email($override_address))
        {

            $from_address = $override_address ;
            if (!empty($override_name)) $from_name = $override_name ;

        }

		$subject = mailusers_replace_sender_templates($subject, $from_name);
		$mail_content = mailusers_replace_sender_templates($mail_content, $from_name);
	
		$post = get_post( $post_id );
		$post_title = $post->post_title;
		$post_url = get_permalink( $post_id );			
		$post_content = explode( '<!--more-->', $post->post_content, 2 );
		$post_excerpt = get_the_excerpt();
        $post_author = get_userdata( $post->post_author )->display_name;
		
        //  Deal with post content in array form
        if (is_array($post_content)) $post_content = $post_content[0] ;

		$subject = mailusers_replace_post_templates($subject, $post_title, $post_author, $post_excerpt, $post_content, $post_url);
        if (mailusers_get_wpautop_processing()=='true')
		    $mail_content = wpautop(mailusers_replace_post_templates($mail_content, $post_title, $post_author, $post_excerpt, $post_content, $post_url));
        else
		    $mail_content = mailusers_replace_post_templates($mail_content, $post_title, $post_author, $post_excerpt, $post_content, $post_url);
?>
	<tr>
		<td><b><?php _e('Subject', MAILUSERS_I18N_DOMAIN); ?></b></td>
		<td><?php echo mailusers_get_default_mail_format()=='html' ? $subject : '<pre>' . format_to_edit($subject) . '</pre>';?></td>
	</tr>
	<tr>
		<td><b><?php _e('Message', MAILUSERS_I18N_DOMAIN); ?></b></td>
		<td><?php echo mailusers_get_default_mail_format()=='html' ? $mail_content : '<pre>' . wordwrap(strip_tags($mail_content), 80, "\n") . '</pre>';?></td>
	</tr>
<?php
	}
?>
	</tbody>
</table>
<form name="SendTestEmail" action="" method="post">
    <?php wp_nonce_field( 'mailusers_plugin_settings', 'mailusers_plugin_settings_nonce' ); ?>
	<p class="submit">
		<input type="hidden" name="sendtestemail" value="true" />
		<input class="button-primary" type="submit" name="Submit" value="<?php _e('Send Test Notification to Yourself', MAILUSERS_I18N_DOMAIN); ?> &raquo;" />
	</p>
</form>	
<br class="clear"/>

</div><!-- inside -->
</div><!-- postbox -->

<div id="email-users-variables" class="postbox email-users-postbox">
<div class="handlediv" title="Click to toggle"><br /></div>
<h3 class="hndle"><span><?php _e('Email-Users Variables', MAILUSERS_I18N_DOMAIN);?></span></h3>
<div class="inside">
<p><?php _e('Variables you can include in the subject or body templates', MAILUSERS_I18N_DOMAIN); ?></p>
<table class="widefat">
	<thead>
	<tr>
		<th><?php _e('Variable', MAILUSERS_I18N_DOMAIN); ?></th>
		<th><?php _e('Description', MAILUSERS_I18N_DOMAIN); ?></th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td><b>%BLOG_URL%</b></td>
		<td><?php _e('the link to the blog', MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	<tr>
		<td><b>%BLOG_NAME%</b></td>
		<td><?php _e('the blog\'s name', MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	<tr>
		<td><b>%FROM_NAME%</b></td>
		<td><?php _e('the WordPress user name of the person sending the mail', MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	<tr>
		<td><b>%POST_TITLE%</b></td>
		<td><?php _e('the title of the post you want to highlight', MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	<tr>
		<td><b>%POST_AUTHOR%</b></td>
		<td><?php _e('the author of the post you want to highlight', MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	<tr>
		<td><b>%POST_EXCERPT%</b></td>
		<td><?php _e('the excerpt of the post you want to highlight', MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	<tr>
		<td><b>%POST_CONTENT%</b></td>
		<td><?php _e('the content of the post you want to highlight', MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	<tr>
		<td><b>%POST_URL%</b></td>
		<td><?php _e('the link to the post you want to highlight', MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	</tbody>
</table>
<br class="clear"/>

</div><!-- inside -->
</div><!-- postbox -->

<div id="email-users-capabilities" class="postbox email-users-postbox">
<div class="handlediv" title="Click to toggle"><br /></div>
<h3 class="hndle"><span><?php _e('Email-Users Capabilities', MAILUSERS_I18N_DOMAIN);?></span></h3>
<div class="inside">

<p><?php _e('Email Users uses capabilities to define what users are allowed to do. Below is a list of the capabilities used by the plugin and the default user role allowed to make these actions.', MAILUSERS_I18N_DOMAIN); ?> <?php _e('If you want to change the roles having those capabilities, you should use the plugin:', MAILUSERS_I18N_DOMAIN); ?> <a href="http://www.im-web-gefunden.de/wordpress-plugins/role-manager/" target="_blank">Role Manager</a></p>

<table class="widefat">
	<thead>
	<tr>
		<th><?php _e('Capability', MAILUSERS_I18N_DOMAIN); ?></th>
		<th><?php _e('Description', MAILUSERS_I18N_DOMAIN); ?></th>
		<th><?php _e('Default Roles', MAILUSERS_I18N_DOMAIN); ?></th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td><b>manage-options</b></td>
		<td><?php _e('Access this options page.', MAILUSERS_I18N_DOMAIN); ?></td>
		<td><?php _e('Administrators only.', MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	<tr>
		<td><b><?php echo MAILUSERS_EMAIL_SINGLE_USER_CAP;?></b></td>
		<td><?php _e('Send an email to a single user.', MAILUSERS_I18N_DOMAIN); ?></td>
		<td><?php _e('Administrators, editors, authors and contributors.', MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	<tr>
		<td><b><?php echo MAILUSERS_EMAIL_MULTIPLE_USERS_CAP; ?></b></td>
		<td><?php _e('Send an email to various users at the same time.', MAILUSERS_I18N_DOMAIN); ?></td>
		<td><?php _e('Administrators, editors and authors.', MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	<tr>
		<td><b><?php echo MAILUSERS_NOTIFY_USERS_CAP; ?></b></td>
		<td><?php _e('Notify users of new posts.', MAILUSERS_I18N_DOMAIN); ?></td>
		<td><?php _e('Administrators and editors.', MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	<tr>
		<td><b><?php echo MAILUSERS_EMAIL_USER_GROUPS_CAP; ?></b></td>
		<td><?php _e('Send an email to user groups.', MAILUSERS_I18N_DOMAIN); ?></td>
		<td><?php _e('Administrators and editors.', MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	</tbody>
</table>

<br/>
</div><!-- inside -->
</div><!-- postbox -->

<div id="email-users-wp_mail" class="postbox email-users-postbox">
<div class="handlediv" title="Click to toggle"><br /></div>
<h3 class="hndle"><span><?php _e('Email-Users wp_mail() Check', MAILUSERS_I18N_DOMAIN);?></span></h3>
<div class="inside">

<p><?php _e('Email Users is dependent on the <a href="http://codex.wordpress.org/Function_Reference/wp_mail">wp_mail()</a> function.', MAILUSERS_I18N_DOMAIN); ?></p>

    <table style="width: auto;" class="widefat">
    <thead>
    <tr>
    <th colspan="2"><?php _e('wp_mail() is loaded from:', MAILUSERS_I18N_DOMAIN);?></th>
    <tr>
    </thead>
    <tbody>
    <th><?php _e('Expected:', MAILUSERS_I18N_DOMAIN) ;?></th>
    <td><?php print $expected;?></td>
    </tr>
    <tr>
    <th><?php _e('Actual:', MAILUSERS_I18N_DOMAIN) ;?></th>
    <td<?php if ($actual != $expected) echo ' style="color: red;"' ; ?>><?php print $actual;?></td>
    </tr>
    </tbody>
    </table>
    <p>
<?php _e('The WordPress wp_mail() function is <a href="http://codex.wordpress.org/Pluggable_Functions">pluggable</a>
    which means the standard WordPress functionality can be overloaded by a Theme or another WordPress plugin.
    It is important to note that if the <strong><i>expected</i></strong> and <strong><i>actual</i></strong> do
    not match, it does not automatically mean there will be a problem.  However, if Email Users is not producing
    the expected results AND the <strong><i>expected</i></strong> and <strong><i>actual</i></strong> do not match,
    it is something which should be looked at as a potential source of the problem.', MAILUSERS_I18N_DOMAIN) ; ?>
    </p>
    <p>
<?php _e('The recommended way to eliminate the overloaded version of wp_mail() as the source of a problem is to disable
    the plugin or theme which has overloaded wp_mail().', MAILUSERS_I18N_DOMAIN) ; ?>
    </p>
<br/>
</div><!-- inside -->
</div><!-- postbox -->

<div id="email-users-integration" class="postbox email-users-postbox">
<div class="handlediv" title="Click to toggle"><br /></div>
<h3 class="hndle"><span><?php _e('Email-Users Integration', MAILUSERS_I18N_DOMAIN);?></span></h3>
<div class="inside">

<p><?php _e('Email Users provides integration hooks with several WordPress "Group" plugins.', MAILUSERS_I18N_DOMAIN); ?></p>

<table class="widefat">
	<thead>
	<tr>
		<th><?php _e('Plugin', MAILUSERS_I18N_DOMAIN); ?></th>
		<th><?php _e('Description <small><i>(from the plugin web site)</i></small>', MAILUSERS_I18N_DOMAIN); ?></th>
		<th><?php _e('Status', MAILUSERS_I18N_DOMAIN); ?></th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td><b><a href="http://wordpress.org/plugins/user-groups/">User Groups</a></b></td>
		<td><?php _e('This plugin does one thing, and does it well: create groups and organize your users by that group.', MAILUSERS_I18N_DOMAIN); ?></td>
		<td><?php _e((class_exists(MAILUSERS_USER_GROUPS_CLASS) ? 'Enabled' : 'Disabled'), MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	<tr>
		<td><b><a href="http://wordpress.org/plugins/user-access-manager/">User Access Manager</a></b></td>
		<td><?php _e('With the "User Access Manager"-plugin you can manage the access to your posts, pages and files.', MAILUSERS_I18N_DOMAIN); ?></td>
		<td><?php _e((class_exists(MAILUSERS_USER_ACCESS_MANAGER_CLASS) ? 'Enabled' : 'Disabled'), MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	<tr>
		<td><b><a href="http://wordpress.org/plugins/groups/">Groups</a></b></td>
		<td><?php _e('Groups provides group-based user membership management, group-based capabilities and content access control.', MAILUSERS_I18N_DOMAIN); ?></td>
		<td><?php _e((class_exists(MAILUSERS_ITTHINX_GROUPS_CLASS) ? 'Enabled' : 'Disabled'), MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	<tr>
		<td><b><a href="http://wordpress.org/plugins/paid-memberships-pro/">Paid Memberships Pro</a></b></td>
		<td><?php _e('Paid Memberships Pro:  The easiest way to GET PAID with your WordPress site. Flexible content control by Membership Level, Reports, Affiliates and Discounts.', MAILUSERS_I18N_DOMAIN); ?></td>
		<td><?php _e((class_exists(MAILUSERS_PMPRO_CLASS) ? 'Enabled' : 'Disabled'), MAILUSERS_I18N_DOMAIN); ?></td>
	</tr>
	</tbody>
</table>

<br/>
</div><!-- inside -->
</div><!-- postbox -->

</div><!-- meta-box-sortables -->
</div><!-- metabox-holder -->
</div><!-- 65% Postbox Container -->
<div class="postbox-container side" style="margin-left: 10px; min-width: 225px; width:25%; border: 0px dashed red;"><!-- 25% Postbox Container -->
<div class="metabox-holder">
<div class="meta-box-sortables">

<div id="email-users-info" class="postbox email-users-postbox">
<div class="handlediv" title="Click to toggle"><br /></div>
<h3 class="hndle"><span><?php _e('Email-Users Information', MAILUSERS_I18N_DOMAIN);?></span></h3>
<div class="inside">

<div><!-- Email Users info box-->
<div class="table table_content">
<table class="widefat">
<tr><th colspan="2"><?php _e('User Settings', MAILUSERS_I18N_DOMAIN) ;?></th></tr>
<tr>
<td><?php _e('Number of Users who accept<br/>post or page notification emails:', MAILUSERS_I18N_DOMAIN); ?></td>
<td<?php if ( count($notifications) == 0) echo ' style="color: red;"' ; ?>><?php echo count($notifications) ; ?></td>
</tr>
<tr>
<td><?php _e('Number of Users who accept<br/>emails sent to multiple recipients:', MAILUSERS_I18N_DOMAIN); ?></td>
<td<?php if ( count($massemails) == 0) echo ' style="color: red;"' ; ?>><?php echo count($massemails) ; ?></td>
</tr>
</table>
<br/>
<table class="widefat">
<tr><th colspan="2"><?php _e('Filters', MAILUSERS_I18N_DOMAIN) ;?></th></tr>
<?php
    $filters = array('wp_mail_content_type', 'wp_mail_charset', 'wp_mail_from', 'wp_mail_from_name') ;

    foreach ($filters as $filter) {
?>
<tr>
<td><?php printf('%s:', $filter); ?></td>
<td<?php if ( has_action($filter)) echo ' style="color: red;"' ; ?>><?php echo has_action($filter) ? __('Yes', MAILUSERS_I18N_DOMAIN) : __('No', MAILUSERS_I18N_DOMAIN) ; ?></td>
</tr>
<?php
    }
?>
</table>
<p><small><?php _e('There are a number of <a href="http://codex.wordpress.org/Plugin_API/Filter_Reference">WordPress Filters</a> which <strong><i>may</i></strong> affect Email Users.  The presence of these filters does not mean they will adversely affect Email Users but should be examined if email delivery isn\'t reaching all of the intended recipients or the mail format and/or header detail isn\'t as expected.', MAILUSERS_I18N_DOMAIN) ; ?></small></p>
</div>
</div>

</div><!-- inside -->
</div><!-- postbox -->

<div id="email-users-donation" class="postbox email-users-postbox">
<div class="handlediv" title="Click to toggle"><br /></div>
<h3 class="hndle"><span><?php _e('Make a Donation', MAILUSERS_I18N_DOMAIN);?></span></h3>
<div class="inside">

<div style="text-align: center; font-size: 0.75em;padding:0px 5px;margin:0px auto;"><!-- PayPal box wrapper -->
<div><!-- PayPal box-->
	<p style="margin: 0.25em 0"><b>Email Users <?php echo mailusers_get_current_version(); ?></b></p>
	<p style="margin: 0.25em 0"><a href="http://email-users.vincentprat.info" target="_blank"><?php _e('Plugin\'s Home Page', MAILUSERS_I18N_DOMAIN); ?></a></p>
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="EYKMSYDUL746U">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
</div><!-- PayPal box -->
</div>

</div><!-- inside -->
</div><!-- postbox -->
<div id="email-users-mw-plugins" class="postbox email-users-postbox">
<div class="handlediv" title="Click to toggle"><br /></div>
<h3 class="hndle"><span><?php _e('More Plugins from Mike Walsh', MAILUSERS_I18N_DOMAIN); ?></span></h3>
<div class="inside" style="">
<div style="padding:0px 5px;">
<div>
	<ul style="list-style-type: square;margin-left: 7px;">
		<li><?php _e('If you use Google Forms and want to integrate them with your WordPress site, try: ', MAILUSERS_I18N_DOMAIN); ?><a href="http://michaelwalsh.org/wordpress/wordpress-plugins/wpgform/">WordPress Google Form</a></li>
	</ul>
</div>
</div>
</div><!-- inside -->
</div><!-- postbox -->

<div id="email-users-ml-plugins" class="postbox email-users-postbox">
<div class="handlediv" title="Click to toggle"><br /></div>
<h3 class="hndle"><span><?php _e('Discover other Plugins by MarvinLabs', MAILUSERS_I18N_DOMAIN); ?></span></h3>
<div class="inside" style="">
<div style="padding:0px 5px;">
<div>
	<ul style="list-style-type: square;margin-left: 7px;">
		<li><?php _e('If Email-Users is not robust enough or if you want to allow your users to communicate with each other, try: ', MAILUSERS_I18N_DOMAIN); ?><a href="http://user-messages.marvinlabs.com">User Messages</a></li>
		<li><a href="https://profiles.wordpress.org/marvinlabs/#content-plugins"><?php _e('Other Plugins from Marvin Labs', MAILUSERS_I18N_DOMAIN); ?></a></li>
	</ul>
</div>
</div>
</div><!-- inside -->
</div><!-- postbox -->
</div><!-- meta-box-sortables -->
</div><!-- metabox-holder -->
</div><!-- 25% Postbox Container -->
</div><!-- Postbox Containers -->
<form style="display:none" method="get" action="">
    <?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
    <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
</form>
</div><!-- wrap -->
