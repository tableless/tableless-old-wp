=== Easy WP SMTP ===
Contributors: wpecommerce
Donate link: https://wp-ecommerce.net/easy-wordpress-smtp-send-emails-from-your-wordpress-site-using-a-smtp-server-2197
Tags: mail, wordpress smtp, phpmailer, smtp, wp_mail, email, gmail, outgoing mail, privacy, security, sendmail, ssl, tls, wp-phpmailer, mail smtp, wp smtp 
Requires at least: 3.0
Tested up to: 4.3
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily send emails from your WordPress blog using your preferred SMTP server

== Description ==

Easy WP SMTP allows you to configure and send all outgoing emails via a SMTP server. This will prevent your emails from going into the junk/spam folder of the recipients.

= Easy WP SMTP Features =

* Send email using a SMTP sever.
* You can use Gmail, Yahoo, Hotmail's SMTP server if you have an account with them.
* Seamlessly connect your WordPress blog with a mail server to handle all outgoing emails (it's as if the email has been composed inside your mail account).
* Securely deliver emails to your recipients.

= Easy WP SMTP Plugin Usage =

Once you have installed the plugin there are some options that you need to configure in the plugin setttings (go to `Settings->Easy WP SMTP` from your WordPress Dashboard).

**a)** Easy WP SMTP General Settings

The general settings section consists of the following options

* From Email Address: The email address that will be used to send emails to your recipients
* From Name: The name your recipients will see as part of the "from" or "sender" value when they receive your message
* SMTP Host: Your outgoing mail server (example: smtp.gmail.com)
* Type of Encryption: none/SSL/TLS
* SMTP Port: The port that will be used to relay outbound mail to your mail server (example: 465)
* SMTP Authentication: No/Yes (This option should always be checked "Yes")
* Username: The username that you use to login to your mail server
* Password: The password that you use to login to your mail server

For detailed documentation on how you can configure these options please visit the [Easy WordPress SMTP](https://wp-ecommerce.net/easy-wordpress-smtp-send-emails-from-your-wordpress-site-using-a-smtp-server-2197) plugin page

**b)** Easy WP SMTP Testing & Debugging Settings

This section allows you to perform some email testing to make sure that your WordPress site is ready to relay all outgoing emails to your configured SMTP server. It consists of the following options:

* To: The email address that will be used to send emails to your recipients
* Subject: The subject of your message
* Message: A textarea to write your test message.

Once you click the "Send Test Email" button the plugin will try to send an email to the recipient specified in the "To" field.

== Installation ==

1. Go to the Add New plugins screen in your WordPress admin area
1. Click the upload tab
1. Browse for the plugin file (easy-wp-smtp.zip)
1. Click Install Now and then activate the plugin
1. Now, go to the settings menu of the plugin and follow the instructions

== Frequently Asked Questions ==

= Can this plugin be used to send emails via SMTP? =

Yes.

== Screenshots ==

For screenshots please visit the [Easy WordPress SMTP](https://wp-ecommerce.net/easy-wordpress-smtp-send-emails-from-your-wordpress-site-using-a-smtp-server-2197) plugin page

== Other Notes ==

Inspired by [WP Mail SMTP](http://wordpress.org/plugins/wp-mail-smtp/) plugin


== Changelog ==

= 1.2.0 =

* Set email charset to utf-8 for test email functionality.
* Run additional checks on the password only if mbstring is enabled on the server. This should fix the issue with password input field not appearing on some servers.

= 1.1.9 =

* Easy SMTP is now compatible with WordPress 4.3

= 1.1.8 =

* Easy SMTP now removes slashes from the "From Name" field.

= 1.1.7 =

* Made some improvements to the encoding option.

= 1.1.7 =

* Made some improvements to the encoding option.

= 1.1.6 =

* Fixed some character encoding issues of test email functionality
* Plugin will now force the from name and email address saved in the settings (just like version 1.1.1)

= 1.1.5 =

* Fixed a typo in the plugin settings
* SMTP Password is now encoded before saving it to the wp_options table

= 1.1.4 =

* Plugin will now also override the default from name and email (WordPress)

= 1.1.3 =

* Removed "ReplyTo" attribute since it was causing compatibility issues with some form plugins

= 1.1.2 =

* "ReplyTo" attribute will now be set when sending an email
* The plugin will only override "From Email Address" and "Name" if they are not present

= 1.1.1 =

* Fixed an issue where the plugin CSS was affecting other input fields on the admin side.

= 1.1.0 =

* "The settings have been changed" notice will only be displayed if a input field is changed

= 1.0.9 =

* Fixed some bugs in the SMTP configuration and mail functionality

= 1.0.8 =

* Plugin now works with WordPress 3.9

= 1.0.7 =

* Plugin now works with WordPress 3.8

= 1.0.6 =

* Plugin is now compatible with WordPress 3.7

= 1.0.5 =

* "Reply-To" text will no longer be added to the email header
* From Name field can now contain quotes. It will no longer be converted to '\'

= 1.0.4 =

* Plugin is now compatible with WordPress 3.6

= 1.0.3 =

* Added a new option to the settings which allows a user to enable/disable SMTP debug

= 1.0.2 =

* Fixed a bug where the debug output was being displayed on the front end

= 1.0.1 =

* First commit of the plugin

== Upgrade Notice ==

There were some major changes in version 1.0.8. So you will need to reconfigure the SMTP options after the upgrade.
