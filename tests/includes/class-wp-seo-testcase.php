<?php
/**
 * WP SEO test fixture.
 *
 * @package WP_SEO
 */

class WP_SEO_Testcase extends WP_UnitTestCase {
	public function create_and_get_term_with_option( $option_value, $args = array() ) {
		$term = $this->factory->term->create_and_get( $args );
		update_option( WP_SEO::instance()->get_term_option_name( $term ), $option_value );
		return get_term( $term->term_id, $term->taxonomy );
	}
}
