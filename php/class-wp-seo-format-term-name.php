<?php
/**
 * Class file for WP_SEO_Format_Term_Name
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for a term's name.
 */
class WP_SEO_Format_Term_Name extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#term_name#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the name of the term whose archive is being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Term name, or false.
	 */
	public function get_value() {
		if ( is_category() || is_tag() || is_tax() ) {
			return get_queried_object()->name;
		}

		return false;
	}
}
