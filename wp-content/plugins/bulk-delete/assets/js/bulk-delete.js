/*! Bulk Delete - v5.5.4 %>
 * http://bulkwp.com
 * Copyright (c) 2016; * Licensed GPLv2+ */
/*global BulkWP, postboxes, pagenow*/
jQuery(document).ready(function () {
	/**
	 * Enable select2
	 */
	jQuery( '.select2' ).select2();

	/**
	 * Enable Postbox handling
	 */
	postboxes.add_postbox_toggles(pagenow);

	/**
	 * Toggle the date restrict fields
	 */
	function toggle_date_restrict(el) {
		if (jQuery("#smbd" + el + "_restrict").is(":checked")) {
			jQuery("#smbd" + el + "_op").removeAttr('disabled');
			jQuery("#smbd" + el + "_days").removeAttr('disabled');
		} else {
			jQuery("#smbd" + el + "_op").attr('disabled', 'true');
			jQuery("#smbd" + el + "_days").attr('disabled', 'true');
		}
	}

	/**
	 * Toggle limit restrict fields
	 */
	function toggle_limit_restrict(el) {
		if (jQuery("#smbd" + el + "_limit").is(":checked")) {
			jQuery("#smbd" + el + "_limit_to").removeAttr('disabled');
		} else {
			jQuery("#smbd" + el + "_limit_to").attr('disabled', 'true');
		}
	}

	/**
	 * Toggle user login restrict fields
	 */
	function toggle_login_restrict(el) {
		if (jQuery("#smbd" + el + "_login_restrict").is(":checked")) {
			jQuery("#smbd" + el + "_login_days").removeAttr('disabled');
		} else {
			jQuery("#smbd" + el + "_login_days").attr('disabled', 'true');
		}
	}

	/**
	 * Toggle user registered restrict fields
	 */
	function toggle_registered_restrict(el) {
		if (jQuery("#smbd" + el + "_registered_restrict").is(":checked")) {
			jQuery("#smbd" + el + "_registered_days").removeAttr('disabled');
		} else {
			jQuery("#smbd" + el + "_registered_days").attr('disabled', 'true');
		}
	}

	// hide all terms
	function hideAllTerms() {
		jQuery('table.terms').hide();
		jQuery('input.terms').attr('checked', false);
	}
	// call it for the first time
	hideAllTerms();

	// taxonomy click handling
	jQuery('.custom-tax').change(function () {
		var $this = jQuery(this),
		$tax = $this.val(),
		$terms = jQuery('table.terms_' + $tax);

		if ($this.is(':checked')) {
			hideAllTerms();
			$terms.show('slow');
		}
	});

	// date time picker
	jQuery.each(BulkWP.dt_iterators, function (index, value) {
		// invoke the date time picker
		jQuery('#smbd' + value + '_cron_start').datetimepicker({
			timeFormat: 'HH:mm:ss'
		});

		jQuery('#smbd' + value + '_restrict').change(function () {
			toggle_date_restrict(value);
		});

		jQuery('#smbd' + value + '_limit').change(function () {
			toggle_limit_restrict(value);
		});

		jQuery('#smbd' + value + '_login_restrict').change(function () {
			toggle_login_restrict(value);
		});

		jQuery('#smbd' + value + '_registered_restrict').change(function () {
			toggle_registered_restrict(value);
		});
	});

	jQuery.each( BulkWP.pro_iterators, function ( index, value) {
		jQuery('.bd-' + value.replace( '_', '-' ) + '-pro').hide();
		jQuery('#smbd_' + value + '_cron_freq, #smbd_' + value + '_cron_start, #smbd_' + value + '_cron').removeAttr('disabled');
	} );

	// Validate user action
	jQuery('button[name="bd_action"]').click(function () {
		var currentButton = jQuery(this).val(),
		valid = false,
		msg_key = "deletePostsWarning",
			error_key = "selectPostOption";

		if (currentButton in BulkWP.validators) {
			valid = BulkWP[BulkWP.validators[currentButton]](this);
		} else {
			if (jQuery(this).parent().prev().children('table').find(":checkbox:checked[value!='true']").size() > 0) { // monstrous selector
				valid = true;
			}
		}

		if (valid) {
			if (currentButton in BulkWP.pre_action_msg) {
				msg_key = BulkWP.pre_action_msg[currentButton];
			}

			return confirm(BulkWP.msg[msg_key]);
		} else {
			if (currentButton in BulkWP.error_msg) {
				error_key = BulkWP.error_msg[currentButton];
			}

			alert(BulkWP.msg[error_key]);
		}

		return false;
	});

	/**
	 * Validation functions
	 */
	BulkWP.noValidation = function() {
		return true;
	};

	BulkWP.validateSelect2 = function(that) {
		if (null !== jQuery(that).parent().prev().children().find(".select2[multiple]").val()) {
			return true;
		} else {
			return false;
		}
	};

	BulkWP.validateUrl = function(that) {
		if (jQuery(that).parent().prev().children('table').find("textarea").val() !== '') {
			return true;
		} else {
			return false;
		}
	};

    BulkWP.validateUserMeta = function() {
        if (jQuery('#smbd_u_meta_value').val() !== '') {
            return true;
        } else {
            return false;
        }
    };
});

//# sourceMappingURL=bulk-delete.js.map