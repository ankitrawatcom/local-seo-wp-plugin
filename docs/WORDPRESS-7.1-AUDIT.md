# WordPress 7.1 Compatibility Audit — Local SEO By Ankit Rawat 4.0.1

Audited: 2026-08-22

## Plugin API surface

| API | Location |
|-----|----------|
| `add_menu_page` (dashicons-location-alt) | `src/Admin/Settings.php` |
| `register_setting` / Settings API | `src/Admin/Settings.php` |
| `wp_enqueue_style` / `wp_enqueue_script` (dep: jquery) | `src/Admin/Assets.php` |
| `wp_localize_script` | `src/Admin/Assets.php` |
| `add_action('wp_head', ...)` | `src/Schema/LocalBusiness.php`, `src/Schema/WooCommerce.php` |
| Options API | `src/Plugin.php`, `src/Migration/Migrator.php`, `uninstall.php` |
| `wp_add_privacy_policy_content` | `src/Plugin.php` |
| `load_plugin_textdomain` | `src/Plugin.php` |
| `current_user_can` | `src/Admin/Settings.php`, `src/Admin/Assets.php` |

No REST API, AJAX, cron, Block Editor, jQuery UI, or custom database usage.

## WordPress 7.1 changes

| Change | Classification | Reason |
|--------|---------------|--------|
| jQuery UI 1.13.3 to 1.14.2 | NOT RELEVANT | Plugin uses only jQuery core |
| Post list table row headers | NOT RELEVANT | Plugin has no list tables |
| Abilities API | NOT RELEVANT | No abilities registered |
| SVG Icon API | DEFERRED | Dashicons still supported |
| Design System / wp-theme CSS | DEFERRED | Optional adoption |
| Persistent admin bar in editors | NOT RELEVANT | No editor integration |
| DataViews / DataForm | NOT RELEVANT | No data views |
| Iframed editor for all themes | NOT RELEVANT | No editor integration |
| PHP minimum unchanged (7.4) | NOT RELEVANT | Already compliant |
| REST API changes | NOT RELEVANT | show_in_rest is false |
| Deprecated functions | NOT RELEVANT | All block-editor-only |
| Settings API / Options API | NOT RELEVANT | No changes to these APIs |
| wp_head hook | NOT RELEVANT | No changes |
| Admin accessibility improvements | TEST ONLY | Standard form-table inherits improvements |
| Admin bar visual refresh | TEST ONLY | Standard admin chrome |

## Verdict

No code changes required for WordPress 7.1 compatibility. The plugin uses exclusively stable, classic WordPress APIs that were not modified.

## Actions taken

1. Updated `Tested up to` from 7.0 to 7.1.
2. Bumped version from 4.0.0 to 4.0.1.
3. Updated changelog and upgrade notice.

## Deferred improvements (not required for 7.1)

- Replace `wp_localize_script` with `wp_add_inline_script` (cosmetic).
- Consider SVG menu icon via the new Icon Registration API (cosmetic).
- Consider `wp-theme` design token CSS dependency (cosmetic).
