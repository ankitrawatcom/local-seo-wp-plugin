<?php
/**
 * JSON-LD encoding for script context.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

namespace AnkitRawat\LocalSEO\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encodes schema arrays for application/ld+json script tags.
 */
final class JsonLd {

	/**
	 * Encode flags that keep JSON valid while neutralizing HTML/script breakout.
	 *
	 * JSON_HEX_TAG encodes < and > so `</script>` cannot terminate the tag.
	 */
	const ENCODE_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

	/**
	 * Encode a schema graph as JSON.
	 *
	 * @param array<string, mixed> $graph Schema object.
	 * @return string Empty string on failure.
	 */
	public static function encode( array $graph ) {
		if ( array() === $graph ) {
			return '';
		}

		$json = wp_json_encode( $graph, self::ENCODE_FLAGS );
		if ( ! is_string( $json ) || '' === $json || 'null' === $json ) {
			return '';
		}

		return $json;
	}

	/**
	 * Print a JSON-LD script tag.
	 *
	 * @param array<string, mixed> $graph Schema object.
	 * @return void
	 */
	public static function print_script( array $graph ) {
		$json = self::encode( $graph );
		if ( '' === $json ) {
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON_HEX_TAG encoded for script context.
		echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
	}

	/**
	 * Whether encoded JSON still contains a raw script closer.
	 *
	 * Used by tests; production output should never return true.
	 *
	 * @param string $json Encoded JSON.
	 * @return bool
	 */
	public static function contains_raw_script_close( $json ) {
		return false !== stripos( $json, '</script' );
	}
}
