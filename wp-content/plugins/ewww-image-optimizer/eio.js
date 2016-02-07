jQuery(document).ready(function($) {
	var ewww_error_counter = 30;
//	var ewww_sleep_action = 'ewww_sleep';
	if (!ewww_vars.attachments) {
		$('#ewww-webp-rewrite').submit(function() {
			var ewww_webp_rewrite_action = 'ewww_webp_rewrite';
			var ewww_webp_rewrite_data = {
				action: ewww_webp_rewrite_action,
				ewww_wpnonce: ewww_vars._wpnonce,
			};
			$.post(ajaxurl, ewww_webp_rewrite_data, function(response) {
				$('#ewww-webp-rewrite-status').html('<b>' + response + '</b>');
				ewww_webp_image = document.getElementById("webp-image").src;
				document.getElementById("webp-image").src = ewww_webp_image + '#' + new Date().getTime();
			});
			return false;
		});
		$('#ewww-status-expand').click(function() {
			$('#ewww-collapsible-status').show();
			$('#ewww-status-expand').hide();
			$('#ewww-status-collapse').show();
		});
		$('#ewww-status-collapse').click(function() {
			$('#ewww-collapsible-status').hide();
			$('#ewww-status-expand').show();
			$('#ewww-status-collapse').hide();
		});
		$('#ewww-cloud-settings').hide();
		$('#ewww-general-settings').show();
		$('li.ewww-general-nav').addClass('ewww-selected');
		$('#ewww-optimization-settings').hide();
		$('#ewww-conversion-settings').hide();
		$('.ewww-cloud-nav').click(function() {
			$('.ewww-tab-nav li').removeClass('ewww-selected');
			$('li.ewww-cloud-nav').addClass('ewww-selected');
			$('.ewww-tab a').blur();
			$('#ewww-cloud-settings').show();
			$('#ewww-general-settings').hide();
			$('#ewww-optimization-settings').hide();
			$('#ewww-conversion-settings').hide();
		});
		$('.ewww-general-nav').click(function() {
			$('.ewww-tab-nav li').removeClass('ewww-selected');
			$('li.ewww-general-nav').addClass('ewww-selected');
			$('.ewww-tab a').blur();
			$('#ewww-cloud-settings').hide();
			$('#ewww-general-settings').show();
			$('#ewww-optimization-settings').hide();
			$('#ewww-conversion-settings').hide();
		});
		$('.ewww-optimization-nav').click(function() {
			$('.ewww-tab-nav li').removeClass('ewww-selected');
			$('li.ewww-optimization-nav').addClass('ewww-selected');
			$('.ewww-tab a').blur();
			$('#ewww-cloud-settings').hide();
			$('#ewww-general-settings').hide();
			$('#ewww-optimization-settings').show();
			$('#ewww-conversion-settings').hide();
		});
		$('.ewww-conversion-nav').click(function() {
			$('.ewww-tab-nav li').removeClass('ewww-selected');
			$('li.ewww-conversion-nav').addClass('ewww-selected');
			$('.ewww-tab a').blur();
			$('#ewww-cloud-settings').hide();
			$('#ewww-general-settings').hide();
			$('#ewww-optimization-settings').hide();
			$('#ewww-conversion-settings').show();
		});
//		if (!ewww_vars.savings_todo) {
//			$('#ewww-total-savings').text('0');
//			return false;
//		}
		var ewww_savings_counter = 0;
		var ewww_savings_total = 0;
		var ewww_savings_todo = parseInt(ewww_vars.savings_todo);
		var ewww_savings_action = 'ewww_savings_loop';
		var ewww_savings_data = {
		        action: ewww_savings_action,
			ewww_wpnonce: ewww_vars._wpnonce,
			ewww_savings_counter: ewww_savings_counter,
			ewww_savings_todo: ewww_savings_todo,
		};
//		ewwwLoopSavings();
		return false;
	} else {
	$(function() {
		$("#ewww-delay-slider").slider({
			min: 0,
			max: 30,
			value: $("#ewww-delay").val(),
			slide: function(event, ui) {
				$("#ewww-delay").val(ui.value);
			}
		});
	});
	// cleanup the attachments array
	var ewww_attachpost = ewww_vars.attachments.replace(/&quot;/g, '"');
	var ewww_attachments = $.parseJSON(ewww_attachpost);
	var ewww_i = 0;
	var ewww_k = 0;
	var ewww_import_total = 0;
	var ewww_force = 0;
	var ewww_delay = 0;
	var ewww_aux = false;
	var ewww_main = false;
	// initialize the ajax actions for the appropriate bulk page
	if (ewww_vars.gallery == 'flag') {
		var ewww_init_action = 'bulk_flag_init';
		var ewww_filename_action = 'bulk_flag_filename';
		var ewww_loop_action = 'bulk_flag_loop';
		var ewww_cleanup_action = 'bulk_flag_cleanup';
	} else if (ewww_vars.gallery == 'nextgen') {
		var ewww_preview_action = 'bulk_ngg_preview';
		var ewww_init_action = 'bulk_ngg_init';
		var ewww_filename_action = 'bulk_ngg_filename';
		var ewww_loop_action = 'bulk_ngg_loop';
		var ewww_cleanup_action = 'bulk_ngg_cleanup';
		// this loads inline on the nextgen gallery management pages
		if (!document.getElementById('ewww-bulk-loading')) {
			var ewww_preview_data = {
			        action: ewww_preview_action,
				ewww_inline: 1,
			};
			$.post(ajaxurl, ewww_preview_data, function(response) {
        	               	$('.wrap').prepend(response);
	$(function() {
		$("#ewww-delay-slider").slider({
			min: 0,
			max: 30,
			value: $("#ewww-delay").val(),
			slide: function(event, ui) {
				$("#ewww-delay").val(ui.value);
			}
		});
	});
				$('#ewww-bulk-start').submit(function() {
					ewwwStartOpt();
					return false;
				});
			});
		}
	} else {
		var ewww_scan_action = 'bulk_aux_images_scan';
		var ewww_init_action = 'bulk_init';
		var ewww_filename_action = 'bulk_filename';
		var ewww_loop_action = 'bulk_loop';
		var ewww_cleanup_action = 'bulk_cleanup';
		ewww_main = true;
	}
	var ewww_init_data = {
	        action: ewww_init_action,
		ewww_wpnonce: ewww_vars._wpnonce,
	};
	var ewww_table_action = 'bulk_aux_images_table';
	var ewww_table_count_action = 'bulk_aux_images_table_count';
	var ewww_import_init_action = 'bulk_import_init';
	var ewww_import_loop_action = 'bulk_import_loop';
	$('#ewww-aux-start').submit(function() {
		ewww_aux = true;
		ewww_init_action = 'bulk_aux_images_init';
		ewww_filename_action = 'bulk_aux_images_filename';
		ewww_loop_action = 'bulk_aux_images_loop';
		ewww_cleanup_action = 'bulk_aux_images_cleanup';
		if ($('#ewww-force:checkbox:checked').val()) {
			ewww_force = 1;
		}
		var ewww_scan_data = {
			action: ewww_scan_action,
			ewww_force: ewww_force,
			ewww_scan: true,
		};
		$('#ewww-aux-start').hide();
		$('#ewww-scanning').show();
		$.post(ajaxurl, ewww_scan_data, function(response) {
			ewww_attachpost = response.replace(/&quot;/g, '"');
			//ewww_attachments = ewww_attachpost;
			ewww_attachments = $.parseJSON(ewww_attachpost);
			ewww_init_data = {
			        action: ewww_init_action,
				ewww_wpnonce: ewww_vars._wpnonce,
			};
			if (ewww_attachments.length == 0) {
				$('#ewww-scanning').hide();
				$('#ewww-nothing').show();
			}
			else {
				ewwwStartOpt();
			}
	        })
		.fail(function() { 
			$('#ewww-scanning').html('<p style="color: red"><b>' + ewww_vars.scan_fail + '</b></p>');
		});
		return false;
	});
/*	$('#import-start').submit(function() {
		$('.bulk-info').hide();
		$('#import-start').hide();
	        $('#ewww-loading').show();
		var import_init_data = {
			action: import_init_action,
			_wpnonce: ewww_vars._wpnonce,
		};
		$.post(ajaxurl, import_init_data, function(response) {
			import_total = response;
			bulkImport();
		});
		return false;
	});	*/
	$('#ewww-show-table').submit(function() {
		var ewww_pointer = 0;
		var ewww_total_pages = Math.ceil(ewww_vars.image_count / 50);
		$('.ewww-aux-table').show();
		$('#ewww-show-table').hide();
		if (ewww_vars.image_count >= 50) {
			$('.tablenav').show();
			$('#next-images').show();
			$('.last-page').show();
		}
	        var ewww_table_data = {
	                action: ewww_table_action,
			ewww_wpnonce: ewww_vars._wpnonce,
			ewww_offset: ewww_pointer,
	        };
		$('.displaying-num').text(ewww_vars.count_string);
		$.post(ajaxurl, ewww_table_data, function(response) {
			$('#ewww-bulk-table').html(response);
		});
		$('.current-page').text(ewww_pointer + 1);
		$('.total-pages').text(ewww_total_pages);
		$('#ewww-pointer').text(ewww_pointer);
		return false;
	});
	$('#next-images').click(function() {
		var ewww_pointer = $('#ewww-pointer').text();
		ewww_pointer++;
	        var ewww_table_data = {
	                action: ewww_table_action,
			ewww_wpnonce: ewww_vars._wpnonce,
			ewww_offset: ewww_pointer,
	        };
		$.post(ajaxurl, ewww_table_data, function(response) {
			$('#ewww-bulk-table').html(response);
		});
		if (ewww_vars.image_count <= ((ewww_pointer + 1) * 50)) {
			$('#next-images').hide();
			$('.last-page').hide();
		}
		$('.current-page').text(ewww_pointer + 1);
		$('#ewww-pointer').text(ewww_pointer);
		$('#prev-images').show();
		$('.first-page').show();
		return false;
	});
	$('#prev-images').click(function() {
		var ewww_pointer = $('#ewww-pointer').text();
		ewww_pointer--;
	        var ewww_table_data = {
	                action: ewww_table_action,
			ewww_wpnonce: ewww_vars._wpnonce,
			ewww_offset: ewww_pointer,
	        };
		$.post(ajaxurl, ewww_table_data, function(response) {
			$('#ewww-bulk-table').html(response);
		});
		if (!ewww_pointer) {
			$('#prev-images').hide();
			$('.first-page').hide();
		}
		$('.current-page').text(ewww_pointer + 1);
		$('#ewww-pointer').text(ewww_pointer);
		$('#next-images').show();
		$('.last-page').show();
		return false;
	});
	$('.last-page').click(function() {
		var ewww_pointer = $('.total-pages').text();
		ewww_pointer--;
	        var ewww_table_data = {
	                action: ewww_table_action,
			ewww_wpnonce: ewww_vars._wpnonce,
			ewww_offset: ewww_pointer,
	        };
		$.post(ajaxurl, ewww_table_data, function(response) {
			$('#ewww-bulk-table').html(response);
		});
		$('#next-images').hide();
		$('.last-page').hide();
		$('.current-page').text(ewww_pointer + 1);
		$('#ewww-pointer').text(ewww_pointer);
		$('#prev-images').show();
		$('.first-page').show();
		return false;
	});
	$('.first-page').click(function() {
		var ewww_pointer = 0;
	        var ewww_table_data = {
	                action: ewww_table_action,
			ewww_wpnonce: ewww_vars._wpnonce,
			ewww_offset: ewww_pointer,
	        };
		$.post(ajaxurl, ewww_table_data, function(response) {
			$('#ewww-bulk-table').html(response);
		});
		$('#prev-images').hide();
		$('.first-page').hide();
		$('.current-page').text(ewww_pointer + 1);
		$('#ewww-pointer').text(ewww_pointer);
		$('#next-images').show();
		$('.last-page').show();
		return false;
	});
	$('#ewww-bulk-start').submit(function() {
		ewwwStartOpt();
		return false;
	});
	}
	function ewwwLoopSavings() {
	        $.post(ajaxurl, ewww_savings_data, function(response) {
			var ewww_int=/^\d+$/;
			if ( ! ewww_int.test(response)) {
				response = 0;
			}
			ewww_savings_total = ewww_savings_total + parseInt(response);
			if (ewww_savings_todo < 0) {
				ewww_savings_action = 'ewww_savings_finish';
				ewww_savings_data = {
				        action: ewww_savings_action,
					ewww_wpnonce: ewww_vars._wpnonce,
					ewww_savings_total: ewww_savings_total,
				};
	        		$.post(ajaxurl, ewww_savings_data, function(response) {
					$('#ewww-total-savings').text(response);
				});
			} else {
				ewww_savings_todo -= 1000;
				ewww_savings_counter += 1000;
				ewww_savings_data = {
				        action: ewww_savings_action,
					ewww_wpnonce: ewww_vars._wpnonce,
					ewww_savings_counter: ewww_savings_counter,
					ewww_savings_todo: ewww_savings_todo,
				};
				ewwwLoopSavings();
			}
	        });
	}
	function ewwwStartOpt () {
		ewww_k = 0;
		$('#ewww-bulk-stop').submit(function() {
			ewww_k = 9;
			$('#ewww-bulk-stop').hide();
			return false;
		});
		if ( ! $('#ewww-delay').val().match( /^[1-9][0-9]*$/) ) {
			ewww_delay = 0;
		} else {
			ewww_delay = $('#ewww-delay').val();
		}
		$('.ewww-aux-table').hide();
		$('#ewww-bulk-stop').show();
		$('.ewww-bulk-form').hide();
		$('.ewww-bulk-info').hide();
		$('h3').hide();
	        $.post(ajaxurl, ewww_init_data, function(response) {
	                $('#ewww-bulk-loading').html(response);
			$('#ewww-bulk-progressbar').progressbar({ max: ewww_attachments.length });
			$('#ewww-bulk-counter').html( ewww_vars.optimized + ' 0/' + ewww_attachments.length);
			ewwwProcessImage();
	        });
	}
	function ewwwProcessImage () {
		ewww_attachment_id = ewww_attachments[ewww_i];
	        var ewww_filename_data = {
	                action: ewww_filename_action,
			ewww_wpnonce: ewww_vars._wpnonce,
			ewww_attachment: ewww_attachment_id,
	        };
		$.post(ajaxurl, ewww_filename_data, function(response) {
			if (ewww_k != 9) {
		        	$('#ewww-bulk-loading').html(response);
			}
		});
		if ($('#ewww-force:checkbox:checked').val()) {
			ewww_force = 1;
		}
	        var ewww_loop_data = {
	                action: ewww_loop_action,
			ewww_wpnonce: ewww_vars._wpnonce,
			ewww_attachment: ewww_attachment_id,
			ewww_sleep: ewww_delay,
			ewww_force: ewww_force,
	        };
	        var ewww_jqxhr = $.post(ajaxurl, ewww_loop_data, function(response) {
			ewww_i++;
			$('#ewww-bulk-progressbar').progressbar("option", "value", ewww_i );
			$('#ewww-bulk-counter').html(ewww_vars.optimized + ' ' + ewww_i + '/' + ewww_attachments.length);
//			var ewww_exceed=/exceeded/m;
//			if (ewww_exceed.test(response)) {
			if (response == '-9exceeded') {
				$('#ewww-bulk-loading').html('<p style="color: red"><b>' + ewww_vars.license_exceeded + '</b></p>');
			}
			else if (ewww_k == 9) {
				ewww_jqxhr.abort();
				ewwwAuxCleanup();
				$('#ewww-bulk-loading').html('<p style="color: red"><b>' + ewww_vars.operation_stopped + '</b></p>');
			}
			else if (ewww_i < ewww_attachments.length) {
	                	$('#ewww-bulk-status').append( response );
				ewww_error_counter = 30;
				ewwwProcessImage();
			}
			else {
	                	$('#ewww-bulk-status').append( response );
			        var ewww_cleanup_data = {
			                action: ewww_cleanup_action,
					ewww_wpnonce: ewww_vars._wpnonce,
			        };
			        $.post(ajaxurl, ewww_cleanup_data, function(response) {
			                $('#ewww-bulk-loading').html(response);
					$('#ewww-bulk-stop').hide();
					ewwwAuxCleanup();
			        });
			}
	        })
		.fail(function() { 
			if (ewww_error_counter == 0) {
				$('#ewww-bulk-loading').html('<p style="color: red"><b>' + ewww_vars.operation_interrupted + '</b></p>');
			} else {
				$('#ewww-bulk-loading').html('<p style="color: red"><b>' + ewww_vars.temporary_failure + ' ' + ewww_error_counter + '</b></p>');
				ewww_error_counter--;
				setTimeout(function() {
					ewwwProcessImage();
				}, 1000);
			}
		});
	}
/*	function bulkImport() {
		var import_loop_data = {
			action: import_loop_action,
			_wpnonce: ewww_vars._wpnonce,
		};
	        var jqxhr = $.post(ajaxurl, import_loop_data, function(response) {
			var unfinished=/^\d+$/m;
			if (unfinished.test(response)) {
				$('#bulk-status').html(response + '/' + import_total);
				ewww_error_counter = 30;
				bulkImport();
			}
			else {
				$('#bulk-status').html(response);
				$('#ewww-loading').hide();
			}
	        })
		.fail(function() { 
			if (ewww_error_counter == 0) {
				$('#ewww-loading').hide();
				$('#bulk-status').html('<p style="color: red"><b>Operation Interrupted</b></p>');
			} else {
				$('#bulk-status').html('<p style="color: red"><b>Temporary failure, retrying for ' + ewww_error_counter + ' more seconds.</b></p>');
				ewww_error_counter--;
				setTimeout(function() {
					bulkImport();
				}, 1000);
			}
		});
	}*/
	function ewwwAuxCleanup() {
		if (ewww_main == true) {
			var ewww_table_count_data = {
				action: ewww_table_count_action,
				ewww_inline: 1,
			};
			$.post(ajaxurl, ewww_table_count_data, function(response) {
				ewww_vars.image_count = response;
			});
			$('#ewww-show-table').show();
	//		$('#ewww-empty-table').show();
			$('#ewww-table-info').show();
			$('.ewww-bulk-form').show();
			$('.ewww-media-info').show();
			$('h3').show();
			if (ewww_aux == true) {
				$('#ewww-aux-first').hide();
				$('#ewww-aux-again').show();
			} else {
				$('#ewww-bulk-first').hide();
				$('#ewww-bulk-again').show();
			}
			ewww_attachpost = ewww_vars.attachments.replace(/&quot;/g, '"');
			ewww_attachments = $.parseJSON(ewww_attachpost);
			ewww_init_action = 'bulk_init';
			ewww_filename_action = 'bulk_filename';
			ewww_loop_action = 'bulk_loop';
			ewww_cleanup_action = 'bulk_cleanup';
			ewww_init_data = {
			        action: ewww_init_action,
				ewww_wpnonce: ewww_vars._wpnonce,
			};
			ewww_aux = false;
			ewww_i = 0;
			ewww_force = 0;
		}
	}	
});
function ewwwRemoveImage(imageID) {
	var ewww_image_removal = {
		action: 'bulk_aux_images_remove',
		ewww_wpnonce: ewww_vars._wpnonce,
		ewww_image_id: imageID,
	};
	jQuery.post(ajaxurl, ewww_image_removal, function(response) {
		if(response == '1') {
			jQuery('#ewww-image-' + imageID).remove();
			var ewww_prev_count = ewww_vars.image_count;
			ewww_vars.image_count--;
			ewww_vars.count_string = ewww_vars.count_string.replace( ewww_prev_count, ewww_vars.image_count );
			jQuery('.displaying-num').text(ewww_vars.count_string);
		} else {
			alert(ewww_vars.remove_failed);
		}
	});
}
