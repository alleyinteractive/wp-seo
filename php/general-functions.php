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
 * @param object $query Optional query argument, defaults to wp_query.
 * @return string key
 */
function wp_seo_get_key( $query = false ) {
	if ( ! $query ) {
		global $wp_query;
		$query = $wp_query;
	}
	if ( $query->is_singular() ) {
		$post_type = get_post_type( get_queried_object() );
		$key = "single_{$post_type}";
	} elseif ( $query->is_front_page() ) {
		$key = 'home';
	} elseif ( $query->is_author() ) {
		$key = 'archive_author';
	} elseif ( $query->is_category() || $query->is_tag() || $query->is_tax() ) {
		$taxonomy = get_queried_object()->taxonomy;
		$key = "archive_{$taxonomy}";
	} elseif ( $query->is_post_type_archive() ) {
		$key = 'archive_' . get_queried_object()->name;
	} elseif ( $query->is_date() ) {
		$key = 'archive_date';
	} elseif ( $query->is_404() ) {
		$key = '404';
	} elseif ( $query->is_search() ) {
		$key = 'search';
	} else {
		$key = false;
	}
	return $key;
}
