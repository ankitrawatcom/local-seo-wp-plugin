# Stage 7A — Prepared for Integration Testing

Docker/WSL was not waited on. WordPress and WooCommerce integration are **not** claimed as passed. `Tested up to` remains **6.7**.

## Changes made

### Product schema admin UI (already in 4.0.0 tree; verified)

Visibility follows `WooCommerce::is_active()` (`class_exists( '\WooCommerce' )` + `function_exists( 'wc_get_products' )`), not business type Store, not `woocommerce_params`.

- `src/Admin/Assets.php` — `should_show_woocommerce_fields()`
- `assets/admin.js` — show/hide from `lsarAdmin.woocommerceActive` only
- `assets/admin.css` — hide fields when wrap has `lsar-woocommerce-unavailable`
- `src/Admin/Settings.php` — wrap class + copy that Product JSON-LD is type-independent
- `tests/Unit/AdminUiTest.php`

Frontend Product JSON-LD and Store catalog rules were not changed to make the UI easier.

### Store catalog documentation (verified / kept)

Default remains front page, home (posts index), and WooCommerce shop. Filter `local_seo_by_ankit_rawat_embed_store_catalog` is documented as customization, not a workaround. 3.3 sitewide `product` nesting is not restored.

### POT (verified)

`languages/local-seo-by-ankit-rawat.pot` was generated with WP-CLI `i18n make-pot` (text domain `local-seo-by-ankit-rawat`). Not hand-fabricated in this stage.

### `Tested up to`

Reverted to **6.7**. Stage 7 Playground 7.0.4 is **not** used as WordPress.org compatibility evidence here. CHANGELOG no longer claims 7.0 from Playground.

### Packaging

`docs/PACKAGING.md` and `bin/package.php` exclude `tests/` (hence Docker Compose), `vendor/`, Git, tooling, and stage reports including this file and `docs/INTEGRATION-TEST-PLAN.md`. No production ZIP was built in 7A.

## Files created

| Path | Purpose |
|------|---------|
| `tests/Integration/docker-compose.yml` | WordPress 7.0.4 + MariaDB 11.4 + plugin bind-mount + WP-CLI profile |
| `tests/Integration/.env.example` | Disposable admin URL/credentials template |
| `tests/Integration/.gitignore` | Ignore local `.env` |
| `tests/Integration/install-site.sh` | Later: core install, activate this plugin, attempt WooCommerce + Plugin Check |
| `tests/Integration/README.md` | How to start the stack; what it is not |
| `tests/Unit/DockerComposeConfigTest.php` | Asserts compose pins 7.0.4 and mounts the plugin (does not boot WP) |
| `docs/INTEGRATION-TEST-PLAN.md` | Exact checks once Docker works |
| `docs/STAGE-7A-REPORT.md` | This report |

Existing helpers kept: `tests/Integration/http-checks.php`, `playground-blueprint.json`. Playground is optional and not accepted for `Tested up to`.

## Tests run

Host PHP **8.5.9**:

- PHP lint (plugin PHP)
- PHPUnit **9.6.36**: OK (**53 tests, 167 assertions**) — stubs plus compose-file guards, not live WordPress
- PHPCS WordPress (exit 0; `tests/` and `bin/` excluded)
- PHPStan 1.12 level 5

These are **not** WordPress/WooCommerce/Plugin Check passes.

## Test suite review

PHPUnit loads `tests/wordpress-stubs.php` only. There is no `WP_TESTS_DOMAIN` / wp-phpunit suite.

| File | Kind |
|------|------|
| `tests/Unit/AutoloaderTest.php` | Unit / security (path confinement) |
| `tests/Unit/MigratorTest.php` | Migration (stubs, not a live `wp_options` table) |
| `tests/Unit/SettingsSanitizerTest.php` | Unit / security (URLs, types, checkbox) |
| `tests/Unit/JsonLdSecurityTest.php` | Security (script breakout, empty properties) |
| `tests/Unit/StoreCatalogTest.php` | Unit (ListItem + placement helpers) |
| `tests/Unit/PluginBootstrapTest.php` | Unit (headers, activate/deactivate against stubs) |
| `tests/Unit/AdminUiTest.php` | Unit (UI/state; not a browser) |
| `tests/Unit/DockerComposeConfigTest.php` | Scaffolding only |

`tests/Integration/*` is environment + HTTP helper. **Not** executed by `phpunit.xml.dist`.

Prior count “47 tests” was Stage 5/6. Current unit suite is larger because of Admin UI and compose-config tests. Stub count is not an integration pass.

## Tests unavailable

- Docker Compose WordPress 7.0.4 (Docker not required to be installed for 7A; stack was not started as a claimed test)
- Live WooCommerce
- WordPress Plugin Check
- PHP 7.4 runtime
- Official Plugin Check CLI

## Docker preparation

`tests/Integration/docker-compose.yml`:

- `wordpress:7.0.4-php8.3-apache` on host port **8088**
- `mariadb:11.4` volume `lsar_test_db`
- WordPress files volume `lsar_wp_html`
- Plugin mounted read-only at `wp-content/plugins/local-seo-by-ankit-rawat`
- `wpcli` service under Compose profile `tools` for later `wp plugin install`

Not in the production ZIP. Plugin runtime does not require Docker.

If the `7.0.4-php8.3-apache` tag is missing when Docker is installed, change the tag and record the real image; booting still does not equal `Tested up to`.

## WordPress integration status

**Not passed.** Configuration is ready. Do not update `Tested up to` until `docs/INTEGRATION-TEST-PLAN.md` WordPress section is executed on this stack (or equivalent MySQL/MariaDB 7.0.x).

## WooCommerce integration status

**Not passed.** `install-site.sh` will try `wp plugin install woocommerce --activate` later. A failed wordpress.org download is an environment limitation, not a reason to change production code.

## Plugin Check status

**Not run.** Same install path: `wp plugin install plugin-check --activate`, then the plan’s Plugin Check section.

## Remaining blockers

1. Docker/WSL actually available and the compose stack healthy
2. Execute `docs/INTEGRATION-TEST-PLAN.md`
3. WooCommerce ZIP reachable from the test machine
4. Plugin Check ZIP reachable
5. Only then: `Tested up to`, WooCommerce version, Plugin Check findings, release ZIP

## Final status

PREPARED FOR INTEGRATION TESTING
