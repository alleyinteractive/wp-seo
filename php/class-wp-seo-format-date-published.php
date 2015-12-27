<?php
/**
 * Class file for WP_SEO_Format_Date_Published.
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the post publish date.
 */
class WP_SEO_Format_Date_Published extends WP_SEO_Formatting_Tag {
	public $tag = '#date_published#';

	public function get_description() {
		return __( 'Replaced with the date that the post being viewed was published.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_singular() ) {
			return get_the_date();
		}

		return false;
	}
}
