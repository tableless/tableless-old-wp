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

function ct_clear_users()
{
	var data = {
		'action': 'ajax_clear_users',
		'security': ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			ct_send_users();
		}
	});
}

function ct_send_users()
{
	var data = {
		'action': 'ajax_check_users',
		'security': ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			if(parseInt(msg)==1)
			{
				ct_send_users();
			}
			else if(parseInt(msg)==0)
			{
				working=false;
				jQuery('#ct_working_message').hide();
				//alert('finish!');
				location.href='users.php?page=ct_check_users';
			}
			else
			{
				working=false;
				alert(msg);
			}
		}
	});
}
function ct_show_users_info()
{
	if(working)
	{
		var data = {
			'action': 'ajax_info_users',
			'security': ajax_nonce
		};
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: data,
			success: function(msg){
				jQuery('#ct_checking_users_status').html(msg);
				setTimeout(ct_show_users_info, 1000);
			}
		});
	}
}
function ct_insert_users()
{
	var data = {
		'action': 'ajax_insert_users',
		'security': ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
		    alert('Inserted ' + msg + ' users.');
		}
	});
}
function ct_delete_all_users()
{
	var data = {
		'action': 'ajax_delete_all_users',
		'security': ajax_nonce
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			if(msg>0)
			{
				jQuery('#cleantalk_users_left').html(msg);
				ct_delete_all_users();
			}
			else
			{
				location.href='users.php?page=ct_check_users';
			}
		},
        error: function(jqXHR, textStatus, errorThrown) {
            if(textStatus === 'timeout') {     
                alert('Failed from timeout. Going to run a new attempt to delete spam users.'); 
				ct_delete_all_users();
            }        
        },
        timeout: 160000
	});
}
function ct_delete_checked_users()
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
		'action': 'ajax_delete_checked_users',
		'security': ajax_nonce,
		'ids':ids
	};
	
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: data,
		success: function(msg){
			location.href='users.php?page=ct_check_users';
			//alert(msg);
		}
	});
	return false;
}

jQuery(".cleantalk_delete_user_button").click(function(){
	id = jQuery(this).attr("data-id");
	ids=Array();
	ids[0]=id;
	var data = {
		'action': 'ajax_delete_checked_users',
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
		},
        timeout: 180000
	});
});
jQuery(".cleantalk_delete_user_button").click(function(){
	id = jQuery(this).attr("data-id");
	animate_comment(0.3, id);
});

jQuery("#ct_check_users_button").click(function(){
	jQuery('#ct_working_message').show();
	jQuery('#ct_check_users_button').hide();
	jQuery('#ct_info_message').hide();
	working=true;
	ct_clear_users();
});
jQuery("#ct_check_users_button").click(function(){

//	jQuery('#ct_checking_users_status').html('');
	jQuery('#ct_check_users_table').hide();
	jQuery('#ct_delete_all_users').hide();
	jQuery('#ct_delete_checked_users').hide();
	jQuery('#ct_preloader').show();
	working=true;
	ct_show_users_info();
});
jQuery("#ct_insert_users").click(function(){
	ct_insert_users();
});

jQuery("#ct_stop_deletion").click(function(){
    window.location.reload();	
});
jQuery("#ct_delete_all_users").click(function(){
    if (!confirm('Delete all spam users?')) {
        return false;
    }

	jQuery('#ct_checking_users_status').hide();
	jQuery('#ct_check_users_table').hide();
	jQuery('#ct_tools_buttons').hide();
	jQuery('#ct_info_message').hide();
	jQuery('#ct_ajax_info_users').hide();
	jQuery('#ct_check_users_table').hide();
	jQuery('#ct_check_users_button').hide();
	jQuery('#ct_search_info').hide();
	jQuery('#ct_deleting_message').show();
	jQuery('#ct_preloader').show();
	jQuery('#ct_stop_deletion').show();
	jQuery("html, body").animate({ scrollTop: 0 }, "slow");
	ct_delete_all_users();
});
jQuery("#ct_delete_checked_users").click(function(){
    if (!confirm('Delete selected users?')) {
        return false;
    }

	ct_delete_checked_users();
});
jQuery(".cleantalk_user").mouseover(function(){
	id = jQuery(this).attr("data-id");
	jQuery("#cleantalk_delete_user_"+id).show();
});
jQuery(".cleantalk_user").mouseout(function(){
	id = jQuery(this).attr("data-id");
	jQuery("#cleantalk_delete_user_"+id).hide();
});

jQuery(document).ready(function(){
	working=true;
	ct_show_users_info();
	working=false;
	if(location.href.match(/do_check/))
	{
		jQuery("#ct_check_users_button").click();
	}
});
