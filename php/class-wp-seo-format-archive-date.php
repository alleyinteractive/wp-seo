<?php
/**
 * Class file for WP_SEO_Format_Archive_Date.
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the archive date.
 */
class WP_SEO_Format_Archive_Date extends WP_SEO_Formatting_Tag {
	public $tag = '#archive_date#';

	public function get_description() {
		return __( 'Replaced with the date of the archive being viewed.', 'wp-seo' );
	}

	// @see the "_s" theme for these date strings.
	public function get_value() {
		if ( is_day() ) {
			return get_the_date();
		} elseif ( is_month() ) {
			return get_the_date( _x( 'F Y', 'monthly archives title tag format', 'wp-seo' ) );
		} elseif ( is_year() ) {
			return get_the_date( _x( 'Y', 'yearly archives title tag format', 'wp-seo' ) );
		}

		return false;
	}
}
