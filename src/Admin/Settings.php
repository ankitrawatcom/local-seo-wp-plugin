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
			)
		);

		add_settings_section(
			'lsar_main',
			__( 'Business details', 'local-seo-by-ankit-rawat' ),
			array( $this, 'render_main_section' ),
			Plugin::PAGE
		);

		$this->add_field( 'schema_enabled', __( 'Enable schema', 'local-seo-by-ankit-rawat' ), 'checkbox' );
		$this->add_field( 'business_type', __( 'Business type', 'local-seo-by-ankit-rawat' ), 'business_type' );
		$this->add_field( 'business_name', __( 'Business name', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'street_address', __( 'Street address', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'locality', __( 'City', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'region', __( 'State/Region', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'postal_code', __( 'Postal code', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'country', __( 'Country', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'phone', __( 'Phone number', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'price_range', __( 'Price range', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'logo', __( 'Business logo URL', 'local-seo-by-ankit-rawat' ), 'url' );
		$this->add_field( 'images', __( 'Additional image URLs', 'local-seo-by-ankit-rawat' ), 'textarea' );
		$this->add_field( 'social_profiles', __( 'Social profile URLs', 'local-seo-by-ankit-rawat' ), 'textarea' );
		$this->add_field( 'latitude', __( 'Latitude', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'longitude', __( 'Longitude', 'local-seo-by-ankit-rawat' ), 'text' );
		$this->add_field( 'place_id', __( 'Google Place ID', 'local-seo-by-ankit-rawat' ), 'text' );

		add_settings_section(
			'lsar_woocommerce',
			__( 'WooCommerce', 'local-seo-by-ankit-rawat' ),
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
		$args = array_merge(
			array(
				'id'   => $id,
				'type' => $type,
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
		echo '<p>' . esc_html__( 'These details are used only for JSON-LD structured data. Empty fields are omitted. This plugin does not connect to Google Business Profile.', 'local-seo-by-ankit-rawat' ) . '</p>';
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

		echo '<p>' . esc_html__( 'When enabled, product JSON-LD is added on product pages. If the business type is Store, up to five published products are also listed in an OfferCatalog on the sitewide schema.', 'local-seo-by-ankit-rawat' ) . '</p>';
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
		?>
		<div class="wrap lsar-settings">
			<h1><?php esc_html_e( 'Local SEO Settings', 'local-seo-by-ankit-rawat' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( Plugin::GROUP );
				do_settings_sections( Plugin::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
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
			printf(
				'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s /> %4$s</label>',
				esc_attr( 'lsar-' . $id ),
				esc_attr( $name ),
				checked( ! empty( $value ), true, false ),
				esc_html( $this->checkbox_label( $id ) )
			);
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
			echo '<p class="description">' . esc_html__( 'One URL per line, or comma-separated. Only http and https URLs are saved.', 'local-seo-by-ankit-rawat' ) . '</p>';
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
			'price_range'          => __( 'Example: $$ or $10-$40. Omitted from schema when empty.', 'local-seo-by-ankit-rawat' ),
			'latitude'             => __( 'Decimal degrees between -90 and 90. GeoCoordinates is output only when both latitude and longitude are valid.', 'local-seo-by-ankit-rawat' ),
			'longitude'            => __( 'Decimal degrees between -180 and 180.', 'local-seo-by-ankit-rawat' ),
			'place_id'             => __( 'Optional reference only. Not sent to Google and not included in JSON-LD.', 'local-seo-by-ankit-rawat' ),
			'woocommerce_currency' => __( 'Optional ISO 4217 code (for example USD). Leave blank to use the WooCommerce store currency.', 'local-seo-by-ankit-rawat' ),
			'country'              => __( 'Country name or ISO country code as you want it to appear in PostalAddress.', 'local-seo-by-ankit-rawat' ),
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

		return __( 'Output LocalBusiness JSON-LD in the site head', 'local-seo-by-ankit-rawat' );
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

		$clean['schema_enabled']             = Sanitize::bool( isset( $input['schema_enabled'] ) ? $input['schema_enabled'] : false );
		$clean['business_type']              = Sanitize::business_type( isset( $input['business_type'] ) ? $input['business_type'] : 'LocalBusiness' );
		$clean['business_name']              = Sanitize::text( isset( $input['business_name'] ) ? $input['business_name'] : '' );
		$clean['street_address']             = Sanitize::text( isset( $input['street_address'] ) ? $input['street_address'] : '' );
		$clean['locality']                   = Sanitize::text( isset( $input['locality'] ) ? $input['locality'] : '' );
		$clean['region']                     = Sanitize::text( isset( $input['region'] ) ? $input['region'] : '' );
		$clean['postal_code']                = Sanitize::text( isset( $input['postal_code'] ) ? $input['postal_code'] : '' );
		$clean['country']                    = Sanitize::text( isset( $input['country'] ) ? $input['country'] : '' );
		$clean['phone']                      = Sanitize::phone( isset( $input['phone'] ) ? $input['phone'] : '' );
		$clean['price_range']                = Sanitize::price_range( isset( $input['price_range'] ) ? $input['price_range'] : '' );
		$clean['logo']                       = Sanitize::http_url( isset( $input['logo'] ) ? $input['logo'] : '' );
		$clean['images']                     = Sanitize::url_list( isset( $input['images'] ) ? $input['images'] : array() );
		$clean['social_profiles']            = Sanitize::url_list( isset( $input['social_profiles'] ) ? $input['social_profiles'] : array() );
		$clean['latitude']                   = Sanitize::latitude( isset( $input['latitude'] ) ? $input['latitude'] : '' );
		$clean['longitude']                  = Sanitize::longitude( isset( $input['longitude'] ) ? $input['longitude'] : '' );
		$clean['place_id']                   = Sanitize::text( isset( $input['place_id'] ) ? $input['place_id'] : '' );
		$clean['woocommerce_product_schema'] = Sanitize::bool( isset( $input['woocommerce_product_schema'] ) ? $input['woocommerce_product_schema'] : false );
		$clean['woocommerce_currency']       = Sanitize::currency( isset( $input['woocommerce_currency'] ) ? $input['woocommerce_currency'] : '' );

		$current = get_option( Plugin::OPTION, array() );
		if ( is_array( $current ) ) {
			if ( isset( $current['legacy_aggregate_rating'] ) ) {
				$clean['legacy_aggregate_rating'] = Sanitize::text( $current['legacy_aggregate_rating'] );
			}
			if ( isset( $current['legacy_review_count'] ) ) {
				$clean['legacy_review_count'] = Sanitize::text( $current['legacy_review_count'] );
			}
		}

		if ( isset( $input['legacy_aggregate_rating'] ) ) {
			$clean['legacy_aggregate_rating'] = Sanitize::text( $input['legacy_aggregate_rating'] );
		}
		if ( isset( $input['legacy_review_count'] ) ) {
			$clean['legacy_review_count'] = Sanitize::text( $input['legacy_review_count'] );
		}

		return $clean;
	}
}
