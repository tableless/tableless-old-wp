jQuery('#changeit').after(' <a href="users.php?page=ct_check_users" class="button" style="margin-top:1px;">'+spambutton_users_text+'</a>');
jQuery("#ct_check_users_button").click(function(){
	var data = {
		'action': 'ajax_check_users',
		security: ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			alert(msg);
		}
	});

});