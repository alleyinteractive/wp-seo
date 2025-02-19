<?php
/**
 * WP SEO Tests: Tests for formatting-functions.php.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;

class FormattingFunctionsTest extends TestCase {
	/**
	 * Test wp_seo_match_all_formatting_tags() with various strings.
	 *
	 * @dataProvider data_match_all_formatting_tags
	 */
	function test_match_all_formatting_tags( $string, $expected_count ) {
		$this->assertCount( $expected_count, wp_seo_match_all_formatting_tags( $string ) );
	}

	/**
	 * @return array {
	 *     @type string String to search.
	 *     @type int Expected size of the resulting array.
	 * }
	 */
	static function data_match_all_formatting_tags() {
		return [
			['', 0],
			['#title#', 1],
			['#title# and #title#', 2],
			['#title# and #title# and #description#', 3],
			["We're #1!", 0],
		];
	}

	/**
	 * Test wp_seo_no_formatting_tags_allowed() with passing strings.
	 *
	 * @dataProvider data_no_formatting_tags_allowed_pass
	 */
	function test_no_formatting_tags_allowed_pass( $string ) {
		$this->assertNotWPError( wp_seo_no_formatting_tags_allowed( $string ) );
	}

	/**
	 * @return array {
	 *     @type string The "formatted" string.
	 * }
	 */
	static function data_no_formatting_tags_allowed_pass() {
		return [
			[''],
			[rand_str()],
		];
	}
}
