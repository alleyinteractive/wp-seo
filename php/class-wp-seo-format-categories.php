<?php
/**
 * Class file for WP_SEO_Format_Categories.
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the post categories.
 */
class WP_SEO_Format_Categories extends WP_SEO_Formatting_Tag {
	public $tag = '#categories#';

	public function get_description() {
		return __( 'Replaced with the Categories, comma-separated, of the content being viewed.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_singular() && is_object_in_taxonomy( get_post_type(), 'category' ) && $categories = get_the_category() ) {
			return implode( __( ', ', 'wp-seo' ), wp_list_pluck( $categories, 'name' ) );
		}

		return false;
	}
}
