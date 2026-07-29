<?php
/**
 * WP SEO Tests: Base Test Class
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests;

use Mantle\Testing\Utils;
use Mantle\Testkit\Test_Case as TestkitTest_Case;

/**
 * WP SEO Base Test Case
 */
abstract class TestCase extends TestkitTest_Case {
	public static function create_and_get_term_with_option( $option_value, $args = array() ) {
		$term = static::factory()->term->create_and_get( $args );
		update_option( \WP_SEO::instance()->get_term_option_name( $term ), $option_value );
		return get_term( $term->term_id, $term->taxonomy );
	}

	/**
	 * Capture the real, fully rendered wp_head output for whatever query
	 * go_to() has already set up - every registered callback fires, not just
	 * one method called directly, so this verifies the feature is actually
	 * wired into WordPress's own rendering rather than just callable in
	 * isolation.
	 *
	 * @return string The rendered wp_head output.
	 */
	protected function get_rendered_head(): string {
		return Utils::get_echo( fn () => do_action( 'wp_head' ) );
	}
}
