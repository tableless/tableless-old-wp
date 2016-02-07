=== Plugin Name ===
Contributors: seanbarton
Tags: welcome email, wordpress welcome email, welcome email editor, mail, email, new user email, password reminder, lost password, welcome email attachment, mail attachment, email attachment
Donate link: http://paypal.me/seanbarton
Requires at least: 4.3.1
Tested up to: 4.4.*

Allows you to edit the Wordpress Welcome/Forgot Password Emails to customise the content and even add an attachment. Allows adding of headers to prevent emails going into spam and changes to the text. Also offers a password reminder service accessable via the quick options on the admin users page.

== Description ==

I thought that the Wordpress Welcome Email to both the Admin and the User were very un-user friendly so I wrote this plugin to allow admin members to change the content and headers.

It simply adds a new admin page that has a few options for the welcome email and gives you a list of hooks to use in the text to make the email a little more personal.

Added support whereby the admin notification can be turned off or a different admin (or admins, support for multiple recipients) can be notified. Plenty of hooks to make the emails as customisable as possible.

A reminder email service has now been added whereby the admin user can send a reminder to any particular user. This can be the original welcome email or a separate template configured on the Welcome Email Editor settings page.

Please email me or use the support forum if you have ideas for extending it or find any issues and I will be back to you as soon as possible.

I would recommend the use of an SMTP service with any Wordpress plugin. A large amount of emails fall needlessly into Spam bins across the world (I get a fair amount of comment approval spam to deal with) because the Wordpress site uses Sendmail to deliver email. I noticed an immediate improvement when using SMTP to send. It's really easy so there's no excuse :) 

== Changelog ==
<V1.6 - Didn't quite manage to add a changelog until now :)

V1.6 - 25/3/11 - Added user_id and custom_fields as hooks for use

V1.7 - 17/4/11 - Added password reminder service and secondary email template for it's use

V1.8 - 24/8/11 - Added [admin_email] hook to be parsed for both user and admin email templates instead of just the email headers

V1.9 - 24/10/11 - Removed conflict with User Access Manager plugin causing the resend welcome email rows to now show on the user list

V2.0 - 27/10/11 - Moved the user column inline next to the edit and delete user actions to save space

V2.1 - 17/11/11 - Added multisite support so that the welcome email will be edited and sent in the same way as the single site variant

V2.2 - 12/12/11 - Added edit box for the subject line and body text for the reminder email. Added option to turn off the reminder service

V2.3 - 16/12/11 - Broke the reminder service in the last update. This patch sorts it out. Also tested with WP 3.3

V2.4 - 03/01/12 - Minor update to disable the reminder service send button in the user list. Previously only stopped the logging but the button remained

V2.5 - 18/01/12 - Minor update to resolve double sending of reminder emails in some cases. Thanks to igorii for sending the fix my way before I had a moment to look myself :)

V2.6 - 30/01/12 - Update adds functionality for reset/forgot password text changes (not formatting or HTML at the moment.. just the copy). Also adds a new shortcode for admin emails for buddypress custom fields: [bp_custom_fields]

V2.7 - 01/02/12 - Minor update adds site wide change of from address and name from plugin settings meaning a more consistent feel for your site. Also reminder email and welcome email shortcode bugs fixed.

V2.8 - 02/02/12 - Minor update fixes sender bug introduced by V2.7

V2.9 - 05/02/12 - Minor update fixes bug which was overriding the from name and address for all wordpress and plugin emails. Now lowered the priority of the filter and have made the global usage of the filter optional via the admin screen. Added labels to the admin screen as the list was getting rather long!

V3.0 - 16/02/12 - Minor update fixes a few coding inconsistencies. With thanks to John Cotton for notifying and fixing these issues on my behalf.

V3.1 - 17/02/12 - Minor update fixes a minor notice showing up on sites with error reporting set to ALL (or anything to include PHP notices)

V3.2 - 21/02/12 - Copy/paste error which broke the reminder email system. My apologies!

V3.3 - 05/05/12 - Buddypress custom fields shortcode now checks for existence of itself before querying nonexistent tables.

V3.4 - 22/05/12 - Minor update.. added [date] and [time] shortcodes to the template

V3.5 - 16/01/13 - Minor update.. Found conflict with S2Member where the FROM address information wasnt being respected. Fixed the conflict

V3.6 - 21/01/13 - Minor update. Moved menu to the settings panel and renmaed to SB Welcome Email so that it fits on one line.

V3.7 - 27/02/13 - Minor update. Added ability to have an attachment with the welcome email. Moved the admin page into the settings menu.

V3.8 - 14/05/13 - Minor update. Removed reminder email functionality

V3.9 - 23/05/13 - Minor update. Added code recommended by 'http://forum.ait-pro.com/forums/topic/bps-pro-5-8-conflict-with-other-email-plugin/'

V4.0 - 20/07/15 - Added some code to force this plugin to the top of the load order to reduce conflict with other plugins. Alsot sorted out those dodgy radio buttons on the settings page!

V4.1 - 22/07/15 - Replaced reference to get_usermeta in favour of get_user_meta. Also added a load of filters and actions for third party developers to hook into.

V4.2 - 30/07/15 - Removed a potential security issue in the admin page loader. Nothing huge but it would allow arbitrary functions to be called by admin users via the URL.

V4.3 - 07/09/15 - Major fixes in this version...Fixed Reset Password email template and send. Added a fix for the new wp_new_user_notification function change. Admin no longer gets the password in plaintext so have replaced with ***** to highlight this. Added [post_data] for admin as it might contain useful information. Added system to convert legacy users into the new format. A run once system to help the transition.

V4.4 - 08/09/15 - Some people were not seeing the upgrade banner on the site. I have bumped the version number to trigger an upgrade

V4.5 - 23/09/15 - Annoyingly WP updated the wp_new_user_notification again. This moved the second to the third parameter therefore breaking this plugin again. Fixed now!

V4.6 - 23/09/15 - Added fix for the lost password email user_login sometimes not being a string. Many thanks to the forum members for this solution

== Installation ==

1. Upload the contents of the ZIP file to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit the admin page it creates at the bottom of the left menu
4. Edit the settings as desired and click save.

Once complete, all new user emails will be sent in the new format.

== Screenshots ==

Don't look at screenshots of admin pages... Just give it a go :) If you must then see the following address for more information...

Screenshots available at: http://www.sean-barton.co.uk/wordpress-welcome-email-editor/

... these are quite old now so perhaps just install it and have a look for yourself ;)