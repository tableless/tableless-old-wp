<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * Example filter to modify the filters to allow usage of WPBE.
 *
 * @see https://wordpress.org/plugins/wp-better-emails/
 *
 * To use this filter, copy the code below to your functions.php
 * file and modify it to suit the application.  The filter MUST
 * return the $to, $headers, and $bcc arguments in an array in
 * the proper order!
 */

/**
 * To customize the look of HTML email or to integrate with other
 * plugins which enhance wp_mail() (e.g. WP Better Emails), use this
 * hook to wrap the email content with whatever HTML is desired - or
 * in some cases, none at all if another plugin will be adding the
 * necessary HTML.
 *
 * This example wraps an "WPBE Test" message and table around the email
 * content so the background can be styled.  A table is the best way
 * to do this because not all mail clients will recognize styling
 * elements such as BODY and DIV like a traditional web page.
 *
 * Drop this code snippet and modify to suit your needs into your
 * theme's functions.php file.
 *
 * @see https://wordpress.org/plugins/wp-better-emails/
 *
 */
function mailusers_sample_html_wrapper($subject, $message, $footer)
{
    //  WP Better Emails will handle the HTML wrapping so simply return
    //  the message content.

    return preg_replace(array('/ {2,}/','/<!--.*?-->|\t|(?:\r?\n[ \t]*)+/s'),array(' ',''),
        sprintf('<h1>WPBE Test</h1><div>%s</div>', $message)) ;
}

add_filter('mailusers_html_wrapper', 'mailusers_sample_html_wrapper', 10, 3) ;

/**
 *  WPBE wants the email to be encoded as plain text so use the
 *  wp_mail_content_type filter to force the content type to be
 *  "text/plain".
*/ 
add_filter( 'wp_mail_content_type', 'mailusers_set_content_type' );
function mailusers_set_content_type( $content_type ) {
	return 'text/plain';
}

?>
