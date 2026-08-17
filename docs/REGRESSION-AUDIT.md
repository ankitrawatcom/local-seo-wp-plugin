# Regression audit — 3.3 vs 4.0.0

| 3.3 feature / behavior | 4.0.0 | Why |
|------------------------|-------|-----|
| Settings API admin screen | **Preserved** | Same top-level menu slug `local-seo-settings`, capability `manage_options`. |
| Enable schema checkbox | **Preserved** (improved) | Now sanitizes; off-state saves as false. |
| Business type dropdown (5 types) | **Preserved** (improved) | Allowlisted on save and on output. |
| NAP fields | **Preserved** | Stored in `local_seo_by_ankit_rawat_options`; migrated from generic keys. |
| Geo lat/lng | **Preserved** (improved) | Range-checked; omitted when invalid. |
| Logo URL | **Preserved** (improved) | HTTP(S) only. |
| Extra images (comma-separated) | **Preserved** (improved) | Normalized to URL array; included in `image` when valid. 3.3 stored them but did not output them. |
| Social profiles | **Preserved** (improved) | Output as `sameAs` when valid. 3.3 stored them but did not output them. |
| Price range | **Preserved** (improved) | Output as `priceRange` when set. 3.3 stored it but did not output it. |
| Sitewide JSON-LD in `wp_head` | **Preserved** (changed) | No placeholders; empty nodes omitted; safer encoding. |
| WooCommerce product JSON-LD on product pages | **Preserved** (improved) | Text-stripped descriptions; `wc_get_product` fallback; currency from WC if override empty. |
| Up to five products on Store schema | **Preserved** (changed) | Still max 5 published products. Property is `hasOfferCatalog` instead of invalid `product`. |
| 1-hour schema transient | **Removed** | Cache ignored most setting changes and served stale product prices. Rebuild cost is one option read (+ up to 5 products for Store). |
| Google My Business API key field | **Removed** from UI | Never used. Not migrated into the new option. Old DB row kept. |
| Google Place ID field | **Preserved** as optional notes field | Not output in JSON-LD; no API calls. |
| Aggregate rating / review count fields | **Deprecated** | Migrated to `legacy_*` keys, not shown, not output (never were). |
| Restaurant menu/cuisine UI | **Removed** | Never `register_setting`’d; never saved; never in schema. |
| Hotel check-in/out/star UI | **Removed** | Same as above. |
| Professional service area/services UI | **Removed** | Same as above. |
| `woocommerce_params` admin JS check | **Removed** | Replaced with PHP `WooCommerce` class check + `lsarAdmin.woocommerceActive`. |
| Debug `console.log` | **Removed** | Noise / leak. |
| Text domain `local-seo` | **Changed** | Now `local-seo-by-ankit-rawat`. |
| Version constant 3.2 vs header 3.3 | **Fixed** | Both 4.0.0. |
| Generic option names | **Changed** | New unique option; old keys retained. |
| Public PHP functions `local_seo_*` | **Removed** | Replaced by namespaced classes. No documented third-party use in 3.3. |
| GMB sync, reviews UI, opening hours (readme) | **Not present** | Never implemented; docs corrected rather than faked. |
| REST / AJAX / cron | **Unchanged** | Still none. |

## Frontend output differences operators should expect

* Sites that enabled schema with **empty** NAP will **stop** publishing fake “123 Main St / Anytown” data.
* Store JSON-LD shape changes from `product` to `hasOfferCatalog`. Rich Results may take time to recrawl.
* `sameAs`, extra `image`, and `priceRange` may **appear** for the first time if those 3.3 fields were filled.
* JSON is compact (no pretty-print) and uses Unicode escapes for `<` `>`.

## Hooks

3.3 had no public filters. 4.0.0 adds the filters listed in `docs/ARCHITECTURE.md`.
