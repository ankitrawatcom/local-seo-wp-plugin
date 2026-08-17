<?php
/**
 * 3.3 → 4.0.0 migration tests.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

use AnkitRawat\LocalSEO\Migration\Migrator;
use AnkitRawat\LocalSEO\Plugin;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase {

	protected function setUp(): void {
		lsar_test_reset_state();
	}

	public function test_migrates_legacy_options_without_deleting_them() {
		update_option( 'local_seo_enable_schema', '1' );
		update_option( 'business_type', 'Store' );
		update_option( 'business_name', 'Legacy Shop' );
		update_option( 'street_address', '9 Market' );
		update_option( 'locality', 'Boston' );
		update_option( 'region', 'MA' );
		update_option( 'postal_code', '02108' );
		update_option( 'country', 'US' );
		update_option( 'phone', '617-555-0199' );
		update_option( 'price_range', '$' );
		update_option( 'business_logo', 'https://example.test/old-logo.png' );
		update_option( 'business_images', 'https://example.test/one.jpg, https://example.test/two.jpg' );
		update_option( 'social_profiles', 'https://facebook.com/legacy' );
		update_option( 'latitude', '42.3601' );
		update_option( 'longitude', '-71.0589' );
		update_option( 'google_my_business_place_id', 'ChIJlegacy' );
		update_option( 'google_my_business_api_key', 'secret-key-do-not-copy' );
		update_option( 'aggregate_rating', '4.5' );
		update_option( 'review_count', '12' );
		update_option( 'woocommerce_product_schema', '1' );
		update_option( 'woocommerce_price_currency', 'usd' );

		Migrator::maybe_run();

		$new = get_option( Plugin::OPTION );
		$this->assertIsArray( $new );
		$this->assertTrue( $new['schema_enabled'] );
		$this->assertSame( 'Store', $new['business_type'] );
		$this->assertSame( 'Legacy Shop', $new['business_name'] );
		$this->assertSame( '9 Market', $new['street_address'] );
		$this->assertSame( 'https://example.test/old-logo.png', $new['logo'] );
		$this->assertSame( array( 'https://facebook.com/legacy' ), $new['social_profiles'] );
		$this->assertSame( 'ChIJlegacy', $new['place_id'] );
		$this->assertSame( 'USD', $new['woocommerce_currency'] );
		$this->assertSame( '4.5', $new['legacy_aggregate_rating'] );
		$this->assertTrue( $new['woocommerce_product_schema'] );

		$serialized = wp_json_encode( $new );
		$this->assertStringNotContainsString( 'secret-key-do-not-copy', $serialized );
		$this->assertArrayNotHasKey( 'google_my_business_api_key', $new );

		$this->assertSame( 'secret-key-do-not-copy', get_option( 'google_my_business_api_key' ) );
		$this->assertSame( 'Legacy Shop', get_option( 'business_name' ) );
		$this->assertSame( '4.0.0', get_option( Plugin::VERSION_KEY ) );
	}

	public function test_migration_is_idempotent_and_does_not_overwrite() {
		update_option( 'business_name', 'Old Name' );
		Migrator::maybe_run();

		$first = get_option( Plugin::OPTION );
		update_option( 'business_name', 'Changed After Migration' );
		$first['business_name'] = 'Kept New Name';
		update_option( Plugin::OPTION, $first );

		Migrator::maybe_run();

		$second = get_option( Plugin::OPTION );
		$this->assertSame( 'Kept New Name', $second['business_name'] );
		$this->assertSame( 'Changed After Migration', get_option( 'business_name' ) );
	}

	public function test_invalid_legacy_type_is_normalized() {
		update_option( 'business_type', 'NotAType' );
		Migrator::maybe_run();
		$new = get_option( Plugin::OPTION );
		$this->assertSame( 'LocalBusiness', $new['business_type'] );
	}

	public function test_api_key_is_in_exclusion_list() {
		$this->assertContains( 'google_my_business_api_key', Migrator::excluded_legacy_keys() );
		$this->assertArrayNotHasKey( 'google_my_business_api_key', Migrator::legacy_key_map() );
	}
}
