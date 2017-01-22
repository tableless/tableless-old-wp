<?php if ( $active_tab == 'multisite' ): ?>
<?php $sites = get_sites(); ?>
<p class="description"> 
    On this page you can grant each subsite the ability to manage SendGrid settings. </br>
    If the checkbox is unchecked then that site will not see the SendGrid settings page and will use the settings set on the network.</br>
    <strong> Warning! </strong> When you activate the management for a subsite, that site will not be able to send emails until the subsite admin updates his SendGrid settings.
</p>
<form method="POST" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI'] ); ?>"> 
<table class="widefat fixed" id="subsites-table-sg" cellspacing="0">
    <thead>
        <tr valign="top">
            <th scope="col" class="manage-column column-columnname num">ID</th>
            <th scope="col" class="manage-column column-columnname">Name</th>
            <th scope="col" class="manage-column column-columnname">Public</th>
            <th scope="col" class="manage-column column-columnname">Site Url</th>
            <th scope="col" class="manage-column"><input style="margin:0 0 0 0px;" type="checkbox" id="sg-check-all-sites"/>  Self-Managed?</th>
        </tr>
    </thead>    
    <tbody>
        <?php foreach ($sites as $index => $site): ?>
            <?php if (!is_main_site($site->blog_id)): ?>
                <?php $site_info = get_blog_details($site->blog_id); ?>
                    <tr <?php echo ($index%2 == 1)? 'class="alternate"':''?>>
                        <td class="column-columnname num" scope="row"><?php echo $site_info->blog_id; ?></td>
                        <td class="column-columnname" scope="row"><?php echo $site_info->blogname; ?></td>
                        <td class="column-columnname" scope="row"><?php echo $site_info->public? "true":"false"; ?></td>
                        <td class="column-columnname" scope="row">
                            <a href="<?php echo $site_info->siteurl; ?>"><?php echo $site_info->siteurl; ?><a>
                        </td>
                        <td class="column-columnname" scope="row" aligh="center">
                            <input type="checkbox" id="check-can-manage-sg" name="checked_sites[<?php echo $site_info->blog_id ?>]" 
                                <?php echo (get_blog_option( $site_info->blog_id, 'sendgrid_can_manage_subsite', 0 )? "checked":"") ?> />
                        </td>
                    </tr>
                <?php endif; ?>
        <?php endforeach; ?>
    </tbody>
</table>
<p class="submit">
    <input type="submit" id="doaction" class="button button-primary" value="Save Settings">
</p>
<input type="hidden" name="subsite_settings" value="true"/>
</form>
<?php endif; ?>