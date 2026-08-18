<?php
/**
 * JSON-LD encoding tests.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

use AnkitRawat\LocalSEO\Schema\JsonLd;
use AnkitRawat\LocalSEO\Schema\LocalBusiness;
use AnkitRawat\LocalSEO\Schema\ProductBuilder;
use PHPUnit\Framework\TestCase;

final class JsonLdSecurityTest extends TestCase {

	protected function setUp(): void {
		lsar_test_reset_state();
	}

	public function test_script_breakout_is_hex_encoded() {
		$payload = '</script><script>alert(1)</script>';
		$json    = JsonLd::encode(
			array(
				'@type'       => 'Product',
				'name'        => 'Safe',
				'description' => $payload,
			)
		);

		$this->assertNotSame( '', $json );
		$this->assertFalse( JsonLd::contains_raw_script_close( $json ) );
		$this->assertStringContainsString( '\\u003C', $json );
		json_decode( $json );
		$this->assertSame( JSON_ERROR_NONE, json_last_error() );
	}

	public function test_product_builder_strips_html_and_encodes_safely() {
		$product = ProductBuilder::from_data(
			array(
				'name'        => 'Mug',
				'description' => '<p>Nice</p></script><script>alert(1)</script>',
				'url'         => 'https://example.test/mug/',
				'price'       => '12.5',
				'currency'    => 'USD',
				'availability'=> 'InStock',
				'sku'         => 'MUG-1',
				'image'       => 'https://example.test/mug.jpg',
			)
		);

		$this->assertSame( 'Mug', $product['name'] );
		$this->assertStringNotContainsString( '<p>', $product['description'] );
		$json = JsonLd::encode( $product );
		$this->assertFalse( JsonLd::contains_raw_script_close( $json ) );
	}

	public function test_product_builder_omits_currency_when_empty() {
		$product = ProductBuilder::from_data(
			array(
				'name'  => 'Mug',
				'price' => '10',
			)
		);
		$this->assertArrayHasKey( 'offers', $product );
		$this->assertArrayNotHasKey( 'priceCurrency', $product['offers'] );
	}

	public function test_local_business_omits_placeholders_and_empty_geo() {
		$builder = new LocalBusiness();
		$schema  = $builder->build(
			array(
				'schema_enabled' => true,
				'business_type'  => 'LocalBusiness',
				'business_name'  => '',
				'street_address' => '',
				'latitude'       => '',
				'longitude'      => '10',
			),
			'https://example.test/'
		);

		$this->assertArrayNotHasKey( 'name', $schema );
		$this->assertArrayNotHasKey( 'address', $schema );
		$this->assertArrayNotHasKey( 'geo', $schema );
		$this->assertSame( 'LocalBusiness', $schema['@type'] );
		$this->assertSame( 'https://example.test/', $schema['url'] );
	}

	public function test_local_business_emits_nap_and_geo_when_valid() {
		$builder = new LocalBusiness();
		$schema  = $builder->build(
			array(
				'business_type'  => 'Hotel',
				'business_name'  => 'Harbor Inn',
				'street_address' => '1 Dock St',
				'locality'       => 'Portland',
				'region'         => 'ME',
				'postal_code'    => '04101',
				'country'        => 'US',
				'phone'          => '+1 207-555-0100',
				'price_range'    => '$$',
				'logo'           => 'https://example.test/logo.png',
				'social_profiles'=> array( 'https://instagram.com/harbor' ),
				'latitude'       => '43.6591',
				'longitude'      => '-70.2568',
			),
			'https://example.test/'
		);

		$this->assertSame( 'Hotel', $schema['@type'] );
		$this->assertSame( 'Harbor Inn', $schema['name'] );
		$this->assertSame( 'PostalAddress', $schema['address']['@type'] );
		$this->assertSame( '1 Dock St', $schema['address']['streetAddress'] );
		$this->assertSame( 'GeoCoordinates', $schema['geo']['@type'] );
		$this->assertSame( '$$', $schema['priceRange'] );
		$this->assertSame( array( 'https://instagram.com/harbor' ), $schema['sameAs'] );
		$this->assertArrayNotHasKey( 'hasOfferCatalog', $schema );
	}

	public function test_invalid_type_filter_cannot_inject_arbitrary_type() {
		$GLOBALS['lsar_test_filters']['local_seo_by_ankit_rawat_business_type'] = static function () {
			return 'EvilType';
		};

		$builder = new LocalBusiness();
		$schema  = $builder->build( array( 'business_type' => 'Restaurant' ), 'https://example.test/' );
		$this->assertSame( 'LocalBusiness', $schema['@type'] );
	}

	public function test_each_allowlisted_type() {
		$builder = new LocalBusiness();
		foreach ( array( 'LocalBusiness', 'Restaurant', 'Hotel', 'ProfessionalService', 'Store' ) as $type ) {
			$schema = $builder->build( array( 'business_type' => $type, 'business_name' => 'Acme' ), 'https://example.test/' );
			$this->assertSame( $type, $schema['@type'] );
		}
	}
}
