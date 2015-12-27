<?php
/**
 * Class file for WP_SEO_Format_Excerpt.
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the post excerpt.
 */
class WP_SEO_Format_Excerpt extends WP_SEO_Formatting_Tag {
	public $tag = '#excerpt#';

	public function get_description() {
		return __( "Replaced with the excerpt of the content being viewed. An excerpt is generated if one isn't written.", 'wp-seo' );
	}

	/**
	 * Use the written excerpt if it exists.
	 *
	 * If there is no excerpt, then generate one similar to wp_trim_excerpt(),
	 * which fails to filter get_the_excerpt() itself at this point.
	 */
	public function get_value() {
		if ( is_singular() && post_type_supports( get_post_type(), 'excerpt' ) ) {
			if ( $excerpt = get_the_excerpt() ) {
				return $excerpt;
			} else {
				$post = get_post();
				return wp_trim_words( $post->post_content, apply_filters( 'excerpt_length', 55 ), '' );
			}
		}

		return false;
	}
}
