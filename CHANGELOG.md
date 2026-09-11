# Changelog

All notable changes to `WP SEO` will be documented in this file.

## 2.0.0 ##
Just kidding about the "no plans for additional feature development" thing, we're back!

* Breaking change: Plugin behavior is organized into features, and every feature is opt-in. Nothing runs until it is enabled in code with the `wp_seo_enable_feature` filter or the `wp_seo_enable_{handle}` filter named for a specific feature. Features migrated so far: Open Graph (`wp_seo_enable_open_graph`).
* Added: A read-only WP SEO Features admin screen listing every registered feature and whether it is running.
* Added: `wp_seo_register_features` action, for adding features to the plugin without editing it.
* Added: Configurable site-wide default Open Graph image, used as a fallback when a post has no explicit or featured image.
* Added: Support for canonical URL and robots meta.
* Changed: Rebase plugin on @alleyinteractive/create-wordpress-plugin.
* Removed: Support for keywords which are no longer used by search engines.

## 1.0.0 ##
This is the final major version release planned for WP SEO. There are no plans for additional feature development.

* Breaking change: Always filter the title, description, and keyword formats. Props Sean Fisher.
* Added: `wp_seo_after_post_meta_fields` action, `wp_seo_saveable_fields` filter, and `wp_seo_character_count_fields` filter. Props Sean Fisher.
* Added: Composer installation support. Props Griffen Fargo.

## 0.13.0 ##
* Added: 'wp_seo_meta_field_content' filter. Props Arun Chaitanya Jami.
* Changed: Improve post meta box UI when placed in the sidebar. Props John Blackbourn.
* Changed: Raised minimum WordPress version to 4.6.0.

## 0.12.0 ##
* Added: `##thumbnail_url##` formatting tag, which represents the URL for the featured image of the content being viewed.
* Added: Formatting Tag "Safe Mode" for preventing the display of unrecognized formatting tags.
* Added: Print an HTML comment next to WP SEO meta tags to help spot them while debugging.
* Fixed: Added translator comments to strings with placeholders.
* Changed: Raised minimum WordPress version to 4.4.0.

## 0.11.3-beta1 ##
* Changed: Announced planned changes to default filters.

## 0.11.2 ##
* Fixed: On the settings page, make the "Search" section heading's font size consistent with other section headings.

## 0.11.1 ##
* Fixed: Update to latest Underscore `_.template()` syntax.

## 0.11.0 ##
* Add hooks for customizing post- and term-field markup.

## 0.10.0 ##
* Add support for title tags generated with `wp_get_document_title()`, introduced in WordPress 4.4.

## 0.9.1 ##
* Fix an error that could occur when attempting to process invalid meta tag values.

## 0.9.0 ##
* Initial release.
