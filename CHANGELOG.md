# Changelog

All notable changes to Local SEO By Ankit Rawat are documented here.

## 4.0.0 — 2026-08-17

Major release. Existing 3.3 configuration is migrated into a namespaced option. Generic 3.3 option keys are **not** deleted.

### Architecture

* Namespaced PHP (`AnkitRawat\LocalSEO`) under `src/`.
* Single option: `local_seo_by_ankit_rawat_options`.
* Text domain: `local-seo-by-ankit-rawat`.
* Version constant aligned with the plugin header (`4.0.0`).
* Standard `Requires at least` header (WordPress 6.0, PHP 7.4 — same floor as 3.3; 4.0 does not require newer APIs).

### Security

* Settings API `sanitize_callback` with allowlisted `@type`, HTTP(S)-only URLs, geo ranges, and checkbox off-state.
* JSON-LD encoded with `JSON_HEX_TAG` (and related hex flags) so `</script>` in product HTML cannot break out of the script tag.
* Unused Google My Business API key is not copied into the new option and is not shown in the admin UI.
* Settings page checks `manage_options` on render and asset enqueue.

### Schema

* Placeholder NAP values (`123 Main St`, `My Business`, …) are no longer emitted.
* Empty properties and invalid geo are omitted.
* Logo, extra images, social URLs (`sameAs`), and price range are output when valid.
* Store nested products use `hasOfferCatalog` / `OfferCatalog` instead of the invalid `product` property. Limit remains 5 published products.

### WooCommerce

* Availability is detected with `class_exists( 'WooCommerce' )` and `wc_get_products()`, not `woocommerce_params`.
* Product descriptions are stripped to text before JSON encoding.
* Currency override is optional; otherwise WooCommerce store currency is used.
* Product object resolved via `global $product` or `wc_get_product()`.

### Compatibility

* Top-level admin menu slug `local-seo-settings` is unchanged.
* Restaurant/Hotel/Professional Service extra fields that never saved in 3.3 are not presented as working features.
* Manual aggregate rating / review count are stored as legacy keys if present, not output (Google disallows self-serving review markup; 3.3 never output them either).
* Filters added so other SEO plugins can disable this plugin’s JSON-LD.

### Documentation and tooling

* Honest `readme.txt`, `LICENSE`, `uninstall.php`, `docs/ARCHITECTURE.md`, `docs/SECURITY.md`, `docs/REGRESSION-AUDIT.md`.
* PHPUnit tests (stubs; no full WordPress/WooCommerce runtime in CI by default).
* PHPCS (WordPress) and PHPStan configs.

## 3.3

* Compatibility fix (as shipped).

## 3.2

* WooCommerce product schema and split includes.

## 3.1

* Sanitization and caching tweaks.

## 3.0

* Geo fields and unused Google My Business settings fields.
