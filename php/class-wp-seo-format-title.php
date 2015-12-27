<?php
/**
 * Class file for WP_SEO_Format_Title.
 *
 * @package WP_SEO
 */

class WP_SEO_Format_Title extends WP_SEO_Formatting_Tag {

	public $tag = '#title#';

	public function get_description() {
		return __( 'Replaced with the title of the content being viewed.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_singular() && post_type_supports( get_post_type(), 'title' ) ) {
			return single_post_title( '', false );
		}

		return false;
	}

}
