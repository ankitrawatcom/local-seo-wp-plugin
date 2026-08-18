<?php
/**
 * Store catalog placement and ListItem wrapping.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

use AnkitRawat\LocalSEO\Schema\WooCommerce;
use PHPUnit\Framework\TestCase;

final class StoreCatalogTest extends TestCase {

	protected function setUp(): void {
		lsar_test_reset_state();
	}

	public function test_offer_catalog_wraps_list_items() {
		$catalog = WooCommerce::offer_catalog(
			array(
				array(
					'@type' => 'Product',
					'name'  => 'A',
				),
				array(
					'@type' => 'Product',
					'name'  => 'B',
				),
			)
		);

		$this->assertSame( 'OfferCatalog', $catalog['@type'] );
		$this->assertCount( 2, $catalog['itemListElement'] );
		$this->assertSame( 'ListItem', $catalog['itemListElement'][0]['@type'] );
		$this->assertSame( 1, $catalog['itemListElement'][0]['position'] );
		$this->assertSame( 'A', $catalog['itemListElement'][0]['item']['name'] );
		$this->assertSame( 2, $catalog['itemListElement'][1]['position'] );
	}

	public function test_offer_catalog_empty_products() {
		$this->assertSame( array(), WooCommerce::offer_catalog( array() ) );
	}

	public function test_catalog_embeds_on_front_not_product() {
		$GLOBALS['lsar_test_is_front_page'] = true;
		$this->assertTrue( WooCommerce::should_embed_store_catalog() );

		$GLOBALS['lsar_test_is_product'] = true;
		$this->assertFalse( WooCommerce::should_embed_store_catalog() );
	}

	public function test_catalog_embeds_on_shop() {
		$GLOBALS['lsar_test_is_shop'] = true;
		$this->assertTrue( WooCommerce::should_embed_store_catalog() );
	}

	public function test_catalog_skipped_on_ordinary_pages() {
		$this->assertFalse( WooCommerce::should_embed_store_catalog() );
	}
}
