<?php
/**
 * Parse saved settings HTML helpers are not needed; PowerShell/curl drives HTTP.
 * This file dumps WC schema after each settings-regression phase via WP-CLI.
 */
$opts = get_option( 'local_seo_by_ankit_rawat_options', array() );
echo wp_json_encode(
	array(
		'woocommerce_product_schema' => isset( $opts['woocommerce_product_schema'] ) ? $opts['woocommerce_product_schema'] : null,
		'business_type'              => isset( $opts['business_type'] ) ? $opts['business_type'] : null,
		'business_name'              => isset( $opts['business_name'] ) ? $opts['business_name'] : null,
		'schema_enabled'             => isset( $opts['schema_enabled'] ) ? $opts['schema_enabled'] : null,
	),
	JSON_UNESCAPED_SLASHES
) . "\n";
