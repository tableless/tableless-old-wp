<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * Example filter to remove the Email Users Dashboard menu for non-admin users.
 *
 * @see https://wordpress.org/support/topic/totally-disable-the-admin-email-user-menu-for-users-wp-admin-menu
 *
 * To use this filter, copy the code below to your functions.php
 * file and modify it to suit the application (e.g. change the capability
 * check to something else).
 */

function remove_email_users_menus(){
    //  Check to see if the current user has Admin capabilities ...
	if (!current_user_can('activate_plugins')) {
        $slug = plugin_basename('email-users' . DIRECTORY_SEPARATOR . 'email-users.php');
        remove_menu_page( $slug );
	}
}
add_action( 'admin_menu', 'remove_email_users_menus' );
