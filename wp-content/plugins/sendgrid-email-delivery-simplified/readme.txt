=== SendGrid ===
Contributors: team-rs
Donate link: http://sendgrid.com/
Tags: email, email reliability, email templates, sendgrid, smtp, transactional email, wp_mail,email infrastructure, email marketing, marketing email, deliverability, email deliverability, email delivery, email server, mail server, email integration, cloud email
Requires at least: 4.2
Tested up to: 4.6
Stable tag: 1.10.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Send emails and upload contacts through SendGrid from your WordPress installation using SMTP or API integration.

== Description ==

What is the SendGrid WordPress Plugin?

SendGrid’s cloud-based email infrastructure relieves businesses of the cost and complexity of maintaining custom email systems. SendGrid provides reliable deliverability, scalability, and real-time analytics along with flexible APIs that make custom integration with your application a breeze.

SendGrid’s WordPress plugin replaces WordPress’s default wp_mail() function by using either an SMTP or API integration with SendGrid to send outgoing email from your WordPress installation. It also allows you to upload contacts directly to your SendGrid Marketing Campaigns account via a subscription widget.

By using the SendGrid plugin, you will be able to take advantage of improved deliverability and an expanded feature set, including tracking and analytics, to enhance user engagement on your WordPress installation. SendGrid also provides world class customer support, should you run into any issues.

For more details, consult our official documentation here : https://sendgrid.com/docs/Integrate/Tutorials/WordPress/index.html

= The Subscription Widget =

SendGrid’s WordPress Subscription Widget makes it easy for people visiting your WordPress site to subscribe to your marketing emails, such as any newsletters, announcements, or promotional offers you may send. Upon signup, they’ll automatically receive an opt-in email allowing them to confirm their desire to begin receiving your emails. This confirmation constitutes “double opt-in,” a deliverability best practice.

For more details, consult the official documentation for the Subscription Widget here : https://sendgrid.com/docs/Integrate/Tutorials/WordPress/subscription_widget.html

= Multisite (Beta) =

If you are using the SendGrid plugin in a Multisite environment, you need to Network Activate it. You can then access the settings page on the network dashboard and the configure settings will be used for all sites.

Configuration on a per-site basis is not currently supported. All configured settings will be applied to all sites.

If you already had the plugin installed in a Multisite environment and you update to versions after 1.9.0 you may need to reconfigure your plugin.


== Installation ==

Requirements:

1. PHP version >= 5.3.0
2. To send emails through SMTP you need to also install the 'Swift Mailer' plugin. ( https://wordpress.org/plugins/swift-mailer/ )
3. If wp_mail() function has been declared by another plugin that you have installed, you won't be able to use the SendGrid plugin

To upload the SendGrid Plugin .ZIP file:

1. Upload the WordPress SendGrid Plugin to the /wp-contents/plugins/ folder.
2. Activate the plugin from the "Plugins" menu in WordPress.
3. Create a SendGrid account at <a href="http://sendgrid.com/partner/wordpress" target="_blank">http://sendgrid.com/partner/wordpress</a>  
4. Navigate to "Settings" -> "SendGrid Settings" and enter your SendGrid credentials

To auto install the SendGrid Plugin from the WordPress admin:

1. Navigate to "Plugins" -> "Add New"
2. Search for "SendGrid Plugin" and click "Install Now" for the "SendGrid Plugin" listing.
3. Activate the plugin from the "Plugins" menu in WordPress, or from the plugin installation screen.
4. Create a SendGrid account at <a href="http://sendgrid.com/partner/wordpress" target="_blank">http://sendgrid.com/partner/wordpress</a>
5. Navigate to "Settings" -> "SendGrid Settings" and enter your SendGrid credentials.

For Multisite:

1. Navigate to "My Sites" -> "Network Admin" -> "Plugins"
2. Click on "Add New"
3. Search for "SendGrid Plugin" and click "Install Now" for the "SendGrid Plugin" listing.
4. Network Activate the plugin from the "Plugins" menu in WordPress, or from the plugin installation screen.
5. Create a SendGrid account at <a href="http://sendgrid.com/partner/wordpress" target="_blank">http://sendgrid.com/partner/wordpress</a>
6. Navigate to "My Sites" -> "Network Admin" -> "Dashboard"
7. Click on the "SendGrid Settings" item in the menu on the left and enter your SendGrid credentials.

= Global Settings =

SendGrid settings can optionally be defined as global variables (wp-config.php):

1. Set credentials (You can use credentials or API key. If using credentials, both need to be set in order to get credentials from variables and not from the database. If using API key you need to make sure you set the Mail Send permissions to FULL ACCESS, Stats to READ ACCESS and Template Engine to READ or FULL ACCESS when you created the api key on SendGrid side, so you can send emails and see statistics on wordpress):
    * Auth method ('apikey' or 'credentials'): define('SENDGRID_AUTH_METHOD', 'apikey');
    * Username: define('SENDGRID_USERNAME', 'sendgrid_username');
    * Password: define('SENDGRID_PASSWORD', 'sendgrid_password');
    * API key:  define('SENDGRID_API_KEY', 'sendgrid_api_key');

2. Set email related settings:
    * Send method ('api' or 'smtp'): define('SENDGRID_SEND_METHOD', 'api');
    * From name: define('SENDGRID_FROM_NAME', 'Example Name');
    * From email: define('SENDGRID_FROM_EMAIL', 'from_email@example.com');
    * Reply to email: define('SENDGRID_REPLY_TO', 'reply_to@example.com');
    * Categories: define('SENDGRID_CATEGORIES', 'category_1,category_2');
    * Template: define('SENDGRID_TEMPLATE', 'templateID');
    * Content-type: define('SENDGRID_CONTENT_TYPE', 'html');
    * Unsubscribe Group: define('SENDGRID_UNSUBSCRIBE_GROUP', 'unsubscribeGroupId');


3. Set widget related settings:
    * Marketing Campaigns API key: define('SENDGRID_MC_API_KEY', 'sendgrid_mc_api_key');
    * Use the same authentication as for sending emails ('true' or 'false'): define('SENDGRID_MC_OPT_USE_TRANSACTIONAL', 'false');
    * The contact list ID: define('SENDGRID_MC_LIST_ID', 'listID');
    * Display the first and last name fields ('true' or 'false'): define('SENDGRID_MC_OPT_INCL_FNAME_LNAME', 'true');
    * First and last name fields are required ('true' or 'false'): define('SENDGRID_MC_OPT_REQ_FNAME_LNAME', 'true');
    * Signup confirmation email subject: define('SENDGRID_MC_SIGNUP_EMAIL_SUBJECT', 'Confirm subscription');
    * Signup confirmation email content: define('SENDGRID_MC_SIGNUP_EMAIL_CONTENT', '&lt;a href="%confirmation_link%"&gt;click here&lt;/a&gt;');
    * Signup confirmation page ID: define('SENDGRID_MC_SIGNUP_CONFIRMATION_PAGE', 'page_id');

= Filters =

Use HTML content type for a single email:

`add_filter('wp_mail_content_type', 'set_html_content_type');

// Send the email 

remove_filter('wp_mail_content_type', 'set_html_content_type');`

Change the email contents for all emails before they are sent:

`function change_content( $message, $content_type ) {   
    if ( 'text/plain' == $content_type ) {
      $message = $message . ' will be sent as text ' ;
    } else {
      $message = $message . ' will be sent as text and HTML ';
    }

    return $message;
}

add_filter( 'sendgrid_override_template', 'change_content' );`

Changing the text content of all emails before they are sent:

`function change_sendgrid_text_email( $message ) {
    return $message . ' changed by way of text filter ';
}

add_filter( 'sendgrid_mail_text', 'change_sendgrid_text_email' );`

Changing the HTML content of all emails before they are sent:

`function change_sendgrid_html_email( $message ) {
    return $message . ' changed by way of html filter ';
}

add_filter( 'sendgrid_mail_html', 'change_sendgrid_html_email' );`

Note that all HTML emails sent through our plugin also contain the HTML body in the text part and that content will pass through the "sendgrid_mail_text" filter as well.

== Frequently asked questions ==

= Is there any official documentation for this plugin ? =

Yes. You can find it here : https://sendgrid.com/docs/Integrate/Tutorials/WordPress/index.html

= What credentials do I need to add on settings page ? =

Create a SendGrid account at <a href="http://sendgrid.com/partner/wordpress" target="_blank">https://sendgrid.com/partner/wordpress</a> and generate a new API key on <https://app.sendgrid.com/settings/api_keys>.

= How can I define a plugin setting to be used for all sites ? =

Add it into your wp-config.php file. Example: `define('SENDGRID_API_KEY', 'your_api_key');`.

= How to use SendGrid with WP Better Emails plugin ? =

If you have WP Better Emails plugin installed and you want to use the template defined here instead of the SendGrid template you can add the following code in your functions.php file from your theme:

`function use_wpbe_template( $message, $content_type ) {   
    global $wp_better_emails;
    if ( 'text/plain' == $content_type ) {
      $message = $wp_better_emails->process_email_text( $message );
    } else {
      $message = $wp_better_emails->process_email_html( $message );
    }

    return $message;
}
add_filter( 'sendgrid_override_template', 'use_wpbe_template', 10, 2 );`

Using the default templates from WP Better Emails will cause all emails to be sent as HTML (i.e. text/html content-type). In order to send emails as plain text (i.e. text/plain content-type) you should remove the HTML Template from WP Better Emails settings page. This is can be done by removing the '%content%' tag from the HTML template.

= Why are my emails sent as HTML instead of plain text ? =

For a detailed explanation see this page: https://support.sendgrid.com/hc/en-us/articles/200181418-Plain-text-emails-converted-to-HTML

= Will contacts from the widget be uploaded to Marketing Campaigns or Legacy Newsletter ? =

The contacts will only be uploaded to Marketing Campaigns.

= What permissions should my API keys have ? =

For the API Key used for sending emails, that is entered on the General tab, the key needs to have Full Access to Mail Send and Read Access to Stats.
For the API Key used for contact upload, that is entered on the Subscription Widget tab, the key needs to have Full Access to Marketings Campaigns.

= Can I disable the opt-in email ? =

No. SendGrid’s Email Policy requires all email addressing being sent to by SendGrid customers be confirmed opt-in addresses.

= Can I change the content of the emails before they are sent ? =

Yes. You must add a filter that does that. You need to declare a function that takes the content of the email as argument and then call add_filter() with the name "sendgrid_mail_text" or "sendgrid_mail_html", depending on what part of the email you want to change.

If you want to change the text content of all the emails before they are sent you can do it like this :

`function change_sendgrid_text_email( $message ) {
    return $message . ' changed by way of text filter ';
}

add_filter( 'sendgrid_mail_text', 'change_sendgrid_text_email' );`

If you want to change HTML content of all the emails before they are sent you can do it like this :

`function change_sendgrid_html_email( $message ) {
    return $message . ' changed by way of html filter ';
}

add_filter( 'sendgrid_mail_html', 'change_sendgrid_html_email' );`

Note that what is changed depends of the content-type that you have set in the settings page or overwritten by way of filter. For "text/plain" only the text part is overwritten by the filter. For "text/html" both the text and the HTML filters are called.

The code snippets above are usually added in the functions.php file of your theme.

= Can I use this plugin with BuddyPress ? =

Yes. Our plugin required special integration with BuddyPress and it's regularly tested to ensure it behaves as expected. If you have noticed issues caused by installing this plugin along with BuddyPress, you can add the following line to your wp-config.php to disable it :

`define('SENDGRID_DISABLE_BUDDYPRESS', '1');`

= Can I use shortcodes to customize the subscription confirmation page ? =

Yes. You need to create custom page and select it from the settings page. You can place any of these shortcodes in the body of that page. Here's an example :

`Hi [sendgridSubscriptionFirstName] [sendgridSubscriptionLastName],
Your email address : [sendgridSubscriptionEmail] has been successfully added.
You'll hear from us soon!`

You need to enable the use of the First Name and Last Name fields from the settings page in order to use the shortcodes for them.

= Does this plugin support Multisite? =

Yes. This plugin has basic Multisite support. You need to Network Activate this plugin. 

The settings for all sites in the network can be configured only by the Network Admin in the Network Admin Dashboard.

== Screenshots ==

1. Go to Admin Panel, section Plugins and activate the SendGrid plugin. If you want to send emails through SMTP you need to install also the 'Swift Mailer' plugin. 
2. After activation "Settings" link will appear. 
3. Go to settings page and provide your SendGrid credentials by choosing the authentication method which default is Api Key. On this page you can set also the default "Name", "Sending Address" and "Reply Address". 
4. If you want to use your username and password for authentication, switch to Username&Password authentication method.
5. If you provide valid credentials, a form which can be used to send test emails will appear. Here you can test the plugin sending some emails. 
6. Header provided in the send test email form. 
7. If you click in the right corner from the top of the page on the "Help" button, a popup window with more information will appear. 
8. Select the time interval for which you want to see SendGrid statistics and charts.
9. You can configure the port number when using SMTP method.
10. You can configure categories for which you would like to see your stats. 
11. You can use substitutions for emails using X-SMTPAPI headers.
12. You can configure the subscription widget.

== Changelog ==

= 1.10.2 =
* Add options to configure subscription widget form (labels, padding)
= 1.10.1 =
* Fixed a javascript error and a PHP warning
= 1.10.0 =
* Added basic Multisite functionality
* WARNING: Multisite users need to network activate this plugin and reconfigure it.
* Fixed an issue where other users would see the SendGrid statistics widget on the dashboard.
= 1.9.5 =
* Fixed an issue with the Reply-To field
= 1.9.4 =
* Added Unsubscribe Group option
* Improved email validation
= 1.9.3 =
* Added BuddyPress integration
* MC API Key is now saved on focusout
* Added posibility of using plain text template for subscription confirmation email
* Added posibility of adding shortcodes to subscription confirmation pages
= 1.9.2 =
* Improved response time on admin dashboard
= 1.9.1 =
* Added filters that allow the change of text or HTML content of all emails before they are sent
* Fixed an issue with the widget admin notice
= 1.9.0 =
* Added the SendGrid Subscription Widget
* The settings page now has tabs to separate the configuration of general settings from the widget settings
* Fixed an issue where a 'gzinflate()' warning was displayed in Query Monitor for each plugin request
* Fixed an issue where the API Key would be deleted from the db if it was set in wp-config
= 1.8.2 =
* Update SendGrid logos
= 1.8.1 =
* Added possibility to override the email template
= 1.8.0 =
* Added SendGrid\Email() for $header
* Fix Send Test form not being displayed issue
= 1.7.6 =
* Updated validation for email addresses in the headers field of the send test email form
* Add ability to have and individual email sent to each recipient by setting x-smtpapi-to in headers
= 1.7.5 =
* Fixed an issue with the reset password email from Wordpress
* Updated validation for email addresses
* Fixed an issue where some errors were not displayed on the settings page
* Add substitutions functionality
= 1.7.4 =
* Fixed some failing requests during API Key checks
* Fixed an error that appeared on fresh installs regarding invalid port setting
= 1.7.3 =
* Add global config for content-type
* Validate send_method and port set in config file
* Be able to define categories for which you would like to see your stats
= 1.7.2 =
* Check your credentials after updating, you might need to reenter your credentials
* Fixed mcrypt library depencency issue
= 1.7.1 =
* BREAKING CHANGE: Don't make update if you don't have mcrypt php library enabled
* Fixed a timeout issue from version 1.7.0
= 1.7.0 = 
* BREAKING CHANGE : wp_mail() now returns only true/false to mirror the return values of the original wp_mail(). If you have written something custom in your function.php that depends on the old behavior of the wp_mail() you should check your code to make sure it will still work right with boolean as return value instead of array
* BREAKING CHANGE: Don't make update if you don't have mcrypt php library enabled
* Added the possibility of setting the api key or username/password empty
* Added the possibility of selecting the authentication method
* Removed dependency on cURL, now all API requests are made through Wordpress
* Sending mail via SMTP now supports API keys
* Security improvements
* Refactored old code
= 1.6.9 =
* Add categories in headers, add errror message on statistics page if API key is not having permissions
= 1.6.8 =
* Update api_key validation
= 1.6.7 =
* Ability to use email templates, fix category statistics, display sender test form if we only have sending errors
= 1.6.6 =
* Remove $plugin variable to avoid conflict with other plugins
= 1.6.5 =
* Add configurable port number for SMTP method, Specify full path for sendgrid php library, Fix special characters and new lines issues
= 1.6.4 =
* Add support for toName in API method, Add required Text Domain
= 1.6.3 =
* Update Smtp class name to avoid conflicts
= 1.6.2 =
* Add Api Keys for authentication, use the last version of Sendgrid library: https://github.com/sendgrid/sendgrid-php/releases/tag/v3.2.0
= 1.6.1 =
* Add unique arguments 
= 1.6 =
* Fix setTo method in SMTP option, update documentation, add link to SendGrid portal
= 1.5.4 =
* Updated the plugin to use the last version of Sendgrid library: https://github.com/sendgrid/sendgrid-php/releases/tag/v3.0.0
= 1.5.3 =
* Fix attachments issue
= 1.5.2 =
* Fix urlencoded username issue
= 1.5.1 =
* Fix wp_remote issue
= 1.5.0 =
* Updated the plugin to use the last version of Sendgrid library: https://github.com/sendgrid/sendgrid-php/releases/tag/v2.2.0
= 1.4.6 =
* Added constants for SendGrid settings
= 1.4.5 =
* Fix changelog order in readme file
= 1.4.4 =
* Fix unicode filename for icon-128x128.png image
= 1.4.3 =
* Update plugin logo, description, screenshots on installation page
= 1.4.2 =
* Added SendGrid Statistics for the categories added in the SendGrid Settings Page
= 1.4.1 =
* Added support to set additional categories
= 1.4 =
* Fix warnings for static method, add notice for php version < 5.3.0, refactor plugin code
= 1.3.2 = 
* Fix URL for loading image
= 1.3.1 = 
* Fixed reply-to to accept: "name <email@example.com>"
= 1.3 =
* Added support for WordPress 3.8, fixed visual issues for WordPress 3.7
= 1.2.1 =
* Fix errors: set_html_content_type error, WP_DEBUG enabled notice, Reply-To header is overwritten by default option
= 1.2 =
* Added statistics for emails sent through WordPress plugin
= 1.1.3 =
* Fix missing argument warning message
= 1.1.2 =
* Fix display for october charts
= 1.1.1 =
* Added default category on sending
= 1.1 =
* Added SendGrid Statistics 
= 1.0 =
* Fixed issue: Add error message when PHP-curl extension is not enabled.

== Upgrade notice ==

= 1.10.2 =
* Add options to configure subscription widget form (labels, padding)
= 1.10.1 =
* Fixed a javascript error and a PHP warning
= 1.10.0 =
* Added basic Multisite functionality
* WARNING: Multisite users need to network activate this plugin and reconfigure it.
* Fixed an issue where other users would see the SendGrid statistics widget on the dashboard.
= 1.9.5 =
* Fixed an issue with the Reply-To field
= 1.9.4 =
* Added Unsubscribe Group option
* Improved email validation
= 1.9.3 =
* Added BuddyPress integration
* MC API Key is now saved on focusout
* Added posibility of using plain text template for subscription confirmation email
* Added posibility of adding shortcodes to subscription confirmation pages
= 1.9.2 =
* Improved response time on admin dashboard
= 1.9.1 =
* Added filters that allow the change of text or HTML content of all emails before they are sent
* Fixed an issue with the widget admin notice
= 1.9.0 =
* Added the SendGrid Subscription Widget
* The settings page now has tabs to separate the configuration of general settings from the widget settings
* Fixed an issue where a 'gzinflate()' warning was displayed in Query Monitor for each plugin request
* Fixed an issue where the API Key would be deleted from the db if it was set in wp-config
= 1.8.2 =
* Update SendGrid logos
= 1.8.1 =
* Added possibility to override the email template
= 1.8.0 =
* Added SendGrid\Email() for $header
* Fix Send Test form not being displayed issue
= 1.7.6 =
* Updated validation for email addresses in the headers field of the send test email form
* Add ability to have and individual email sent to each recipient by setting x-smtpapi-to in headers
= 1.7.5 =
* Fixed an issue with the reset password email from Wordpress
* Updated validation for email addresses
* Fixed an issue where some errors were not displayed on the settings page
* Add substitutions functionality
= 1.7.4 =
* Fixed some failing requests during API Key checks
* Fixed an error that appeared on fresh installs regarding invalid port setting
= 1.7.3 =
* Add global config for content-type
* Validate send_method and port set in config file
* Be able to define categories for which you would like to see your stats
= 1.7.2 =
* Check your credentials after updating, you might need to reenter your credentials
* Fixed mcrypt library depencency issue
= 1.7.1 =
* BREAKING CHANGE: Don't make update if you don't have mcrypt php library enabled
* Fixed a timeout issue from version 1.7.0
= 1.7.0 = 
* BREAKING CHANGE : wp_mail() now returns only true/false to mirror the return values of the original wp_mail(). If you have written something custom in your function.php that depends on the old behavior of the wp_mail() you should check your code to make sure it will still work right with boolean as return value instead of array
* BREAKING CHANGE: Don't make update if you don't have mcrypt php library enabled
* Added the possibility of setting the api key or username/password empty
* Added the possibility of selecting the authentication method
* Removed dependency on cURL, now all API requests are made through Wordpress
* Sending mail via SMTP now supports API keys
* Security improvements
* Refactored old code
= 1.6.9 =
* Add categories in headers, add errror message on statistics page if API key is not having permissions
= 1.6.8 =
* Update api_key validation
= 1.6.7 =
* Ability to use email templates, fix category statistics, display sender test form if we only have sending errors
= 1.6.6 =
* Remove $plugin variable to avoid conflict with other plugins
= 1.6.5 =
* Add configurable port number for SMTP method, Specify full path for sendgrid php library, Fix special characters and new lines issues
= 1.6.4 =
* Add support for toName in API method, Add required Text Domain
= 1.6.3 =
* Update Smtp class name to avoid conflicts
= 1.6.2 =
* Add Api Keys for authentication, use the last version of Sendgrid library: https://github.com/sendgrid/sendgrid-php/releases/tag/v3.2.0
= 1.6.1 =
* Add unique arguments 
= 1.6 =
* Fix setTo method in SMTP option, update documentation, add link to SendGrid portal
= 1.5.4 =
* Updated the plugin to use the last version of Sendgrid library: https://github.com/sendgrid/sendgrid-php/releases/tag/v3.0.0
= 1.5.3 =
* Fix attachments issue
= 1.5.2 =
* Fix urlencoded username issue
= 1.5.1 =
* Fix wp_remote issue
= 1.5.0 =
* Updated the plugin to use the last version of Sendgrid library: https://github.com/sendgrid/sendgrid-php/releases/tag/v2.2.0
= 1.4.6 =
* Added constants for  SendGrid settings
= 1.4.5 =
* Fix changelog order in readme file
= 1.4.4 =
* Fix unicode filename for icon-128x128.png image
= 1.4.3 =
* Update plugin logo, description, screenshots on installation page
= 1.4.2 =
* Added SendGrid Statistics for the categories added in the SendGrid Settings Page
= 1.4.1 =
* Added support to set additional categories
= 1.4 =
* Fix warnings for static method, add notice for php version < 5.3.0, refactor plugin code
= 1.3 =
* Added support for WordPress 3.8, fixed visual issues for WordPress 3.7
= 1.2 =
* Now you can switch between Sendgrid general statistics and Sendgrid WordPress statistics.
= 1.1 =
* SendGrid Statistics can be used by selecting the time interval for which you want to see your statistics.