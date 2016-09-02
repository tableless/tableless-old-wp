<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/*
Plugin Name: Email Users
Version: 4.8.2
Plugin URI: http://wordpress.org/extend/plugins/email-users/
Description: Allows the site editors to send an e-mail to the blog users. Credits to <a href="http://www.catalinionescu.com">Catalin Ionescu</a> who gave me (Vincent Pratt) some ideas for the plugin and has made a similar plugin. Bug reports and corrections by Cyril Crua, Pokey and Mike Walsh.  Development for enhancements and bug fixes since version 4.1 primarily by <a href="http://michaelwalsh.org">Mike Walsh</a>.
Author: Mike Walsh & MarvinLabs
Author URI: http://www.michaelwalsh.org
*/

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

// Version of the plugin
define( 'MAILUSERS_CURRENT_VERSION', '4.8.2');

// i18n plugin domain
define( 'MAILUSERS_I18N_DOMAIN', 'email-users' );

// Capabilities used by the plugin
define( 'MAILUSERS_EMAIL_SINGLE_USER_CAP', 'email_single_user' );
define( 'MAILUSERS_EMAIL_MULTIPLE_USERS_CAP', 'email_multiple_users' );
define( 'MAILUSERS_EMAIL_USER_GROUPS_CAP', 'email_user_groups' );
define( 'MAILUSERS_NOTIFY_USERS_CAP', 'email_users_notify' );

// User meta
define( 'MAILUSERS_ACCEPT_NOTIFICATION_USER_META', 'email_users_accept_notifications' );
define( 'MAILUSERS_ACCEPT_MASS_EMAIL_USER_META', 'email_users_accept_mass_emails' );

// Debug
define( 'MAILUSERS_DEBUG', (mailusers_get_debug() === 'true'));

//  Enable integration with User Groups plugin?
//  @see http://wordpress.org/plugins/user-groups/

define( 'MAILUSERS_USER_GROUPS_CLASS', 'KWS_User_Groups' );
define( 'MAILUSERS_USER_GROUPS_TAXONOMY', 'user-group' );
define( 'MAILUSERS_USERS_GROUPS_PREFIX', 'ug') ;

//  Enable integration with User Access Manager plugin?
//  @see http://wordpress.org/plugins/user-access-manager/

define( 'MAILUSERS_USER_ACCESS_MANAGER_CLASS', 'UserAccessManager' );
define( 'MAILUSERS_USER_ACCESS_MANAGER_PREFIX', 'uam') ;

//  Enable integration with ItThinx Groups plugin?
//  @see http://wordpress.org/plugins/groups/

define( 'MAILUSERS_ITTHINX_GROUPS_CLASS', 'Groups_WordPress' );
define( 'MAILUSERS_ITTHINX_GROUPS_PREFIX', 'groups') ;

//  Enable integration with PMPro plugin?

//  @see http://wordpress.org/plugins/paid-memberships-pro/
define( 'MAILUSERS_PMPRO_CLASS', 'MemberOrder' );
define( 'MAILUSERS_PMPRO_PREFIX', 'pmpro') ;


define( 'MAILUSERS_CM_FILTER_PREFIX', 'filter') ;
$mailusers_user_custom_meta_filters = array() ;
$mailusers_group_custom_meta_filters = array() ;

/**
 * Initialise the internationalisation domain
 */
$is_mailusers_i18n_setup = false;
function mailusers_init_i18n() {
	global $is_mailusers_i18n_setup;

	if ($is_mailusers_i18n_setup == false) {
		load_plugin_textdomain(MAILUSERS_I18N_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/') ;
		$is_mailusers_i18n_setup = true;
	}
}

/**
 * Default values for the plugin settings
 */
function mailusers_get_default_plugin_settings($option = null)
{
	$default_plugin_settings = array(
		// Version of the email users plugin
		'mailusers_version' => mailusers_get_current_version(),
		// The default title to use when using the post notification functionality
		'mailusers_default_subject' => '[%BLOG_NAME%] ' . __('A post of interest:', MAILUSERS_I18N_DOMAIN) . ' "%POST_TITLE%"',
		// Mail User - The default body to use when using the post notification functionality
		'mailusers_default_body' => __('<p>Hello, </p><p>I would like to bring your attention on a new post published on the blog. Details of the post follow; I hope you will find it interesting.</p><p>Best regards, </p><p>%FROM_NAME%</p><hr><p><strong>%POST_TITLE%</strong></p><p>%POST_EXCERPT%</p><ul><li>Link to the post: <a href="%POST_URL%">%POST_URL%</a></li><li>Link to %BLOG_NAME%: <a href="%BLOG_URL%">%BLOG_URL%</a></li></ul>', MAILUSERS_I18N_DOMAIN),
		// Mail User - Default mail format (html or plain text)
		'mailusers_default_mail_format' => 'html',
		// Mail User - Default sort users by (none, display name, last name or first name)
		'mailusers_default_sort_users_by' => 'none',
		// Mail User - Maximum number of recipients in the BCC field
		'mailusers_max_bcc_recipients' => '0',
		// Mail User - Default setting for From Sender Name Override
		'mailusers_from_sender_name_override' => '',
		// Mail User - Default setting for From Sender Address Override
		'mailusers_from_sender_address_override' => '',
		// Mail User - Default setting for Send Bounces To Address Override
		'mailusers_send_bounces_to_address_override' => '',
		// Mail User - Maximum number of rows to show in the User Settings table
		'mailusers_user_settings_table_rows' => '20',
		// Mail User - Default setting for Notifications
		'mailusers_default_notifications' => 'true',
		// Mail User - Default setting for Mass Email
		'mailusers_default_mass_email' => 'true',
		// Mail User - Default setting for User Control
		'mailusers_default_user_control' => 'true',
		// Mail User - Default setting for "no roler" user filtering
		'mailusers_no_role_filter' => 'false',
		// Mail User - Default setting for Short Code Processing
		'mailusers_shortcode_processing' => 'false',
		// Mail User - Default setting for wpautop Processing
		'mailusers_wpautop_processing' => 'false',
		// Mail User - Default setting for From Sender Exclude
		'mailusers_from_sender_exclude' => 'true',
		// Mail User - Default setting for Copy Sender
		'mailusers_copy_sender' => 'false',
		// Mail User - Default setting for Add X-Mailer header
		'mailusers_add_x_mailer_header' => 'false',
		// Mail User - Default setting Enhanced Recipient Selection
		'mailusers_enhanced_recipient_selection' => 'true',
		// Mail User - Default setting Omit Display Names in Email Addresses
		'mailusers_omit_display_names' => 'false',
		// Mail User - The header to use when sending email
		'mailusers_header' => '<h3 style="border-bottom: 1px solid #eee;">%BLOG_NAME%</h3>',
		// Mail User - Default setting for Header usage
		'mailusers_header_usage' => 'none',
		// Mail User - The footer to use when sending email
		'mailusers_footer' => '<h5 style="border-top: 1px solid #eee;">' . __('Powered by', MAILUSERS_I18N_DOMAIN) . ' <a href="http://wordpress.org/plugins/email-users/">Email Users</a>.</h5>',
		// Mail User - Default setting for Header/Footer usage
		'mailusers_footer_usage' => 'notification',
		// Mail User - Default setting for Debug
		'mailusers_debug' => 'false',
		// Mail User - Default setting for Base64 Encode
		'mailusers_base64_encode' => 'false',
		// Mail User - Show Dashboard Widgets
		'mailusers_dashboard_widgets' => 'true',
		// Mail User - Show Notification Widget
		'mailusers_notification_widget' => 'true',
		// Mail User - Show Notification Menus
		'mailusers_notification_menus' => 'true',
	) ;

    if (array_key_exists($option, $default_plugin_settings))
        return $default_plugin_settings[$option] ;
    else
	    return $default_plugin_settings ;

}

/**
 * Reset plugin to use default settings
 */
function mailusers_reset_to_default_settings() {
	$plugin_settings = mailusers_get_default_plugin_settings() ;

	//  Update the options which will add them if they don't exist
	//  but WILL overwrite any existing settings back to the default.

	foreach ($plugin_settings as $key => $value)
		if ($key !== 'mailusers_version') update_option($key, $value) ;
}

/**
 * Set default values for the options (check against the version)
 */
register_activation_hook(__FILE__, 'mailusers_plugin_activation');
function mailusers_plugin_activation() {
	mailusers_init_i18n();

	$installed_version = mailusers_get_installed_version();

	if ( $installed_version==mailusers_get_current_version() ) {
		// do nothing
	}
	elseif ( $installed_version=='' ) {
		$plugin_settings = mailusers_get_default_plugin_settings() ;

		//  Add the options which will add them if they don't
		//  exist but won't overwrite any existing settings.

		foreach ($plugin_settings as $key => $value)
			add_option($key, $value) ;

		mailusers_add_default_capabilities();
		mailusers_add_default_user_meta();

	} else if ( $installed_version>='2.0' && $installed_version<'3.0.0' ) {
		// Version 2.x, a bug was corrected in the template, update it
		$plugin_settings = mailusers_get_default_plugin_settings() ;

		//  Add the options which will add them if they don't
		//  exist but won't overwrite any existing settings.

		foreach ($plugin_settings as $key => $value)
			add_option($key, $value) ;

		delete_option('mailusers_mail_user_level');
		delete_option('mailusers_mail_method');
		delete_option('mailusers_smtp_port');
		delete_option('mailusers_smtp_server');
		delete_option('mailusers_smtp_user');
		delete_option('mailusers_smtp_authentication');
		delete_option('mailusers_smtp_password');

		// Remove old capabilities
		$role = get_role('editor');
		$role->remove_cap('email_users');

		mailusers_add_default_capabilities();
		mailusers_add_default_user_meta();
	} else {
	}

	// Update version number
	update_option( 'mailusers_version', mailusers_get_current_version() );
}

/**
* Plugin deactivation
*/
register_deactivation_hook( __FILE__, 'mailusers_plugin_deactivation' );
function mailusers_plugin_deactivation() {
	//  Force the activation hook to run again when reactivated
	update_option('mailusers_version', '');
}

/**
* Add default user meta information
*/
function mailusers_add_default_user_meta() {
if (0) :
	$users = get_users() ;
	foreach ($users as $user) {
		mailusers_user_register($user->ID);
	}
else :
    $users = get_users(array('blog_id' => get_current_blog_id(), 'fields' => 'ID') );
	foreach ($users as $user) {
		mailusers_user_register($user);
	}
endif;
}

/**
* Add capabilities to roles by default
*/
function mailusers_add_default_capabilities() {
	$role = get_role('contributor');

    if ($role !== null) {
        //error_log(sprintf("%s::%s  contributor", basename(__FILE__), __LINE__)) ;
	    $role->add_cap(MAILUSERS_EMAIL_SINGLE_USER_CAP);
    }

	$role = get_role('author');

    if ($role !== null) {
        //error_log(sprintf("%s::%s  author", basename(__FILE__), __LINE__)) ;
	    $role->add_cap(MAILUSERS_EMAIL_SINGLE_USER_CAP);
	    $role->add_cap(MAILUSERS_EMAIL_MULTIPLE_USERS_CAP);
    }

	$role = get_role('editor');

    if ($role !== null) {
        //error_log(sprintf("%s::%s  editor", basename(__FILE__), __LINE__)) ;
	    $role->add_cap(MAILUSERS_NOTIFY_USERS_CAP);
	    $role->add_cap(MAILUSERS_EMAIL_SINGLE_USER_CAP);
	    $role->add_cap(MAILUSERS_EMAIL_MULTIPLE_USERS_CAP);
	    $role->add_cap(MAILUSERS_EMAIL_USER_GROUPS_CAP);
    }

	$role = get_role('administrator');

    if ($role !== null) {
        //error_log(sprintf("%s::%s  admin", basename(__FILE__), __LINE__)) ;
	    $role->add_cap(MAILUSERS_NOTIFY_USERS_CAP);
	    $role->add_cap(MAILUSERS_EMAIL_SINGLE_USER_CAP);
	    $role->add_cap(MAILUSERS_EMAIL_MULTIPLE_USERS_CAP);
	    $role->add_cap(MAILUSERS_EMAIL_USER_GROUPS_CAP);
    }
}

/**
 * Add the meta field when a user registers
 */
add_action('user_register', 'mailusers_user_register');
function mailusers_user_register($user_id) {
	mailusers_user_meta_init($user_id);
}

add_action('profile_update', 'mailusers_profile_update');
function mailusers_profile_update($user_id) {
	mailusers_user_meta_init($user_id);
}

/**
 * Add the meta field when a user registers
 */
function mailusers_user_meta_init($user_id) {
    $values = array('true', 'false');

    //  Check to see if user already has the user meta value and it is set.
	$default = mailusers_get_default_notifications() == 'true' ? 'true' : 'false' ;
	if (!in_array(get_user_meta($user_id, MAILUSERS_ACCEPT_NOTIFICATION_USER_META, true), $values))
		update_user_meta($user_id, MAILUSERS_ACCEPT_NOTIFICATION_USER_META, $default);

    //  Check to see if user already has the user meta value and it is set.
	$default = mailusers_get_default_mass_email() == 'true' ? 'true' : 'false' ;
	if (!in_array(get_user_meta($user_id, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META, true), $values))
		update_user_meta($user_id, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META, $default);
}

/**
* Add a related link to the post edit page to create a template from current post
*/
add_action('submitpost_box', 'mailusers_post_relatedlink');
function mailusers_post_relatedlink() {
	global $post_ID;
    //  Only show widget when enabled
    if (mailusers_get_notification_widget() === 'true') {
	    if (isset($post_ID) && current_user_can(MAILUSERS_NOTIFY_USERS_CAP)) {
?>
<div id="email-users-notify-post" class="postbox email-users-notify-postbox">
<h3 class='hndle'><span><?php _e('Email Users', MAILUSERS_I18N_DOMAIN); ?></span></h3>
<div class="inside">
<p><img style="padding: 5px; vertical-align: middle;" src="<?php echo plugins_url('images/email.png' , __FILE__); ?>"</img><a href="admin.php?page=mailusers-send-notify-mail-post&post_id=<?php echo $post_ID; ?>"><?php _e('Notify Users About this Post', MAILUSERS_I18N_DOMAIN); ?></a></p>
</div>
</div>
<?php
	    }
	}
}

add_action('submitpage_box', 'mailusers_page_relatedlink');
function mailusers_page_relatedlink() {
	global $post_ID;
    //  Only show widget when enabled
    if (mailusers_get_notification_widget() === 'true') {
	    if (isset($post_ID) && current_user_can(MAILUSERS_NOTIFY_USERS_CAP)) {
?>
<div id="email-users-notify-page" class="postbox email-users-notify-postbox">
<h3 class='hndle'><span><?php _e('Email Users', MAILUSERS_I18N_DOMAIN); ?></span></h3>
<div class="inside">
<p><img style="padding: 5px; vertical-align: middle;" src="<?php echo plugins_url('images/email.png' , __FILE__); ?>"</img><a href="admin.php?page=mailusers-send-notify-mail-page&post_id=<?php echo $post_ID; ?>"><?php _e('Notify Users About this Page', MAILUSERS_I18N_DOMAIN); ?></a></p>
</div>
</div>
<?php
	    }
	}
}

/**
 * Add a new menu under Write:, visible for all users with access levels 8+ (administrator role).
 */
add_action( 'admin_menu', 'mailusers_add_pages' );
function mailusers_add_pages() {
    global $mailusers_user_custom_meta_filters ;
    global $mailusers_group_custom_meta_filters ;

    mailusers_init_i18n();

    //  Show Notify Menus only when notification menus enabled
    if (mailusers_get_notification_menus() === 'true') {
        add_posts_page(
	        __('Notify Users', MAILUSERS_I18N_DOMAIN),
	        __('Notify Users', MAILUSERS_I18N_DOMAIN),
	        MAILUSERS_NOTIFY_USERS_CAP,
       	    'mailusers-send-notify-mail-post',
       	    'mailusers_send_notify_mail') ;

        add_pages_page(
	        __('Notify Users', MAILUSERS_I18N_DOMAIN),
	        __('Notify Users', MAILUSERS_I18N_DOMAIN),
	        MAILUSERS_NOTIFY_USERS_CAP,
       	    'mailusers-send-notify-mail-page',
       	    'mailusers_send_notify_mail') ;
    }

    $mailusers_options_page = add_options_page(
	    __('Email Users', MAILUSERS_I18N_DOMAIN),
	    __('Email Users', MAILUSERS_I18N_DOMAIN),
	    'manage_options',
       	'mailusers-options-page',
       	'mailusers_options_page') ;

    add_menu_page(
	    __('Email Users', MAILUSERS_I18N_DOMAIN), 
	    __('Email Users', MAILUSERS_I18N_DOMAIN), 
	    MAILUSERS_EMAIL_SINGLE_USER_CAP,
       	plugin_basename(__FILE__),
	    'mailusers_overview_page',
	    plugins_url( 'images/email.png' , __FILE__)) ;

    //  Send to User(s) Menu
    add_submenu_page(plugin_basename(__FILE__),
	    __('Send to User(s)', MAILUSERS_I18N_DOMAIN), 
	    __('Send to User(s)', MAILUSERS_I18N_DOMAIN), 
	MAILUSERS_EMAIL_SINGLE_USER_CAP,
       	'mailusers-send-to-user-page',
       	'mailusers_send_to_user_page') ;

    /**
     * Do we need to deal with a user custom meta filter?
     *
     */

    //  Load any custom meta filters
    do_action('mailusers_user_custom_meta_filter') ;

    foreach ($mailusers_user_custom_meta_filters as $mf)
    {
        $slug = strtolower($mf['label']);
        $slug = preg_replace("/[^a-z0-9\s-]/", "", $slug);
        $slug = trim(preg_replace("/[\s-]+/", " ", $slug));
        $slug = trim(substr($slug, 0));
        $slug1 = preg_replace("/\s/", "-", $slug);
        $slug2 = preg_replace("/\s/", "_", $slug);
        
        //  Need to create the function to call the custom filter email script

        $fn = create_function('', 'global $mailusers_mf, $mailusers_mv, $mailusers_mc; $mailusers_mf = \'' .
            $mf['meta_filter'] .  '\' ; $mailusers_mv = \'' . $mf['meta_value'] .  '\' ; $mailusers_mc = \'' .
            $mf['meta_compare'] . '\' ; require(\'email_users_send_custom_filter_mail.php\') ;');

        add_submenu_page(plugin_basename(__FILE__),
	        sprintf(__('Send to %s'), $mf['label'], MAILUSERS_I18N_DOMAIN), 
	        sprintf(__('Send to %s'), $mf['label'], MAILUSERS_I18N_DOMAIN), 
	        MAILUSERS_EMAIL_USER_GROUPS_CAP,
       	    'mailusers-send-to-custom-filter-page-' . $slug1, $fn) ;
       	    //'mailusers_send_to_custom_filter_page_' . $slug2) ;
    }

    //  Send to Group(s) Menu
    add_submenu_page(plugin_basename(__FILE__),
	    __('Send to Group(s)', MAILUSERS_I18N_DOMAIN), 
	    __('Send to Group(s)', MAILUSERS_I18N_DOMAIN), 
	    MAILUSERS_EMAIL_USER_GROUPS_CAP,
       	'mailusers-send-to-group-page',
       	'mailusers_send_to_group_page') ;

    /**
     * Do we need to deal with a user custom meta filter?
     *
     */

    //  Load any custom meta key filters
    do_action('mailusers_group_custom_meta_key_filter') ;

    //  Load any custom meta filters
    do_action('mailusers_group_custom_meta_filter') ;

    /**
    if (!empty($mailusers_group_custom_meta_filters))
    {
        //  Send to Group(s) Menu
        add_submenu_page(plugin_basename(__FILE__),
	        __('Send to Meta Group(s)', MAILUSERS_I18N_DOMAIN), 
	        __('Send to Meta Group(s)', MAILUSERS_I18N_DOMAIN), 
	        MAILUSERS_EMAIL_USER_GROUPS_CAP,
            'mailusers-send-to-group-custom-meta-page',
   	        'mailusers_send_to_group_custom_meta_page') ;
    }
    **/

    //  User Settings Menu
    add_submenu_page(plugin_basename(__FILE__),
	    __('User Settings', MAILUSERS_I18N_DOMAIN), 
	    __('User Settings', MAILUSERS_I18N_DOMAIN), 
	    'edit_users',
       	'mailusers-user-settings',
       	'mailusers_user_settings_page') ;

    //  Plugin specific script and CSS loading ...
    //  ***  Not currently used!  ***
    //add_action('admin_footer-'.$mailusers_options_page, 'mailusers_options_admin_footer') ;
    //add_action('admin_print_scripts-'.$mailusers_options_page, 'mailusers_options_print_scripts') ;
    //add_action('admin_print_styles-'.$mailusers_options_page, 'mailusers_options_print_styles') ;
}

/**
 * Wrapper for the options page menu
 */
function mailusers_options_page() {
    require_once('email_users_set_options.php') ;
}

/**
 * Wrapper for the main email users menu page
 */
function mailusers_overview_page()
{
    require_once('email_users_overview.php') ;
}

/**
 * Wrapper for the email users send to user menu
 */
function mailusers_send_to_user_page()
{
    require_once('email_users_send_user_mail.php') ;
}

/**
 * Wrapper for the email users send to group menu
 */
function mailusers_send_to_group_page()
{
    global $mailusers_send_to_group_mode ;
	$mailusers_send_to_group_mode = 'role' ;
    require_once('email_users_send_group_mail.php') ;
}

/**
 * Wrapper for the email users send to group custom meta menu
 */
function mailusers_send_to_group_custom_meta_page()
{
    global $mailusers_send_to_group_mode ;
    $mailusers_send_to_group_mode = 'meta' ;
    require_once('email_users_send_group_mail.php') ;
}

/**
 * Wrapper for the email users noptify users menu
 */
function mailusers_send_notify_mail()
{
    require_once('email_users_send_notify_mail.php') ;
}

/**
 * Wrapper for the email users notify group menu
 */
function mailusers_notify_group_page()
{
    require_once('email_users_notify_form.php') ;
}

function mailusers_user_settings_page()
{
    require_once('email_users_user_settings.php') ;
}

/**
 * Wrapper for the email users noptify users menu
 */
function mailusers_set_options_page()
{
    require_once('email_users_set_options.php') ;
}

/**
 * Wrapper for the email users send group mail page
 */
function mailusers_send_group_mail_page()
{
    require_once('email_users_send_group_mail.php') ;
}

/**
 * Action hook to add e-mail options to current user profile
 */
add_action('show_user_profile', 'mailusers_user_profile_form');
function mailusers_user_profile_form() {
	global $user_ID;

    mailusers_edit_any_user_profile_form($user_ID);
}

/**
 * Action hook to add e-mail options to any user profile
 */
add_action('edit_user_profile', 'mailusers_edit_user_profile_form');
function mailusers_edit_user_profile_form() {
	global $profileuser;

    mailusers_edit_any_user_profile_form($profileuser->ID);
}

/**
 * Add a form to change user preferences in the profile
 */
function mailusers_edit_any_user_profile_form($uid) {
 
    //  Do we let users control their own settings?  If so, show the
    //  checkboxes as part of the profile.  If not, settings are hidden.
 
    if ((mailusers_get_default_user_control()=='true') || current_user_can('edit_users')) {
?>
	<h3><?php _e('Email Preferences', MAILUSERS_I18N_DOMAIN); ?></h3>

	<table class="form-table">
	<tbody>
		<tr>
			<th></th>
			<td>
                <input type="hidden" name="<?php echo MAILUSERS_ACCEPT_NOTIFICATION_USER_META; ?>" value="false">
				<input type="checkbox"
						name="<?php echo MAILUSERS_ACCEPT_NOTIFICATION_USER_META; ?>"
						id="<?php echo MAILUSERS_ACCEPT_NOTIFICATION_USER_META; ?>"
						value="true"
						<?php if (get_user_meta($uid, MAILUSERS_ACCEPT_NOTIFICATION_USER_META, true)=='true') echo 'checked="checked"'; ?> ></input>
				<?php _e('Accept to receive post or page notification emails', MAILUSERS_I18N_DOMAIN); ?><br/>
				<input type="hidden" name="<?php echo MAILUSERS_ACCEPT_MASS_EMAIL_USER_META; ?>" value="false">
				<input type="checkbox"
						name="<?php echo MAILUSERS_ACCEPT_MASS_EMAIL_USER_META; ?>"
						id="<?php echo MAILUSERS_ACCEPT_MASS_EMAIL_USER_META; ?>"
						value="true"
						<?php if (get_user_meta($uid, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META, true)=='true') echo 'checked="checked"'; ?> ></input>

				<?php _e('Accept to receive emails sent to multiple recipients (but still accept emails sent only to me)', MAILUSERS_I18N_DOMAIN); ?>
			</td>
		</tr>
	</tbody>
	</table>
<?php
    }
}

/**
 * Action hook to update mailusers profile for current user
 */
add_action('personal_options_update', 'mailusers_user_profile_update');
function mailusers_user_profile_update() {
	global $user_ID;
	mailusers_any_user_profile_update($user_ID);
}

/**
 * Action hook to update mailusers profile for any user
 */
add_action('profile_update', 'mailusers_edit_user_profile_update');
function mailusers_edit_user_profile_update($uid) {
	mailusers_any_user_profile_update($uid);
}

/**
 * Save mailusers profile data for any user id
 */
function mailusers_any_user_profile_update($uid) {

	if (isset($_POST[MAILUSERS_ACCEPT_NOTIFICATION_USER_META])) {
	    $value = $_POST[MAILUSERS_ACCEPT_NOTIFICATION_USER_META] ;
		update_user_meta($uid, MAILUSERS_ACCEPT_NOTIFICATION_USER_META, $value);
	} else {
        add_user_meta($uid,  MAILUSERS_ACCEPT_NOTIFICATION_USER_META, get_option(MAILUSERS_ACCEPT_NOTIFICATION_USER_META), true);
	}

	if (isset($_POST[MAILUSERS_ACCEPT_MASS_EMAIL_USER_META])) {
	    $value = $_POST[MAILUSERS_ACCEPT_MASS_EMAIL_USER_META];
		update_user_meta($uid, MAILUSERS_ACCEPT_MASS_EMAIL_USER_META, $value);
	} else {
        add_user_meta($uid,  MAILUSERS_ACCEPT_MASS_EMAIL_USER_META, get_option(MAILUSERS_ACCEPT_MASS_EMAIL_USER_META), true);
    }
}

/**
 * mailusers_register_chosen()
 *
 * WordPress script registration for mailusers
 */
function mailusers_register_chosen()
{
    if (mailusers_get_enhanced_recipient_selection())
    {
        //  Register the jQuery Chosen script from the plugin
        wp_register_script('mailusers-chosen',
                plugins_url(plugin_basename(dirname(__FILE__) . '/js/chosen/chosen.jquery.min.js')),
            array('jquery'), false, true) ;

        //  Register the jQuery Chosen script from the plugin
        wp_register_script('mailusers',
                plugins_url(plugin_basename(dirname(__FILE__) . '/js/mailusers.js')),
            array('jquery', 'mailusers-chosen'), false, true) ;

        //  Register the jQuery Chosen CSS from the plugin
        wp_register_style('mailusers-chosen',
                plugins_url(plugin_basename(dirname(__FILE__) . '/js/chosen/chosen.min.css'))) ;
    }
}

/**
 * Enqueue scripts when needed
 *
 */
function email_users_enqueue_scripts($hook) {
    //  Load the JS and CSS only when appropriate ...
    switch ($hook)
    {
        case 'email-users_page_mailusers-send-to-user-page':
        case 'email-users_page_mailusers-send-to-group-page':
        case (preg_match('/posts_page_mailusers-send-notify-mail-.*/', $hook) ? true : false) :
            mailusers_register_chosen() ;

	        wp_enqueue_script('word-count');
	        wp_enqueue_script('post');
	        wp_enqueue_script('editor');
	        wp_enqueue_script('media-upload');

            wp_enqueue_script('mailusers-chosen') ;
            wp_enqueue_script('mailusers') ;
            wp_enqueue_style('mailusers-chosen') ;
            break ;
        default:
            break ;
    }
}
add_action('admin_enqueue_scripts', 'email_users_enqueue_scripts') ;

/**
 * Register settings for the WordPres Options API to work
 */
add_action('admin_init', 'mailusers_admin_init');
function mailusers_admin_init() {
    register_setting('email_users', 'mailusers_default_body') ;
    register_setting('email_users', 'mailusers_default_mail_format') ;
    register_setting('email_users', 'mailusers_default_mass_email') ;
    register_setting('email_users', 'mailusers_default_notifications') ;
    register_setting('email_users', 'mailusers_default_sort_users_by') ;
    register_setting('email_users', 'mailusers_default_subject') ;
    register_setting('email_users', 'mailusers_default_user_control') ;
    register_setting('email_users', 'mailusers_no_role_filter') ;
    register_setting('email_users', 'mailusers_max_bcc_recipients') ;
    register_setting('email_users', 'mailusers_user_settings_table_rows') ;
    register_setting('email_users', 'mailusers_shortcode_processing') ;
    register_setting('email_users', 'mailusers_wpautop_processing') ;
    register_setting('email_users', 'mailusers_from_sender_exclude') ;
    register_setting('email_users', 'mailusers_enhanced_recipient_selection') ;
    register_setting('email_users', 'mailusers_omit_display_names') ;
    register_setting('email_users', 'mailusers_copy_sender') ;
    register_setting('email_users', 'mailusers_from_sender_name_override') ;
    register_setting('email_users', 'mailusers_group_taxonomy') ;
    register_setting('email_users',
        'mailusers_from_sender_address_override', 'mailusers_from_sender_address_override_validate') ;
    register_setting('email_users',
        'mailusers_send_bounces_to_address_override', 'mailusers_send_bounces_to_address_override_validate') ;
    register_setting('email_users', 'mailusers_add_x_mailer_header') ;
    register_setting('email_users', 'mailusers_add_mime_version_header') ;
    register_setting('email_users', 'mailusers_header') ;
    register_setting('email_users', 'mailusers_header_usage') ;
    register_setting('email_users', 'mailusers_footer') ;
    register_setting('email_users', 'mailusers_footer_usage') ;
    register_setting('email_users', 'mailusers_debug') ;
    register_setting('email_users', 'mailusers_base64_encode') ;
    register_setting('email_users', 'mailusers_version') ;
    register_setting('email_users', 'mailusers_dashboard_widgets') ;
    register_setting('email_users', 'mailusers_notification_widget') ;
    register_setting('email_users', 'mailusers_notification_menus') ;
}

/**
 * Wrapper for the option 'mailusers_default_subject'
 */
function mailusers_get_default_subject() {
	return stripslashes(get_option( 'mailusers_default_subject' ));
}

/**
 * Wrapper for the option 'mailusers_default_subject'
 */
function mailusers_update_default_subject( $subject ) {
	return update_option( 'mailusers_default_subject', stripslashes($subject) );
}

/**
 * Wrapper for the option 'mailusers_default_body'
 */
function mailusers_get_default_body() {
	return stripslashes(get_option( 'mailusers_default_body' ));
}

/**
 * Wrapper for the option 'mailusers_default_body'
 */
function mailusers_update_default_body( $body ) {
	return update_option( 'mailusers_default_body', stripslashes($body) );
}

/**
 * Wrapper for the option 'mailusers_header'
 */
function mailusers_get_header() {
    $option = get_option( 'mailusers_header' );

    if ($option === false)
        $option = mailusers_get_default_plugin_settings( 'mailusers_header' );

    return stripslashes($option);
}

/**
 * Wrapper for the option 'mailusers_header'
 */
function mailusers_update_header( $header ) {
	return update_option( 'mailusers_header', stripslashes($header) );
}

/**
 * Wrapper for the option 'mailusers_footer'
 */
function mailusers_get_footer() {
    $option = get_option( 'mailusers_footer' );

    if ($option === false)
        $option = mailusers_get_default_plugin_settings( 'mailusers_footer' );

    return stripslashes($option);
}

/**
 * Wrapper for the option 'mailusers_footer'
 */
function mailusers_update_footer( $footer ) {
	return update_option( 'mailusers_footer', stripslashes($footer) );
}

/**
 * Wrapper for the option 'mailusers_header_usage'
 */
function mailusers_get_header_usage() {
    $option = get_option( 'mailusers_header_usage' );

    if ($option === false)
        $option = mailusers_get_default_plugin_settings( 'mailusers_header_usage' );

    return stripslashes($option);
}

/**
 * Wrapper for the option 'mailusers_header_usage'
 */
function mailusers_update_header_usage( $usage ) {
	return update_option( 'mailusers_header_usage', stripslashes($usage) );
}

/**
 * Wrapper for the option 'mailusers_footer_usage'
 */
function mailusers_get_footer_usage() {
    $option = get_option( 'mailusers_footer_usage' );

    if ($option === false)
        $option = mailusers_get_default_plugin_settings( 'mailusers_footer_usage' );

    return stripslashes($option);
}

/**
 * Wrapper for the option 'mailusers_footer_usage'
 */
function mailusers_update_footer_usage( $usage ) {
	return update_option( 'mailusers_footer_usage', stripslashes($usage) );
}

/**
 * Wrapper for the option 'mailusers_version'
 */
function mailusers_get_installed_version() {
	return get_option( 'mailusers_version' );
}

/**
 * Wrapper for the option 'mailusers_version'
 */
function mailusers_get_current_version() {
	return MAILUSERS_CURRENT_VERSION;
}

/**
 * Wrapper for the option default_mail_format
 */
function mailusers_get_default_mail_format() {
	return get_option( 'mailusers_default_mail_format' );
}

/**
 * Wrapper for the option default_mail_format
 */
function mailusers_update_default_mail_format( $default_mail_format ) {
	return update_option( 'mailusers_default_mail_format', $default_mail_format );
}

/**
 * Wrapper for the option default_sort_users_by
 */
function mailusers_get_default_sort_users_by() {
	return get_option( 'mailusers_default_sort_users_by' );
}

/**
 * Wrapper for the option default_sort_users_by
 */
function mailusers_update_default_sort_users_by( $default_sort_users_by ) {
	return update_option( 'mailusers_default_sort_users_by', $default_sort_users_by );
}

/**
 * Wrapper for the option mail_method
 */
function mailusers_get_max_bcc_recipients() {
	return get_option( 'mailusers_max_bcc_recipients' );
}

/**
 * Wrapper for the option max bcc recipients
 */
function mailusers_update_max_bcc_recipients( $max_bcc_recipients ) {
	return update_option( 'mailusers_max_bcc_recipients', $max_bcc_recipients );
}

/**
 * Wrapper for the user settings table rows option
 */
function mailusers_get_user_settings_table_rows() {
	return get_option( 'mailusers_user_settings_table_rows' );
}

/**
 * Wrapper for the user settings table rows option
 */
function mailusers_update_user_settings_table_rows( $user_settings_table_rows ) {
	return update_option( 'mailusers_user_settings_table_rows', $user_settings_table_rows );
}

/**
 * Wrapper for the from sender name override option
 */
function mailusers_get_from_sender_name_override() {
	return get_option( 'mailusers_from_sender_name_override' );
}

/**
 * Wrapper for the group taxonomy option
 */
function mailusers_get_group_taxonomy() {
	return get_option( 'mailusers_group_taxonomy' );
}

/**
 * Wrapper for the from sender name override option
 */
function mailusers_update_from_sender_name_override( $from_sender_name_override ) {
	return update_option( 'mailusers_from_sender_name_override', $from_sender_name_override );
}

/**
 * Wrapper for the from sender address override option
 */
function mailusers_get_from_sender_address_override() {
	return get_option( 'mailusers_from_sender_address_override' );
}

/**
 * Wrapper for the from sender address override option
 */
function mailusers_update_from_sender_address_override( $from_sender_address_override ) {
	return update_option( 'mailusers_from_sender_address_override', $from_sender_address_override );
}

/**
 * Wrapper for the from sender address override option validation
 */
function mailusers_from_sender_address_override_validate( $from_sender_address_override ) {
	return is_email($from_sender_address_override ) ? $from_sender_address_override : false ;
}


/**
 * Wrapper for the from sender address override option
 */
function mailusers_get_send_bounces_to_address_override() {
	return get_option( 'mailusers_send_bounces_to_address_override' );
}

/**
 * Wrapper for the from sender address override option
 */
function mailusers_update_send_bounces_to_address_override( $send_bounces_to_address_override ) {
	return update_option( 'mailusers_send_bounces_to_address_override', $send_bounces_to_address_override );
}

/**
 * Wrapper for the from sender address override option validation
 */
function mailusers_send_bounces_to_address_override_validate( $send_bounces_to_address_override ) {
	return is_email($send_bounces_to_address_override ) ? $send_bounces_to_address_override : false ;
}

/**
 * Wrapper for the default notification setting
 */
function mailusers_get_default_notifications() {
	return get_option( 'mailusers_default_notifications' );
}

/**
 * Wrapper for the default notification setting
 */
function mailusers_update_default_notifications( $default_notifications ) {
	return update_option( 'mailusers_default_notifications', $default_notifications );
}

/**
 * Wrapper for the default mass email setting
 */
function mailusers_get_default_mass_email() {
	return get_option( 'mailusers_default_mass_email' );
}

/**
 * Wrapper for the default mass email setting
 */
function mailusers_update_default_mass_email( $default_mass_email ) {
	return update_option( 'mailusers_default_mass_email', $default_mass_email );
}

/**
 * Wrapper for the default user control setting
 */
function mailusers_get_default_user_control() {
	return get_option( 'mailusers_default_user_control' );
}

/**
 * Wrapper to set the default user control setting
 */
function mailusers_update_default_user_control( $default_user_control ) {
	return update_option( 'mailusers_default_user_control', $default_user_control );
}

/**
 * Wrapper for the "no role" filter setting
 */
function mailusers_get_no_role_filter() {
	return get_option( 'mailusers_no_role_filter' );
}

/**
 * Wrapper to set the "no role" filter setting
 */
function mailusers_update_no_role_filter( $no_role_filter ) {
	return update_option( 'mailusers_no_role_filter', $no_role_filter );
}

/**
 * Wrapper for getting the Add X-Mailer Header option
 */
function mailusers_get_add_x_mailer_header() {
	return get_option( 'mailusers_add_x_mailer_header' );
}

/**
 * Wrapper for setting the Add X-Mailer Header option
 */
function mailusers_update_add_x_mailer_header( $add_x_mailer_header ) {
	return update_option( 'mailusers_add_x_mailer_header', $add_x_mailer_header );
}

/**
 * Wrapper for getting the Add MIME-Version Header option
 */
function mailusers_get_add_mime_version_header() {
	return get_option( 'mailusers_add_mime_version_header' );
}

/**
 * Wrapper for setting the Add MIME-Version Header option
 */
function mailusers_update_add_mime_version_header( $add_mime_version_header ) {
	return update_option( 'mailusers_add_mime_version_header', $add_mime_version_header );
}

/**
 * Wrapper for the short code processing setting
 */
function mailusers_get_shortcode_processing() {
	return get_option( 'mailusers_shortcode_processing' );
}

/**
 * Wrapper for the short code processing setting
 */
function mailusers_update_shortcode_processing( $shortcode_processing ) {
	return update_option( 'mailusers_shortcode_processing', $shortcode_processing );
}

/**
 * Wrapper for the short code processing setting
 */
function mailusers_get_wpautop_processing() {
	return get_option( 'mailusers_wpautop_processing' );
}

/**
 * Wrapper for the short code processing setting
 */
function mailusers_update_wpautop_processing( $wpautop_processing ) {
	return update_option( 'mailusers_wpautop_processing', $wpautop_processing );
}

/**
 * Wrapper for the enhanced recipient selection setting
 */
function mailusers_get_enhanced_recipient_selection() {
    $option = get_option( 'mailusers_enhanced_recipient_selection' );

    if ($option === false)
        $option = mailusers_get_default_plugin_settings( 'enhanced_recipient_selection' );

    return $option;
}

/**
 * Wrapper for the omit display names setting
 */
function mailusers_update_enhanced_recipient_selection( $enhanced_recipient_selection ) {
	return update_option( 'mailusers_enhanced_recipient_selection', $enhanced_recipient_selection );
}

/**
 * Wrapper for the omit display names setting
 */
function mailusers_get_omit_display_names() {
    $option = get_option( 'mailusers_omit_display_names' );

    if ($option === false)
        $option = mailusers_get_default_plugin_settings( 'omit_display_names' );

    return $option;
}

/**
 * Wrapper for the omit display names setting
 */
function mailusers_update_omit_display_names( $omit_display_names ) {
	return update_option( 'mailusers_omit_display_names', $omit_display_names );
}

/**
 * Wrapper for the from send exclude setting
 */
function mailusers_get_from_sender_exclude() {
    $option = get_option( 'mailusers_from_sender_exclude' );

    if ($option === false)
        $option = mailusers_get_default_plugin_settings( 'mailusers_from_sender_exclude' );

    return $option;
}

/**
 * Wrapper for the from sender exclude setting
 */
function mailusers_update_from_sender_exclude( $from_sender_exclude ) {
	return update_option( 'mailusers_from_sender_exclude', $from_sender_exclude );
}

/**
 * Wrapper for the from send exclude setting
 */
function mailusers_get_copy_sender() {
    $option = get_option( 'mailusers_copy_sender' );

    if ($option === false)
        $option = mailusers_get_default_plugin_settings( 'mailusers_copy_sender' );

    return $option;
}

/**
 * Wrapper for the from sender exclude setting
 */
function mailusers_update_copy_sender( $copy_sender ) {
	return update_option( 'mailusers_copy_sender', $copy_sender );
}

/**
 * Wrapper for the DEBUG setting
 */
function mailusers_get_debug() {
    $option = get_option( 'mailusers_debug' );

    if ($option === false)
        $option = mailusers_get_default_plugin_settings( 'mailusers_debug' );

    return $option;
}

/**
 * Wrapper for the DEBUG setting
 */
function mailusers_update_debug( $debug ) {
	return update_option( 'mailusers_debug', $debug );
}

/**
 * Wrapper for the Dashboard Widgets setting
 */
function mailusers_get_dashboard_widgets() {
    $option = get_option( 'mailusers_dashboard_widgets' );

    if ($option === false)
        $option = mailusers_get_default_plugin_settings( 'mailusers_dashboard_widgets' );

    return $option;
}

/**
 * Wrapper for the Dashboard Widgets setting
 */
function mailusers_update_dashboard_widgets( $dashboard_widgets ) {
	return update_option( 'mailusers_dashboard_widgets', $dashboard_widgets );
}

/**
 * Wrapper for the Notifcation Widget setting
 */
function mailusers_get_notification_widget() {
    $option = get_option( 'mailusers_notification_widget' );

    if ($option === false)
        $option = mailusers_get_default_plugin_settings( 'mailusers_notification_widget' );

    return $option;
}

/**
 * Wrapper for the Notifcation Widgets setting
 */
function mailusers_update_notification_widget( $notification_widget ) {
	return update_option( 'mailusers_notification_widget', $notification_widget );
}

/**
 * Wrapper for the Notifcation Menus setting
 */
function mailusers_get_notification_menus() {
    $option = get_option( 'mailusers_notification_menus' );

    if ($option === false)
        $option = mailusers_get_default_plugin_settings( 'mailusers_notification_menus' );

    return $option;
}

/**
 * Wrapper for the Notifcation Menus setting
 */
function mailusers_update_notification_menus( $notification_menus ) {
	return update_option( 'mailusers_notification_menus', $notification_menus );
}

/**
/**
 * Wrapper for the Base64 Encoding setting
 */
function mailusers_get_base64_encode() {
    $option = get_option( 'mailusers_base64_encode' );

    if ($option === false)
        $option = mailusers_get_default_plugin_settings( 'mailusers_base64_encode' );

    return $option;
}

/**
 * Wrapper for the DEBUG setting
 */
function mailusers_update_base64_encode( $base64_encode ) {
	return update_option( 'mailusers_base64_encode', $base64_encode );
}

/**
 * Get the users
 * $meta_filter can be '', MAILUSERS_ACCEPT_NOTIFICATION_USER_META, or MAILUSERS_ACCEPT_MASS_EMAIL_USER_META
 */
function mailusers_get_users( $exclude_id='', $meta_filter = '', $args = array(), $sortby = null, $meta_value = 'true', $meta_compare = '=') {
    if (MAILUSERS_DEBUG) printf('<!-- %s::%s -->%s', basename(__FILE__), __LINE__, PHP_EOL);
    
	if ($sortby == null) $sortby = mailusers_get_default_sort_users_by();

    //  Set up the arguments for get_users()

    $args = array_merge($args, array(
        'exclude' => array($exclude_id),
        //'fields' => array('ID', 'display_name', 'user_email'),
        'fields' => 'all',
        'offset' => '0',
        'number' => '500',
    )) ;

    //  Apply the meta filter

    if ($meta_filter != '')
    {
        $args = array_merge($args, array(
            //'fields' => 'all_with_meta',
            'meta_key' => $meta_filter,
            'meta_value' => $meta_value,
            'meta_like_escape' => false,
            'meta_compare' => $meta_compare)) ;

        if (!array_key_exists('include', $args))
            $args['include'] = '' ;

        if (!array_key_exists('exclude', $args))
            $args['exclude'] = '' ;
 
        //  Note:  WordPress 3.5.1 and earlier do not support 'LIKE' 
        //  constructs on meta queries - they get wrapped with SQL
        //  protection.  A patch has been submitted for WordPress 3.6
        //  to allow LIKE and NOT LIKE to work properly.
        //
        //  http://core.trac.wordpress.org/ticket/23373
 
        //  Is it a LIKE comparison?  If so, handle it differently ...
 
        if (in_array(strtoupper(trim($meta_compare)), array('LIKE', 'NOT LIKE')))
        {
            $args = array_merge($args, array(
                'meta_like_escape' => true,
                'meta_compare' => strtoupper(trim($meta_compare)))) ;
        }

    }

    //  Filter users with no role on site from list?

    $nr = array() ;

    if (mailusers_get_no_role_filter()=='true'):
        $roles = new WP_Roles() ;

        //  Find all the users which have a role

        $u = array() ;
        foreach ($roles->get_names() as $role)
            $u = array_merge($u, get_users(array('role' => $role, 'fields' => 'ID'))) ;

        //  Now find all of the users which don't have a role

        $nr = get_users(array('exclude' => $u, 'fields' => 'ID')) ;

        $args['exclude'] = array_merge($args['exclude'], $nr) ;
    endif;

    if (MAILUSERS_DEBUG) printf('<!-- %s::%s -->%s', basename(__FILE__), __LINE__, PHP_EOL) ;
    if (MAILUSERS_DEBUG) printf('<!-- %s::%s -->%s', basename(__FILE__), __LINE__, PHP_EOL) ;
    if (MAILUSERS_DEBUG) printf('<!-- %s%s -->%s', PHP_EOL, print_r(count_users(), true), PHP_EOL) ;
    if (MAILUSERS_DEBUG) printf('<!-- %s::%s -->%s', basename(__FILE__), __LINE__, PHP_EOL) ;
    if (MAILUSERS_DEBUG) printf('<!-- %s%s -->%s', PHP_EOL, print_r($args, true), PHP_EOL) ;
    if (MAILUSERS_DEBUG) printf('<!-- %s::%s -->%s', basename(__FILE__), __LINE__, PHP_EOL) ;

    //  On some sites with a large number of users, it is possible to run out of memory
    //  when calling get_users() with the arguments 'fields' => 'all_with_meta' (which is
    //  no longer being used as it become unnecessary in WordPress 3.x).  To limit the
    //  potential of memory exhaustion, the query is done in chunks and a result is assembled.

    //  Retrieve the list of users

    $users = count_users() ;
    $total = $users['total_users'] ;

    $users = array() ;

    $q = 1 ;

    while ($args['offset'] < $total)
    {
        if (MAILUSERS_DEBUG) printf('<!-- %s::%s  Query #%s  Memory Usage:  %s -->%s',
            basename(__FILE__), __LINE__, $q++, mailusers_memory_usage(true), PHP_EOL) ;
        $users = array_merge($users, get_users($args)) ;
        $args['offset'] += $args['number'] ;
    }

    if (MAILUSERS_DEBUG) printf('<!-- %s%s -->%s', PHP_EOL, print_r(count($users), true), PHP_EOL);

    //  Sort the users based on the plugin settings

    if ( ! empty( $users) ) {
		switch ($sortby) {
			case 'fl' :
			case 'flul' :
                usort( $users, 'mailusers_sort_users_by_first_name' );
				break;
			case 'lf' :
			case 'lful' :
                usort( $users, 'mailusers_sort_users_by_last_name' );
				break;
			case 'ul' :
			case 'uldn' :
			case 'ulfn' :
			case 'ulln' :
                usort( $users, 'mailusers_sort_users_by_user_login' );
				break;
			case 'display name' :
                usort( $users, 'mailusers_sort_users_by_display_name' );
				break;
			default:
				break;
		}

    }

    if (MAILUSERS_DEBUG) printf('<!-- %s::%s -->%s', basename(__FILE__), __LINE__, PHP_EOL) ;

    return $users ;
}

/**
 * Sort by last name
 */
function mailusers_sort_users_by_last_name( $a, $b )
{
    if ( $a->last_name == $b->last_name ) {
        return 0;
    }

    return ( $a->last_name < $b->last_name ) ? -1 : 1;
}

/**
 * Sort by first name
 */
function mailusers_sort_users_by_first_name( $a, $b )
{
    if ( $a->first_name == $b->first_name ) {
        return 0;
    }

    return ( $a->first_name < $b->first_name ) ? -1 : 1;
}

/**
 * Sort by display name
 */
function mailusers_sort_users_by_display_name( $a, $b )
{
    if ( $a->display_name == $b->display_name ) {
        return 0;
    }

    return ( $a->display_name < $b->display_name ) ? -1 : 1;
}

/**
 * Sort by user login
 */
function mailusers_sort_users_by_user_login( $a, $b )
{
    if ( $a->user_login == $b->user_login ) {
        return 0;
    }

    return ( $a->user_login < $b->user_login ) ? -1 : 1;
}

/**
 * Get the users based on roles
 * $meta_filter can be '', MAILUSERS_ACCEPT_NOTIFICATION_USER_META, or MAILUSERS_ACCEPT_MASS_EMAIL_USER_META
 *
 * Added support for editable_roles filter
 * @see https://wordpress.org/support/topic/mailusers_get_roles-function-to-use-the-core-get_editable_roles?replies=2#post-6513328
 *
 */
function mailusers_get_roles( $exclude_id='', $meta_filter = '') {
	$roles = array();

	$wp_roles = get_editable_roles( );

	foreach ($wp_roles as $key => $value) {
		$users_in_role = mailusers_get_recipients_from_roles(array($key), $exclude_id, $meta_filter);
		if (!empty($users_in_role)) {
			$roles[$key] = $value['name'];
        }
	}

	return $roles;
}

/**
 * Get the users based on group custom meta filters
 * $meta_filter can be '', MAILUSERS_ACCEPT_NOTIFICATION_USER_META, or MAILUSERS_ACCEPT_MASS_EMAIL_USER_META
 */
function mailusers_get_group_meta_filters( $exclude_id='', $meta_filter = '') {
	$filters = array();

    global $mailusers_group_custom_meta_filters ;

    $users = mailusers_get_users($exclude_id, $meta_filter) ;
    $ids = array() ;

    foreach ($users as $user)
        $ids[] = $user->ID ;

    $mf = &$mailusers_group_custom_meta_filters ;

	foreach ($mf as $key => $value) {
        $users_in_filter = mailusers_get_recipients_from_custom_meta_filter($ids,
            $exclude_id, $value['meta_filter'], $value['meta_value'], $value['meta_compare']);
		if (!empty($users_in_filter)) {
			$filters[$key] = $value['label'];
        }
	}

	return $filters;
}

/**
 * Get the users given a role or an array of ids
 * $meta_filter can be '', MAILUSERS_ACCEPT_NOTIFICATION_USER_META, or MAILUSERS_ACCEPT_MASS_EMAIL_USER_META
 */
function mailusers_get_recipients_from_ids( $ids, $exclude_id='', $meta_filter = '') {
    return mailusers_get_users($exclude_id, $meta_filter, array('include' => $ids)) ;
}

/**
 * Get the users given a role or an array of roles
 * $meta_filter can be '', MAILUSERS_ACCEPT_NOTIFICATION_USER_META, or MAILUSERS_ACCEPT_MASS_EMAIL_USER_META
 */
function mailusers_get_recipients_from_roles($roles, $exclude_id='', $meta_filter = '') {

    $users = array() ;

    foreach ($roles as $role)
        $users = array_merge($users, mailusers_get_users($exclude_id, $meta_filter, array('role' => $role))) ;

    return $users ;
}


/**
 * Get the users given the existance of a custom meta filter
 * $meta_filter can be '', MAILUSERS_ACCEPT_NOTIFICATION_USER_META, or MAILUSERS_ACCEPT_MASS_EMAIL_USER_META
 */
function mailusers_get_recipients_from_custom_meta_filter( $ids, $exclude_id='', $meta_filter='', $meta_value='', $meta_compare='=') {

    return mailusers_get_users($exclude_id, $meta_filter, array('include' => $ids), null, $meta_value, $meta_compare) ;
}

/**
 * Get the users given a filter or an array of filters.
 * $meta_filter can be '', MAILUSERS_ACCEPT_NOTIFICATION_USER_META, or MAILUSERS_ACCEPT_MASS_EMAIL_USER_META
 */
function mailusers_get_recipients_from_custom_meta_filters($filters, $exclude_id='', $meta_filter = '') {

    global $mailusers_group_custom_meta_filters ;

    $users = mailusers_get_users($exclude_id, $meta_filter) ;
    $ids = array() ;

    foreach ($users as $user)
        $ids[] = $user->ID ;

    $users = array() ;

    foreach ($filters as $filter)
    {
        $mf = &$mailusers_group_custom_meta_filters[$filter] ;
        $users = array_merge($users, mailusers_get_recipients_from_custom_meta_filter($ids,
            $exclude_id, $mf['meta_filter'], $mf['meta_value'], $mf['meta_compare']));
    }

    return $users ;
}

/**
 * Register a user custom meta filter
 *
 */
function mailusers_register_user_custom_meta_filter($label, $meta_filter, $meta_value, $meta_compare = '=') {
    global $mailusers_user_custom_meta_filters ;

    $mailusers_user_custom_meta_filters[] = array(
        'label' => $label,
        'meta_filter' => $meta_filter,
        'meta_value' => $meta_value,
        'meta_compare' => $meta_compare) ;
}

/**
 * Register a group custom meta filter
 *
 */
function mailusers_register_group_custom_meta_filter($label, $meta_filter, $meta_value, $meta_compare = '=') {
    global $mailusers_group_custom_meta_filters ;

    $mailusers_group_custom_meta_filters[] = array(
        'label' => $label,
        'meta_filter' => $meta_filter,
        'meta_value' => $meta_value,
        'meta_compare' => $meta_compare) ;
}

/**
 * Register a group custom meta key filter
 *
 */
function mailusers_register_group_custom_meta_key_filter($meta_key, $meta_value = null, $label_cb = null) {
    require_once('email_users_custom_filter_class.php') ;

    CustomMetaKeyGroupFilter::BuildFilter($meta_key, $meta_value, $label_cb) ;
}

/**
 * Check Valid E-Mail Address
 */
function mailusers_is_valid_email($email) {
	if (function_exists('is_email')) {
		return is_email($email);
	}

	$regex = '/^[A-z0-9][\w.+-]*@[A-z0-9][\w\-\.]+\.[A-z0-9]{2,6}$/';
	return (preg_match($regex, $email));
}

/**
 * Protect against special characters (e.g. $) in the post content
 * being processed as part of the preg_replace() replacement string.
 *
 * @see http://www.procata.com/blog/archives/2005/11/13/two-preg_replace-escaping-gotchas/
 */
function mailusers_preg_quote($str) {
    return preg_replace('/(\$|\\\\)(?=\d)/', '\\\\\1', $str);
}

/**
 * Replace the template variables in a given text.
 */
function mailusers_replace_post_templates($text, $post_title, $post_author, $post_excerpt, $post_content, $post_url) {
	$text = preg_replace( '/%POST_TITLE%/', mailusers_preg_quote($post_title), $text );
	$text = preg_replace( '/%POST_AUTHOR%/', mailusers_preg_quote($post_author), $text );
	$text = preg_replace( '/%POST_EXCERPT%/', mailusers_preg_quote($post_excerpt), $text );
	$text = preg_replace( '/%POST_CONTENT%/', mailusers_preg_quote($post_content), $text );
	$text = preg_replace( '/%POST_URL%/', mailusers_preg_quote($post_url), $text );
	return $text;
}

/**
 * Replace the template variables in a given text.
 */
function mailusers_replace_blog_templates($text) {
	$blog_url = get_option( 'home' );
	$blog_name = get_option( 'blogname' );

	$text = preg_replace( '/%BLOG_URL%/', mailusers_preg_quote($blog_url), $text );
	$text = preg_replace( '/%BLOG_NAME%/', mailusers_preg_quote($blog_name), $text );
	return $text;
}

/**
 * Replace the template variables in a given text.
 */
function mailusers_replace_sender_templates($text, $sender_name) {
	$text = preg_replace( '/%FROM_NAME%/', mailusers_preg_quote($sender_name), $text );
	return $text;
}

/**
 * Delivers email to recipients in HTML or plaintext
 *
 * Returns number of recipients addressed in emails or false on internal error.
 */
function mailusers_send_mail($recipients = array(), $subject = '', $message = '',
    $type='plaintext', $sender_name='', $sender_email='', $useheader = false, $usefooter = false) {
    
    $headers = array() ;
    $base64 = (mailusers_get_base64_encode() == 'true') ;
    $omit = (mailusers_get_omit_display_names() == 'true') ;

    //  Default the To: and Cc: values to the send email address
    //  Some MTAs won't deliver email with an address in the TO header!
    $to = ($omit) ? $sender_email : sprintf('%s <%s>', $sender_name, $sender_email) ;
    $cc = sprintf('Cc: %s', $to) ;

	$num_sent = 0; // return value
	if ( (empty($recipients)) ) { return $num_sent; }
	if ('' == $message) { return false; }

    //  Cc: Sender?
    $ccsender = mailusers_get_copy_sender() ;

    //  Return path defaults to sender email if not specified
    //$return_path = mailusers_get_send_bounces_to_address_override() ;
    //if (!empty($return_path)) {
    //    echo '<div class="update-nag fade"><p>' . sprintf(__('Setting a bounce email address is no longer supported due to casuing problems delivering mail.  Please remove the current setting (%s).', MAILUSERS_I18N_DOMAIN), $return_path) . '</p></div>';
    //}

    //  Build headers
	$headers[] = ($omit) ? sprintf('From: %s', $sender_email) : sprintf('From: "%s" <%s>', $sender_name, $sender_email);
	//$headers[] = sprintf('Return-Path: <%s>', $return_path);
    //  Return path defaults to sender email if not specified
    $return_path = mailusers_get_send_bounces_to_address_override() ;
    if (empty($return_path))
	    $headers[] = ($omit) ? sprintf('Reply-To: %s', $sender_email) : sprintf('Reply-To: "%s" <%s>', $sender_name, $sender_email);
    //$headers[] = 'MIME-Version: 1.0';

    if (mailusers_get_add_x_mailer_header() == 'true')
	    $headers[] = sprintf('X-Mailer: PHP %s', phpversion()) ;

	$subject = stripslashes($subject);
	$message = stripslashes($message);
    $header = $useheader ? mailusers_replace_blog_templates(mailusers_get_header()) : '' ;
    $footer = $usefooter ? mailusers_replace_blog_templates(mailusers_get_footer()) : '' ;

	if ('html' == $type) {
        if (mailusers_get_add_mime_version_header() == 'true')
		    $headers[] = 'MIME-Version: 1.0';
		$headers[] = sprintf('Content-Type: %s; charset="%s"', get_bloginfo('html_type'), get_bloginfo('charset')) ;

        //  Apply HTML wrapper filter(s) if one exists
        if (has_filter('mailusers_html_wrapper'))
            $mailtext = apply_filters('mailusers_html_wrapper', $subject, $message, $footer) ;
        else
		    $mailtext = "<html><head><title>" . $subject . "</title></head><body>" . $header . $message . $footer . "</body></html>";

	} else {
        if (mailusers_get_add_mime_version_header() == 'true')
		    $headers[] = 'MIME-Version: 1.0';
		$headers[] = sprintf('Content-Type: text/plain; charset="%s"', get_bloginfo('charset')) ;
		$message = preg_replace('|&[^a][^m][^p].{0,3};|', '', $message);
		$message = preg_replace('|&amp;|', '&', $message);
		$mailtext = wordwrap(strip_tags($header . "\n" . $message . "\n" . $footer), 80, "\n");
	}

    //  Base64 Encode email?
    if ($base64) {
        //$subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        $headers[] = "Content-Transfer-Encoding: base64";
        //$mailtext = base64_encode($mailtext);
    }

	// If unique recipient, send mail using TO field.
	//--
	// If multiple recipients, use the BCC field
	//--
	$bcc = array();
	$bcc_limit = mailusers_get_max_bcc_recipients();

	if (count($recipients)==1) {
        $recipient = reset($recipients) ; // reset will return first value of the array!
		if (mailusers_is_valid_email($recipient->user_email)) {
            $to = ($omit) ? $recipient->user_email : sprintf('%s <%s>', $recipient->display_name, $recipient->user_email) ;

            if ($ccsender) $headers[] = $cc ;

			if (MAILUSERS_DEBUG) {
				mailusers_preprint_r($headers);
		        mailusers_debug_wp_mail($to, $subject, $mailtext, $headers);
			}
			
            do_action('mailusers_before_wp_mail') ;

            //  Filter to manipulate the headers?
            if (has_filter('mailusers_manipulate_headers'))
            {
                $mh = apply_filters('mailusers_manipulate_headers', $to, $headers, $bcc) ;
                list($to, $headers, $bcc) = $mh ;
            }

            if ($base64)
			    @wp_mail($to, sprintf("=UTF-8?B?%s?=", base64_encode($subject)), base64_encode($mailtext), $headers);
            else
			    @wp_mail($to, $subject, $mailtext, $headers);
            do_action('mailusers_after_wp_mail') ;

			$num_sent++;
		} else {
			echo '<div class="error fade"><p>' . sprintf(__('The email address (%s) of the user you are trying to send mail to is not a valid email address format.', MAILUSERS_I18N_DOMAIN), $recipient->user_email) . '</p></div>';
			return $num_sent;
		}
		return $num_sent;
	}

    elseif ($bcc_limit != 0 && (count($recipients) > $bcc_limit))
    {
		$count = 0;
		$sender_emailed = false;

        //  Make sure there are no duplicates which can result
        //  if/when the user selects both roles and users as
        //  the recipients.

        foreach ($recipients as $key=> $value)
			$recipients[$key] = $recipients[$key]->user_email;

        $recipients = array_unique($recipients) ;

        foreach ($recipients as $recipient) {

            if (!mailusers_is_valid_email($recipient)) {
                continue;
            }
            if ( empty($recipient) || ($sender_email == $recipient) ) {
                continue;
            }

            //  When BCC limit is -1, use the TO header to send instead of BCC header
            if ($bcc_limit == -1)
                //$to = ($omit) ? $recipient->user_email : sprintf('%s <%s>', $recipient->display_name, $recipient->user_email) ;
                $to = $recipient ;
            else
    		    $bcc[] = sprintf('Bcc: %s', $recipient) ;

			$count++;

            //  Use abs() of bcc_limit to account for -1 setting
			if ((abs($bcc_limit) == $count) || ($num_sent == count($recipients) - 1)) {
					
				if (MAILUSERS_DEBUG) {
		            mailusers_debug_wp_mail($to, $subject, $mailtext, array_merge($headers, $bcc)) ;
				}
			
                do_action('mailusers_before_wp_mail') ;

                //  Filter to manipulate the headers?
                if (has_filter('mailusers_manipulate_headers'))
                {
                    $mh = apply_filters('mailusers_manipulate_headers', $to, $headers, $bcc) ;
                    list($to, $headers, $bcc) = $mh ;
                }

                if ($base64)
                    @wp_mail($to, sprintf("=UTF-8?B?%s?=",
                        base64_encode($subject)), base64_encode($mailtext), array_merge($headers, $bcc));
                else
				    @wp_mail($to, $subject, $mailtext, array_merge($headers, $bcc)) ;
                do_action('mailusers_after_wp_mail') ;

				$count = 0;
				$bcc = array() ;
			}

			$num_sent++;
		}
    }
    else
    {
        if ($ccsender) $headers[] = $cc ;

        foreach ($recipients as $key=> $value)
			$recipients[$key] = $recipients[$key]->user_email;

        $recipients = array_unique($recipients) ;

        foreach ($recipients as $recipient) {

            if (!mailusers_is_valid_email($recipient)) {
                echo '<div class="error fade"><p>' . sprintf(__('Invalid email address ("%s") found.', MAILUSERS_I18N_DOMAIN), $recipient) . '</p></div>';
                continue;
            }

			if (empty($recipient) || ($sender_email == $recipient)) continue;

    		$bcc[] = sprintf('Bcc: %s', $recipient) ;
			$num_sent++;
		}

		if (MAILUSERS_DEBUG) {
			mailusers_preprint_r(array_merge($headers, $bcc)) ;
		    mailusers_debug_wp_mail($to, $subject, $mailtext, array_merge($headers, $bcc)) ;
		}
			
        do_action('mailusers_before_wp_mail') ;

        //  Filter to manipulate the headers?
        if (has_filter('mailusers_manipulate_headers'))
        {
            $mh = apply_filters('mailusers_manipulate_headers', $to, $headers, $bcc) ;
            list($to, $headers, $bcc) = $mh ;
        }

        if ($base64)
            @wp_mail($to, sprintf("=UTF-8?B?%s?=",
                base64_encode($subject)), base64_encode($mailtext), array_merge($headers, $bcc));
        else
		    @wp_mail($to, $subject, $mailtext, array_merge($headers, $bcc)) ;
        do_action('mailusers_after_wp_mail') ;
	}

	return $num_sent;
}

/**
 * Add a widget to the dashboard.
 *
 * This function is hooked into the 'wp_dashboard_setup' action below.
 */
function mailusers_add_dashboard_widgets() {

    //  Only show widget when enabled
    if (mailusers_get_dashboard_widgets() === 'true')
    {
        //  Only show widget for users who have the capability
        if (current_user_can(MAILUSERS_EMAIL_SINGLE_USER_CAP) ||
            current_user_can(MAILUSERS_EMAIL_MULTIPLE_USERS_CAP) ||
            current_user_can(MAILUSERS_EMAIL_USER_GROUPS_CAP) ||
            current_user_can(MAILUSERS_NOTIFY_USERS_CAP)) 
        {
	        wp_add_dashboard_widget(
                'mailusers_dashboard_widget',         // Widget slug.
                'Email Users',                        // Title.
                'mailusers_dashboard_widget_function' // Display function.
            );	
        }
    }
}
add_action( 'wp_dashboard_setup', 'mailusers_add_dashboard_widgets' );

/**
 * Create the function to output the contents of our Dashboard Widget.
 */
function mailusers_dashboard_widget_function() {
?>
    <div class="table table_content">
    <p class="sub"><?php _e('Default User Settings', MAILUSERS_I18N_DOMAIN); ?></p>
    <table style="text-align: left; width: 90%;">
   	<tr>
    <th><?php _e('Receive post or page notification emails:', MAILUSERS_I18N_DOMAIN); ?></th>
	<td><?php echo (mailusers_get_default_notifications()=='true') ? __('On', MAILUSERS_I18N_DOMAIN) : __('Off', MAILUSERS_I18N_DOMAIN) ; ?></td>
	</tr>
   	<tr>
    <th><?php _e('Receive emails sent to multiple recipients:', MAILUSERS_I18N_DOMAIN); ?></th>
	<td><?php echo (mailusers_get_default_mass_email()=='true') ? __('On', MAILUSERS_I18N_DOMAIN) : __('Off', MAILUSERS_I18N_DOMAIN) ; ?></td>
	</tr>
   	<tr>
    <th><?php _e('Allow Users to control their own Email Users settings:', MAILUSERS_I18N_DOMAIN); ?></th>
	<td><?php echo (mailusers_get_default_user_control()=='true') ? __('On', MAILUSERS_I18N_DOMAIN) : __('Off', MAILUSERS_I18N_DOMAIN) ; ?></td>
	</tr>
   	<tr>
    <th><?php _e('Filter Users with no role from Recipient List:', MAILUSERS_I18N_DOMAIN); ?></th>
	<td><?php echo (mailusers_get_no_role_filter()=='true') ? __('On', MAILUSERS_I18N_DOMAIN) : __('Off', MAILUSERS_I18N_DOMAIN) ; ?></td>
	</tr>
	</table>
    </div>
<?php

    //  Report the number of users who accept notifications and mass emails

    $massemails = mailusers_get_users('', MAILUSERS_ACCEPT_MASS_EMAIL_USER_META) ;
    $notifications = mailusers_get_users('', MAILUSERS_ACCEPT_NOTIFICATION_USER_META) ;

?>
    <div class="table table_content">
    <p class="sub"><?php _e('User Statistics', MAILUSERS_I18N_DOMAIN); ?></p>
    <table style="text-align: left; width: 90%;">
   	<tr>
    <th><?php _e('Number of Users who accept post or page notification emails:', MAILUSERS_I18N_DOMAIN); ?></th>
	<td<?php if ( count($notifications) == 0) echo ' style="color: red;"' ; ?>><?php echo count($notifications) ; ?></td>
	</tr>
   	<tr>
    <th><?php _e('Number of Users who accept emails sent to multiple recipients:', MAILUSERS_I18N_DOMAIN); ?></th>
	<td<?php if ( count($massemails) == 0) echo ' style="color: red;"' ; ?>><?php echo count($massemails) ; ?></td>
	</tr>
	</table>
    </div>
<?php return ; /**  The remainder of the filter information isn't finished yet.  **/ ?>
    <div class="table table_content">
    <p class="sub"><?php _e('Content Filters', MAILUSERS_I18N_DOMAIN); ?></p>
    <table style="text-align: left; width: 90%;">
<?php
    global $wp_filter;
    $filters = array(
        'the_content' => 'http://codex.wordpress.org/Function_Reference/the_content',
        'the_excerpt' => 'http://codex.wordpress.org/Function_Reference/the_excerpt',
        'tiny_mce_before_init' => 'http://codex.wordpress.org/Plugin_API/Filter_Reference/tiny_mce_before_init'
    ) ;
    $hooks = array(
        'wpautop' => 'http://codex.wordpress.org/Function_Reference/wpautop',
        'wptexturize' => 'http://codex.wordpress.org/Function_Reference/wptexturize',
        'shortcode_unautop' => 'https://developer.wordpress.org/reference/functions/shortcode_unautop/'
    ) ;

    //  Loop through filters and hooks checking for anything missing
 
    foreach ($filters as $fkey => $fvalue)
    {
        if (has_filter($fkey))
        {
            $f = array() ;
            foreach (array_keys($wp_filter[$fkey]) as $key => $value)
                $f= array_merge($f, array_keys($wp_filter[$fkey][$value])) ;
            foreach ($hooks as $key => $value)
            {
?>
   	<tr>
    <th><a href="<?php echo $fvalue; ?>"><?php echo $fkey; ?></a> / <a href="<?php echo $value; ?>"><?php echo $key; ?></a></th>
	  <td<?php if (!in_array($key, $f)) echo ' style="color: red;"' ; ?>><?php echo in_array($key, $f) ? __('Present', MAILUSERS_I18N_DOMAIN) : __('Missing', MAILUSERS_I18N_DOMAIN) ; ?></td>
	</tr>
<?php
            }
        }
    }
?>
	</table>
    </div>
<?php
} 


/**
 * Setup Integration with other plugins
 *
 */
add_action( 'plugins_loaded', 'mailusers_plugin_integration' );
function mailusers_plugin_integration()
{
    //  Enable integration with User Groups plugin?
    //  @see http://wordpress.org/plugins/user-groups/

    if (class_exists(MAILUSERS_USER_GROUPS_CLASS)) :
        require_once(plugin_dir_path(__FILE__) . 'integration/user-groups.php') ;
    endif;

    //  Enable integration with User Access Manager plugin?
    //  @see http://wordpress.org/plugins/user-access-manager/

    if (class_exists(MAILUSERS_USER_ACCESS_MANAGER_CLASS)) :
        require_once(plugin_dir_path(__FILE__) . 'integration/user-access-manager.php') ;
    endif;

    //  Enable integration with ItThinx Groups plugin?
    //  @see http://wordpress.org/plugins/groups/

    if (class_exists(MAILUSERS_ITTHINX_GROUPS_CLASS)) :
        require_once(plugin_dir_path(__FILE__) . 'integration/itthinx-groups.php') ;
    endif;
    
    //  Enable integration with PMPro plugin?
    //  @see http://wordpress.org/plugins/paid-memberships-pro/
    if (class_exists(MAILUSERS_PMPRO_CLASS)) :
        require_once(plugin_dir_path(__FILE__) . 'integration/pmpro.php') ;
    endif;

}

/**
 * mailusers_fix_return_path()
 *
 * Fix the bounce (return path) setting if specified.  The return
 * path header isn't recognized correctly in some (most?) cases.
 *
 * @param $mailer mixed PHPMailer instance
 * @see https://wordpress.org/support/topic/bounced-email-testing
 *
 * From the WordPress class-phpmailer.php file:
 *
 * @deprecated Email senders should never set a return-path header;
 * it's the receiver's job (RFC5321 section 4.4), so this no longer does anything.
 * @link https://tools.ietf.org/html/rfc5321#section-4.4 RFC5321 reference
 */
function mailusers_fix_return_path( $phpmailer ) {
    $return_path = mailusers_get_send_bounces_to_address_override() ;

    if (!empty($return_path)) {
        $phpmailer->addReplyTo($return_path);
    }
}
add_action( 'phpmailer_init', 'mailusers_fix_return_path' );
    

if (MAILUSERS_DEBUG) :

//  Load PHPMailer Class
if ( ! class_exists( 'phpmailerException' ) ) :
    require_once(ABSPATH . 'wp-includes/class-phpmailer.php') ;
endif;

//  Define a new "debug" PHPMailer Class
class mailusersDebugPHPMailer {
    public function Send() {
        printf('<div class="error fade"><h3>%s</h3></div>', __('Mail sending aborted.', MAILUSERS_I18N_DOMAIN)) ;
        throw new phpmailerException(__('Mail sending aborted.', MAILUSERS_I18N_DOMAIN)) ;
    }
}

add_action( 'phpmailer_init', 'mailusers_phpmailer_init', 1000 );
function mailusers_phpmailer_init( $phpmailer ) {
    $phpmailer = new mailusersDebugPHPMailer();
}
    
add_filter('phpmailer_init', 'mailusers_debug_phpmailer') ;
/**
 * mailusers_debug_wp_mail()
 *
 * @param $mailer mixed PHPMailer instance
 */
function mailusers_debug_phpmailer($mailer)
{
?>
<div class="postbox-container" style="width: 100%">
        <div class="metabox-holder">
            <div class="meta-box-sortables">
                <div class="postbox" id="first">
                    <div class="handlediv" title="Click to toggle"><br /></div>
                    <h3 class="hndle"><span><?php _e('PHPMailer Debug', MAILUSERS_I18N_DOMAIN); ?></span></h3>
                    <div class="inside">
                    <pre><?php print_r($mailer) ; ?></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form style="display:none" method="get" action="">
        <?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
        <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
    </form>
<?php
}

/**
 * mailusers_debug_wp_mail()
 *
 * @param $to string recipient email address
 * @param $subject string email subject
 * @param $mailtext string email content
 * @param $headers mixed additional email headers
 */
function mailusers_debug_wp_mail($to, $subject, $mailtext, $headers)
{
?>
<div class="postbox-container" style="width: 100%">
        <div class="metabox-holder">
            <div class="meta-box-sortables">
                <div class="postbox" id="first">
                    <div class="handlediv" title="Click to toggle"><br /></div>
                    <h3 class="hndle"><span><?php _e('wp_mail() Debug', MAILUSERS_I18N_DOMAIN); ?></span></h3>
                    <div class="inside">
                    <pre>
<?php
    printf('<br/>') ;
    print_r(htmlentities(print_r($to, true))) ;
    printf('<br/>') ;
    print_r(htmlentities(print_r($subject, true))) ;
    printf('<br/>') ;
    print_r(htmlentities(print_r($mailtext, true))) ;
    printf('<br/>') ;
    print_r(htmlentities(print_r($headers, true))) ;
    printf('<br/>') ;
?>
                    </pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form style="display:none" method="get" action="">
        <?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
        <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>
    </form>
<?php
}
/**
 * Filter testing functions
 */
function mailusers_wp_mail_content_type($x)
{
    error_log(sprintf('%s::%s', basename(__FILE__), __LINE__)) ;
    error_log($x) ;
}
//add_filter('wp_mail_content_type', 'mailusers_wp_mail_content_type') ;

function mailusers_wp_mail_charset($x)
{
    error_log(sprintf('%s::%s', basename(__FILE__), __LINE__)) ;
    error_log($x) ;
}
//add_filter('wp_mail_charset', 'mailusers_wp_mail_charset') ;

function mailusers_wp_mail_from($x)
{
    error_log(sprintf('%s::%s', basename(__FILE__), __LINE__)) ;
    error_log($x) ;
}
//add_filter('wp_mail_from', 'mailusers_wp_mail_from') ;

function mailusers_wp_mail_from_name($x)
{
    error_log(sprintf('%s::%s', basename(__FILE__), __LINE__)) ;
    error_log($x) ;
}
//add_filter('wp_mail_from_name', 'mailusers_wp_mail_from_name') ;

/**
 * Debug functions
 */
function mailusers_preprint_r()
{
    $numargs = func_num_args() ;
    $arg_list = func_get_args() ;
    for ($i = 0; $i < $numargs; $i++) {
	    //printf('<pre style="text-align:left;">%s</pre>', print_r($arg_list[$i], true)) ;
	    error_log(print_r($arg_list[$i], true)) ;
    }
}

function mailusers_whereami($x, $y)
{
	//printf('<h2>%s::%s</h2>', basename($x), $y) ;
	error_log(sprintf('%s::%s', basename($x), $y)) ;
}
endif;

function mailusers_memory_usage($real_usage = false)
{ 
    $mem_usage = memory_get_usage($real_usage); 

    if ($mem_usage < 1024) 
        return $mem_usage." bytes"; 
    elseif ($mem_usage < 1048576) 
        return round($mem_usage/1024,2)."K"; 
    else 
        return round($mem_usage/1048576,2)."M"; 
}

//  Integration testing stubs - change the if statement to '1' to test

if (0):
    //  wpMandrill integration test
    require('examples/wpMandrill.php') ;
endif;

if (0):
    //  mailusers_html_wrapper filter test
    require('examples/mailusers_sample_html_wrapper.php') ;
endif;

if (0):
    //  WPBE integration test
    require('examples/wpbe.php') ;
endif;
?>
