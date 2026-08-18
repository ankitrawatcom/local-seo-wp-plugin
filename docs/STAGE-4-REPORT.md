# Stage 4 Report

Fixes for Stage 3 High findings H1, H2, and H3 only. Schema, hooks, business types, and caching were not changed. `docs/STAGE-3-REVIEW.md` was not modified. No Git push.

## High #1 — Migration

**Root cause:** `Migrator::migrate_from_3_3()` copied generic option names (`phone`, `country`, `business_name`, …) whenever those keys existed. Version 3.3 never stored a plugin version, so that treated unrelated site options as Local SEO data.

**Fix:** Generic keys are copied only when `local_seo_enable_schema` is present in the database. That was the only prefixed option 3.3 registered. Detection uses `get_option( 'local_seo_enable_schema', 'lsar.option.missing.v1' )` so a missing option is not confused with a stored empty value. Existing `local_seo_by_ankit_rawat_options` values are still not overwritten. Legacy rows are never deleted. `google_my_business_api_key` is still excluded. After the run, `local_seo_by_ankit_rawat_version` is set to `4.0.0`.

**Migration detection strategy:** Conservative. 3.3 had no dedicated version marker; none was invented. Marker = `local_seo_enable_schema` exists. False negatives are accepted: a 3.3 site that never saved this plugin’s settings will not import generic NAP. False-positive import of another plugin’s `phone` is rejected. Documented in `docs/ARCHITECTURE.md` and `docs/SECURITY.md`. This is not certain proof of a 3.3 install; it is the safest practical gate given 3.3’s actual options.

**Tests:** `tests/Unit/MigratorTest.php`

- Genuine 3.3 marker + NAP → values migrate; API key not copied; old keys remain.
- Generic `phone`/`country`/`business_name` without marker → not copied; keys remain in the database.
- Existing 4.0.0 option + marker + different legacy name → 4.0.0 values kept.
- Second `maybe_run()` is a no-op (version gate).

## High #2 — WooCommerce Checkbox

**Root cause:** `Settings::sanitize()` treated a missing `woocommerce_product_schema` key as false. Hidden or unposted checkboxes therefore turned product JSON-LD off.

**Fix:**

1. If the key is **absent**, keep the stored option value (`schema_enabled` is unchanged: it remains “missing = false” because that control is always on the form).
2. If the key is **present**, `Sanitize::bool()` applies (`1`/`true`/`on` → true; `0`/`''` → false).
3. The WooCommerce checkbox is preceded by a hidden `value="0"` input so an explicit uncheck is posted as `0` (WordPress-native checkbox pattern). CSS/`display:none` does not remove that pair from POST; when the box stays checked while hidden, `1` still wins.

**Tests:** `tests/Unit/SettingsSanitizerTest.php`

- A: stored true, key absent → true (other fields still sanitize).
- B: stored false, key absent → false.
- C: submitted `'1'` → true.
- D: submitted `'0'` → false.

## High #3 — Autoloader

**Root cause:** The class suffix was concatenated onto `src/` and `require`d if readable, including `..` segments.

**Fix:** Suffix must match `^[A-Za-z_][A-Za-z0-9_]*(\\[A-Za-z_][A-Za-z0-9_]*)*$`. Empty, NUL, and `..` are rejected. File is `realpath(src) + DS + PSR-4 path + .php`. After `realpath()`, the normalized path must start with the normalized `src/` directory. Mapping is deterministic: `AnkitRawat\LocalSEO\Foo\Bar` → `src/Foo/Bar.php`.

**Tests:** `tests/Unit/AutoloaderTest.php` plus bait file `tests/fixtures/traversal-bait.php`. Traversal patterns (`..`, `../`, `..\`, mixed slashes, NUL) must not set the bait flag. `Plugin` and `Sanitize` still load.

**Manual bypass check:** `..` fails before filesystem. `/` in the suffix fails the regex. `realpath` outside `src/` would fail the prefix check. No include of arbitrary paths.

## Validation

| Check | Result |
|-------|--------|
| PHP syntax (`php -l` on changed PHP) | Pass |
| PHPUnit 9.6 | **38 tests, 126 assertions, OK** |
| PHPCS (WordPress) | Pass (0 errors, 0 warnings after alignment fix in `Settings.php`) |
| PHPStan 1.12 level 5 | Pass (upgrade nag only) |
| WordPress Plugin Check | **Not available** in this environment |

## Regression Risk

- 3.3 sites that **never wrote** `local_seo_enable_schema` will not auto-import generic NAP (intentional false negative).
- Hidden WooCommerce checkbox + hidden `0` means an explicit uncheck still saves false; absence no longer wipes true.
- Legitimate namespaced classes continue to autoload; invalid class names are ignored (PHP continues the autoload stack).

## Remaining Findings

Stage 3 Medium/Low items are **not** addressed here: duplicate schema vs Yoast/WC, OfferCatalog shape, Store queries on every page, stub `LICENSE`, stale `README.md` GMB claim, `label_for`, variable-product prices, no `.pot`, no live WooCommerce tests.

H1–H3 root causes are fixed and covered by tests. No obvious remaining bypass was found for those three issues in the changed code.

**Stage 4 (three High findings only): PASS**

The plugin is **not** declared production-ready as a whole.
