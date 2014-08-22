<?php
/**
 * Tests for outputting content into wp_title() and wp_head().
 *
 * @package  WP SEO
 */
class WP_SEO_WP_Title_WP_Head_Tests extends WP_UnitTestCase {

	var $taxonomy  = 'demo_taxonomy';
	var $post_type = 'demo_post_type';
	var $options   = array();

	function setUp() {
		parent::setUp();

		register_taxonomy( $this->taxonomy, 'post' );
		register_post_type( $this->post_type, array( 'rewrite' => true, 'has_archive' => true, 'public' => true ) );

		$this->_update_option_for_tests();
		WP_SEO_Settings()->reset_properties();
	}

	/**
	 * Update the option with title, description, and keyword values for each test.
	 *
	 * This option should include all of the values used in these tests. Not
	 * each test uses all values, but setting them all is a little cleaner, and
	 * the option has to be set one way or another.
	 */
	function _update_option_for_tests() {
		$this->options['post_types'] = array( 'post' );
		$this->options['taxonomies'] = array( 'category' );

		foreach ( array(
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
		) as $key ) {
			$this->options[ "{$key}_title" ]       = "demo_{$key}_title";
			$this->options[ "{$key}_description" ] = "demo_{$key}_description";
			$this->options[ "{$key}_keywords" ]    = "demo_{$key}_keywords";
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
	}

	/**
	 * Test that WP_SEO::wp_head() echoes <meta> tags with expected values.
	 *
	 * @param  string $description The expected meta description content.
	 * @param  string $keywords The expected meta keywords content.
	 */
	function _assert_meta( $description, $keywords ) {
		$expected = <<<EOF
<meta name='description' content='{$description}' />
<meta name='keywords' content='{$keywords}' />
EOF;
		$this->assertSame( strip_ws( $expected ), strip_ws( get_echo( array( WP_SEO(), 'wp_head' ) ) ) );
	}

	/**
	 * Wrapper for checking _assert_title() and _assert_meta() on option values.
	 *
	 * @param  string $key The option to test. Use a name that prefixes
	 *     '_title', '_description', and '_keywords' in the option, like 'home'.
	 */
	function _assert_option_filters( $key ) {
		$this->_assert_title( $this->options[ "{$key}_title" ] );
		$this->_assert_meta( $this->options["{$key}_description"], $this->options["{$key}_keywords"] );
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
		$this->go_to( get_permalink( $this->factory->post->create( array( 'post_type' => $this->post_type ) ) ) );
		$this->_assert_option_filters( "single_{$this->post_type}" );
	}

	// A post with custom values should not use the single_{type}_ values.
	function test_single_custom() {
		$this->go_to( get_permalink( $post_ID = $this->factory->post->create() ) );
		update_post_meta( $post_ID, '_meta_title', '_custom_meta_title' );
		update_post_meta( $post_ID, '_meta_description', '_custom_meta_description' );
		update_post_meta( $post_ID, '_meta_keywords', '_custom_meta_keywords' );
		$this->_assert_title( '_custom_meta_title' );
		$this->_assert_meta( '_custom_meta_description', '_custom_meta_keywords' );
	}

	function test_home() {
		$this->go_to( '/' );
		$this->_assert_option_filters( 'home' );
	}

	function test_author_archive() {
		$author_ID = $this->factory->user->create( array( 'user_login' => 'user-a' ) );
		$this->factory->post->create( array( 'post_author' => $author_ID ) );
		$this->go_to( get_author_posts_url( $author_ID ) );
		$this->_assert_option_filters( 'archive_author' );
	}

	function test_category() {
		$category_ID = $this->factory->term->create( array( 'name' => 'cat-a', 'taxonomy' => 'category' ) );
		$this->go_to( get_term_link( $category_ID, 'category' ) );
		$this->_assert_option_filters( 'archive_category' );
	}

	function test_tax() {
		$term_ID = $this->factory->term->create( array( 'name' => 'demo-a', 'taxonomy' => $this->taxonomy ) );
		$this->go_to( get_term_link( $term_ID, $this->taxonomy ) );
		$this->_assert_option_filters( "archive_{$this->taxonomy}" );
	}

	// A term with custom values should not use the archive_{taxonomy}_ fields.
	function test_category_custom() {
		$term_ID = $this->factory->term->create( array( 'name' => 'cat-b', 'taxonomy' => 'category' ) );
		update_option( WP_SEO()->get_term_option_name( get_term( $term_ID, 'category' ) ), array( 'title' => '_custom_title', 'description' => '_custom_description', 'keywords' => '_custom_keywords' ) );
		$this->go_to( get_term_link( $term_ID, 'category' ) );
		$this->_assert_title( '_custom_title' );
		$this->_assert_meta( '_custom_description', '_custom_keywords' );
	}

	function test_post_type_archive() {
		$this->go_to( get_post_type_archive_link( $this->post_type ) );
		$this->_assert_option_filters( "archive_{$this->post_type}" );
	}

	function test_date_archive() {
		$this->factory->post->create( array( 'post_date' => '2007-09-04 12:34' ) );
		$this->go_to( get_day_link( '2007', '09', '04' ) );
		$this->_assert_option_filters( 'archive_date' );
	}

	// No <meta> support.
	function test_search() {
		$this->go_to( get_search_link( 'wp-seo' ) );
		$this->_assert_title( 'demo_search_title' );
	}

	// No <meta> support.
	function test_404() {
		$this->go_to( get_day_link( '2014', '13', '13' ) );
		$this->_assert_title( 'demo_404_title' );
	}

	/**
	 * Proxy for testing an unsupported page.
	 *
	 * This tests both that even if somehow $key became true for a feed, there
	 * would be no setting for it.
	 */
	function test_feed() {
		$this->go_to( get_feed_link() );
		$this->assertEmpty( wp_title( '|', false ) );
	}

}