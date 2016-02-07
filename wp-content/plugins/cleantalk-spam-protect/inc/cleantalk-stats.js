function ct_update_stats()
{
	var data = {
		'action': 'ajax_get_stats',
		'security': ajax_nonce
	};
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		dataType: 'json',
		success: function(msg){
			jQuery('#ct_stats').html('<span>' + msg.stat_accepted + '</span> / <span>' + msg.stat_blocked + '</span>');
			setTimeout(ct_update_stats,60000);
		}
	});
}
jQuery(document).ready(function(){
	ct_update_stats();
});