<?php
/**
 * WP SEO Tests: Tests for functions that hook into wp_title() and wp_head.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;
use Mantle\Testing\Utils;
use WP_SEO_Settings;
use WP_SEO;

class WPTitleWPHeadTest extends TestCase {

	var string $taxonomy  = 'demo_taxonomy';
	var string $post_type = 'demo_post_type';
	var array $options   = [];

	function setUp(): void {
		parent::setUp();
		register_taxonomy( $this->taxonomy, 'post' );
		register_post_type( $this->post_type, [ 'rewrite' => true, 'has_archive' => true, 'public' => true ] );
		WP_SEO_Settings()->set_properties();

		$this->_update_option_for_tests();
		WP_SEO_Settings()->set_options();

		global $wp_rewrite;
		$wp_rewrite->init();
		$wp_rewrite->flush_rules();
	}

	function tearDown(): void {
		parent::tearDown();
		// Leave the place as we found it.
		_wp_seo_reset_post_types();
		_wp_seo_reset_taxonomies();
		delete_option( WP_SEO_Settings::SLUG );
		WP_SEO_Settings()->set_properties();
		WP_SEO_Settings()->set_options();
	}

	/**
	 * Update the plugin option with data for each test.
	 *
	 * This option should include all of the expected values used in these
	 * tests. Not each test uses all values, but setting them all is a little
	 * cleaner, and the option has to be set one way or another.
	 */
	function _update_option_for_tests() {
		// wp_robots() only renders robots meta for post types/taxonomies enabled
		// here (has_post_fields()/has_term_fields()), unlike the title/description/
		// canonical settings fallback, which applies regardless - so the custom
		// post type and taxonomy this test registers must be included too.
		$this->options['post_types'] = [ 'post', $this->post_type ];
		$this->options['taxonomies'] = [ 'category', $this->taxonomy ];
		$this->options['arbitrary_tags'] = [
			[
				'name' => 'demo arbitrary title',
				'content' => 'demo arbitrary content',
			],
		];
		$this->options['robots_meta_directives'] = [
			[ 'label' => 'NoIndex', 'value' => 'noindex', 'description' => 'Directive description' ],
			[ 'label' => 'NoFollow', 'value' => 'nofollow', 'description' => 'Directive description' ],
		];

		foreach ( [
			'home',
			'single_post',
			"single_{$this->post_type}",
			'archive_author',
			'archive_category',
			"archive_{$this->taxonomy}",
			"archive_{$this->post_type}",
			'archive_date',
			'search',
			'404',
			'feed',
		] as $key ) {
			$this->options[ "{$key}_title" ]         = "demo_{$key}_title";
			$this->options[ "{$key}_description" ]   = "demo_{$key}_description";
			$this->options[ "{$key}_canonical_url" ] = "http://demo_{$key}_canonical_url";
			$this->options[ "{$key}_robots" ]        = [ 'noindex' ];
		}

		update_option( WP_SEO_Settings::SLUG, WP_SEO_Settings()->sanitize_options( $this->options ) );
	}

	/**
	 * Test that a value matches wp_title().
	 *
	 * @param  string $title The expected value.
	 */
	function _assert_title( $title ) {
		$this->assertSame( $title, wp_title( '|', false, 'right' ) );

		if ( function_exists( 'wp_get_document_title' ) ) {
			$this->assertSame( $title, wp_get_document_title() );
		}
	}

	/**
	 * Test that WP_SEO::wp_head() and the core wp_robots filter it hooks into
	 * together produce all expected <meta> tags.
	 *
	 * Robots meta output no longer comes from WP_SEO::wp_head() itself - it's
	 * rendered by core's wp_robots(), hooked separately to wp_head and fed by
	 * the wp_robots filter WP_SEO::wp_robots() hooks into. So this captures
	 * both to reconstruct the full <head> output.
	 *
	 * @param  string   $description   The expected meta description content.
	 * @param  string[] $robots        The expected enabled robots directives (e.g. [ 'noindex' ]).
	 */
	function _assert_all_meta( $description, $robots ) {
		// wp_head() also unconditionally prints the canonical link (tested
		// separately via _assert_canonical()), so this checks that each expected
		// line is present rather than requiring an exact full-output match.
		$actual = strip_ws( Utils::get_echo( [ WP_SEO(), 'wp_head' ] ) . Utils::get_echo( 'wp_robots' ) );

		$this->assertStringContainsString( "<meta name='description' content='{$description}' /><!-- WP SEO -->", $actual );
		$this->assertStringContainsString( "<meta name='demo arbitrary title' content='demo arbitrary content' /><!-- WP SEO -->", $actual );

		// Core's own default wp_robots callbacks (e.g. max-image-preview:large)
		// may also be present, so only check WP_SEO's own contribution rather
		// than requiring an exact match on the whole content attribute.
		preg_match( "/<meta name='robots' content='([^']*)'/", $actual, $matches );
		$robots_content = $matches[1] ?? '';

		foreach ( [ 'noindex', 'nofollow' ] as $directive ) {
			if ( in_array( $directive, $robots, true ) ) {
				$this->assertStringContainsString( $directive, $robots_content );
			} else {
				$this->assertStringNotContainsString( $directive, $robots_content );
			}
		}
	}

	/**
	 * Test that WP_SEO::wp_head() echoes only the arbitrary <meta> tags.
	 */
	function _assert_arbitrary_meta() {
		$expected = <<<EOF
<meta name='demo arbitrary title' content='demo arbitrary content' /><!-- WP SEO -->
EOF;

		$this->assertSame( strip_ws( $expected ), strip_ws( Utils::get_echo( [ WP_SEO(), 'wp_head' ] ) ) );
	}

	/**
	 * Test that WP_SEO::wp_head() echoes <link> canonical tag with expected value.
	 * 
	 * @param string $canonical_url The expected canonical URL.
	 */
	function _assert_canonical( $canonical_url ) {
		$expected = "<link rel='canonical' href='{$canonical_url}' /><!-- WP SEO -->";
		$this->assertStringContainsString( $expected, strip_ws( Utils::get_echo( [ WP_SEO(), 'wp_head' ] ) ) );
	}

	/**
	 * Wrapper for checking _assert_title(), _assert_all_meta() and
	 * _assert_canonical() on option values.
	 *
	 * @param  string $key The option to test. Use a name that prefixes
	 *     '_title' and '_description' in the option, like 'home'.
	 */
	function _assert_option_filters( $key ) {
		$this->_assert_title( $this->options[ "{$key}_title" ] );
		$this->_assert_all_meta(
			$this->options["{$key}_description"],
			$this->options["{$key}_robots"],
		);
		$this->_assert_canonical( $this->options["{$key}_canonical_url"] );
	}

	/**
	 * Tests for the core filters on each supported type of request.
	 *
	 * Most requests should be subject to _assert_option_filters(), at least.
	 */

	function test_single() {
		$this->go_to( get_permalink( $this->factory->post->create() ) );
		$this->_assert_option_filters( 'single_post' );
	}

	function test_singular() {
		$this->go_to( get_permalink( $this->factory->post->create( [ 'post_type' => $this->post_type ] ) ) );
		$this->_assert_option_filters( "single_{$this->post_type}" );
	}

	// A post with custom values should not use the single_{type}_ values.
	function test_single_custom() {
		$this->go_to( get_permalink( $post_ID = $this->factory->post->create() ) );
		update_post_meta( $post_ID, '_meta_title', '_custom_meta_title' );
		update_post_meta( $post_ID, '_meta_description', '_custom_meta_description' );
		update_post_meta( $post_ID, '_meta_canonical_url', 'http://_custom_canonical_url' );
		update_post_meta( $post_ID, '_meta_robots_noindex', '1' );
		update_post_meta( $post_ID, '_meta_robots_nofollow', '' );
		$this->_assert_title( '_custom_meta_title' );
		$this->_assert_all_meta( '_custom_meta_description', [ 'noindex' ] );
		$this->_assert_canonical( 'http://_custom_canonical_url' );
	}

	// If there is no format string, return the original post title.
	function test_no_format_string() {
		add_filter( 'wp_seo_title_tag_format', '__return_false' );
		$title = rand_str();
		$this->go_to( get_permalink( $this->factory->post->create( [ 'post_title' => $title ] ) ) );
		// The site name doesn't appear in all versions we test against; just check for our title.
		$this->assertStringContainsString( $title, wp_title( '&raquo;', false ) );
		// WP_UnitTestCase::_restore_hooks() was introduced in 4.0.
		remove_filter( 'wp_seo_title_tag_format', '__return_false' );
	}

	function test_home() {
		$this->go_to( '/' );
		$this->_assert_option_filters( 'home' );
	}

	function test_author_archive() {
		$author_ID = $this->factory->user->create( [ 'user_login' => 'user-a' ] );
		$this->factory->post->create( [ 'post_author' => $author_ID ] );
		$this->go_to( get_author_posts_url( $author_ID ) );
		$this->_assert_option_filters( 'archive_author' );
	}

	function test_category() {
		$category_ID = $this->factory->term->create( [ 'name' => 'cat-a', 'taxonomy' => 'category' ] );
		$this->go_to( get_term_link( $category_ID, 'category' ) );
		$this->_assert_option_filters( 'archive_category' );
	}

	function test_tax() {
		$term_ID = $this->factory->term->create( [ 'name' => 'demo-a', 'taxonomy' => $this->taxonomy ] );
		$this->go_to( get_term_link( $term_ID, $this->taxonomy ) );
		$this->_assert_option_filters( "archive_{$this->taxonomy}" );
	}

	// A term with custom values should not use the archive_{taxonomy}_ fields.
	function test_category_custom() {
		$term_ID = $this->factory->term->create( [ 'name' => 'cat-b', 'taxonomy' => 'category' ] );
		update_option(
			WP_SEO()->get_term_option_name(
				get_term( $term_ID, 'category' )
			),
			[
				'title' => '_custom_title',
				'description' => '_custom_description',
				'canonical_url' => 'http://_custom_canonical_url',
				'robots_noindex' => '1',
				'robots_nofollow' => '',
			],
		);
		$this->go_to( get_term_link( $term_ID, 'category' ) );
		$this->_assert_title( '_custom_title' );
		$this->_assert_all_meta( '_custom_description', [ 'noindex' ] );
		$this->_assert_canonical( 'http://_custom_canonical_url' );
	}

	function test_post_type_archive() {
		$this->go_to( get_post_type_archive_link( $this->post_type ) );
		$this->_assert_option_filters( "archive_{$this->post_type}" );
	}

	function test_date_archive() {
		$this->factory->post->create( [ 'post_date' => '2007-09-04 12:34' ] );
		$this->go_to( get_day_link( '2007', '09', '04' ) );
		$this->_assert_option_filters( 'archive_date' );
	}

	// No <meta> support.
	function test_search() {
		$this->go_to( get_search_link( 'wp-seo' ) );
		$this->_assert_title( 'demo_search_title' );
		$this->_assert_arbitrary_meta();
	}

	// No <meta> support.
	function test_404() {
		$this->go_to( get_day_link( '2014', '13', '13' ) );
		$this->_assert_title( 'demo_404_title' );
		$this->_assert_arbitrary_meta();
	}

	/**
	 * Proxy for testing an unsupported page.
	 *
	 * This tests both that nothing is output on a feed and that both if somehow
	 * $key became true for a feed, there would be no setting for it.
	 */
	function test_feed() {
		/**
		 * Valid feed URLs without posts returned 404 before WordPress 4.0.
		 *
		 * @see https://core.trac.wordpress.org/ticket/18505
		 */
		$this->factory->post->create();

		$this->go_to( get_feed_link() );
		$this->assertEmpty( wp_title( '|', false ) );
	}

	/**
	 * If no option exists, test that the title is the default and that no meta are rendered.
	 */
	function test_no_option() {
		delete_option( WP_SEO_Settings::SLUG );
		WP_SEO_Settings()->set_options();

		$this->go_to( get_permalink( $this->factory->post->create() ) );

		// Uses a random $sep to be sure it couldn't have come from us.
		$sep = rand_str();
		$this->assertStringContainsString( $sep, wp_title( $sep, false ) );

		$this->assertEmpty( Utils::get_echo( [ WP_SEO(), 'wp_head' ] ) );
	}

	/**
	 * Test that WP_SEO::meta_field() rejects non-string input.
	 */
	function test_invalid_meta_field() {
		delete_option( WP_SEO_Settings::SLUG );
		WP_SEO_Settings()->set_options();

		update_option( WP_SEO_Settings::SLUG, [
			'arbitrary_tags' => [
				'name' => 'foo',
				'value' => new \WP_Error(),
			],
		] );

		$this->go_to( '/' );

		$this->assertEmpty( Utils::get_echo( [ WP_SEO(), 'wp_head' ] ) );
	}

}
