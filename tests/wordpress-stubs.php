<?php
/**
 * Minimal WordPress function stubs for unit tests.
 *
 * @package AnkitRawat\LocalSEO
 */

$GLOBALS['lsar_test_options']    = array();
$GLOBALS['lsar_test_transients'] = array();
$GLOBALS['lsar_test_actions']    = array();
$GLOBALS['lsar_test_filters']    = array();

function lsar_test_reset_state() {
	$GLOBALS['lsar_test_options']       = array();
	$GLOBALS['lsar_test_transients']    = array();
	$GLOBALS['lsar_test_filters']       = array();
	$GLOBALS['lsar_test_is_product']    = false;
	$GLOBALS['lsar_test_is_front_page'] = false;
	$GLOBALS['lsar_test_is_home']       = false;
	$GLOBALS['lsar_test_is_shop']       = false;
	$GLOBALS['lsar_privacy']            = '';
}

function add_action( $hook, $callback, $priority = 10, $accepted = 1 ) {
	$GLOBALS['lsar_test_actions'][ $hook ][] = $callback;
	return true;
}

function add_filter( $hook, $callback, $priority = 10, $accepted = 1 ) {
	return true;
}

function apply_filters( $hook, $value ) {
	$args = func_get_args();
	array_shift( $args );
	if ( isset( $GLOBALS['lsar_test_filters'][ $hook ] ) && is_callable( $GLOBALS['lsar_test_filters'][ $hook ] ) ) {
		return call_user_func_array( $GLOBALS['lsar_test_filters'][ $hook ], $args );
	}
	return $value;
}

function add_option( $key, $value, $deprecated = '', $autoload = 'yes' ) {
	if ( ! array_key_exists( $key, $GLOBALS['lsar_test_options'] ) ) {
		$GLOBALS['lsar_test_options'][ $key ] = $value;
		return true;
	}
	return false;
}

function update_option( $key, $value, $autoload = null ) {
	$GLOBALS['lsar_test_options'][ $key ] = $value;
	return true;
}

function get_option( $key, $default = false ) {
	if ( array_key_exists( $key, $GLOBALS['lsar_test_options'] ) ) {
		return $GLOBALS['lsar_test_options'][ $key ];
	}
	return $default;
}

function delete_option( $key ) {
	unset( $GLOBALS['lsar_test_options'][ $key ] );
	return true;
}

function set_transient( $key, $value, $ttl = 0 ) {
	$GLOBALS['lsar_test_transients'][ $key ] = $value;
	return true;
}

function get_transient( $key ) {
	if ( array_key_exists( $key, $GLOBALS['lsar_test_transients'] ) ) {
		return $GLOBALS['lsar_test_transients'][ $key ];
	}
	return false;
}

function delete_transient( $key ) {
	unset( $GLOBALS['lsar_test_transients'][ $key ] );
	return true;
}

function sanitize_text_field( $str ) {
	$str = is_scalar( $str ) ? (string) $str : '';
	$str = strip_tags( $str );
	$str = preg_replace( '/[\r\n\t ]+/', ' ', $str );
	return trim( $str );
}

function wp_strip_all_tags( $string, $remove_breaks = false ) {
	$string = is_scalar( $string ) ? (string) $string : '';
	$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
	$string = strip_tags( $string );
	if ( $remove_breaks ) {
		$string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
	}
	return trim( $string );
}

function esc_url_raw( $url, $protocols = null ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	if ( ! preg_match( '#^https?://#i', $url ) ) {
		return '';
	}
	return filter_var( $url, FILTER_SANITIZE_URL ) ?: '';
}

function esc_url( $url ) {
	return esc_url_raw( $url );
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_html__( $text, $domain = null ) {
	return $text;
}

function esc_html_e( $text, $domain = null ) {
	echo esc_html( $text );
}

function esc_attr__( $text, $domain = null ) {
	return $text;
}

function esc_textarea( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function __( $text, $domain = null ) {
	return $text;
}

function wp_json_encode( $data, $options = 0, $depth = 512 ) {
	return json_encode( $data, $options, $depth );
}

function home_url( $path = '/' ) {
	return 'https://example.test' . $path;
}

function plugin_dir_path( $file ) {
	return dirname( $file ) . DIRECTORY_SEPARATOR;
}

function plugin_dir_url( $file ) {
	return 'https://example.test/wp-content/plugins/local-seo-by-ankit-rawat/';
}

function plugin_basename( $file ) {
	return 'local-seo-by-ankit-rawat/' . basename( $file );
}

function load_plugin_textdomain( $domain, $deprecated = false, $rel = '' ) {
	return true;
}

function register_activation_hook( $file, $callback ) {}
function register_deactivation_hook( $file, $callback ) {}

function register_setting( $group, $name, $args = array() ) {}
function add_settings_section( $id, $title, $callback, $page ) {}
function add_settings_field( $id, $title, $callback, $page, $section, $args = array() ) {}
function settings_fields( $group ) {}
function do_settings_sections( $page ) {}
function submit_button() {}
function add_menu_page( $page_title, $menu_title, $cap, $slug, $callback, $icon = '', $pos = null ) {}
function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {}
function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {}
function wp_localize_script( $handle, $object_name, $l10n ) {}
function current_user_can( $cap ) {
	return ! empty( $GLOBALS['lsar_test_can'] );
}
function is_admin() {
	return ! empty( $GLOBALS['lsar_test_is_admin'] );
}
function wp_die( $message = '' ) {
	throw new RuntimeException( strip_tags( (string) $message ) );
}
function checked( $checked, $current = true, $echo = true ) {
	$result = ( (string) $checked === (string) $current ) ? ' checked="checked"' : '';
	if ( $echo ) {
		echo $result;
	}
	return $result;
}
function selected( $selected, $current = true, $echo = true ) {
	$result = ( (string) $selected === (string) $current ) ? ' selected="selected"' : '';
	if ( $echo ) {
		echo $result;
	}
	return $result;
}
function wp_get_attachment_url( $id ) {
	return $id ? 'https://example.test/image.jpg' : false;
}
function get_permalink( $id ) {
	return 'https://example.test/product/' . (int) $id . '/';
}
function get_the_ID() {
	return 1;
}
function is_product() {
	return ! empty( $GLOBALS['lsar_test_is_product'] );
}
function wc_get_products( $args = array() ) {
	return isset( $GLOBALS['lsar_test_products'] ) ? $GLOBALS['lsar_test_products'] : array();
}
function wc_get_product( $id ) {
	return null;
}
function get_woocommerce_currency() {
	return 'EUR';
}
function is_front_page() {
	return ! empty( $GLOBALS['lsar_test_is_front_page'] );
}
function is_home() {
	return ! empty( $GLOBALS['lsar_test_is_home'] );
}
function is_shop() {
	return ! empty( $GLOBALS['lsar_test_is_shop'] );
}
function settings_errors( $filter = '' ) {}
function wp_add_privacy_policy_content( $plugin_name, $policy_text ) {
	$GLOBALS['lsar_privacy'] = $policy_text;
}
function wp_kses_post( $data ) {
	return $data;
}
