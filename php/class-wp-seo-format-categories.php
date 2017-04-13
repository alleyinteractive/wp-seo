<?php
/**
 * Class file for WP_SEO_Format_Categories
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for a post's categories.
 */
class WP_SEO_Format_Categories extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#categories#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the Categories, comma-separated, of the content being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Category list, or false.
	 */
	public function get_value() {
		$categories = get_the_category();
		if ( is_singular() && is_object_in_taxonomy( get_post_type(), 'category' ) && $categories ) {
			return implode( __( ', ', 'wp-seo' ), wp_list_pluck( $categories, 'name' ) );
		}

		return false;
	}
}
