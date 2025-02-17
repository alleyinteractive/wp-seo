<?php
/**
 * WP SEO Tests: Tests for internal-functions.php.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;

class InternalFunctionsTest extends TestCase {
	/**
	 * Test that enabling formatting-tag safe mode has the expected effects.
	 */
	function test_enable_formatting_tag_safe_mode() {
		$unsafe_string = 'Foo #bar#';

		// Before.
		$this->assertSame( $unsafe_string, WP_SEO()->format( $unsafe_string ) );

		wp_seo_enable_formatting_tag_safe_mode();

		// After.
		$this->assertWPError( WP_SEO()->format( $unsafe_string ) );
	}
}
