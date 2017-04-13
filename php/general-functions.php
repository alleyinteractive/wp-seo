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
