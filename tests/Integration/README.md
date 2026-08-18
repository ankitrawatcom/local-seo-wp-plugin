# Integration environment

This directory is **development-only**. It is excluded from the production ZIP.

## What this is not

- Not a passed WordPress integration test
- Not a passed WooCommerce test
- Not a Plugin Check run
- Not permission to change `Tested up to`

PHPUnit tests under `tests/Unit` use WordPress function stubs. They are not this environment.

## Docker Compose (preferred once Docker exists)

From `tests/Integration`:

```
docker compose up -d
```

Then, with Docker working:

```
bash install-site.sh
```

On Windows PowerShell, equivalent WP-CLI calls:

```
docker compose --profile tools run --rm wpcli core install --url=http://127.0.0.1:8088 --title="Local SEO Integration" --admin_user=admin --admin_password=admin --admin_email=integration@example.test --skip-email
docker compose --profile tools run --rm wpcli plugin activate local-seo-by-ankit-rawat
docker compose --profile tools run --rm wpcli plugin install woocommerce --activate
docker compose --profile tools run --rm wpcli plugin install plugin-check --activate
```

| Service | Image (pinned intent) | Role |
|---------|----------------------|------|
| `db` | `mariadb:11.4` | Disposable test database (`lsar_test_db` volume) |
| `wordpress` | `wordpress:7.0.4-php8.3-apache` | WordPress 7.0.4 |
| `wpcli` | `wordpress:cli-php8.3` | Optional tools profile |

The plugin working tree is mounted read-only at `/var/www/html/wp-content/plugins/local-seo-by-ankit-rawat`.

Site URL: `http://127.0.0.1:8088/`

Tear down: `docker compose down -v`

If `wordpress:7.0.4-php8.3-apache` is missing from Docker Hub, switch the tag and record the actual image in the Stage report. Booting the container is not a compatibility claim.

WooCommerce and Plugin Check are installed on demand from wordpress.org. If those ZIPs cannot be downloaded, stop and document the limitation. Do not change production plugin code to work around a missing download.

## Playground (optional, not accepted for Tested up to)

```
npx --yes @wp-playground/cli@latest start --wp=7.0.4 --skip-browser --blueprint=tests/Integration/playground-blueprint.json
```

Playground uses SQLite/WASM PHP. Stage 7A does **not** treat it as the Docker/MySQL integration environment.

## HTTP helper

`php tests/Integration/http-checks.php http://127.0.0.1:8088 admin admin` is a convenience client. It is not a substitute for `docs/INTEGRATION-TEST-PLAN.md`.
