# Local SEO By Ankit Rawat — Version 3.3 Audit

**Audit date:** 17 August 2026  
**Source:** `local-seo-by-ankit-rawat.3.3.zip`  
**Stage:** 1 (audit only; no modernization implementation)

This document is a complete inventory of the shipped 3.3 codebase. Findings are based on source inspection, not runtime WordPress Plugin Check.

---

## Executive summary

Version 3.3 is a small **procedural** plugin (8 files, ~350 lines of PHP). It provides:

1. A Settings API admin page for a single business NAP profile.
2. Optional JSON-LD in `wp_head` for one Schema.org type (`LocalBusiness`, `Restaurant`, `Hotel`, `ProfessionalService`, or `Store`).
3. Optional WooCommerce Product JSON-LD on product pages, plus up to five products nested under Store schema.

Marketing claims in `readme.txt` (Google My Business sync, review management, opening hours, restaurant menus in schema, hotel amenities, multi-feature “advanced” SEO) are **largely unimplemented**. Several admin fields never save and never appear in JSON-LD.

The plugin has **no custom tables, REST routes, AJAX, cron, or outbound HTTP**. The main risks are **generic option-name collisions**, **missing Settings API sanitization**, **stale/incorrect schema**, **JSON-LD script injection if product HTML contains `</script>`**, and **stored secrets (API key) that are never used**.

**Recommended next version:** `4.0.0` (major: architecture, option namespacing, schema correctness, text domain).

**Recommended compatibility baseline:** WordPress 6.2+, PHP 7.4+ (keep 7.4 to avoid abandoning 3.3 sites; target PHP 8.1 in CI). Do not invent a higher WordPress floor than the current header (`Requires WP: 6.0` / readme `Requires at least: 6.0`) without a documented reason; 6.2 is justified by dropped support for older WP in the current ecosystem while remaining conservative.

---

## 1. Plugin architecture

### 1.1 Main plugin file

| Item | Value |
|------|--------|
| File | `local-seo-by-ankit-rawat.php` |
| Plugin Name | Local SEO By Ankit Rawat |
| Header Version | `3.3` |
| Constant `LOCAL_SEO_PLUGIN_VERSION` | `3.2` (**mismatch**) |
| Text Domain (header) | `local-seo` (**not** `local-seo-by-ankit-rawat`) |
| License | GPLv2 or later (header only; no `LICENSE` file) |
| Bootstrap | `ABSPATH` guard, constants, four `require_once`, `init` → `load_plugin_textdomain` |

Missing standard header fields: `Requires at least` (non-standard `Requires WP: 6.0` is used instead), `Domain Path`, `Update URI`.

### 1.2 Directory structure (as shipped)

```text
local-seo-by-ankit-rawat/
├── local-seo-by-ankit-rawat.php
├── readme.txt
├── assets/
│   ├── admin.css
│   └── admin.js
└── includes/
    ├── admin-settings.php
    ├── helpers.php
    ├── schema-generator.php
    └── woocommerce-integration.php
```

**Absent:** `uninstall.php`, `LICENSE`, `CHANGELOG.md`, `composer.json`, `phpcs.xml.dist`, `phpstan.neon.dist`, `.editorconfig`, `.gitignore`, `languages/`, `tests/`, `docs/` (this file is new).

### 1.3 Classes

None. No namespaces. No autoloader. All global functions prefixed `local_seo_`.

### 1.4 Functions

| Function | File | Role |
|----------|------|------|
| `local_seo_load_textdomain` | main | i18n |
| `local_seo_enqueue_admin_scripts` | admin-settings | Admin CSS/JS on settings page only |
| `local_seo_add_menu` | admin-settings | Top-level admin menu |
| `local_seo_render_settings_page` | admin-settings | Settings form |
| `local_seo_register_settings` | admin-settings | `register_setting` + fields |
| `local_seo_render_enable_schema_field` | admin-settings | Checkbox |
| `local_seo_render_standard_field` | admin-settings | Text inputs |
| `local_seo_render_business_type_dropdown` | admin-settings | Type select |
| `local_seo_render_country_input` | admin-settings | Country text |
| `local_seo_render_woocommerce_product_schema_field` | admin-settings | WC checkbox |
| `local_seo_clear_schema_cache` | helpers | Deletes one transient |
| `local_seo_add_local_business_schema` | schema-generator | Sitewide JSON-LD |
| `local_seo_get_woocommerce_product_schema` | schema-generator | Up to 5 WC products |
| `local_seo_add_woocommerce_product_schema` | woocommerce-integration | Product page JSON-LD |

### 1.5 Modules

| Module | Responsibility | Coupling |
|--------|----------------|----------|
| Bootstrap | Constants + includes | Loads all files on every request |
| Admin | Menu, Settings API, assets | Always loaded on frontend too |
| Helpers | Cache invalidation (incomplete) | Only one option hook |
| Schema | JSON-LD + transient | Reads many unprefixed options |
| WooCommerce | Product JSON-LD | Always loaded; gated by `class_exists` / `is_product` |

### 1.6 Admin components

- Top-level menu: slug `local-seo-settings`, capability `manage_options`, Dashicon `dashicons-location-alt`, position `100`.
- Settings group: `local_seo_options_group`.
- Two sections: general fields; “business type specific” fields.
- Assets scoped to hook `toplevel_page_local-seo-settings` (correct).

### 1.7 Frontend components

- No shortcodes, widgets, blocks, or frontend CSS/JS.
- Output: two `wp_head` callbacks that echo `<script type="application/ld+json">`.

### 1.8 REST API

None. No `register_rest_route`.

### 1.9 AJAX

None. No `wp_ajax_*` / `wp_ajax_nopriv_*`.

### 1.10 JavaScript

`assets/admin.js` (jQuery):

- Toggles `.business-specific-field` by concatenating `#business_type` value + `-field`.
- Leaves `console.log` debug statements.
- Treats `typeof woocommerce_params !== 'undefined'` as “WooCommerce is active”. `woocommerce_params` is typically localized on WooCommerce screens, **not** this plugin’s settings page, so Store fields often stay hidden even when WooCommerce is active.

No `wp_localize_script`, no nonce in JS (none needed today).

### 1.11 CSS

`assets/admin.css`: layout for settings table; hides `.business-specific-field` and `.Store-field`. The selector `#business_type[value="Store"] ~ .Store-field` cannot work: the select and table rows are not siblings.

### 1.12 Database usage

No custom tables. No `$wpdb`. No `dbDelta`. No post types, taxonomies, post/term/user meta.

### 1.13 Options (all default autoload)

Registered via Settings API **without** `sanitize_callback`:

| Option name | Intended use | Collision risk |
|-------------|--------------|----------------|
| `local_seo_enable_schema` | Master switch | Low (prefixed) |
| `business_type` | Schema `@type` | **High** |
| `business_name` | Name | **High** |
| `street_address` | Address | **High** |
| `locality` | City | **High** |
| `region` | State | **High** |
| `postal_code` | Postal | **High** |
| `country` | Country | **High** |
| `phone` | Telephone | **High** |
| `price_range` | Price range (UI only) | **High** |
| `business_logo` | Logo URL | **High** |
| `business_images` | Extra images (UI only) | **High** |
| `social_profiles` | Social URLs (UI only) | **High** |
| `latitude` / `longitude` | Geo | **High** |
| `google_my_business_api_key` | Unused secret | **High** |
| `google_my_business_place_id` | Unused | **High** |
| `aggregate_rating` | Unused | **High** |
| `review_count` | Unused | **High** |
| `woocommerce_product_schema` | Product schema flag | Medium |
| `woocommerce_price_currency` | Currency override (not WC’s `woocommerce_currency`) | Medium |

**UI fields that are NOT `register_setting`’d** (do not persist through Settings API):

- `menu_url`, `cuisine_type`, `checkin_time`, `checkout_time`, `star_rating`, `services_offered`, `area_served`

### 1.14 Transients

| Key | TTL | Contents |
|-----|-----|----------|
| `local_seo_json_ld_schema` | `HOUR_IN_SECONDS` | PHP array of sitewide LocalBusiness-like schema (may include WC products) |

Invalidation: only `update_option_local_seo_enable_schema`. Changing NAP, type, or WC settings does **not** clear the cache.

### 1.15 Cron

None.

### 1.16 External HTTP requests

None. No `wp_remote_get` / `wp_remote_post`. Google My Business / Places is not integrated despite settings and readme.

### 1.17 Integrations

| Integration | Actual behavior |
|-------------|-----------------|
| WooCommerce | If `WooCommerce` class exists and product schema option is on: product-page JSON-LD; Store type also embeds 5 products via `wc_get_products` |
| Google My Business | Settings fields only |
| Other SEO plugins | No detection; duplicate Product/LocalBusiness JSON-LD is likely with Yoast, Rank Math, WooCommerce itself |

### 1.18 Third-party libraries

None (jQuery from WordPress core only).

### 1.19 Hooks inventory

**Actions used:**

- `init` — textdomain
- `admin_enqueue_scripts` — assets
- `admin_menu` — menu
- `admin_init` — settings
- `update_option_local_seo_enable_schema` — cache clear
- `wp_head` (10) — local business schema
- `wp_head` (20) — WC product schema

**Filters:** none added, none applied. No public extension API.

**Shortcodes / REST / AJAX / cron:** none.

---

## 2. Feature inventory (version 3.3)

### 2.1 Enable / disable JSON-LD

| | |
|--|--|
| **Implementation** | `local_seo_enable_schema` option; early return in `local_seo_add_local_business_schema` |
| **Hooks** | Settings API; `wp_head` |
| **Storage** | Option `local_seo_enable_schema` |
| **Usage** | Admin checkbox; frontend schema gate |
| **Dependencies** | None |
| **Security** | No sanitize callback; unchecked checkbox may not persist `0` reliably |
| **Compatibility** | OK |

### 2.2 Single-location LocalBusiness JSON-LD

| | |
|--|--|
| **Implementation** | `includes/schema-generator.php` |
| **Hooks** | `wp_head` |
| **Storage** | Generic options listed above |
| **Usage** | Every frontend request when enabled (including admin-ajax? no — `wp_head` on front) |
| **Dependencies** | None |
| **Security** | See §3 (JSON-LD output, unsanitized storage, placeholder NAP) |
| **Compatibility** | `@type` taken from option with **no allowlist** at output time; polluted `business_type` can emit arbitrary `@type` |

**Actually emitted properties:** `@context`, `@type`, `name`, `image` (logo only), `url` (`get_site_url()`), `telephone`, `address` (`PostalAddress`), `geo` (`GeoCoordinates`). Optional empty geo still emitted.

**Not emitted despite UI/readme:** `priceRange`, `sameAs`, extra `image` URLs, `aggregateRating`, `openingHours`, `menu`, `servesCuisine`, hotel check-in/out, `starRating`, `areaServed`, `makesOffer` / services.

### 2.3 Business type selector

Types: `LocalBusiness`, `Restaurant`, `Hotel`, `ProfessionalService`, `Store`.

Restaurant / Hotel / Professional Service extra fields **do not save** and **do not affect schema**. Only Store + WC flag changes output.

### 2.4 Geo coordinates

Stored and output as strings. No numeric validation. Empty strings still produce a `geo` node.

### 2.5 Google My Business “integration”

**Claimed** in plugin header, readme, and settings. **Not implemented.** API key and Place ID are stored if an administrator submits the form. No OAuth, no Places API, no review import.

### 2.6 Manual aggregate rating / review count

Admin fields exist and register as options. **Never read** by schema generator. Not displayed on the front end.

### 2.7 WooCommerce product schema (product pages)

| | |
|--|--|
| **Implementation** | `includes/woocommerce-integration.php` |
| **Hooks** | `wp_head` priority 20 |
| **Storage** | `woocommerce_product_schema`, `woocommerce_price_currency` |
| **Usage** | `is_product()` only |
| **Dependencies** | WooCommerce |
| **Security** | Product name/description/HTML into JSON-LD without `wp_strip_all_tags` / `JSON_HEX_TAG` |
| **Compatibility** | Duplicates WooCommerce and SEO-plugin Product schema; uses plugin currency option instead of `get_woocommerce_currency()`; `global $product` may be unset depending on theme/timing |

### 2.8 WooCommerce products nested in Store schema

`wc_get_products( ['limit' => 5] )` on cache miss, attached as `$schema['product']`. Schema.org `Store` does not define a `product` property in this shape; this is **invalid structured data** and can confuse Google. Queries five products on **every cache miss of the sitewide schema**, not only store pages.

### 2.9 Schema caching

Transient `local_seo_json_ld_schema`, 1 hour. Shared (not user-specific) — appropriate for public schema, but stale after most setting changes. Cached payload includes product names/prices that can be outdated for up to an hour.

### 2.10 Admin settings UI

WordPress Settings API form (`options.php`). Capability `manage_options`. Nonce via `settings_fields()`. Field-level sanitization missing.

### 2.11 i18n

User-facing strings wrapped in `__()` / `esc_html_e()` with text domain `local-seo`. No `languages/` directory. Header Text Domain does not match plugin slug. `load_plugin_textdomain` on `init` is redundant for WordPress.org-hosted plugins since 4.6 but harmless.

### 2.12 Features advertised but **not present**

| Advertised | Status |
|------------|--------|
| GMB connect / sync reviews / location | Not implemented |
| Opening hours | Not implemented |
| Review management UI | Not implemented (manual unused fields only) |
| Canonical URLs / robots | Not implemented |
| Organization / WebSite / WebPage / BreadcrumbList | Not implemented |
| Multi-location | Not implemented |
| Reservation links | Not implemented |
| Hotel amenities | Not implemented |
| “Critical security patches” (3.3 upgrade notice) | 3.3 changelog only says “Compatibility issue”; no security-related code visible |

---

## 3. Security audit

No `eval`, `unserialize`, `shell_exec`, dynamic `include`, `$wpdb`, REST, or AJAX handlers were found. Privilege model is simple (`manage_options` + Settings API). Remaining issues are still real.

### Critical

None identified that allow unauthenticated RCE or unauthenticated data theft given current code paths.

### High

| ID | Finding | Location | Notes |
|----|---------|----------|--------|
| H1 | **Generic option names collide with other plugins/themes** | `register_setting` list | Another plugin can overwrite NAP, phone, or `google_my_business_api_key`. Integrity and SEO poisoning. |
| H2 | **No `sanitize_callback` on any setting** | `admin-settings.php` | WordPress Plugin Handbook requires sanitization on save. Admins can store HTML/JS/URLs. Combined with H1, a less-trusted plugin writing the same keys becomes stored XSS / schema injection. |
| H3 | **JSON-LD printed without `JSON_HEX_TAG` / `JSON_HEX_AMP` and without stripping HTML** | `schema-generator.php`, `woocommerce-integration.php` | `wp_json_encode` does not escape `</script>`. WooCommerce short descriptions often contain HTML. A product description containing `</script>` can break out of the JSON-LD script tag (**stored XSS** on the storefront). |
| H4 | **Google API key stored as a generic autoloaded option** | `google_my_business_api_key` | Secret in `wp_options` autoload, exposed to any code that calls `get_option('google_my_business_api_key')`, and to database leaks. Never used, so risk without benefit. |

### Medium

| ID | Finding | Location | Notes |
|----|---------|----------|--------|
| M1 | **`business_type` not allowlisted on output** | schema-generator | Stored value becomes JSON `@type`. |
| M2 | **Placeholder NAP defaults in public schema** | schema-generator | Empty installs can publish “123 Main St, Anytown, CA 12345” if schema is enabled. SEO/legal misrepresentation. |
| M3 | **`esc_url()` used as a sanitizer for logo in JSON** | schema-generator | Wrong API for data (escaping vs sanitizing). Prefer `esc_url_raw` on save and validated URL in JSON. |
| M4 | **Checkbox options without explicit `0` handling** | enable schema / WC schema | Unchecking may fail to save off-state depending on Settings API behavior. |
| M5 | **Transient not invalidated** when NAP/WC options change | helpers.php | Stale public schema; also caches product offers. |
| M6 | **Type-specific fields submitted but not registered** | admin-settings | Unexpected POST keys ignored; users believe data is saved (integrity/UX; not a CSRF issue because Settings API nonce exists). |

### Low

| ID | Finding | Location | Notes |
|----|---------|----------|--------|
| L1 | **ABSPATH guards only** | all PHP files | Correct for WP includes; not a complete security model. Architecture should not execute privileged work at include time (currently only `add_action`). |
| L2 | **Admin JS debug `console.log`** | admin.js | Information leak of selected type (low). |
| L3 | **`price_range`, social URLs, images stored unsanitized** | options | Not currently output; become XSS if a future version echoes them unsafely. |
| L4 | **No `uninstall.php`** | — | Options and API key remain after uninstall (privacy). |
| L5 | **Product schema uses `global $product` without `wc_get_product()` fallback** | woocommerce-integration | Incorrect/empty output more than a security issue. |

### Informational

| ID | Finding |
|----|---------|
| I1 | Header version `3.3` vs `LOCAL_SEO_PLUGIN_VERSION` `3.2`. |
| I2 | Upgrade notice claims “critical security patches”; 3.3 changelog does not. |
| I3 | Text domain `local-seo` vs slug `local-seo-by-ankit-rawat`. |
| I4 | No REST `permission_callback` issues (no REST). |
| I5 | CSRF on settings is handled by Settings API nonces. Capability is `manage_options`. Nonce is not used as the only auth (acceptable). |
| I6 | No unsafe redirects, SSRF, file uploads, or command execution. |
| I7 | No user meta. Business address/phone is personal/business data with no privacy exporters. |
| I8 | Direct file access of PHP files does nothing useful beyond `exit` without `ABSPATH`. |

### Capability / nonce / REST / AJAX

- Privileged UI: `manage_options` on `add_menu_page`. Settings saves go to `options.php` (core capability + nonce).
- No custom admin-post handlers, so no extra nonce gaps **in custom code**.
- Missing: explicit `current_user_can('manage_options')` on render is optional because `add_menu_page` gates access; still recommended for defense in depth.

---

## 4. WordPress / PHP compatibility audit (Phase 2 input)

### Declared

- WordPress: 6.0+ (`Requires WP` / readme)
- PHP: 7.4+
- Tested up to: 6.7 (readme; WordPress 6.8+ exists as of audit date — header should be retested)

### Deprecated / obsolete API usage

| Item | Assessment |
|------|------------|
| `load_plugin_textdomain` on `init` | Still valid; for .org plugins often unnecessary since 4.6 |
| Settings API `register_setting` without args array | Pre-4.7 style; WP 6.x expects `type`, `sanitize_callback`, `show_in_rest`, `default` |
| `add_menu_page` | Valid; a Settings submenu under Settings is more conventional than a top-level menu for a small plugin |
| `get_site_url()` | Valid; `home_url()` is usually the public URL for schema `url` |
| `esc_url` on data | Misuse (see M3) |
| jQuery `$().change()` | Deprecated jQuery pattern; should be `.on('change', …)` |
| `checked( $value, 1 )` with `get_option(…, false)` | Fragile if option is `'1'` vs `1` vs `'on'` |
| No `wp_add_inline_script` / `wp_localize_script` | Fine for current JS |
| PHP 7.4 | Code uses `??` in one place; no typed properties, no enums; compatible with 7.4 |

### JavaScript / CSS enqueue

Admin-only, hook-guarded: **good**. No frontend assets.

### Localization

Inconsistent text domain vs plugin slug (WordPress.org requirement is slug = text domain).

### Database APIs

N/A beyond Options / Transients.

---

## 5. Database / storage findings

| Store | Keys | Autoload | Migration note |
|-------|------|----------|----------------|
| Options | 20 registered names, many generic | Default yes (all autoloaded) | Must migrate into a single prefixed array option, e.g. `lsar_settings` |
| Transient | `local_seo_json_ld_schema` | n/a | Safe to drop and regenerate |
| Custom tables | none | | |
| Meta | none | | |
| Cron | none | | |
| CPT/tax | none | | |

**Autoload cost:** ~20 options loaded on every request even when schema is off.

**Uninstall:** currently leaves all of the above, including an API key.

---

## 6. SEO / schema correctness (preserve vs fix)

Preserve:

- Ability to output LocalBusiness-family JSON-LD from NAP + geo.
- Business type choices currently in the dropdown.
- Optional WooCommerce product JSON-LD **if** users rely on it — but default should avoid duplicating WooCommerce 8+ / core markup.

Fix (justified; not silent removal):

- Stop emitting placeholder addresses.
- Allowlist `@type`.
- Map Restaurant/Hotel/ProfessionalService fields into valid schema **after** those options actually save.
- Put `sameAs`, `image` array, `priceRange`, `aggregateRating` into JSON-LD only when valid.
- Do not nest five products under `Store.product`.
- Use `JSON_HEX_TAG | JSON_UNESCAPED_SLASHES` (pretty-print optional, admin-only).
- Filter `local_seo_schema` / `lsar_schema` so developers can extend without forks.
- Detect duplicate schema from major SEO plugins where practical (filter off by default or document conflict).

Opening hours, Organization, WebSite, BreadcrumbList: **new features**, not present in 3.3. Do not claim they existed.

---

## 7. Recommended architecture (Stage 2)

Complexity is low. Do **not** add Composer runtime dependencies, custom tables, or REST unless a real GMB integration is specified (GMB API is not a simple API-key Places call; implementing it in 4.0 without OAuth would repeat the 3.3 lie).

Proposed layout (aligned to actual modules):

```text
local-seo-by-ankit-rawat/
├── local-seo-by-ankit-rawat.php   # header + bootstrap only
├── uninstall.php
├── readme.txt
├── LICENSE
├── CHANGELOG.md
├── phpcs.xml.dist
├── phpstan.neon.dist
├── src/
│   ├── Plugin.php                 # hooks
│   ├── Admin/SettingsPage.php
│   ├── Admin/SettingsSanitizer.php
│   ├── Frontend/SchemaPrinter.php
│   ├── Schema/LocalBusinessBuilder.php
│   ├── Schema/ProductSchema.php   # WC, optional
│   ├── Settings/Repository.php    # namespaced option + defaults
│   ├── Settings/Migrator.php      # 3.3 → 4.0
│   └── Support/Cache.php
├── assets/css/admin.css
├── assets/js/admin.js
├── languages/
├── tests/
└── docs/
```

PHP: namespace `AnkitRawat\LocalSeo`, `declare(strict_types=1)`, WP coding standards. Small classes; no singleton required — a `Plugin` instance from the bootstrap file is enough.

**Do not implement live Google Business Profile sync in 4.0** unless product requirements include OAuth, quota, and privacy disclosure. Replace the unused API key field with a documented “Place ID only” or remove with migration (keep the stored key until uninstall/user confirms).

---

## 8. Migration risks (3.3 → modern)

| Risk | Severity | Mitigation |
|------|----------|------------|
| Renaming generic options | High | One-time migrator copies known keys into `lsar_settings`; **do not delete** old keys in 4.0.0 (deprecate; delete in a later major or on uninstall) |
| Other plugins already use `phone` / `country` | High | Migrator should only copy if `local_seo_enable_schema` exists **or** a sentinel `lsar_version` is absent and Local SEO menu was used; document false-positive copies |
| Schema HTML changes | Medium | Allowlist types; omit empty nodes; sites may see Rich Result diffs — changelog must say so |
| WC nested products removed | Medium | Feature was invalid; document; keep product-page schema behind a setting |
| Text domain change | Medium | Use `local-seo-by-ankit-rawat`; keep `_deprecated` wrappers only if translations exist (they do not in the zip) |
| Version constant mismatch | Low | Single source of truth |
| Users who filled restaurant/hotel fields | Low | Those fields never saved; nothing to migrate |
| API key | Medium | Migrate into namespaced option with `autoload => false`; never log it |

Migrator must be idempotent, version-aware (`lsar_db_version` / `lsar_plugin_version`), and run on `admin_init` or `upgrader_process_complete` + activation — **not** on every front-end request.

---

## 9. Proposed version number

**`4.0.0`**

Reasons (SemVer major):

- Public storage contract changes (namespaced settings).
- Schema output will change (correctness, no placeholders, no invalid Store.product).
- Text domain / architecture / bootstrap change.
- Removal or deprecation of fake GMB “integration” fields as a live API.

Keep 3.3 as the documented from-version in the migrator and changelog.

---

## 10. Testing gaps (for Stage 3)

No tests exist. Minimum for 4.0:

- Bootstrap, activation, deactivation, uninstall (data policy).
- Sanitization/allowlists.
- Migrator 3.3 → 4.0 (generic options copied once).
- Schema builder snapshots for each business type (no placeholder NAP).
- JSON-LD does not contain raw `</script>`.
- WC product schema gated; no Store.product nest.
- Cache invalidation on settings save.
- Capability: settings page hidden from subscribers.

---

## 11. Stage 1 decision: stop

This audit is complete. **No production PHP/JS/CSS refactor has been applied** (only this document).

Stage 2 should implement the architecture, sanitization, migration, and honest readme in that order—without claiming GMB sync until it exists.
