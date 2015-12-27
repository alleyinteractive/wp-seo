<?php
/**
 * Class file for WP_SEO_Format_Site_Description.
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the site description.
 */
class WP_SEO_Format_Site_Description extends WP_SEO_Formatting_Tag {
	public $tag = '#site_description#';

	public function get_description() {
		return __( "Replaced with this site's description.", 'wp-seo' );
	}

	public function get_value() {
		return get_bloginfo( 'description' );
	}
}
