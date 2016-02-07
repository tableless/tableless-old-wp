//borrowed from https://developers.google.com/speed/webp/faq#how_can_i_detect_browser_support_using_javascript
function check_webp_feature(feature, callback) {
    var kTestImages = {
        lossy: "UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA",
        lossless: "UklGRhoAAABXRUJQVlA4TA0AAAAvAAAAEAcQERGIiP4HAA==",
        alpha: "UklGRkoAAABXRUJQVlA4WAoAAAAQAAAAAAAAAAAAQUxQSAwAAAARBxAR/Q9ERP8DAABWUDggGAAAABQBAJ0BKgEAAQAAAP4AAA3AAP7mtQAAAA==",
        animation: "UklGRlIAAABXRUJQVlA4WAoAAAASAAAAAAAAAAAAQU5JTQYAAAD/////AABBTk1GJgAAAAAAAAAAAAAAAAAAAGQAAABWUDhMDQAAAC8AAAAQBxAREYiI/gcA"
    };
    var img = new Image();
    img.onload = function () {
        var result = (img.width > 0) && (img.height > 0);
        callback(result);
    };
    img.onerror = function () {
        callback(false);
    };
    img.src = "data:image/webp;base64," + kTestImages[feature];
}
function ewww_load_images(ewww_webp_supported) {
	(function($) {
	if (ewww_webp_supported) {
		$('.batch-image img, .image-wrapper a, .ngg-pro-masonry-item a').each(function() {
			var ewww_attr = $(this).attr('data-webp');
			if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
				$(this).attr('data-src', ewww_attr);
			}
			var ewww_attr = $(this).attr('data-webp-thumbnail');
			if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
				$(this).attr('data-thumbnail', ewww_attr);
			}
		});
		$('.image-wrapper a, .ngg-pro-masonry-item a').each(function() {
			var ewww_attr = $(this).attr('data-webp');
			if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
				$(this).attr('href', ewww_attr);
			}
		});
	}
	$('.ewww_webp').each(function() {
		var ewww_img = document.createElement('img');
		if (ewww_webp_supported) {
			$(ewww_img).attr('src', $(this).attr('data-webp'));
		} else {
			$(ewww_img).attr('src', $(this).attr('data-img'));
		}
		var ewww_attr = $(this).attr('data-align');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('align', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-alt');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('alt', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-border');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('border', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-crossorigin');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('crossorigin', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-height');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('height', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-hspace');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('hspace', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-ismap');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('ismap', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-longdesc');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('longdesc', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-usemap');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('usemap', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-vspace');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('vspace', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-width');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('width', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-accesskey');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('accesskey', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-class');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('class', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-contenteditable');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('contenteditable', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-contextmenu');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('contextmenu', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-dir');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('dir', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-draggable');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('draggable', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-dropzone');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('dropzone', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-hidden');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('hidden', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-id');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('id', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-lang');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('lang', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-spellcheck');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('spellcheck', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-style');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('style', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-tabindex');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('tabindex', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-title');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('title', ewww_attr);
		}
		var ewww_attr = $(this).attr('data-translate');
		if (typeof ewww_attr !== typeof undefined && ewww_attr !== false) {
			$(ewww_img).attr('translate', ewww_attr);
		}
		$(this).after(ewww_img);
	});
	})(jQuery);
}
check_webp_feature('alpha', ewww_load_images);
