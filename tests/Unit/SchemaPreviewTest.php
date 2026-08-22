<?php
/**
 * Unit tests for the Schema Preview rendering.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

use AnkitRawat\LocalSEO\Admin\Settings;
use AnkitRawat\LocalSEO\Plugin;
use PHPUnit\Framework\TestCase;

final class SchemaPreviewTest extends TestCase {

	protected function setUp(): void {
		lsar_test_reset_state();
		$GLOBALS['lsar_test_can']      = true;
		$GLOBALS['lsar_test_is_admin'] = true;
	}

	protected function tearDown(): void {
		lsar_test_reset_state();
		$GLOBALS['lsar_test_can']      = false;
		$GLOBALS['lsar_test_is_admin'] = false;
	}

	private function render_preview( array $settings = array() ): string {
		update_option( Plugin::OPTION, $settings );

		$obj    = new Settings();
		$method = new ReflectionMethod( $obj, 'render_schema_preview' );
		$method->setAccessible( true );

		ob_start();
		$method->invoke( $obj );
		return ob_get_clean();
	}

	private function settings( array $overrides = array() ): array {
		return array_merge( Settings::defaults(), $overrides );
	}

	// ─── Card structure ────────────────────────────────────────────────

	public function test_preview_card_renders_wrapper(): void {
		$html = $this->render_preview( $this->settings() );
		$this->assertStringContainsString( 'lsar-preview-card', $html );
	}

	public function test_preview_card_has_header_with_title(): void {
		$html = $this->render_preview( $this->settings() );
		$this->assertStringContainsString( 'Schema Preview', $html );
	}

	public function test_preview_card_has_header_description(): void {
		$html = $this->render_preview( $this->settings() );
		$this->assertStringContainsString( 'The structured data this plugin outputs on your website.', $html );
	}

	// ─── Schema disabled state ─────────────────────────────────────────

	public function test_disabled_shows_notice(): void {
		$html = $this->render_preview( $this->settings( array( 'schema_enabled' => false ) ) );
		$this->assertStringContainsString( 'Structured data output is disabled.', $html );
	}

	public function test_disabled_does_not_show_json(): void {
		$html = $this->render_preview( $this->settings( array( 'schema_enabled' => false ) ) );
		$this->assertStringNotContainsString( 'lsar-schema-json', $html );
	}

	public function test_disabled_does_not_show_copy_button(): void {
		$html = $this->render_preview( $this->settings( array( 'schema_enabled' => false ) ) );
		$this->assertStringNotContainsString( 'lsar-copy-json', $html );
	}

	// ─── Schema enabled state ──────────────────────────────────────────

	public function test_enabled_renders_details_element(): void {
		$html = $this->render_preview( $this->settings( array( 'schema_enabled' => true ) ) );
		$this->assertStringContainsString( '<details>', $html );
	}

	public function test_enabled_renders_summary_text(): void {
		$html = $this->render_preview( $this->settings( array( 'schema_enabled' => true ) ) );
		$this->assertStringContainsString( 'View JSON-LD', $html );
	}

	public function test_enabled_renders_pre_with_json(): void {
		$html = $this->render_preview( $this->settings( array(
			'schema_enabled' => true,
			'business_name'  => 'Test Biz',
		) ) );
		$this->assertStringContainsString( 'lsar-schema-json', $html );
		$this->assertStringContainsString( 'Test Biz', $html );
	}

	public function test_enabled_json_contains_context(): void {
		$html = $this->render_preview( $this->settings( array( 'schema_enabled' => true ) ) );
		$this->assertStringContainsString( 'https://schema.org', $html );
	}

	public function test_enabled_renders_copy_button(): void {
		$html = $this->render_preview( $this->settings( array( 'schema_enabled' => true ) ) );
		$this->assertStringContainsString( 'lsar-copy-json', $html );
		$this->assertStringContainsString( 'Copy JSON', $html );
	}

	public function test_enabled_renders_feedback_span(): void {
		$html = $this->render_preview( $this->settings( array( 'schema_enabled' => true ) ) );
		$this->assertStringContainsString( 'lsar-copy-feedback', $html );
		$this->assertStringContainsString( 'aria-live="polite"', $html );
	}

	public function test_enabled_renders_google_test_link(): void {
		$html = $this->render_preview( $this->settings( array( 'schema_enabled' => true ) ) );
		$this->assertStringContainsString( 'https://search.google.com/test/rich-results', $html );
		$this->assertStringContainsString( 'Test with Google Rich Results', $html );
	}

	public function test_google_link_includes_site_url(): void {
		$html = $this->render_preview( $this->settings( array( 'schema_enabled' => true ) ) );
		$this->assertStringContainsString( rawurlencode( 'https://example.test/' ), $html );
	}

	public function test_google_link_opens_new_tab(): void {
		$html = $this->render_preview( $this->settings( array( 'schema_enabled' => true ) ) );
		$this->assertStringContainsString( 'target="_blank"', $html );
		$this->assertStringContainsString( 'rel="noopener noreferrer"', $html );
	}

	public function test_enabled_renders_description_below_link(): void {
		$html = $this->render_preview( $this->settings( array( 'schema_enabled' => true ) ) );
		$this->assertStringContainsString( 'Your site must be publicly accessible', $html );
	}

	// ─── JSON uses LocalBusiness::build() path ─────────────────────────

	public function test_json_uses_build_method(): void {
		$html = $this->render_preview( $this->settings( array(
			'schema_enabled' => true,
			'business_name'  => 'Rawat Shop',
			'phone'          => '+91 12345 67890',
		) ) );
		$this->assertStringContainsString( 'Rawat Shop', $html );
		$this->assertStringContainsString( '+91 12345 67890', $html );
	}

	public function test_json_includes_site_url(): void {
		$html = $this->render_preview( $this->settings( array( 'schema_enabled' => true ) ) );
		$this->assertStringContainsString( 'https://example.test/', $html );
	}

	public function test_preview_json_identical_to_canonical_build(): void {
		$s = $this->settings( array(
			'schema_enabled' => true,
			'business_name'  => 'Canonical Test',
			'business_type'  => 'Restaurant',
			'phone'          => '+91 12345 67890',
			'street_address' => '42 Test St',
			'locality'       => 'Delhi',
			'country'        => 'India',
		) );

		$lb       = new \AnkitRawat\LocalSEO\Schema\LocalBusiness();
		$expected = $lb->build( $s, home_url( '/' ) );

		$html = $this->render_preview( $s );
		preg_match( '/<code>(.*?)<\/code>/s', $html, $m );
		$this->assertNotEmpty( $m[1], 'JSON block must exist in preview' );

		$actual = json_decode( html_entity_decode( $m[1], ENT_QUOTES, 'UTF-8' ), true );
		$this->assertSame( $expected, $actual );
	}

	// ─── XSS escaping ──────────────────────────────────────────────────

	public function test_xss_in_business_name_is_escaped(): void {
		$html = $this->render_preview( $this->settings( array(
			'schema_enabled' => true,
			'business_name'  => '<script>alert(1)</script>',
		) ) );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '</script>', $html );
	}

	// ─── Disabled state completeness ────────────────────────────────────

	public function test_disabled_does_not_show_details(): void {
		$html = $this->render_preview( $this->settings( array( 'schema_enabled' => false ) ) );
		$this->assertStringNotContainsString( '<details>', $html );
	}

	public function test_disabled_does_not_show_google_link(): void {
		$html = $this->render_preview( $this->settings( array( 'schema_enabled' => false ) ) );
		$this->assertStringNotContainsString( 'search.google.com', $html );
	}

	// ─── Store catalog note ────────────────────────────────────────────

	public function test_store_note_hidden_when_type_not_store(): void {
		$html = $this->render_preview( $this->settings( array(
			'schema_enabled'             => true,
			'business_type'              => 'Restaurant',
			'woocommerce_product_schema' => true,
		) ) );
		$this->assertStringNotContainsString( 'lsar-preview-store-note', $html );
	}

	public function test_store_note_hidden_when_wc_inactive(): void {
		$this->assertFalse(
			\AnkitRawat\LocalSEO\Schema\WooCommerce::is_active(),
			'WooCommerce class is not loaded in unit tests'
		);
		$html = $this->render_preview( $this->settings( array(
			'schema_enabled'             => true,
			'business_type'              => 'Store',
			'woocommerce_product_schema' => true,
		) ) );
		$this->assertStringNotContainsString( 'lsar-preview-store-note', $html );
	}

	public function test_store_note_requires_all_three_conditions(): void {
		$wc_active = \AnkitRawat\LocalSEO\Schema\WooCommerce::is_active();
		if ( ! $wc_active ) {
			$this->markTestSkipped( 'WooCommerce class not available in unit tests.' );
		}

		$html = $this->render_preview( $this->settings( array(
			'schema_enabled'             => true,
			'business_type'              => 'Store',
			'woocommerce_product_schema' => true,
		) ) );
		$this->assertStringContainsString( 'lsar-preview-store-note', $html );
		$this->assertStringContainsString( 'hasOfferCatalog', $html );
	}

	public function test_store_note_hidden_when_product_schema_disabled(): void {
		$html = $this->render_preview( $this->settings( array(
			'schema_enabled'             => true,
			'business_type'              => 'Store',
			'woocommerce_product_schema' => false,
		) ) );
		$this->assertStringNotContainsString( 'lsar-preview-store-note', $html );
	}
}
