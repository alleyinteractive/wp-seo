<?php
/**
 * Helper and miscellaneous functions.
 *
 * @package WP_SEO
 */

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
 * Enqueue scripts for admin pages.
 */
function wp_seo_admin_scripts() {
	wp_enqueue_script( 'wp-seo-admin', WP_SEO_URL . 'js/wp-seo.js', array( 'jquery', 'underscore' ), '0.9.0', true );
	wp_localize_script( 'wp-seo-admin', 'wp_seo_admin', array(
		'repeatable_add_more_label' => __( 'Add another', 'wp-seo' ),
		'repeatable_remove_label' => __( 'Remove group', 'wp-seo' ),
	) );

	wp_enqueue_style( 'wp-seo-admin', WP_SEO_URL . 'css/wp-seo.css', array(), '0.9.0' );
}
add_action( 'admin_enqueue_scripts', 'wp_seo_admin_scripts' );

/**
 * @deprecated
 */
function wp_seo_noscript_character_count( $text ) {
	_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Meta_Boxes::noscript_character_count()' );
	return WP_SEO_Meta_Boxes::instance()->noscript_character_count( $text );
}
