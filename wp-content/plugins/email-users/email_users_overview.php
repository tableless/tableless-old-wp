<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */
/*  Copyright 2006 Vincent Prat  

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
?>

<?php 
	if (!(current_user_can(MAILUSERS_EMAIL_SINGLE_USER_CAP) 
		|| 	current_user_can(MAILUSERS_EMAIL_MULTIPLE_USERS_CAP)
		||	current_user_can(MAILUSERS_EMAIL_USER_GROUPS_CAP))) {		
        wp_die(printf('<div class="error fade"><p>%s</p></div>',
            __('You are not allowed to send emails.', MAILUSERS_I18N_DOMAIN)));
	} 
?>

<div class="wrap">

	<div id="icon-users" class="icon32"><br/></div>
	<h2><?php _e('Email Users', MAILUSERS_I18N_DOMAIN); ?></h2>
	<br/>

	<?php if (current_user_can(MAILUSERS_EMAIL_SINGLE_USER_CAP)
		|| 	current_user_can(MAILUSERS_EMAIL_MULTIPLE_USERS_CAP)) { ?>

<div> <!-- Postbox Containers -->
<div class="postbox-container" style="width:65%; border: 0px dashed blue;"><!-- 65% Postbox Container -->
<div class="metabox-holder">
<div class="meta-box-sortables">
<div class="postbox">
<div class="handlediv" title="Click to toggle"><br /></div>
<h3 class="hndle"><span><?php _e('Send Individual Message', MAILUSERS_I18N_DOMAIN); ?></span></h3>
<div class="inside">
	<div style="float:left"><a href="admin.php?page=mailusers-send-to-user-page">
		<img src="<?php echo WP_CONTENT_URL . '/plugins/email-users/images/user.png'; ?>" alt="<?php _e('Send an email to one or more individual users', MAILUSERS_I18N_DOMAIN); ?>" title="<?php _e('Send an email to one or more individual users', MAILUSERS_I18N_DOMAIN); ?>" /></a>
	</div>
	<p><?php _e('You can send an email to one or more users by selecting them individually.', MAILUSERS_I18N_DOMAIN); ?></p>
	<p><a href="admin.php?page=mailusers-send-to-user-page"><?php _e('Send Individual Message', MAILUSERS_I18N_DOMAIN); ?> &raquo;</a></p>
	<div class="clear"></div>
</div><!-- inside -->
</div><!-- postbox -->
<div class="clear"></div>
	
	
	<?php } ?>

	<?php if (current_user_can(MAILUSERS_EMAIL_USER_GROUPS_CAP)) { ?>
<div class="postbox">
<div class="handlediv" title="Click to toggle"><br /></div>
<h3 class="hndle"><span><?php _e('Send a Group Message', MAILUSERS_I18N_DOMAIN); ?></span></h3>
<div class="inside">
	<div style="float:left"><a href="admin.php?page=mailusers-send-to-group-page">
		<img src="<?php echo WP_CONTENT_URL . '/plugins/email-users/images/group.png'; ?>" alt="<?php _e('Send an email to one or more user groups', MAILUSERS_I18N_DOMAIN); ?>" title="<?php _e('Send an email to one or more user groups', MAILUSERS_I18N_DOMAIN); ?>" /></a>
	</div>
	<p><?php _e('You can send an email to one or more user groups (i.e. users belonging to the same WordPress role).', MAILUSERS_I18N_DOMAIN); ?></p>
	<p><a href="admin.php?page=mailusers-send-to-group-page"><?php _e('Send Group Message', MAILUSERS_I18N_DOMAIN); ?> &raquo;</a></p>
	<div class="clear"></div>
</div><!-- inside -->
</div><!-- postbox -->
	<?php } ?>
	
</div><!-- meta-box-sortables -->
</div><!-- metabox-holder -->
</div><!-- 65% Postbox Container -->

<div class="postbox-container side" style="margin: 0px 0px 20px 10px; min-width: 225px; width:25%; border: 0px dashed red;"><!-- 25% Postbox Container -->
<div class="metabox-holder">
<div class="meta-box-sortables">
<?php include_once "email_users_credits.php";?>
</div><!-- meta-box-sortables -->
</div><!-- metabox-holder -->
</div><!-- 25% Postbox Container -->
</div><!-- Postbox Containers -->
</div><!-- wrap -->
