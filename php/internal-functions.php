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

/**
 * Resolve an attachment ID stored in post meta to a full-size image URL.
 *
 * @param int    $post_id  The post ID.
 * @param string $meta_key The meta key storing the attachment ID.
 *
 * @return string|false The image URL, or false if no valid image is set.
 */
function wp_seo_get_image_url_from_meta( $post_id, $meta_key ) {
	$attachment_id = get_post_meta( $post_id, $meta_key, true );

	if ( empty( $attachment_id ) || ! is_string( $attachment_id ) ) {
		return false;
	}

	return wp_get_attachment_image_url( (int) $attachment_id, 'full' );
}
