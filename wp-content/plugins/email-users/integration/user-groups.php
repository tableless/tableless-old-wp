<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/**
 *  Enable integration with User Groups plugin?
 *  @see http://wordpress.org/plugins/user-groups/
 *
 * This functionality will be included by the core plugin if/when the
 * User Groups plugin is installed and activated.
 */

/**
 * Get the users based on groups from the User Groups plugin
 * $meta_filter can be '', MAILUSERS_ACCEPT_NOTIFICATION_USER_META, or MAILUSERS_ACCEPT_MASS_EMAIL_USER_META
 */
function mailusers_get_user_groups($exclude_id='', $meta_filter = '') {
	$ug = array();

	$terms = get_terms(MAILUSERS_USER_GROUPS_TAXONOMY, array('hide_empty' => true));
	foreach ( $terms as $term ) {
		$users_in_group = mailusers_get_recipients_from_user_groups(array($term->term_id), $exclude_id, $meta_filter);
		if (!empty($users_in_group)) {
			$ug[$term->term_id]=$term->name;
		}
	}
	return $ug;
}

/**
 * Get the users given a term or an array of terms
 * $meta_filter can be '', MAILUSERS_ACCEPT_NOTIFICATION_USER_META, or MAILUSERS_ACCEPT_MASS_EMAIL_USER_META
 */
function mailusers_get_recipients_from_user_groups($terms, $exclude_id='', $meta_filter = '') {
	
	$ids = get_objects_in_term($terms, MAILUSERS_USER_GROUPS_TAXONOMY);
	
	return mailusers_get_recipients_from_ids($ids, $exclude_id, $meta_filter);
}

?>
