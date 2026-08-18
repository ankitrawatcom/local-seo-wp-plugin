<?php
/**
 * Plugin bootstrap tests.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

use AnkitRawat\LocalSEO\Admin\Settings;
use AnkitRawat\LocalSEO\Plugin;
use PHPUnit\Framework\TestCase;

final class PluginBootstrapTest extends TestCase {

	protected function setUp(): void {
		lsar_test_reset_state();
	}

	public function test_version_constant_matches_plugin_header() {
		$this->assertSame( '4.0.0', LOCAL_SEO_BY_ANKIT_RAWAT_VERSION );
		$this->assertSame( '4.0.0', Plugin::VERSION );

		$main = file_get_contents( LOCAL_SEO_BY_ANKIT_RAWAT_FILE );
		$this->assertMatchesRegularExpression( '/^\s*\*\s*Version:\s*4\.0\.0/m', $main );
		$this->assertMatchesRegularExpression( '/Text Domain:\s*local-seo-by-ankit-rawat/', $main );
		$this->assertMatchesRegularExpression( '/Requires at least:\s*6\.0/', $main );
		$this->assertDoesNotMatchRegularExpression( '/Requires WP:/', $main );
	}

	public function test_activation_writes_namespaced_options() {
		Plugin::activate();
		$this->assertSame( '4.0.0', get_option( Plugin::VERSION_KEY ) );
		$settings = get_option( Plugin::OPTION );
		$this->assertIsArray( $settings );
		$this->assertFalse( $settings['schema_enabled'] );
	}

	public function test_deactivation_does_not_delete_settings() {
		Plugin::activate();
		update_option( Plugin::OPTION, array_merge( Settings::defaults(), array( 'business_name' => 'Keep Me' ) ) );
		Plugin::deactivate();
		$settings = get_option( Plugin::OPTION );
		$this->assertSame( 'Keep Me', $settings['business_name'] );
	}

	public function test_settings_capability_constant() {
		$this->assertSame( 'manage_options', Plugin::CAPABILITY );
	}

	public function test_uninstall_file_skips_generic_keys() {
		$uninstall = file_get_contents( dirname( LOCAL_SEO_BY_ANKIT_RAWAT_FILE ) . '/uninstall.php' );
		$this->assertStringContainsString( "delete_option( 'local_seo_by_ankit_rawat_options' )", $uninstall );
		$this->assertStringNotContainsString( "delete_option( 'phone' )", $uninstall );
		$this->assertStringNotContainsString( "delete_option( 'business_name' )", $uninstall );
		$this->assertStringNotContainsString( 'google_my_business_api_key', $uninstall );
	}

	public function test_privacy_policy_content_is_registered() {
		$plugin = new Plugin();
		$plugin->privacy_policy();
		$this->assertNotSame( '', $GLOBALS['lsar_privacy'] );
		$this->assertStringContainsString( 'does not send', wp_strip_all_tags( $GLOBALS['lsar_privacy'] ) );
	}

	public function test_uninstall_removes_legacy_marker() {
		$uninstall = file_get_contents( dirname( LOCAL_SEO_BY_ANKIT_RAWAT_FILE ) . '/uninstall.php' );
		$this->assertStringContainsString( "delete_option( 'local_seo_enable_schema' )", $uninstall );
	}
}
