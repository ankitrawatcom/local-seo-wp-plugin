# Architecture — Local SEO By Ankit Rawat 4.0.0

## Bootstrap

`local-seo-by-ankit-rawat.php` defines constants, registers a PSR-4 autoloader for `AnkitRawat\LocalSEO\`, instantiates `Plugin`, and registers activation/deactivation hooks. Composer is not required in production.

Minimum versions match 3.3: **WordPress 6.0** and **PHP 7.4**. 4.0.0 does not call APIs that need a higher WordPress floor.

## Modules

| Class | Role |
|-------|------|
| `Plugin` | Hooks, settings accessor, activation |
| `Admin\Settings` | Settings API, sanitization, page |
| `Admin\Assets` | Admin CSS/JS on this plugin’s screen only |
| `Schema\LocalBusiness` | Sitewide JSON-LD |
| `Schema\WooCommerce` | Product pages + Store catalog |
| `Schema\ProductBuilder` | Product node from normalized data |
| `Schema\JsonLd` | Safe JSON encoding / print |
| `Migration\Migrator` | 3.3 → 4.0.0, admin/activation only |
| `Support\Sanitize` | Field validation |

There is no REST API, AJAX, cron, custom table, or frontend asset.

## Settings

Single autoloaded option: `local_seo_by_ankit_rawat_options`.

Version sentinel: `local_seo_by_ankit_rawat_version` (not autoloaded).

Capability: `manage_options`. Group: `local_seo_by_ankit_rawat_group`. Page slug: `local-seo-settings` (same as 3.3).

## Schema

`wp_head` priority 10 prints LocalBusiness-family JSON-LD when `schema_enabled` is true.

Empty fields are omitted. `@type` is allowlisted. Geo requires both coordinates in range.

## WooCommerce

`wp_head` priority 20 prints Product JSON-LD on `is_product()` when enabled.

Store + product schema: `wc_get_products( status=publish, limit=5 )` attached as `hasOfferCatalog`. The limit matches 3.3 and avoids querying the full catalog on every public request.

## Caching

The 3.3 transient `local_seo_json_ld_schema` is **not** used. NAP data is one `get_option()` call. The transient went stale when any setting except the enable checkbox changed. It is deleted on migration and deactivation.

## Migration

`Migrator::maybe_run()` on `admin_init` (priority 1) and on activation. Skips when stored version is `>= 4.0.0`. Does not run on the frontend. Does not delete 3.3 options. Does not copy `google_my_business_api_key`.

## Uninstall

Deletes `local_seo_by_ankit_rawat_options`, `local_seo_by_ankit_rawat_version`, the old transient, and `local_seo_enable_schema` (unique to this plugin). Does not delete generic keys such as `phone`.

## Public hooks

| Hook | Type | Purpose |
|------|------|---------|
| `local_seo_by_ankit_rawat_settings` | filter | Settings array after merge with defaults |
| `local_seo_by_ankit_rawat_business_type` | filter | `@type` (re-allowlisted after the filter) |
| `local_seo_by_ankit_rawat_schema` | filter | Sitewide graph before encode |
| `local_seo_by_ankit_rawat_product_schema` | filter | Single Product node |
| `local_seo_by_ankit_rawat_output_local_schema` | filter | `bool` skip sitewide output |
| `local_seo_by_ankit_rawat_output_product_schema` | filter | `bool` skip product-page output |
| `local_seo_by_ankit_rawat_store_product_limit` | filter | Catalog size (clamped 1–20) |

## Testing

`tests/` uses WordPress function stubs (no wp-env required). PHPUnit 9. WooCommerce storefront output is not integration-tested unless WooCommerce is present in the environment.
