<?php
/**
 * Administration functions.
 *
 * @package WP_SEO
 */

/**
 * Get the default title above SEO fields on post- and term-edit screens.
 *
 * @return string The title.
 */
function wp_seo_get_box_title() {
	/**
	 * Filters the default title above SEO fields on post- and term-edit screens.
	 *
	 * @param string $title The title.
	 */
	return apply_filters( 'wp_seo_box_heading', __( 'Search Engine Optimization', 'wp-seo' ) );
}

/**
 * Call printing function for the meta title input for a given post.
 *
 * @param int $post_id Post ID.
 */
function wp_seo_post_id_to_the_meta_title_input( $post_id ) {
	wp_seo_the_meta_title_input( get_post_meta( $post_id, '_meta_title', true ) );
}

/**
 * Call printing function for the title character count for a given post.
 *
 * @param int $post_id Post ID.
 */
function wp_seo_post_id_to_the_title_character_count( $post_id ) {
	wp_seo_the_title_character_count( strlen( get_post_meta( $post_id, '_meta_title', true ) ) );
}

/**
 * Call printing function for the meta description input for a given post.
 *
 * @param int $post_id Post ID.
 */
function wp_seo_post_id_to_the_meta_description_input( $post_id ) {
	wp_seo_the_meta_description_input( get_post_meta( $post_id, '_meta_description', true ) );
}

/**
 * Call printing function for the description character count for a given post.
 *
 * @param int $post_id Post ID.
 */
function wp_seo_post_id_to_the_description_character_count( $post_id ) {
	wp_seo_the_description_character_count( strlen( get_post_meta( $post_id, '_meta_description', true ) ) );
}

/**
 * Call printing function for the meta keywords input for a given post.
 *
 * @param int $post_id Post ID.
 */
function wp_seo_post_id_to_the_meta_keywords_input( $post_id ) {
	wp_seo_the_meta_keywords_input( get_post_meta( $post_id, '_meta_keywords', true ) );
}

/**
 * Call printing function for the meta title input for a given term.
 *
 * @param int    $term_id  Term ID.
 * @param string $taxonomy The taxonomy slug.
 */
function wp_seo_term_data_to_the_meta_title_input( $term_id, $taxonomy ) {
	$term_option = WP_SEO()->intersect_term_option( (array) WP_SEO()->get_term_option( $term_id, $taxonomy ) );
	wp_seo_the_meta_title_input( $term_option['title'] );
}

/**
 * Call printing function for the title character count for a given term.
 *
 * @param int    $term_id  Term ID.
 * @param string $taxonomy The taxonomy slug.
 */
function wp_seo_term_data_to_the_title_character_count( $term_id, $taxonomy ) {
	$term_option = WP_SEO()->intersect_term_option( (array) WP_SEO()->get_term_option( $term_id, $taxonomy ) );
	wp_seo_the_title_character_count( strlen( $term_option['title'] ) );
}

/**
 * Call printing function for the meta description input for a given term.
 *
 * @param int    $term_id  Term ID.
 * @param string $taxonomy The taxonomy slug.
 */
function wp_seo_term_data_to_the_meta_description_input( $term_id, $taxonomy ) {
	$term_option = WP_SEO()->intersect_term_option( (array) WP_SEO()->get_term_option( $term_id, $taxonomy ) );
	wp_seo_the_meta_description_input( $term_option['description'] );
}

/**
 * Call printing function for the description character count for a given term.
 *
 * @param int    $term_id  Term ID.
 * @param string $taxonomy The taxonomy slug.
 */
function wp_seo_term_data_to_the_description_character_count( $term_id, $taxonomy ) {
	$term_option = WP_SEO()->intersect_term_option( (array) WP_SEO()->get_term_option( $term_id, $taxonomy ) );
	wp_seo_the_description_character_count( strlen( $term_option['description'] ) );
}

/**
 * Call printing function for the meta keywords input for a given term.
 *
 * @param int    $term_id  Term ID.
 * @param string $taxonomy The taxonomy slug.
 */
function wp_seo_term_data_to_the_meta_keywords_input( $term_id, $taxonomy ) {
	$term_option = WP_SEO()->intersect_term_option( (array) WP_SEO()->get_term_option( $term_id, $taxonomy ) );
	wp_seo_the_meta_keywords_input( $term_option['keywords'] );
}
