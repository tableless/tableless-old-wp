var ct_adv_settings=null;
var ct_adv_settings_title=null;
var ct_adv_settings_show=false;
jQuery(document).ready(function(){
	var d = new Date();
	var n = d.getTimezoneOffset();
	var data = {
		'action': 'ajax_get_timezone',
		'security': ajax_nonce,
		'offset': n
	};
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			//
		}
	});
	
	if(cleantalk_good_key)
	{
		jQuery('.form-table').first().hide();
		
		banner_html="<div id='ct_stats_banner'>"+cleantalk_blocked_message;
		banner_html+=cleantalk_statistics_link+'</div>';
		jQuery('.form-table').first().before(banner_html);
		if(!cleantalk_wpmu)
		{
			jQuery('.form-table').first().before("<br /><a href='#' style='font-size:10pt;' id='cleantalk_access_key_link'>Show the access key</a>");
		}
	}
	
	jQuery('#cleantalk_access_key_link').click(function(){
		if(jQuery('.form-table').first().is(":visible"))
		{
			jQuery('.form-table').first().hide();
		}
		else
		{
			jQuery('.form-table').first().show();
		}
	});
	
	ct_adv_settings=jQuery('#cleantalk_registrations_test1').parent().parent().parent().parent();
	ct_adv_settings.hide();
	ct_adv_settings_title=ct_adv_settings.prev();
	ct_adv_settings.wrap("<div id='ct_advsettings_hide'>");
	ct_adv_settings_title.append(" <span id='ct_adv_showhide' style='cursor:pointer'><b><a href='#' style='text-decoration:none;'></a></b></span>");
	ct_adv_settings_title.css('cursor','pointer');
	ct_adv_settings_title.click(function(){
		if(ct_adv_settings_show)
		{
			ct_adv_settings.hide();
			ct_adv_settings_show=false;
			jQuery('#ct_adv_showhide').html("<b><a href='#' style='text-decoration:none;'></a></b>");
		}
		else
		{
			ct_adv_settings.show();
			ct_adv_settings_show=true;
			jQuery('#ct_adv_showhide').html("<b><a href='#' style='text-decoration:none;'></a></b>");
		}
		
	});
});