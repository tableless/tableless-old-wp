<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * Example filter to modify the headers to work with wpMandrill.
 *
 * @see:  https://wordpress.org/plugins/wpmandrill/
 *
 * To use this filter, copy the code below to your functions.php
 * file and modify it to suit the application.  The filter MUST
 * return the $to, $headers, and $bcc arguments in an array in
 * the proper order!
 */

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
    //  ... but only if there are actually addresses in the BCC header.
    //  When only one (1) recipient is selected, Email Users will place
    //  the recipient address in the TO header instead of the BCC header.

    //  Only do this when there is something in the BCC header ...

    if (count($bcc) > 0) {
        $to = preg_replace('/^Bcc:\s+/', '', $bcc) ;

        //  Empty out the BCC header
        $bcc = array() ;
    }

    return array($to, $headers, $bcc) ;
}

add_filter('mailusers_manipulate_headers', 'mailusers_mandrill_headers', 10, 3) ;
?>
