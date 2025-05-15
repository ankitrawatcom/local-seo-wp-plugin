<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Clear schema cache when settings are updated
add_action('update_option_local_seo_enable_schema', 'local_seo_clear_schema_cache');
function local_seo_clear_schema_cache() {
    delete_transient(LOCAL_SEO_TRANSIENT_NAME);
}