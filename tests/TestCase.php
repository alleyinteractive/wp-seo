<?php
/**
 * WP SEO Tests: Base Test Class
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests;

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
}
