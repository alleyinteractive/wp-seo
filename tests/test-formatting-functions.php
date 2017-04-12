<?php
/**
 * Tests for formatting-functions.php.
 *
 * @package WP_SEO
 */

class WP_SEO_Formatting_Functions_Tests extends WP_SEO_Testcase {
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
	function data_match_all_formatting_tags() {
		return array(
			array( '', 0 ),
			array( '#title#', 1 ),
			array( '#title# and #title#', 2 ),
			array( '#title# and #title# and #description#', 3 ),
			array( "We're #1!", 0 ),
		);
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
	function data_no_formatting_tags_allowed_pass() {
		return array(
			array( '' ),
			array( rand_str() ),
		);
	}

	/**
	 * Test wp_seo_no_formatting_tags_allowed() with failing strings.
	 *
	 * @dataProvider data_no_formatting_tags_allowed_fail
	 */
	function test_no_formatting_tags_allowed_fail( $string, $data ) {
		$actual = wp_seo_no_formatting_tags_allowed( $string );

		$this->assertWPError( $actual );
		$this->assertSame( $data, $actual->get_error_data() );
	}

	/**
	 * @return array {
	 *     @type string The "formatted" string.
	 *     @type mixed The expected WP_Error::data.
	 * }
	 */
	function data_no_formatting_tags_allowed_fail() {
		return array(
			array(
				rand_str() . ' #title#',
				array( '#title#' ),
			),
		);
	}

	/**
	 * Test wp_seo_get_the_display_character_count() with strings that can be counted.
	 *
	 * @dataProvider data_display_character_count_string_lengths
	 */
	function test_display_character_count_string_length( $string, $length ) {
		$this->assertSame( wp_seo_get_the_display_character_count( $string ), $length );
	}

	/**
	 * @return array {
	 *     @type string String to count.
	 *     @type string Expected length.
	 * }
	 */
	function data_display_character_count_string_lengths() {
		return array(
			array( 'abcde', '5' ),
		);
	}

	/**
	 * Test wp_seo_get_the_display_character_count() with strings that should return descriptions.
	 *
	 * @dataProvider data_display_character_count_string_descriptions
	 */
	function test_display_character_count_string_description( $string ) {
		$this->assertFalse( is_numeric( wp_seo_get_the_display_character_count( $string ) ) );
	}

	/**
	 * @return array {
	 *     @type string String to count.
	 * }
	 */
	function data_display_character_count_string_descriptions() {
		return array(
			array( 'Hello #world#' ),
			array( '#helloworld#' ),
		);
	}
}
