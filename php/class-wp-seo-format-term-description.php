<?php
/**
 * Class file for WP_SEO_Format_Term_Description.
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for archive term description.
 */
class WP_SEO_Format_Term_Description extends WP_SEO_Formatting_Tag {
	public $tag = '#term_description#';

	public function get_description() {
		return __( 'Replaced with the description of the term whose archive is being viewed.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_category() || is_tag() || is_tax() ) {
			return get_queried_object()->description;
		}

		return false;
	}
}
