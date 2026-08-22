<?php
/**
 * Disposable helper: print site/catalog-related WordPress options.
 */
$opts = get_option( 'local_seo_by_ankit_rawat_options', array() );
echo "business_type=" . ( isset( $opts['business_type'] ) ? $opts['business_type'] : '' ) . "\n";
echo "schema_enabled=" . ( ! empty( $opts['schema_enabled'] ) ? '1' : '0' ) . "\n";
echo "woocommerce_product_schema=" . ( ! empty( $opts['woocommerce_product_schema'] ) ? '1' : '0' ) . "\n";
echo "show_on_front=" . get_option( 'show_on_front' ) . "\n";
echo "page_on_front=" . get_option( 'page_on_front' ) . "\n";
echo "page_for_posts=" . get_option( 'page_for_posts' ) . "\n";
echo "woocommerce_shop_page_id=" . get_option( 'woocommerce_shop_page_id' ) . "\n";
echo "home_url=" . home_url( '/' ) . "\n";
if ( function_exists( 'wc_get_page_permalink' ) ) {
	echo "shop_url=" . wc_get_page_permalink( 'shop' ) . "\n";
}
$pages = get_pages( array( 'number' => 20 ) );
if ( is_array( $pages ) ) {
	foreach ( $pages as $page ) {
		echo "page id={$page->ID} slug={$page->post_name} title={$page->post_title} status={$page->post_status}\n";
	}
}
$products = function_exists( 'wc_get_products' ) ? wc_get_products( array( 'status' => 'publish', 'limit' => 10, 'orderby' => 'date', 'order' => 'DESC', 'return' => 'ids' ) ) : array();
echo "published_product_ids=" . implode( ',', $products ) . "\n";
