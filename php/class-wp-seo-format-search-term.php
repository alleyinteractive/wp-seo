<?php
/**
 * Class file for WP_SEO_Format_Search_Term.
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the current search term.
 */
class WP_SEO_Format_Search_Term extends WP_SEO_Formatting_Tag {
	public $tag = '#search_term#';

	public function get_description() {
		return __( "Replaced with the user's search term.", 'wp-seo' );
	}

	public function get_value() {
		return ( $term = get_search_query() ) ? $term : false;
	}
}
