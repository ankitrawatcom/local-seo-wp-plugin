<?php
/**
 * Uninstall handler.
 *
 * Removes only namespaced 4.0 data. Generic 3.3 option names are left in place
 * because they may belong to another plugin.
 *
 * @package AnkitRawat\LocalSEO
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'local_seo_by_ankit_rawat_options' );
delete_option( 'local_seo_by_ankit_rawat_version' );
delete_transient( 'local_seo_json_ld_schema' );

/*
 * Unique 3.3 option that is clearly this plugin. Safe to remove.
 * Generic keys such as `phone` and `business_name` are NOT deleted.
 */
delete_option( 'local_seo_enable_schema' );

delete_metadata( 'user', 0, 'local_seo_by_ankit_rawat_activation_notice_dismissed', '', true );
