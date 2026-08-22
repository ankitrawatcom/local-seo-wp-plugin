(function ($) {
	'use strict';

	$(function () {
		var wcActive = !!(window.lsarAdmin && window.lsarAdmin.woocommerceActive);
		var $rows = $('.lsar-woocommerce-field').closest('tr');

		if (wcActive) {
			$rows.show();
		} else {
			$rows.hide();
		}

		var $logoUrl = $('#lsar-logo');
		if ($logoUrl.length && window.wp && wp.media) {
			var $preview = $('#lsar-logo-preview');
			var $previewWrap = $preview.parent();
			var $removeBtn = $('#lsar-logo-remove');
			var frame;

			function updateLogoPreview() {
				var url = $logoUrl.val();
				if (url) {
					$preview.attr('src', url);
					$previewWrap.show();
					$removeBtn.show();
				} else {
					$previewWrap.hide();
					$removeBtn.hide();
				}
			}

			$logoUrl.on('input change', updateLogoPreview);

			$('#lsar-logo-choose').on('click', function (e) {
				e.preventDefault();

				if (frame) {
					frame.open();
					return;
				}

				frame = wp.media({
					title: lsarAdmin.mediaTitle,
					button: { text: lsarAdmin.mediaButton },
					multiple: false,
					library: { type: 'image' }
				});

				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					$logoUrl.val(attachment.url).trigger('change');
				});

				frame.open();
			});

			$('#lsar-logo-remove').on('click', function (e) {
				e.preventDefault();
				$logoUrl.val('').trigger('change');
			});
		}
	});
})(jQuery);
