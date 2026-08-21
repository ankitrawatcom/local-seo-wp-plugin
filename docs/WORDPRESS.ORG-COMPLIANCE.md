# WordPress.org Plugin Directory Compliance — Local SEO By Ankit Rawat 4.0.1

Audited: 2026-08-22

## 1. Licensing

GPL-2.0-or-later. `LICENSE` file present. Plugin header declares `License: GPL-2.0-or-later`. All source is original or GPL-compatible.

PASS

## 2. Human-readable source

All PHP, JS, and CSS is unminified, unobfuscated, and readable. No compiled/transpiled assets. No build step required.

PASS

## 3. Third-party libraries

None bundled in production. `vendor/` contains dev-only tools (PHPUnit, PHPCS, PHPStan) and is excluded from the release ZIP via `bin/package.php` and `.gitignore`.

PASS

## 4. External services

The plugin makes no external HTTP requests. No API calls, no remote fetches, no CDN assets, no analytics endpoints, no update checks beyond WordPress.org.

PASS

## 5. Tracking / telemetry

None. No usage tracking, no analytics, no opt-in telemetry, no phone-home.

PASS

## 6. Trialware / serviceware

No features locked behind payment, licensing, or external service. All functionality is fully available.

PASS

## 7. Dashboard behavior

One top-level admin menu page (`Local SEO By Ankit Rawat`) at position 100. No dashboard widgets, no admin notices outside the settings page, no nag screens, no promotional banners.

PASS

## 8. Public-site behavior

JSON-LD output only (when enabled). No frontend CSS, JS, widgets, shortcodes, credits, or links injected into the public site.

PASS

## 9. Remote requests

None. Confirmed by grep for `wp_remote_*`, `curl`, `file_get_contents` with URLs, `wp_safe_remote_*`. Zero hits in production source.

PASS

## 10. Executable code

No `eval`, `exec`, `shell_exec`, `system`, `passthru`, `proc_open`, `popen`, `assert` with string argument, or `create_function`. No remote code download or execution.

PASS

## 11. Security

- All settings require `manage_options` capability.
- Settings saves use WordPress Settings API (nonce via `settings_fields`, capability enforced by `options.php`).
- No REST API endpoints, no AJAX handlers.
- All admin output escaped with `esc_html`, `esc_attr`, `esc_textarea`.
- JSON-LD encoded with `JSON_HEX_TAG` to prevent script breakout.
- Business type is allowlisted.
- URLs restricted to http/https schemes.
- Autoloader has path traversal protection.
- No direct superglobal access.
- No `$wpdb` queries.

PASS

## 12. Privacy

`wp_add_privacy_policy_content` registered. Explains that NAP data is stored locally and not sent to external services. No cookies set. No user data collected.

PASS

## 13. Uninstall

`uninstall.php` present. Deletes `local_seo_by_ankit_rawat_options`, `local_seo_by_ankit_rawat_version`, the legacy transient, and `local_seo_enable_schema`. Does not delete generic option names that may belong to other plugins.

PASS

## 14. Readme

`readme.txt` follows WordPress.org format. Accurate description, installation, FAQ, changelog, upgrade notice, tags, and metadata. No unsupported claims.

PASS

## 15. Versioning

Consistent version 4.0.1 across: plugin header, `LOCAL_SEO_BY_ANKIT_RAWAT_VERSION`, `Plugin::VERSION`, readme `Stable tag`.

PASS

## 16. Stable tag

`Stable tag: 4.0.1` matches the plugin header `Version: 4.0.1`.

PASS

## 17. Tested up to

`Tested up to: 7.1`. WordPress 7.1 compatibility verified via API surface analysis. No deprecated APIs used.

PASS

## 18. Coding standards

WordPress Coding Standards configured via `phpcs.xml.dist`. PSR-4 class filenames excluded (documented). Text domain, prefix, and minimum WP version configured.

PASS

## 19. Accessibility

Standard WordPress Settings API form (`form-table`, `regular-text`, `select`, `textarea`, checkboxes). All form fields have `label_for` attributes. No custom widgets or complex UI. Inherits WordPress admin accessibility.

PASS

## 20. Translation

Text domain `local-seo-by-ankit-rawat` with `Domain Path: /languages`. POT file present. All user-facing strings wrapped in `__()`, `esc_html__()`, or `esc_html_e()`.

PASS

## 21. Final package contents

Production ZIP (`bin/package.php`) contains only:

- `local-seo-by-ankit-rawat.php`, `uninstall.php`
- `readme.txt`, `README.md`, `CHANGELOG.md`, `LICENSE`
- `src/` (9 PHP files)
- `assets/` (admin.css, admin.js)
- `languages/` (index.php, .pot file)

Excluded: `.git`, `tests/`, `vendor/`, `bin/`, `tools/`, `dist/`, `docs/`, config files, editor files.

PASS

## 22. Known risks

None identified. The plugin is lightweight and uses only stable WordPress APIs.

## 23. Items requiring manual WordPress.org verification

- The `Tested up to: 7.1` claim is based on API surface analysis, not a full WordPress 7.1 integration test environment. A smoke test on a live WordPress 7.1 installation is recommended before submission.
- WordPress.org reviewers may verify that the plugin does not duplicate core functionality or conflict with other plugins.

## Summary

All 21 compliance checks pass. No blockers for WordPress.org submission.
