<?php
/**
 * Class file for WP_SEO_Formatter.
 *
 * @package WP_SEO
 */

/**
 * Formats the formatting tags.
 */
class WP_SEO_Formatter extends WP_SEO_Singleton {
	/**
	 * Replace formatting tags in a string with their value for the current page.
	 *
	 * @param string $string The string with formatting tags.
	 * @return string|WP_Error The formatted string, or WP_Error on error.
	 */
	public function format( $string ) {
		if ( ! is_string( $string ) ) {
			return new WP_Error( 'format_error', __( "Please don't try to format() a non-string.", 'wp-seo' ) );
		}

		$raw_string = $string;

		preg_match_all( $this->get_formatting_tag_pattern(), $string, $matches );
		if ( empty( $matches[0] ) ) {
			return $string;
		}

		$replacements = array();
		$unique_matches = array_unique( $matches[0] );

		foreach ( WP_SEO_Formatting_Tag_Collection::instance()->get_all() as $id => $tag ) {
			if ( ! empty( $tag->tag ) && in_array( $tag->tag, $unique_matches ) ) {
				/**
				 * Filter the value of a formatting tag for the current page.
				 *
				 * The dynamic portion of the hook name, `$id`, refers to the
				 * key used to register the formatting tag. For example, the
				 * hook for the default "#site_name#" formatting tag is
				 * 'wp_seo_format_site_name'.
				 *
				 * @see wp_seo_default_formatting_tags() for the defaults' keys.
				 *
				 * @param string The value returned by the formatting tag.
				 */
				$replacements[ $tag->tag ] = apply_filters( "wp_seo_format_{$id}", $tag->get_value() );
			}
		}

		if ( ! empty( $replacements ) ) {
			$string = str_replace( array_keys( $replacements ), array_values( $replacements ), $string );
		}

		/**
		 * Filter the formatted string.
		 *
		 * @param string $string The formatted string.
		 * @param string $raw_string The string as submitted.
		 */
		return apply_filters( 'wp_seo_after_format_string', $string, $raw_string );
	}

	/**
	 * Get the regular expression used to find formatting tags in a string.
	 *
	 * @return string The regex.
	 */
	public function get_formatting_tag_pattern() {
		/**
		 * Filter the regular expression used to find formatting tags in a string.
		 *
		 * You might filter this if you want to add unusual custom tags.
		 *
		 * @param string $formatting_tag_pattern The regex.
		 */
		return apply_filters( 'wp_seo_formatting_tag_pattern', '/#[a-zA-Z\_]+#/' );
	}
}
