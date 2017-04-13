<?php
/**
 * Class file for WP_SEO_Format_Thumbnail_URL
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the post thumbnail URL.
 */
class WP_SEO_Format_Thumbnail_URL extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#thumbnail_url#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the URL for the featured image of the content being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Thumbnail URL, or false.
	 */
	public function get_value() {
		/*
		 * The {@see "wp_seo_format_{$id}"} filter is available for returning a
		 * different image size. You also can subclass this formatting tag for
		 * your needs and use the {@see 'wp_seo_formatting_tags'} filter.
		 */
		return ( is_singular() ) ? get_the_post_thumbnail_url( get_the_ID(), 'full' ) : false;
	}
}
