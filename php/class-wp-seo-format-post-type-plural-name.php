<?php
/**
 * Class file for WP_SEO_Format_Post_Type_Plural_Name
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for a post type's plural name.
 */
class WP_SEO_Format_Post_Type_Plural_Name extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#post_type_plural_name#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the plural form of the name of the post type being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Post type plural name, or false.
	 */
	public function get_value() {
		if ( is_singular() ) {
			return get_post_type_object( get_post_type() )->labels->name;
		} elseif ( is_post_type_archive() ) {
			return get_queried_object()->labels->name;
		}

		return false;
	}
}
