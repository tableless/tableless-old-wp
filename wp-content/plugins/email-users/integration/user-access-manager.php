<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * Enable integration with User Access Manager plugin?
 * @see http://wordpress.org/plugins/user-access-manager/
 *
 * This functionality will be included by the core plugin if/when the
 * ItThinx Groups plugin is installed and activated.
 */

/**
 * Get the users based on groups from the User Access Manager plugin
 * $meta_filter can be '', MAILUSERS_ACCEPT_NOTIFICATION_USER_META, or MAILUSERS_ACCEPT_MASS_EMAIL_USER_META
 */
function mailusers_get_uam_groups($exclude_id='', $meta_filter = '') {
    global $wpdb ;

    $uam = array() ;

    $groups = $wpdb->get_results("
        SELECT DISTINCT a.id, a.groupname FROM {$wpdb->prefix}uam_accessgroups a
		INNER JOIN {$wpdb->prefix}uam_accessgroup_to_object b ON a.id = b.group_id
		WHERE b.object_type != 'role' ") ;

    foreach ($groups as $group)
    {
        $ids = mailusers_get_recipients_from_uam_group($group->id, $exclude_id, $meta_filter) ;

        if (!empty($ids)) $uam[$group->id] = $group->groupname ;
    }

    return $uam ;
}

/**
 * Get the users based on groups from the User Access Manager plugin
 * $meta_filter can be '', MAILUSERS_ACCEPT_NOTIFICATION_USER_META, or MAILUSERS_ACCEPT_MASS_EMAIL_USER_META
 */
function mailusers_get_recipients_from_uam_group($uam_ids, $exclude_id='', $meta_filter = '') {
    global $wpdb ;

    //  Make sure we have an array
    if (!is_array($uam_ids)) $uam_ids = array($uam_ids) ;

    //  No groups?  Return an empty array
    if (empty($uam_ids)) return array() ;

    //  Prepare tends to wrap stuff in quotes so we need to build up the IN construct
    //  based on the number of UAM IDs that are provided by the caller.

    $in = '' ;

    foreach ($uam_ids as $id)
        $in .= '%d' . ($id == end($uam_ids) ? '' : ',') ;

    $ids = array() ;

    $query = $wpdb->prepare("
		SELECT DISTINCT a.ID FROM $wpdb->users a
		INNER JOIN {$wpdb->prefix}uam_accessgroup_to_object b ON a.id = b.object_id
		WHERE b.object_type != 'role' AND b.group_id IN (" . $in . ")", $uam_ids) ;

    //  Get the IDs and put them in the proper format as
    //  the Query will return an array of Standard Objects
    foreach ($wpdb->get_results($query) as $id)
        $ids[] = $id->ID ;

    //  Make sure the list of IDs accounts for the Email Users settings for email
    $ids = mailusers_get_recipients_from_ids($ids, $exclude_id, $meta_filter) ;

    return $ids ;
}    

?>
