<?php
/**
 * Seed uninstall fixtures, then include the real uninstall.php.
 *
 * Must define WP_UNINSTALL_PLUGIN before loading uninstall.php.
 * Invoked via wp eval-file AFTER WordPress has loaded; define the constant
 * immediately before require, matching WordPress core uninstall bootstrap.
 */

use AnkitRawat\LocalSEO\Plugin;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	define( 'WP_UNINSTALL_PLUGIN', 'local-seo-by-ankit-rawat/local-seo-by-ankit-rawat.php' );
}

function lsar_u( $key ) {
	return get_option( $key, 'LSAR_MISSING' );
}

global $wpdb;

$before_tables = $wpdb->get_col( 'SHOW TABLES' );

update_option(
	Plugin::OPTION,
	array(
		'business_name'              => 'Uninstall Me',
		'schema_enabled'             => true,
		'woocommerce_product_schema' => true,
		'business_type'              => 'Store',
	)
);
update_option( Plugin::VERSION_KEY, Plugin::VERSION, false );
update_option( 'local_seo_enable_schema', '1' );
update_option( 'phone', 'KEEP-GENERIC' );
update_option( 'business_name', 'KEEP-GENERIC-NAME' );
update_option( 'google_my_business_api_key', 'KEEP-API-KEY' );
update_option( 'lsar_unrelated_keep_me', 'UNRELATED-VALUE' );
set_transient( Plugin::LEGACY_CACHE, '{"stale":true}', DAY_IN_SECONDS );

$before = array(
	'local_seo_by_ankit_rawat_options' => lsar_u( Plugin::OPTION ),
	'local_seo_by_ankit_rawat_version' => lsar_u( Plugin::VERSION_KEY ),
	'local_seo_enable_schema'          => lsar_u( 'local_seo_enable_schema' ),
	'phone'                            => lsar_u( 'phone' ),
	'business_name'                    => lsar_u( 'business_name' ),
	'google_my_business_api_key'       => lsar_u( 'google_my_business_api_key' ),
	'lsar_unrelated_keep_me'           => lsar_u( 'lsar_unrelated_keep_me' ),
	'blogname'                         => lsar_u( 'blogname' ),
	'woocommerce_version'              => lsar_u( 'woocommerce_version' ),
	'transient'                        => get_transient( Plugin::LEGACY_CACHE ),
	'table_count'                      => count( $before_tables ),
);

require WP_PLUGIN_DIR . '/local-seo-by-ankit-rawat/uninstall.php';

$after_tables = $wpdb->get_col( 'SHOW TABLES' );
$removed_tables = array_values( array_diff( $before_tables, $after_tables ) );

$after = array(
	'local_seo_by_ankit_rawat_options' => lsar_u( Plugin::OPTION ),
	'local_seo_by_ankit_rawat_version' => lsar_u( Plugin::VERSION_KEY ),
	'local_seo_enable_schema'          => lsar_u( 'local_seo_enable_schema' ),
	'phone'                            => lsar_u( 'phone' ),
	'business_name'                    => lsar_u( 'business_name' ),
	'google_my_business_api_key'       => lsar_u( 'google_my_business_api_key' ),
	'lsar_unrelated_keep_me'           => lsar_u( 'lsar_unrelated_keep_me' ),
	'blogname'                         => lsar_u( 'blogname' ),
	'woocommerce_version'              => lsar_u( 'woocommerce_version' ),
	'transient'                        => get_transient( Plugin::LEGACY_CACHE ),
	'table_count'                      => count( $after_tables ),
	'removed_tables'                   => $removed_tables,
);

$pass = (
	'LSAR_MISSING' === $after['local_seo_by_ankit_rawat_options']
	&& 'LSAR_MISSING' === $after['local_seo_by_ankit_rawat_version']
	&& 'LSAR_MISSING' === $after['local_seo_enable_schema']
	&& 'KEEP-GENERIC' === $after['phone']
	&& 'KEEP-GENERIC-NAME' === $after['business_name']
	&& 'KEEP-API-KEY' === $after['google_my_business_api_key']
	&& 'UNRELATED-VALUE' === $after['lsar_unrelated_keep_me']
	&& $before['blogname'] === $after['blogname']
	&& $before['woocommerce_version'] === $after['woocommerce_version']
	&& false === $after['transient']
	&& array() === $removed_tables
);

echo wp_json_encode(
	array(
		'before' => $before,
		'after'  => $after,
		'pass'   => $pass,
	),
	JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
) . "\n";
