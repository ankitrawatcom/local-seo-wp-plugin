<?php
/**
 * Schema Health Score — deterministic configuration completeness indicator.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

namespace AnkitRawat\LocalSEO\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calculates a 0–10 schema health score from the plugin settings.
 */
final class HealthScore {

	/**
	 * Calculate the schema health score.
	 *
	 * @param array<string, mixed> $settings The plugin settings array (merged with defaults).
	 * @return array{score: int, total: int, status: string, checks: list<array{id: string, label: string, passed: bool}>, recommendations: list<string>}
	 */
	public static function calculate( array $settings ): array {
		$checks          = self::run_checks( $settings );
		$score           = 0;
		$recommendations = array();

		foreach ( $checks as $check ) {
			if ( $check['passed'] ) {
				++$score;
			} else {
				$recommendations[] = $check['recommendation'];
			}
		}

		$result_checks = array();
		foreach ( $checks as $check ) {
			$result_checks[] = array(
				'id'     => $check['id'],
				'label'  => $check['label'],
				'passed' => $check['passed'],
			);
		}

		return array(
			'score'           => $score,
			'total'           => 10,
			'status'          => self::status_label( $score ),
			'checks'          => $result_checks,
			'recommendations' => $recommendations,
		);
	}

	/**
	 * Run all 10 checks.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return list<array{id: string, label: string, passed: bool, recommendation: string}>
	 */
	private static function run_checks( array $settings ): array {
		return array(
			self::check_schema_enabled( $settings ),
			self::check_business_name( $settings ),
			self::check_core_address( $settings ),
			self::check_address_detail( $settings ),
			self::check_phone( $settings ),
			self::check_logo( $settings ),
			self::check_photos( $settings ),
			self::check_coordinates( $settings ),
			self::check_social( $settings ),
			self::check_price_range( $settings ),
		);
	}

	/**
	 * Check 1 — Schema output enabled.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array{id: string, label: string, passed: bool, recommendation: string}
	 */
	private static function check_schema_enabled( array $settings ): array {
		return array(
			'id'             => 'schema_enabled',
			'label'          => __( 'Structured data enabled', 'local-seo-by-ankit-rawat' ),
			'passed'         => ! empty( $settings['schema_enabled'] ),
			'recommendation' => __( 'Enable structured data so search engines can find your business.', 'local-seo-by-ankit-rawat' ),
		);
	}

	/**
	 * Check 2 — Business name.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array{id: string, label: string, passed: bool, recommendation: string}
	 */
	private static function check_business_name( array $settings ): array {
		$value = isset( $settings['business_name'] ) && is_string( $settings['business_name'] )
			? trim( $settings['business_name'] )
			: '';

		return array(
			'id'             => 'business_name',
			'label'          => __( 'Business name added', 'local-seo-by-ankit-rawat' ),
			'passed'         => mb_strlen( $value ) >= 2,
			'recommendation' => __( 'Add your business name.', 'local-seo-by-ankit-rawat' ),
		);
	}

	/**
	 * Check 3 — Core address (street + city + country).
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array{id: string, label: string, passed: bool, recommendation: string}
	 */
	private static function check_core_address( array $settings ): array {
		$street  = self::trimmed_string( $settings, 'street_address' );
		$city    = self::trimmed_string( $settings, 'locality' );
		$country = self::trimmed_string( $settings, 'country' );

		$passed = '' !== $street && '' !== $city && '' !== $country;

		$recommendation = self::core_address_recommendation( $street, $city, $country );

		return array(
			'id'             => 'core_address',
			'label'          => __( 'Street address, city, and country added', 'local-seo-by-ankit-rawat' ),
			'passed'         => $passed,
			'recommendation' => $recommendation,
		);
	}

	/**
	 * Adaptive recommendation for core address check.
	 *
	 * @param string $street Trimmed street.
	 * @param string $city   Trimmed city.
	 * @param string $country Trimmed country.
	 * @return string
	 */
	private static function core_address_recommendation( string $street, string $city, string $country ): string {
		$missing = array();
		if ( '' === $street ) {
			$missing[] = 'street';
		}
		if ( '' === $city ) {
			$missing[] = 'city';
		}
		if ( '' === $country ) {
			$missing[] = 'country';
		}

		if ( 1 === count( $missing ) ) {
			if ( 'street' === $missing[0] ) {
				return __( 'Add your street address.', 'local-seo-by-ankit-rawat' );
			}
			if ( 'city' === $missing[0] ) {
				return __( 'Add your city or town.', 'local-seo-by-ankit-rawat' );
			}
			return __( 'Add your country.', 'local-seo-by-ankit-rawat' );
		}

		return __( 'Complete your address — add your street, city, and country.', 'local-seo-by-ankit-rawat' );
	}

	/**
	 * Check 4 — Address detail (region OR postal code).
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array{id: string, label: string, passed: bool, recommendation: string}
	 */
	private static function check_address_detail( array $settings ): array {
		$region = self::trimmed_string( $settings, 'region' );
		$postal = self::trimmed_string( $settings, 'postal_code' );

		return array(
			'id'             => 'address_detail',
			'label'          => __( 'State/province or postal code added', 'local-seo-by-ankit-rawat' ),
			'passed'         => '' !== $region || '' !== $postal,
			'recommendation' => __( 'Add your state/province or postal code for a more precise address.', 'local-seo-by-ankit-rawat' ),
		);
	}

	/**
	 * Check 5 — Phone number (>= 7 digits).
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array{id: string, label: string, passed: bool, recommendation: string}
	 */
	private static function check_phone( array $settings ): array {
		$value  = self::trimmed_string( $settings, 'phone' );
		$digits = preg_replace( '/[^0-9]/', '', $value );

		return array(
			'id'             => 'phone',
			'label'          => __( 'Phone number added', 'local-seo-by-ankit-rawat' ),
			'passed'         => is_string( $digits ) && strlen( $digits ) >= 7,
			'recommendation' => __( 'Add your phone number.', 'local-seo-by-ankit-rawat' ),
		);
	}

	/**
	 * Check 6 — Business logo (valid http/https URL).
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array{id: string, label: string, passed: bool, recommendation: string}
	 */
	private static function check_logo( array $settings ): array {
		$value = self::trimmed_string( $settings, 'logo' );

		return array(
			'id'             => 'logo',
			'label'          => __( 'Business logo added', 'local-seo-by-ankit-rawat' ),
			'passed'         => self::is_http_url( $value ),
			'recommendation' => __( 'Add a business logo.', 'local-seo-by-ankit-rawat' ),
		);
	}

	/**
	 * Check 7 — Business photos (>= 1 valid URL in images array).
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array{id: string, label: string, passed: bool, recommendation: string}
	 */
	private static function check_photos( array $settings ): array {
		$images = isset( $settings['images'] ) && is_array( $settings['images'] )
			? $settings['images']
			: array();

		$passed = false;
		foreach ( $images as $url ) {
			if ( is_string( $url ) && self::is_http_url( trim( $url ) ) ) {
				$passed = true;
				break;
			}
		}

		return array(
			'id'             => 'photos',
			'label'          => __( 'Business photos added', 'local-seo-by-ankit-rawat' ),
			'passed'         => $passed,
			'recommendation' => __( 'Add at least one business photo.', 'local-seo-by-ankit-rawat' ),
		);
	}

	/**
	 * Check 8 — Map coordinates (both numeric, in range).
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array{id: string, label: string, passed: bool, recommendation: string}
	 */
	private static function check_coordinates( array $settings ): array {
		$lat_raw = self::trimmed_string( $settings, 'latitude' );
		$lng_raw = self::trimmed_string( $settings, 'longitude' );

		$passed = false;

		if ( '' !== $lat_raw && '' !== $lng_raw && is_numeric( $lat_raw ) && is_numeric( $lng_raw ) ) {
			$lat = (float) $lat_raw;
			$lng = (float) $lng_raw;

			if ( $lat >= -90.0 && $lat <= 90.0 && $lng >= -180.0 && $lng <= 180.0 ) {
				$passed = true;
			}
		}

		return array(
			'id'             => 'coordinates',
			'label'          => __( 'Map coordinates added', 'local-seo-by-ankit-rawat' ),
			'passed'         => $passed,
			'recommendation' => __( 'Add both latitude and longitude for your map location.', 'local-seo-by-ankit-rawat' ),
		);
	}

	/**
	 * Check 9 — Social profiles (>= 1 valid URL in array).
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array{id: string, label: string, passed: bool, recommendation: string}
	 */
	private static function check_social( array $settings ): array {
		$profiles = isset( $settings['social_profiles'] ) && is_array( $settings['social_profiles'] )
			? $settings['social_profiles']
			: array();

		$passed = false;
		foreach ( $profiles as $url ) {
			if ( is_string( $url ) && self::is_http_url( trim( $url ) ) ) {
				$passed = true;
				break;
			}
		}

		return array(
			'id'             => 'social',
			'label'          => __( 'Social media links added', 'local-seo-by-ankit-rawat' ),
			'passed'         => $passed,
			'recommendation' => __( 'Add at least one social media link.', 'local-seo-by-ankit-rawat' ),
		);
	}

	/**
	 * Check 10 — Price range.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @return array{id: string, label: string, passed: bool, recommendation: string}
	 */
	private static function check_price_range( array $settings ): array {
		$value = self::trimmed_string( $settings, 'price_range' );

		return array(
			'id'             => 'price_range',
			'label'          => __( 'Price range added', 'local-seo-by-ankit-rawat' ),
			'passed'         => '' !== $value,
			'recommendation' => __( 'Add your typical price range.', 'local-seo-by-ankit-rawat' ),
		);
	}

	/**
	 * Status label from score.
	 *
	 * @param int $score 0–10.
	 * @return string
	 */
	private static function status_label( int $score ): string {
		if ( $score <= 2 ) {
			return __( 'Needs setup', 'local-seo-by-ankit-rawat' );
		}
		if ( $score <= 5 ) {
			return __( 'Good start', 'local-seo-by-ankit-rawat' );
		}
		if ( $score <= 8 ) {
			return __( 'Almost there', 'local-seo-by-ankit-rawat' );
		}
		return __( 'Complete', 'local-seo-by-ankit-rawat' );
	}

	/**
	 * Get a trimmed string from settings.
	 *
	 * @param array<string, mixed> $settings Settings.
	 * @param string               $key      Key.
	 * @return string
	 */
	private static function trimmed_string( array $settings, string $key ): string {
		if ( ! isset( $settings[ $key ] ) || ! is_scalar( $settings[ $key ] ) ) {
			return '';
		}
		return trim( (string) $settings[ $key ] );
	}

	/**
	 * Check if a value is a valid http/https URL.
	 *
	 * @param string $value Value to check.
	 * @return bool
	 */
	private static function is_http_url( string $value ): bool {
		if ( '' === $value ) {
			return false;
		}
		return 0 === stripos( $value, 'http://' ) || 0 === stripos( $value, 'https://' );
	}
}
