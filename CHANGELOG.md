# Changelog

All notable changes to `WP SEO` will be documented in this file.

## 2.0.0 ##
Just kidding about the "no plans for additional feature development" thing, we're back!

* Breaking change: Plugin behavior is organized into features, and every feature is opt-in. Nothing runs until it is enabled in code with the `wp_seo_enable_feature` filter or the `wp_seo_enable_{handle}` filter named for a specific feature. Features migrated so far: Open Graph (`wp_seo_enable_open_graph`).
* Added: A read-only WP SEO Features admin screen listing every registered feature and whether it is running.
* Added: `wp_seo_register_features` action, for adding features to the plugin without editing it.
* Added: Configurable site-wide default Open Graph image, used as a fallback when a post has no explicit or featured image.
* Added: Support for canonical URL and robots meta.
* Added: `AI_Providers` class for sending prompts through the AI connectors configured in WordPress. `AI_Providers::check_provider()` returns the AI provider a feature sends its prompts to, or a `WP_Error` when there is none, when AI is turned off on the site, or before WordPress 7.0, when `AI_Providers::get_available_providers()` also lists no providers.
* Added: A WP SEO AI settings page for choosing the AI provider WP SEO sends prompts to, the `AI_Providers::get_provider()` method, and the `wp_seo_ai_provider` filter for setting the provider in code.
* Added: Meta Description Generator feature (`wp_seo_enable_description_generator`), which writes a meta description for a post with the AI provider chosen on the WP SEO AI settings page. It adds the `Description_Generator::generate_description()`, `Description_Generator::get_post_types()`, and `Description_Generator::get_max_length()` methods, a `POST /wp-seo/v1/generate-description` REST route for people who can edit the post, and the `wp_seo_description_generator_prompt`, `wp_seo_description_generator_content`, `wp_seo_description_generator_max_length`, and `wp_seo_description_generator_post_types` filters.
* Added: A rate limit on the Meta Description Generator's REST route of 30 requests per user an hour, after which it responds with a 429 status and a `Retry-After` header. The `wp_seo_description_generator_rate_limit` filter changes the limit for a user, or turns it off. Calling `Description_Generator::generate_description()` directly is not limited.
* Added: A "Generate description" button under the Description field of the block editor's Search Engine panel, which fills the field with a description from the Meta Description Generator for the editor to review and save. The feature shows the button with the `wp-seo-description-generator` post type support, which it adds once an AI provider is chosen, to each of its post types that registers `search_engine_description`.
* Added: A `wp wp-seo generate-descriptions` command, which saves a description from the Meta Description Generator to each post without one. Posts are worked through in order of ID, and the place reached is saved after each post, so the next run with the same `--query` continues where the last stopped. It takes `--query` (WP_Query arguments as JSON), `--limit` (posts per run, default 100), `--fault-tolerance` (percentage of the limit that may fail, default 5), `--dry-run`, and `--rewind`, and stops early when the AI provider responds with a 429 status. The `wp_seo_description_generator_cli_limit` and `wp_seo_description_generator_cli_fault_tolerance` filters change the defaults. Adds the `alleyinteractive/wp-bulk-task` dependency.
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
