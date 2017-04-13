<?php
/**
 * Class file for WP_SEO_Format_Search_Term
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for a user's search term.
 */
class WP_SEO_Format_Search_Term extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#search_term#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( "Replaced with the user's search term.", 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Search term, or false.
	 */
	public function get_value() {
		$term = get_search_query();
		return ( $term ) ? $term : false;
	}
}
