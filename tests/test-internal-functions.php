<?php
/**
 * Tests for internal-functions.php.
 *
 * @package WP_SEO
 */

class WP_SEO_Internal_Functions_Tests extends WP_SEO_Testcase {
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
