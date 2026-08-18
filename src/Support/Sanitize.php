<?php
/**
 * Input sanitization helpers.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

namespace AnkitRawat\LocalSEO\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitizes and validates plugin field values.
 */
final class Sanitize {

	/**
	 * Allowed Schema.org @type values.
	 *
	 * @return array<int, string>
	 */
	public static function business_types() {
		return array(
			'LocalBusiness',
			'Restaurant',
			'Hotel',
			'ProfessionalService',
			'Store',
		);
	}

	/**
	 * Sanitize a single-line string.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function text( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		return sanitize_text_field( (string) $value );
	}

	/**
	 * Allowlist business type.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function business_type( $value ) {
		$type = self::text( $value );
		if ( in_array( $type, self::business_types(), true ) ) {
			return $type;
		}

		return 'LocalBusiness';
	}

	/**
	 * HTTP(S) URL or empty string.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function http_url( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$url = esc_url_raw( trim( (string) $value ) );
		if ( '' === $url ) {
			return '';
		}

		if ( 0 !== stripos( $url, 'https://' ) && 0 !== stripos( $url, 'http://' ) ) {
			return '';
		}

		return $url;
	}

	/**
	 * Normalize comma-separated or array URL lists.
	 *
	 * @param mixed $value Raw value.
	 * @return array<int, string>
	 */
	public static function url_list( $value ) {
		$items = array();

		if ( is_array( $value ) ) {
			$items = $value;
		} elseif ( is_scalar( $value ) ) {
			$split = preg_split( '/\s*,\s*/', (string) $value );
			$items = is_array( $split ) ? $split : array();
		}

		$urls = array();
		foreach ( $items as $item ) {
			$url = self::http_url( $item );
			if ( '' !== $url && ! in_array( $url, $urls, true ) ) {
				$urls[] = $url;
			}
		}

		return $urls;
	}

	/**
	 * Phone string: keep international characters, cap length.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function phone( $value ) {
		$phone = self::text( $value );
		$phone = preg_replace( '/[^0-9+\-\s().#extEXT]/', '', $phone );
		if ( ! is_string( $phone ) ) {
			return '';
		}

		$phone = trim( $phone );
		if ( strlen( $phone ) > 32 ) {
			$phone = substr( $phone, 0, 32 );
		}

		return $phone;
	}

	/**
	 * Latitude in range or empty.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function latitude( $value ) {
		if ( ! is_scalar( $value ) || '' === trim( (string) $value ) ) {
			return '';
		}

		if ( ! is_numeric( $value ) ) {
			return '';
		}

		$lat = (float) $value;
		if ( $lat < -90 || $lat > 90 ) {
			return '';
		}

		return self::geo_string( $lat );
	}

	/**
	 * Longitude in range or empty.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function longitude( $value ) {
		if ( ! is_scalar( $value ) || '' === trim( (string) $value ) ) {
			return '';
		}

		if ( ! is_numeric( $value ) ) {
			return '';
		}

		$lng = (float) $value;
		if ( $lng < -180 || $lng > 180 ) {
			return '';
		}

		return self::geo_string( $lng );
	}

	/**
	 * Format a geo number without trailing junk.
	 *
	 * @param float $number Coordinate.
	 * @return string
	 */
	private static function geo_string( $number ) {
		$formatted = rtrim( rtrim( sprintf( '%.8f', $number ), '0' ), '.' );
		return $formatted;
	}

	/**
	 * ISO 4217-like currency code or empty.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function currency( $value ) {
		$code = strtoupper( self::text( $value ) );
		if ( 1 !== preg_match( '/^[A-Z]{3}$/', $code ) ) {
			return '';
		}

		return $code;
	}

	/**
	 * Price range such as $, $$, or $10-$50.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function price_range( $value ) {
		$range = self::text( $value );
		if ( '' === $range ) {
			return '';
		}

		if ( strlen( $range ) > 32 ) {
			$range = substr( $range, 0, 32 );
		}

		return $range;
	}

	/**
	 * Boolean from checkbox-like input.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	public static function bool( $value ) {
		return in_array( $value, array( true, 1, '1', 'true', 'on', 'yes' ), true );
	}

	/**
	 * Plain text from HTML (product descriptions).
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function plain_text( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$text = wp_strip_all_tags( (string) $value );
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = sanitize_text_field( $text );

		return $text;
	}

	/**
	 * Offer price as a numeric string.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function price( $value ) {
		if ( ! is_scalar( $value ) || '' === (string) $value ) {
			return '';
		}

		if ( ! is_numeric( $value ) ) {
			return '';
		}

		$price = (float) $value;
		if ( $price < 0 ) {
			return '';
		}

		return rtrim( rtrim( number_format( $price, 2, '.', '' ), '0' ), '.' );
	}
}
