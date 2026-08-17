<?php
/**
 * Admin assets.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

namespace AnkitRawat\LocalSEO\Admin;

use AnkitRawat\LocalSEO\Plugin;
use AnkitRawat\LocalSEO\Schema\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueues CSS/JS only on this plugin's settings screen.
 */
final class Assets {

	/**
	 * Hook asset loading.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Enqueue CSS and JS on the settings screen.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( 'toplevel_page_' . Plugin::PAGE !== $hook ) {
			return;
		}

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			return;
		}

		$handle = 'local-seo-by-ankit-rawat-admin';

		wp_enqueue_style(
			$handle,
			LOCAL_SEO_BY_ANKIT_RAWAT_URL . 'assets/admin.css',
			array(),
			LOCAL_SEO_BY_ANKIT_RAWAT_VERSION
		);

		wp_enqueue_script(
			$handle,
			LOCAL_SEO_BY_ANKIT_RAWAT_URL . 'assets/admin.js',
			array( 'jquery' ),
			LOCAL_SEO_BY_ANKIT_RAWAT_VERSION,
			true
		);

		wp_localize_script(
			$handle,
			'lsarAdmin',
			array(
				'woocommerceActive' => WooCommerce::is_active(),
			)
		);
	}
}
