<?php
/**
 * Class file for WP_SEO_Format_Title
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for a post's title.
 */
class WP_SEO_Format_Title extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#title#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the title of the content being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Post title, or false.
	 */
	public function get_value() {
		if ( is_singular() && post_type_supports( get_post_type(), 'title' ) ) {
			return single_post_title( '', false );
		}

		return false;
	}
}
