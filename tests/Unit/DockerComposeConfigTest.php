<?php
/**
 * Guards the disposable Docker Compose file (does not boot WordPress).
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DockerComposeConfigTest extends TestCase {

	public function test_compose_file_pins_wordpress_7_1_and_mounts_plugin() {
		$path = dirname( LOCAL_SEO_BY_ANKIT_RAWAT_FILE ) . '/tests/Integration/docker-compose.yml';
		$this->assertFileExists( $path );
		$yml = file_get_contents( $path );
		$this->assertIsString( $yml );
		$this->assertStringContainsString( 'wordpress:7.1-php8.3-apache', $yml );
		$this->assertStringContainsString( 'mariadb:11.4', $yml );
		$this->assertStringContainsString( 'wp-content/plugins/local-seo-by-ankit-rawat', $yml );
		$this->assertStringContainsString( 'lsar_test_db', $yml );
		$this->assertStringContainsString( 'plugin-check', file_get_contents( dirname( $path ) . '/install-site.sh' ) );
		$this->assertStringContainsString( 'woocommerce', file_get_contents( dirname( $path ) . '/install-site.sh' ) );
	}

	public function test_this_suite_is_not_a_live_wordpress_run() {
		$this->assertFalse( defined( 'WP_TESTS_DOMAIN' ), 'No WordPress test suite bootstrap is loaded.' );
	}
}
