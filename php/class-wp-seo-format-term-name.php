<?php
/**
 * Class file for WP_SEO_Format_Term_Name.
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the archive term name.
 */
class WP_SEO_Format_Term_Name extends WP_SEO_Formatting_Tag {
	public $tag = '#term_name#';

	public function get_description() {
		return __( 'Replaced with the name of the term whose archive is being viewed.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_category() || is_tag() || is_tax() ) {
			return get_queried_object()->name;
		}

		return false;
	}
}
