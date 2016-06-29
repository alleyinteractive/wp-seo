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
