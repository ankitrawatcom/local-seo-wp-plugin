<?php
/**
 * Version-aware upgrade from 3.3 generic options.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

namespace AnkitRawat\LocalSEO\Migration;

use AnkitRawat\LocalSEO\Admin\Settings;
use AnkitRawat\LocalSEO\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Copies 3.3 options into the namespaced array without deleting the originals.
 */
final class Migrator {

	/**
	 * Run once until version is at least 4.0.0.
	 *
	 * Invoked from activation and admin_init only, not on the frontend.
	 *
	 * @return void
	 */
	public static function maybe_run() {
		$stored_version = get_option( Plugin::VERSION_KEY, '' );
		if ( is_string( $stored_version ) && '' !== $stored_version && version_compare( $stored_version, '4.0.0', '>=' ) ) {
			return;
		}

		self::migrate_from_3_3();
		update_option( Plugin::VERSION_KEY, Plugin::VERSION, false );
		delete_transient( Plugin::LEGACY_CACHE );
	}

	/**
	 * Map legacy options into the new array.
	 *
	 * Existing new values win. Empty new values are filled from 3.3 when present.
	 * google_my_business_api_key is intentionally not copied (unused secret).
	 *
	 * @return void
	 */
	public static function migrate_from_3_3() {
		$existing = get_option( Plugin::OPTION, false );
		$current  = is_array( $existing ) ? $existing : array();
		$merged   = array_merge( Settings::defaults(), $current );

		// When the 4.0 option does not exist yet, copy legacy values over defaults.
		$replace_defaults = ( false === $existing );

		$map = array(
			'local_seo_enable_schema'     => 'schema_enabled',
			'business_type'               => 'business_type',
			'business_name'               => 'business_name',
			'street_address'              => 'street_address',
			'locality'                    => 'locality',
			'region'                      => 'region',
			'postal_code'                 => 'postal_code',
			'country'                     => 'country',
			'phone'                       => 'phone',
			'price_range'                 => 'price_range',
			'business_logo'               => 'logo',
			'latitude'                    => 'latitude',
			'longitude'                   => 'longitude',
			'google_my_business_place_id' => 'place_id',
			'woocommerce_product_schema'  => 'woocommerce_product_schema',
			'woocommerce_price_currency'  => 'woocommerce_currency',
			'aggregate_rating'            => 'legacy_aggregate_rating',
			'review_count'                => 'legacy_review_count',
		);

		foreach ( $map as $old_key => $new_key ) {
			if ( ! $replace_defaults && ! self::is_empty_value( $merged[ $new_key ] ) ) {
				continue;
			}

			$old = get_option( $old_key, null );
			if ( null === $old || false === $old ) {
				continue;
			}

			$merged[ $new_key ] = $old;
		}

		if ( $replace_defaults || self::is_empty_value( $merged['images'] ) ) {
			$legacy_images = get_option( 'business_images', null );
			if ( null !== $legacy_images && false !== $legacy_images ) {
				$merged['images'] = $legacy_images;
			}
		}

		if ( $replace_defaults || self::is_empty_value( $merged['social_profiles'] ) ) {
			$legacy_social = get_option( 'social_profiles', null );
			if ( null !== $legacy_social && false !== $legacy_social ) {
				$merged['social_profiles'] = $legacy_social;
			}
		}

		$sanitized = Settings::sanitize( $merged );

		/*
		 * Do not delete 3.3 options, including google_my_business_api_key.
		 * They remain for rollback. Uninstall does not remove generic keys.
		 */
		update_option( Plugin::OPTION, $sanitized, true );
	}

	/**
	 * Whether a migrated field should be treated as unset.
	 *
	 * @param mixed $value Stored value.
	 * @return bool
	 */
	private static function is_empty_value( $value ) {
		if ( false === $value || null === $value || '' === $value ) {
			return true;
		}

		if ( is_array( $value ) && array() === $value ) {
			return true;
		}

		return false;
	}

	/**
	 * Map used by tests to assert coverage of 3.3 keys.
	 *
	 * @return array<string, string>
	 */
	public static function legacy_key_map() {
		return array(
			'local_seo_enable_schema'     => 'schema_enabled',
			'business_type'               => 'business_type',
			'business_name'               => 'business_name',
			'street_address'              => 'street_address',
			'locality'                    => 'locality',
			'region'                      => 'region',
			'postal_code'                 => 'postal_code',
			'country'                     => 'country',
			'phone'                       => 'phone',
			'price_range'                 => 'price_range',
			'business_logo'               => 'logo',
			'business_images'             => 'images',
			'social_profiles'             => 'social_profiles',
			'latitude'                    => 'latitude',
			'longitude'                   => 'longitude',
			'google_my_business_place_id' => 'place_id',
			'woocommerce_product_schema'  => 'woocommerce_product_schema',
			'woocommerce_price_currency'  => 'woocommerce_currency',
			'aggregate_rating'            => 'legacy_aggregate_rating',
			'review_count'                => 'legacy_review_count',
		);
	}

	/**
	 * Keys that must never be copied into the active 4.0 option.
	 *
	 * @return array<int, string>
	 */
	public static function excluded_legacy_keys() {
		return array(
			'google_my_business_api_key',
		);
	}
}
