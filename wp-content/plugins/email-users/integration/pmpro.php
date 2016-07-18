<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * Enable integration with Paid Memberships Pro plugin?
 * @see http://wordpress.org/plugins/paid-memberships-pro/
 *
 * This functionality will be included by the core plugin if/when the
 * Paid Memberships Pro plugin is installed and activated.
 */

/**
 * Get the users based on groups from the Paid Memberships Pro plugin
 * $meta_filter can be '', MAILUSERS_ACCEPT_NOTIFICATION_USER_META, or MAILUSERS_ACCEPT_MASS_EMAIL_USER_META
 */
function mailusers_get_membership_levels($exclude_id='', $meta_filter = '') {
    global $wpdb ;
    $pmp = array() ;

    $groups = $wpdb->get_results("
        SELECT DISTINCT ml.id, ml.name FROM {$wpdb->prefix}pmpro_membership_levels ml") ;

    foreach ($groups as $group)
    {
        $ids = mailusers_get_recipients_from_membership_levels($group->id, $exclude_id, $meta_filter) ;

        if (!empty($ids)) $pmp[$group->id] = $group->name ;
    }

    return $pmp ;
}

/**
 * Get the users based on groups from the Paid Memberships Pro plugin
 * $meta_filter can be '', MAILUSERS_ACCEPT_NOTIFICATION_USER_META, or MAILUSERS_ACCEPT_MASS_EMAIL_USER_META
 */
function mailusers_get_recipients_from_membership_levels($pmp_ids, $exclude_id='', $meta_filter = '') {
    global $wpdb ;
    
    //  Make sure we have an array
    if (!is_array($pmp_ids)) $pmp_ids = array($pmp_ids) ;

    //  No groups?  Return an empty array
    if (empty($pmp_ids)) return array() ;

    //  Prepare tends to wrap stuff in quotes so we need to build up the IN construct
    //  based on the number of PMP IDs that are provided by the caller.

    $in = '' ;

    foreach ($pmp_ids as $id)
        $in .= '%d' . ($id == end($pmp_ids) ? '' : ',') ;

    $ids = array() ;

    $query = $wpdb->prepare("
		SELECT DISTINCT a.ID FROM $wpdb->users a
		INNER JOIN {$wpdb->prefix}pmpro_memberships_users b ON a.id = b.user_id
		WHERE b.status = 'active' and b.membership_id IN (" . $in . ")", $pmp_ids) ;

    //  Get the IDs and put them in the proper format as
    //  the Query will return an array of Standard Objects
    foreach ($wpdb->get_results($query) as $id)
        $ids[] = $id->ID ;

    //  Make sure the list of IDs accounts for the Email Users settings for email
    //  but only do it IF the list of ids is not empty otherwise all IDs will be
    //  returned!

    if (!empty($ids))
        $ids = mailusers_get_recipients_from_ids($ids, $exclude_id, $meta_filter) ;
    
    return $ids;
}    

?>
