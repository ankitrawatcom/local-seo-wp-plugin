# Integration test plan (Docker / WSL)

Execute this plan **after** Docker (or WSL + Docker) can run `tests/Integration/docker-compose.yml`.

Do **not** mark `Tested up to` until the WordPress section below passes on the intended 7.0.x image.

Do **not** treat `docker compose up` or a container boot as a pass.

Do **not** use a production WordPress site.

Default URL after compose: `http://127.0.0.1:8088/`  
Default admin (after `install-site.sh`): `admin` / `admin`

PHPUnit in `tests/Unit` uses stubs. Those results are **not** this plan.

---

## 0. Bring the stack up

1. `cd tests/Integration && docker compose up -d`
2. Confirm WordPress responds (installer or installed site). Record the **exact** image tags and `wp core version`.
3. `bash install-site.sh` (or the PowerShell WP-CLI commands in `tests/Integration/README.md`).
4. If WooCommerce or Plugin Check cannot be downloaded, **stop those sections**, record the URL/error, and do not change production PHP to work around it.

---

## WordPress

| # | Test | Pass criteria |
|---|------|----------------|
| W1 | Activation | Plugin activates without fatal. `local_seo_by_ankit_rawat_version` is `4.0.0`. |
| W2 | Deactivation | Settings remain. No frontend JSON-LD after deactivate. Reactivate restores hooks. |
| W3 | Settings page | `manage_options` only. Labels, `label_for`, nonce, `settings_errors`. |
| W4 | Settings save | NAP, type, URLs, geo persist. Invalid lat/lng/URLs emptied. |
| W5 | Schema enable | With schema on and real NAP, public `head` contains `application/ld+json`. JSON parses. |
| W6 | Schema disable | No this-plugin LocalBusiness JSON-LD. |
| W7 | Business types | Each allowlisted type. Invalid type becomes LocalBusiness. |
| W8 | Address / logo / images / social / geo | Output only when valid. `javascript:` URLs dropped. Geo only if both coords valid. |
| W9 | Admin UI | CSS/JS load only on this screen. Product schema fields **hidden without WooCommerce**. |
| W10 | Admin JavaScript | No console errors. Fields do **not** require type Store. |
| W11 | No PHP errors | `WP_DEBUG` / `WP_DEBUG_LOG`: no notices, warnings, fatals on admin or front. |
| W12 | Migration genuine 3.3 | Marker `local_seo_enable_schema` present. NAP copied. API key not copied. Generics remain. Version set. Second run does not overwrite. |
| W13 | Migration unrelated | Generics without marker **not** imported. |
| W14 | Migration existing 4.0 | Existing namespaced values kept. |
| W15 | Uninstall | Namespaced options + marker + old transient gone. Generic `phone` / `business_name` remain. Repeat with no options present. |

---

## WooCommerce

Skip the whole section if install fails. Status: **not passed** until every row is executed on a real store.

| # | Test | Pass criteria |
|---|------|----------------|
| C1 | WooCommerce activation | Store runs. Plugin settings show product-schema fields for **any** business type. |
| C2 | Simple product | Published product, price, SKU, image, plain description → Product JSON-LD on product page when setting on. |
| C3 | Variable product + variation | Parent page emits Product; price uses `get_price()` or min variation; no invented USD. |
| C4 | Sale price | Offer uses the active price WooCommerce exposes (`get_price()`). Record if regular vs sale is incomplete. |
| C5 | Currency | Blank override → `get_woocommerce_currency()`. Override `EUR` appears as `priceCurrency`. |
| C6 | Image / no image | Image URL only when attachment exists. |
| C7 | HTML description | Tags stripped in JSON-LD. |
| C8 | Malicious description `</script><script>alert(1)</script>` | Valid JSON. Encoded output has no raw `</script`. |
| C9 | Unicode / quotes / encoded HTML | Valid JSON. No script breakout. |
| C10 | Product schema setting on | Product page JSON-LD present (LocalBusiness or Store type). |
| C11 | Product schema setting off | No this-plugin Product JSON-LD. Catalog also absent. |
| C12 | Store type + catalog | `hasOfferCatalog` / `ListItem` on **front, home, shop only**. Not on product page. Not on a random post. Max 5 published. |
| C13 | Non-Store type | Product JSON-LD still follows the product setting. No Store catalog. |
| C14 | Unpublished / missing price | Draft/private not in catalog. Empty price omits `price` (no fake currency). |

---

## Plugin Check

| # | Test | Pass criteria |
|---|------|----------------|
| P1 | Install `plugin-check` | Plugin appears in admin. |
| P2 | Run against `local-seo-by-ankit-rawat` | Save the full error/warning/info report. |
| P3 | Review findings | Fix genuine production issues. Do not suppress blindly. |

Until P2 runs, Plugin Check status is **not run**.

---

## Security (live)

| # | Test | Pass criteria |
|---|------|----------------|
| S1 | `</script>` in business name / product description | Hex-encoded or stripped; no breakout. |
| S2 | HTML / encoded HTML / newlines / Unicode in NAP | Valid JSON-LD. Admin escaped. |
| S3 | Malformed URLs | Not saved; not printed. |
| S4 | Invalid coordinates | No `GeoCoordinates`. |
| S5 | Invalid business type POST | Stored/output as LocalBusiness. |
| S6 | Direct POST extras | Unknown keys dropped. Capability + nonce required. |
| S7 | Unrelated `phone` option | Not imported without 3.3 marker. |

---

## After a real pass

1. Set `Tested up to` to the WordPress **major.minor** actually tested (for example `7.0` if 7.0.4 passed). Never 7.1 beta.
2. Record WooCommerce version.
3. Attach Plugin Check output to the next stage report.
4. Rebuild the production ZIP with `php bin/package.php` (still do not publish from this stage unless asked).
