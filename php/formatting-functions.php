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

/**
 * Get the character count of a format string for display to users.
 *
 * @since 0.13.0
 *
 * @param  string $string String to count.
 * @return string         String with the character count for display.
 */
function wp_seo_get_the_display_character_count( $string ) {
	$matches = wp_seo_match_all_formatting_tags( $string );

	if ( count( $matches ) ) {
		// Formatting tags are present, so we have to estimate the count.
		$length = strlen( str_replace( $matches, '', $string ) );

		if ( 0 === $length ) {
			// The only thing in the string is formatting tags.
			return __( 'Same as the character count of the formatting tag values.', 'wp-seo' );
		}

		/* translators: %d: character count */
		return sprintf( __( 'At least %d, plus the character count of formatting tag values.', 'wp-seo' ), $length );
	}

	return (string) strlen( $string );
}

/**
 * Sanitizes a submitted variable into an integer after casting as string for consistency.
 *
 * @param mixed $input The input's current value.
 * @return int $input The sanitized value, or 0 on failure.
 */
function wp_seo_sanitize_integer_field( $input ) {
	if ( ! function_exists( 'ctype_digit' ) || ! defined( 'FILTER_SANITIZE_NUMBER_INT' ) ) {
		return intval( $input );
	}

	if ( ! is_scalar( $input ) ) {
		if ( is_object( $input ) ) {
			return 0;
		}

		$input = intval( $input );
	}

	$input = (string) $input;

	if ( ! ctype_digit( $input ) ) {
		return 0;
	}

	return (int) filter_var( $input, FILTER_SANITIZE_NUMBER_INT );
}
