<?php
/**
 * Settings sanitization tests.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

use AnkitRawat\LocalSEO\Admin\Settings;
use AnkitRawat\LocalSEO\Support\Sanitize;
use PHPUnit\Framework\TestCase;

final class SettingsSanitizerTest extends TestCase {

	protected function setUp(): void {
		lsar_test_reset_state();
	}

	public function test_defaults_disable_schema() {
		$defaults = Settings::defaults();
		$this->assertFalse( $defaults['schema_enabled'] );
		$this->assertSame( 'LocalBusiness', $defaults['business_type'] );
		$this->assertSame( array(), $defaults['images'] );
	}

	public function test_sanitize_accepts_valid_values() {
		$clean = Settings::sanitize(
			array(
				'schema_enabled'  => '1',
				'business_type'   => 'Restaurant',
				'business_name'   => ' Cafe <b>Luigi</b> ',
				'phone'           => '+1 (415) 555-0100',
				'logo'            => 'https://example.test/logo.png',
				'images'          => "https://example.test/a.jpg,\nhttps://example.test/b.jpg",
				'social_profiles' => 'https://twitter.com/example',
				'latitude'        => '37.7749',
				'longitude'       => '-122.4194',
				'price_range'     => '$$',
			)
		);

		$this->assertTrue( $clean['schema_enabled'] );
		$this->assertSame( 'Restaurant', $clean['business_type'] );
		$this->assertSame( 'Cafe Luigi', $clean['business_name'] );
		$this->assertSame( 'https://example.test/logo.png', $clean['logo'] );
		$this->assertCount( 2, $clean['images'] );
		$this->assertSame( array( 'https://twitter.com/example' ), $clean['social_profiles'] );
		$this->assertNotSame( '', $clean['latitude'] );
		$this->assertNotSame( '', $clean['longitude'] );
	}

	public function test_sanitize_rejects_unknown_business_type() {
		$clean = Settings::sanitize( array( 'business_type' => 'Attack<script>' ) );
		$this->assertSame( 'LocalBusiness', $clean['business_type'] );
	}

	public function test_sanitize_rejects_javascript_urls() {
		$clean = Settings::sanitize(
			array(
				'logo'            => 'javascript:alert(1)',
				'images'          => 'javascript:alert(1),https://example.test/ok.png',
				'social_profiles' => 'data:text/html,hi',
			)
		);
		$this->assertSame( '', $clean['logo'] );
		$this->assertSame( array( 'https://example.test/ok.png' ), $clean['images'] );
		$this->assertSame( array(), $clean['social_profiles'] );
	}

	public function test_sanitize_rejects_invalid_geo() {
		$clean = Settings::sanitize(
			array(
				'latitude'  => '91',
				'longitude' => '-181',
			)
		);
		$this->assertSame( '', $clean['latitude'] );
		$this->assertSame( '', $clean['longitude'] );
	}

	public function test_sanitize_unchecked_schema_checkbox_is_false() {
		$clean = Settings::sanitize( array() );
		$this->assertFalse( $clean['schema_enabled'] );
	}

	public function test_woocommerce_checkbox_absent_preserves_true() {
		update_option(
			\AnkitRawat\LocalSEO\Plugin::OPTION,
			array_merge( Settings::defaults(), array( 'woocommerce_product_schema' => true ) )
		);
		$clean = Settings::sanitize( array( 'business_name' => 'NAP only' ) );
		$this->assertTrue( $clean['woocommerce_product_schema'] );
		$this->assertSame( 'NAP only', $clean['business_name'] );
	}

	public function test_woocommerce_checkbox_absent_preserves_false() {
		update_option(
			\AnkitRawat\LocalSEO\Plugin::OPTION,
			array_merge( Settings::defaults(), array( 'woocommerce_product_schema' => false ) )
		);
		$clean = Settings::sanitize( array( 'business_name' => 'NAP only' ) );
		$this->assertFalse( $clean['woocommerce_product_schema'] );
	}

	public function test_woocommerce_checkbox_submitted_checked_becomes_true() {
		update_option(
			\AnkitRawat\LocalSEO\Plugin::OPTION,
			array_merge( Settings::defaults(), array( 'woocommerce_product_schema' => false ) )
		);
		$clean = Settings::sanitize( array( 'woocommerce_product_schema' => '1' ) );
		$this->assertTrue( $clean['woocommerce_product_schema'] );
	}

	public function test_woocommerce_checkbox_submitted_unchecked_becomes_false() {
		update_option(
			\AnkitRawat\LocalSEO\Plugin::OPTION,
			array_merge( Settings::defaults(), array( 'woocommerce_product_schema' => true ) )
		);
		$clean = Settings::sanitize( array( 'woocommerce_product_schema' => '0' ) );
		$this->assertFalse( $clean['woocommerce_product_schema'] );
	}

	public function test_sanitize_rejects_invalid_currency() {
		$clean = Settings::sanitize( array( 'woocommerce_currency' => 'USDX' ) );
		$this->assertSame( '', $clean['woocommerce_currency'] );

		$clean = Settings::sanitize( array( 'woocommerce_currency' => 'usd' ) );
		$this->assertSame( 'USD', $clean['woocommerce_currency'] );
	}

	public function test_business_type_allowlist() {
		$this->assertSame(
			array( 'LocalBusiness', 'Restaurant', 'Hotel', 'ProfessionalService', 'Store' ),
			Sanitize::business_types()
		);
	}

	public function test_phone_allows_extension_hash() {
		$clean = Settings::sanitize( array( 'phone' => '+1 415 555 0100 #12' ) );
		$this->assertStringContainsString( '#12', $clean['phone'] );
	}
}
