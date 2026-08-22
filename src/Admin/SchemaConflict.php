<?php
/**
 * Detects active SEO plugins that may produce duplicate structured data.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

namespace AnkitRawat\LocalSEO\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lightweight detection of popular SEO plugins that may output JSON-LD.
 *
 * Detection only — never modifies another plugin or disables schema.
 */
final class SchemaConflict {

	/**
	 * Detect active SEO plugins that may also output structured data.
	 *
	 * @return array{detected: bool, plugins: list<array{key: string, name: string}>}
	 */
	public static function detect(): array {
		$found = array();

		if ( self::is_yoast_active() ) {
			$found[] = array(
				'key'  => 'yoast',
				'name' => 'Yoast SEO',
			);
		}

		if ( self::is_rank_math_active() ) {
			$found[] = array(
				'key'  => 'rank-math',
				'name' => 'Rank Math SEO',
			);
		}

		if ( self::is_aioseo_active() ) {
			$found[] = array(
				'key'  => 'aioseo',
				'name' => 'All in One SEO',
			);
		}

		return array(
			'detected' => array() !== $found,
			'plugins'  => $found,
		);
	}

	/**
	 * @return bool
	 */
	private static function is_yoast_active(): bool {
		return defined( 'WPSEO_VERSION' ) && class_exists( 'WPSEO_Utils', false );
	}

	/**
	 * @return bool
	 */
	private static function is_rank_math_active(): bool {
		return defined( 'RANK_MATH_VERSION' ) && class_exists( 'RankMath', false );
	}

	/**
	 * @return bool
	 */
	private static function is_aioseo_active(): bool {
		return defined( 'AIOSEO_VERSION' ) && class_exists( 'AIOSEO\\Plugin\\AIOSEO', false );
	}
}
