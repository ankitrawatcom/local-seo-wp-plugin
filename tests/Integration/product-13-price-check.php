<?php
/**
 * One-off WP-CLI helper: print parent 13 min/max prices.
 */
$product = wc_get_product( 13 );
if ( ! $product ) {
	echo "missing\n";
	return;
}
echo 'type=' . $product->get_type() . "\n";
echo 'price=' . $product->get_price() . "\n";
echo 'min=' . $product->get_variation_price( 'min', true ) . "\n";
echo 'max=' . $product->get_variation_price( 'max', true ) . "\n";
echo 'regular_min=' . $product->get_variation_regular_price( 'min', true ) . "\n";
echo 'regular_max=' . $product->get_variation_regular_price( 'max', true ) . "\n";
echo 'currency=' . get_woocommerce_currency() . "\n";
