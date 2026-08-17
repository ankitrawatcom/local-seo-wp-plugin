# Security — Local SEO By Ankit Rawat 4.0.0

## Trust boundaries

* **Administrators** (`manage_options`) may change business NAP and schema toggles. That data is treated as untrusted for output encoding (it is still sanitized on save).
* **Public visitors** receive JSON-LD only. They have no write path.
* **WooCommerce product content** (name, description, SKU) is merchant-controlled and is stripped to text then JSON-encoded with `JSON_HEX_TAG`.
* **No external HTTP.** The plugin does not call Google, does not fetch URLs, and does not disable SSL verification because it makes no requests.

## Authorization

* Menu, settings, and admin assets require `manage_options`.
* Settings saves go through `options.php` (core capability + Settings API nonce).
* Nonces are not used as a substitute for capability checks.
* There are no REST routes or `admin-ajax.php` handlers.

## Input validation / sanitization

`Admin\Settings::sanitize()` is the `register_setting` callback.

* Business type: allowlist only.
* Text: `sanitize_text_field`.
* Phone: restricted character set, max length 32.
* URLs: `esc_url_raw` plus an http/https scheme check. `javascript:` and `data:` are dropped.
* Images / social profiles: URL lists, unique.
* Latitude −90…90, longitude −180…180, both required for `GeoCoordinates`.
* Currency: `^[A-Z]{3}$`.
* Booleans: missing checkbox → false.

## Output escaping

* Admin HTML: `esc_html`, `esc_attr`, `esc_textarea`.
* JSON-LD: `JsonLd::encode()` with `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE`. HTML escaping is not applied to JSON (it would corrupt the graph).

## JSON-LD script context

Values such as `</script><script>alert(1)</script>` must not appear raw inside the script element. Hex-encoding `<` / `>` prevents tag breakout. Unit tests cover this.

## Migration security

* Runs on activation and `admin_init` only.
* Idempotent via `local_seo_by_ankit_rawat_version`.
* Does not copy `google_my_business_api_key` into `local_seo_by_ankit_rawat_options`.
* Does not delete that key (rollback). It is not displayed.
* Place ID may be copied to `place_id` for the operator’s records and is not printed in JSON-LD.
* Manual ratings may be copied to `legacy_*` keys and are not printed (3.3 never output them; self-serving review markup is also against Google’s guidelines).

## Uninstall

Removes namespaced 4.0 options and the unique 3.3 key `local_seo_enable_schema`. Generic 3.3 names (`phone`, `business_name`, …) are left alone so another plugin’s data is not destroyed.

## WooCommerce

Product HTML is not trusted. Prices must be numeric. Only `publish` products are included in the Store catalog. Default catalog size is 5.

## Database

No `$wpdb` queries. Options API only.

## Secrets

4.0.0 does not store new API keys. Do not paste Google API keys into this plugin; they are not used.
