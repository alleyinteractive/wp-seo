<?php
/**
 * Class file for WP_SEO_Format_Date_Modified
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for a post's modified date.
 */
class WP_SEO_Format_Date_Modified extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#date_modified#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the date that the content being viewed was last modified.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Post-modified date, or false.
	 */
	public function get_value() {
		if ( is_singular() ) {
			return get_the_modified_date();
		}

		return false;
	}
}
