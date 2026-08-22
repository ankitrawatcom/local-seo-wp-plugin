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

		var $copyBtn = $('#lsar-copy-json');
		if ($copyBtn.length) {
			$copyBtn.on('click', function () {
				var jsonEl = document.getElementById('lsar-schema-json');
				if (!jsonEl) { return; }
				var text = jsonEl.textContent;
				var $feedback = $('#lsar-copy-feedback');

				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(function () {
						$feedback.text(lsarAdmin.copied);
						setTimeout(function () { $feedback.text(''); }, 2000);
					}, function () {
						fallbackCopy(text, $feedback);
					});
				} else {
					fallbackCopy(text, $feedback);
				}
			});
		}

		function fallbackCopy(text, $feedback) {
			var ta = document.createElement('textarea');
			ta.value = text;
			ta.style.position = 'fixed';
			ta.style.opacity = '0';
			document.body.appendChild(ta);
			ta.select();
			try {
				document.execCommand('copy');
				$feedback.text(lsarAdmin.copied);
				setTimeout(function () { $feedback.text(''); }, 2000);
			} catch (e) {
				$feedback.text('');
			}
			document.body.removeChild(ta);
		}
	});
})(jQuery);
