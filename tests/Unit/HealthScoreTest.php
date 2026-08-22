<?php
/**
 * Unit tests for the Schema Health Score algorithm.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

use AnkitRawat\LocalSEO\Admin\HealthScore;
use AnkitRawat\LocalSEO\Admin\Settings;
use PHPUnit\Framework\TestCase;

final class HealthScoreTest extends TestCase {

	/**
	 * Merge overrides with defaults for convenience.
	 *
	 * @param array<string, mixed> $overrides Overrides.
	 * @return array<string, mixed>
	 */
	private function settings( array $overrides = array() ): array {
		return array_merge( Settings::defaults(), $overrides );
	}

	/**
	 * Build a complete valid settings array (score 10/10).
	 *
	 * @return array<string, mixed>
	 */
	private function complete_settings(): array {
		return $this->settings( array(
			'schema_enabled'  => true,
			'business_name'   => 'Rawat Consulting',
			'street_address'  => '123 MG Road',
			'locality'        => 'Jaipur',
			'region'          => 'Rajasthan',
			'postal_code'     => '302001',
			'country'         => 'India',
			'phone'           => '+91 98765 43210',
			'price_range'     => '$$',
			'logo'            => 'https://example.com/logo.png',
			'images'          => array( 'https://example.com/store.jpg' ),
			'social_profiles' => array( 'https://facebook.com/rawat' ),
			'latitude'        => '26.9124',
			'longitude'       => '75.7873',
		) );
	}

	// ─── Structure tests ─────────────────────────���─────────────────────

	public function test_result_has_required_keys(): void {
		$result = HealthScore::calculate( $this->settings() );

		$this->assertArrayHasKey( 'score', $result );
		$this->assertArrayHasKey( 'total', $result );
		$this->assertArrayHasKey( 'status', $result );
		$this->assertArrayHasKey( 'checks', $result );
		$this->assertArrayHasKey( 'recommendations', $result );
	}

	public function test_total_is_always_10(): void {
		$this->assertSame( 10, HealthScore::calculate( $this->settings() )['total'] );
		$this->assertSame( 10, HealthScore::calculate( $this->complete_settings() )['total'] );
	}

	public function test_checks_array_always_has_10_items(): void {
		$this->assertCount( 10, HealthScore::calculate( $this->settings() )['checks'] );
		$this->assertCount( 10, HealthScore::calculate( $this->complete_settings() )['checks'] );
	}

	public function test_recommendation_count_equals_failures(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'schema_enabled' => true,
			'business_name'  => 'Test Co',
			'phone'          => '+1 555 123 4567',
		) ) );

		$failed = 10 - $result['score'];
		$this->assertCount( $failed, $result['recommendations'] );
	}

	// ─── Empty / full ──────────────────────────────────────────────────

	public function test_empty_defaults_score_zero(): void {
		$result = HealthScore::calculate( $this->settings() );

		$this->assertSame( 0, $result['score'] );
		$this->assertSame( 'Needs setup', $result['status'] );
		$this->assertCount( 10, $result['recommendations'] );

		foreach ( $result['checks'] as $check ) {
			$this->assertFalse( $check['passed'] );
		}
	}

	public function test_complete_configuration_score_ten(): void {
		$result = HealthScore::calculate( $this->complete_settings() );

		$this->assertSame( 10, $result['score'] );
		$this->assertSame( 'Complete', $result['status'] );
		$this->assertSame( array(), $result['recommendations'] );

		foreach ( $result['checks'] as $check ) {
			$this->assertTrue( $check['passed'] );
		}
	}

	// ─── Check 1: Schema enabled ───────────────────────────────────────

	public function test_schema_enabled_true(): void {
		$result = HealthScore::calculate( $this->settings( array( 'schema_enabled' => true ) ) );
		$this->assertTrue( $result['checks'][0]['passed'] );
		$this->assertSame( 'schema_enabled', $result['checks'][0]['id'] );
	}

	public function test_schema_enabled_false(): void {
		$result = HealthScore::calculate( $this->settings( array( 'schema_enabled' => false ) ) );
		$this->assertFalse( $result['checks'][0]['passed'] );
	}

	public function test_schema_disabled_otherwise_complete(): void {
		$s = $this->complete_settings();
		$s['schema_enabled'] = false;

		$result = HealthScore::calculate( $s );

		$this->assertSame( 9, $result['score'] );
		$this->assertSame( 'Complete', $result['status'] );
		$this->assertCount( 1, $result['recommendations'] );
	}

	// ─── Check 2: Business name ────────────────────────────────────────

	public function test_business_name_valid(): void {
		$result = HealthScore::calculate( $this->settings( array( 'business_name' => 'Test Co' ) ) );
		$this->assertTrue( $result['checks'][1]['passed'] );
	}

	public function test_business_name_too_short_one_char(): void {
		$result = HealthScore::calculate( $this->settings( array( 'business_name' => 'A' ) ) );
		$this->assertFalse( $result['checks'][1]['passed'] );
	}

	public function test_business_name_whitespace(): void {
		$result = HealthScore::calculate( $this->settings( array( 'business_name' => '   ' ) ) );
		$this->assertFalse( $result['checks'][1]['passed'] );
	}

	public function test_business_name_exactly_two_chars(): void {
		$result = HealthScore::calculate( $this->settings( array( 'business_name' => 'AB' ) ) );
		$this->assertTrue( $result['checks'][1]['passed'] );
	}

	public function test_business_name_with_surrounding_spaces(): void {
		$result = HealthScore::calculate( $this->settings( array( 'business_name' => '  AB  ' ) ) );
		$this->assertTrue( $result['checks'][1]['passed'] );
	}

	// ─── Check 3: Core address ─────────────────────────────────────────

	public function test_core_address_all_three_present(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'street_address' => '123 Main',
			'locality'       => 'Jaipur',
			'country'        => 'India',
		) ) );
		$this->assertTrue( $result['checks'][2]['passed'] );
	}

	public function test_core_address_street_only(): void {
		$result = HealthScore::calculate( $this->settings( array( 'street_address' => '123 Main' ) ) );
		$this->assertFalse( $result['checks'][2]['passed'] );
	}

	public function test_core_address_city_only(): void {
		$result = HealthScore::calculate( $this->settings( array( 'locality' => 'Jaipur' ) ) );
		$this->assertFalse( $result['checks'][2]['passed'] );
	}

	public function test_core_address_country_only(): void {
		$result = HealthScore::calculate( $this->settings( array( 'country' => 'India' ) ) );
		$this->assertFalse( $result['checks'][2]['passed'] );
	}

	public function test_core_address_street_and_city_no_country(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'street_address' => '123 Main',
			'locality'       => 'Jaipur',
		) ) );
		$this->assertFalse( $result['checks'][2]['passed'] );
	}

	public function test_core_address_recommendation_only_street_missing(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'locality' => 'Jaipur',
			'country'  => 'India',
		) ) );
		$recs = $result['recommendations'];
		$this->assertContains( 'Add your street address.', $recs );
	}

	public function test_core_address_recommendation_only_city_missing(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'street_address' => '123 Main',
			'country'        => 'India',
		) ) );
		$recs = $result['recommendations'];
		$this->assertContains( 'Add your city or town.', $recs );
	}

	public function test_core_address_recommendation_only_country_missing(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'street_address' => '123 Main',
			'locality'       => 'Jaipur',
		) ) );
		$recs = $result['recommendations'];
		$this->assertContains( 'Add your country.', $recs );
	}

	// ─── Check 4: Address detail ───────────────────────────────────────

	public function test_address_detail_region_only(): void {
		$result = HealthScore::calculate( $this->settings( array( 'region' => 'Rajasthan' ) ) );
		$this->assertTrue( $result['checks'][3]['passed'] );
	}

	public function test_address_detail_postal_only(): void {
		$result = HealthScore::calculate( $this->settings( array( 'postal_code' => '302001' ) ) );
		$this->assertTrue( $result['checks'][3]['passed'] );
	}

	public function test_address_detail_both_present(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'region'      => 'Rajasthan',
			'postal_code' => '302001',
		) ) );
		$this->assertTrue( $result['checks'][3]['passed'] );
	}

	public function test_address_detail_neither(): void {
		$result = HealthScore::calculate( $this->settings() );
		$this->assertFalse( $result['checks'][3]['passed'] );
	}

	// ─── Check 5: Phone ────────────────────────────────────────────────

	public function test_phone_with_7_digits(): void {
		$result = HealthScore::calculate( $this->settings( array( 'phone' => '123 4567' ) ) );
		$this->assertTrue( $result['checks'][4]['passed'] );
	}

	public function test_phone_with_6_digits(): void {
		$result = HealthScore::calculate( $this->settings( array( 'phone' => '12 34 56' ) ) );
		$this->assertFalse( $result['checks'][4]['passed'] );
	}

	public function test_phone_with_country_code(): void {
		$result = HealthScore::calculate( $this->settings( array( 'phone' => '+91 98765 43210' ) ) );
		$this->assertTrue( $result['checks'][4]['passed'] );
	}

	public function test_phone_all_non_digits(): void {
		$result = HealthScore::calculate( $this->settings( array( 'phone' => 'call me' ) ) );
		$this->assertFalse( $result['checks'][4]['passed'] );
	}

	public function test_phone_with_extension(): void {
		$result = HealthScore::calculate( $this->settings( array( 'phone' => '+1 555 123 4567 ext 89' ) ) );
		$this->assertTrue( $result['checks'][4]['passed'] );
	}

	// ─── Check 6: Logo ─────────────────────────────────────────────────

	public function test_logo_valid_https(): void {
		$result = HealthScore::calculate( $this->settings( array( 'logo' => 'https://example.com/logo.png' ) ) );
		$this->assertTrue( $result['checks'][5]['passed'] );
	}

	public function test_logo_valid_http(): void {
		$result = HealthScore::calculate( $this->settings( array( 'logo' => 'http://example.com/logo.png' ) ) );
		$this->assertTrue( $result['checks'][5]['passed'] );
	}

	public function test_logo_uppercase_http(): void {
		$result = HealthScore::calculate( $this->settings( array( 'logo' => 'HTTP://EXAMPLE.COM/X.PNG' ) ) );
		$this->assertTrue( $result['checks'][5]['passed'] );
	}

	public function test_logo_plain_text(): void {
		$result = HealthScore::calculate( $this->settings( array( 'logo' => 'my-logo' ) ) );
		$this->assertFalse( $result['checks'][5]['passed'] );
	}

	public function test_logo_ftp_url(): void {
		$result = HealthScore::calculate( $this->settings( array( 'logo' => 'ftp://example.com/img.png' ) ) );
		$this->assertFalse( $result['checks'][5]['passed'] );
	}

	// ─── Check 7: Photos ───────────────────────────────────────────────

	public function test_photos_empty_array(): void {
		$result = HealthScore::calculate( $this->settings( array( 'images' => array() ) ) );
		$this->assertFalse( $result['checks'][6]['passed'] );
	}

	public function test_photos_invalid_urls(): void {
		$result = HealthScore::calculate( $this->settings( array( 'images' => array( 'foo', 'bar' ) ) ) );
		$this->assertFalse( $result['checks'][6]['passed'] );
	}

	public function test_photos_one_valid(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'images' => array( 'https://example.com/1.jpg' ),
		) ) );
		$this->assertTrue( $result['checks'][6]['passed'] );
	}

	public function test_photos_mixed_valid_invalid(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'images' => array( 'foo', 'https://example.com/1.jpg' ),
		) ) );
		$this->assertTrue( $result['checks'][6]['passed'] );
	}

	public function test_photos_all_empty_strings(): void {
		$result = HealthScore::calculate( $this->settings( array( 'images' => array( '', '', '' ) ) ) );
		$this->assertFalse( $result['checks'][6]['passed'] );
	}

	public function test_photos_malformed_string_not_array(): void {
		$result = HealthScore::calculate( $this->settings( array( 'images' => 'not-an-array' ) ) );
		$this->assertFalse( $result['checks'][6]['passed'] );
	}

	// ─── Check 8: Coordinates ──────────────────────────────────────────

	public function test_coordinates_both_valid(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'latitude'  => '26.9',
			'longitude' => '75.7',
		) ) );
		$this->assertTrue( $result['checks'][7]['passed'] );
	}

	public function test_coordinates_latitude_only(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'latitude'  => '26.9',
			'longitude' => '',
		) ) );
		$this->assertFalse( $result['checks'][7]['passed'] );
	}

	public function test_coordinates_longitude_only(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'latitude'  => '',
			'longitude' => '75.7',
		) ) );
		$this->assertFalse( $result['checks'][7]['passed'] );
	}

	public function test_coordinates_latitude_non_numeric(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'latitude'  => 'abc',
			'longitude' => '75.7',
		) ) );
		$this->assertFalse( $result['checks'][7]['passed'] );
	}

	public function test_coordinates_latitude_out_of_range(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'latitude'  => '91',
			'longitude' => '75.7',
		) ) );
		$this->assertFalse( $result['checks'][7]['passed'] );
	}

	public function test_coordinates_longitude_out_of_range(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'latitude'  => '26.9',
			'longitude' => '181',
		) ) );
		$this->assertFalse( $result['checks'][7]['passed'] );
	}

	public function test_coordinates_boundary_negative_90_positive_180(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'latitude'  => '-90',
			'longitude' => '180',
		) ) );
		$this->assertTrue( $result['checks'][7]['passed'] );
	}

	public function test_coordinates_boundary_positive_90_negative_180(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'latitude'  => '90',
			'longitude' => '-180',
		) ) );
		$this->assertTrue( $result['checks'][7]['passed'] );
	}

	public function test_coordinates_zero_zero(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'latitude'  => '0',
			'longitude' => '0',
		) ) );
		$this->assertTrue( $result['checks'][7]['passed'] );
	}

	// ─── Check 9: Social profiles ──────────────────────────────────────

	public function test_social_empty_array(): void {
		$result = HealthScore::calculate( $this->settings( array( 'social_profiles' => array() ) ) );
		$this->assertFalse( $result['checks'][8]['passed'] );
	}

	public function test_social_non_url(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'social_profiles' => array( '@handle' ),
		) ) );
		$this->assertFalse( $result['checks'][8]['passed'] );
	}

	public function test_social_protocol_relative(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'social_profiles' => array( '//facebook.com/biz' ),
		) ) );
		$this->assertFalse( $result['checks'][8]['passed'] );
	}

	public function test_social_valid_url(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'social_profiles' => array( 'https://facebook.com/biz' ),
		) ) );
		$this->assertTrue( $result['checks'][8]['passed'] );
	}

	public function test_social_malformed_string_not_array(): void {
		$result = HealthScore::calculate( $this->settings( array( 'social_profiles' => 'string' ) ) );
		$this->assertFalse( $result['checks'][8]['passed'] );
	}

	// ─── Check 10: Price range ─────────────────────────────────────────

	public function test_price_range_empty(): void {
		$result = HealthScore::calculate( $this->settings( array( 'price_range' => '' ) ) );
		$this->assertFalse( $result['checks'][9]['passed'] );
	}

	public function test_price_range_whitespace(): void {
		$result = HealthScore::calculate( $this->settings( array( 'price_range' => '  ' ) ) );
		$this->assertFalse( $result['checks'][9]['passed'] );
	}

	public function test_price_range_single_symbol(): void {
		$result = HealthScore::calculate( $this->settings( array( 'price_range' => '$' ) ) );
		$this->assertTrue( $result['checks'][9]['passed'] );
	}

	public function test_price_range_with_range(): void {
		$result = HealthScore::calculate( $this->settings( array( 'price_range' => '$10-$50' ) ) );
		$this->assertTrue( $result['checks'][9]['passed'] );
	}

	// ─── Status boundaries ─────────────────────────────────────────────

	public function test_status_boundary_0_needs_setup(): void {
		$result = HealthScore::calculate( $this->settings() );
		$this->assertSame( 'Needs setup', $result['status'] );
	}

	public function test_status_boundary_2_needs_setup(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'schema_enabled' => true,
			'business_name'  => 'Test',
		) ) );
		$this->assertSame( 2, $result['score'] );
		$this->assertSame( 'Needs setup', $result['status'] );
	}

	public function test_status_boundary_3_good_start(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'schema_enabled' => true,
			'business_name'  => 'Test',
			'region'         => 'CA',
		) ) );
		$this->assertSame( 3, $result['score'] );
		$this->assertSame( 'Good start', $result['status'] );
	}

	public function test_status_boundary_5_good_start(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'schema_enabled' => true,
			'business_name'  => 'Test',
			'region'         => 'CA',
			'phone'          => '+1 555 1234567',
			'price_range'    => '$$',
		) ) );
		$this->assertSame( 5, $result['score'] );
		$this->assertSame( 'Good start', $result['status'] );
	}

	public function test_status_boundary_6_almost_there(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'schema_enabled' => true,
			'business_name'  => 'Test',
			'region'         => 'CA',
			'phone'          => '+1 555 1234567',
			'price_range'    => '$$',
			'logo'           => 'https://example.com/logo.png',
		) ) );
		$this->assertSame( 6, $result['score'] );
		$this->assertSame( 'Almost there', $result['status'] );
	}

	public function test_status_boundary_8_almost_there(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'schema_enabled'  => true,
			'business_name'   => 'Test',
			'region'          => 'CA',
			'phone'           => '+1 555 1234567',
			'price_range'     => '$$',
			'logo'            => 'https://example.com/logo.png',
			'images'          => array( 'https://example.com/x.jpg' ),
			'social_profiles' => array( 'https://fb.com/x' ),
		) ) );
		$this->assertSame( 8, $result['score'] );
		$this->assertSame( 'Almost there', $result['status'] );
	}

	public function test_status_boundary_9_complete(): void {
		$s = $this->complete_settings();
		$s['schema_enabled'] = false;

		$result = HealthScore::calculate( $s );
		$this->assertSame( 9, $result['score'] );
		$this->assertSame( 'Complete', $result['status'] );
	}

	// ─── Excluded fields ───────────────────────────────────────────────

	public function test_woocommerce_fields_no_effect(): void {
		$a = HealthScore::calculate( $this->settings() );
		$b = HealthScore::calculate( $this->settings( array(
			'woocommerce_product_schema' => true,
			'woocommerce_currency'       => 'USD',
		) ) );
		$this->assertSame( $a['score'], $b['score'] );
	}

	public function test_legacy_fields_no_effect(): void {
		$a = HealthScore::calculate( $this->settings() );
		$b = HealthScore::calculate( $this->settings( array(
			'legacy_aggregate_rating' => '4.5',
			'legacy_review_count'     => '100',
		) ) );
		$this->assertSame( $a['score'], $b['score'] );
	}

	public function test_business_type_no_effect(): void {
		$a = HealthScore::calculate( $this->settings( array( 'business_type' => 'LocalBusiness' ) ) );
		$b = HealthScore::calculate( $this->settings( array( 'business_type' => 'Restaurant' ) ) );
		$this->assertSame( $a['score'], $b['score'] );
	}

	public function test_place_id_no_effect(): void {
		$a = HealthScore::calculate( $this->settings() );
		$b = HealthScore::calculate( $this->settings( array( 'place_id' => 'ChIJN1t_tDeuEmsRUsoyG83frY4' ) ) );
		$this->assertSame( $a['score'], $b['score'] );
	}

	// ─── Recommendation ordering ───────────────────────────────────────

	public function test_recommendations_ordered_by_check_number(): void {
		$result = HealthScore::calculate( $this->settings( array(
			'price_range'    => '$$',
			'business_name'  => 'Test Co',
		) ) );

		$recs = $result['recommendations'];
		$this->assertNotEmpty( $recs );

		$first_rec = $recs[0];
		$this->assertStringContainsString( 'Enable structured data', $first_rec );
	}

	// ─── Determinism ───────────────────────────────────────────────────

	public function test_deterministic_same_input_same_output(): void {
		$s = $this->complete_settings();
		$s['latitude'] = '';

		$a = HealthScore::calculate( $s );
		$b = HealthScore::calculate( $s );

		$this->assertSame( $a, $b );
	}
}
