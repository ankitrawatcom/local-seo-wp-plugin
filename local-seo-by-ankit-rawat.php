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

	/*
	 * Strict PSR-4 class suffix: no "..", no slashes, no NULs.
	 * Mapping is deterministic: Namespace\Foo\Bar → src/Foo/Bar.php.
	 */
	if ( '' === $relative || false !== strpos( $relative, "\0" ) || false !== strpos( $relative, '..' ) ) {
		return;
	}

	if ( 1 !== preg_match( '/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $relative ) ) {
		return;
	}

	$src_dir  = LOCAL_SEO_BY_ANKIT_RAWAT_DIR . 'src';
	$src_real = realpath( $src_dir );
	if ( false === $src_real || ! is_dir( $src_real ) ) {
		return;
	}

	$candidate = $src_real . DIRECTORY_SEPARATOR . str_replace( '\\', DIRECTORY_SEPARATOR, $relative ) . '.php';
	if ( ! is_readable( $candidate ) ) {
		return;
	}

	$file_real = realpath( $candidate );
	if ( false === $file_real ) {
		return;
	}

	$src_prefix = strtolower( str_replace( '\\', '/', $src_real ) ) . '/';
	$file_norm  = strtolower( str_replace( '\\', '/', $file_real ) );
	if ( 0 !== strpos( $file_norm, $src_prefix ) ) {
		return;
	}

	require $file_real;
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
