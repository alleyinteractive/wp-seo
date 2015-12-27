<?php
/**
 * Class file for WP_SEO_Format_Post_Type_Singular_Name.
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the post type singular name.
 */
class WP_SEO_Format_Post_Type_Singular_Name extends WP_SEO_Formatting_Tag {
	public $tag = '#post_type_singular_name#';

	public function get_description() {
		return __( 'Replaced with the singular form of the name of the post type being viewed.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_singular() ) {
			return get_post_type_object( get_post_type() )->labels->singular_name;
		} elseif ( is_post_type_archive() ) {
			return get_queried_object()->labels->singular_name;
		}

		return false;
	}
}
