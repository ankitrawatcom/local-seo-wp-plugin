<?php
/**
 * Plugin bootstrap.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

namespace AnkitRawat\LocalSEO;

use AnkitRawat\LocalSEO\Admin\Assets;
use AnkitRawat\LocalSEO\Admin\Settings;
use AnkitRawat\LocalSEO\Migration\Migrator;
use AnkitRawat\LocalSEO\Schema\LocalBusiness;
use AnkitRawat\LocalSEO\Schema\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers hooks for Local SEO By Ankit Rawat.
 */
final class Plugin {

	public const VERSION      = '4.0.1';
	public const OPTION       = 'local_seo_by_ankit_rawat_options';
	public const VERSION_KEY  = 'local_seo_by_ankit_rawat_version';
	public const GROUP        = 'local_seo_by_ankit_rawat_group';
	public const PAGE         = 'local-seo-settings';
	public const CAPABILITY   = 'manage_options';
	public const TEXT_DOMAIN  = 'local-seo-by-ankit-rawat';
	public const LEGACY_CACHE = 'local_seo_json_ld_schema';

	/**
	 * Register runtime hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'admin_init', array( Migrator::class, 'maybe_run' ), 1 );
		add_action( 'admin_init', array( $this, 'privacy_policy' ) );

		$settings = new Settings();
		$settings->register();

		$assets = new Assets();
		$assets->register();

		$local = new LocalBusiness();
		$local->register();

		$woocommerce = new WooCommerce();
		$woocommerce->register();
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			self::TEXT_DOMAIN,
			false,
			dirname( plugin_basename( LOCAL_SEO_BY_ANKIT_RAWAT_FILE ) ) . '/languages'
		);
	}

	/**
	 * Privacy policy suggested text (business NAP stored locally, no remote send).
	 *
	 * @return void
	 */
	public function privacy_policy() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}

		$content = '<p>' . esc_html__( 'Local SEO By Ankit Rawat stores business contact details that you enter in plugin settings (name, address, telephone, geographic coordinates, logo/image URLs, and optional social profile URLs) in the WordPress database. When schema output is enabled, those details may be printed in public JSON-LD on the site. The plugin does not send this information to Google or other remote services.', 'local-seo-by-ankit-rawat' ) . '</p>';

		wp_add_privacy_policy_content(
			__( 'Local SEO By Ankit Rawat', 'local-seo-by-ankit-rawat' ),
			wp_kses_post( $content )
		);
	}

	/**
	 * Activation: migrate 3.3 options if present, then record version.
	 *
	 * @return void
	 */
	public static function activate() {
		Migrator::maybe_run();

		if ( false === get_option( self::VERSION_KEY, false ) ) {
			add_option( self::VERSION_KEY, self::VERSION, '', false );
		}

		if ( false === get_option( self::OPTION, false ) ) {
			add_option( self::OPTION, Settings::defaults(), '', true );
		}
	}

	/**
	 * Deactivation does not delete settings.
	 *
	 * @return void
	 */
	public static function deactivate() {
		delete_transient( self::LEGACY_CACHE );
	}

	/**
	 * Return merged, filtered settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function settings() {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$settings = array_merge( Settings::defaults(), $stored );

		/**
		 * Filter plugin settings used for admin display and schema.
		 *
		 * @param array<string, mixed> $settings Settings array.
		 */
		return apply_filters( 'local_seo_by_ankit_rawat_settings', $settings );
	}
}
