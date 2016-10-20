<?php
/**
 * Functions for WP SEO to use behind the scenes.
 *
 * @package WP_SEO
 */

/**
 * Load plugin files used only in the admin.
 */
function wp_seo_load_admin_files() {
	// Admin-only functions.
	require_once WP_SEO_PATH . '/php/admin-functions.php';

	// Admin-only template tags.
	require_once WP_SEO_PATH . '/php/admin-template.php';
}

/**
 * Add hooks to enable "safe mode" for formatting tags.
 *
 * @since 0.12.0
 */
function wp_seo_enable_formatting_tag_safe_mode() {
	add_filter( 'wp_seo_after_format_string', 'wp_seo_no_formatting_tags_allowed' );
}
