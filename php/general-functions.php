<?php
/**
 * General functions.
 *
 * @package WP_SEO
 */

/**
 * Merge user arguments into a defaults array without appending to the defaults.
 *
 * This can be useful when you want to make sure a new key can't be added to
 * $args without also being added to $defaults, thus providing some security
 * against undefined-index errors when the key is not included with $args in
 * some future scenario.
 *
 * @param array $args Value to merge with defaults.
 * @param array $defaults Defaults array.
 * @return array The defaults after merging values for matching $args keys.
 */
function wp_seo_intersect_args( $args, $defaults ) {
	return array_intersect_key( $args, $defaults ) + $defaults;
}


/**
 * Helper function for determining the 'key' for use in head
 *
 * @return string key
 */
function wp_seo_get_key() {
	if ( is_singular() ) {
		$post_type = get_post_type();
		$key = "single_{$post_type}";
	} elseif ( is_front_page() ) {
		$key = 'home';
	} elseif ( is_author() ) {
		$key = 'archive_author';
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$taxonomy = get_queried_object()->taxonomy;
		$key = "archive_{$taxonomy}";
	} elseif ( is_post_type_archive() ) {
		$key = 'archive_' . get_queried_object()->name;
	} elseif ( is_date() ) {
		$key = 'archive_date';
	} elseif ( is_404() ) {
		$key = '404';
	} elseif ( is_search() ) {
		$key = 'search';
	} else {
		$key = false;
	}
	return $key;
}
