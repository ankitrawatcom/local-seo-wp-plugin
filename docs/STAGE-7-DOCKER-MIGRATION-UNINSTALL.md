# Stage 7 Docker: migration, uninstall, settings regression

Development-only record of the WordPress 7.0.4 / MariaDB 11.4 / WooCommerce 11.0.1 integration run. Production plugin source was not changed.

Environment: `tests/Integration/docker-compose.yml`, site `http://127.0.0.1:8088/`.

## Migration contract (4.0.0)

- 3.3 had **no** version option. The unique 3.3 Settings API key is `local_seo_enable_schema`.
- `Migrator::has_legacy_install_evidence()` is true when that option **exists** (`get_option` default `lsar.option.missing.v1`), including a stored `0`.
- `Migrator::maybe_run()` (activation + `admin_init` priority 1) returns immediately if `local_seo_by_ankit_rawat_version` is `>= 4.0.0`.
- Generic keys are copied only with marker evidence. Existing namespaced values are not overwritten. `google_my_business_api_key` is never copied.
- After a run: version set to `4.0.0`; transient `local_seo_json_ld_schema` deleted; **marker and generic 3.3 options are not deleted**.

## Results

| Scenario | Result |
|----------|--------|
| Genuine 3.3 (`local_seo_enable_schema=1` + NAP) | PASS — copied into `local_seo_by_ankit_rawat_options` with mapped keys; API key excluded; generics remained; marker remained `1`; version `4.0.0`; transient gone |
| Generics without marker | PASS — namespaced NAP empty/defaults; generics unchanged |
| Existing 4.0 values | PASS — `Already Four` / `111-222-3333` / `Restaurant` kept |
| Idempotent `maybe_run` | PASS — second call no-op after version gate |
| Uninstall `uninstall.php` | PASS — namespaced option + version + marker + transient removed; `phone`, `business_name`, API key, `blogname`, `woocommerce_version`, unrelated option kept; 51 tables unchanged |
| Empty uninstall (options already absent) | PASS — no error; generics kept |
| Settings: WC schema stays ON when another field is saved | PASS (`options.php`) |
| Settings: explicit uncheck OFF, then re-enable | PASS |
| Settings UI: WooCommerce fields visible for LocalBusiness (not Store-only) | PASS (`lsar-woocommerce-available`, `lsarAdmin.woocommerceActive=1`, checkbox present) |

Disposable DB copy: `mariadb-dump` → restore with `mariadb … source`. Helpers live under `tests/Integration/`. SQL/cookie dumps are gitignored.
