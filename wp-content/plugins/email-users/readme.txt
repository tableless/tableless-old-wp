=== Email Users ===
Contributors: vprat, mpwalsh8, marvinlabs
Donate link: http://michaelwalsh.org/wordpress/wordpress-plugins/email-users/
Tags: email, users, list, admin
Requires at least: 3.6.1
Tested up to: 4.5.2
Stable tag: 4.8.2
License: GPL

== Description ==

A plugin for WordPress which allows you to send an email to the registered blog users. Users can send personal emails to each other. Power users can email groups of users and even notify group of users of posts.

== Other ==

All the instructions for installation, the support forums, a FAQ, etc. can be found on the [plugin home page](http://wordpress.org/extend/plugins/email-users/) or on the [plugin overview page](http://michaelwalsh.org/wordpress/wordpress-plugins/email-users/).

= Translation =

Email Users has language translation support for a number of languages.  New languages and updates to existing languages are always welcome.  Thank you to the people who have provided these translations.

1. Spanish (es_ES) - Ponç J. Llaneras (last updated:  4.6.3)
1. Bulgarian (sr_RS) - [Borisa Djuraskovic](http://www.webhostinghub.com/) (last update 4.6.2)
1. Italian (it_IT) - ? (last updated 4.5.1)
1. German (de_DE) - Tobias Bechtold (last updated 4.4.1)
1. Persian (fa_IR) - ? (last updated 4.3.6)
1. French (fr_FR) - Emilie DCCLXI (last updated 4.3.6)
1. Russian (ru_RU) - ? (last updated 4.3.8)
1. Chinese (zh_CN) - ? (last updated 4.5.1)
1. Dutch (nl_NL) - Bart van Strien (last updated 4.6.3)


== License ==

This plugin is available under the GPL license, which means that it's free. If you use it for a commercial web site, if you appreciate my efforts or if you want to encourage me to develop and maintain it, please consider making a donation using Paypal, a secured payment solution. You just need to click the donate button on the the [plugin overview page](http://michaelwalsh.org/wordpress/wordpress-plugins/email-users/) and follow the instructions.

== Frequently Asked Questions ==

= Does Email Users work with "insert your favorite groups or user management plugin here"? =

Email Users currently supports integration with three (3) groups / user management plugins:

1. [User Groups](http://wordpress.org/plugins/user-groups/)
1. [User Access Manager](http://wordpress.org/plugins/user-access-manager/)
1. [ItThinx Groups](http://wordpress.org/plugins/groups/)

If any of these plugins are enabled you see additional group recipients to choose on the Notify and Send to Groups page (assuming there are users in those groups who received mass email and/or notifications).

= Will you add integration with "insert your favorite groups or user management plugin here"? =

Maybe.  If the plugin stores its information as user meta data, you can probably use the Custom Meta Filter functionality to create your own integration.  Other plugins will be considered on a case by case basis and adding integrations tends to be a very low priority.

= My hosting provider limits the number of emails that can be sent.  Does Email Users support this? =

No.  Email Users does not have any "queueing" capability nor is it planned.  Email Users utilizes WordPress' [wp_mail()](http://codex.wordpress.org/Function_Reference/wp_mail) API for sending email.  It is technically possible to develop a plugin that hooks into wp_mail() using [wp_cron()](http://codex.wordpress.org/Function_Reference/wp_cron) that would implement queueing but that is beyond the scope of Email Users.

= How do I know my users are receiving email? =

Unfortunately there is no way to know for sure.  Email Users has a "debug" mode which will allow you to see the composition of the email headers.  Examining this data will allow you to confirm the expected addresses are indeed in the headers.  What happens to the actual email once it is sent using wp_mail() is out of Email Users control and there is no way using WordPress to verify the email was sent and received.

== Filters and Actions ==

Email Users supports a number of filters and actions.
1.  Action:  mailusers_before_wp_mail - called before wp_mail is called.
1.  Action:  mailusers_after_wp_mail - called after wp_mail is called.
1.  Filter:  mailusers_manipulate_headers - called before wp_mail is called.

This example shows how the mailusers_manipulate_headers filter can be used to change the headers to be compatible with [wpMandrill](https://wordpress.org/plugins/wpmandrill/).  This code could/would be placed in your [functions.php](http://codex.wordpress.org/Functions_File_Explained) file.

`
/**
 * wpMandrill needs the recipients in the TO header instead
 * of the BCC header which Email Users uses by default.  This
 * filter will move all of the recipients from the BCC header
 * into the TO header and clean up any formatting and then nuke
 * the BCC header.
 *
 */
function mailusers_mandrill_headers($to, $headers, $bcc)
{
    //  Copy the BCC headers to the TO header without the "Bcc:" prefix
    $to = preg_replace('/^Bcc:\s+/', '', $bcc) ;

    //  Empty out the BCC header
    $bcc = array() ;

    return array($to, $headers, $bcc) ;
}

add_filter('mailusers_manipulate_headers', 'mailusers_mandrill_headers', 10, 3) ;
`
== Custom Filter Usage ==

Email Users provides the ability to send email to a very specific set of users using a custom meta filter.  To create a special mail list, you will need to add something similar to the following to your theme's functions.php file or create a separate plugin file.

The mailusers_register_user_custom_meta_filter() and mailusers_register_group_custom_meta_filter() actions each take 3-4 parameters:
1.  Label - text that will appear on the WordPress Email-Users menu (users) or in the Recipient List (groups).
1.  Meta Key - the meta key to search for in the user meta table.
1.  Meta Value - the value to match against in the user meta table.
1.  Meta Compare - optional, defaults to '='.  The type of comparison to be performed.

This example will filter the user list to only those users where the first name is Alex.
`
add_action( 'mailusers_user_custom_meta_filter', 'first_name_alex', 5 );

function first_name_alex()
{
    mailusers_register_user_custom_meta_filter('First Name: Alex', 'first_name', 'Alex');
}
`

Regular SQL comparisons (=, !=, etc.) can be performed.  Wildcard matches (LIKE, NOT LIKE) are not yet supported due to how the WordPress get_users() API currently handles LIKE comparison.  A patch has been submitted and hopefully it will be addressed in WordPress 3.6.  Once addressed, you will be able to create filters like the one below to specifically match last names which begin with the letter M.

`
add_action( 'mailusers_user_custom_meta_filter', 'last_names_starting_with_m', 5 );

function last_names_starting_with_m()
{
    mailusers_register_user_custom_meta_filter('Last Name: M', 'last_name', 'M%', 'LIKE');
}
`

In addition to filtering on User Meta data to build a custom list of users, you can now define custom groups based on User Meta data.

`
add_action( 'mailusers_group_custom_meta_filter', 'send_to_fire_department', 5 );

function send_to_fire_department()
{
    mailusers_register_group_custom_meta_filter('Fire Department', 'department', 'fire');
}


add_action( 'mailusers_group_custom_meta_filter', 'send_to_police_department', 5 );

function send_to_police_department()
{
    mailusers_register_group_custom_meta_filter('Police Department', 'department', 'police');
}
`

In addition to defining specific Meta Key and Value pairs, Email Users also supports a filter to generate the Meta Group filters based on a Meta Key.  The Meta Key filter supports two optional arguments - a Meta Value and a function callback to generate the label.  Neither is required.  When the label callback is used, it receives two arguments, both strings, the Meta Key and Meta Value.  It must return a string.

`
//  Define action to send to blog followers
add_action( 'mailusers_group_custom_meta_key_filter', 'send_to_my_blog_followers', 5 );

function send_to_my_blog_followers()
{
    mailusers_register_group_custom_meta_key_filter('blog_follower');
}

function send_to_departments_label($mk, $mv)
{
    return(ucwords($mk) . ' = ' . ucwords($mv)) ;
}

//  Define action to send to departments using custom callback to generate the label
add_action( 'mailusers_group_custom_meta_key_filter', 'send_to_departments', 5 );

function send_to_departments()
{
    mailusers_register_group_custom_meta_key_filter('department', null, 'send_to_departments_label');
}

function send_to_departments_label($mk, $mv)
{
    return(ucwords($mk) . ' = ' . ucwords($mv)) ;
}
`

New in v4.5.0 is an action, *mailusers_update_custom_meta_filters*, which can be used to dynamically update Meta Filters before they're used for recipient selection or email address retrieval.  The example below leverages the Meta Key *Department* and its various values to define and update a new Meta Key called *publicworks*.  Anytime a Group Email is sent or Post/Page notification is initiated, this action will fire and rebuild the *publicworks* meta key based on the values of the *department* meta key.  This sort of action could be used to create more complex meta value relationships or to integrate other plugins.

`
add_action( 'mailusers_group_custom_meta_filter', 'send_to_public_works', 5 );

function send_to_public_works()
{
    mailusers_register_group_custom_meta_filter('Public Works', 'publicworks', true);
}

add_action( 'mailusers_update_custom_meta_filters', 'update_publicworks_meta_filter', 5 );

function update_publicworks_meta_filter()
{
    $pw_mk = 'publicworks' ;
    $dept_mk = 'department' ;

    //  Define the valid matches - the array keys match user
    //  meta keys and the array values match the user meta values.
    //
    //  The array could contain a mixed set of meta keys and values
    //  in order to group users based on an arbitrary collection of
    //  user meta data.

    $publicworks = array(
        array($dept_mk => 'fire'),
        array($dept_mk => 'police'),
        array($dept_mk => 'water and sewer'),
        array($dept_mk => 'parks and recreation'),
    ) ;

    //  Remove all instances of the Public Works meta key
    //  to account for employees no longer with Public Works
	$uq = new WP_User_Query(array('meta_key' => $pw_mk)) ;

    foreach ($uq->get_results() as $u)
        delete_user_meta($u->ID, $pw_mk) ;

    //  Loop through the departs and select Users accordingly
    foreach  ($publicworks as $pw)
    {
    	$uq = new WP_User_Query(array('meta_key' => $dept_mk, 'meta_value' => $pw[$dept_mk])) ;

        //  Loop through the users in the department and tag them as Public Works employees
        foreach ($uq->get_results() as $u)
            update_user_meta($u->ID, $pw_mk, true) ;
    }
}
`

== Changelog ==

= Version 4.8.2 =
* Addressed deprecated warnings for get_currentuserinfo() in WordPress 4.5.
* Added setting to turn the Post/Page Notification widget on the edit screen on or off.
* Added setting to turn the Post/Page Notification widget on the Dashboard menu on or off.
* Added option to define custom header similar to existing footer.
* Added options to apply custom header and footer to user user and group email and/or notificatins.

= Version 4.8.1 =
* Added option to disabled jQuery enhanced recipient selection (revert to classic select boxes).
* Removed leftover debug code which left information in the error_log.
* Updated translation files with missing text.

= Version 4.8.0 =
* Integrated jQuery Chosen plugin to enhance select lists when choosing recipients.
* Added Danish translation (thank you Thomas Canell)
* Added BCC options of 2, 3, and 5 for more granular control.
* Added new example (remove_menus.php) to show how to remove the Email Users Dashboard menu for all users but Administrators.
* Fixed broken links referring to Marvin Labs.

= Version 4.7.10 =
* Added new setting to control applying post content with wpautop prior to seending an update to th team.
* Changed mechanism for handling bounce email address.  Added warning to note bounce addresses have proven to be unreliable.
* Added User Email as an option for user sorting and display in selection lists.

= Version 4.7.9 =
* Fixed bug in "Test Notification Email" feature on Settings page which prevented the test email from being sent.

= Version 4.7.8 =
* Fixed bug in Post/Page Notification Dasboard menu resulting in bad post ID error message.
* Fixed Mandrill sample integration to account for selecting a single user.
* Added explicit header setting for bounces which is unlikely to work based on comments in WordPress' class-phpmailer.php file.

= Version 4.7.7 =
* Fixed bug in User Settings which prevented bulk setting user Notification and Mass Email settings.

= Version 4.7.6 =
* Fixed integration bug with ItThinx Groups plugin due to PHP 5.4 and 5.5 differences.  Tested with Groups 1.7.1 and WordPress 4.2.2.
* Fixed security concern raised by WordPress.com.

= Version 4.7.5 =
* Fixed bug in footer text setting introduced during WPBE testing.

= Version 4.7.4 =
*  Resolved a number of PHP Strict Standard notices resulting from calling non-static functions statically.

= Version 4.7.3 =
*  Changed plugin activation hook to handle sites with large amounts of users.  This addresses an out of memory bug reported on the WordPress Support Forum.  The get_users() function returns a large amount of information for each user by default, the activation hook only needs the ID field.
* Added example to demonstrate filter usage to allow Email Users to work with WP Better Emails.

= Version 4.7.2 =
*  Added *mailusers_html_wrapper* filter to allow sites to customize the HTML which is wrapped around the message text.  When using this hook, the hook implementation is responsible for adding all of necessary HTML necessary for a valid document.  There is an example usage in the /examples directory within the plugin.

= Version 4.7.1 =
*  Tightened up the max_input_vars check to account for older versions of PHP which do not have this setting.
*  Added support for the WordPress [Editable Roles Filter](http://codex.wordpress.org/Plugin_API/Filter_Reference/editable_roles) per a request from the [WordPress Support Forum](https://wordpress.org/support/topic/mailusers_get_roles-function-to-use-the-core-get_editable_roles?replies=2#post-6513328).
*  Added filter to facilitate manipulating mail headers.  This is primarily targeted at wpMandrill support but can be used for other purposes.

= Version 4.7.0 =
* Added code to detect scenario where number of email recipients could potentially exceed the web server's ability to process it (PHP's max_input_vars setting).  A warning is displayed to the user when this situation is detected.
* Swedish translation added (thank you Elger Lindgren).

= Version 4.6.11 =
* Fixed bug with excerpt - excerpt was not being extracted from post properly.
* Added information to Dashboard widget to show status of filters which may affect Email Users.
* Fixed duplicate MIME-Version header per [Support Forum bug report](https://wordpress.org/support/topic/duplicate-mime-version-header?replies=1#post-6230950).

= Version 4.6.10 =
* Fixed bug with from_name which happens in certain circumstances.
* Added ability to edit post/page/cpt email content and subject.
* Added _mailusers_before_wp_mail_ and _mailusers_before_wp_mail_ hooks to allow doing actions before and after calling wp_mail().
* Fixed malformed email header when omitting display names [per Support Forum bug report](https://wordpress.org/support/topic/bug-report-can-not-set-sender-with-omit-option-on#post-5939774).
* Preliminary work to support option to Base64 encode email [per Support Forum request](http://wordpress.org/support/topic/chinese-character-encoding-problem-on-ios-device?replies=3#post-5931091) to better support mobile devices.  This feature is not currently enabled.
* Updated German translations (thank you Dr. Dieter Menne).

= Version 4.6.9 =
* Removed references to deprecated WordPress API function format_to_post().
* Fixed bug where %FROM_NAME% substitution was not handled properly with Sender Name overrides.
* Added author and blog keyword substitution to user and group emails.
* Updated German translation (thank you Dr. Dieter Menne)

= Version 4.6.8 =
* Added Finnish translation (thank you Juga Paazmaya)
* Replaced calls to mysql_real_escape_string() with esc_sql() for PHP 5.5 compatibility.

= Version 4.6.7 =
* Fixed problem with User Access Manager integration which resulted in sending email to all users regardless of UAM group assignment.

= Version 4.6.6 =
* Added new option to filter users with no role from the User Recipient List.
* Bumped supported version of WordPress to 3.6.1.
* Added ability to have recipient appear in To: list instead of Bcc: list when using BCC option of 1.
* Fixed internationalization of BCC options.
* Fixed problem with User Groups when using non-English versions of WordPress or groups which contain hyphen characters.

= Version 4.6.5 =
* Fixed sorting issue which was caused by commenting out code for debugging purposes to resolve problem fixed in 4.6.4.

= Version 4.6.4 =
* Fixed bug which caused first and last names to display as N/A in some instances.
* Updated Dutch translation support (thank you Bart van Strien).

= Version 4.6.3 =
* Fixed several strings which were not properly set up for language translation.
* Re-added French translation file as it had gotten corrupted somehow and wouldn't load in WordPress.
* Added Dutch translation support (thank you Bart van Strien).
* Fixed bug where sites with large numbers of users would exhaust memory.
* Updated Spanish translation (thank you Ponc J. Llaneras)
* Added additional options for BCC limit.
* Fixed several CSS bugs on Settings page.
* Initial implementation of Paid Memeberships Pro integration.

= Version 4.6.2 =
* Refactored integration with other "Groups" plugins.
* Added integration support with ItThinx Groups plugin.
* Fixed language translation issues with several strings on Options page.
* Updated Bulgarian language translation files.  (thank you Borisa Djuraskovic)

= Version 4.6.1 =
* Updated Spanish language translation files. (thank you Ponç J. Llaneras)
* New Bulgarian language translation files.  (thank you Borisa Djuraskovic)
* Fixed formatting issue in the plugin README file.

= Version 4.6.0 =
* Significantly improved debug functionality to chase down mail header issues.
* Check added to determine if wp_mail() has been overloaded by a theme or plugin.
* Rewrite of mailusers_send_mail() fucntion to construct headers as arrays insyead of as string.  The string would sometimes not break correctly and recognize the Bcc: field.
* Implemented new email footer option.
* Cleaned up presentation of Options page.
* Implemented new omit display names option.
* Updated language translation files.

= Version 4.5.5 =
* Forgot to register settings for new MIME-Type and X-Mailer settings.

= Version 4.5.4 =
* Version bump due to bad tagging in WordPress plugin repository.

= Version 4.5.3 =
* Fixed bug in Test Notification which failed to incorporate %POST_AUTHOR% keyword.
* Replaced bold font in Email-Users Info meta box on settings page.
* Added %POST_CONTENT% substitution keyword.
* Refactored construction of email headers.
* Added option to specifically CC sender.
* Fixed sorting problems on User Settings page.
* Added Search to User Settings page.
* Replaced First/Last Names on User Settings page with Display Name - needed to eliminate complex query.
* Replaced complex SQL query with proper call to get_users() for User Settings page, facilitated fixing several bugs on User Settings page.
* Fixed bug which preventing showing all posts and/or pages in Notify dropdown.
* Added translation for value of Role when used in select boxes.
* Resolved duplicate MIME-Version and X-Mailer header problem.
* Added new options to optionally add MIME-Version and X-Mailer headers as by default, they are added by WordPress and shouldn't be added by Email Users.
* Improved Information Panel on Email Users settings page, now shows status of any filters which could affect Email Users.

= Version 4.5.2 =
* Added Dashboard Widget to report number of users who accept each type of email and default settings.
* Added Message to Settings page to warn Admin when no users will not receive emails.
* Added Meta Box to Settings page to report number of users who accept each type of email.

= Version 4.5.1 =
* Fixed Post Excerpt to use WordPress API to allow usage of filters.
* Added support for %POST_AUTHOR% keyword replacement.
* Added Italian language translation.

= Version 4.5.0 =
* Added CSS class and ID to Post and Page Notification post boxes so they can be styled or easily hidden via CSS.
* Added integration with User Groups plugin.
* Added integration with User Access Manager Plugin.
* Cleaned up recipient selection for Group Email and Post/Page Notifications so it includes Filters, Roles, and groups from integrated plugins (when enabled).
* Added integration pane to Settings page to note which plugins Email-Users recorgnizes and enables integration with.
* Added *mailusers_update_custom_meta_filters* action to update of dynamic meta filters prior to their use.  Useful to creating and updating meta values based on other meta data or plugins.

= Version 4.4.4 =
* Bumped version because the tag wasn't done correctly.

= Version 4.4.3 =
* Fixed typo which prevented saving Sender Exclude option.
* Fixed bug with User Meta filters.
* Fixed bug with duplicate emails being sent in some instances when both Roles and Users selected.

= Version 4.4.2 =
* Fixed bug which caused email to be sent to all recipients instead of just those in a specific group.
* Addressed deprecated update_usermeta() usage.

= Version 4.4.1 =
* Added German translation files (thank you Tobias Bechtold).
* Fixed bug in Send to Groups where number of users receiving email was wrong.
* Added internationalization support to Send to Group status messages.
* Added support for sending email to users based on a custom meta filter.
* Added support for sending email to groups based on a custom meta filter.
* Added suppport for providing a bounce email address.
* Fixed bug in Options form which prevented translation of strings.
* Added support for defining email group meta filters based on meta key.
* Implemented solution from @maximinime to fix lost Email-Users settings.
* Removed invalid references to Marvin Labs.
* Fixed bug where Post Excerpt wasn't being used in email when present.
* Updated French and Spanish language files.

= Version 4.3.21 =
* Updated Spanish translation files.

= Version 4.3.20 =
* ReadMe file updates.

= Version 4.3.19 =
* Fixed missing DIV on landing page causing footer to appear in wrong spot.
* Added some more marketing information.
* Tweaked some more wording to be consistent with other areas of the plugin.
* Removed page layout development code.

= Version 4.3.18 =
* Updated plugin landing page to be cleaner and use modern WordPress styling.

= Version 4.3.17 =
* Fixed "%FROM_NAME%" does not get replaced properly in notifications when using override.
* Updated Plugin Settings page to be cleaner and use modern WordPress styling.

= Version 4.3.16 =
* Fixed "%FROM_NAME%" does not get replaced in notifications

= Version 4.3.15 =
* Replaced use of deprecated function *the_editor()* with *wp_editor().
* Fixed Javascript conflict which affects Dashboard and Menu Management resulting from enqueing the WordPress *'post'* library.
* Fixed bug where user settings are not saved correctly when toggling user setting control.
* Fixed bug when the dollar sign character ($) appears in the content of a page or post.
* Added option to include sender in recipient list.
* Numerous updates to make translation easier.
* Updated Spanish and French translation files.

= Version 4.3.14 =
* Bump in version number because one was missed in 4.3.13 preventing automatic updates from the WordPress plugin repository.  Duh.

= Version 4.3.13 =
* Bump in version number because one was missed in 4.3.12 preventing automatic updates from the WordPress plugin repository.

= Version 4.3.12 =
* Fixed bug(s) which prevented users with capabilities to email other users from doing so.
* Initial inclusion of Spanish language translation files (courtesy of Ponç J. Llaneras).
* Updated French language translation files.

= Version 4.3.11 =
* Fixed problem when using BCC limits where the last "chunk" of addresses were never sent the email.

= Version 4.3.10 =
* Fixed a problem with the "To:" header when sending email to a single user which appeared on some platforms (e.g. one IIS system that I know of).

= Version 4.3.9 =
* Removed some debug messages which slipped through, one of which caused a PHP error.

= Version 4.3.8 =
* Fixed a problem with Send to Users ignoring the Mass Email setting when selecting multiple users.
* Added messages to alert user when addresses were filtered out due to Mass Email setting.
* Added internationalization support for some additional messages.
* Inclusion of Russian language translation files.
* Fixed bug which resulted in duplicate emails for recipients when both roles and users were selected.

= Version 4.3.7 =
* Fixed minor typos.
* Fixed version number so Dashboard update will kick off.

= Version 4.3.6 =
* Fixed bug in User Settings Table Rows setting which stored number of rows in the wrong option field.
* Added options to set an "override" From Name and/or From Email Address which can be used when sending Mass or Post/Page Notifications.
* Added ability to override sender name and/or email address when sending Mass Email or Post/Page Notifications.  The default remains to use the name and email address from the currently logged in user.  When the Override Address is set on the Email Users Plugin Settings Page, the user will be presented with a Radio Button choice on email and notification pages where they can send the email using their login (default behavior) or select the Override Address and From Name.
* Added new option to enable process short codes embedded in posts and pages when sending notifications.
* Fixed a bunch of text messages to support translation.
* Initial inclusion of French language translation files (courtesy of Emilie DCCLXI).
* Inclusion of Persian language translation files.
* Fixed bug on User Settings page where number_format() warning was issued.  Reported on the WordPress.org Support Forum.

= Version 4.3.5 =
* Added some more values for the BCC limit setting
* Corrected one message for translation

= Version 4.3.4 =
* Fixed bug which caused some user recipients to be reported as having invalid email addresses.
* Added translation support to several error messages where it was missing.
* Fixed several more typos.

= Version 4.3.3 =
* Fixed typos which appears on the user profile page for the options to receive email and notifications.
* Added an option to allow the Admin User (requires role 'edit_users') to enable or disable users ability to control their Email Users settings.  By default users can control their own settings.

= Version 4.3.2 =
* Hid "Notify Users" submenu on Pages and Posts Menu for users who don't have the proper capability.
* Fixed problem where debug code was preventing mail from being sent to users.

= Version 4.3.1 =
* Migrated custom SQL query over to WordPress get_users() API.  Use of this API requires WordPress 3.3 or later.
* Fixed SQL bug in User Settings table when changing the column sort.
* Fixed plugin activation error which appears when running WordPress 3.4.

= Version 4.3.0 =
* Replaced slow, inefficient SQL queries to build "nice" user names with more efficient queries.  Thanks to the WP Hackers mailing list for the assistance.

= Version 4.2.0 =
* Fixed serious flaw in User Setting implementation which uses the WP_List_Table class.  The logic was not accounting for large number of users and would slow to a crawl because it was processing the entire list of users instead of just a subset for each page.

= Version 4.1.0 =
* Fixed bug which prevented default settings for a user from being added when a user was registered.
* Added new plugin options to set default state for notifications and mass email.  It is now possible to default new users to any combination of email and notifications settings.
 
= Version 4.0.0 =
* Code updated to use WordPress Menu API for Dashboard Menus.
* Code updated to use WordPress Options API for plugin settings.
* Updated plugin to eliminate WordPress deprecated function notices.
* Added new User Settings page to the pluin menu where bulk settings can be applied to one or more users.  This page makes reviewing user settings much easier than looking at users one at a time.

== Installation ==

Use the automatic installer from within the WordPress admin, or:

1. Download the .zip file by clicking on the Download button on the right
1. Unzip the file
1. Upload the email-users directory to your plugins directory
1. Go to the Plugins page from within the WordPress administration
1. Click Activate for Email Users
1. After activation a new Email Users options menu will appear under Settings.
1. Visit the Settings page to adjust your Email Users settings as needed.

You can now start sending email or notifications.

== Upgrade Notice ==

After updating Email Users, visit the Plugin Settings page (Dashboard->Settings->Email Users) and check for any new options.  Make any necessary adjustments and save the options.  A notice will appear on the Settings page until the options are saved noting that the plugin version has been updated.
