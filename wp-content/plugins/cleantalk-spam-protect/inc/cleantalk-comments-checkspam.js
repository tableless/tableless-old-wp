var working=false;

String.prototype.format = String.prototype.f = function ()
{
    var args = arguments;
    return this.replace(/\{\{|\}\}|\{(\d+)\}/g, function (m, n)
    {
        if (m == "{{") { return "{"; }
        if (m == "}}") { return "}"; }
        return args[n];
    });
};
var close_animate=true;
function animate_comment(to,id)
{
	if(close_animate)
	{
		if(to==0.3)
		{
			jQuery('#comment-'+id).fadeTo(200,to,function(){
				animate_comment(1,id)
			});
		}
		else
		{
			jQuery('#comment-'+id).fadeTo(200,to,function(){
				animate_comment(0.3,id)
			});
		}
	}
	else
	{
		close_animate=true;
	}
}

function ct_clear_comments()
{
	var data = {
		'action': 'ajax_clear_comments',
		'security': ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			ct_send_comments();
		}
	});
}

function ct_send_comments()
{
	var data = {
		'action': 'ajax_check_comments',
		'security': ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			if(parseInt(msg)==1)
			{
				ct_send_comments();
			}
			else if(parseInt(msg)==0)
			{
				working=false;
				jQuery('#ct_working_message').hide();
				//alert('finish!');
				location.href='edit-comments.php?page=ct_check_spam';
			}
			else
			{
				working=false;
				alert(msg);
			}
		}
	});
}
function ct_show_info()
{
	if(working)
	{
		var data = {
			'action': 'ajax_info_comments',
			'security': ajax_nonce
		};
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: data,
			success: function(msg){
				jQuery('#ct_checking_status').html(msg);
				setTimeout(ct_show_info, 1000);
			}
		});
	}
}
function ct_insert_comments()
{
	var data = {
		'action': 'ajax_insert_comments',
		'security': ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			if(msg=='ok')
			{
				alert('Added 500 comments');
			}
		}
	});
}
function ct_delete_all()
{
	var data = {
		'action': 'ajax_delete_all',
		'security': ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			if(msg>0)
			{
				jQuery('#cleantalk_comments_left').html(msg);
				ct_delete_all();
			}
			else
			{
				location.href='edit-comments.php?page=ct_check_spam';
			}
		}
	});
}
function ct_delete_checked()
{
	ids=Array();
	var cnt=0;
	jQuery('input[id^=cb-select-][id!=cb-select-all-1]').each(function(){
		if(jQuery(this).prop('checked'))
		{
			ids[cnt]=jQuery(this).attr('id').substring(10);
			cnt++;
		}
	});
	var data = {
		'action': 'ajax_delete_checked',
		'security': ajax_nonce,
		'ids':ids
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			location.href='edit-comments.php?page=ct_check_spam';
			//alert(msg);
		}
	});
}
jQuery("#ct_check_spam_button").click(function(){
	jQuery('#ct_working_message').show();
	jQuery('#ct_check_spam_button').hide();
	jQuery('#ct_info_message').hide();
	working=true;
	ct_clear_comments();
});
jQuery("#ct_check_spam_button").click(function(){
	jQuery('#ct_checking_status').html('');
	jQuery('#ct_check_comments_table').hide();
	jQuery('#ct_delete_all').hide();
	jQuery('#ct_delete_checked').hide();
	jQuery('#ct_preloader').show();
	working=true;
	ct_show_info();
});
jQuery("#ct_insert_comments").click(function(){
	ct_insert_comments();
});
jQuery("#ct_delete_all").click(function(){
	jQuery('#ct_check_comments_table').hide();
	jQuery('#ct_deleting_message').show();
	jQuery("html, body").animate({ scrollTop: 0 }, "slow");
	ct_delete_all();
});
jQuery("#ct_delete_checked").click(function(){
	ct_delete_checked();
});
jQuery(".cleantalk_comment").mouseover(function(){
	id = jQuery(this).attr("data-id");
	jQuery("#cleantalk_delete_"+id).show();
});
jQuery(".cleantalk_comment").mouseout(function(){
	id = jQuery(this).attr("data-id");
	jQuery("#cleantalk_delete_"+id).hide();
});
jQuery(".cleantalk_delete_button").click(function(){
	id = jQuery(this).attr("data-id");
	ids=Array();
	ids[0]=id;
	var data = {
		'action': 'ajax_delete_checked',
		'security': ajax_nonce,
		'ids':ids
	};
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			close_animate=false;
			jQuery("#comment-"+id).hide();
			jQuery("#comment-"+id).remove();
			close_animate=true;
		}
	});
});
jQuery(".cleantalk_delete_button").click(function(){
	id = jQuery(this).attr("data-id");
	animate_comment(0.3, id);
});

jQuery(document).ready(function(){
	working=true;
	ct_show_info();
	working=false;
	if(location.href.match(/do_check/))
	{
		jQuery("#ct_check_spam_button").click();
	}
});
