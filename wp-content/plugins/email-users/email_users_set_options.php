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
	if (!current_user_can('manage_options')) {
        wp_die(printf('<div class="error fade"><p>%s</p></div>',
            __('You are not allowed to change the options of this plugin.', MAILUSERS_I18N_DOMAIN)));
	} 

	// Send the email if it has been requested
	if (array_key_exists('sendtestemail', $_POST) && $_POST['sendtestemail']=='true') {
		include('email_users_send_test_mail.php');
	}

	// Reset the plugin back to the default settings if it has been requested
	if (array_key_exists('resetpluginsettings', $_POST) && $_POST['resetpluginsettings']=='true') {
		mailusers_reset_to_default_settings();
?>
	<div class="updated fade">
		<p><?php _e("Plugin settings have been restored to the defaults.", MAILUSERS_I18N_DOMAIN); ?></p>
	</div>		
<?php
}

	//  Display the form
	include('email_users_options_form.php');
?>
