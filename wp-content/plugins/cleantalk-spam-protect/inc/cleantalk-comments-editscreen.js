jQuery('#post-query-submit').after('<a href="edit-comments.php?page=ct_check_spam" class="button">'+spambutton_text+'</a>');
jQuery("#ct_check_spam_button").click(function(){
	var data = {
		'action': 'ajax_check_comments',
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