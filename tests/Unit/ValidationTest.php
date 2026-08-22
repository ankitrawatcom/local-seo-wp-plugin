<?php
/**
 * Unit tests for inline settings validation feedback.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

use AnkitRawat\LocalSEO\Admin\Settings;
use AnkitRawat\LocalSEO\Plugin;
use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase {

	protected function setUp(): void {
		lsar_test_reset_state();
	}

	/** @return array[] */
	private function errors(): array {
		return $GLOBALS['lsar_test_settings_errors'];
	}

	/** @return string[] */
	private function error_codes(): array {
		return array_column( $this->errors(), 'code' );
	}

	// ─── No false positives ───────────────────────────────────────────

	public function test_valid_input_produces_no_errors(): void {
		Settings::sanitize( array(
			'schema_enabled'       => '1',
			'business_name'        => 'Test Biz',
			'logo'                 => 'https://example.com/logo.png',
			'latitude'             => '28.6139',
			'longitude'            => '77.2090',
			'phone'                => '+91 98765 43210',
			'images'               => 'https://example.com/a.jpg,https://example.com/b.jpg',
			'social_profiles'      => 'https://facebook.com/test',
			'woocommerce_currency' => 'USD',
		) );
		$this->assertSame( array(), $this->errors() );
	}

	public function test_empty_input_produces_no_errors(): void {
		Settings::sanitize( array() );
		$this->assertSame( array(), $this->errors() );
	}

	public function test_empty_optional_fields_no_errors(): void {
		Settings::sanitize( array(
			'logo'                 => '',
			'latitude'             => '',
			'longitude'            => '',
			'phone'                => '',
			'images'               => '',
			'social_profiles'      => '',
			'woocommerce_currency' => '',
		) );
		$this->assertSame( array(), $this->errors() );
	}

	public function test_whitespace_only_fields_no_errors(): void {
		Settings::sanitize( array(
			'logo'      => '   ',
			'latitude'  => '  ',
			'longitude' => '  ',
			'phone'     => '  ',
		) );
		$this->assertSame( array(), $this->errors() );
	}

	// ─── Logo ─────────────────────────────────────────────────────────

	public function test_invalid_logo_javascript_produces_error(): void {
		Settings::sanitize( array( 'logo' => 'javascript:alert(1)' ) );
		$this->assertContains( 'invalid_logo', $this->error_codes() );
	}

	public function test_invalid_logo_ftp_produces_error(): void {
		Settings::sanitize( array( 'logo' => 'ftp://example.com/logo.png' ) );
		$this->assertContains( 'invalid_logo', $this->error_codes() );
	}

	public function test_invalid_logo_plain_text_produces_error(): void {
		Settings::sanitize( array( 'logo' => 'not a url at all' ) );
		$this->assertContains( 'invalid_logo', $this->error_codes() );
	}

	public function test_valid_logo_https_no_error(): void {
		Settings::sanitize( array( 'logo' => 'https://example.com/logo.png' ) );
		$this->assertNotContains( 'invalid_logo', $this->error_codes() );
	}

	public function test_valid_logo_http_no_error(): void {
		Settings::sanitize( array( 'logo' => 'http://example.com/logo.png' ) );
		$this->assertNotContains( 'invalid_logo', $this->error_codes() );
	}

	// ─── Latitude ─────────────────────────────────────────────────────

	public function test_non_numeric_latitude_produces_error(): void {
		Settings::sanitize( array( 'latitude' => 'abc' ) );
		$this->assertContains( 'invalid_latitude', $this->error_codes() );
	}

	public function test_out_of_range_latitude_high_produces_error(): void {
		Settings::sanitize( array( 'latitude' => '91' ) );
		$this->assertContains( 'invalid_latitude', $this->error_codes() );
	}

	public function test_out_of_range_latitude_low_produces_error(): void {
		Settings::sanitize( array( 'latitude' => '-91' ) );
		$this->assertContains( 'invalid_latitude', $this->error_codes() );
	}

	public function test_valid_latitude_boundary_90_no_error(): void {
		Settings::sanitize( array( 'latitude' => '90' ) );
		$this->assertNotContains( 'invalid_latitude', $this->error_codes() );
	}

	public function test_valid_latitude_boundary_negative_90_no_error(): void {
		Settings::sanitize( array( 'latitude' => '-90' ) );
		$this->assertNotContains( 'invalid_latitude', $this->error_codes() );
	}

	public function test_valid_latitude_zero_no_error(): void {
		Settings::sanitize( array( 'latitude' => '0' ) );
		$this->assertNotContains( 'invalid_latitude', $this->error_codes() );
	}

	// ─── Longitude ────────────────────────────────────────────────────

	public function test_non_numeric_longitude_produces_error(): void {
		Settings::sanitize( array( 'longitude' => 'xyz' ) );
		$this->assertContains( 'invalid_longitude', $this->error_codes() );
	}

	public function test_out_of_range_longitude_high_produces_error(): void {
		Settings::sanitize( array( 'longitude' => '181' ) );
		$this->assertContains( 'invalid_longitude', $this->error_codes() );
	}

	public function test_out_of_range_longitude_low_produces_error(): void {
		Settings::sanitize( array( 'longitude' => '-181' ) );
		$this->assertContains( 'invalid_longitude', $this->error_codes() );
	}

	public function test_valid_longitude_boundary_180_no_error(): void {
		Settings::sanitize( array( 'longitude' => '180' ) );
		$this->assertNotContains( 'invalid_longitude', $this->error_codes() );
	}

	public function test_valid_longitude_boundary_negative_180_no_error(): void {
		Settings::sanitize( array( 'longitude' => '-180' ) );
		$this->assertNotContains( 'invalid_longitude', $this->error_codes() );
	}

	// ─── Currency ─────────────────────────────────────────────────────

	public function test_invalid_currency_too_long_produces_error(): void {
		Settings::sanitize( array( 'woocommerce_currency' => 'USDX' ) );
		$this->assertContains( 'invalid_currency', $this->error_codes() );
	}

	public function test_invalid_currency_too_short_produces_error(): void {
		Settings::sanitize( array( 'woocommerce_currency' => 'US' ) );
		$this->assertContains( 'invalid_currency', $this->error_codes() );
	}

	public function test_invalid_currency_numbers_produces_error(): void {
		Settings::sanitize( array( 'woocommerce_currency' => '123' ) );
		$this->assertContains( 'invalid_currency', $this->error_codes() );
	}

	public function test_valid_currency_no_error(): void {
		Settings::sanitize( array( 'woocommerce_currency' => 'USD' ) );
		$this->assertNotContains( 'invalid_currency', $this->error_codes() );
	}

	public function test_valid_currency_lowercase_no_error(): void {
		Settings::sanitize( array( 'woocommerce_currency' => 'eur' ) );
		$this->assertNotContains( 'invalid_currency', $this->error_codes() );
	}

	// ─── Phone ────────────────────────────────────────────────────────

	public function test_non_phone_chars_only_produces_error(): void {
		Settings::sanitize( array( 'phone' => 'abc' ) );
		$this->assertContains( 'invalid_phone', $this->error_codes() );
	}

	public function test_valid_phone_no_error(): void {
		Settings::sanitize( array( 'phone' => '+91 98765 43210' ) );
		$this->assertNotContains( 'invalid_phone', $this->error_codes() );
	}

	public function test_phone_with_some_valid_chars_no_error(): void {
		Settings::sanitize( array( 'phone' => 'abc123' ) );
		$this->assertNotContains( 'invalid_phone', $this->error_codes() );
	}

	// ─── Images ───────────────────────────────────────────────────────

	public function test_all_invalid_images_produces_error(): void {
		Settings::sanitize( array( 'images' => 'not-a-url' ) );
		$this->assertContains( 'invalid_images', $this->error_codes() );
	}

	public function test_partial_invalid_images_produces_error(): void {
		Settings::sanitize( array( 'images' => 'not-a-url,https://example.com/ok.jpg' ) );
		$this->assertContains( 'invalid_images', $this->error_codes() );
	}

	public function test_valid_images_no_error(): void {
		Settings::sanitize( array( 'images' => 'https://example.com/a.jpg,https://example.com/b.jpg' ) );
		$this->assertNotContains( 'invalid_images', $this->error_codes() );
	}

	public function test_empty_images_no_error(): void {
		Settings::sanitize( array( 'images' => '' ) );
		$this->assertNotContains( 'invalid_images', $this->error_codes() );
	}

	public function test_images_array_with_invalid_entries_produces_error(): void {
		Settings::sanitize( array( 'images' => array( 'https://example.com/ok.jpg', 'bad-url' ) ) );
		$this->assertContains( 'invalid_images', $this->error_codes() );
	}

	// ─── Social profiles ──────────────────────────────────────────────

	public function test_invalid_social_profiles_produces_error(): void {
		Settings::sanitize( array( 'social_profiles' => 'not-a-url' ) );
		$this->assertContains( 'invalid_social', $this->error_codes() );
	}

	public function test_valid_social_profiles_no_error(): void {
		Settings::sanitize( array( 'social_profiles' => 'https://facebook.com/test' ) );
		$this->assertNotContains( 'invalid_social', $this->error_codes() );
	}

	// ─── Data safety ──────────────────────────────────────────────────

	public function test_invalid_field_does_not_destroy_valid_fields(): void {
		$clean = Settings::sanitize( array(
			'business_name' => 'My Restaurant',
			'phone'         => '+91 98765 43210',
			'latitude'      => 'abc',
			'logo'          => 'https://example.com/logo.png',
		) );
		$this->assertSame( 'My Restaurant', $clean['business_name'] );
		$this->assertSame( '+91 98765 43210', $clean['phone'] );
		$this->assertSame( 'https://example.com/logo.png', $clean['logo'] );
		$this->assertSame( '', $clean['latitude'] );
	}

	public function test_multiple_invalid_fields_produce_multiple_errors(): void {
		Settings::sanitize( array(
			'latitude'  => 'abc',
			'longitude' => 'xyz',
			'logo'      => 'ftp://bad.com/logo.png',
		) );
		$codes = $this->error_codes();
		$this->assertContains( 'invalid_latitude', $codes );
		$this->assertContains( 'invalid_longitude', $codes );
		$this->assertContains( 'invalid_logo', $codes );
		$this->assertCount( 3, $codes );
	}

	public function test_all_seven_fields_can_error_simultaneously(): void {
		Settings::sanitize( array(
			'logo'                 => 'ftp://bad.com/logo.png',
			'latitude'             => 'abc',
			'longitude'            => 'xyz',
			'woocommerce_currency' => 'NOPE',
			'phone'                => '!!!',
			'images'               => 'not-a-url',
			'social_profiles'      => 'data:text/html,hi',
		) );
		$codes = $this->error_codes();
		$this->assertContains( 'invalid_logo', $codes );
		$this->assertContains( 'invalid_latitude', $codes );
		$this->assertContains( 'invalid_longitude', $codes );
		$this->assertContains( 'invalid_currency', $codes );
		$this->assertContains( 'invalid_phone', $codes );
		$this->assertContains( 'invalid_images', $codes );
		$this->assertContains( 'invalid_social', $codes );
		$this->assertCount( 7, $codes );
	}

	// ─── Error format ─────────────────────────────────────────────────

	public function test_errors_use_type_error(): void {
		Settings::sanitize( array( 'latitude' => 'abc', 'logo' => 'ftp://x' ) );
		foreach ( $this->errors() as $error ) {
			$this->assertSame( 'error', $error['type'] );
		}
	}

	public function test_errors_target_plugin_option(): void {
		Settings::sanitize( array( 'latitude' => 'abc' ) );
		foreach ( $this->errors() as $error ) {
			$this->assertSame( Plugin::OPTION, $error['setting'] );
		}
	}

	public function test_error_codes_are_unique(): void {
		Settings::sanitize( array(
			'latitude'  => 'abc',
			'longitude' => 'xyz',
			'logo'      => 'ftp://x',
		) );
		$codes = $this->error_codes();
		$this->assertSame( $codes, array_unique( $codes ) );
	}

	// ─── Regression ───────────────────────────────────────────────────

	public function test_defaults_unchanged(): void {
		$this->assertCount( 20, Settings::defaults() );
	}

	public function test_option_keys_unchanged(): void {
		$expected = array(
			'schema_enabled', 'business_type', 'business_name', 'street_address',
			'locality', 'region', 'postal_code', 'country', 'phone', 'price_range',
			'logo', 'images', 'social_profiles', 'latitude', 'longitude',
			'place_id', 'woocommerce_product_schema', 'woocommerce_currency',
			'legacy_aggregate_rating', 'legacy_review_count',
		);
		$this->assertSame( $expected, array_keys( Settings::defaults() ) );
	}

	public function test_sanitization_output_unchanged(): void {
		$clean = Settings::sanitize( array(
			'business_type' => 'Attack<script>',
			'latitude'      => '91',
			'longitude'     => '-181',
			'logo'          => 'javascript:alert(1)',
		) );
		$this->assertSame( 'LocalBusiness', $clean['business_type'] );
		$this->assertSame( '', $clean['latitude'] );
		$this->assertSame( '', $clean['longitude'] );
		$this->assertSame( '', $clean['logo'] );
	}

	public function test_valid_sanitization_unchanged(): void {
		$clean = Settings::sanitize( array(
			'schema_enabled' => '1',
			'business_type'  => 'Restaurant',
			'business_name'  => ' Cafe <b>Luigi</b> ',
			'latitude'       => '37.7749',
			'longitude'      => '-122.4194',
		) );
		$this->assertTrue( $clean['schema_enabled'] );
		$this->assertSame( 'Restaurant', $clean['business_type'] );
		$this->assertSame( 'Cafe Luigi', $clean['business_name'] );
		$this->assertNotSame( '', $clean['latitude'] );
		$this->assertNotSame( '', $clean['longitude'] );
	}
}
