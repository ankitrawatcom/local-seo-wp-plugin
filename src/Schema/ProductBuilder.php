<?php
/**
 * Product JSON-LD fragment builder.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

namespace AnkitRawat\LocalSEO\Schema;

use AnkitRawat\LocalSEO\Support\Sanitize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds Schema.org Product objects from a normalized data array.
 */
final class ProductBuilder {

	/**
	 * Build a Product node. Omits empty properties.
	 *
	 * @param array<string, mixed> $data Normalized product data.
	 * @return array<string, mixed>
	 */
	public static function from_data( array $data ) {
		$product = array(
			'@type' => 'Product',
		);

		$name = Sanitize::plain_text( isset( $data['name'] ) ? $data['name'] : '' );
		if ( '' === $name ) {
			return array();
		}
		$product['name'] = $name;

		$description = Sanitize::plain_text( isset( $data['description'] ) ? $data['description'] : '' );
		if ( '' !== $description ) {
			$product['description'] = $description;
		}

		$image = Sanitize::http_url( isset( $data['image'] ) ? $data['image'] : '' );
		if ( '' !== $image ) {
			$product['image'] = $image;
		}

		$sku = Sanitize::text( isset( $data['sku'] ) ? $data['sku'] : '' );
		if ( '' !== $sku ) {
			$product['sku'] = $sku;
		}

		$url          = Sanitize::http_url( isset( $data['url'] ) ? $data['url'] : '' );
		$price        = Sanitize::price( isset( $data['price'] ) ? $data['price'] : '' );
		$currency     = Sanitize::currency( isset( $data['currency'] ) ? $data['currency'] : '' );
		$availability = self::availability( isset( $data['availability'] ) ? $data['availability'] : '' );

		$offer = array(
			'@type' => 'Offer',
		);
		if ( '' !== $price ) {
			$offer['price'] = $price;
		}
		if ( '' !== $currency ) {
			$offer['priceCurrency'] = $currency;
		}
		if ( '' !== $availability ) {
			$offer['availability'] = $availability;
		}
		if ( '' !== $url ) {
			$offer['url']   = $url;
			$product['url'] = $url;
		}

		if ( count( $offer ) > 1 ) {
			$product['offers'] = $offer;
		}

		/**
		 * Filter a single Product JSON-LD object.
		 *
		 * @param array<string, mixed> $product Product graph.
		 * @param array<string, mixed> $data    Normalized input.
		 */
		$product = apply_filters( 'local_seo_by_ankit_rawat_product_schema', $product, $data );

		return is_array( $product ) ? $product : array();
	}

	/**
	 * Map availability to a Schema.org URL.
	 *
	 * @param mixed $value Raw availability.
	 * @return string
	 */
	private static function availability( $value ) {
		$value = is_scalar( $value ) ? (string) $value : '';
		$map   = array(
			'InStock'    => 'https://schema.org/InStock',
			'OutOfStock' => 'https://schema.org/OutOfStock',
			'PreOrder'   => 'https://schema.org/PreOrder',
		);

		if ( isset( $map[ $value ] ) ) {
			return $map[ $value ];
		}

		if ( in_array( $value, $map, true ) ) {
			return $value;
		}

		return '';
	}
}
