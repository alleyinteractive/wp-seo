<?php
/**
 * WP SEO Tests: Tests for whether submitted data are sanitized correctly before saving as options.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;
use WP_SEO_Settings;

class SanitizeOptionsTest extends TestCase {
	var $option_valid = [
		'home_title'       => 'Home | Alley Interactive',
		'home_description' => 'We are a team of experienced digital professionals who tackle the most complex challenges facing top publishers.',
		'arbitrary_tags'   => [
			[ 'name' => 'viewport', 'content' => 'width=device-width, initial-scale=1' ],
		],
	];

	var $option_empty_repeatable = [
		'arbitrary_tags' => [ [ 'name' => '', 'content' => '' ] ],
	];

	var $option_many_empty_repeatables = [
		'arbitrary_tags' => [ [ 'name' => '', 'content' => '' ], [ 'name' => '', 'content' => '' ], [ 'name' => '', 'content' => '' ] ],
	];

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
		$actual = $this->_sanitize( [ 'home_title' => 'That is <strong>not</strong> allowed.' ] );
		$this->assertSame( $actual['home_title'], 'That is not allowed.' );
	}

	// Test that non-post types and non-taxonomies are removed.
	function test_invalid_objects() {
		$actual = $this->_sanitize( [
			'post_types' => [ 'post', 'page', 'foo' ],
			'taxonomies' => [ 'category', 'post_tag', 'bar' ],
		] );
		$this->assertSame( $actual['post_types'], [ 'post', 'page' ] );
		$this->assertSame( $actual['taxonomies'], [ 'category', 'post_tag' ] );
	}

	// Test that keys with empty values are still included in the option array.
	function test_missing_keys() {
		$actual = $this->_sanitize( [
			'home_title' => '',
			'home_description' => '',
		] );
		$this->assertArrayHasKey( 'arbitrary_tags', $actual );
	}

	/**
	 * Test that values of the wrong type are included in the array as empty
	 * versions of the correct type, and that unknown keys are removed.
	 */
	function test_illegal_elements() {
		$actual = $this->_sanitize( [
			'home_title'     => [ 'Not a string' ],
			'post_types'     => 'post',
			'taxonomies'     => 'category',
			'unknown_key'    => 'Unknown value.',
			'arbitrary_tags' => null,
		] );
		$this->assertEmpty( $actual['post_types'] );
		$this->assertIsArray( $actual['post_types'] );
		$this->assertEmpty( $actual['taxonomies'] );
		$this->assertIsArray( $actual['taxonomies'] );
		$this->assertArrayNotHasKey( 'unknown_key', $actual );
		$this->assertIsArray( $actual['arbitrary_tags'] );
	}

	function test_invalid_repeatables() {
		$actual = $this->_sanitize( [
			'arbitrary_tags' => [
				[ 'content' => 'Unknown' ],
				[ 'name' => '', 'content' => '' ],
				[ 'name' => 'application-name', 'content' => 'WP SEO' ],
				[],
			],
		] );
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
		$this->assertIsArray( $actual['arbitrary_tags'] );

		$actual = $this->_sanitize( $this->option_many_empty_repeatables );
		$actual = $this->_sanitize( $actual );
		$this->assertIsArray( $actual['arbitrary_tags'] );
	}

}

