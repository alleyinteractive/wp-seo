<?php
/**
 * Helper and miscellaneous functions.
 *
 * @package WP_SEO
 */

/**
 * Helper function to use the WP_SEO instance.
 *
 * @return WP_SEO
 */
function wp_seo() {
	return WP_SEO::instance();
}

/**
 * Helper function to use the WP_SEO_Settings instance.
 *
 * @return WP_SEO_Settings
 */
function wp_seo_settings() {
	return WP_SEO_Settings::instance();
}

/**
 * Construct an option name for per-term SEO fields.
 *
 * @param object $term The term object.
 * @return string The option name.
 */
function wp_seo_get_term_option_name( $term ) {
	return "wp-seo-term-{$term->term_taxonomy_id}";
}

/**
 * @deprecated
 */
function wp_seo_noscript_character_count( $text ) {
	_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Meta_Boxes::noscript_character_count()' );
	return WP_SEO_Meta_Boxes::instance()->noscript_character_count( $text );
}
