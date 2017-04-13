<?php
/**
 * Class file for WP_SEO_Format_Archive_Date
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the date of a date archive.
 */
class WP_SEO_Format_Archive_Date extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#archive_date#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the date of the archive being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @see The "_s" theme for these date strings.
	 *
	 * @return mixed Date of the archive being viewed, or false.
	 */
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
