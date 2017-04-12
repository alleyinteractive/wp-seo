<?php
/**
 * Tests for functions in ajax-functions.php.
 *
 * @group   ajax
 * @package WP_SEO
 */

class WP_SEO_Ajax_Functions_Tests extends WP_Ajax_UnitTestCase {
	/**
	 * Test the JSON response for displaying character counts given various $_GET values.
	 *
	 * @dataProvider data_test_display_character_count
	 */
	function test_display_character_count( $string ) {
		$_GET['string'] = $string;

		try {
			$this->_handleAjax( 'wp_seo_display_character_count' );
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		}

		$response = json_decode( $this->_last_response, true );

		$this->assertSame( isset( $string ), $response['success'] );
		$this->assertSame( isset( $string ), isset( $response['data'] ) );
	}

	/**
	 * @return array {
	 *    @type string $string The value of $_GET['string'].
	 * }
	 */
	function data_test_display_character_count() {
		return array(
			array( 'abcdef' ),
			array( '' ),
			array( null ),
		);
	}
}
