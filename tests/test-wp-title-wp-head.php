<?php
/**
 * Tests for functions that hook into wp_title() and wp_head.
 *
 * @package WP SEO
 */
class WP_SEO_WP_Title_WP_Head_Tests extends WP_UnitTestCase {

	var $taxonomy  = 'demo_taxonomy';
	var $post_type = 'demo_post_type';
	var $options   = array();

	function setUp() {
		parent::setUp();

		register_taxonomy( $this->taxonomy, 'post' );
		register_post_type( $this->post_type, array( 'rewrite' => true, 'has_archive' => true, 'public' => true ) );
		WP_SEO_Settings()->set_properties();

		$this->_update_option_for_tests();
		WP_SEO_Settings()->set_options();

		global $wp_rewrite;
		$wp_rewrite->init();
		$wp_rewrite->flush_rules();
	}

	function tearDown() {
		parent::tearDown();
		// Leave the place as we found it.
		_wp_seo_reset_post_types();
		_wp_seo_reset_taxonomies();
		delete_option( WP_SEO_Settings::SLUG );
		WP_SEO_Settings()->set_properties();
		WP_SEO_Settings()->set_options();
	}

	/**
	 * Update the plugin option with titles, descriptions, and keywords for each test.
	 *
	 * This option should include all of the expected values used in these
	 * tests. Not each test uses all values, but setting them all is a little
	 * cleaner, and the option has to be set one way or another.
	 */
	function _update_option_for_tests() {
		$this->options['post_types'] = array( 'post' );
		$this->options['taxonomies'] = array( 'category' );
		$this->options['arbitrary_tags'] = array(
			array(
				'name' => 'demo arbitrary title',
				'content' => 'demo arbitrary content',
			),
		);

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

		if ( function_exists( 'wp_get_document_title' ) ) {
			$this->assertSame( $title, wp_get_document_title() );
		}
	}

	/**
	 * Test that WP_SEO::wp_head() echoes all <meta> tags with expected values.
	 *
	 * @param  string $description The expected meta description content.
	 * @param  string $keywords The expected meta keywords content.
	 */
	function _assert_all_meta( $description, $keywords ) {
		$expected = <<<EOF
<meta name='description' content='{$description}' /><!-- WP SEO -->
<meta name='keywords' content='{$keywords}' /><!-- WP SEO -->
<meta name='demo arbitrary title' content='demo arbitrary content' /><!-- WP SEO -->
EOF;
		$this->assertSame( strip_ws( $expected ), strip_ws( get_echo( array( WP_SEO(), 'wp_head' ) ) ) );
	}

	/**
	 * Test that WP_SEO::wp_head() echoes only the arbitrary <meta> tags.
	 */
	function _assert_arbitrary_meta() {
				$expected = <<<EOF
<meta name='demo arbitrary title' content='demo arbitrary content' /><!-- WP SEO -->
EOF;
		$this->assertSame( strip_ws( $expected ), strip_ws( get_echo( array( WP_SEO(), 'wp_head' ) ) ) );
	}

	/**
	 * Wrapper for checking _assert_title() and _assert_all_meta() on option values.
	 *
	 * @param  string $key The option to test. Use a name that prefixes
	 *     '_title', '_description', and '_keywords' in the option, like 'home'.
	 */
	function _assert_option_filters( $key ) {
		$this->_assert_title( $this->options[ "{$key}_title" ] );
		$this->_assert_all_meta( $this->options["{$key}_description"], $this->options["{$key}_keywords"] );
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
		$this->_assert_all_meta( '_custom_meta_description', '_custom_meta_keywords' );
	}

	// If there is no format string, return the original post title.
	function test_no_format_string() {
		add_filter( 'wp_seo_title_tag_format', '__return_false' );
		$title = rand_str();
		$this->go_to( get_permalink( $this->factory->post->create( array( 'post_title' => $title ) ) ) );
		// The site name doesn't appear in all versions we test against; just check for our title.
		$this->assertContains( $title, wp_title( '&raquo;', false ) );
		// WP_UnitTestCase::_restore_hooks() was introduced in 4.0.
		remove_filter( 'wp_seo_title_tag_format', '__return_false' );
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
		$this->_assert_all_meta( '_custom_description', '_custom_keywords' );
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
		$this->assertContains( $sep, wp_title( $sep, false ) );

		$this->assertEmpty( get_echo( array( WP_SEO(), 'wp_head' ) ) );
	}

	/**
	 * Test that WP_SEO::meta_field() rejects non-string input.
	 */
	function test_invalid_meta_field() {
		delete_option( WP_SEO_Settings::SLUG );
		WP_SEO_Settings()->set_options();

		update_option( WP_SEO_Settings::SLUG, array(
			'arbitrary_tags' => array(
				'name' => 'foo',
				'value' => new WP_Error(),
			),
		) );

		$this->go_to( '/' );

		$this->assertEmpty( get_echo( array( WP_SEO(), 'wp_head' ) ) );
	}

}
