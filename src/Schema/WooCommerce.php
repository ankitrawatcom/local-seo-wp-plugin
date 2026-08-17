<?php
/**
 * WooCommerce product JSON-LD.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

namespace AnkitRawat\LocalSEO\Schema;

use AnkitRawat\LocalSEO\Plugin;
use AnkitRawat\LocalSEO\Support\Sanitize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Optional product structured data for WooCommerce.
 */
final class WooCommerce {

	/**
	 * Default number of products nested under Store schema.
	 *
	 * Matches version 3.3. Kept small to limit queries on the homepage.
	 */
	const STORE_PRODUCT_LIMIT = 5;

	/**
	 * Register product-page output.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_head', array( $this, 'print_product_schema' ), 20 );
	}

	/**
	 * Whether WooCommerce is available.
	 *
	 * @return bool
	 */
	public static function is_active() {
		return class_exists( '\WooCommerce' ) && function_exists( 'wc_get_products' );
	}

	/**
	 * Product-page JSON-LD.
	 *
	 * @return void
	 */
	public function print_product_schema() {
		if ( is_admin() || ! self::is_active() ) {
			return;
		}

		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$settings = Plugin::settings();
		if ( empty( $settings['woocommerce_product_schema'] ) ) {
			return;
		}

		/**
		 * Whether to print product JSON-LD on WooCommerce product pages.
		 *
		 * @param bool                 $output   Whether to output.
		 * @param array<string, mixed> $settings Settings.
		 */
		$should_output = apply_filters( 'local_seo_by_ankit_rawat_output_product_schema', true, $settings );
		if ( ! $should_output ) {
			return;
		}

		$product = $this->current_product();
		if ( ! $product ) {
			return;
		}

		$schema = ProductBuilder::from_data( $this->normalize_product( $product, $settings ) );
		if ( array() === $schema ) {
			return;
		}

		$schema = array_merge( array( '@context' => 'https://schema.org' ), $schema );
		JsonLd::print_script( $schema );
	}

	/**
	 * Up to five published products for Store schema.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array<int, array<string, mixed>>
	 */
	public function store_products( array $settings ) {
		if ( ! self::is_active() ) {
			return array();
		}

		/**
		 * Number of products included under Store hasOfferCatalog.
		 *
		 * @param int $limit Maximum products.
		 */
		$limit = (int) apply_filters( 'local_seo_by_ankit_rawat_store_product_limit', self::STORE_PRODUCT_LIMIT );
		if ( $limit < 1 ) {
			return array();
		}
		if ( $limit > 20 ) {
			$limit = 20;
		}

		$products = wc_get_products(
			array(
				'status'  => 'publish',
				'limit'   => $limit,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		if ( ! is_array( $products ) ) {
			return array();
		}

		$schema = array();
		foreach ( $products as $product ) {
			$node = ProductBuilder::from_data( $this->normalize_product( $product, $settings ) );
			if ( array() !== $node ) {
				$schema[] = $node;
			}
		}

		return $schema;
	}

	/**
	 * Resolve the current product object.
	 *
	 * @return object|null
	 */
	private function current_product() {
		global $product;

		if ( is_object( $product ) && is_a( $product, 'WC_Product' ) ) {
			return $product;
		}

		if ( function_exists( 'wc_get_product' ) ) {
			$resolved = wc_get_product( get_the_ID() );
			if ( is_object( $resolved ) ) {
				return $resolved;
			}
		}

		return null;
	}

	/**
	 * Map a WC_Product to sanitized builder input.
	 *
	 * @param object               $product  WC product.
	 * @param array<string, mixed> $settings Settings.
	 * @return array<string, mixed>
	 */
	private function normalize_product( $product, array $settings ) {
		$name        = method_exists( $product, 'get_name' ) ? $product->get_name() : '';
		$short       = method_exists( $product, 'get_short_description' ) ? $product->get_short_description() : '';
		$long        = method_exists( $product, 'get_description' ) ? $product->get_description() : '';
		$description = '' !== $short ? $short : $long;
		$image       = '';
		if ( method_exists( $product, 'get_image_id' ) && function_exists( 'wp_get_attachment_url' ) ) {
			$image_id = (int) $product->get_image_id();
			if ( $image_id > 0 ) {
				$image = (string) wp_get_attachment_url( $image_id );
			}
		}

		$sku   = method_exists( $product, 'get_sku' ) ? $product->get_sku() : '';
		$price = method_exists( $product, 'get_price' ) ? $product->get_price() : '';
		$id    = method_exists( $product, 'get_id' ) ? (int) $product->get_id() : 0;
		$url   = $id > 0 ? get_permalink( $id ) : '';

		$in_stock     = method_exists( $product, 'is_in_stock' ) ? (bool) $product->is_in_stock() : false;
		$availability = $in_stock ? 'InStock' : 'OutOfStock';

		$currency = Sanitize::currency( isset( $settings['woocommerce_currency'] ) ? $settings['woocommerce_currency'] : '' );
		if ( '' === $currency && function_exists( 'get_woocommerce_currency' ) ) {
			$currency = Sanitize::currency( get_woocommerce_currency() );
		}
		if ( '' === $currency ) {
			$currency = 'USD';
		}

		return array(
			'name'         => $name,
			'description'  => $description,
			'image'        => $image,
			'sku'          => $sku,
			'price'        => $price,
			'currency'     => $currency,
			'availability' => $availability,
			'url'          => is_string( $url ) ? $url : '',
		);
	}
}
