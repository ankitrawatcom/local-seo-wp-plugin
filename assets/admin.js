(function ($) {
	'use strict';

	$(function () {
		/*
		 * Visibility comes from PHP (WooCommerce class + wc_get_products).
		 * Product schema is not limited to business type Store.
		 */
		var wcActive = !!(window.lsarAdmin && window.lsarAdmin.woocommerceActive);
		var $rows = $('.lsar-woocommerce-field').closest('tr');

		if (wcActive) {
			$rows.show();
		} else {
			$rows.hide();
		}
	});
})(jQuery);
