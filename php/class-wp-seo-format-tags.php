<?php
/**
 * Class file for WP_SEO_Format_Tags.
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the post tags.
 */
class WP_SEO_Format_Tags extends WP_SEO_Formatting_Tag {
	public $tag = '#tags#';

	public function get_description() {
		return __( 'Replaced with the Tags, comma-separated, of the content being viewed.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_singular() && is_object_in_taxonomy( get_post_type(), 'post_tag' ) && $tags = get_the_tags() ) {
			return implode( __( ', ', 'wp-seo' ), wp_list_pluck( $tags, 'name' ) );
		}

		return false;
	}
}
