<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Add WooCommerce-specific schema to product pages
add_action('wp_head', 'local_seo_add_woocommerce_product_schema', 20);
function local_seo_add_woocommerce_product_schema() {
    // Check if WooCommerce is installed and active
    if (!class_exists('WooCommerce')) {
        return;
    }

    // Check if function is_product() exists before using it
    if (!function_exists('is_product') || !is_product() || !get_option('woocommerce_product_schema', false)) {
        return;
    }

    global $product;
    if (!$product) {
        return;
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->get_name(),
        'description' => $product->get_short_description(),
        'image' => wp_get_attachment_url($product->get_image_id()),
        'sku' => $product->get_sku(),
        'offers' => [
            '@type' => 'Offer',
            'price' => $product->get_price(),
            'priceCurrency' => get_option('woocommerce_price_currency', 'USD'),
            'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url' => get_permalink($product->get_id()),
        ],
    ];

    // Output schema
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}