(function() {

  function check_wp_version(version) {
    return parseFloat(tinyCompress.wpVersion) >= version
  }

  function compress_image(event) {
    var element = jQuery(event.target)
    var container = element.closest('.tiny-ajax-container')

    element.attr('disabled', 'disabled')
    container.find('.spinner').removeClass('hidden')
    container.find('span.dashicons').addClass('hidden')
    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_compress_image',
        id: element.data('id') || element.attr('data-id')
      },
      success: function(data) {
        container.html(data)
      },
      error: function() {
        element.removeAttr('disabled')
        container.find('.spinner').addClass('hidden')
      }
    })
  }

  function dismiss_notice(event) {
    var element = jQuery(event.target)
    var notice = element.closest(".tiny-notice")
    element.attr('disabled', 'disabled')
    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      dataType: "json",
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_dismiss_notice',
        name: notice.data('name') || notice.attr('data-name')
      },
      success: function(data) {
        if (data) {
          notice.remove()
        }
      },
      error: function() {
        element.removeAttr('disabled')
      }
    })
    return false
  }

  function bulk_compress_callback(error, data, items, i) {
      var row = jQuery(jQuery('#media-items').children("div")[i])
      var status

      if (check_wp_version(3.3)) {
        status = row.find('.bar')
      } else {
        row.find('.bar').remove()
        status = row.find('.percent')
      }

      if (data.thumbnail) {
        var img = jQuery('<img class="pinkynail">')
        img.attr("src", data.thumbnail)
        row.prepend(img)
      }

      if (error) {
        status.addClass('failed')
        row.find('.percent').html(tinyCompress.L10nInternalError)
        row.find('.progress').attr("title", error.toString())
      } else if (data.error) {
        status.addClass('failed')
        row.find('.percent').html(tinyCompress.L10nError)
        row.find('.progress').attr("title", data.error)
      } else if (data.failed > 0) {
        status.addClass('failed')
        row.find('.bar').css('width', '100%')
        row.find('.percent').html(data.success + " " + (data.success == 1 ? tinyCompress.L10nCompression : tinyCompress.L10nCompressions))
        row.find('.progress').attr("title", data.message)
      } else {
        status.addClass('success')
        row.find('.bar').css('width', '100%')
        row.find('.percent').html(data.success + " " + (data.success == 1 ? tinyCompress.L10nCompression : tinyCompress.L10nCompressions))
      }

      if (data.status) {
        jQuery('#tiny-status span').html(data.status)
      }

      if (items[++i]) {
        bulk_compress_item(items, i)
      } else {
        var message = jQuery('<div class="updated"><p></p></div>');
        message.find('p').html(tinyCompress.L10nAllDone)
        message.insertAfter(jQuery("#tiny-bulk-compress h2"))
      }
  }

  function bulk_compress_item(items, i) {
    var item = items[i]
    var row = jQuery(jQuery('#media-items').children("div")[i])
    row.find('.percent').html(tinyCompress.L10nCompressing)
    jQuery.ajax({
      url: ajaxurl,
      type: "POST",
      dataType: "json",
      data: {
        _nonce: tinyCompress.nonce,
        action: 'tiny_compress_image',
        id: items[i].ID,
        json: true
      },
      success: function(data) { bulk_compress_callback(null, data, items, i)},
      error: function(xhr, textStatus, errorThrown) { bulk_compress_callback(errorThrown, {}, items, i) }
    })
    jQuery('#tiny-progress span').html(i + 1)
  }

  function bulk_compress(items) {
    var list = jQuery('#media-items')
    var row
    for (var i = 0; i < items.length; i++) {
      if (check_wp_version(3.3)) {
        row = jQuery('<div class="media-item"><div class="progress"><div class="percent"></div><div class="bar"></div></div><div class="filename"></div></div>')
      } else {
        row = jQuery('<div class="media-item" style="box-shadow: none"><div class="progress"><div class="bar"></div></div><div class="percent"></div><div class="filename"></div></div>')
      }
      row.find('.percent').html(tinyCompress.L10nWaiting)
      row.find('.filename').html(items[i].post_title)
      list.append(row)
    }
    bulk_compress_item(items, 0)
  }

  function update_resize_settings() {
    if (jQuery('#tinypng_sizes_0').prop('checked')) {
      jQuery('.tiny-resize-available').show()
      jQuery('.tiny-resize-unavailable').hide()
    } else {
      jQuery('.tiny-resize-available').hide()
      jQuery('.tiny-resize-unavailable').show()
    }

    var original_enabled = jQuery('#tinypng_resize_original_enabled').prop('checked')
    jQuery('#tinypng_resize_original_width, #tinypng_resize_original_height').each(function (i, el) {
      el.disabled = !original_enabled
    })
  }

  function update_preserve_settings() {
    if (jQuery('#tinypng_sizes_0').prop('checked')) {
      jQuery('.tiny-preserve').show()
    } else {
      jQuery('.tiny-preserve').hide()
      jQuery('#tinypng_preserve_data_creation').attr('checked', false)
      jQuery('#tinypng_preserve_data_copyright').attr('checked', false)
      jQuery('#tinypng_preserve_data_location').attr('checked', false)
    }
  }

  function update_settings() {
    update_resize_settings()
    update_preserve_settings()
  }

  var adminpage = ""
  if (typeof window.adminpage !== "undefined") {
    adminpage = window.adminpage
  }

  function eventOn(parentSelector, event, eventSelector, callback) {
    if (typeof jQuery.fn.on === "function") {
      jQuery(parentSelector).on(event, eventSelector, callback)
    } else {
      jQuery(eventSelector).live(event, callback)
    }
  }

  if (adminpage === "upload-php") {
    eventOn('table', 'click', 'button.tiny-compress', compress_image)

    if (typeof jQuery.fn.prop === "function") {
      jQuery('button.tiny-compress').prop('disabled', null)
    } else {
      jQuery('button.tiny-compress').attr('disabled', null)
    }

    jQuery('<option>').val('tiny_bulk_compress').text(tinyCompress.L10nBulkAction).appendTo('select[name="action"]')
    jQuery('<option>').val('tiny_bulk_compress').text(tinyCompress.L10nBulkAction).appendTo('select[name="action2"]')
  } else if (adminpage === "post-php") {
    eventOn('div.postbox-container div.tiny-compress-images', 'click', 'button.tiny-compress', compress_image)
  } else if (adminpage === "options-media-php") {
    jQuery('#tiny-compress-status').load(ajaxurl + '?action=tiny_compress_status')
    jQuery('#tiny-compress-savings').load(ajaxurl + '?action=tiny_compress_savings')

    jQuery('input[name*="tinypng_sizes"], input#tinypng_resize_original_enabled').on("click", function() {
      // Unfortunately, we need some additional information to display the correct notice.
      totalSelectedSizes = jQuery('input[name*="tinypng_sizes"]:checked').length
      var image_count_url = ajaxurl + '?action=tiny_image_sizes_notice&image_sizes_selected=' + totalSelectedSizes
      if (jQuery('input#tinypng_resize_original_enabled').prop('checked') && jQuery('input#tinypng_sizes_0').prop('checked')) {
        image_count_url += '&resize_original=true'
      }
      jQuery('#tiny-image-sizes-notice').load(image_count_url)
    })

    jQuery('#tinypng_sizes_0, #tinypng_resize_original_enabled').click(update_settings)
    update_settings()
  }

  jQuery('.tiny-notice a.tiny-dismiss').click(dismiss_notice)
  jQuery(function() {
    jQuery('.tiny-notice.is-dismissible button').unbind('click').click(dismiss_notice)
  })

  window.tinyBulkCompress = bulk_compress
}).call()
