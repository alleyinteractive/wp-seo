<?php
/**
 * Class file for WP_SEO_Format_Site_Description
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the site description.
 */
class WP_SEO_Format_Site_Description extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#site_description#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( "Replaced with this site's description.", 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return string Site description.
	 */
	public function get_value() {
		return get_bloginfo( 'description' );
	}
}
