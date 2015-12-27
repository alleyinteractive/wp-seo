<?php
/**
 * Class file for WP_SEO_Format_Date_Modified.
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the post modified date.
 */
class WP_SEO_Format_Date_Modified extends WP_SEO_Formatting_Tag {
	public $tag = '#date_modified#';

	public function get_description() {
			return __( 'Replaced with the date that the post being viewed was last modified.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_singular() ) {
			return get_the_modified_date();
		}

		return false;
	}
}
