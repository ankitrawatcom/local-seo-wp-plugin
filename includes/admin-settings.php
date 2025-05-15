<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Enqueue admin scripts and styles
add_action('admin_enqueue_scripts', 'local_seo_enqueue_admin_scripts');
function local_seo_enqueue_admin_scripts($hook) {
    if ($hook !== 'toplevel_page_local-seo-settings') {
        return;
    }
    wp_enqueue_script('local-seo-admin', LOCAL_SEO_PLUGIN_URL . 'assets/admin.js', ['jquery'], LOCAL_SEO_PLUGIN_VERSION, true);
    wp_enqueue_style('local-seo-admin', LOCAL_SEO_PLUGIN_URL . 'assets/admin.css', [], LOCAL_SEO_PLUGIN_VERSION);
}

// Add settings page to WordPress admin menu
add_action('admin_menu', 'local_seo_add_menu');
function local_seo_add_menu() {
    add_menu_page(
        __('Local SEO By Ankit Rawat', 'local-seo'),
        __('Local SEO By Ankit Rawat', 'local-seo'),
        'manage_options',
        'local-seo-settings',
        'local_seo_render_settings_page',
        'dashicons-location-alt',
        100
    );
}

// Render settings page
function local_seo_render_settings_page() {
    ?>
    <div class="wrap local-seo-settings">
        <h1><?php esc_html_e('Local SEO Settings', 'local-seo'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields(LOCAL_SEO_OPTION_GROUP);
            do_settings_sections('local-seo-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings and fields
add_action('admin_init', 'local_seo_register_settings');
function local_seo_register_settings() {
    // Register settings
    register_setting(LOCAL_SEO_OPTION_GROUP, 'local_seo_enable_schema');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'business_type');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'business_name');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'street_address');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'locality');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'region');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'postal_code');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'country');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'phone');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'price_range');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'business_logo');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'business_images');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'social_profiles');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'latitude');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'longitude');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'google_my_business_api_key');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'google_my_business_place_id');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'aggregate_rating');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'review_count');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'woocommerce_product_schema');
    register_setting(LOCAL_SEO_OPTION_GROUP, 'woocommerce_price_currency');

    // Add settings sections and fields
    add_settings_section('local_seo_settings_section', __('Local SEO Settings', 'local-seo'), null, 'local-seo-settings');

    // Enable Schema checkbox
    add_settings_field(
        'local_seo_enable_schema',
        __('Enable Schema', 'local-seo'),
        'local_seo_render_enable_schema_field',
        'local-seo-settings',
        'local_seo_settings_section'
    );

    // Business Type dropdown
    add_settings_field(
        'business_type',
        __('Business Type', 'local-seo'),
        'local_seo_render_business_type_dropdown',
        'local-seo-settings',
        'local_seo_settings_section'
    );

    // Common fields
    $fields = [
        'business_name'  => __('Business Name', 'local-seo'),
        'street_address' => __('Street Address', 'local-seo'),
        'locality'       => __('City', 'local-seo'),
        'region'         => __('State/Region', 'local-seo'),
        'postal_code'    => __('Postal Code', 'local-seo'),
        'phone'          => __('Phone Number', 'local-seo'),
        'price_range'    => __('Price Range', 'local-seo'),
        'business_logo'  => __('Business Logo URL', 'local-seo'),
        'business_images'=> __('Business Images (comma-separated)', 'local-seo'),
        'social_profiles'=> __('Social Profiles (comma-separated)', 'local-seo'),
        'latitude'       => __('Latitude', 'local-seo'),
        'longitude'      => __('Longitude', 'local-seo'),
        'google_my_business_api_key' => __('Google My Business API Key', 'local-seo'),
        'google_my_business_place_id' => __('Google My Business Place ID', 'local-seo'),
        'aggregate_rating' => __('Aggregate Rating', 'local-seo'),
        'review_count'   => __('Review Count', 'local-seo'),
    ];

    foreach ($fields as $field => $label) {
        add_settings_field(
            $field,
            $label,
            'local_seo_render_standard_field',
            'local-seo-settings',
            'local_seo_settings_section',
            ['id' => $field]
        );
    }

    // Country text input
    add_settings_field(
        'country',
        __('Country', 'local-seo'),
        'local_seo_render_country_input',
        'local-seo-settings',
        'local_seo_settings_section'
    );

    // Business Type Specific Fields
    add_settings_section('business_type_specific_section', __('Business Type Specific Fields', 'local-seo'), null, 'local-seo-settings');

    // Restaurant-specific fields
    add_settings_field(
        'menu_url',
        __('Menu URL', 'local-seo'),
        'local_seo_render_standard_field',
        'local-seo-settings',
        'business_type_specific_section',
        ['id' => 'menu_url', 'class' => 'Restaurant-field business-specific-field']
    );
    add_settings_field(
        'cuisine_type',
        __('Cuisine Type', 'local-seo'),
        'local_seo_render_standard_field',
        'local-seo-settings',
        'business_type_specific_section',
        ['id' => 'cuisine_type', 'class' => 'Restaurant-field business-specific-field']
    );

    // Hotel-specific fields
    add_settings_field(
        'checkin_time',
        __('Check-in Time', 'local-seo'),
        'local_seo_render_standard_field',
        'local-seo-settings',
        'business_type_specific_section',
        ['id' => 'checkin_time', 'class' => 'Hotel-field business-specific-field']
    );
    add_settings_field(
        'checkout_time',
        __('Checkout Time', 'local-seo'),
        'local_seo_render_standard_field',
        'local-seo-settings',
        'business_type_specific_section',
        ['id' => 'checkout_time', 'class' => 'Hotel-field business-specific-field']
    );
    add_settings_field(
        'star_rating',
        __('Star Rating', 'local-seo'),
        'local_seo_render_standard_field',
        'local-seo-settings',
        'business_type_specific_section',
        ['id' => 'star_rating', 'class' => 'Hotel-field business-specific-field']
    );

    // Professional Service-specific fields
    add_settings_field(
        'services_offered',
        __('Services Offered', 'local-seo'),
        'local_seo_render_standard_field',
        'local-seo-settings',
        'business_type_specific_section',
        ['id' => 'services_offered', 'class' => 'ProfessionalService-field business-specific-field']
    );
    add_settings_field(
        'area_served',
        __('Area Served', 'local-seo'),
        'local_seo_render_standard_field',
        'local-seo-settings',
        'business_type_specific_section',
        ['id' => 'area_served', 'class' => 'ProfessionalService-field business-specific-field']
    );

    // WooCommerce-specific fields
    add_settings_field(
        'woocommerce_product_schema',
        __('Enable Product Schema', 'local-seo'),
        'local_seo_render_woocommerce_product_schema_field',
        'local-seo-settings',
        'business_type_specific_section',
        ['id' => 'woocommerce_product_schema', 'class' => 'Store-field business-specific-field']
    );
    add_settings_field(
        'woocommerce_price_currency',
        __('Price Currency', 'local-seo'),
        'local_seo_render_standard_field',
        'local-seo-settings',
        'business_type_specific_section',
        ['id' => 'woocommerce_price_currency', 'class' => 'Store-field business-specific-field']
    );
}

// Render "Enable Schema" checkbox field
function local_seo_render_enable_schema_field() {
    $value = get_option('local_seo_enable_schema', false);
    ?>
    <input type="checkbox" id="local_seo_enable_schema" name="local_seo_enable_schema" value="1" <?php checked($value, 1); ?> />
    <label for="local_seo_enable_schema"><?php esc_html_e('Enable Schema Markup', 'local-seo'); ?></label>
    <?php
}

// Render standard input field for settings
function local_seo_render_standard_field($args) {
    $value = get_option($args['id'], '');
    ?>
    <input type="text" id="<?php echo esc_attr($args['id']); ?>" name="<?php echo esc_attr($args['id']); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text <?php echo esc_attr($args['class'] ?? ''); ?>" />
    <?php
}

// Render dropdown field for "Business Type"
function local_seo_render_business_type_dropdown() {
    $value = get_option('business_type', 'LocalBusiness');
    $options = [
        'LocalBusiness'       => __('Local Business', 'local-seo'),
        'Restaurant'          => __('Restaurant', 'local-seo'),
        'Hotel'               => __('Hotel', 'local-seo'),
        'ProfessionalService' => __('Professional Service', 'local-seo'),
        'Store'               => __('Store', 'local-seo'),
    ];
    ?>
    <select id="business_type" name="business_type">
        <?php foreach ($options as $key => $label) : ?>
            <option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
                <?php echo esc_html($label); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

// Render text input field for "Country"
function local_seo_render_country_input() {
    $value = get_option('country', 'United States');
    ?>
    <input type="text" id="country" name="country" value="<?php echo esc_attr($value); ?>" class="regular-text" />
    <p class="description"><?php esc_html_e('Enter the name of the country where your business is located.', 'local-seo'); ?></p>
    <?php
}

// Render WooCommerce product schema checkbox field
function local_seo_render_woocommerce_product_schema_field() {
    $value = get_option('woocommerce_product_schema', false);
    ?>
    <input type="checkbox" id="woocommerce_product_schema" name="woocommerce_product_schema" value="1" <?php checked($value, 1); ?> />
    <label for="woocommerce_product_schema"><?php esc_html_e('Enable Product Schema for WooCommerce', 'local-seo'); ?></label>
    <?php
}