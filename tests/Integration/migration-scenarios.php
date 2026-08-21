<?php
/**
 * Real-runtime 3.3 → 4.0.0 migration scenarios using Migrator::maybe_run().
 *
 * Development helper. Not production plugin source.
 */

use AnkitRawat\LocalSEO\Migration\Migrator;
use AnkitRawat\LocalSEO\Plugin;

function lsar_get( $key ) {
	return get_option( $key, 'LSAR_MISSING' );
}

function lsar_dump_keys() {
	$keys = array(
		'local_seo_enable_schema',
		'local_seo_by_ankit_rawat_options',
		'local_seo_by_ankit_rawat_version',
		'business_name',
		'phone',
		'country',
		'street_address',
		'locality',
		'region',
		'postal_code',
		'business_type',
		'business_logo',
		'google_my_business_place_id',
		'google_my_business_api_key',
		'woocommerce_product_schema',
		'woocommerce_price_currency',
		'aggregate_rating',
		'review_count',
		'social_profiles',
		'business_images',
		'price_range',
		'latitude',
		'longitude',
	);
	$out = array();
	foreach ( $keys as $key ) {
		$out[ $key ] = lsar_get( $key );
	}
	$out['_transient'] = get_transient( Plugin::LEGACY_CACHE );
	return $out;
}

function lsar_clear_plugin_state() {
	delete_option( Plugin::OPTION );
	delete_option( Plugin::VERSION_KEY );
	delete_option( Migrator::LEGACY_MARKER_OPTION );
	delete_transient( Plugin::LEGACY_CACHE );
}

function lsar_seed_generic_nap( $prefix ) {
	update_option( 'business_type', 'Store' );
	update_option( 'business_name', $prefix . ' Shop' );
	update_option( 'street_address', '9 Market' );
	update_option( 'locality', 'Boston' );
	update_option( 'region', 'MA' );
	update_option( 'postal_code', '02108' );
	update_option( 'country', 'US' );
	update_option( 'phone', '617-555-0199' );
	update_option( 'price_range', '$' );
	update_option( 'business_logo', 'https://example.test/old-logo.png' );
	update_option( 'business_images', 'https://example.test/one.jpg, https://example.test/two.jpg' );
	update_option( 'social_profiles', 'https://facebook.com/legacy' );
	update_option( 'latitude', '42.3601' );
	update_option( 'longitude', '-71.0589' );
	update_option( 'google_my_business_place_id', 'ChIJlegacy' );
	update_option( 'google_my_business_api_key', 'secret-key-do-not-copy' );
	update_option( 'aggregate_rating', '4.5' );
	update_option( 'review_count', '12' );
	update_option( 'woocommerce_product_schema', '1' );
	update_option( 'woocommerce_price_currency', 'usd' );
}

$report = array(
	'marker_contract' => array(
		'legacy_marker_option' => Migrator::LEGACY_MARKER_OPTION,
		'option_missing_sentinel' => Migrator::OPTION_MISSING,
		'version_gate' => Plugin::VERSION_KEY . ' >= 4.0.0 skips maybe_run',
		'legacy_cache' => Plugin::LEGACY_CACHE,
		'note' => '3.3 never stored a version option. Marker is presence of local_seo_enable_schema, including stored 0.',
	),
	'scenarios' => array(),
);

// --- Scenario: no genuine 3.3 marker ---
lsar_clear_plugin_state();
lsar_seed_generic_nap( 'OTHER' );
delete_option( 'local_seo_enable_schema' );
$before = lsar_dump_keys();
Migrator::maybe_run();
$after = lsar_dump_keys();
$new   = is_array( $after['local_seo_by_ankit_rawat_options'] ) ? $after['local_seo_by_ankit_rawat_options'] : array();
$report['scenarios']['no_marker'] = array(
	'before' => $before,
	'after'  => $after,
	'pass'   => (
		( ! isset( $new['phone'] ) || '' === $new['phone'] )
		&& ( ! isset( $new['business_name'] ) || '' === $new['business_name'] )
		&& 'OTHER Shop' === $after['business_name']
		&& '617-555-0199' === $after['phone']
		&& '4.0.0' === $after['local_seo_by_ankit_rawat_version']
		&& 'LSAR_MISSING' === $after['local_seo_enable_schema']
	),
);

// --- Scenario: genuine 3.3 ---
lsar_clear_plugin_state();
lsar_seed_generic_nap( 'Legacy' );
update_option( 'local_seo_enable_schema', '1' );
set_transient( Plugin::LEGACY_CACHE, '{"stale":true}', DAY_IN_SECONDS );
$before = lsar_dump_keys();
Migrator::maybe_run();
$after = lsar_dump_keys();
$new   = is_array( $after['local_seo_by_ankit_rawat_options'] ) ? $after['local_seo_by_ankit_rawat_options'] : array();
$serialized = wp_json_encode( $new );
$report['scenarios']['genuine_3_3'] = array(
	'before' => $before,
	'after_namespaced' => $new,
	'after_generics' => array(
		'local_seo_enable_schema' => $after['local_seo_enable_schema'],
		'business_name' => $after['business_name'],
		'phone' => $after['phone'],
		'google_my_business_api_key' => $after['google_my_business_api_key'],
		'version' => $after['local_seo_by_ankit_rawat_version'],
		'transient' => $after['_transient'],
	),
	'pass' => (
		true === $new['schema_enabled']
		&& 'Store' === $new['business_type']
		&& 'Legacy Shop' === $new['business_name']
		&& '9 Market' === $new['street_address']
		&& 'Boston' === $new['locality']
		&& 'MA' === $new['region']
		&& '02108' === $new['postal_code']
		&& 'US' === $new['country']
		&& '617-555-0199' === $new['phone']
		&& 'https://example.test/old-logo.png' === $new['logo']
		&& 'ChIJlegacy' === $new['place_id']
		&& 'USD' === $new['woocommerce_currency']
		&& true === $new['woocommerce_product_schema']
		&& '4.5' === $new['legacy_aggregate_rating']
		&& '12' === $new['legacy_review_count']
		&& false === strpos( (string) $serialized, 'secret-key-do-not-copy' )
		&& ! isset( $new['google_my_business_api_key'] )
		&& 'secret-key-do-not-copy' === $after['google_my_business_api_key']
		&& 'Legacy Shop' === $after['business_name']
		&& '1' === (string) $after['local_seo_enable_schema']
		&& '4.0.0' === $after['local_seo_by_ankit_rawat_version']
		&& false === $after['_transient']
	),
);

// --- Scenario: existing 4.0 values win ---
lsar_clear_plugin_state();
update_option(
	Plugin::OPTION,
	array(
		'schema_enabled'             => true,
		'business_type'              => 'Restaurant',
		'business_name'              => 'Already Four',
		'phone'                      => '111-222-3333',
		'street_address'             => '',
		'locality'                   => '',
		'region'                     => '',
		'postal_code'                => '',
		'country'                    => '',
		'price_range'                => '',
		'logo'                       => '',
		'images'                     => array(),
		'social_profiles'            => array(),
		'latitude'                   => '',
		'longitude'                  => '',
		'place_id'                   => '',
		'woocommerce_product_schema' => true,
		'woocommerce_currency'       => 'USD',
		'legacy_aggregate_rating'    => '',
		'legacy_review_count'        => '',
	)
);
update_option( 'local_seo_enable_schema', '1' );
update_option( 'business_name', 'Legacy Should Not Win' );
update_option( 'phone', '000-LEGACY' );
update_option( 'business_type', 'Store' );
$before = lsar_dump_keys();
Migrator::maybe_run();
$after = lsar_dump_keys();
$new   = $after['local_seo_by_ankit_rawat_options'];
$report['scenarios']['no_overwrite'] = array(
	'before_namespaced_name' => is_array( $before['local_seo_by_ankit_rawat_options'] ) ? $before['local_seo_by_ankit_rawat_options']['business_name'] : null,
	'after_namespaced_name'  => is_array( $new ) ? $new['business_name'] : null,
	'after_namespaced_phone' => is_array( $new ) ? $new['phone'] : null,
	'after_namespaced_type'  => is_array( $new ) ? $new['business_type'] : null,
	'generic_name'           => $after['business_name'],
	'pass'                   => (
		is_array( $new )
		&& 'Already Four' === $new['business_name']
		&& '111-222-3333' === $new['phone']
		&& 'Restaurant' === $new['business_type']
		&& 'Legacy Should Not Win' === $after['business_name']
	),
);

// --- Scenario: idempotent maybe_run ---
$first = get_option( Plugin::OPTION );
$first['business_name'] = 'Kept New Name';
update_option( Plugin::OPTION, $first );
update_option( 'business_name', 'Changed After Migration' );
Migrator::maybe_run();
$second = get_option( Plugin::OPTION );
$report['scenarios']['idempotent'] = array(
	'namespaced' => is_array( $second ) ? $second['business_name'] : null,
	'generic'    => lsar_get( 'business_name' ),
	'version'    => lsar_get( Plugin::VERSION_KEY ),
	'pass'       => (
		is_array( $second )
		&& 'Kept New Name' === $second['business_name']
		&& 'Changed After Migration' === lsar_get( 'business_name' )
		&& '4.0.0' === lsar_get( Plugin::VERSION_KEY )
	),
);

$all = true;
foreach ( $report['scenarios'] as $row ) {
	if ( empty( $row['pass'] ) ) {
		$all = false;
	}
}
$report['all_pass'] = $all;

echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
