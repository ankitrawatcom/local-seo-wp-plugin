<?php
/**
 * Settings API screen and sanitization.
 *
 * @package AnkitRawat\LocalSEO
 */

declare(strict_types=1);

namespace AnkitRawat\LocalSEO\Admin;

use AnkitRawat\LocalSEO\Plugin;
use AnkitRawat\LocalSEO\Schema\WooCommerce;
use AnkitRawat\LocalSEO\Support\Sanitize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Local SEO settings page.
 */
final class Settings {

	/** @var array<string, array> */
	private $field_config = array();

	/**
	 * Default option values.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults() {
		return array(
			'schema_enabled'             => false,
			'business_type'              => 'LocalBusiness',
			'business_name'              => '',
			'street_address'             => '',
			'locality'                   => '',
			'region'                     => '',
			'postal_code'                => '',
			'country'                    => '',
			'phone'                      => '',
			'price_range'                => '',
			'logo'                       => '',
			'images'                     => array(),
			'social_profiles'            => array(),
			'latitude'                   => '',
			'longitude'                  => '',
			'place_id'                   => '',
			'woocommerce_product_schema' => false,
			'woocommerce_currency'       => '',
			'legacy_aggregate_rating'    => '',
			'legacy_review_count'        => '',
		);
	}

	/**
	 * Hook Settings API.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
	}

	/**
	 * Top-level menu (same location as 3.3).
	 *
	 * @return void
	 */
	public function add_menu() {
		add_menu_page(
			__( 'Local SEO By Ankit Rawat', 'local-seo-by-ankit-rawat' ),
			__( 'Local SEO By Ankit Rawat', 'local-seo-by-ankit-rawat' ),
			Plugin::CAPABILITY,
			Plugin::PAGE,
			array( $this, 'render_page' ),
			'dashicons-location-alt',
			100
		);
	}

	/**
	 * Register the single namespaced option.
	 *
	 * @return void
	 */
	public function register_setting() {
		register_setting(
			Plugin::GROUP,
			Plugin::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
				'capability'        => Plugin::CAPABILITY,
			)
		);

		add_settings_section(
			'lsar_main',
			__( 'Your business information', 'local-seo-by-ankit-rawat' ),
			array( $this, 'render_main_section' ),
			Plugin::PAGE
		);

		$this->add_field( 'schema_enabled', __( 'Show your business in search results', 'local-seo-by-ankit-rawat' ), 'checkbox' );
		$this->add_field(
			'business_type',
			__( 'What type of business are you?', 'local-seo-by-ankit-rawat' ),
			'business_type',
			'lsar_main',
			array( 'label_for' => 'lsar-business-type' )
		);
		$this->add_field( 'business_name', __( 'Business name', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'street_address', __( 'Street address', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'locality', __( 'City / Town', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'region', __( 'State / Province / Region', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'postal_code', __( 'ZIP / Postal code', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'country', __( 'Country', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'phone', __( 'Phone number', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'price_range', __( 'Typical price range', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'logo', __( 'Business logo', 'local-seo-by-ankit-rawat' ), 'url' );
		$this->add_field( 'images', __( 'Additional photos', 'local-seo-by-ankit-rawat' ), 'textarea' );
		$this->add_field( 'social_profiles', __( 'Social media links', 'local-seo-by-ankit-rawat' ), 'textarea' );
		$this->add_field( 'latitude', __( 'Latitude', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'longitude', __( 'Longitude', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'place_id', __( 'Google Place ID', 'local-seo-by-ankit-rawat' ), 'text' );

		add_settings_section(
			'lsar_woocommerce',
			__( 'WooCommerce product data', 'local-seo-by-ankit-rawat' ),
			array( $this, 'render_woocommerce_section' ),
			Plugin::PAGE
		);

		$this->add_field(
			'woocommerce_product_schema',
			__( 'Product schema', 'local-seo-by-ankit-rawat' ),
			'checkbox',
			'lsar_woocommerce',
			array( 'class' => 'lsar-woocommerce-field' )
		);
		$this->add_field(
			'woocommerce_currency',
			__( 'Price currency override', 'local-seo-by-ankit-rawat' ),
			'text',
			'lsar_woocommerce',
			array( 'class' => 'lsar-woocommerce-field' )
		);
	}

	/**
	 * Register one Settings API field.
	 *
	 * @param string               $id       Field id.
	 * @param string               $label    Label.
	 * @param string               $type     Control type.
	 * @param string               $section  Section id.
	 * @param array<string, mixed> $extra    Extra args.
	 * @return void
	 */
	private function add_field( $id, $label, $type, $section = 'lsar_main', array $extra = array() ) {
		$this->field_config[ $id ] = array(
			'label' => $label,
			'type'  => $type,
			'extra' => $extra,
		);

		$args = array_merge(
			array(
				'id'        => $id,
				'type'      => $type,
				'label_for' => 'lsar-' . $id,
			),
			$extra
		);

		add_settings_field(
			$id,
			$label,
			array( $this, 'render_field' ),
			Plugin::PAGE,
			$section,
			$args
		);
	}

	/**
	 * Main section help text.
	 *
	 * @return void
	 */
	public function render_main_section() {
		echo '<p>' . esc_html__( 'Enter your business details below. This information is used to create structured data that search engines like Google use for rich results. Empty fields are simply skipped.', 'local-seo-by-ankit-rawat' ) . '</p>';
	}

	/**
	 * WooCommerce section help.
	 *
	 * @return void
	 */
	public function render_woocommerce_section() {
		if ( ! WooCommerce::is_active() ) {
			echo '<p>' . esc_html__( 'WooCommerce is not active. Product schema settings are hidden until WooCommerce is installed and activated.', 'local-seo-by-ankit-rawat' ) . '</p>';
			return;
		}

		echo '<p>' . esc_html__( 'When enabled, Product JSON-LD is printed on WooCommerce product pages for any business type.', 'local-seo-by-ankit-rawat' ) . '</p>';
		echo '<p>' . esc_html__( 'If the business type is Store, up to five published products are also listed in a Schema.org OfferCatalog (hasOfferCatalog with ListItem entries) on the front page, the posts index (home), and the WooCommerce shop. The catalog is not added on every URL. Customize placement with the local_seo_by_ankit_rawat_embed_store_catalog filter.', 'local-seo-by-ankit-rawat' ) . '</p>';
	}

	/**
	 * Settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'local-seo-by-ankit-rawat' ) );
		}

		$wc_wrap = WooCommerce::is_active() ? 'lsar-woocommerce-available' : 'lsar-woocommerce-unavailable';
		?>
		<div class="wrap lsar-settings <?php echo esc_attr( $wc_wrap ); ?>">
			<h1><?php esc_html_e( 'Local SEO Settings', 'local-seo-by-ankit-rawat' ); ?></h1>
			<?php settings_errors(); ?>
			<?php $this->render_health_score(); ?>
			<?php $this->render_schema_preview(); ?>
			<form method="post" action="options.php">
				<?php
				settings_fields( Plugin::GROUP );
				$this->render_cards();
				submit_button();
				?>
			</form>
			<?php $this->render_support_section(); ?>
		</div>
		<?php
	}

	/**
	 * Render the optional support/donation section.
	 *
	 * @return void
	 */
	private function render_support_section() {
		?>
		<hr style="margin-top:2em;" />
		<div class="lsar-support-section" style="max-width:520px;margin-top:1.5em;">
			<h2><?php esc_html_e( 'Liked this plugin?', 'local-seo-by-ankit-rawat' ); ?></h2>
			<p><?php esc_html_e( 'If Local SEO By Ankit Rawat is helping your website, you can support its continued development.', 'local-seo-by-ankit-rawat' ); ?></p>
			<p>
				<a href="https://razorpay.me/@hridyaa" target="_blank" rel="noopener noreferrer"><img src="<?php echo esc_url( plugin_dir_url( LOCAL_SEO_BY_ANKIT_RAWAT_FILE ) . 'assets/beer-button.png' ); ?>" alt="<?php esc_attr_e( 'Buy me a beer', 'local-seo-by-ankit-rawat' ); ?>" style="max-width:220px;height:auto;" /></a>
			</p>
			<p class="description"><?php esc_html_e( 'This is completely voluntary and does not affect plugin functionality. Payment is processed securely by Razorpay.', 'local-seo-by-ankit-rawat' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render the Schema Health score card.
	 *
	 * @return void
	 */
	private function render_health_score() {
		$settings = get_option( Plugin::OPTION, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings = array_merge( self::defaults(), $settings );

		$result = HealthScore::calculate( $settings );
		$score  = $result['score'];
		$total  = $result['total'];
		$status = $result['status'];
		$checks = $result['checks'];
		$recs   = $result['recommendations'];

		$schema_disabled = empty( $settings['schema_enabled'] );
		?>
		<div class="lsar-card lsar-health-card">
			<div class="lsar-card-header">
				<h2><?php esc_html_e( 'Schema Health', 'local-seo-by-ankit-rawat' ); ?></h2>
				<p><?php esc_html_e( 'How complete is the information you\'ve provided for your structured data.', 'local-seo-by-ankit-rawat' ); ?></p>
			</div>
			<div class="lsar-card-body">
				<div class="lsar-health-summary">
					<span class="lsar-health-score"><?php echo esc_html( $score . '/' . $total ); ?></span>
					<span class="lsar-health-status"><?php echo esc_html( $status ); ?></span>
				</div>

				<?php if ( $schema_disabled ) : ?>
					<p class="lsar-health-notice">
						<?php esc_html_e( 'Structured data is currently disabled. Your business information is saved but not visible to search engines.', 'local-seo-by-ankit-rawat' ); ?>
					</p>
				<?php endif; ?>

				<ul class="lsar-health-checks">
					<?php foreach ( $checks as $check ) : ?>
						<li class="<?php echo $check['passed'] ? 'lsar-check-pass' : 'lsar-check-fail'; ?>">
							<span class="lsar-check-icon" aria-hidden="true"><?php echo $check['passed'] ? '&#10003;' : '&#10007;'; ?></span>
							<?php echo esc_html( $check['label'] ); ?>
						</li>
					<?php endforeach; ?>
				</ul>

				<?php if ( ! empty( $recs ) ) : ?>
					<div class="lsar-health-recommendations">
						<p class="lsar-health-rec-heading"><strong><?php esc_html_e( 'To improve your score:', 'local-seo-by-ankit-rawat' ); ?></strong></p>
						<ul>
							<?php foreach ( $recs as $rec ) : ?>
								<li><?php echo esc_html( $rec ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>

				<?php $this->render_conflict_notice(); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render a notice when other SEO plugins are detected.
	 *
	 * @return void
	 */
	private function render_conflict_notice() {
		$result = SchemaConflict::detect();
		if ( ! $result['detected'] ) {
			return;
		}

		$plugins = $result['plugins'];
		?>
		<div class="lsar-conflict-notice" role="alert">
			<p class="lsar-conflict-heading">
				<strong><?php esc_html_e( 'Other SEO plugins detected', 'local-seo-by-ankit-rawat' ); ?></strong>
			</p>
			<ul class="lsar-conflict-list">
				<?php foreach ( $plugins as $plugin ) : ?>
					<li><?php echo esc_html( $plugin['name'] ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p class="lsar-conflict-desc">
				<?php esc_html_e( 'Another SEO plugin may also output structured data. Review your schema settings in each plugin to avoid duplicate markup on your site.', 'local-seo-by-ankit-rawat' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the Schema Preview card.
	 *
	 * @return void
	 */
	private function render_schema_preview() {
		$settings = get_option( Plugin::OPTION, array() );
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}
		$settings = array_merge( self::defaults(), $settings );

		$schema_disabled = empty( $settings['schema_enabled'] );
		?>
		<div class="lsar-card lsar-preview-card">
			<div class="lsar-card-header">
				<h2><?php esc_html_e( 'Schema Preview', 'local-seo-by-ankit-rawat' ); ?></h2>
				<p><?php esc_html_e( 'The structured data this plugin outputs on your website.', 'local-seo-by-ankit-rawat' ); ?></p>
			</div>
			<div class="lsar-card-body">
				<?php if ( $schema_disabled ) : ?>
					<p class="lsar-health-notice">
						<?php esc_html_e( 'Structured data output is disabled. Enable it above to preview what search engines will see.', 'local-seo-by-ankit-rawat' ); ?>
					</p>
				<?php else : ?>
					<?php
					$local  = new \AnkitRawat\LocalSEO\Schema\LocalBusiness();
					$schema = $local->build( $settings, home_url( '/' ) );

					if ( ! empty( $schema ) ) :
						$json = wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
					?>
					<details>
						<summary><?php esc_html_e( 'View JSON-LD', 'local-seo-by-ankit-rawat' ); ?></summary>
						<pre class="lsar-preview-json" id="lsar-schema-json" tabindex="0"><code><?php echo esc_html( $json ); ?></code></pre>
						<div class="lsar-preview-actions">
							<button type="button" id="lsar-copy-json" class="button">
								<?php esc_html_e( 'Copy JSON', 'local-seo-by-ankit-rawat' ); ?>
							</button>
							<span id="lsar-copy-feedback" aria-live="polite"></span>
							<a href="<?php echo esc_url( 'https://search.google.com/test/rich-results?url=' . rawurlencode( home_url( '/' ) ) ); ?>"
							   target="_blank"
							   rel="noopener noreferrer"
							   class="lsar-preview-test-link">
								<?php esc_html_e( 'Test with Google Rich Results', 'local-seo-by-ankit-rawat' ); ?> &rarr;
							</a>
						</div>
						<p class="description lsar-preview-test-desc">
							<?php esc_html_e( 'Opens Google\'s Rich Results Test in a new tab. Your site must be publicly accessible for Google to fetch it.', 'local-seo-by-ankit-rawat' ); ?>
						</p>
						<?php
						$type              = isset( $settings['business_type'] ) ? $settings['business_type'] : '';
						$wc_product_schema = ! empty( $settings['woocommerce_product_schema'] );
						if ( 'Store' === $type && $wc_product_schema && \AnkitRawat\LocalSEO\Schema\WooCommerce::is_active() ) :
						?>
						<p class="description lsar-preview-store-note">
							<?php esc_html_e( 'Note: The Store product catalog (hasOfferCatalog) appears only on the front page, home, and shop pages — it is not shown in this preview.', 'local-seo-by-ankit-rawat' ); ?>
						</p>
						<?php endif; ?>
					</details>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the card-based settings layout.
	 *
	 * @return void
	 */
	private function render_cards() {
		echo '<div class="lsar-card">';
		echo '<div class="lsar-card-header">';
		echo '<h2>' . esc_html__( 'Your business information', 'local-seo-by-ankit-rawat' ) . '</h2>';
		echo '<p>' . esc_html__( 'Enter your business details below. This information is used to create structured data that search engines like Google use for rich results. Empty fields are simply skipped.', 'local-seo-by-ankit-rawat' ) . '</p>';
		echo '</div>';
		echo '<div class="lsar-card-body">';
		$this->render_card_field( 'schema_enabled' );
		$this->render_card_field( 'business_type' );
		$this->render_card_field( 'business_name' );
		$this->render_card_field( 'price_range' );
		echo '</div></div>';

		echo '<div class="lsar-card">';
		echo '<div class="lsar-card-header">';
		echo '<h2>' . esc_html__( 'Location', 'local-seo-by-ankit-rawat' ) . '</h2>';
		echo '<p>' . esc_html__( 'Where customers can find your business.', 'local-seo-by-ankit-rawat' ) . '</p>';
		echo '</div>';
		echo '<div class="lsar-card-body">';
		$this->render_card_field( 'street_address' );
		$this->render_card_field( 'locality' );
		$this->render_card_field( 'region' );
		$this->render_card_field( 'postal_code' );
		$this->render_card_field( 'country' );
		$this->render_card_field( 'latitude' );
		$this->render_card_field( 'longitude' );
		echo '</div></div>';

		echo '<div class="lsar-card">';
		echo '<div class="lsar-card-header">';
		echo '<h2>' . esc_html__( 'Contact & online presence', 'local-seo-by-ankit-rawat' ) . '</h2>';
		echo '<p>' . esc_html__( 'How customers reach and follow you online.', 'local-seo-by-ankit-rawat' ) . '</p>';
		echo '</div>';
		echo '<div class="lsar-card-body">';
		$this->render_card_field( 'phone' );
		$this->render_card_field( 'logo' );
		$this->render_card_field( 'images' );
		$this->render_card_field( 'social_profiles' );
		$this->render_card_field( 'place_id' );
		echo '</div></div>';

		echo '<div class="lsar-card">';
		echo '<div class="lsar-card-header">';
		echo '<h2>' . esc_html__( 'WooCommerce product data', 'local-seo-by-ankit-rawat' ) . '</h2>';
		echo '</div>';
		echo '<div class="lsar-card-body">';
		if ( ! WooCommerce::is_active() ) {
			echo '<p>' . esc_html__( 'WooCommerce is not active. Product schema settings are hidden until WooCommerce is installed and activated.', 'local-seo-by-ankit-rawat' ) . '</p>';
		} else {
			echo '<p>' . esc_html__( 'When enabled, Product JSON-LD is printed on WooCommerce product pages for any business type.', 'local-seo-by-ankit-rawat' ) . '</p>';
			echo '<p>' . esc_html__( 'If the business type is Store, up to five published products are also listed in a Schema.org OfferCatalog (hasOfferCatalog with ListItem entries) on the front page, the posts index (home), and the WooCommerce shop. The catalog is not added on every URL. Customize placement with the local_seo_by_ankit_rawat_embed_store_catalog filter.', 'local-seo-by-ankit-rawat' ) . '</p>';
			$this->render_card_field( 'woocommerce_product_schema' );
			$this->render_card_field( 'woocommerce_currency' );
		}
		echo '</div></div>';
	}

	/**
	 * Render one field inside a card.
	 *
	 * @param string $id Field id.
	 * @return void
	 */
	private function render_card_field( $id ) {
		if ( ! isset( $this->field_config[ $id ] ) ) {
			return;
		}

		$config    = $this->field_config[ $id ];
		$label_for = isset( $config['extra']['label_for'] ) ? $config['extra']['label_for'] : 'lsar-' . $id;

		$args = array_merge(
			array(
				'id'        => $id,
				'type'      => $config['type'],
				'label_for' => $label_for,
			),
			$config['extra']
		);

		echo '<div class="lsar-field">';
		if ( 'checkbox' !== $config['type'] ) {
			echo '<label for="' . esc_attr( $label_for ) . '">' . esc_html( $config['label'] ) . '</label>';
		}
		$this->render_field( $args );
		echo '</div>';
	}

	/**
	 * Render one field.
	 *
	 * @param array<string, mixed> $args Field args.
	 * @return void
	 */
	public function render_field( array $args ) {
		$settings = Plugin::settings();
		$id       = isset( $args['id'] ) ? (string) $args['id'] : '';
		$type     = isset( $args['type'] ) ? (string) $args['type'] : 'text';
		$name     = Plugin::OPTION . '[' . $id . ']';
		$value    = isset( $settings[ $id ] ) ? $settings[ $id ] : '';

		if ( 'checkbox' === $type ) {
			if ( 'woocommerce_product_schema' === $id ) {
				printf(
					'<input type="hidden" name="%s" value="0" />',
					esc_attr( $name )
				);
			}
			printf(
				'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s /> %4$s</label>',
				esc_attr( 'lsar-' . $id ),
				esc_attr( $name ),
				checked( ! empty( $value ), true, false ),
				esc_html( $this->checkbox_label( $id ) )
			);
			if ( 'schema_enabled' === $id ) {
				echo '<p class="description">' . esc_html__( 'When enabled, your business details are added to every page so search engines can show rich results.', 'local-seo-by-ankit-rawat' ) . '</p>';
			}
			if ( 'woocommerce_product_schema' === $id ) {
				echo '<p class="description">' . esc_html__( 'This control is independent of business type. Store OfferCatalog output still requires type Store and only appears on the front page, home, and shop by default.', 'local-seo-by-ankit-rawat' ) . '</p>';
			}
			return;
		}

		if ( 'business_type' === $type ) {
			echo '<select id="lsar-business-type" name="' . esc_attr( $name ) . '">';
			foreach ( Sanitize::business_types() as $type_key ) {
				printf(
					'<option value="%1$s" %2$s>%3$s</option>',
					esc_attr( $type_key ),
					selected( $value, $type_key, false ),
					esc_html( $this->type_label( $type_key ) )
				);
			}
			echo '</select>';
			return;
		}

		if ( 'textarea' === $type ) {
			$text = is_array( $value ) ? implode( "\n", $value ) : (string) $value;
			printf(
				'<textarea id="%1$s" name="%2$s" class="large-text" rows="3" cols="50">%3$s</textarea>',
				esc_attr( 'lsar-' . $id ),
				esc_attr( $name ),
				esc_textarea( $text )
			);
			$this->render_description( $id );
			return;
		}

		if ( 'logo' === $id ) {
			$url      = is_scalar( $value ) ? (string) $value : '';
			$has_logo = '' !== $url;

			printf(
				'<div class="lsar-logo-preview-wrap"%s><img id="lsar-logo-preview" src="%s" alt="" /></div>',
				$has_logo ? '' : ' style="display:none"',
				$has_logo ? esc_url( $url ) : ''
			);

			printf(
				'<input type="url" id="%1$s" name="%2$s" value="%3$s" class="regular-text" />',
				esc_attr( 'lsar-' . $id ),
				esc_attr( $name ),
				esc_attr( $url )
			);

			echo '<p class="lsar-logo-actions">';
			printf(
				'<button type="button" id="lsar-logo-choose" class="button">%s</button> ',
				esc_html__( 'Choose Image', 'local-seo-by-ankit-rawat' )
			);
			printf(
				'<button type="button" id="lsar-logo-remove" class="button-link lsar-logo-remove-btn"%s>%s</button>',
				$has_logo ? '' : ' style="display:none"',
				esc_html__( 'Remove', 'local-seo-by-ankit-rawat' )
			);
			echo '</p>';

			$this->render_description( $id );
			return;
		}

		$input_type = 'url' === $type ? 'url' : 'text';
		printf(
			'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" />',
			esc_attr( $input_type ),
			esc_attr( 'lsar-' . $id ),
			esc_attr( $name ),
			esc_attr( is_scalar( $value ) ? (string) $value : '' )
		);

		$this->render_description( $id );
	}

	/**
	 * Field descriptions.
	 *
	 * @param string $id Field id.
	 * @return void
	 */
	private function render_description( $id ) {
		$descriptions = array(
			'business_name'        => __( 'Your official business name, exactly as customers know it.', 'local-seo-by-ankit-rawat' ),
			'street_address'       => __( 'Your physical business address. Appears in search results and maps.', 'local-seo-by-ankit-rawat' ),
			'phone'                => __( 'Include country code for international visibility. Example: +91 98765 43210', 'local-seo-by-ankit-rawat' ),
			'price_range'          => __( 'Use symbols like $, $$, $$$ or a range like $10-$50. Helps customers gauge affordability.', 'local-seo-by-ankit-rawat' ),
			'logo'                 => __( 'Google displays this in Knowledge Panels. Recommended: square, at least 112x112px.', 'local-seo-by-ankit-rawat' ),
			'images'               => __( 'Photos of your business, storefront, or products. One URL per line.', 'local-seo-by-ankit-rawat' ),
			'social_profiles'      => __( 'Links to your Facebook, Instagram, LinkedIn, or YouTube. One per line.', 'local-seo-by-ankit-rawat' ),
			'latitude'             => __( 'Find your coordinates on Google Maps (right-click, copy coordinates). Decimal format.', 'local-seo-by-ankit-rawat' ),
			'longitude'            => __( 'Second number from the Google Maps coordinates.', 'local-seo-by-ankit-rawat' ),
			'place_id'             => __( 'Optional. For your records only — not included in search results.', 'local-seo-by-ankit-rawat' ),
			'country'              => __( 'Country name as you want it shown (e.g. India, United States, UK).', 'local-seo-by-ankit-rawat' ),
			'woocommerce_currency' => __( 'Optional ISO 4217 code (for example USD). Leave blank to use the WooCommerce store currency.', 'local-seo-by-ankit-rawat' ),
		);

		if ( isset( $descriptions[ $id ] ) ) {
			echo '<p class="description">' . esc_html( $descriptions[ $id ] ) . '</p>';
		}
	}

	/**
	 * Checkbox helper text.
	 *
	 * @param string $id Field id.
	 * @return string
	 */
	private function checkbox_label( $id ) {
		if ( 'woocommerce_product_schema' === $id ) {
			return __( 'Output product JSON-LD', 'local-seo-by-ankit-rawat' );
		}

		return __( 'Enable structured data in your site\'s HTML', 'local-seo-by-ankit-rawat' );
	}

	/**
	 * Human-readable business type label.
	 *
	 * @param string $type Schema type.
	 * @return string
	 */
	private function type_label( $type ) {
		$labels = array(
			'LocalBusiness'       => __( 'Local business', 'local-seo-by-ankit-rawat' ),
			'Restaurant'          => __( 'Restaurant', 'local-seo-by-ankit-rawat' ),
			'Hotel'               => __( 'Hotel', 'local-seo-by-ankit-rawat' ),
			'ProfessionalService' => __( 'Professional service', 'local-seo-by-ankit-rawat' ),
			'Store'               => __( 'Store', 'local-seo-by-ankit-rawat' ),
		);

		return isset( $labels[ $type ] ) ? $labels[ $type ] : $type;
	}

	/**
	 * Sanitize the option array.
	 *
	 * @param mixed $input Raw submitted option.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$defaults = self::defaults();
		$clean    = $defaults;

		$clean['schema_enabled']       = Sanitize::bool( isset( $input['schema_enabled'] ) ? $input['schema_enabled'] : false );
		$clean['business_type']        = Sanitize::business_type( isset( $input['business_type'] ) ? $input['business_type'] : 'LocalBusiness' );
		$clean['business_name']        = Sanitize::text( isset( $input['business_name'] ) ? $input['business_name'] : '' );
		$clean['street_address']       = Sanitize::text( isset( $input['street_address'] ) ? $input['street_address'] : '' );
		$clean['locality']             = Sanitize::text( isset( $input['locality'] ) ? $input['locality'] : '' );
		$clean['region']               = Sanitize::text( isset( $input['region'] ) ? $input['region'] : '' );
		$clean['postal_code']          = Sanitize::text( isset( $input['postal_code'] ) ? $input['postal_code'] : '' );
		$clean['country']              = Sanitize::text( isset( $input['country'] ) ? $input['country'] : '' );
		$clean['phone']                = Sanitize::phone( isset( $input['phone'] ) ? $input['phone'] : '' );
		$clean['price_range']          = Sanitize::price_range( isset( $input['price_range'] ) ? $input['price_range'] : '' );
		$clean['logo']                 = Sanitize::http_url( isset( $input['logo'] ) ? $input['logo'] : '' );
		$clean['images']               = Sanitize::url_list( isset( $input['images'] ) ? $input['images'] : array() );
		$clean['social_profiles']      = Sanitize::url_list( isset( $input['social_profiles'] ) ? $input['social_profiles'] : array() );
		$clean['latitude']             = Sanitize::latitude( isset( $input['latitude'] ) ? $input['latitude'] : '' );
		$clean['longitude']            = Sanitize::longitude( isset( $input['longitude'] ) ? $input['longitude'] : '' );
		$clean['place_id']             = Sanitize::text( isset( $input['place_id'] ) ? $input['place_id'] : '' );
		$clean['woocommerce_currency'] = Sanitize::currency( isset( $input['woocommerce_currency'] ) ? $input['woocommerce_currency'] : '' );

		$current = get_option( Plugin::OPTION, array() );
		if ( ! is_array( $current ) ) {
			$current = array();
		}

		/*
		 * WooCommerce product schema may be omitted from POST when the field is
		 * not shown. Absent key ≠ unchecked. Explicit 0/1 (hidden+checkbox) is required to change it.
		 */
		if ( array_key_exists( 'woocommerce_product_schema', $input ) ) {
			$clean['woocommerce_product_schema'] = Sanitize::bool( $input['woocommerce_product_schema'] );
		} elseif ( array_key_exists( 'woocommerce_product_schema', $current ) ) {
			$clean['woocommerce_product_schema'] = Sanitize::bool( $current['woocommerce_product_schema'] );
		} else {
			$clean['woocommerce_product_schema'] = false;
		}

		if ( isset( $current['legacy_aggregate_rating'] ) ) {
			$clean['legacy_aggregate_rating'] = Sanitize::text( $current['legacy_aggregate_rating'] );
		}
		if ( isset( $current['legacy_review_count'] ) ) {
			$clean['legacy_review_count'] = Sanitize::text( $current['legacy_review_count'] );
		}

		if ( isset( $input['legacy_aggregate_rating'] ) ) {
			$clean['legacy_aggregate_rating'] = Sanitize::text( $input['legacy_aggregate_rating'] );
		}
		if ( isset( $input['legacy_review_count'] ) ) {
			$clean['legacy_review_count'] = Sanitize::text( $input['legacy_review_count'] );
		}

		self::report_validation( $input, $clean );

		return $clean;
	}

	/**
	 * Report validation errors for values the sanitizer rejected.
	 *
	 * @param array<string, mixed> $input Raw submitted values.
	 * @param array<string, mixed> $clean Sanitized values.
	 * @return void
	 */
	private static function report_validation( array $input, array $clean ) {
		$raw_logo = isset( $input['logo'] ) && is_scalar( $input['logo'] ) ? trim( (string) $input['logo'] ) : '';
		if ( '' !== $raw_logo && '' === $clean['logo'] ) {
			add_settings_error(
				Plugin::OPTION,
				'invalid_logo',
				__( 'Business logo: Please enter a valid HTTP or HTTPS URL.', 'local-seo-by-ankit-rawat' ),
				'error'
			);
		}

		$raw_lat = isset( $input['latitude'] ) && is_scalar( $input['latitude'] ) ? trim( (string) $input['latitude'] ) : '';
		if ( '' !== $raw_lat && '' === $clean['latitude'] ) {
			add_settings_error(
				Plugin::OPTION,
				'invalid_latitude',
				__( 'Latitude: Please enter a number between -90 and 90.', 'local-seo-by-ankit-rawat' ),
				'error'
			);
		}

		$raw_lng = isset( $input['longitude'] ) && is_scalar( $input['longitude'] ) ? trim( (string) $input['longitude'] ) : '';
		if ( '' !== $raw_lng && '' === $clean['longitude'] ) {
			add_settings_error(
				Plugin::OPTION,
				'invalid_longitude',
				__( 'Longitude: Please enter a number between -180 and 180.', 'local-seo-by-ankit-rawat' ),
				'error'
			);
		}

		$raw_cur = isset( $input['woocommerce_currency'] ) && is_scalar( $input['woocommerce_currency'] ) ? trim( (string) $input['woocommerce_currency'] ) : '';
		if ( '' !== $raw_cur && '' === $clean['woocommerce_currency'] ) {
			add_settings_error(
				Plugin::OPTION,
				'invalid_currency',
				__( 'Currency override: Please enter a valid three-letter currency code (for example USD).', 'local-seo-by-ankit-rawat' ),
				'error'
			);
		}

		$raw_phone = isset( $input['phone'] ) && is_scalar( $input['phone'] ) ? trim( (string) $input['phone'] ) : '';
		if ( '' !== $raw_phone && '' === $clean['phone'] ) {
			add_settings_error(
				Plugin::OPTION,
				'invalid_phone',
				__( 'Phone number: Please enter a valid phone number.', 'local-seo-by-ankit-rawat' ),
				'error'
			);
		}

		self::report_url_list_rejection(
			isset( $input['images'] ) ? $input['images'] : '',
			$clean['images'],
			'invalid_images',
			__( 'Additional photos: One or more entries were not valid HTTP or HTTPS URLs and were removed.', 'local-seo-by-ankit-rawat' )
		);

		self::report_url_list_rejection(
			isset( $input['social_profiles'] ) ? $input['social_profiles'] : '',
			$clean['social_profiles'],
			'invalid_social',
			__( 'Social media links: One or more entries were not valid HTTP or HTTPS URLs and were removed.', 'local-seo-by-ankit-rawat' )
		);
	}

	/**
	 * Report a validation error when a URL list lost entries.
	 *
	 * @param mixed        $raw     Raw submitted value.
	 * @param array<int,string> $clean   Sanitized URL array.
	 * @param string       $code    Error code slug.
	 * @param string       $message Translated error message.
	 * @return void
	 */
	private static function report_url_list_rejection( $raw, array $clean, $code, $message ) {
		$raw_count = self::count_raw_items( $raw );
		if ( $raw_count > 0 && count( $clean ) < $raw_count ) {
			add_settings_error( Plugin::OPTION, $code, $message, 'error' );
		}
	}

	/**
	 * Count non-empty items in a raw URL field value.
	 *
	 * @param mixed $value Raw value (string or array).
	 * @return int
	 */
	private static function count_raw_items( $value ) {
		if ( is_array( $value ) ) {
			$items = $value;
		} elseif ( is_scalar( $value ) ) {
			$str = trim( (string) $value );
			if ( '' === $str ) {
				return 0;
			}
			$items = preg_split( '/\s*,\s*/', $str );
			if ( ! is_array( $items ) ) {
				return 0;
			}
		} else {
			return 0;
		}

		$count = 0;
		foreach ( $items as $item ) {
			if ( is_scalar( $item ) && '' !== trim( (string) $item ) ) {
				++$count;
			}
		}
		return $count;
	}
}
