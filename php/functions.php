<?php
/**
 * Helper and miscellaneous functions.
 *
 * @package WP_SEO
 */

/**
 * Get the WP SEO meta box heading.
 *
 * @return string
 */
function wp_seo_meta_box_heading() {
	/**
	 * Filter the heading above SEO fields on edit-post screens.
	 *
	 * @param string The text. Default is "Search Engine Optimization."
	 */
	return apply_filters( 'wp_seo_box_heading', __( 'Search Engine Optimization', 'wp-seo' ) );
}

/**
 * Get the translated <noscript> text for the character count.
 *
 * @param string $text The text to count.
 * @return string The text to go between the <noscript> tags.
 */
function wp_seo_noscript_character_count( $text ) {
	return sprintf( __( '%d (save changes to update)', 'wp-seo' ), strlen( $text ) );
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
