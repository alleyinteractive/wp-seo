<?php
/**
 * Tests for whether submitted data are sanitized correctly before saving as options.
 *
 * @package WP SEO
 */
class WP_SEO_Sanitize_Options_Tests extends WP_UnitTestCase {

	var $option_valid = array(
		'home_title'       => 'Home | Alley Interactive',
		'home_description' => 'We are a team of experienced digital professionals who tackle the most complex challenges facing top publishers.',
		'home_keywords'    => 'WordPress, Drupal, Open Source',
		'arbitrary_tags'   => array(
			array( 'name' => 'viewport', 'content' => 'width=device-width, initial-scale=1' ),
		),
	);

	var $option_empty_repeatable = array(
		'arbitrary_tags' => array( array( 'name' => '', 'content' => '' ) ),
	);

	var $option_many_empty_repeatables = array(
		'arbitrary_tags' => array( array( 'name' => '', 'content' => '' ), array( 'name' => '', 'content' => '' ), array( 'name' => '', 'content' => '' ) ),
	);

	/**
	 * Wrapper for WP_SEO_Settings::sanitize_options().
	 *
	 * @param  array $options Options to sanitize.
	 * @return array.
	 */
	function _sanitize( $options ) {
		return WP_SEO_Settings()->sanitize_options( $options );
	}

	function test_valid_options() {
		$actual = $this->_sanitize( $this->option_valid );
		foreach ( $this->option_valid as $key => $value ) {
			$this->assertSame( $actual[ $key ], $value );
		}
	}

	function test_unsanitized_option() {
		$actual = $this->_sanitize( array( 'home_title' => 'That is <strong>not</strong> allowed.' ) );
		$this->assertSame( $actual['home_title'], 'That is not allowed.' );
	}

	// Test that non-post types and non-taxonomies are removed.
	function test_invalid_objects() {
		$actual = $this->_sanitize( array(
			'post_types' => array( 'post', 'page', 'foo' ),
			'taxonomies' => array( 'category', 'post_tag', 'bar' ),
		) );
		$this->assertSame( $actual['post_types'], array( 'post', 'page' ) );
		$this->assertSame( $actual['taxonomies'], array( 'category', 'post_tag' ) );
	}

	// Test that keys with empty values are still included in the option array.
	function test_missing_keys() {
		$actual = $this->_sanitize( array(
			'home_title' => '',
			'home_description' => '',
		) );
		$this->assertArrayHasKey( 'home_keywords', $actual );
		$this->assertArrayHasKey( 'arbitrary_tags', $actual );
	}

	/**
	 * Test that values of the wrong type are included in the array as empty
	 * versions of the correct type, and that unknown keys are removed.
	 */
	function test_illegal_elements() {
		$actual = $this->_sanitize( array(
			'home_title'     => array( 'Not a string' ),
			'post_types'     => 'post',
			'taxonomies'     => 'category',
			'unknown_key'    => 'Unknown value.',
			'arbitrary_tags' => null,
		) );
		$this->assertEmpty( $actual['post_types'] );
		$this->assertInternalType( 'array', $actual['post_types'] );
		$this->assertEmpty( $actual['taxonomies'] );
		$this->assertInternalType( 'array', $actual['taxonomies'] );
		$this->assertArrayNotHasKey( 'unknown_key', $actual );
		$this->assertInternalType( 'array', $actual['arbitrary_tags'] );
	}

	function test_invalid_repeatables() {
		$actual = $this->_sanitize( array(
			'arbitrary_tags' => array(
				array( 'content' => 'Unknown' ),
				array( 'name' => '', 'content' => '' ),
				array( 'name' => 'application-name', 'content' => 'WP SEO' ),
				array(),
			),
		) );
		$this->assertCount( 2, $actual['arbitrary_tags'] );
		foreach ( $actual['arbitrary_tags'] as $i => $tag ) {
			$this->assertTrue( isset( $tag['name'] ) || isset( $tag['content'] ) );
		}
	}

	function test_empty_repeatable() {
		$actual = $this->_sanitize( $this->option_empty_repeatable );
		$this->assertCount( 0, $actual['arbitrary_tags'] );
	}

	function test_many_empty_repeatables() {
		$actual = $this->_sanitize( $this->option_many_empty_repeatables );
		$this->assertCount( 0, $actual['arbitrary_tags'] );
	}

	/**
	 * Test that empty repeatable fields can be sanitized twice and still be arrays.
	 *
	 * @see https://core.trac.wordpress.org/ticket/21989.
	 */
	function test_double_sanitizing() {
		$actual = $this->_sanitize( $this->option_empty_repeatable );
		$actual = $this->_sanitize( $actual );
		$this->assertInternalType( 'array', $actual['arbitrary_tags'] );

		$actual = $this->_sanitize( $this->option_many_empty_repeatables );
		$actual = $this->_sanitize( $actual );
		$this->assertInternalType( 'array', $actual['arbitrary_tags'] );
	}

}

