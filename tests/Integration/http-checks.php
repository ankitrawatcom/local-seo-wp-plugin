<?php
/**
 * HTTP integration checks against a running WordPress site.
 *
 * Usage: php tests/Integration/http-checks.php http://127.0.0.1:9400 admin password
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$base = isset( $argv[1] ) ? rtrim( $argv[1], '/' ) : 'http://127.0.0.1:9400';
$user = isset( $argv[2] ) ? $argv[2] : 'admin';
$pass = isset( $argv[3] ) ? $argv[3] : 'password';

$cookie = sys_get_temp_dir() . '/lsar-wp-cookies.txt';
@unlink( $cookie );

function lsar_http( $url, $opts = array() ) {
	global $cookie;
	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
	curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie );
	curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookie );
	curl_setopt( $ch, CURLOPT_TIMEOUT, 120 );
	curl_setopt( $ch, CURLOPT_USERAGENT, 'LSAR-Stage7/1.0' );
	if ( ! empty( $opts['post'] ) ) {
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $opts['post'] );
	}
	$body = curl_exec( $ch );
	$code = (int) curl_getinfo( $ch, CURLINFO_HTTP_CODE );
	$err  = curl_error( $ch );
	curl_close( $ch );
	return array( $code, is_string( $body ) ? $body : '', $err );
}

$pass_n = 0;
$fail_n = 0;
function lsar_assert( $ok, $label ) {
	global $pass_n, $fail_n;
	if ( $ok ) {
		echo "PASS $label\n";
		++$pass_n;
	} else {
		echo "FAIL $label\n";
		++$fail_n;
	}
}

list( $code, $home ) = lsar_http( $base . '/' );
lsar_assert( 200 === $code, "homepage HTTP $code" );
lsar_assert( (bool) preg_match( '/content="WordPress 7\.0\.4"/', $home ), 'WordPress generator 7.0.4' );
lsar_assert( false === stripos( $home, 'Fatal error' ), 'homepage no fatal' );
lsar_assert( false === stripos( $home, '</script><script>' ), 'homepage no raw script breakout' );

lsar_http( $base . '/wp-login.php' );
list( $code, $dash ) = lsar_http(
	$base . '/wp-login.php',
	array(
		'post' => array(
			'log'         => $user,
			'pwd'         => $pass,
			'wp-submit'   => 'Log In',
			'redirect_to' => $base . '/wp-admin/',
			'testcookie'  => '1',
		),
	)
);
lsar_assert( 200 === $code && false !== strpos( $dash, 'Dashboard' ), 'admin login' );

list( $code, $plugins ) = lsar_http( $base . '/wp-admin/plugins.php' );
lsar_assert( 200 === $code, "plugins.php HTTP $code" );
lsar_assert( false !== strpos( $plugins, 'local-seo-by-ankit-rawat' ), 'plugin listed' );
$wc_slug = (bool) preg_match( '/data-slug="woocommerce"/', $plugins );
lsar_assert( $wc_slug, 'WooCommerce plugin installed' );
$pc_slug = (bool) preg_match( '/data-slug="plugin-check"/', $plugins );
lsar_assert( $pc_slug, 'Plugin Check installed' );

$activate = null;
if ( preg_match( '#plugins\.php\?action=activate&amp;plugin=local-seo-by-ankit-rawat%2Flocal-seo-by-ankit-rawat\.php&amp;_wpnonce=([a-z0-9]+)#', $plugins, $m ) ) {
	$activate = $base . '/wp-admin/plugins.php?action=activate&plugin=local-seo-by-ankit-rawat%2Flocal-seo-by-ankit-rawat.php&_wpnonce=' . $m[1];
}
if ( $activate ) {
	list( $code, $after ) = lsar_http( $activate );
	lsar_assert( 200 === $code && ( false !== strpos( $after, 'Plugin activated' ) || false !== strpos( $after, 'Deactivate' ) ), 'activate Local SEO' );
} else {
	lsar_assert( false !== strpos( $plugins, 'lsar-deactivate' ) || false !== stripos( $plugins, 'Deactivate' ), 'Local SEO already active or activate link missing' );
}

list( $code, $settings ) = lsar_http( $base . '/wp-admin/admin.php?page=local-seo-settings' );
lsar_assert( 200 === $code, "settings page HTTP $code" );
lsar_assert( false !== strpos( $settings, 'Local SEO Settings' ), 'settings heading' );
lsar_assert( false !== strpos( $settings, 'lsar-business-type' ), 'business type field' );
lsar_assert( false !== strpos( $settings, 'id="lsar-schema_enabled"' ) || false !== strpos( $settings, 'lsar-schema_enabled' ), 'schema checkbox' );
lsar_assert( false === stripos( $settings, 'Fatal error' ), 'settings no fatal' );
lsar_assert( false !== strpos( $settings, 'local-seo-by-ankit-rawat-admin' ), 'admin assets enqueued' );

$wc_available = false !== strpos( $settings, 'lsar-woocommerce-available' );
$wc_unavail   = false !== strpos( $settings, 'lsar-woocommerce-unavailable' );
if ( $wc_slug ) {
	lsar_assert( $wc_available, 'WC wrap class available when WooCommerce installed' );
	lsar_assert( false !== strpos( $settings, 'lsar-woocommerce_product_schema' ) || false !== strpos( $settings, 'woocommerce_product_schema' ), 'product schema field in markup' );
} else {
	lsar_assert( $wc_unavail, 'WC wrap class unavailable without WooCommerce' );
}

$option_page = '';
$nonce       = '';
if ( preg_match( '/name="option_page" value="([^"]+)"/', $settings, $m ) ) {
	$option_page = $m[1];
}
if ( preg_match( '/name="_wpnonce" value="([^"]+)"/', $settings, $m ) ) {
	$nonce = $m[1];
}
lsar_assert( '' !== $nonce && 'local_seo_by_ankit_rawat_group' === $option_page, 'settings nonce + group' );

$post = array(
	'option_page'      => $option_page,
	'action'           => 'update',
	'_wpnonce'         => $nonce,
	'_wp_http_referer' => '/wp-admin/admin.php?page=local-seo-settings',
	'local_seo_by_ankit_rawat_options[schema_enabled]'              => '1',
	'local_seo_by_ankit_rawat_options[business_type]'               => 'LocalBusiness',
	'local_seo_by_ankit_rawat_options[business_name]'               => 'Harbor Test Co',
	'local_seo_by_ankit_rawat_options[street_address]'              => '1 Dock St',
	'local_seo_by_ankit_rawat_options[locality]'                    => 'Portland',
	'local_seo_by_ankit_rawat_options[region]'                      => 'ME',
	'local_seo_by_ankit_rawat_options[postal_code]'                 => '04101',
	'local_seo_by_ankit_rawat_options[country]'                     => 'US',
	'local_seo_by_ankit_rawat_options[phone]'                       => '+1 207-555-0100',
	'local_seo_by_ankit_rawat_options[price_range]'                 => '$$',
	'local_seo_by_ankit_rawat_options[logo]'                        => 'https://example.test/logo.png',
	'local_seo_by_ankit_rawat_options[images]'                      => 'https://example.test/one.jpg',
	'local_seo_by_ankit_rawat_options[social_profiles]'             => "https://instagram.com/harbor\njavascript:alert(1)",
	'local_seo_by_ankit_rawat_options[latitude]'                    => '43.6591',
	'local_seo_by_ankit_rawat_options[longitude]'                   => '-70.2568',
	'local_seo_by_ankit_rawat_options[place_id]'                    => 'ChIJtest',
	'local_seo_by_ankit_rawat_options[woocommerce_currency]'        => 'USD',
);

if ( $wc_slug ) {
	$post['local_seo_by_ankit_rawat_options[woocommerce_product_schema]'] = '0';
}

list( $code, $saved ) = lsar_http( $base . '/wp-admin/options.php', array( 'post' => $post ) );
lsar_assert( 200 === $code, "options.php save HTTP $code" );

list( $code, $front ) = lsar_http( $base . '/' );
lsar_assert( false !== strpos( $front, 'application/ld+json' ), 'JSON-LD present when enabled' );
lsar_assert( false !== strpos( $front, 'Harbor Test Co' ), 'business name in JSON-LD' );
lsar_assert( false !== strpos( $front, 'PostalAddress' ), 'address in JSON-LD' );
lsar_assert( false !== strpos( $front, 'GeoCoordinates' ), 'geo in JSON-LD' );
lsar_assert( false === strpos( $front, 'javascript:' ), 'javascript URL not saved/output' );
lsar_assert( false === stripos( $front, '</script><script>' ), 'no script breakout after save' );

if ( preg_match( '#<script type="application/ld\+json">(.*?)</script>#s', $front, $jm ) ) {
	$decoded = json_decode( $jm[1], true );
	lsar_assert( is_array( $decoded ), 'JSON-LD decodes' );
	lsar_assert( isset( $decoded['@type'] ) && 'LocalBusiness' === $decoded['@type'], '@type LocalBusiness' );
	lsar_assert( ! isset( $decoded['hasOfferCatalog'] ), 'no catalog on LocalBusiness' );
} else {
	lsar_assert( false, 'extract JSON-LD script' );
}

list( $code, $settings2 ) = lsar_http( $base . '/wp-admin/admin.php?page=local-seo-settings' );
if ( preg_match( '/name="_wpnonce" value="([^"]+)"/', $settings2, $m ) ) {
	$nonce = $m[1];
}
$post['option_page'] = 'local_seo_by_ankit_rawat_group';
$post['_wpnonce']    = $nonce;
unset( $post['local_seo_by_ankit_rawat_options[schema_enabled]'] );
$post['local_seo_by_ankit_rawat_options[business_name]'] = 'Harbor Test Co';
list( $code, $saved2 ) = lsar_http( $base . '/wp-admin/options.php', array( 'post' => $post ) );
list( $code, $front2 ) = lsar_http( $base . '/' );
lsar_assert( false === strpos( $front2, 'application/ld+json' ) || false === strpos( $front2, 'Harbor Test Co' ), 'schema disabled omits NAP JSON-LD' );

echo "\nPassed: $pass_n Failed: $fail_n\n";
exit( $fail_n > 0 ? 1 : 0 );
