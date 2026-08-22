<?php
/**
 * Unit tests for the dismissible activation admin notice.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

use AnkitRawat\LocalSEO\Admin\ActivationNotice;
use AnkitRawat\LocalSEO\Admin\HealthScore;
use AnkitRawat\LocalSEO\Admin\Settings;
use AnkitRawat\LocalSEO\Plugin;
use PHPUnit\Framework\TestCase;

final class ActivationNoticeTest extends TestCase {

	protected function setUp(): void {
		lsar_test_reset_state();
		$_GET = array();
	}

	protected function tearDown(): void {
		$_GET = array();
	}

	/** @return string */
	private function capture_notice(): string {
		$notice = new ActivationNotice();
		ob_start();
		$notice->maybe_show();
		return ob_get_clean();
	}

	// ─── Activation ──────────────────────────────────────────────────

	public function test_on_activation_sets_user_meta(): void {
		$GLOBALS['lsar_test_current_user_id'] = 1;
		ActivationNotice::on_activation();
		$this->assertSame( '0', get_user_meta( 1, ActivationNotice::META_KEY, true ) );
	}

	public function test_on_activation_does_not_overwrite_dismissed(): void {
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_user_meta( 1, ActivationNotice::META_KEY, '1' );
		ActivationNotice::on_activation();
		$this->assertSame( '1', get_user_meta( 1, ActivationNotice::META_KEY, true ) );
	}

	public function test_on_activation_skips_when_no_user(): void {
		$GLOBALS['lsar_test_current_user_id'] = 0;
		ActivationNotice::on_activation();
		$this->assertSame( array(), $GLOBALS['lsar_test_user_meta'] );
	}

	public function test_activation_does_not_modify_plugin_options(): void {
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_option( Plugin::OPTION, Settings::defaults() );
		$before = get_option( Plugin::OPTION );
		ActivationNotice::on_activation();
		$this->assertSame( $before, get_option( Plugin::OPTION ) );
	}

	// ─── Notice visibility ───────────────────────────────────────────

	public function test_admin_with_capability_sees_notice(): void {
		$GLOBALS['lsar_test_can']             = true;
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_user_meta( 1, ActivationNotice::META_KEY, '0' );

		$html = $this->capture_notice();
		$this->assertStringContainsString( 'notice notice-info', $html );
	}

	public function test_user_without_capability_does_not_see_notice(): void {
		$GLOBALS['lsar_test_can']             = false;
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_user_meta( 1, ActivationNotice::META_KEY, '0' );

		$html = $this->capture_notice();
		$this->assertSame( '', $html );
	}

	public function test_no_user_context_does_not_show_notice(): void {
		$GLOBALS['lsar_test_can']             = true;
		$GLOBALS['lsar_test_current_user_id'] = 0;

		$html = $this->capture_notice();
		$this->assertSame( '', $html );
	}

	public function test_notice_not_shown_when_meta_absent(): void {
		$GLOBALS['lsar_test_can']             = true;
		$GLOBALS['lsar_test_current_user_id'] = 1;

		$html = $this->capture_notice();
		$this->assertSame( '', $html );
	}

	public function test_notice_contains_settings_url(): void {
		$GLOBALS['lsar_test_can']             = true;
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_user_meta( 1, ActivationNotice::META_KEY, '0' );

		$html = $this->capture_notice();
		$this->assertStringContainsString( 'admin.php?page=' . Plugin::PAGE, $html );
	}

	public function test_notice_contains_expected_copy(): void {
		$GLOBALS['lsar_test_can']             = true;
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_user_meta( 1, ActivationNotice::META_KEY, '0' );

		$html = $this->capture_notice();
		$this->assertStringContainsString( 'Local SEO By Ankit Rawat is ready.', $html );
		$this->assertStringContainsString( 'Add your business details', $html );
		$this->assertStringContainsString( 'Configure Local SEO', $html );
	}

	public function test_notice_uses_native_notice_classes(): void {
		$GLOBALS['lsar_test_can']             = true;
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_user_meta( 1, ActivationNotice::META_KEY, '0' );

		$html = $this->capture_notice();
		$this->assertStringContainsString( 'class="notice notice-info', $html );
	}

	public function test_notice_contains_dismiss_link(): void {
		$GLOBALS['lsar_test_can']             = true;
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_user_meta( 1, ActivationNotice::META_KEY, '0' );

		$html = $this->capture_notice();
		$this->assertStringContainsString( 'lsar_dismiss_notice', $html );
		$this->assertStringContainsString( 'Dismiss', $html );
	}

	// ─── Dismissal ───────────────────────────────────────────────────

	public function test_valid_dismissal_sets_user_meta(): void {
		$GLOBALS['lsar_test_can']             = true;
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_user_meta( 1, ActivationNotice::META_KEY, '0' );

		$_GET['lsar_dismiss_notice'] = '1';
		$_GET['_wpnonce']            = 'test_nonce_lsar_dismiss_activation_notice';

		$notice = new ActivationNotice();
		try {
			$notice->handle_dismiss();
		} catch ( LsarTestRedirectException $e ) {
			// Expected — redirect triggers after successful dismissal.
		}

		$this->assertSame( '1', get_user_meta( 1, ActivationNotice::META_KEY, true ) );
	}

	public function test_invalid_nonce_does_not_dismiss(): void {
		$GLOBALS['lsar_test_can']             = true;
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_user_meta( 1, ActivationNotice::META_KEY, '0' );

		$_GET['lsar_dismiss_notice'] = '1';
		$_GET['_wpnonce']            = 'wrong_nonce';

		$notice = new ActivationNotice();
		$notice->handle_dismiss();

		$this->assertSame( '0', get_user_meta( 1, ActivationNotice::META_KEY, true ) );
	}

	public function test_missing_nonce_does_not_dismiss(): void {
		$GLOBALS['lsar_test_can']             = true;
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_user_meta( 1, ActivationNotice::META_KEY, '0' );

		$_GET['lsar_dismiss_notice'] = '1';

		$notice = new ActivationNotice();
		$notice->handle_dismiss();

		$this->assertSame( '0', get_user_meta( 1, ActivationNotice::META_KEY, true ) );
	}

	public function test_user_without_capability_cannot_dismiss(): void {
		$GLOBALS['lsar_test_can']             = false;
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_user_meta( 1, ActivationNotice::META_KEY, '0' );

		$_GET['lsar_dismiss_notice'] = '1';
		$_GET['_wpnonce']            = 'test_nonce_lsar_dismiss_activation_notice';

		$notice = new ActivationNotice();
		$notice->handle_dismiss();

		$this->assertSame( '0', get_user_meta( 1, ActivationNotice::META_KEY, true ) );
	}

	public function test_dismissal_is_idempotent(): void {
		$GLOBALS['lsar_test_can']             = true;
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_user_meta( 1, ActivationNotice::META_KEY, '1' );

		$_GET['lsar_dismiss_notice'] = '1';
		$_GET['_wpnonce']            = 'test_nonce_lsar_dismiss_activation_notice';

		$notice = new ActivationNotice();
		try {
			$notice->handle_dismiss();
		} catch ( LsarTestRedirectException $e ) {
			// Expected.
		}

		$this->assertSame( '1', get_user_meta( 1, ActivationNotice::META_KEY, true ) );
	}

	public function test_after_dismissal_notice_not_shown(): void {
		$GLOBALS['lsar_test_can']             = true;
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_user_meta( 1, ActivationNotice::META_KEY, '0' );

		$_GET['lsar_dismiss_notice'] = '1';
		$_GET['_wpnonce']            = 'test_nonce_lsar_dismiss_activation_notice';

		$notice = new ActivationNotice();
		try {
			$notice->handle_dismiss();
		} catch ( LsarTestRedirectException $e ) {
			// Expected.
		}

		$_GET = array();
		$html = $this->capture_notice();
		$this->assertSame( '', $html );
	}

	public function test_dismissal_does_not_modify_plugin_options(): void {
		$GLOBALS['lsar_test_can']             = true;
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_user_meta( 1, ActivationNotice::META_KEY, '0' );
		update_option( Plugin::OPTION, Settings::defaults() );
		$before = get_option( Plugin::OPTION );

		$_GET['lsar_dismiss_notice'] = '1';
		$_GET['_wpnonce']            = 'test_nonce_lsar_dismiss_activation_notice';

		$notice = new ActivationNotice();
		try {
			$notice->handle_dismiss();
		} catch ( LsarTestRedirectException $e ) {
			// Expected.
		}

		$this->assertSame( $before, get_option( Plugin::OPTION ) );
	}

	// ─── User meta ───────────────────────────────────────────────────

	public function test_exact_meta_key_name(): void {
		$this->assertSame(
			'local_seo_by_ankit_rawat_activation_notice_dismissed',
			ActivationNotice::META_KEY
		);
	}

	public function test_no_unrelated_user_meta(): void {
		$GLOBALS['lsar_test_current_user_id'] = 1;
		update_user_meta( 1, 'unrelated_key', 'value' );

		ActivationNotice::on_activation();

		$this->assertSame( 'value', get_user_meta( 1, 'unrelated_key', true ) );
	}

	public function test_plugin_options_preserved_through_full_lifecycle(): void {
		$GLOBALS['lsar_test_current_user_id'] = 1;
		$GLOBALS['lsar_test_can']             = true;
		$custom = array_merge( Settings::defaults(), array( 'business_name' => 'My Shop' ) );
		update_option( Plugin::OPTION, $custom );

		ActivationNotice::on_activation();

		$_GET['lsar_dismiss_notice'] = '1';
		$_GET['_wpnonce']            = 'test_nonce_lsar_dismiss_activation_notice';

		$notice = new ActivationNotice();
		try {
			$notice->handle_dismiss();
		} catch ( LsarTestRedirectException $e ) {
			// Expected.
		}

		$this->assertSame( $custom, get_option( Plugin::OPTION ) );
	}

	public function test_multiple_users_independent(): void {
		$GLOBALS['lsar_test_can'] = true;

		$GLOBALS['lsar_test_current_user_id'] = 1;
		ActivationNotice::on_activation();
		update_user_meta( 1, ActivationNotice::META_KEY, '1' );

		$GLOBALS['lsar_test_current_user_id'] = 2;
		update_user_meta( 2, ActivationNotice::META_KEY, '0' );

		$GLOBALS['lsar_test_current_user_id'] = 1;
		$html1 = $this->capture_notice();
		$this->assertSame( '', $html1 );

		$GLOBALS['lsar_test_current_user_id'] = 2;
		$html2 = $this->capture_notice();
		$this->assertStringContainsString( 'notice notice-info', $html2 );
	}

	// ─── Uninstall ───────────────────────────────────────────────────

	public function test_uninstall_removes_activation_meta(): void {
		update_user_meta( 1, ActivationNotice::META_KEY, '1' );
		update_user_meta( 2, ActivationNotice::META_KEY, '0' );

		delete_metadata( 'user', 0, ActivationNotice::META_KEY, '', true );

		$this->assertSame( '', get_user_meta( 1, ActivationNotice::META_KEY, true ) );
		$this->assertSame( '', get_user_meta( 2, ActivationNotice::META_KEY, true ) );
	}

	public function test_uninstall_preserves_other_user_meta(): void {
		update_user_meta( 1, 'other_meta_key', 'keep_this' );
		update_user_meta( 1, ActivationNotice::META_KEY, '1' );

		delete_metadata( 'user', 0, ActivationNotice::META_KEY, '', true );

		$this->assertSame( 'keep_this', get_user_meta( 1, 'other_meta_key', true ) );
	}

	public function test_uninstall_safe_when_meta_absent(): void {
		$result = delete_metadata( 'user', 0, ActivationNotice::META_KEY, '', true );
		$this->assertTrue( $result );
	}

	// ─── Regression ──────────────────────────────────────────────────

	public function test_defaults_unchanged(): void {
		$this->assertCount( 20, Settings::defaults() );
	}

	public function test_option_keys_unchanged(): void {
		$expected = array(
			'schema_enabled', 'business_type', 'business_name', 'street_address',
			'locality', 'region', 'postal_code', 'country', 'phone', 'price_range',
			'logo', 'images', 'social_profiles', 'latitude', 'longitude',
			'place_id', 'woocommerce_product_schema', 'woocommerce_currency',
			'legacy_aggregate_rating', 'legacy_review_count',
		);
		$this->assertSame( $expected, array_keys( Settings::defaults() ) );
	}

	public function test_sanitization_unchanged(): void {
		$clean = Settings::sanitize( array(
			'schema_enabled' => '1',
			'business_name'  => 'Test',
			'latitude'       => '28.6139',
		) );
		$this->assertTrue( $clean['schema_enabled'] );
		$this->assertSame( 'Test', $clean['business_name'] );
		$this->assertNotSame( '', $clean['latitude'] );
	}

	public function test_health_score_unchanged(): void {
		$settings = array_merge( Settings::defaults(), array(
			'schema_enabled'  => true,
			'business_name'   => 'Test',
			'street_address'  => '123 Main',
			'locality'        => 'Delhi',
			'country'         => 'India',
			'region'          => 'Delhi',
			'phone'           => '+91 12345 67890',
			'logo'            => 'https://example.com/logo.png',
			'images'          => array( 'https://example.com/photo.jpg' ),
			'latitude'        => '28.6139',
			'longitude'       => '77.2090',
			'social_profiles' => array( 'https://facebook.com/test' ),
			'price_range'     => '$$',
		) );
		$result = HealthScore::calculate( $settings );
		$this->assertSame( 10, $result['score'] );
		$this->assertSame( 10, $result['total'] );
	}
}
