<?php
/**
 * Second uninstall pass with namespaced options already absent.
 */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	define( 'WP_UNINSTALL_PLUGIN', 'local-seo-by-ankit-rawat/local-seo-by-ankit-rawat.php' );
}

delete_option( 'local_seo_by_ankit_rawat_options' );
delete_option( 'local_seo_by_ankit_rawat_version' );
delete_option( 'local_seo_enable_schema' );
delete_transient( 'local_seo_json_ld_schema' );

update_option( 'phone', 'EMPTY-UNINSTALL-GENERIC' );
update_option( 'lsar_unrelated_keep_me', 'EMPTY-UNINSTALL-UNRELATED' );

require WP_PLUGIN_DIR . '/local-seo-by-ankit-rawat/uninstall.php';

function lsar_e( $key ) {
	return get_option( $key, 'LSAR_MISSING' );
}

$pass = (
	'LSAR_MISSING' === lsar_e( 'local_seo_by_ankit_rawat_options' )
	&& 'LSAR_MISSING' === lsar_e( 'local_seo_by_ankit_rawat_version' )
	&& 'LSAR_MISSING' === lsar_e( 'local_seo_enable_schema' )
	&& 'EMPTY-UNINSTALL-GENERIC' === lsar_e( 'phone' )
	&& 'EMPTY-UNINSTALL-UNRELATED' === lsar_e( 'lsar_unrelated_keep_me' )
);

echo wp_json_encode( array( 'pass' => $pass, 'phone' => lsar_e( 'phone' ) ), JSON_UNESCAPED_SLASHES ) . "\n";
