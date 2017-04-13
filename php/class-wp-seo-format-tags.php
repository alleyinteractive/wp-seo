<?php
/**
 * Class file for WP_SEO_Format_Tags
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for a post's tags.
 */
class WP_SEO_Format_Tags extends WP_SEO_Formatting_Tag {
	/**
	 * (Formatting) tag name.
	 *
	 * @var string
	 */
	public $tag = '#tags#';

	/**
	 * Get the (formatting) tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the Tags, comma-separated, of the content being viewed.', 'wp-seo' );
	}

	/**
	 * Get the (formatting) tag value for the current page.
	 *
	 * @return mixed Tag list, or false.
	 */
	public function get_value() {
		$tags = get_the_tags();
		if ( is_singular() && is_object_in_taxonomy( get_post_type(), 'post_tag' ) && $tags ) {
			return implode( __( ', ', 'wp-seo' ), wp_list_pluck( $tags, 'name' ) );
		}

		return false;
	}
}
