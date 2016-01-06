<?php
/**
 * Tests for the settings page, the settings fields, and settings section help text.
 *
 * @package WP SEO
 */
class WP_SEO_Settings_Page_Tests extends WP_UnitTestCase {

	function tearDown() {
		parent::tearDown();
		// Clean up after the one-off post types and taxonomies.
		_wp_seo_reset_post_types();
		_wp_seo_reset_taxonomies();
	}

	/**
	 * Make sure we have a settings page.
	 */
	function test_add_options_page() {
		wp_set_current_user( 1 );
		WP_SEO_Settings()->add_options_page();

		global $submenu;
		$this->assertContains(
			array(
				'SEO',
				'manage_options',
				'wp-seo',
				'WP SEO Settings',
			),
			$submenu['options-general.php']
		);
	}

	/**
	 * Make sure we have a help tab.
	 */
	function test_add_help_tab() {
		set_current_screen( 'front' );
		WP_SEO_Settings()->add_help_tab();

		$actual = get_current_screen()->get_help_tab( 'formatting-tags' );
		// Not all versions we test against include the priority.
		if ( isset( $actual['priority'] ) ) {
			unset( $actual['priority'] );
		}

		$this->assertEquals( $actual, array(
			'id'       => 'formatting-tags',
			'title'    => 'Formatting Tags',
			'content'   => '',
			'callback' => array( WP_SEO_Settings(), 'view_formatting_tags_help_tab' ),
		) );
	}

	/**
	 * Test that the "example URL" method includes the text and any included link.
	 */
	function test_example_url() {
		$html = get_echo( array( WP_SEO_Settings(), 'example_url' ), array( 'Demo text' ) );
		$this->assertSame( '<p class="description">Demo text</p>', $html );

		$html = get_echo( array( WP_SEO_Settings(), 'example_url' ), array( 'Demo text', 'http://wordpress.org' ) );
		$this->assertContains( '<code><a href="http://wordpress.org" target="_blank">http://wordpress.org</a></code>', $html );
	}

	/**
	 * Test that the example of a Post includes a link to the latest post.
	 */
	function test_example_permalink() {
		$post_ID = $this->factory->post->create();
		$section = array( 'id' => 'single_post' );
		$html = get_echo( array( WP_SEO_Settings(), 'example_permalink' ), array( $section ) );
		$this->assertContains( get_permalink( $post_ID ), $html );
	}

	/**
	 * Test that the example of a new custom post type displays the fallback string.
	 */
	function test_example_permalink_no_posts() {
		register_post_type( 'demo' );
		$section = array( 'id' => 'single_demo' );
		$html = get_echo( array( WP_SEO_Settings(), 'example_permalink' ), array( $section ) );
		$this->assertContains( 'No posts yet.', $html );
	}

	/**
	 * Test that the example of a term archive includes a link to the newest term.
	 */
	function test_example_term_archive() {
		$category_ID = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		wp_set_object_terms( $this->factory->post->create(), $category_ID, 'category' );

		$section = array( 'id' => 'archive_category' );
		$html = get_echo( array( WP_SEO_Settings(), 'example_term_archive' ), array( $section ) );

		$this->assertContains( get_term_link( $category_ID, 'category' ), $html );
	}

	/**
	 * Test that the example of a new taxonomy displays the fallback string.
	 */
	function test_example_term_archive_no_terms() {
		register_taxonomy( 'demo', 'post' );

		$section = array( 'id' => 'archive_demo' );
		$html = get_echo( array( WP_SEO_Settings(), 'example_term_archive' ), array( $section ) );

		$this->assertContains( 'No terms yet.', $html );
	}

	/**
	 * Test that the example of a post type archive includes the right link.
	 */
	function test_example_post_type_archive() {
		register_post_type( 'demo', array( 'has_archive' => true ) );
		$this->factory->post->create( array( 'post_type' => 'demo' ) );

		$section = array( 'id' => 'archive_demo' );
		$html = get_echo( array( WP_SEO_Settings(), 'example_post_type_archive' ), array( $section ) );

		$this->assertContains( get_post_type_archive_link( 'demo' ), $html );
	}

	/**
	 * Test that the example of a post type without archive support is blank.
	 */
	function test_example_post_type_archive_no_support() {
		register_post_type( 'demo' );
		$this->factory->post->create( array( 'post_type' => 'demo' ) );

		$section = array( 'id' => 'archive_demo' );
		$html = get_echo( array( WP_SEO_Settings(), 'example_post_type_archive' ), array( $section ) );

		$this->assertEmpty( $html );
	}

	/**
	 * Test that the example of a date archive includes this year and month.
	 *
	 * Before testing for the current month, remove the current year to avoid a
	 * false positive in January.
	 */
	function test_example_date_archive() {
		$html = get_echo( array( WP_SEO_Settings(), 'example_date_archive' ) );
		$this->assertContains( date( 'Y' ), $html );
		$this->assertContains( date( 'm' ), str_replace( date( 'Y' ), '', $html ) );
	}

	/**
	 * Test that the example of an author archive includes the URL for this user.
	 */
	function test_example_author_archive() {
		$html = get_echo( array( WP_SEO_Settings(), 'example_author_archive' ) );
		$this->assertContains( get_author_posts_url( get_current_user_id() ), $html );
	}

	/**
	 * Test that the example of a search link includes a search query string.
	 */
	function test_example_search_page() {
		$html = get_echo( array( WP_SEO_Settings(), 'example_search_page' ) );
		$this->assertContains( get_search_link( 'wordpress' ), $html );
	}

	/**
	 * Test that the example 404 page includes the hashed blog URL.
	 */
	function test_example_404_page() {
		$html = get_echo( array( WP_SEO_Settings(), 'example_404_page' ) );
		$this->assertContains( md5( get_bloginfo( 'url' ) ), $html );
	}

	/**
	 * Test the various states of the field() helper method.
	 */
	function test_field() {
		// No field.
		$html = get_echo( array( WP_SEO_Settings(), 'field' ), array( array() ) );
		$this->assertEmpty( $html );

		// No type? Use a text field.
		$html = get_echo( array( WP_SEO_Settings(), 'field' ), array( array( 'field' => 'demo' ) ) );
		$this->assertRegExp( '/<input[^>]+type="text"[^>]+name="wp-seo\[demo\]"/', $html );

		// Check that a value is passed.
		WP_SEO_Settings()->options['demo'] = 'demo value';
		$html = get_echo( array( WP_SEO_Settings(), 'field' ), array( array( 'field' => 'demo' ) ) );
		$this->assertRegExp( '/<input[^>]+type="text"[^>]+value="demo value"/', $html );

		// Check the rendered field types.
		$html = get_echo( array( WP_SEO_Settings(), 'field' ), array( array( 'field' => 'demo', 'type' => 'textarea' ) ) );
		$this->assertContains( '<textarea', $html );

		$html = get_echo( array( WP_SEO_Settings(), 'field' ), array( array(
			'field' => 'demo',
			'type' => 'checkboxes',
			'boxes' => array( 'foo' => 'bar' )
		) ) );
		$this->assertRegExp( '/<input[^>]+type="checkbox"/', $html );

		$html = get_echo( array( WP_SEO_Settings(), 'field' ), array(
			array(
				'field' => 'demo_repeatable', // Not "demo," which does have a value.
				'type' => 'repeatable',
				'repeat' => array( 'foo' => 'bar' )
			),
		) );
		$this->assertRegExp( '/<input[^>]+type="text"/', $html );
	}

	/**
	 * Test the default text field output and the output with all args.
	 */
	function test_render_text_field() {
		$html = get_echo( array( WP_SEO_Settings(), 'render_text_field' ), array(
			array( 'field' => 'demo', 'type' => 'text' ),
			'demo value',
		) );

		// Look for the correct attributes: type of "text," field name and value.
		$this->assertContains( 'type="text"', $html );
		$this->assertContains( 'name="wp-seo[demo]"', $html );
		$this->assertContains( 'value="demo value"', $html );

		// Expect the field to have some size.
		$this->assertRegExp( '/size="\d+"/', $html );

		$html = get_echo( array( WP_SEO_Settings(), 'render_text_field' ), array(
			array( 'type' => 'number', 'field' => 'demo', 'size' => 5 ),
			'40',
		) );
		$this->assertContains( 'type="number"', $html );
		$this->assertContains( 'value="40"', $html );
		$this->assertContains( 'size="5"', $html );
	}

	/**
	 * Test the default textarea output and the output with all args.
	 */
	function test_render_textarea() {
		$html = get_echo( array( WP_SEO_Settings(), 'render_textarea' ), array(
			array( 'field' => 'demo', ),
			'demo value',
		) );

		// Expect at least a textarea, with with the field name and the correct value.
		$this->assertRegExp( '/<textarea[^>]+name="wp-seo\[demo\]"[^>]+>demo value<\/textarea>/', $html );

		// Expect some row and column sizes.
		$this->assertRegExp( '/rows="\d+"/', $html );
		$this->assertRegExp( '/cols="\d+"/', $html );
	}

	function test_render_checkboxes() {
		$html = get_echo( array( WP_SEO_Settings(), 'render_checkboxes' ), array(
			array(
				'field' => 'demo',
				'boxes' => array(
					'page' => 'Page',
					'post_tag' => 'Tag',
				),
			),
			array( 'post_tag' ),
		) );

		// Expect two checkboxes.
		$this->assertSame( substr_count( $html, 'type="checkbox"' ), 2 );
		$this->assertContains( 'Page', $html );
		$this->assertContains( 'Tag', $html );
		// Expect only the "Tag" checkbox to be checked.
		$this->assertRegExp( '/<input[^>]+type="checkbox"[^>]+value="post_tag"[^>]+checked/', $html );
		$this->assertNotRegExp( '/<input[^>]+type="checkbox"[^>]+value="page"[^>]+checked/', $html );
	}

	/**
	 * Test the default repeatable field output and the output with args.
	 */
	function test_render_repeatable() {
		$args = array(
			'field' => 'demo',
			'repeat' => array(
				'first_name' => 'First name',
				'last_name' => 'Last Name',
			),
		);

		$html = get_echo( array( WP_SEO_Settings(), 'render_repeatable_field' ), array( $args, array() ) );

		// Expect a "name" attribute in with the counter for the template.
		$this->assertContains( 'name="wp-seo[demo][<%= i %>][first_name]"', $html );

		// No values: We repeat two fields, so expect four inputs, two each for input and the template.
		$this->assertSame( substr_count( $html, '<input class="repeatable" type="text"' ), 4 );
		// No values: The "name" attribute of our field should be "0", not "1."
		$this->assertContains( 'name="wp-seo[demo][0][first_name]"', $html );
		$this->assertNotContains( 'name="wp-seo[demo][1][first_name]"', $html );
		// No values: Expect that the template starts at "1."
		$this->assertContains( 'data-start="1"', $html );

		$args['size'] = '40';
		$html = get_echo( array( WP_SEO_Settings(), 'render_repeatable_field' ), array( $args, array(
			array(
				'first_name' => 'Millard',
				'last_name' => 'Fillmore',
			),
			array(
				'first_name' => 'William Howard',
				'last_name' => 'Taft'
			),
			array(
				'first_name' => 'James',
				'last_name' => 'Buchanan',
			),
		) ) );

		// Three values: Expect eight inputs (there isn't a blank one).
		$this->assertSame( substr_count( $html, '<input class="repeatable"' ), 8 );
		// $args changed: Each input should be size 40
		$this->assertSame( substr_count( $html, 'size="40"' ), 8 );
		// The "name" attribute should go up to "2."
		$this->assertContains( 'name="wp-seo[demo][2][first_name]"', $html );
		$this->assertNotContains( 'name="wp-seo[demo][3][first_name]"', $html );
		// Expect that the template starts at "3."
		$this->assertContains( 'data-start="3"', $html );
		// Look for our field values.
		$this->assertRegExp( '/<input[^>]+class="repeatable"[^>]+\[1\]\[first_name\][^>]+value="William Howard"[^>]+>/', $html );
		$this->assertRegExp( '/<input[^>]+class="repeatable"[^>]+\[2\]\[last_name\][^>]+value="Buchanan"[^>]+>/', $html );
	}

}
