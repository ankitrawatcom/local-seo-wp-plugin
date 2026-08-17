<?php
/**
 * Sitewide LocalBusiness JSON-LD.
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
 * Builds and prints LocalBusiness-family schema.
 */
final class LocalBusiness {

	/**
	 * Register frontend output.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_head', array( $this, 'print_schema' ), 10 );
	}

	/**
	 * Print JSON-LD when enabled.
	 *
	 * @return void
	 */
	public function print_schema() {
		if ( is_admin() ) {
			return;
		}

		$settings = Plugin::settings();
		if ( empty( $settings['schema_enabled'] ) ) {
			return;
		}

		/**
		 * Whether to print sitewide LocalBusiness JSON-LD.
		 *
		 * Return false to skip (for example if another SEO plugin already emits it).
		 *
		 * @param bool                 $output   Whether to output.
		 * @param array<string, mixed> $settings Plugin settings.
		 */
		$should_output = apply_filters( 'local_seo_by_ankit_rawat_output_local_schema', true, $settings );
		if ( ! $should_output ) {
			return;
		}

		$schema = $this->build( $settings );
		if ( array() === $schema ) {
			return;
		}

		JsonLd::print_script( $schema );
	}

	/**
	 * Build a schema object from settings. Public for tests.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $site_url Public site URL.
	 * @return array<string, mixed>
	 */
	public function build( array $settings, $site_url = '' ) {
		$type = Sanitize::business_type( isset( $settings['business_type'] ) ? $settings['business_type'] : 'LocalBusiness' );

		/**
		 * Filter the Schema.org @type.
		 *
		 * Invalid types are reset to LocalBusiness.
		 *
		 * @param string               $type     Business type.
		 * @param array<string, mixed> $settings Settings.
		 */
		$type = Sanitize::business_type( apply_filters( 'local_seo_by_ankit_rawat_business_type', $type, $settings ) );

		if ( '' === $site_url ) {
			$site_url = home_url( '/' );
		}

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => $type,
		);

		$name = Sanitize::text( isset( $settings['business_name'] ) ? $settings['business_name'] : '' );
		if ( '' !== $name ) {
			$schema['name'] = $name;
		}

		$site_url = Sanitize::http_url( $site_url );
		if ( '' !== $site_url ) {
			$schema['url'] = $site_url;
		}

		$phone = Sanitize::phone( isset( $settings['phone'] ) ? $settings['phone'] : '' );
		if ( '' !== $phone ) {
			$schema['telephone'] = $phone;
		}

		$images = $this->images( $settings );
		if ( array() !== $images ) {
			$schema['image'] = 1 === count( $images ) ? $images[0] : $images;
		}

		$address = $this->address( $settings );
		if ( array() !== $address ) {
			$schema['address'] = $address;
		}

		$geo = $this->geo( $settings );
		if ( array() !== $geo ) {
			$schema['geo'] = $geo;
		}

		$price_range = Sanitize::price_range( isset( $settings['price_range'] ) ? $settings['price_range'] : '' );
		if ( '' !== $price_range ) {
			$schema['priceRange'] = $price_range;
		}

		$same_as = Sanitize::url_list( isset( $settings['social_profiles'] ) ? $settings['social_profiles'] : array() );
		if ( array() !== $same_as ) {
			$schema['sameAs'] = $same_as;
		}

		if ( 'Store' === $type && ! empty( $settings['woocommerce_product_schema'] ) && WooCommerce::is_active() ) {
			$products = ( new WooCommerce() )->store_products( $settings );
			if ( array() !== $products ) {
				$schema['hasOfferCatalog'] = array(
					'@type'           => 'OfferCatalog',
					'name'            => __( 'Products', 'local-seo-by-ankit-rawat' ),
					'itemListElement' => $products,
				);
			}
		}

		/**
		 * Filter the LocalBusiness JSON-LD object before encoding.
		 *
		 * @param array<string, mixed> $schema   Schema.org graph.
		 * @param array<string, mixed> $settings Settings.
		 */
		$schema = apply_filters( 'local_seo_by_ankit_rawat_schema', $schema, $settings );

		return is_array( $schema ) ? $schema : array();
	}

	/**
	 * PostalAddress node or empty.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array<string, string>
	 */
	private function address( array $settings ) {
		$map = array(
			'streetAddress'   => Sanitize::text( isset( $settings['street_address'] ) ? $settings['street_address'] : '' ),
			'addressLocality' => Sanitize::text( isset( $settings['locality'] ) ? $settings['locality'] : '' ),
			'addressRegion'   => Sanitize::text( isset( $settings['region'] ) ? $settings['region'] : '' ),
			'postalCode'      => Sanitize::text( isset( $settings['postal_code'] ) ? $settings['postal_code'] : '' ),
			'addressCountry'  => Sanitize::text( isset( $settings['country'] ) ? $settings['country'] : '' ),
		);

		$address = array_filter(
			$map,
			static function ( $value ) {
				return '' !== $value;
			}
		);

		if ( array() === $address ) {
			return array();
		}

		$address = array_merge( array( '@type' => 'PostalAddress' ), $address );
		return $address;
	}

	/**
	 * GeoCoordinates when both values are valid.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array<string, string>
	 */
	private function geo( array $settings ) {
		$lat = Sanitize::latitude( isset( $settings['latitude'] ) ? $settings['latitude'] : '' );
		$lng = Sanitize::longitude( isset( $settings['longitude'] ) ? $settings['longitude'] : '' );

		if ( '' === $lat || '' === $lng ) {
			return array();
		}

		return array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => $lat,
			'longitude' => $lng,
		);
	}

	/**
	 * Logo plus extra images, unique HTTP URLs.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array<int, string>
	 */
	private function images( array $settings ) {
		$urls = array();

		$logo = Sanitize::http_url( isset( $settings['logo'] ) ? $settings['logo'] : '' );
		if ( '' !== $logo ) {
			$urls[] = $logo;
		}

		$extra = Sanitize::url_list( isset( $settings['images'] ) ? $settings['images'] : array() );
		foreach ( $extra as $url ) {
			if ( ! in_array( $url, $urls, true ) ) {
				$urls[] = $url;
			}
		}

		return $urls;
	}
}
