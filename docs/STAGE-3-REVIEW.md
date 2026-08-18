# Stage 3 — Independent Review of Local SEO 4.0.0

**Review date:** 17 August 2026  
**Scope:** Current working tree (plugin 4.0.0). Compared against `docs/AUDIT.md`, version 3.3 behavior, Stage 2 requirements, and current WordPress practice.  
**Method:** Manual source review. Automated tests were not re-run as part of this review and are **not** treated as proof of security.  
**Code changes:** None.

---

## Executive Summary

4.0.0 is a real modernization of a small plugin: namespaced PHP, one prefixed option, Settings API sanitization, allowlisted `@type`, JSON-LD `JSON_HEX_TAG`, honest readme, uninstall that avoids deleting generic 3.3 keys, and no fake Google Business Profile API.

It is **not** ready to call production-safe without fixes. The highest-impact issues are:

1. First-run migration copies **generic** WordPress options (`phone`, `country`, `business_name`, …) whenever those keys exist, even if this plugin was never installed. That can import another plugin’s data into Local SEO (and later into public JSON-LD).
2. Saving the settings form **resets hidden WooCommerce checkboxes to false**, because unchecked/hidden inputs are omitted from POST and `Settings::sanitize()` treats missing keys as off.
3. The global autoloader concatenates the class suffix onto a filesystem path **without rejecting `..`**, so a caller can attempt local file inclusion.

JSON-LD script-breakout handling for values that pass through `JsonLd::encode()` is directionally correct. Capability + Settings API nonces for the admin screen are basically sound. WooCommerce product-page output is preserved in intent but not integration-tested and still duplicates other SEO plugins by default.

**Overall verdict:** **FAIL** for release until the High migration, settings-save, and autoloader issues are addressed.

---

## Critical Findings

None that yield unauthenticated RCE or unauthenticated stored XSS on the public site given the current output path (`JSON_HEX_TAG` on plugin-built graphs).

No Critical items.

---

## High Findings

### H1 — Migration imports colliding generic options on any site

| | |
|--|--|
| **File** | `src/Migration/Migrator.php` |
| **Function/class** | `Migrator::migrate_from_3_3()` |
| **Line** | ~50–90 |
| **Problem** | When `local_seo_by_ankit_rawat_options` does not exist, `$replace_defaults` is true and **every** mapped legacy key is copied via `get_option( $old_key )`. Keys include `phone`, `country`, `business_name`, `latitude`, `woocommerce_product_schema`, etc. There is **no** check that 3.3 of *this* plugin was installed (for example `local_seo_enable_schema` present). |
| **Why it matters** | `docs/AUDIT.md` flagged generic option names as High collision risk. A fresh 4.0.0 install on a site that already has `phone` from another plugin will store that value as Local SEO NAP. If schema is later enabled, **another plugin’s data is published as this business**. Tests only cover the happy path where 3.3 keys were set on purpose (`tests/Unit/MigratorTest.php`). |
| **Recommended fix** | Migrate only if a **unique** 3.3 sentinel exists (`local_seo_enable_schema` and/or a known combination). Do not copy `phone`/`country`/… unless that sentinel is present. |
| **Severity** | High |

### H2 — Settings save turns hidden WooCommerce flags off

| | |
|--|--|
| **File** | `src/Admin/Settings.php`, `assets/admin.js` |
| **Function/class** | `Settings::sanitize()`, `toggleWooCommerceFields()` |
| **Line** | Settings ~350–367; JS ~8–16 |
| **Problem** | `woocommerce_product_schema` is a checkbox. Sanitize uses `Sanitize::bool( isset( $input['woocommerce_product_schema'] ) ? … : false )`. Admin JS **hides** those rows unless business type is Store **and** WooCommerce is active. Hidden checkboxes are not posted. Any save from LocalBusiness/Restaurant/Hotel/ProfessionalService, or while WooCommerce is inactive, sets `woocommerce_product_schema` to **false**. |
| **Why it matters** | Operators can lose the 3.3/4.0 product-schema toggle by saving unrelated NAP fields. Temporarily deactivating WooCommerce and saving Local SEO settings permanently disables product JSON-LD. Stage 2 required preserving WooCommerce behavior. |
| **Recommended fix** | Persist hidden values: hidden input `0` plus checkbox `1`, or merge unchecked keys from the existing option when the field was not in the submitted UI. Do not hide fields in a way that drops them from POST unless you merge. |
| **Severity** | High |

### H3 — Autoloader path not constrained

| | |
|--|--|
| **File** | `local-seo-by-ankit-rawat.php` |
| **Function/class** | `local_seo_by_ankit_rawat_autoload()` |
| **Line** | ~36–47 |
| **Problem** | After stripping the namespace prefix, `$relative` is interpolated into `LOCAL_SEO_BY_ANKIT_RAWAT_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php'` and `require`d if readable. There is no allowlist (`^[A-Za-z0-9_/]+$`) and no rejection of `..`. The function is global and callable by any PHP on the site. |
| **Why it matters** | A compromised or malicious plugin can `require` arbitrary readable `.php` files via `AnkitRawat\LocalSEO\..\..\uploads\…`. This is local file inclusion, not remote RCE by itself. |
| **Recommended fix** | Reject `..`, NUL, and non-PSR-4 characters; `realpath()` and confirm the file is inside `src/`. |
| **Severity** | High |

---

## Medium Findings

### M1 — Duplicate structured data by default

| | |
|--|--|
| **File** | `src/Schema/LocalBusiness.php`, `src/Schema/WooCommerce.php` |
| **Function/class** | `print_schema()`, `print_product_schema()` |
| **Line** | LocalBusiness ~56; WooCommerce ~74 |
| **Problem** | Filters `local_seo_by_ankit_rawat_output_local_schema` and `…_output_product_schema` default to **true**. No detection of WooCommerce core Product JSON-LD, Yoast, or Rank Math. On a Store product page both sitewide Store graph (including catalog) and product JSON-LD print. |
| **Why it matters** | Stage 2 required not blindly emitting duplicate schema. Filters are an opt-out, not a default-safe design. Duplicate Product markup confuses Google. |
| **Recommended fix** | Default product-page output off when WooCommerce already prints product schema, or document a recommended snippet and consider defaulting product JSON-LD off when `WC()->structured_data` is present. Keep filters. |
| **Severity** | Medium |

### M2 — `OfferCatalog.itemListElement` as raw Product nodes

| | |
|--|--|
| **File** | `src/Schema/LocalBusiness.php` |
| **Function/class** | `LocalBusiness::build()` |
| **Line** | ~138–145 |
| **Problem** | 3.3 used invalid `Store.product`. 4.0 uses `hasOfferCatalog` with `itemListElement` = array of Product. Schema.org ItemList/OfferCatalog more correctly uses `ListItem` (with `position`) or Offers, not a bare Product array. |
| **Why it matters** | Rich Results may ignore or warn. Functionality of “up to five products” is preserved; validity is only partly improved. |
| **Recommended fix** | Wrap each product in `ListItem` + `position`, or emit a `@graph` of Store + Products. |
| **Severity** | Medium |

### M3 — Store catalog queries on every public request

| | |
|--|--|
| **File** | `src/Schema/LocalBusiness.php`, `src/Schema/WooCommerce.php` |
| **Function/class** | `build()` → `store_products()` |
| **Line** | LocalBusiness ~138; WooCommerce ~117–124 |
| **Problem** | Transient caching was removed (justified vs stale 3.3 cache). For Store + product schema, `wc_get_products()` runs on **every** front-end `wp_head`, including posts and archives, not only a shop page. |
| **Why it matters** | Stage 2 asked to review whether cache was needed. For NAP-only sites this is fine. For Store sites this is extra WooCommerce queries sitewide. |
| **Recommended fix** | Restrict catalog embedding to the front page / shop, or cache the catalog fragment and invalidate on product/settings save. |
| **Severity** | Medium |

### M4 — Uninstall + reinstall re-imports leftover generic options

| | |
|--|--|
| **File** | `uninstall.php`, `src/Migration/Migrator.php` |
| **Function/class** | uninstall; `maybe_run()` |
| **Line** | uninstall ~15–23; Migrator ~31–38 |
| **Problem** | Uninstall deletes the namespaced option and version (good) and `local_seo_enable_schema`. Generic 3.3 keys remain (intentional). After reinstall, H1 runs again: leftover `phone` etc. are copied back. |
| **Why it matters** | Users who uninstall expecting a clean plugin still get old/foreign NAP on next activate. |
| **Recommended fix** | Same sentinel as H1. Optionally document a WP-CLI/cleanup recipe for leftover keys. |
| **Severity** | Medium |

### M5 — `README.md` still advertises Google My Business

| | |
|--|--|
| **File** | `README.md` |
| **Function/class** | n/a |
| **Line** | 1–2 |
| **Problem** | GitHub readme still says “integrate Google My Business”. Plugin `readme.txt` correctly says that is not implemented. |
| **Why it matters** | Stage 2 required removing inaccurate claims. This file was not updated (likely leftover from `main`). |
| **Recommended fix** | Align `README.md` with `readme.txt`. |
| **Severity** | Medium |

### M6 — LICENSE is a stub, not the full GPL-2.0 text

| | |
|--|--|
| **File** | `LICENSE` |
| **Function/class** | n/a |
| **Line** | entire file (~24 lines) |
| **Problem** | Header claims GPL-2.0-or-later. File points to gnu.org instead of including the license body. WordPress.org plugin guidelines expect a GPL-compatible license; many reviews want the full text in the zip. |
| **Why it matters** | Distribution/compliance friction, not a runtime bug. |
| **Recommended fix** | Ship the full GPLv2 (or GPLv3) text. |
| **Severity** | Medium |

### M7 — Variable / empty WooCommerce prices

| | |
|--|--|
| **File** | `src/Schema/WooCommerce.php`, `src/Schema/ProductBuilder.php` |
| **Function/class** | `normalize_product()`, `ProductBuilder::from_data()` |
| **Line** | WooCommerce ~184; ProductBuilder ~56–64 |
| **Problem** | Uses `get_price()`. Variable products often have an empty price until a variation is selected. `Sanitize::price('')` omits `offers.price`. Fallback currency `USD` if WC currency is missing (WooCommerce ~195–197) can fabricate USD. |
| **Why it matters** | Invalid or incomplete Product+Offer markup. Stage 2 asked for correct price/currency. Brand is never output. |
| **Recommended fix** | For variable products use `get_variation_price()` / min-max or skip Offer until a numeric price exists. Never default currency to USD; omit `priceCurrency` if unknown. Add brand from WC if present. |
| **Severity** | Medium |

### M8 — Settings API: no `settings_errors()`, no explicit capability on `register_setting`

| | |
|--|--|
| **File** | `src/Admin/Settings.php` |
| **Function/class** | `register_setting()`, `render_page()` |
| **Line** | ~88–97, ~203–217 |
| **Problem** | `register_setting()` does not set `'capability' => 'manage_options'` (core default for `options.php` is still `manage_options`, so this is hardening, not a current bypass). The page never calls `settings_errors()`. |
| **Why it matters** | Future WP changes aside, users get no Settings API error notices if sanitization adds them later. |
| **Recommended fix** | Set capability explicitly; call `settings_errors()`. |
| **Severity** | Medium |

### M9 — No `.pot` / translation files

| | |
|--|--|
| **File** | `languages/index.php` |
| **Function/class** | n/a |
| **Line** | n/a |
| **Problem** | Text domain is corrected and strings are wrapped, but there is no POT/PO. `load_plugin_textdomain` on `init` is redundant for WordPress.org JIT since 6.5+ but harmless. |
| **Why it matters** | Translators cannot ship translations with the plugin as-is. |
| **Recommended fix** | Generate `languages/local-seo-by-ankit-rawat.pot`. |
| **Severity** | Medium |

### M10 — `local_seo_schema` filter can replace the whole graph after allowlisting

| | |
|--|--|
| **File** | `src/Schema/LocalBusiness.php` |
| **Function/class** | `build()` |
| **Line** | ~155 |
| **Problem** | `@type` is allowlisted, then `apply_filters( 'local_seo_by_ankit_rawat_schema', $schema )` can set any `@type` or raw HTML-ish strings. `JsonLd::encode()` still hex-encodes `<`/`>`. |
| **Why it matters** | Trusted PHP only (other plugins). Not an unauthenticated bug. Arbitrary schema injection by a buggy mu-plugin is possible. |
| **Recommended fix** | Re-allowlist `@type` after the filter, or document that the filter is fully trusted. |
| **Severity** | Medium (trusted context) |

---

## Low Findings

### L1 — Accessibility: input `id` vs Settings API label

| | |
|--|--|
| **File** | `src/Admin/Settings.php` |
| **Function/class** | `add_field()`, `render_field()` |
| **Line** | ~156–172, ~227–277 |
| **Problem** | Inputs use `id="lsar-{id}"`. `add_settings_field()` is not passed `label_for`. Screen-reader / click-label association is weak. |
| **Recommended fix** | Pass `'label_for' => 'lsar-' . $id` (and match the select id). |
| **Severity** | Low |

### L2 — `home_url( '/' )` vs 3.3 `get_site_url()`

| | |
|--|--|
| **File** | `src/Schema/LocalBusiness.php` |
| **Function/class** | `build()` |
| **Line** | ~89–105 |
| **Problem** | Public `url` may differ (home vs site URL, trailing slash). Usually an improvement; can change Rich Results URL. |
| **Recommended fix** | Use `home_url( '/' )` consistently and document; optionally filter. |
| **Severity** | Low |

### L3 — `Tested up to: 6.7` while review date is 2026

| | |
|--|--|
| **File** | `readme.txt` |
| **Line** | 5 |
| **Problem** | Stale compatibility claim vs current WordPress. Header `Requires at least: 6.0` matches 3.3 (Stage 2 correctly did not invent 6.2). |
| **Recommended fix** | Retest and bump `Tested up to`. |
| **Severity** | Low |

### L4 — Phone sanitizer drops `#` and many Unicode digits

| | |
|--|--|
| **File** | `src/Support/Sanitize.php` |
| **Function/class** | `phone()` |
| **Line** | ~121–133 |
| **Problem** | Character class is ASCII-oriented; length 32. International numbers using other punctuation may be altered. |
| **Recommended fix** | Allow a wider safe set or store `sanitize_text_field` without stripping `+`/spaces only. |
| **Severity** | Low |

### L5 — `Plugin::activate` uses deprecated-style autoload `'yes'` string

| | |
|--|--|
| **File** | `src/Plugin.php` |
| **Function/class** | `activate()` |
| **Line** | ~84 |
| **Problem** | `add_option( …, '', 'yes' )`. WP 6.6+ prefers boolean autoload. Still works on WP 6.0. |
| **Recommended fix** | Use `true`/`false`. |
| **Severity** | Low |

### L6 — `load_plugin_textdomain` on `init`

| | |
|--|--|
| **File** | `src/Plugin.php` |
| **Function/class** | `load_textdomain()` |
| **Line** | ~63–68 |
| **Problem** | Not invalid; WordPress.org plugins often rely on just-in-time loading (6.7 notes). |
| **Recommended fix** | Optional removal if only targeting WP.org. |
| **Severity** | Low |

### L7 — PHPCS excludes `WordPress.Files.FileName`

| | |
|--|--|
| **File** | `phpcs.xml.dist` |
| **Line** | ~16–18 |
| **Problem** | Documented PSR-4 exception. Fine if intentional; WPCS-pure plugins would use `class-plugin.php`. |
| **Recommended fix** | Keep with comment (already present). |
| **Severity** | Low |

### L8 — Admin CSS file is almost empty of rules that hide WC fields

| | |
|--|--|
| **File** | `assets/admin.css` |
| **Problem** | Visibility is JS-only. Without JS, WooCommerce fields show always (better than H2’s hide-and-clobber, actually). |
| **Severity** | Low |

### L9 — No privacy policy / exporter

| | |
|--|--|
| **File** | n/a |
| **Problem** | Business NAP can be personal data. No `wp_add_privacy_policy_content`. Audit mentioned this. Plugin does not transmit data. |
| **Severity** | Low |

### L10 — Deprecated 3.3 global functions removed with no wrappers

| | |
|--|--|
| **File** | (removed `includes/*.php`) |
| **Problem** | `local_seo_*` functions are gone. Unlikely third-party use; still a hard break. |
| **Severity** | Low |

---

## Security Findings

| ID | Topic | Result |
|----|--------|--------|
| H3 | Autoloader LFI | High |
| H1 | Option collision → wrong NAP in JSON-LD | High |
| H2 | Accidental disable of product schema | High (integrity, not XSS) |
| JSON-LD | `JSON_HEX_TAG \| JSON_HEX_AMP \| JSON_HEX_APOS` in `JsonLd::ENCODE_FLAGS` (~26, ~59) | **Sound** for `</script>` in encoded output |
| XSS admin | Field values use `esc_attr` / `esc_textarea` / `esc_html` | **Sound** |
| CSRF | `settings_fields( Plugin::GROUP )` | **Sound** |
| Caps | Menu + `render_page` + asset enqueue use `manage_options` | **Sound** (nonce is not used as auth) |
| REST/AJAX | None | **N/A** |
| SQL | No `$wpdb` | **N/A** |
| HTTP | No `wp_remote_*` | **Pass** (Stage 2: do not add) |
| GMB key | Not copied, not displayed | **Pass** vs audit H4 |
| `show_in_rest` | `false` | **Pass** |
| Missing `JSON_HEX_QUOT` | Quotes not hex-encoded | Low residual; tag breakout is the real script issue |

Unauthenticated stored XSS via product HTML is **mitigated** if output always goes through `JsonLd::encode()`. A `local_seo_by_ankit_rawat_schema` filter that echoed raw HTML would be a third-party bug.

**Security category: FAIL** (H1–H3). JSON-LD encoding itself is **PASS**.

---

## WordPress Standards Findings

- Settings API used with `sanitize_callback` and `type => array` — **meets** the main Stage 2 requirement.
- Missing `capability` on `register_setting`, missing `settings_errors()`, missing `label_for` — M8, L1.
- Prefixes: option `local_seo_by_ankit_rawat_options` is unique — **Pass** for new storage.
- Text domain matches slug — **Pass**.
- Header uses `Requires at least` not `Requires WP` — **Pass**.
- Version 4.0.0 aligned in header, constant, `Plugin::VERSION`, readme stable tag — **Pass**.
- WPCS FileName sniff excluded — documented.
- PHPStan/PHPCS were reported clean in Stage 2; this review did not re-execute them. Treat as **unverified here**.

**WordPress standards category: FAIL** (incomplete Settings API/a11y), not a total miss.

---

## Migration Findings

- Idempotent after `local_seo_by_ankit_rawat_version >= 4.0.0` — **Pass**.
- Does not delete 3.3 options — **Pass** (audit).
- Does not copy `google_my_business_api_key` — **Pass**.
- Place ID copied to `place_id`, not output in JSON-LD — **Pass** / honest.
- Ratings copied to `legacy_*`, not shown, not output — **Pass** vs Google self-serving review rules.
- Runs on `admin_init` + activation, not front-end — **Pass**.
- **Fails** H1 (no unique 3.3 sentinel).
- **Fails** M4 (reinstall).
- `get_option( $old, null )` treats missing as `false` and skips — correct for “not in DB”; cannot distinguish empty string stored by another plugin.

**Migration category: FAIL**.

---

## WooCommerce Findings

- Detection via `class_exists( '\WooCommerce' )` and `wc_get_products` — **Pass** vs `woocommerce_params`.
- `lsarAdmin.woocommerceActive` — **Pass**.
- Product pages: `is_product()`, `WC_Product` or `wc_get_product()` — **improvement** vs 3.3 `global $product` only.
- Descriptions stripped then JSON-encoded — **Pass** for XSS.
- Published-only catalog, limit 5 (filterable, clamped 20) — **Pass** intent.
- H2 save clobber — **Fail**.
- M1 duplicates — **Fail** default.
- M3 every-request query — **Fail** performance for Store.
- M7 variable price / USD fallback — **Fail** correctness.
- No real WooCommerce PHPUnit tests — **Fail** testing.

**WooCommerce category: FAIL**.

---

## Schema Findings

- Allowlisted types; placeholders removed; empty nodes omitted; geo requires both valid coords — **Pass** vs audit M1/M2.
- `sameAs`, extra images, `priceRange` now output when valid — **intentional change** (3.3 stored, did not emit).
- JSON-LD flags — **Pass** for script context.
- `url` sanitized as http(s) — **Pass**.
- M2 OfferCatalog shape — remaining validity issue.
- Restaurant/Hotel/ProfessionalService still have **no type-specific properties** (3.3 UI never saved those fields) — honest, not a fake implementation.

**Schema category: PASS** with Medium validity/duplication issues (not a High).

---

## Regression Findings

| 3.3 | 4.0.0 | Assessment |
|-----|--------|------------|
| Settings screen / menu slug | Kept | Pass |
| LocalBusiness JSON-LD | Kept, stricter | Pass (SEO change: no fake NAP) |
| WC product JSON-LD | Kept | At risk from H2 |
| Five products on Store | Kept as catalog | Shape changed (documented) |
| Transient cache | Removed | Justified; Store cost up |
| GMB fields | Key gone from UI; Place ID notes | Pass |
| `local_seo_*` functions | Removed | Low break |
| Unsaved restaurant/hotel fields | Removed from UI | Pass (never worked) |
| Text domain | Changed | Expected major |

**Regression category: FAIL** until H2 is fixed (silent loss of WC flag). Otherwise feature regression is documented and mostly acceptable.

---

## Testing Findings

Present: sanitization allowlist, geo/URL reject, JSON `</script>` hex, migration happy path + idempotency + API key exclusion, version header regex, uninstall file string checks.

Missing vs Stage 2:

- Capability: only asserts the constant, does not simulate a subscriber.
- No test that migration **does not** copy `phone` without a 3.3 sentinel (would currently fail if written).
- No test that saving settings without `woocommerce_product_schema` in the array preserves an existing true flag (would fail).
- No WooCommerce `WC_Product` doubles for variable products.
- No WP integration tests (activation in real `WP_UnitTestCase`).
- Stubs reimplement `get_option`; they cannot catch WP `false` vs `null` nuances.
- PHPUnit was not re-run in Stage 3.

**Testing category: FAIL**.

---

## Architecture Findings

- Size is appropriate: no DI container, no Composer runtime deps, no REST/AJAX/tables/HTTP.
- Skipping `SchemaCache` matches Stage 2 “don’t cache just because 3.3 did” — acceptable; M3 is the leftover cost.
- `ProductBuilder` vs `WooCommerce` split is justified for tests.
- Global autoloader is simple but unsafe (H3).
- `Plugin::settings()` filter before admin/schema is useful; must stay escaped at output (it is, for admin).

**Architecture category: PASS** (H3 is security, not over-engineering).

---

## Stage 2 / AUDIT items not done

| Requirement | Status |
|-------------|--------|
| Namespaced option + sanitization + allowlist | Done |
| JSON_HEX_TAG | Done |
| No placeholder NAP | Done |
| Don’t migrate GMB API key | Done |
| Don’t delete generic 3.3 options | Done |
| Don’t fake GMB API | Done |
| Honest readme.txt | Done |
| `README.md` honesty | **Not done** (M5) |
| Migration without colliding with other plugins | **Not done** (H1) — audit explicitly warned |
| Invalidation/cache | Cache removed; Store query cost not mitigated |
| Duplicate schema | Filters only, default still duplicates |
| WooCommerce detection | Done |
| Persist WC settings | **Broken** on save (H2) |
| label_for / a11y | **Not done** |
| Full GPL text | **Not done** |
| PHPUnit covering authorization, migration collision, WC live | **Partial** |
| Autoloader hardening | **Not done** |
| `register_setting` capability | **Not done** |

---

## Recommended Fixes

Priority order:

1. **H1** — Gate migration on `local_seo_enable_schema` (or another unique 3.3 key). Add a unit test that `phone` alone does not migrate.
2. **H2** — Preserve `woocommerce_product_schema` / currency when those inputs are absent from POST; add a hidden `0` + checkbox pattern or merge-from-stored. Add a unit test.
3. **H3** — Constrain autoload paths to `src/` via realpath + prefix check.
4. **M1/M2/M3** — Catalog `ListItem`s; consider not printing sitewide Store catalog on every URL; default-off product schema when WC already emits it.
5. **M5/M6** — Fix GitHub `README.md`; ship full GPL.
6. **M7** — Variable product prices; no USD fabrication.
7. **L1/M8** — `label_for`, `settings_errors()`, explicit capability.
8. Expand tests as listed under Testing Findings.

Do not treat green PHPUnit on stubs as a security sign-off.

---

## PASS / FAIL by category

| Category | Result |
|----------|--------|
| Security (overall) | **FAIL** |
| JSON-LD script encoding | **PASS** |
| WordPress Settings API / caps / nonces | **FAIL** (H2 + incomplete API options) |
| WordPress Coding Standards | **UNVERIFIED** this stage (previously reported clean with FileName exclude) |
| Migration safety | **FAIL** |
| Uninstall safety (generic key deletion) | **PASS** |
| Uninstall + reinstall data meaning | **FAIL** (M4 + H1) |
| WooCommerce | **FAIL** |
| Schema correctness | **PASS** (with Medium leftover validity) |
| Backward compatibility / regression | **FAIL** (H2; otherwise documented) |
| Testing | **FAIL** |
| Architecture (scope/size) | **PASS** |
| Internationalization (strings/domain) | **PASS** |
| Internationalization (shipped translations) | **FAIL** (M9) |
| Performance | **FAIL** for Store+catalog on all pages; **PASS** for NAP-only |
| Accessibility | **FAIL** |
| PHP 7.4 compatibility (source) | **PASS** (no 8.0-only syntax observed) |
| Licensing completeness | **FAIL** (stub LICENSE + stale README.md) |
| Audit High items H1–H4 (generic options going forward / sanitization / JSON-LD / API key) | **PARTIAL** — new option names and sanitization/JSON-LD/key handling improved; **generic collision still lives in the migrator** |

**Release recommendation: FAIL** until H1, H2, and H3 are fixed and covered by tests.
