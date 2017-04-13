<?php
/**
 * Class file for WP_SEO_Format_Site_Name
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the site name.
 */
class WP_SEO_Format_Site_Name extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#site_name#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( "Replaced with this site's name.", 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return string Site name.
	 */
	public function get_value() {
		return get_bloginfo( 'name' );
	}
}
