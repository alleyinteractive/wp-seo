<?php
/**
 * Formatting functions.
 *
 * @package WP_SEO
 */

/**
 * Get each formatting tag in a string.
 *
 * @since 0.12.0
 *
 * @param string $string String to search.
 * @return array Array with any found formatting tags. Duplicates are retained.
 */
function wp_seo_match_all_formatting_tags( $string ) {
	preg_match_all( WP_SEO()->formatting_tag_pattern, $string, $matches, PREG_PATTERN_ORDER );
	return $matches[0];
}

/**
 * Reject strings containing formatting tags.
 *
 * @since 0.12.0
 *
 * @param string $string String to search.
 * @return string|WP_Error The string, or an error if the string has formatting tags.
 */
function wp_seo_no_formatting_tags_allowed( $string ) {
	$matches = wp_seo_match_all_formatting_tags( $string );

	if ( $matches ) {
		return new WP_Error( 'has_formatting_tags', __( 'String has formatting tags.', 'wp-seo' ), $matches );
	}

	return $string;
}
