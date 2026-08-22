<?php
/**
 * Dismissible activation admin notice.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

namespace AnkitRawat\LocalSEO\Admin;

use AnkitRawat\LocalSEO\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shows a one-time onboarding notice after plugin activation.
 */
final class ActivationNotice {

	public const META_KEY = 'local_seo_by_ankit_rawat_activation_notice_dismissed';

	private const DISMISS_ACTION = 'lsar_dismiss_activation_notice';

	/**
	 * @return void
	 */
	public function register() {
		add_action( 'admin_notices', array( $this, 'maybe_show' ) );
		add_action( 'admin_init', array( $this, 'handle_dismiss' ) );
	}

	/**
	 * Flag the activating administrator for the onboarding notice.
	 *
	 * @return void
	 */
	public static function on_activation() {
		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return;
		}

		if ( '' === get_user_meta( $user_id, self::META_KEY, true ) ) {
			update_user_meta( $user_id, self::META_KEY, '0' );
		}
	}

	/**
	 * Render the onboarding notice when appropriate.
	 *
	 * @return void
	 */
	public function maybe_show() {
		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return;
		}

		if ( '0' !== get_user_meta( $user_id, self::META_KEY, true ) ) {
			return;
		}

		$settings_url = admin_url( 'admin.php?page=' . Plugin::PAGE );
		$dismiss_url  = wp_nonce_url(
			add_query_arg( 'lsar_dismiss_notice', '1' ),
			self::DISMISS_ACTION
		);

		?>
		<div class="notice notice-info lsar-activation-notice">
			<p>
				<strong><?php esc_html_e( 'Local SEO By Ankit Rawat is ready.', 'local-seo-by-ankit-rawat' ); ?></strong>
				<?php esc_html_e( 'Add your business details to help search engines find your business.', 'local-seo-by-ankit-rawat' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( $settings_url ); ?>" class="button button-primary"><?php esc_html_e( 'Configure Local SEO', 'local-seo-by-ankit-rawat' ); ?></a>
				<a href="<?php echo esc_url( $dismiss_url ); ?>" style="margin-left:1em;"><?php esc_html_e( 'Dismiss', 'local-seo-by-ankit-rawat' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Process the dismiss action.
	 *
	 * @return void
	 */
	public function handle_dismiss() {
		if ( ! isset( $_GET['lsar_dismiss_notice'] ) ) {
			return;
		}

		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], self::DISMISS_ACTION ) ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( $user_id <= 0 ) {
			return;
		}

		update_user_meta( $user_id, self::META_KEY, '1' );

		wp_safe_redirect( remove_query_arg( array( 'lsar_dismiss_notice', '_wpnonce' ) ) );
		exit;
	}
}
