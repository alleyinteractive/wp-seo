<?php
/**
 * Class file for WP_SEO_Format_Author
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for a post's author.
 */
class WP_SEO_Format_Author extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#author#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the author name of the content or author archive being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * On singular, uses get_the_author() if it's available.
	 *
	 * If it isn't available yet, gets the author field from the post ID, and
	 * apply the 'the_author' filter for Co-Authors Plus support.
	 *
	 * On author archives, gets author data directly from the queried object,
	 * not get_the_author(), to prevent Co-Authors Plus from filtering it.
	 *
	 * @return mixed Author name, or false.
	 */
	public function get_value() {
		if ( is_singular() && post_type_supports( get_post_type(), 'author' ) ) {
			$author = get_the_author();
			$post_author = get_post_field( 'post_author', get_the_ID() );
			if ( $author ) {
				return $author;
			} elseif ( $post_author ) {
				return apply_filters( 'the_author', get_the_author_meta( 'display_name', $post_author ) );
			}
		} elseif ( is_author() ) {
			return get_the_author_meta( 'display_name', get_queried_object_id() );
		}

		return false;
	}
}
