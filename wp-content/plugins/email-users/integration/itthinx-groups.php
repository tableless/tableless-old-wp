<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 * Integration with the ItThinx Groups plugin
 * @see http://wordpress.org/plugins/groups/
 *
 * This functionality will be included by the core plugin if/when the
 * ItThinx Groups plugin is installed and activated.
 */

/**
 * Extend the Groups_Utility class
 *
 */
class Mailusers_Groups_Utility extends Groups_Utility
{
    /**
     * ItThinx Groups returns the list of groups as a tree - not terriby
     * useful so it needs to be flattened into an array of key values.
     */
    static public function flatten_group_tree($input)
    { 
        $output = array_keys($input) ; 

        foreach($input as $sub)
        { 
            if (is_array($sub))
            { 
                $output = array_merge($output, self::flatten_group_tree($sub)) ; 
            } 
        } 

        return $output ;
    }
}

/**
 * Get the users based on groups from the User Access Manager plugin
 * $meta_filter can be '', MAILUSERS_ACCEPT_NOTIFICATION_USER_META, or MAILUSERS_ACCEPT_MASS_EMAIL_USER_META
 */
function mailusers_get_itthinx_groups($exclude_id='', $meta_filter = '') {
    global $wpdb ;

    $itthinx_groups = array() ;

    $groups = Groups_Utility::get_group_tree() ;
    $groups = Mailusers_Groups_Utility::flatten_group_tree($groups) ;

    foreach ($groups as $key => $value)
    {
        $group = Groups_Group::read($value);
        $ids = mailusers_get_recipients_from_itthinx_groups_group($group->group_id, $exclude_id, $meta_filter) ;

        if (!empty($ids)) $itthinx_groups[$group->group_id] = $group->name ;
    }

    return $itthinx_groups ;
}

/**
 * Get the users based on groups from the User Access Manager plugin
 * $meta_filter can be '', MAILUSERS_ACCEPT_NOTIFICATION_USER_META, or MAILUSERS_ACCEPT_MASS_EMAIL_USER_META
 */
function mailusers_get_recipients_from_itthinx_groups_group($itthinx_groups_ids, $exclude_id='', $meta_filter = '') {
    global $wpdb ;

    $ids = array() ;

    //  Make sure we have an array
    if (!is_array($itthinx_groups_ids)) $itthinx_groups_ids = array($itthinx_groups_ids) ;

    //  No groups?  Return an empty array
    if (empty($itthinx_groups_ids)) return array() ;

    foreach ($itthinx_groups_ids as $key => $value)
    {
        $group = new Groups_Group($value) ;

        foreach ($group->__get('users') as $u)
        {
            $ids[] = $u->user->ID ;
        }
    }

    //  Make sure the list of IDs accounts for the Email Users settings for email
    $ids = mailusers_get_recipients_from_ids($ids, $exclude_id, $meta_filter) ;

    return $ids ;
}    
?>
