<?php
/**
 * Admin WooCommerce field visibility (independent of Store type).
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

use AnkitRawat\LocalSEO\Admin\Assets;
use AnkitRawat\LocalSEO\Schema\WooCommerce;
use PHPUnit\Framework\TestCase;

final class AdminUiTest extends TestCase {

	protected function setUp(): void {
		lsar_test_reset_state();
	}

	public function test_woocommerce_fields_follow_php_availability_not_store_type() {
		$this->assertFalse( class_exists( '\WooCommerce', false ) );
		$this->assertFalse( WooCommerce::is_active() );
		$this->assertFalse( Assets::should_show_woocommerce_fields() );
		$this->assertSame( WooCommerce::is_active(), Assets::should_show_woocommerce_fields() );
	}

	public function test_admin_js_does_not_require_store_type() {
		$js = file_get_contents( dirname( LOCAL_SEO_BY_ANKIT_RAWAT_FILE ) . '/assets/admin.js' );
		$this->assertIsString( $js );
		$this->assertStringContainsString( 'woocommerceActive', $js );
		$this->assertStringNotContainsString( "=== 'Store'", $js );
		$this->assertDoesNotMatchRegularExpression( '/woocommerce_params/', $js );
	}

	public function test_admin_css_hides_fields_only_when_woocommerce_unavailable() {
		$css = file_get_contents( dirname( LOCAL_SEO_BY_ANKIT_RAWAT_FILE ) . '/assets/admin.css' );
		$this->assertIsString( $css );
		$this->assertStringContainsString( 'lsar-woocommerce-unavailable', $css );
		$this->assertStringNotContainsString( 'Store', $css );
	}

	public function test_localize_uses_php_availability_helper() {
		$src = file_get_contents( dirname( LOCAL_SEO_BY_ANKIT_RAWAT_FILE ) . '/src/Admin/Assets.php' );
		$this->assertIsString( $src );
		$this->assertStringContainsString( 'should_show_woocommerce_fields', $src );
		$this->assertStringContainsString( 'WooCommerce::is_active()', $src );
		$this->assertDoesNotMatchRegularExpression( '/\$GLOBALS\s*\[\s*[\'"]woocommerce_params[\'"]/', $src );
	}
}
