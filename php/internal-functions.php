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
 * Check whether the plugin's admin script and style are needed on the
 * current admin screen.
 *
 * True on the plugin's settings page, on add/edit screens for taxonomies
 * with SEO fields enabled, and on classic-editor post edit screens for
 * post types with SEO fields enabled.
 *
 * @param string $hook_suffix The current admin page's hook suffix, as
 *                             passed to the admin_enqueue_scripts action.
 * @return bool
 */
function wp_seo_is_admin_screen_enqueueable( $hook_suffix ) {
	if ( 'settings_page_' . WP_SEO_Settings::SLUG === $hook_suffix ) {
		return true;
	}

	$screen = get_current_screen();

	if ( ! $screen ) {
		return false;
	}

	if ( in_array( $hook_suffix, array( 'edit-tags.php', 'term.php' ), true ) ) {
		return ! empty( $screen->taxonomy ) && WP_SEO_Settings()->has_term_fields( $screen->taxonomy );
	}

	if ( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		if ( empty( $screen->post_type ) || ! WP_SEO_Settings()->has_post_fields( $screen->post_type ) ) {
			return false;
		}

		if ( function_exists( 'use_block_editor_for_post_type' ) ) {
			return ! use_block_editor_for_post_type( $screen->post_type );
		}

		return true;
	}

	return false;
}
