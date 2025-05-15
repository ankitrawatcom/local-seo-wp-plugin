<?php
/**
 * Plugin Name: Local SEO By Ankit Rawat
 * Description: Adds advanced Local SEO features including Reviews, Geo Coordinates, and Google My Business Integration.
 * Version: 3.3
 * Author: Ankit Rawat
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Tested up to: 6.7
 * Requires PHP: 7.4
 * Requires WP: 6.0
 * Author URI: https://ankitrawat.com
 * Plugin URI: https://ankitrawat.com/local-seo-by-ankit-rawat/
 * Text Domain: local-seo
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define plugin constants
define('LOCAL_SEO_PLUGIN_VERSION', '3.2');
define('LOCAL_SEO_OPTION_GROUP', 'local_seo_options_group');
define('LOCAL_SEO_TRANSIENT_NAME', 'local_seo_json_ld_schema');
define('LOCAL_SEO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LOCAL_SEO_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once LOCAL_SEO_PLUGIN_DIR . 'includes/helpers.php';
require_once LOCAL_SEO_PLUGIN_DIR . 'includes/admin-settings.php';
require_once LOCAL_SEO_PLUGIN_DIR . 'includes/schema-generator.php';
require_once LOCAL_SEO_PLUGIN_DIR . 'includes/woocommerce-integration.php';

// Load textdomain for translations
add_action('init', 'local_seo_load_textdomain');
function local_seo_load_textdomain() {
    load_plugin_textdomain('local-seo', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}