(function ($) {
	'use strict';

	$(function () {
		var $type = $('#lsar-business-type');
		var wcActive = !!(window.lsarAdmin && window.lsarAdmin.woocommerceActive);

		function toggleWooCommerceFields() {
			var isStore = $type.val() === 'Store';
			var $rows = $('.lsar-woocommerce-field').closest('tr');

			if (isStore && wcActive) {
				$rows.show();
			} else {
				$rows.hide();
			}
		}

		$type.on('change', toggleWooCommerceFields);
		toggleWooCommerceFields();
	});
})(jQuery);
