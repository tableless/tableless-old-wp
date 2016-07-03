<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * Example usage of the mailusers_html_wrapper filter.
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
 * This example wraps an "Urgent" message and table around the email
 * content so the background can be styled.  A table is the best way
 * to do this because not all mail clients will recognize styling
 * elements such as BODY and DIV like a traditional web page.
 *
 * Drop this code snippet and modify to suit your needs into your
 * theme's functions.php file.
 *
 * @see https://wordpress.org/plugins/wp-better-emails/
 * @see https://litmus.com/blog/background-colors-html-email
 *
 */
function mailusers_sample_html_wrapper($subject, $message, $footer)
{
    //  Wrap the HTML in proper header and body tags
    //  add some CSS styling to make the email look good.

    $mailtext = sprintf('
<html>
<head>
<title>%s</title>
<style>
table { border: 1px solid black; width: 800px; background-color: #c5f6c0; }
td { background-color: #c5f6c0 }
</style>
</head>
<body>
<table class="content">
<tr>
<td class="content">
<div class="content">
<h1>This is an Urgent Message from Email Users!</h1>
%s
</div>
<div class="footer">
%s
</div>
</td>
</tr>
</table>
</body>
</html>', $subject, $message, $footer) ;
    
    return $mailtext ;
}

add_filter('mailusers_html_wrapper', 'mailusers_sample_html_wrapper', 10, 3) ;
?>
