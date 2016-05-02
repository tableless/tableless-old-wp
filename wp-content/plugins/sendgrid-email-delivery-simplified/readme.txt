=== SendGrid ===
Contributors: team-rs
Donate link: http://sendgrid.com/
Tags: email, email reliability, email templates, sendgrid, smtp, transactional email, wp_mail,email infrastructure, email marketing, marketing email, deliverability, email deliverability, email delivery, email server, mail server, email integration, cloud email
Requires at least: 3.3
Tested up to: 4.4
Stable tag: 1.8.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Send emails throught SendGrid from your WordPress installation using SMTP or API integration.

== Description ==

SendGrid's cloud-based email infrastructure relieves businesses of the cost and complexity of maintaining custom email systems. SendGrid provides reliable delivery, scalability and real-time analytics along with flexible APIs that make custom integration a breeze.

The SendGrid plugin uses SMTP or API integration to send outgoing emails from your WordPress installation. It replaces the wp_mail function included with WordPress. 

First, you need to have PHP-curl extension enabled. To send emails through SMTP you need to install also the 'Swift Mailer' plugin. 

To have the SendGrid plugin running after you have activated it, go to the plugin's settings page and set the SendGrid credentials, and how your email will be sent - either through SMTP or API.

You can also set default values for the "Name", "Sending Address" and the "Reply Address", so that you don't need to set these headers every time you want to send an email from your application.

You can set the template ID to be used in all your emails on the settings page or you can set it for each email in headers.

You can have an individual email sent to each recipient by setting x-smtpapi-to in headers: `'x-smtpapi-to: address1@sendgrid.com,address2@sendgrid.com'`. Note: when using SMTP method you need to have also the `to` address set (this may be dummy data since will be overwritten with the addresses from x-smtpapi-to) in order to be able to send emails. 

Emails are tracked and automatically tagged for statistics within the SendGrid Dashboard. You can also add general tags to every email sent, as well as particular tags based on selected emails defined by your requirements. 

There are a couple levels of integration between your WordPress installation and the SendGrid plugin:

* The simplest option is to Install it, Configure it, and the SendGrid plugin for WordPress will start sending your emails through SendGrid.
* We amended wp_mail() function so all email sends from WordPress should go through SendGrid. The wp_mail function is sending text emails as default, but you have an option of sending an email with HTML content.

How to use `wp_mail()` function:

We amended `wp_mail()` function so all email sends from WordPress should go through SendGrid.

You can send emails using the following function: `wp_mail($to, $subject, $message, $headers = '', $attachments = array())`

Where:

* `$to` - Array or comma-separated list of email addresses to send message.
* `$subject` - Email subject
* `$message` - Message contents
* `$headers` - Array or SendGrid\Email() object. Optional.
* `$attachments` - Array or "\n"/"," separated list of files to attach. Optional.

The wp_mail function is sending text emails as default. If you want to send an email with HTML content you have to set the content type to 'text/html' running `add_filter('wp_mail_content_type', 'set_html_content_type');` function before to `wp_mail()` one.

After wp_mail function you need to run the `remove_filter('wp_mail_content_type', 'set_html_content_type');` to remove the 'text/html' filter to avoid conflicts --http://core.trac.wordpress.org/ticket/23578

Example about how to send an HTML email using different headers:

Using array for $headers:

`$subject = 'Test SendGrid plugin';
$message = 'testing WordPress plugin';
$to = array('address1@sendgrid.com', 'Address2 <address2@sendgrid.com>', 'address3@sendgrid.com');
 
$headers = array();
$headers[] = 'From: Me Myself <me@example.net>';
$headers[] = 'Cc: address4@sendgrid.com';
$headers[] = 'Bcc: address5@sendgrid.com';
$headers[] = 'unique-args:customer=mycustomer;location=mylocation';
$headers[] = 'categories: category1, category2';
$headers[] = 'template: templateID';
$headers[] = 'x-smtpapi-to: address1@sendgrid.com,address2@sendgrid.com';
 
$attachments = array('/tmp/img1.jpg', '/tmp/img2.jpg');
 
add_filter('wp_mail_content_type', 'set_html_content_type');
$mail = wp_mail($to, $subject, $message, $headers, $attachments);
 
remove_filter('wp_mail_content_type', 'set_html_content_type');`


Using SendGrid\Email() for $headers:

`$subject = 'Test SendGrid plugin';
$message = 'testing WordPress plugin';
$to = array('address1@sendgrid.com', 'Address2 <address2@sendgrid.com>', 'address3@sendgrid.com');
 
$headers = new SendGrid\Email();
$headers->setFromName("Me Myself")
        ->setFrom("me@example.net")
        ->setCc("address4@sendgrid.com")
        ->setBcc("address5@sendgrid.com")
        ->setUniqueArgs(array('customer' => 'mycustomer', 'location' => 'mylocation'))
        ->addCategory('category1')
        ->addCategory('category2')
        ->setTemplateId('templateID');
 
$attachments = array('/tmp/img1.jpg', '/tmp/img2.jpg');
 
add_filter('wp_mail_content_type', 'set_html_content_type');
$mail = wp_mail($to, $subject, $message, $headers, $attachments);
 
remove_filter('wp_mail_content_type', 'set_html_content_type');`

= How to use Substitution and Sections =

`$subject = 'Hey %name%, you work at %place%';
$message = 'testing WordPress plugin';
$to = array('address1@sendgrid.com');

$headers = new SendGrid\Email();
$headers
    ->addSmtpapiTo("john@somewhere.com")
    ->addSmtpapiTo("harry@somewhere.com")
    ->addSmtpapiTo("Bob@somewhere.com")
    ->addSubstitution("%name%", array("John", "Harry", "Bob"))
    ->addSubstitution("%place%", array("%office%", "%office%", "%home%"))
    ->addSection("%office%", "an office")
    ->addSection("%home%", "your house")
;

$mail = wp_mail($to, $subject, $message, $headers);`

More examples for using SendGrid SMTPAPI header: <https://github.com/sendgrid/sendgrid-php#smtpapi>

= Using categories =

Categories used for emails can be set:

* globally, for all emails sent, by setting the 'Categories' field in the 'Mail settings' section
* per email by adding the category in the headers array: `$headers[] = 'categories: category1, category2';`

If you would like to configure categories for statistics, you can configure it by setting the 'Categories' field in the 'Statistics settings' section

== Installation ==

Requirements:

1. PHP version >= 5.3.0
2. You need to have PHP-curl extension enabled in order to send attachments.
3. To send emails through SMTP you need to install also the 'Swift Mailer' plugin.
4. If wp_mail() function has been declared by another plugin that you have installed, you won't be able to use the SendGrid plugin

To upload the SendGrid Plugin .ZIP file:

1. Upload the WordPress SendGrid Plugin to the /wp-contents/plugins/ folder.
2. Activate the plugin from the "Plugins" menu in WordPress.
3. Create a SendGrid account at <a href="http://sendgrid.com/partner/wordpress" target="_blank">http://sendgrid.com/partner/wordpress</a>  
4. Navigate to "Settings" -> "SendGrid Settings" and enter your SendGrid credentials

To auto install the SendGrid Plugin from the WordPress admin:

1. Navigate to "Plugins" -> "Add New"
2. Search for "SendGrid Plugin" and click "Install Now" for the "SendGrid Plugin" listing
3. Activate the plugin from the "Plugins" menu in WordPress, or from the plugin installation screen.
4. Create a SendGrid account at <a href="http://sendgrid.com/partner/wordpress" target="_blank">http://sendgrid.com/partner/wordpress</a>
5. Navigate to "Settings" -> "SendGrid Settings" and enter your SendGrid credentials

SendGrid settings can optionally be defined as global variables (wp-config.php):

1. Set credentials (You can use credentials or Api key. If using credentials, both need to be set in order to get credentials from variables and not from the database. If using API key you need to make sure you set the Mail Send permissions to FULL ACCESS, Stats to READ ACCESS and Template Engine to READ or FULL ACCESS when you created the api key on SendGrid side, so you can send emails and see statistics on wordpress):
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

== Frequently asked questions ==

= What credentials do I need to add on settings page =

Create a SendGrid account at <a href="http://sendgrid.com/partner/wordpress" target="_blank">https://sendgrid.com/partner/wordpress</a> and generate a new API key on <https://app.sendgrid.com/settings/api_keys>.

= How can I define a plugin setting to be used for all sites =

Add it into your wp-config.php file. Example: `define('SENDGRID_API_KEY', 'your_api_key');`.

= How to use SendGrid with WP Better Emails plugin =

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

= Why are my emails sent as HTML instead of plain text =

For a detailed explanation see this page: https://support.sendgrid.com/hc/en-us/articles/200181418-Plain-text-emails-converted-to-HTML

== Screenshots ==

1. Go to Admin Panel, section Plugins and activate the SendGrid plugin. If you want to send emails through SMTP you need to install also the 'Swift Mailer' plugin. 
2. After activation "Settings" link will appear. 
3. Go to settings page and provide your SendGrid credentials by choosing the authentication method which default is Api Key. On this page you can set also the default "Name", "Sending Address" and "Reply Address". 
4. If you want to use your username and password for authentication, switch to Username&Password authentication method.
5. If you provide valid credentials, a form which can be used to send test emails will appear. Here you can test the plugin sending some emails. 
6. Header provided in the send test email form. 
7. If you click in the right corner from the top of the page on the "Help" button, a popup window with more information will appear. 
8. Select the time interval for which you want to see SendGrid statistics and charts.
9. Now you are able to configure port number when using SMTP method.
10. You are able to configure what template to use for sending emails.
11. You are able to configure categories for which you would like to see your stats. 
12. You can use substitutions for emails.

== Changelog ==

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