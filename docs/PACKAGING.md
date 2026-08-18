# Production packaging

From the plugin root:

```
php bin/package.php
```

Creates `dist/local-seo-by-ankit-rawat-<version>.zip` with a top-level folder `local-seo-by-ankit-rawat/`.

## Included

* `local-seo-by-ankit-rawat.php`
* `uninstall.php`
* `readme.txt`, `README.md`, `CHANGELOG.md`, `LICENSE`
* `src/`, `assets/`, `languages/`
* Operator docs: `docs/ARCHITECTURE.md`, `docs/SECURITY.md`, `docs/REGRESSION-AUDIT.md`

## Excluded

* `tests/`, `vendor/`, `bin/`, `tools/`, `dist/`
* Composer, PHPCS, PHPStan, PHPUnit configs
* Git metadata and editor files
* Stage/audit reports (`docs/AUDIT.md`, `docs/STAGE-*.md`, `docs/INTEGRATION-TEST-PLAN.md`, this file)
* Docker Compose and other files under `tests/`

Do not zip the working tree by hand if `vendor/` is present. Do not publish or upload the ZIP from this script; it only builds the archive locally.
