=== Local SEO By Ankit Rawat ===
Contributors: ankitrawat
Tags: local seo, schema, local business, woocommerce
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 4.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Outputs LocalBusiness JSON-LD from a settings screen, with optional WooCommerce product structured data.

Official website: [Local SEO By Ankit Rawat](https://ankitrawat.com/products/local-seo-by-ankit-rawat/)

== Description ==

Local SEO By Ankit Rawat adds JSON-LD structured data for a single business location. You enter name, address, phone, geo coordinates, logo, images, social profile URLs, and price range. When schema is enabled, those values are printed in the site `head` as `application/ld+json`.

This plugin does **not** connect to Google Business Profile, import reviews, manage opening hours, or change canonical URLs or robots directives.

= What it does =

* Settings screen (capability: `manage_options`) for one business profile.
* Optional sitewide LocalBusiness-family JSON-LD for: LocalBusiness, Restaurant, Hotel, ProfessionalService, Store.
* Optional WooCommerce Product JSON-LD on product pages (any business type, when WooCommerce is active and the setting is on).
* When the type is Store and product schema is enabled, up to five published products are listed in a Schema.org `OfferCatalog` (`hasOfferCatalog` with `ListItem` entries) on the **front page**, the **posts index (home)**, and the **WooCommerce shop**. The catalog is not added on every URL.

Developers can change catalog placement with the `local_seo_by_ankit_rawat_embed_store_catalog` filter. Version 3.3 attached an invalid `product` property on essentially every URL; 4.0.0 does not restore that structure.

= What it does not do =

* Google Business Profile / Google My Business API synchronization.
* Review collection, aggregation, or review schema from manually entered ratings.
* Opening hours, menus, reservations, or hotel amenity schema.
* Multiple locations.
* Frontend widgets, shortcodes, or extra CSS/JS on the public site.

= Developer filters =

See `docs/ARCHITECTURE.md` for hook names. You can disable output if another SEO plugin already emits equivalent JSON-LD. Catalog placement is controlled with `local_seo_by_ankit_rawat_embed_store_catalog` (default: front page, home, and shop).

== Installation ==

1. Upload the `local-seo-by-ankit-rawat` folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Open **Local SEO By Ankit Rawat** in the admin menu.
4. Enter business details and enable schema only when the data is accurate.

== Upgrade Notice ==

= 4.0.1 =
Maintenance release: verified WordPress 7.1 compatibility, updated official product page URL. No settings or schema changes.

= 4.0.0 =
Major release: settings move into a namespaced option. Existing 3.3 values are copied automatically. Old generic options are not deleted. Google Business Profile claims in earlier readmes were never implemented and have been removed from the documentation.

== Frequently Asked Questions ==

= Will my 3.3 settings be kept? =

Yes. On first admin load or activation after upgrade, values are copied into `local_seo_by_ankit_rawat_options`. The old option keys remain in the database.

= Why is my Google API key missing from the new screen? =

Version 3.3 stored `google_my_business_api_key` but never used it. 4.0.0 does not copy that secret into the new option and does not display it. The old option is left in the database until you delete it yourself.

= How do I test the schema? =

Use [Google’s Rich Results Test](https://search.google.com/test/rich-results) after enabling schema and filling in real NAP data.

= Does this slow the site down? =

There is no frontend CSS or JavaScript. Schema is built from one option (and, for Store catalogs, up to five WooCommerce product queries). Version 3.3’s hour-long transient was removed because it served stale data after settings changes.

= Where does the Store product catalog appear? =

By default only on the front page, the posts index (home), and the WooCommerce shop. Product pages already have Product JSON-LD and do not receive the catalog. To change that, use the `local_seo_by_ankit_rawat_embed_store_catalog` filter.

= Can this duplicate Yoast, Rank Math, or WooCommerce schema? =

Yes. Use the `local_seo_by_ankit_rawat_output_local_schema` and `local_seo_by_ankit_rawat_output_product_schema` filters to turn off this plugin’s output when another plugin already provides it.

== Changelog ==

= 4.0.1 =
* Verified WordPress 7.1 compatibility (no code changes required; Settings API, Options API, and wp_head output are unaffected by 7.1 changes).
* Added optional support/donation button on the settings page (voluntary, powered by Razorpay).
* Updated official product page URL.
* Cleaned development artifacts from repository.

= 4.0.0 =
* Namespaced settings option and 3.3 migration (legacy options are not deleted).
* JSON-LD encoding uses JSON_HEX_TAG to prevent script breakout.
* No placeholder NAP in public schema.
* Allowlisted business types.
* WooCommerce detection no longer uses `woocommerce_params`.
* Store products emitted as OfferCatalog with ListItem entries (limit 5, published only) on the front page, home, and shop by default.
* Documentation matches actual features.
* GPL license file, uninstall handler, tests, and coding-standard configs.

= 3.3 =
* Compatibility fix.

= 3.2 =
* WooCommerce product schema and modular file layout.

= 3.1 =
* Sanitization and caching tweaks.

= 3.0 =
* Geo fields and unused Google My Business settings fields.

== Upgrade From 3.3 ==

1. Back up the database.
2. Install 4.0.0 over 3.3 (same plugin folder).
3. Activate or open wp-admin so migration can run.
4. Confirm settings on the Local SEO screen.
5. Do not expect Google Business Profile sync; it was never implemented.

== Troubleshooting ==

* Schema missing: confirm “Enable schema” is checked and you are viewing the public site, not wp-admin.
* Product schema missing: WooCommerce must be active, product schema enabled, and you must be on a product page. The setting is shown whenever WooCommerce is active, for any business type.
* Store catalog missing: business type must be Store, product schema enabled, published products must exist, and you must be on the front page, home, or shop (unless a filter changes placement).
* Stale 3.3 schema: the old `local_seo_json_ld_schema` transient is deleted during migration.

== External Services ==

This plugin includes an optional "support the developer" link on its admin settings page. When clicked, it opens an external payment page hosted by [Razorpay](https://razorpay.com/).

* The link (`https://razorpay.me/@hridyaa`) appears **only** on the plugin's own settings page, not on the public site.
* **No external scripts are loaded.** The link is a standard HTML anchor; no JavaScript from Razorpay runs on your site.
* Payment processing is handled entirely by Razorpay. The plugin does not process, store, or transmit payment information.
* Supporting development is completely voluntary and does not affect plugin functionality.
* Razorpay's [Terms of Service](https://razorpay.com/terms/) and [Privacy Policy](https://razorpay.com/privacy/) apply to any payment made through the link.

== Screenshots ==

1. Settings screen for business details and schema toggle.
