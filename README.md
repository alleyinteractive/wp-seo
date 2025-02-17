# WP SEO

**Contributors:** alleyinteractive\
**Tags:** alleyinteractive, wp-seo\
**Stable tag:** 0.0.0\
**Requires at least:** 6.3\
**Tested up to:** 6.7\
**Requires PHP:** 8.2\
**License:** GPL v2 or later



Enterprise SEO for large, performant sites.

---

## Description

WP SEO is designed for professionals who want to build a solid foundation for an SEO-friendly website.

It allows you to create templates for the title tag and meta description on your posts, pages, custom post types, archives, and more. The templates can be populated dynamically with built-in formatting tags like `#title#` or `#Alley Interactive#`. You can even allow authors to create custom title and meta values for individual entries.

Meanwhile, it leaves other features like Open Graph metadata and XML sitemaps to more specialized plugins.

For developers, WP SEO is welcoming. It applies filters all over the place, and extending the plugin with your own custom formatting tags is a cinch.

---

## Installation

You can install the package via Composer:

```bash
composer require alleyinteractive/wp-seo
```

Or manually:

1. Upload to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Visit **Settings > SEO** to begin setting up the plugin.

---

## Usage

WP SEO allows you to customize the default format of `<title>` tags and `<meta>` descriptions for your home page, single posts and custom post types, taxonomy and date archives, and more.

Most of this customization happens on the WP SEO settings page, which you can access in the Dashboard at **Settings > SEO**.

### Basic Usage

The settings page has headings for each group of fields you can customize. Groups are hidden by default; click the heading to expand it.

For example:

- To customize the defaults for your **Posts**, use the fields under **Single Post Defaults**.
- To customize the defaults for **author archives**, use the fields under **Author Archives**.

After editing fields, save your changes using the button at the bottom of the page.

A "format" can be regular text. For example, you could set the `<title>` tag of all date archives to be:

> "Posts from our time machine."

#### Formatting Tags

The power of formats comes from **formatting tags**, which dynamically generate text based on page content.

A formatting tag looks like `#site_name#`, `#author#`, or `#archive_date#`.

Example:

- Setting the `<title>` tag format of your **date archive** to:
  > "Time machine set to #archive\_date#" would display something like: "Time machine set to September 2014" The date updates automatically based on the archive the user is viewing.

WP SEO includes many formatting tags out-of-the-box:

```
#archive_date#  #author#  #categories#  #date_modified#  #date_published#
#excerpt#  #post_type_plural_name#  #post_type_singular_name#  #search_term#
#site_description#  #site_name#  #tags#  #term_description#  #term_name#
#thumbnail_url#  #title#
```

Third-party plugins can register additional tags. For instance, a social media plugin could add a `#twitter_handle#` tag to display an author's Twitter username.

More details about each tag are available under the **Help** button in the upper-right corner of the settings page.

---

### Per-Entry and Per-Term Fields

The WP SEO settings page allows you to set **global defaults**, but you can also customize title, description, and keyword values for individual entries and taxonomy terms.

- **Per-post type fields**: Enable these under **Post Types** on the settings page. The fields will appear on the "Edit" page for each post type.
- **Per-taxonomy fields**: Enable these under **Taxonomies** on the settings page. The fields will appear in the "Add New" and "Edit" forms for each taxonomy term.

---

### Custom Meta Tags

In addition to `<meta>` descriptions, WP SEO allows you to set **custom **``** tags** site-wide. These are managed under **Other Meta Tags** in the settings.

For example, to add a Google Verification meta tag:

1. Go to **Other Meta Tags**.
2. Add `google-site-verification` under **Name**.
3. Add the verification code under **Content**.
4. Use the **Add another** button to add more custom `<meta>` tags as needed.
5. Use **Remove group** to delete a custom `<meta>` tag.

---

## Frequently Asked Questions

### How can I change who has access to the SEO settings page?

In your plugin or theme, return your desired capability to the `'wp_seo_options_capability'` filter.

```php
// Allow users with the 'edit_posts' capability to access Settings > SEO.
add_filter( 'wp_seo_options_capability', function () {
    return 'edit_posts';
} );

// Do not allow anyone to access Settings > SEO.
add_filter( 'wp_seo_options_capability', function () {
    return 'do_not_allow';
} );
```

---

## Formatting Tag "Safe Mode"

Enable **safe mode** by calling `wp_seo_enable_formatting_tag_safe_mode()` in your plugin or theme:

```php
add_action( 'template_redirect', 'wp_seo_enable_formatting_tag_safe_mode' );
```

### What does "Safe Mode" do?

- Prevents WP SEO from setting `<title>` or `<meta>` tags with **unrecognized** formatting tags.
- Unrecognized tags may be typos (e.g., `#categoories#`) or tags from an uninstalled plugin.
- If safe mode is **disabled**, WP SEO will include the unrecognized formatting tag as regular text.
- Safe mode is **disabled by default**.

You can choose the mode based on your needs. **Disabled mode** makes it easier to spot mistakes, while **enabled mode** prevents broken metadata.

---

## Screenshots

1. **Settings page**
2. **On-screen documentation** for included formatting tags
3. **Title and meta fields** for a post

---