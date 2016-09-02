<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/*  Copyright 2006 Vincent Prat, 2013 Mike Walsh

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/**
 * Build a Custom Meta Group Filter based on a meta field.
 * This class will find all possible values for a specific
 * meta field and then add the appropriate action for each
 * one it finds.
 *
 */
class CustomMetaKeyGroupFilter
{
    /**
     * Retrieve all of the users who have a specific
     * meta field attached with an optionally supplied
     * value.
     *
     * @param $meta_key string name of the user meta key
     * @param $meta_value string optional value of the user meta key
     */
    private static function get_users_by_meta_key($meta_key, $meta_value = null)
    {
    	// Query for users based on the meta data
     
    	$uq = new WP_User_Query(array('meta_key' => $meta_key, 'meta_value' => $meta_value )) ;
    
    	return $uq->get_results() ;
    }
     
    /**
     * Retrieve all of the possible values for a user meta field.
     * meta field attached with an optionally supplied
     * value.
     *
     * @param $meta_key string name of the user meta key
     * @param $meta_value string optional value of the user meta key
     */
    private static function get_user_meta_key_values($meta_key, $meta_value = null)
    {
    	$meta_values = array() ;
    
        foreach (self::get_users_by_meta_key($meta_key, $meta_value) as $user)
    		$meta_values = array_merge($meta_values, (array)get_user_meta($user->ID, $meta_key, $meta_value)) ;
    	
        sort($meta_values) ;
    
        return array_unique($meta_values) ;
    }
    
    /**
     * Build a label to be used in the form
     *
     * @param $mk string - meta key name
     * @param $mv string - meta value name
     * @return $label string - label
     */
    function get_label($mk, $mv)
    {
        return ucwords($mv . ' ' . $mk) ;
    }

    /**
     * Retrieve all of the possible values for a user meta field.
     * meta field attached with an optionally supplied
     * value.
     *
     * @param $meta_key string name of the user meta key
     * @param $meta_value string optional value of the user meta key
     */
    public static function BuildFilter($meta_key, $meta_value = null, $label_cb)
    {
        $meta_values = self::get_user_meta_key_values($meta_key, $meta_value) ;

        //  Loop through meta values and add an action for each one

        foreach ($meta_values as $mv)
        {
            if (is_null($label_cb))
            {
            $fn = create_function('',
                sprintf('mailusers_register_group_custom_meta_filter(\'%s\', \'%s\', \'%s\');',
                $this->get_label($meta_key, $mv), $meta_key, $mv)) ;
            }
            else
            {
            $fn = create_function('',
                sprintf('mailusers_register_group_custom_meta_filter(\'%s\', \'%s\', \'%s\');',
                $label_cb($meta_key, $mv), $meta_key, $mv)) ;
            }
            add_action('mailusers_group_custom_meta_filter', $fn) ;
        }
    }
}
?>
