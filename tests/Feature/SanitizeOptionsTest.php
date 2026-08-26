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

	// Test that non-post types are removed from the Twitter Card post types option.
	function test_twitter_card_post_types() {
		$actual = $this->_sanitize( [
			'twitter_card_post_types' => [ 'post', 'page', 'foo' ],
		] );
		$this->assertSame( $actual['twitter_card_post_types'], [ 'post', 'page' ] );
	}

	// Test that the default Open Graph image is sanitized down to an attachment ID.
	function test_default_open_graph_image() {
		$actual = $this->_sanitize( [
			'default_open_graph_image' => '42',
		] );
		$this->assertSame( 42, $actual['default_open_graph_image'] );
	}

	// Test that a missing default Open Graph image key defaults to 0.
	function test_default_open_graph_image_missing_key() {
		$actual = $this->_sanitize( [] );
		$this->assertSame( 0, $actual['default_open_graph_image'] );
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

	// Test that a disabled post type's previously saved fields survive a save
	// that doesn't include them (because its section isn't rendered).
	function test_disabled_post_type_preserves_previous_values() {
		update_option( WP_SEO_Settings::SLUG, [
			'post_types'         => [],
			'taxonomies'         => [],
			'single_post_title'  => 'Old Title',
			'single_post_robots' => [ 'noindex' ],
		] );
		WP_SEO_Settings()->set_options();

		$actual = $this->_sanitize( [
			'post_types' => [],
			'taxonomies' => [],
		] );

		$this->assertSame( 'Old Title', $actual['single_post_title'] );
		$this->assertSame( [ 'noindex' ], $actual['single_post_robots'] );
	}

	// Test that a disabled taxonomy's previously saved fields survive a save
	// that doesn't include them.
	function test_disabled_taxonomy_preserves_previous_values() {
		update_option( WP_SEO_Settings::SLUG, [
			'post_types'             => [],
			'taxonomies'             => [],
			'archive_category_title' => 'Old Category Title',
		] );
		WP_SEO_Settings()->set_options();

		$actual = $this->_sanitize( [
			'post_types' => [],
			'taxonomies' => [],
		] );

		$this->assertSame( 'Old Category Title', $actual['archive_category_title'] );
	}

	// Test that an enabled post type's submitted values still win over any
	// previously saved values.
	function test_enabled_post_type_honors_submitted_values() {
		update_option( WP_SEO_Settings::SLUG, [
			'post_types'        => [ 'post' ],
			'taxonomies'        => [],
			'single_post_title' => 'Old Title',
		] );
		WP_SEO_Settings()->set_options();

		$actual = $this->_sanitize( [
			'post_types'        => [ 'post' ],
			'single_post_title' => 'New Title',
		] );

		$this->assertSame( 'New Title', $actual['single_post_title'] );
	}

	// Test that an enabled post type's fields still null out when missing
	// from the submission, unchanged from prior behavior.
	function test_enabled_post_type_nulls_missing_field() {
		update_option( WP_SEO_Settings::SLUG, [
			'post_types'        => [ 'post' ],
			'taxonomies'        => [],
			'single_post_title' => 'Old Title',
		] );
		WP_SEO_Settings()->set_options();

		$actual = $this->_sanitize( [
			'post_types' => [ 'post' ],
		] );

		$this->assertNull( $actual['single_post_title'] );
	}

	// Test that fields with no post type/taxonomy owner always null out when
	// missing, regardless of the post_types/taxonomies option state.
	function test_non_type_specific_fields_always_null_when_missing() {
		update_option( WP_SEO_Settings::SLUG, [
			'post_types' => [],
			'taxonomies' => [],
		] );
		WP_SEO_Settings()->set_options();

		$actual = $this->_sanitize( [
			'post_types' => [],
			'taxonomies' => [],
		] );

		$this->assertNull( $actual['home_title'] );
		$this->assertNull( $actual['search_title'] );
		$this->assertNull( $actual['404_title'] );
		$this->assertNull( $actual['archive_author_title'] );
		$this->assertNull( $actual['archive_date_title'] );
	}

	// Test that a post type and a taxonomy sharing the same name don't
	// clobber each other's stored archive values when only one is enabled.
	function test_colliding_post_type_and_taxonomy_names_do_not_overwrite_each_other() {
		register_post_type( 'wpseo_demo_shared', [
			'public'      => true,
			'has_archive' => true,
			'label'       => 'Demo Shared',
		] );
		register_taxonomy( 'wpseo_demo_shared', 'post', [
			'public' => true,
			'label'  => 'Demo Shared',
		] );
		WP_SEO_Settings()->set_properties();

		update_option( WP_SEO_Settings::SLUG, [
			'post_types' => [ 'wpseo_demo_shared' ],
			'taxonomies' => [],
			'archive_term_wpseo_demo_shared_title' => 'Old Taxonomy Title',
		] );
		WP_SEO_Settings()->set_options();

		$actual = $this->_sanitize( [
			'post_types' => [ 'wpseo_demo_shared' ],
			'taxonomies' => [],
			'archive_post_wpseo_demo_shared_title' => 'New Post Type Title',
		] );

		$this->assertSame( 'New Post Type Title', $actual['archive_post_wpseo_demo_shared_title'] );
		$this->assertSame( 'Old Taxonomy Title', $actual['archive_term_wpseo_demo_shared_title'] );
	}

	protected function tearDown(): void {
		parent::tearDown();
		// Leave the place as we found it, so other tests see everything enabled.
		_wp_seo_reset_post_types();
		_wp_seo_reset_taxonomies();
		delete_option( WP_SEO_Settings::SLUG );
		WP_SEO_Settings()->set_properties();
		WP_SEO_Settings()->set_options();
	}

}

