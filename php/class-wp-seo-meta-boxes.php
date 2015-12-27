<?php
/**
 * Class file for WP_SEO_Meta_Boxes.
 *
 * @package WP_SEO
 */

/**
 * Base class for adding WP SEO meta boxes.
 */
class WP_SEO_Meta_Boxes extends WP_SEO_Singleton {
	/**
	 * Get the meta box heading.
	 *
	 * @return string
	 */
	protected function get_box_heading() {
		/**
		 * Filter the heading above SEO fields in meta boxes.
		 *
		 * @param string The text. Default is "Search Engine Optimization."
		 */
		return apply_filters( 'wp_seo_box_heading', __( 'Search Engine Optimization', 'wp-seo' ) );
	}

	/**
	 * Get the translated <noscript> text for the character count.
	 *
	 * Public visibility for backcompat with wp_seo_noscript_character_count().
	 *
	 * @param string $text The text to count.
	 * @return string The text to go between the <noscript> tags.
	 */
	public function noscript_character_count( $text ) {
		return sprintf( __( '%d (save changes to update)', 'wp-seo' ), strlen( $text ) );
	}
}
