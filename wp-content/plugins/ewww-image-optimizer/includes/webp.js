jQuery(document).ready(function($) {
	var ewww_error_counter = 30;
	var sleep_action = 'ewww_sleep';
	var init_action = 'webp_init';
	var loop_action = 'webp_loop';
	var cleanup_action = 'webp_cleanup';
	var init_data = {
	        action: init_action,
		_wpnonce: ewww_vars._wpnonce,
	};
	$('#webp-start').submit(function() {
		startMigrate();
		return false;
	});
	function startMigrate () {
		$('.webp-form').hide();
	        $.post(ajaxurl, init_data, function(response) {
	                $('#webp-loading').html(response);
			processLoop();
	        });
	}
	function processLoop () {
	        var loop_data = {
	                action: loop_action,
			_wpnonce: ewww_vars._wpnonce,
	        };
	        var jqxhr = $.post(ajaxurl, loop_data, function(response) {
			if (response) {
		                $('#webp-status').append( response );
				$('#webp-loading').hide();
				processLoop();
			} else {
			        var cleanup_data = {
			                action: cleanup_action,
					_wpnonce: ewww_vars._wpnonce,
			        };
			        $.post(ajaxurl, cleanup_data, function(response) {
					$('#webp-loading').hide();
			                $('#webp-status').append(response);
			        });
			}
	        })
		.fail(function() { 
			if (ewww_error_counter == 0) {
				$('#webp-loading').html('<p style="color: red"><b>Operation Interrupted</b></p>');
			} else {
				$('#webp-loading').html('<p style="color: red"><b>Temporary failure, retrying for ' + ewww_error_counter + ' more seconds.</b></p>');
				ewww_error_counter--;
				setTimeout(function() {
					processLoop();
				}, 1000);
			}
		});
	}
});
