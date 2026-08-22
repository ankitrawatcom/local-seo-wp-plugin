<?php
/**
 * Plugin Name: LSAR disposable catalog override
 * Description: Disposable integration-test MU-plugin. Not part of Local SEO production source.
 */

add_filter( 'local_seo_by_ankit_rawat_embed_store_catalog', '__return_true' );
