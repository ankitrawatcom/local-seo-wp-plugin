# Stage 5 Report

Remaining Stage 3 Medium/Low items, classified and acted on. Stage 4 High fixes were not weakened. No Git push.

| Finding | Severity | Decision | Status | Evidence |
|---------|----------|----------|--------|----------|
| H1 Unsafe migration | High | ALREADY FIXED | Done in Stage 4 | `Migrator::has_legacy_install_evidence()`; `tests/Unit/MigratorTest.php` |
| H2 WC checkbox loss | High | ALREADY FIXED | Done in Stage 4 | `Settings::sanitize()` + hidden `0`; sanitizer tests A–D |
| H3 Autoloader traversal | High | ALREADY FIXED | Done in Stage 4 | `local_seo_by_ankit_rawat_autoload()`; `tests/Unit/AutoloaderTest.php` |
| M1 Duplicate schema by default | Medium | FIX (partial) + ACCEPT | Catalog not on product pages; Yoast/Rank Math still opt-out | `WooCommerce::should_embed_store_catalog()`; filters unchanged and still default true for product JSON-LD (preserves 3.3) |
| M2 OfferCatalog ListItem | Medium | FIX | Done | `WooCommerce::offer_catalog()`; `tests/Unit/StoreCatalogTest.php` |
| M3 Catalog query every request | Medium | FIX | Front/home/shop only | Same helper; filter `local_seo_by_ankit_rawat_embed_store_catalog` restores sitewide 3.3 behavior |
| M4 Uninstall re-import | Medium | ALREADY FIXED | Stage 4 + uninstall deletes marker | `uninstall.php` deletes `local_seo_enable_schema`; generics without marker are not copied |
| M5 README.md GMB claim | Medium | FIX | Done | `README.md` aligned with `readme.txt` |
| M6 Stub LICENSE | Medium | FIX | Done | Full GPLv2 text in `LICENSE` |
| M7 Variable price / fake USD | Medium | FIX (partial) | Min variation price; no USD default. Brand not added (no reliable WC brand API) | `normalize_product()`; `ProductBuilder` omits empty `priceCurrency` |
| M8 Settings capability / errors | Medium | FIX | Done | `register_setting` `capability`; `settings_errors()` on the settings page |
| M9 No POT | Medium | FIX | Done | `languages/local-seo-by-ankit-rawat.pot` |
| M10 Schema filter trusted | Medium | ACCEPT WITH JUSTIFICATION | Retained | Filter is a trusted PHP extension point; re-allowlisting `@type` after it would block legitimate extra Schema.org types. `JsonLd::encode()` still applies `JSON_HEX_TAG`. |
| L1 label_for | Low | FIX | Done | `add_settings_field` `label_for`; business type uses `lsar-business-type` |
| L2 home_url vs site_url | Low | ACCEPT WITH JUSTIFICATION | Retained | `home_url( '/' )` is the public business URL; 3.3 `get_site_url()` was often wrong for subdirectory installs |
| L3 Tested up to 6.7 | Low | ACCEPT WITH JUSTIFICATION | Retained | Not retested against a newer WordPress runtime in this environment; will not invent a Tested up to value |
| L4 Phone charset | Low | FIX (minimal) | `#` allowed for extensions | `Sanitize::phone()`; sanitizer test |
| L5 add_option `'yes'` | Low | FIX | Done | `add_option( …, true )` in `Plugin::activate()` |
| L6 load_plugin_textdomain | Low | ACCEPT WITH JUSTIFICATION | Retained | Still valid; helps non–WordPress.org installs with `/languages` |
| L7 PHPCS FileName exclude | Low | ACCEPT WITH JUSTIFICATION | Retained | PSR-4 `Plugin.php` vs `class-plugin.php`; documented in `phpcs.xml.dist` |
| L8 Admin CSS hide rules | Low | ACCEPT WITH JUSTIFICATION | Retained | JS toggles visibility; no-JS still shows fields (safer than hiding-and-dropping POST) |
| L9 Privacy policy | Low | FIX | Done | `Plugin::privacy_policy()` → `wp_add_privacy_policy_content()` |
| L10 Removed `local_seo_*` functions | Low | ACCEPT WITH JUSTIFICATION | Retained | 3.3 functions were not a documented public API |

## Security Changes

- Privacy policy text states NAP is stored locally and not sent remotely.
- No USD invented for offers (avoids incorrect structured data, not XSS).
- Autoloader, migration gate, JSON-LD encoding, and WC checkbox preservation unchanged from Stage 4.

## WordPress Standards Changes

- `register_setting( …, array( 'capability' => manage_options ) )`
- `settings_errors()` on the settings screen
- `label_for` on settings fields
- Full GPLv2 `LICENSE`
- POT file
- Privacy policy helper

## Schema Changes

- Store `hasOfferCatalog.itemListElement` is `ListItem` + `position` + `item`
- Catalog omitted on product pages and ordinary inner URLs by default (still on front/home/shop)

## WooCommerce Changes

- Empty `get_price()` uses `get_variation_price( 'min', true )` when available
- Currency: override or `get_woocommerce_currency()` only
- Product-page JSON-LD still on when the setting is on (3.3 behavior); operators can disable via filter if Yoast/WC already emit it

## Migration Changes

- None beyond Stage 4. M4 is covered because uninstall removes the 3.3 marker.

## Testing

- PHP lint on changed PHP
- PHPUnit (includes new catalog, privacy, phone, currency tests)
- PHPCS
- PHPStan
- Plugin Check: not available

## Remaining Accepted Risks

- Duplicate LocalBusiness/Product JSON-LD if Yoast, Rank Math, or WooCommerce core also print it (filters exist; defaulting product schema off would drop a 3.3 feature).
- `local_seo_by_ankit_rawat_schema` can change `@type` (trusted PHP).
- Catalog no longer appears on every URL (3.3 did); restore with `local_seo_by_ankit_rawat_embed_store_catalog`.
- 3.3 sites that never saved `local_seo_enable_schema` still will not auto-import generic NAP.
- No live WooCommerce / WordPress Plugin Check in this environment.
- `Tested up to` remains 6.7 until actually retested.
- No WooCommerce “brand” property (no stable core API).

**STAGE 5: PASS**

The plugin is **not** declared production-ready: remaining accepted risks and missing live WP/WC/Plugin Check still apply.
