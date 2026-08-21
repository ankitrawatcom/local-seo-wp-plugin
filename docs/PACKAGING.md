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

## Excluded

* `docs/` (architecture and security docs are for maintainers, not the production ZIP)
* `tests/`, `vendor/`, `bin/`, `tools/`, `dist/`
* Composer, PHPCS, PHPStan, PHPUnit configs
* Git metadata and editor files

Do not zip the working tree by hand if `vendor/` is present. Do not publish or upload the ZIP from this script; it only builds the archive locally.
