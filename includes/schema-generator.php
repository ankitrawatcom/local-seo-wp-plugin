<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Generate the JSON-LD schema for the selected business type
add_action('wp_head', 'local_seo_add_local_business_schema', 10);
function local_seo_add_local_business_schema() {
    if (!get_option('local_seo_enable_schema', false)) {
        return;
    }

    $schema = get_transient(LOCAL_SEO_TRANSIENT_NAME);

    if (false === $schema) {
        $business_type = get_option('business_type', 'LocalBusiness');

        // Common schema fields
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $business_type,
            'name' => sanitize_text_field(get_option('business_name', 'My Business')),
            'image' => esc_url(get_option('business_logo', '')),
            'url' => get_site_url(),
            'telephone' => sanitize_text_field(get_option('phone', '')),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => sanitize_text_field(get_option('street_address', '123 Main St')),
                'addressLocality' => sanitize_text_field(get_option('locality', 'Anytown')),
                'addressRegion' => sanitize_text_field(get_option('region', 'CA')),
                'postalCode' => sanitize_text_field(get_option('postal_code', '12345')),
                'addressCountry' => sanitize_text_field(get_option('country', 'United States')),
            ],
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => sanitize_text_field(get_option('latitude', '')),
                'longitude' => sanitize_text_field(get_option('longitude', '')),
            ],
        ];

        // Add WooCommerce product schema if enabled
        if ($business_type === 'Store' && get_option('woocommerce_product_schema', false) && class_exists('WooCommerce')) {
            $schema['@type'] = 'Store';
            $schema['product'] = local_seo_get_woocommerce_product_schema();
        }

        // Cache schema for 1 hour
        set_transient(LOCAL_SEO_TRANSIENT_NAME, $schema, HOUR_IN_SECONDS);
    }

    // Output schema
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

// Get WooCommerce product schema
function local_seo_get_woocommerce_product_schema() {
    if (!class_exists('WooCommerce')) {
        return [];
    }

    $products = wc_get_products(['limit' => 5]);
    $product_schema = [];

    foreach ($products as $product) {
        $product_schema[] = [
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
    }

    return $product_schema;
}