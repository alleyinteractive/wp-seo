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

	// Admin Ajax handlers.
	if ( function_exists( 'wp_doing_ajax' ) ) {
		if ( wp_doing_ajax() ) {
			require_once WP_SEO_PATH . '/php/ajax-functions.php';
		}
	} else {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			require_once WP_SEO_PATH . '/php/ajax-functions.php';
		}
	}
}

/**
 * Add hooks to enable "safe mode" for formatting tags.
 *
 * @since 0.12.0
 */
function wp_seo_enable_formatting_tag_safe_mode() {
	add_filter( 'wp_seo_after_format_string', 'wp_seo_no_formatting_tags_allowed' );
}
