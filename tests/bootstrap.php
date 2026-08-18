<?php
/**
 * PHPUnit bootstrap (no WordPress install required).
 *
 * @package AnkitRawat\LocalSEO
 */

$root = dirname( __DIR__ );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $root . '/tests/' );
}

require_once __DIR__ . '/wordpress-stubs.php';

require_once $root . '/local-seo-by-ankit-rawat.php';
