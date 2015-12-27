<?php
/**
 * Class file for WP_SEO_Format_Author.
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the author name.
 */
class WP_SEO_Format_Author extends WP_SEO_Formatting_Tag {
	public $tag = '#author#';

	public function get_description() {
		return __( 'Replaced with the author name of the post or author archive being viewed.', 'wp-seo' );
	}

	/**
	 * On singular, use get_the_author() if it's available.
	 *
	 * If it isn't available yet, get the author field from the post ID, and
	 * apply the 'the_author' filter for Co-Authors Plus support.
	 *
	 * On author archives, get author data directly from the queried object, not
	 * get_the_author(), to prevent Co-Authors Plus from filtering it.
	 */
	public function get_value() {
		if ( is_singular() && post_type_supports( get_post_type(), 'author' ) ) {
			if ( $author = get_the_author() ) {
				return $author;
			} elseif ( $post_author = get_post_field( 'post_author', get_the_ID() ) ) {
				return apply_filters( 'the_author', get_the_author_meta( 'display_name', $post_author ) );
			}
		} elseif ( is_author() ) {
			return get_the_author_meta( 'display_name', get_queried_object_id() );
		}

		return false;
	}
}
