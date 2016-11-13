<?php
/**
 * Ajax handlers.
 *
 * @package WP_SEO
 */

/**
 * Ajax handler for getting the character count for display.
 *
 * @since 0.13.0
 */
function wp_seo_ajax_display_character_count() {
	if ( ! isset( $_GET['string'] ) ) {
		wp_send_json_error();
	}

	wp_send_json_success(
		wp_seo_get_the_display_character_count( sanitize_text_field( wp_unslash( $_GET['string'] ) ) )
	);
}
