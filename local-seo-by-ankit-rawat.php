<?php
/**
 * Plugin Name:       Local SEO By Ankit Rawat
 * Plugin URI:        https://ankitrawat.com/local-seo-by-ankit-rawat/
 * Description:       Outputs LocalBusiness JSON-LD and optional WooCommerce product structured data from a settings screen.
 * Version:           4.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Ankit Rawat
 * Author URI:        https://ankitrawat.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       local-seo-by-ankit-rawat
 * Domain Path:       /languages
 *
 * @package AnkitRawat\LocalSEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'LOCAL_SEO_BY_ANKIT_RAWAT_VERSION', '4.0.0' );
define( 'LOCAL_SEO_BY_ANKIT_RAWAT_FILE', __FILE__ );
define( 'LOCAL_SEO_BY_ANKIT_RAWAT_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOCAL_SEO_BY_ANKIT_RAWAT_URL', plugin_dir_url( __FILE__ ) );

/**
 * Simple PSR-4 autoloader for the AnkitRawat\LocalSEO namespace.
 *
 * Production installs do not require Composer.
 *
 * @param string $class_name Fully-qualified class name.
 * @return void
 */
function local_seo_by_ankit_rawat_autoload( $class_name ) {
	$prefix = 'AnkitRawat\\LocalSEO\\';
	if ( 0 !== strpos( $class_name, $prefix ) ) {
		return;
	}

	$relative = substr( $class_name, strlen( $prefix ) );
	$file     = LOCAL_SEO_BY_ANKIT_RAWAT_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';

	if ( is_readable( $file ) ) {
		require $file;
	}
}

spl_autoload_register( 'local_seo_by_ankit_rawat_autoload' );

/**
 * Boot the plugin.
 *
 * @return void
 */
function local_seo_by_ankit_rawat_boot() {
	$plugin = new \AnkitRawat\LocalSEO\Plugin();
	$plugin->register();
}

local_seo_by_ankit_rawat_boot();

register_activation_hook( LOCAL_SEO_BY_ANKIT_RAWAT_FILE, array( '\AnkitRawat\LocalSEO\Plugin', 'activate' ) );
register_deactivation_hook( LOCAL_SEO_BY_ANKIT_RAWAT_FILE, array( '\AnkitRawat\LocalSEO\Plugin', 'deactivate' ) );
