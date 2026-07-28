<?php
/**
 * WP SEO Tests: Tests for class-wp-seo.php.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;
use WP_SEO;

class WPSEOTest extends TestCase {

	function test_get_non_term_option() {
		$this->assertEmpty(
			WP_SEO::instance()->get_term_option( rand( - 1, - 100 ), rand_str() ),
			'Non-existent terms should not return term option data'
		);
	}

	function test_get_term_option() {
		$option_value = rand_str();
		$term         = $this->create_and_get_term_with_option( $option_value );
		$this->assertSame(
			WP_SEO::instance()->get_term_option( $term->term_id, $term->taxonomy ),
			$option_value,
			'Valid terms with option data should be returned'
		);
	}

	function test_intersect_term_option() {
		// Spelled out in full (rather than a count + assertArrayHasKey per key)
		// so any missing or unexpectedly-added key shows up directly in the
		// diff, per PR #179 review feedback.
		$this->assertSame(
			[
				'title'       => '',
				'description' => '',
			],
			WP_SEO::instance()->intersect_term_option( [] )
		);
	}

}
