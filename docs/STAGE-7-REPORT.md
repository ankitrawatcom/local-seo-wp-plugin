# Stage 7 Integration and Release Candidate Report

WordPress 7.0.4 was exercised on a disposable Playground site. WooCommerce and Plugin Check were not installed. The plugin is **not** declared production-ready.

## Environment

PHP:
- Host CLI: **8.5.9** (C:\php\php.exe) — PHPUnit, PHPCS, PHPStan, packaging, WP-CLI phar
- Playground runtime: **8.3** (WordPress Playground WASM)
- PHP 7.4: **not installed**, not executed

WordPress:
- **7.0.4** via `@wp-playground/cli` (`npx @wp-playground/cli@latest start --wp=7.0.4 --php=8.3 --port=9400`)
- Theme: Twenty Twenty-Five
- Database: **SQLite** (Playground site files under `%USERPROFILE%\.wordpress-playground\sites\…`)
- URL: `http://127.0.0.1:9400/`
- Direct `wordpress.org/wordpress-7.0.4.zip` download stalled (~3% / ~8 KB/s) and was aborted
- Docker: **not installed**
- MySQL/MariaDB: **not installed**
- No production site was used

WooCommerce:
- **Not installed.** Blueprint `plugins: ["woocommerce", "plugin-check"]` did not add those plugins (Installed Plugins showed only Akismet, Hello Dolly, Local SEO). wordpress.org plugin ZIPs were not obtained in time on this network.

MySQL/MariaDB:
- Unavailable. SQLite only (Playground).

WP-CLI:
- `tools/wp-cli.phar` **2.12.0** (used for `i18n make-pot` only; not connected to the Playground site)

Composer:
- Present (`C:\composer\composer.bat`) — existing `vendor/` used for PHPUnit/PHPCS/PHPStan

Plugin Check:
- **Not run.** The plugin was not present on the test site; directory install from wordpress.org did not complete.

Other:
- Node **v24.19.0** / npm **11.17.0** (Playground CLI)
- Git **2.55.0**
- PHPUnit **9.6.36**
- Zip extension: yes; `pdo_sqlite`: yes; `sqlite3` extension: no

## Confirmed Fixes

### Product Schema UI

Admin JS no longer requires business type **Store** to show WooCommerce fields.

- PHP: `Admin\Assets::should_show_woocommerce_fields()` returns `WooCommerce::is_active()` (`class_exists( '\WooCommerce' )` and `function_exists( 'wc_get_products' )`). Never `woocommerce_params`.
- `wp_localize_script` still exposes `lsarAdmin.woocommerceActive` from that helper.
- Settings wrap class: `lsar-woocommerce-available` / `lsar-woocommerce-unavailable`.
- CSS hides `.lsar-woocommerce-field` rows only when WooCommerce is unavailable.
- Help text states Product JSON-LD applies on product pages for **any** business type; Store OfferCatalog remains Store-only on front/home/shop.
- Tests: `tests/Unit/AdminUiTest.php`.

Frontend schema behavior was not changed to match the old Store-only UI.

### Store Catalog Documentation

Default kept: front page, posts index (home), WooCommerce shop. Structure remains `hasOfferCatalog` / `OfferCatalog` / `ListItem`.

Updated: `readme.txt`, `README.md`, `CHANGELOG.md`, `docs/ARCHITECTURE.md`, `docs/REGRESSION-AUDIT.md`, admin section copy, `WooCommerce` docblocks.

`local_seo_by_ankit_rawat_embed_store_catalog` is documented as the supported placement filter, not as a bug workaround. 3.3 sitewide invalid `product` nesting is not restored.

### POT

Regenerated with WP-CLI 2.12.0:

`php tools/wp-cli.phar i18n make-pot . languages/local-seo-by-ankit-rawat.pot --domain=local-seo-by-ankit-rawat --exclude=vendor,tests,bin,dist,tools,docs`

Text domain remains `local-seo-by-ankit-rawat`. Privacy policy, WooCommerce help, field descriptions, and new UI strings are present.

### Packaging

`php bin/package.php` writes `dist/local-seo-by-ankit-rawat-4.0.0.zip` (22 files, 39277 bytes in this run). Procedure: `docs/PACKAGING.md`. `bin/` is excluded from PHPCS and from the ZIP.

## WordPress Integration Tests

Performed on Playground WordPress **7.0.4** (not 7.1 beta).

| Item | Result |
|------|--------|
| Site loads | HTTP 200, generator `WordPress 7.0.4` |
| Plugin listed | Yes (inactive until activated) |
| Activation | `ACTIVATE_OK`, version option `4.0.0` |
| Settings page | HTTP 200 after activation; heading, checkbox, saved name, admin CSS/JS handle present; no fatal |
| Settings persistence | Saved NAP appeared on the settings screen |
| Validation | `javascript:` social URL rejected in unit tests; HTTP save used valid URLs only |
| Schema enable | Front page JSON-LD with Harbor Test Co, PostalAddress, GeoCoordinates, sameAs |
| Schema disable | Name omitted from front HTML after disable action |
| Business type | LocalBusiness graph; no `hasOfferCatalog` (expected) |
| Address / logo / social / geo | Present in decoded JSON-LD |
| JSON-LD validity | `ConvertFrom-Json` succeeded; no raw `</script` |
| Admin CSS/JS | `local-seo-by-ankit-rawat-admin` enqueued on settings |
| PHP fatals on front/settings | None observed |
| JS console | Not reliably verified (Playground admin session expired in the IDE browser) |
| Deactivation | Not separately scripted after activate; uninstall path deactivated then ran `uninstall.php` |
| HTTP `options.php` CSRF flow | First curl run got **403** until the plugin was active; later settings GET worked with a logged-in cookie |

A disposable mu-plugin on the Playground site (`lsar-stage7.php`, not in this repository) drove activate/save/migrate/uninstall. `tests/Integration/http-checks.php` can be used when the plugin is already active.

## WooCommerce Integration Tests

**Not performed.** WooCommerce was not present on the test site.

Product schema UI with WooCommerce active was therefore only covered by unit tests and static review (fields show whenever `WooCommerce::is_active()` is true). Live checks for simple/variable/sale/HTML/`</script>` product descriptions, shop catalog, and Store vs non-Store types remain open.

## Migration Tests

Run on the live 7.0.4 SQLite database:

Genuine 3.3 (`local_seo_enable_schema` + `business_name` + `phone` + API key):

- Copied name `Legacy Shop` and phone `617-555-0199`
- Secret **not** in the new option
- Generic `business_name` still `Legacy Shop`
- Version `4.0.0`

Unrelated generics without marker:

- Imported phone/name empty
- Generic phone still `999-OTHER-PLUGIN`

Existing 4.0 values:

- Kept `Already Four` (legacy name did not win)

## Uninstall Tests

After writing namespaced options, version, marker, and generic `phone=KEEP-GENERIC`, `uninstall.php` ran under `WP_UNINSTALL_PLUGIN`:

- `local_seo_by_ankit_rawat_options` gone
- `local_seo_by_ankit_rawat_version` gone
- `local_seo_enable_schema` gone
- generic `phone` still `KEEP-GENERIC`

Empty-option uninstall was not a separate run; `delete_option` on missing keys is a no-op. Multisite was not tested (not claimed).

## Security Tests

- Front JSON-LD after save: valid JSON, no script closer
- Unit `JsonLdSecurityTest` still covers `</script>` hex encoding
- Live malicious **product** HTML was not tested (no WooCommerce)
- Migration did not copy the GMB API key

## Plugin Check Results

**Not run.** Plugin Check was not installed. wordpress.org plugin downloads from this environment were unreliable (core ZIP crawl stalled; Playground blueprint did not install directory plugins).

## Static Analysis

| Tool | Version / notes | Result |
|------|-----------------|--------|
| PHP lint | Host 8.5.9 | Clean on plugin PHP (prior run) |
| PHPUnit | 9.6.36 | **OK (51 tests, 158 assertions)** |
| PHPCS | WordPress ruleset; `bin/`, `tests/`, `vendor/` excluded | Exit 0 |
| PHPStan | 1.12 level 5 | No errors |
| Plugin Check | — | Unavailable |
| WordPress 7.0.4 | Playground | Activation, settings, JSON-LD, migration, uninstall as above |
| WooCommerce | — | Unavailable |

## Remaining Issues

1. No live WooCommerce (products, catalog URLs, HTML/`</script>` descriptions, UI with WC active).
2. Plugin Check not executed.
3. PHP 7.4 still untested at runtime.
4. Admin JavaScript console not confirmed (Playground session expiry).
5. Variable-product `AggregateOffer` still not implemented (accepted Stage 6 medium).
6. Duplicate schema vs Yoast/Rank Math/WC core still default-on (accepted).
7. Conservative 3.3 false-negative migration still applies.
8. Network/multisite uninstall still unsupported.

## Accepted Risks

Unchanged from Stage 6 except catalog docs and product-schema UI are no longer documentation/UI defects.

- Duplicate JSON-LD with other SEO plugins
- Trusted PHP schema filters
- Catalog default is front/home/shop, not 3.3 sitewide
- Removed undocumented `local_seo_*` functions
- `home_url` vs 3.3 `get_site_url`

## Release Package Contents

`dist/local-seo-by-ankit-rawat-4.0.0.zip` includes plugin bootstrap, `src/`, `assets/`, `languages/`, `uninstall.php`, `LICENSE`, `readme.txt`, `README.md`, `CHANGELOG.md`, `docs/ARCHITECTURE.md`, `docs/SECURITY.md`, `docs/REGRESSION-AUDIT.md`.

Excluded: `tests/`, `vendor/`, `bin/`, `tools/`, Composer/QA configs, Git metadata, Stage/audit reports, `docs/PACKAGING.md`.

Not published or uploaded.

## Tested Up To Decision

WordPress **7.0.4** integration succeeded for this plugin’s own screens, JSON-LD, migration, and uninstall. `readme.txt` **Tested up to** is updated to **7.0** (WordPress.org major.minor form). **7.1 beta was not used.**

This does **not** claim WooCommerce-on-7.0.x testing.

## Final Verdict

NOT RELEASE CANDIDATE READY
