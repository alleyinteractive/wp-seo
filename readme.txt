=== WP SEO ===
Contributors: alleyinteractive, mboynes, dlh
Tags: seo
Requires at least: 3.9.1
Tested up to: 4.4.0
Stable tag: 0.10.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

An SEO plugin that stays out of your way.

== Description ==

WP SEO is designed for professionals who want to build a solid foundation for an SEO-friendly website.

It allows you to create templates for the title tag, meta description, and meta keywords on your posts, pages, custom post types, archives, and more. The templates can be populated dynamically with built-in formatting tags like `#title#` or `#author_name#`. You can even allow authors to create custom title and meta values for individual entries.

Meanwhile, it leaves other features like Open Graph metadata and XML sitemaps to more-specialized plugins.

For developers, WP SEO is welcoming. It applies filters all over the place, and extending the plugin with your own custom formatting tags is a cinch.

== Installation ==

1. Upload to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Visit Settings > SEO to begin setting up the plugin.

== Usage ==

WordPress SEO allows you to customize the default format of `<title>` tags, `<meta>` descriptions, and `<meta>` keywords for your home page, single posts and custom post types, taxonomy and date archives, and more.

Most of this customization happens on the WP SEO settings page, which you can access in the Dashboard at Settings > SEO.

=== Basic usage ===

The settings page has headings for each group of fields you can customize. Groups are hidden by default; click the heading to expand it.

For example, to customize the defaults for your Posts, use the fields under the heading "Single Post Defaults." To customize the default for author archives, use the fields under the heading "Author Archives."

When you're done editing fields, save your changes using the button at the bottom of the page.

A "format" can be regular text. For example, you could set the `<title>` tag of all date archives to be "Posts from our time machine."

==== Formatting tags ====

The power of formats, though, is in formatting tags, which create dynamic text that responds to the content of the page.

A formatting tag looks like `#site_name#` or `#author#` or `#archive_date#`.

With formatting tags, setting the `<title>` tag format of your date archive to "Time machine set to #archive_date#" would display something like "Time machine set to September 2014" -- and the date would change automatically based on the archive the user looked at.

Some more examples:

* If you wanted to include the author name and tags by default in your `<meta>` keywords for all Posts, you could go to "Single Post Defaults" and, under "Meta Keywords Format," use "#author#, #tags#."

* If you wanted to use category descriptions in the `<meta>` description field, you could go to "Category Archives" and, under "Meta Description Format", use #term_description#."

* If you had a custom "Review" post type and wanted the `<title>` tag to include the date each review was last updated, you could go to "Single Review Defaults" and, under "Title Tag Format," use "#title# Review (Updated #date_modified#).

These formatting tags are available out-of-the-box:

* `#archive_date#`
* `#author#`
* `#categories#`
* `#date_modified#`
* `#date_published#`
* `#excerpt#`
* `#post_type_plural_name#`
* `#post_type_singular_name#`
* `#search_term#`
* `#site_description#`
* `#site_name#`
* `#tags#`
* `#term_description#`
* `#term_name#`
* `#title#`

More details about each tag are available under the "Help" button in the upper-right corner of the settings page.

=== Per-entry and per-term fields ===

The WP SEO Settings page allows you to set global defaults. But WP SEO also supports setting custom title, description, and keyword values for your site's individual entries and taxonomy terms.

You can enable these fields on a per-post type basis under the "Post Types" heading on the WP SEO Settings page. Check the box next to a post type to enable the fields, and the fields will appear on the "Edit" page for each post type.

You can enable the fields on a per-taxonomy basis under the "Taxonomies" heading on the WP SEO Settings page. Check the box next to a taxonomy to enable the fields, and the fields will appear in the "Add New" form for each taxonomy and the "Edit" page for each taxonomy term.

=== Custom meta tags ===

In addition to the core support for `<meta>` description and keywords, WP SEO allows you to set custom `<meta>` tags that are used throughout your site. These are managed under the "Other Meta Tags" heading on the WP SEO Settings page.

For example, if you wanted to add a Google Verification `<meta>` tag for your site, you could go to "Other Meta Tags," add "google-site-verification" under the "Name" field, and the value under the "Content" field.

Use the "Add another" button to add as many custom `<meta>` tags as you need.

Use the "Remove group" button, or just remove the field content, to remove a custom `<meta>` tag.

== Screenshots ==

1. Settings page
2. The on-screen documentation for the included formatting tags
3. Setting title and meta fields for a post

== Changelog ==

= 0.10.0 =
* Add support for title tags generated with `wp_get_document_title()`, introduced in WordPress 4.4.

= 0.9.1 =
* Fix an error that could occur when attempting to process invalid meta tag values.

= 0.9.0 =
* Initial release.
