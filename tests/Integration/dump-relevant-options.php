<?php
/**
 * Dump Local SEO / 3.3 / unrelated options for migration-uninstall tests.
 */
function lsar_opt( $key ) {
	$v = get_option( $key, 'LSAR_MISSING' );
	return $v;
}

$keys = array(
	'local_seo_enable_schema',
	'local_seo_by_ankit_rawat_options',
	'local_seo_by_ankit_rawat_version',
	'business_type',
	'business_name',
	'street_address',
	'locality',
	'region',
	'postal_code',
	'country',
	'phone',
	'price_range',
	'business_logo',
	'business_images',
	'social_profiles',
	'latitude',
	'longitude',
	'google_my_business_place_id',
	'google_my_business_api_key',
	'woocommerce_product_schema',
	'woocommerce_price_currency',
	'aggregate_rating',
	'review_count',
	'lsar_unrelated_keep_me',
	'blogname',
	'siteurl',
);

$out = array(
	'transient_local_seo_json_ld_schema' => get_transient( 'local_seo_json_ld_schema' ),
	'options'                            => array(),
);
foreach ( $keys as $key ) {
	$out['options'][ $key ] = lsar_opt( $key );
}
echo wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
