//< target : http://wp2.host/wp-admin/comment.php?c=26&action=approvecomment&_wpnonce=338ed90533
//> target : http://wp2.host/wp-admin/comment.php?c=26&action=unapprovecomment&_wpnonce=338ed90533

//< data : action=dim-comment&id=26&dimClass=unapproved&_ajax_nonce=338ed90533&new=approved
//> data : action=dim-comment&id=26&dimClass=unapproved&_ajax_nonce=338ed90533&new=unapproved

jQuery(document).ajaxSuccess(function(e, xhr, settings) {
    if(settings['target'].toString().indexOf('action=approvecomment') > -1){
	window.location.reload();
    }
});
