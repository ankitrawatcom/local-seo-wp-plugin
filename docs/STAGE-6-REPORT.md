# Stage 6 Final Release Audit

Independent review of Local SEO By Ankit Rawat **4.0.0**. No plugin source files were modified. This report is the only new artifact.

Review basis: `docs/AUDIT.md`, `docs/STAGE-3-REVIEW.md`, `docs/STAGE-4-REPORT.md`, `docs/STAGE-5-REPORT.md`, complete `src/` + bootstrap + `uninstall.php` + `readme.txt` + languages, PHPUnit 9.6 with WordPress stubs, PHPCS WordPress, PHPStan 1.12 level 5, extra JSON-LD payload encoding against the live classes, official WordPress.org version history as of **17 August 2026**.

This audit treats 4.0.0 as potentially defective. Passing unit tests is not treated as proof of production readiness.

## Executive Summary

The 4.0.0 rewrite is a substantial security and architecture improvement over 3.3: namespaced options, Settings API sanitization, conservative migration, `JSON_HEX_TAG` JSON-LD encoding, a path-constrained autoloader, and no outbound HTTP or telemetry.

It is **not** ready to ship as a WordPress.org (or equivalent) release. The declared `Tested up to: 6.7` is not backed by any WordPress runtime in this environment, while the current stable WordPress line is **7.0.4** (released 12 August 2026). WooCommerce was not available for integration tests. WordPress Plugin Check was not available. The settings UI hides WooCommerce product-schema controls unless the business type is Store, which does not match runtime behavior. The readme still describes Store catalog as part of the “sitewide” graph without stating the new home/front/shop default.

No critical XSS, CSRF, SQL injection, or privilege-escalation defects were found in static review plus stub unit tests. Remaining blockers are compatibility evidence, WooCommerce reality-testing, documentation accuracy, and a small set of product defects.

**Store catalog recommendation: KEEP NEW DEFAULT** (see that section).

## Critical Findings

None.

No exploitable stored/reflected XSS, CSRF bypass, capability skip on settings, SQL injection, path traversal in the autoloader, unsafe `unserialize`, or secret copy of `google_my_business_api_key` was demonstrated. JSON-LD `</script>` breakout is hex-encoded in unit tests and in an extra payload run.

Absence of critical findings does **not** mean the plugin has been proven safe on WordPress 7.0 or WooCommerce.

## High Findings

1. **`Tested up to: 6.7` is not an actually tested current WordPress version.** WordPress.org’s own guidance is that this field reflects versions the plugin was tested against. As of this audit, WordPress **7.0.4** is current; **7.0.1** exists (9 July 2026) but is not the latest 7.0.x. This workspace has **no WordPress install, no database, and no Docker**. WordPress 7.0.1 could not be tested. Compatibility with 7.0.x must not be claimed from static analysis.

2. **No live WooCommerce integration.** Product schema, catalog queries, prices, variations, and `is_product()` / `is_shop()` behavior were only stubbed or statically reviewed.

3. **WordPress Plugin Check unavailable.** Directory-facing issues (headers, `Tested up to`, i18n, hidden files) were not run through the official tool.

4. **Admin UI vs runtime mismatch for product schema.** `assets/admin.js` shows WooCommerce fields only when business type is **Store** and WooCommerce is active. `WooCommerce::print_product_schema()` outputs product JSON-LD on product pages for **any** business type when `woocommerce_product_schema` is on. Operators on LocalBusiness/Restaurant/Hotel cannot see or change that checkbox without switching type. 3.3 exposed the WC setting independently of Store. This is a real settings regression, not a cosmetic issue.

5. **Readme/settings copy still implies sitewide Store catalog.** `readme.txt` and the WooCommerce section help say up to five products are listed on the **sitewide** graph. 4.0.0 defaults to front page, home, and shop only. Shipping that text will mislead upgraders.

## Medium Findings

1. **Duplicate JSON-LD with Yoast, Rank Math, and WooCommerce core** remains the default when this plugin’s schema checkboxes are on. Filters exist (`local_seo_by_ankit_rawat_output_local_schema`, `local_seo_by_ankit_rawat_output_product_schema`). Accepted if documented; not acceptable to silently drop 3.3 product schema.

2. **POT file is incomplete.** `languages/local-seo-by-ankit-rawat.pot` misses section help, field descriptions, privacy-policy text, and several `esc_html__` strings. Text domain itself is consistent (`local-seo-by-ankit-rawat`).

3. **Multisite / network activation is not supported.** `uninstall.php` deletes options on the current blog only. The plugin does not declare `Network: true`. If network-activated, leftover options can remain on other sites.

4. **Variable products emit a single `Offer`**, using `get_price()` or min variation price. Google often expects `AggregateOffer` (low/high) for variables. Sale vs regular price and `priceValidUntil` are not modeled.

5. **`legacy_aggregate_rating` / `legacy_review_count` can be written via extra POST keys** in `Settings::sanitize()` even though they are not in the UI. Values are sanitized as text and are **not** printed in JSON-LD. Residual footgun if review markup is added later.

6. **Conservative 3.3 migration false negatives.** Sites that never saved `local_seo_enable_schema` will not import generic `phone` / `business_name` keys. Intentional (Stage 4). Operators in that state must re-enter NAP.

7. **No schema caching in 4.0**, despite deleting 3.3 transient `local_seo_json_ld_schema` on migrate/deactivate/uninstall. The key is never set. Not a security bug; dead compatibility cleanup.

8. **PHP 7.4 is declared but not executed here.** Only PHP **8.5.9** CLI is installed. Syntax is consistent with 7.4 (`declare(strict_types=1)`, `public const`, no union types / `match` / constructor promotion in `src/`). Runtime on 7.4 is unverified.

## Low Findings

1. Checkbox fields wrap an extra `<label>` while Settings API already emits `label_for`. Mild duplicate-label noise.

2. Invalid latitude/longitude/URLs are dropped silently; no `add_settings_error()` messages.

3. PHPCS excludes `WordPress.Files.FileName` for PSR-4 (`Plugin.php` vs `class-plugin.php`). Documented and acceptable.

4. `load_plugin_textdomain()` is retained; still valid for non–directory installs.

5. `home_url('/')` vs 3.3 `get_site_url()` remains an accepted public-URL change.

6. WordPress **7.1** is scheduled around 19 August 2026 (beta). It should **not** be listed in `Tested up to` until a stable 7.1 is actually tested.

7. Autoloader path compare uses `strtolower()`, which is fine on Windows; theoretical Unicode path quirks only.

8. `contains_raw_script_close()` is a test helper on a production class. Harmless.

## Security

| Area | Result |
|------|--------|
| XSS / stored XSS | Settings output uses `esc_html`, `esc_attr`, `esc_textarea`. JSON-LD is not HTML-escaped; it uses `JSON_HEX_TAG \| JSON_HEX_AMP \| JSON_HEX_APOS`. Extra payloads (`</script>`, quotes, backslashes, Unicode, HTML, encoded HTML, newlines) encoded without a raw `</script` sequence. Product HTML is `wp_strip_all_tags` + `sanitize_text_field` first. A name that is only `</script><script>` sanitizes to empty and omits the Product node. |
| Reflected XSS | No custom query-arg rendering. |
| CSRF | Settings go through `options.php` + `settings_fields()` nonces. No custom admin-post/AJAX. |
| Capabilities | Menu, page render, asset enqueue, and `register_setting` `capability` all use `manage_options`. |
| Nonce misuse | None found. |
| Privilege escalation | None found. Migration on `admin_init` can run for any user who loads wp-admin; it only copies options when the 3.3 marker exists and does not grant caps. |
| Unsafe redirects / URLs | `Sanitize::http_url()` requires `http://` or `https://` via `esc_url_raw`. |
| Path traversal / inclusion | Autoloader requires PSR-4 class suffix, rejects `..` / NUL, `realpath()` must stay under `src/`. Unit tests cover this. |
| Filesystem | No uploads, includes of user paths, or arbitrary file reads. |
| Deserialization | No `unserialize`. |
| SQL / `$wpdb` | None. Options API only. |
| Options / migration | Single namespaced option; API key excluded; generics not deleted. |
| Uninstall | Namespaced keys + 3.3 marker only. |
| Secrets | No API keys in 4.0 UI/option. Legacy key left in generic option (not copied). |
| JSON-LD / WooCommerce HTML | See JSON-LD section. |
| Filters | `local_seo_by_ankit_rawat_schema` / `_product_schema` / `_settings` are trusted PHP (site owner). `@type` allowlist is re-applied after `_business_type` only. Stage 5 accepted this. |

## WordPress Compatibility

**Requires at least (declared):** 6.0  
**APIs used:** Settings API (`register_setting`, `add_settings_section/field`, `settings_fields`, `do_settings_sections`, `settings_errors`), Options API (`get_option`, `update_option`, `add_option`, `delete_option`), `add_menu_page`, `wp_enqueue_*`, `wp_localize_script`, `load_plugin_textdomain`, `wp_add_privacy_policy_content`, `wp_head`, activation/deactivation/uninstall, `home_url`, `get_permalink`, `wp_get_attachment_url`, `apply_filters` / `add_action`, `wp_json_encode`, i18n helpers.

No deprecated APIs were identified against current WordPress 6.0+ practice. `register_setting` array args (`type`, `sanitize_callback`, `show_in_rest`, `capability`) are the current form.

**Incorrect / incomplete usage:**

- `Tested up to` does not match any test performed here.
- Transient API is only used to **delete** a 3.3 cache key, never to cache 4.0 schema (an improvement vs stale 3.3 cache, not a misuse).
- WooCommerce: `wc_get_products`, `wc_get_product`, `get_woocommerce_currency`, `is_product`, `is_shop` — correct names; untested live.

**Hook timing:** `admin_init` priority 1 for migration is appropriate. Schema on `wp_head` 10 (local) and 20 (product) matches 3.3. Textdomain on `init` is correct.

**What was actually tested against WordPress:** stub functions in `tests/wordpress-stubs.php` only. **Not** 6.0, 6.7, 7.0.1, or 7.0.4.

**Can WordPress 7.0.1 be tested here?** No. No `wp-config.php` under the Wordpress folder, no Docker, no `wp` CLI. **7.1 beta should be excluded** from `Tested up to` until a stable 7.1 is tested.

## PHP Compatibility

| Item | Determination |
|------|----------------|
| Declared minimum | 7.4 (`Requires PHP`, `composer.json`) |
| Should the minimum rise? | **No** solely because 8.x exists. Code does not require 8.0+ language features. |
| Should 7.4 be dropped as obsolete? | **Not from this audit.** Implementation looks 7.4-safe. Keep 7.4 until a real 7.4 run fails. |
| PHP 8.x | Unit tests ran on **8.5.9** (47 tests OK). That is evidence for 8.5 CLI with stubs, not for WordPress-on-8.5. |
| PHP 8.4 | Not installed; not tested. |
| PHP 8.5 | CLI 8.5.9 used for lint, PHPUnit, PHPCS, PHPStan. |

Rationale: 3.3 already required 7.4. 4.0 does not add 8.0-only syntax. Raising the floor without a 7.4 failure would punish hosts still on 7.4.

## Migration

3.3 → 4.0.0 `Migrator` (activation + `admin_init`):

| Requirement | Status |
|-------------|--------|
| Unrelated generic options not imported | **Pass** (requires `local_seo_enable_schema` present, including stored `0`) |
| Existing 4.0 values not overwritten | **Pass** (non-empty 4.0 wins) |
| Idempotent | **Pass** (version ≥ 4.0.0 returns immediately) |
| Does not run on frontend | **Pass** (`admin_init` / activation only) |
| Does not delete generic 3.3 options | **Pass** |
| Does not copy API key | **Pass** |
| Unauthorized trigger | Any wp-admin user can cause the one-time write; no settings UI CSRF. Acceptable for upgrades; not a cap escalation. |
| Genuine 3.3 config | **Pass** in `MigratorTest::test_genuine_3_3_install_migrates_settings` (stubs, not a live DB) |
| Malformed legacy type | **Pass** (`NotAType` → `LocalBusiness` via sanitize) |

Tests are stub-backed, not a real `wp_options` table. Still the migration logic is coherent and conservative.

## Uninstall

`uninstall.php` requires `WP_UNINSTALL_PLUGIN` and deletes:

- `local_seo_by_ankit_rawat_options`
- `local_seo_by_ankit_rawat_version`
- transient `local_seo_json_ld_schema`
- `local_seo_enable_schema`

It does **not** delete generic 3.3 keys (`phone`, `business_name`, `google_my_business_api_key`, …). Missing options are fine (`delete_option` no-ops). No UI. **Multisite is not supported**; document that rather than implying network-wide cleanup.

## JSON-LD

`JsonLd::ENCODE_FLAGS` = `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`. Equivalent protection to `JSON_HEX_TAG` for script breakout. Double quotes are still JSON-escaped by `wp_json_encode`.

Schema.org shape (when fields are filled): `@context`, allowlisted `@type`, `name`, `url` (`home_url`), `telephone`, `image`, `address` (`PostalAddress`), `geo` (`GeoCoordinates` only if both coords valid), `priceRange`, `sameAs`, Store `hasOfferCatalog` with `ListItem` + `position` + `item`. Empty properties omitted. Place ID stored, **not** emitted. No placeholder NAP.

Independent encode run (stubs + real classes): seven malicious/awkward name/address strings → valid JSON, no raw `</script`. Product builder with script-only name produced an empty product (safe).

`JSON_UNESCAPED_UNICODE` can emit U+2028/U+2029; modern HTML script contexts are generally fine. Residual Low risk in ancient browsers only.

## WooCommerce

**Environment:** WooCommerce is **not** installed. No shop, products, or `WC_Product` runtime.

Static review:

- Product pages: `is_product()`, setting on, filter default true, `global $product` or `wc_get_product( get_the_ID() )`.
- Catalog: `wc_get_products( status => publish, limit 5, date DESC )`. Draft/private/deleted excluded.
- Descriptions: short then long, stripped to text.
- Price: `get_price()`; if empty, `get_variation_price( 'min', true )`. No invented USD.
- Currency: optional ISO 4217 override else `get_woocommerce_currency()`.
- Availability: InStock / OutOfStock URLs only (PreOrder mapped if passed in).
- Images: attachment URL only.
- SKU / permalink included when present.
- Missing price: Offer may omit `price` (valid omission; Google may warn).

Untested live: sale prices, variable `AggregateOffer`, out-of-stock, password-protected, custom product types, HPOS, block themes, `is_shop()` on 7.0.

## Settings

- Capability `manage_options` on menu, render, `register_setting`, assets.
- Sanitize rebuilds from defaults; unknown keys dropped except legacy rating POST (Medium).
- Checkboxes: `schema_enabled` missing → false (field always in main section). `woocommerce_product_schema` uses hidden `0` + `1`; absent key preserves stored value (Stage 4).
- Invalid URLs/geo/currency silently emptied.
- `show_in_rest` false.
- Direct POST of `javascript:` URLs and invalid types is rejected by sanitizers (unit tests). No live `options.php` POST was possible without WordPress.

## Accessibility

- `label_for` is set (`lsar-{id}`, business type `lsar-business-type`).
- Nested checkbox `<label>` is redundant (Low).
- Keyboard: native inputs; no custom widgets.
- No-JS: WC rows remain visible (safer for POST). With JS, WC rows hide unless Store + WC active (High finding above).
- Notices: `settings_errors()` present; sanitizer rarely adds errors.
- Admin CSS/JS only on `toplevel_page_local-seo-settings` with capability check.
- Escaping of admin strings is correct.

## Internationalization

- Single text domain `local-seo-by-ankit-rawat` matching the plugin slug and header.
- User-facing PHP strings are wrapped.
- POT exists but is **not** a complete extract (Medium).
- Translated output is escaped (`esc_html__` / `esc_html_e` / `esc_html( __( … ) )`).
- No `.po`/`.mo` shipping required for English.

## Performance

- Frontend: no extra queries for LocalBusiness-only output (options autoload true — appropriate for one small array).
- Store catalog: `wc_get_products` only when type is Store, product schema on, WC active, and `should_embed_store_catalog()` is true (home/front/shop by default). Better than 3.3’s sitewide cache-miss query.
- No 4.0 schema transient. Re-adding caching would add invalidation bugs (3.3 M5) for little gain on a small graph.
- Admin assets scoped to one screen.
- Autoload: settings yes, version no (`add_option( …, false )`).

Caching as implemented is **not** beneficial because it does not exist; that is the right call.

## Backward Compatibility

**Preserved (renamed, documented in architecture docs):**

- `local_seo_by_ankit_rawat_settings`
- `local_seo_by_ankit_rawat_business_type`
- `local_seo_by_ankit_rawat_schema`
- `local_seo_by_ankit_rawat_product_schema`
- `local_seo_by_ankit_rawat_output_local_schema`
- `local_seo_by_ankit_rawat_output_product_schema`
- `local_seo_by_ankit_rawat_store_product_limit`
- `local_seo_by_ankit_rawat_embed_store_catalog` (new in 4.0 for catalog placement)

**Removed:** undocumented 3.3 `local_seo_*` functions (`local_seo_get_woocommerce_product_schema`, render helpers, etc.). They were never a public API in readme. Realistic third-party use is **low** (tiny plugin, prefixed internals in `includes/`). Residual risk: a custom theme that called those functions will fatal. Acceptable for a 4.0.0 major if changelog states it (CHANGELOG currently does not name the removed function list).

Menu slug `local-seo-settings` preserved.

## Testing

Exact results from this machine (17 August 2026):

| Tool | Result |
|------|--------|
| PHP lint (`php -l` on plugin PHP) | No syntax errors |
| PHPUnit 9.6.36 | **OK (47 tests, 143 assertions)** — WordPress/WooCommerce stubs only |
| PHPCS WordPress | **Exit 0** (FileName rule excluded) |
| PHPStan 1.12 level 5 | **No errors** (tool warned it is an old 1.12 line) |
| WordPress Plugin Check | **Unavailable** |
| Real WordPress | **Not run** (no WP, no DB, no Docker) |
| WordPress 7.0.1 / 7.0.4 | **Not run** |
| Real WooCommerce | **Not run** |
| Extra JSON-LD payloads | **OK** (no script closer; valid JSON) |

PHPUnit runtime: PHP 8.5.9.

## WordPress.org Readiness

| Item | Status |
|------|--------|
| Plugin header | Present and internally consistent (4.0.0, slug, author, GPL-2.0-or-later) |
| Requires at least | 6.0 |
| Requires PHP | 7.4 |
| Tested up to | **6.7 — stale vs current 7.0.4; not retested** |
| Stable tag | 4.0.0 |
| License | GPLv2 or later; `LICENSE` is full GPLv2 |
| Text domain / Domain Path | Match slug `/languages` |
| readme.txt | Standard sections; catalog wording inaccurate |
| Slug | `local-seo-by-ankit-rawat` |
| Human-readable source | Yes (no obfuscation) |
| External requests | None |
| Tracking/telemetry | None |
| Third-party prod dependencies | None (Composer is dev-only) |
| Contributors | `ankitrawat` — must match the .org account at upload |

Not directory-ready until `Tested up to` is updated **after** a real test on current 7.0.x, Plugin Check is run, and catalog/UI docs match behavior.

## Release Package

Inspected directory (as it would be zipped from the working tree):

**Must ship:** `local-seo-by-ankit-rawat.php`, `uninstall.php`, `readme.txt`, `LICENSE`, `src/`, `assets/`, `languages/`.

**Should ship:** `README.md`, `CHANGELOG.md` (helpful; .org uses `readme.txt` as canonical).

**Must not ship in a production .org ZIP:** `vendor/` (PHPUnit/PHPCS/PHPStan), `tests/`, `phpunit.xml.dist`, `phpstan.neon.dist`, `phpcs.xml.dist`, `composer.json` / `composer.lock` (optional; .org often allows composer.json but **vendor must not** include dev packages), `.git/`, `.gitignore`, `.editorconfig`, `docs/` (audit reports are not needed on user sites; keeping a short `docs/ARCHITECTURE.md` is optional).

`.gitignore` already ignores `vendor/`. A naive “zip the whole folder including vendor” would ship development tooling and is **not** an acceptable production package.

This audit did not create or modify a ZIP.

## Accepted Risks

These may remain **after** the required changes, with documentation:

1. Duplicate LocalBusiness/Product JSON-LD alongside Yoast, Rank Math, or WooCommerce core. Disable via filters; do not default product schema off (would drop 3.3 functionality).
2. Trusted PHP filters can alter the graph, including `@type` on `_schema` (not re-allowlisted after that filter). Encoding still applies.
3. Store catalog **not** on every URL (3.3 did). Restore with `local_seo_by_ankit_rawat_embed_store_catalog`.
4. 3.3 sites without `local_seo_enable_schema` will not auto-import generic NAP.
5. Manual ratings are stored as legacy keys and never output (Google policy; 3.3 also did not output them).
6. No WooCommerce `brand` property (no stable core API).
7. `home_url` vs 3.3 `get_site_url()`.
8. Removed undocumented `local_seo_*` functions.

**Store catalog (item 9): KEEP NEW DEFAULT.**

## Store catalog regression (detail)

| | 3.3 | 4.0.0 |
|--|-----|--------|
| What | On cache miss, `wc_get_products( limit 5 )` nested under invalid Schema.org `product` on the **sitewide** LocalBusiness graph (every URL sharing that transient). | `hasOfferCatalog` + `ListItem`/`position`; query only when Store + product schema + WC; **default embed: `is_front_page` OR `is_home` OR `is_shop`**, never `is_product()`. |
| SEO | Invalid property; catalog repeated on posts/pages. | Valider graph; catalog where a storefront listing is plausible. |
| Compat | Themes/SEO tools that scraped sitewide `product` nodes would see them everywhere. | Inner URLs lose nested products unless the filter returns true. |
| Filter | None | `local_seo_by_ankit_rawat_embed_store_catalog` is sufficient to restore sitewide embedding without resurrecting `Store.product`. |

**KEEP NEW DEFAULT.** 3.3 behavior was both invalid structured data and unnecessary work on every page. Restricting the catalog is an intentional correctness/performance fix, not an accidental omission. Forcing a restore of 3.3’s sitewide invalid `product` nest would be a regression. Document the change in readme/changelog and keep the filter for the rare site that needs catalog on every URL.

## Required Changes Before Release

Source was not changed in this stage. These are the gates:

1. Test on a real WordPress **7.0.x** site (prefer **7.0.4**, current as of 12 August 2026). Then set `Tested up to` to the version actually tested. Do **not** invent 7.0 compatibility. Do **not** use 7.1 beta for that field.
2. Test real WooCommerce: simple, variable, sale, unpublished, missing price, shop/home/product templates.
3. Run WordPress Plugin Check on the intended production file set.
4. Show WooCommerce product-schema fields whenever WooCommerce is active, not only when type is Store (or document and enforce Store-only runtime — currently inconsistent).
5. Align `readme.txt`, admin help, and CHANGELOG with home/front/shop catalog default and the restore filter.
6. Complete the POT (or generate it with `wp i18n make-pot`).
7. Ship a ZIP that excludes `vendor/`, `tests/`, and QA configs.
8. State in changelog that undocumented `local_seo_*` functions are gone, and that network/multisite uninstall is per-site only.

Optional but recommended: `AggregateOffer` for variables; `add_settings_error` for invalid geo/URLs; list removed 3.3 function names in CHANGELOG.

## Final Verdict

NOT RELEASE READY
