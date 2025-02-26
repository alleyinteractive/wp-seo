<?php
/**
 * WP SEO Tests: Tests for the settings page, the settings fields, and settings section help text.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;
use Mantle\Testing\Utils;
use WP_SEO_Settings;
use WP_SEO;
use Mantle\Testing\Concerns\Admin_Screen;

class SettingsPageTest extends TestCase {
	use Admin_Screen;

	/**
	 * Make sure we have a settings page.
	 */
	function test_add_options_page() {
		wp_set_current_user( 1 );
		WP_SEO_Settings()->add_options_page();

		global $submenu;
		$this->assertContains(
			[
				'SEO',
				'manage_options',
				'wp-seo',
				'WP SEO Settings',
			],
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

		$this->assertEquals( $actual, [
			'id'       => 'formatting-tags',
			'title'    => 'Formatting Tags',
			'content'  => '',
			'callback' => [ WP_SEO_Settings(), 'view_formatting_tags_help_tab' ],
		] );
	}

	/**
	 * Test that the "example URL" method includes the text and any included link.
	 */
	function test_example_url() {

		$html = Utils::get_echo( [ WP_SEO_Settings(), 'example_url' ], [ 'Demo text' ] );
		$this->assertSame( '<p class="description">Demo text</p>', $html );

		$html = Utils::get_echo( [ WP_SEO_Settings(), 'example_url' ], [ 'Demo text', 'http://wordpress.org' ] );
		$this->assertStringContainsString( '<code><a href="http://wordpress.org" target="_blank">http://wordpress.org</a></code>', $html );
	}

	/**
	 * Test that the example of a Post includes a link to the latest post.
	 */
	function test_example_permalink() {
		$post_ID = $this->factory->post->create();

		$section = [ 'id' => 'single_post' ];
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'example_permalink' ], [ $section ] );

		$this->assertStringContainsString( get_permalink( $post_ID ), $html );
	}

	/**
	 * Test that the example of a new custom post type displays the fallback string.
	 */
	function test_example_permalink_no_posts() {
		register_post_type( 'demo' );

		$section = [ 'id' => 'single_demo' ];
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'example_permalink' ], [ $section ] );

		$this->assertStringContainsString( 'No posts yet.', $html );
	}

	/**
	 * Test that the example of a term archive includes a link to the newest term.
	 */
	function test_example_term_archive() {
		$category_ID = $this->factory->term->create( [ 'taxonomy' => 'category' ] );
		wp_set_object_terms( $this->factory->post->create(), $category_ID, 'category' );

		$section = [ 'id' => 'archive_category' ];
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'example_term_archive' ], [ $section ] );

		$this->assertStringContainsString( get_term_link( $category_ID, 'category' ), $html );
	}

	/**
	 * Test that the example of a new taxonomy displays the fallback string.
	 */
	function test_example_term_archive_no_terms() {
		register_taxonomy( 'demo', 'post' );

		$section = [ 'id' => 'archive_demo' ];
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'example_term_archive' ], [ $section ] );

		$this->assertStringContainsString( 'No terms yet.', $html );
	}

	/**
	 * Test that the example of a post type archive includes the right link.
	 */
	function test_example_post_type_archive() {
		register_post_type( 'demo', [ 'has_archive' => true ] );
		$this->factory->post->create( [ 'post_type' => 'demo' ] );

		$section = [ 'id' => 'archive_demo' ];
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'example_post_type_archive' ], [ $section ] );

		$this->assertStringContainsString( get_post_type_archive_link( 'demo' ), $html );
	}

	/**
	 * Test that the example of a post type without archive support is blank.
	 */
	function test_example_post_type_archive_no_support() {
		register_post_type( 'demo' );
		$this->factory->post->create( [ 'post_type' => 'demo' ] );

		$section = [ 'id' => 'archive_demo' ];
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'example_post_type_archive' ], [ $section ] );

		$this->assertEmpty( $html );
	}

	/**
	 * Test that the example of a date archive includes this year and month.
	 *
	 * Before testing for the current month, remove the current year to avoid a
	 * false positive in January.
	 */
	function test_example_date_archive() {
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'example_date_archive' ] );

		$this->assertStringContainsString( date( 'Y' ), $html );
		$this->assertStringContainsString( date( 'm' ), str_replace( date( 'Y' ), '', $html ) );
	}

	/**
	 * Test that the example of an author archive includes the URL for this user.
	 */
	function test_example_author_archive() {
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'example_author_archive' ] );

		$this->assertStringContainsString( get_author_posts_url( get_current_user_id() ), $html );
	}

	/**
	 * Test that the example of a search link includes a search query string.
	 */
	function test_example_search_page() {
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'example_search_page' ] );

		$this->assertStringContainsString( get_search_link( 'wordpress' ), $html );
	}

	/**
	 * Test that the example 404 page includes the hashed blog URL.
	 */
	function test_example_404_page() {
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'example_404_page' ] );

		$this->assertStringContainsString( md5( get_bloginfo( 'url' ) ), $html );
	}

	/**
	 * Test the various states of the field() helper method.
	 */
	function test_field() {
		// No field.
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'field' ], [ [] ] );

		$this->assertEmpty( $html );

		// No type? Use a text field.
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'field' ], [ [ 'field' => 'demo' ] ] );
		$this->assertMatchesRegularExpression( '/<input[^>]+type="text"[^>]+name="wp-seo\[demo\]"/', $html );

		// Check that a value is passed.
		WP_SEO_Settings()->options['demo'] = 'demo value';
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'field' ], [ [ 'field' => 'demo' ] ] );

		$this->assertMatchesRegularExpression( '/<input[^>]+type="text"[^>]+value="demo value"/', $html );

		// Check the rendered field types.
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'field' ], [
			[
				'field' => 'demo',
				'type'  => 'textarea',
			]
		] );

		$this->assertStringContainsString( '<textarea', $html );

		$html = Utils::get_echo( [ WP_SEO_Settings(), 'field' ], [
			[
				'field' => 'demo',
				'type'  => 'checkboxes',
				'boxes' => [ 'foo' => 'bar' ],
			]
		] );

		$this->assertMatchesRegularExpression( '/<input[^>]+type="checkbox"/', $html );

		$html = Utils::get_echo( [ WP_SEO_Settings(), 'field' ], [
			[
				'field'  => 'demo_repeatable', // Not "demo," which does have a value.
				'type'   => 'repeatable',
				'repeat' => [ 'foo' => 'bar' ],
			]
		] );

		$this->assertMatchesRegularExpression( '/<input[^>]+type="text"/', $html );
	}

	/**
	 * Test the default text field output and the output with all args.
	 */
	function test_render_text_field() {

		$html = Utils::get_echo( [ WP_SEO_Settings(), 'render_text_field' ], [
			[ 'field' => 'demo', 'type' => 'text' ],
			'demo value',
		] );

		// Look for the correct attributes: type of "text," field name and value.
		$this->assertStringContainsString( 'type="text"', $html );
		$this->assertStringContainsString( 'name="wp-seo[demo]"', $html );
		$this->assertStringContainsString( 'value="demo value"', $html );

		// Expect the field to have some size.
		$this->assertMatchesRegularExpression( '/size="\d+"/', $html );

		$html = Utils::get_echo( [ WP_SEO_Settings(), 'render_text_field' ], [
			[ 'type' => 'number', 'field' => 'demo', 'size' => 5 ],
			'40',
		] );

		$this->assertStringContainsString( 'type="number"', $html );
		$this->assertStringContainsString( 'value="40"', $html );
		$this->assertStringContainsString( 'size="5"', $html );
	}

	/**
	 * Test the default textarea output and the output with all args.
	 */
	function test_render_textarea() {
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'render_textarea' ], [
			[ 'field' => 'demo' ],
			'demo value'
		] );

		// Expect at least a textarea, with with the field name and the correct value.
		$this->assertMatchesRegularExpression( '/<textarea[^>]+name="wp-seo\[demo\]"[^>]+>demo value<\/textarea>/', $html );

		// Expect some row and column sizes.
		$this->assertMatchesRegularExpression( '/rows="\d+"/', $html );
		$this->assertMatchesRegularExpression( '/cols="\d+"/', $html );
	}

	function test_render_checkboxes() {
		$html = Utils::get_echo( [ WP_SEO_Settings(), 'render_checkboxes' ], [
			[
				'field' => 'demo',
				'boxes' => [
					'page'     => 'Page',
					'post_tag' => 'Tag',
				],
			],
			[ 'post_tag' ],
		] );

		// Expect two checkboxes.
		$this->assertSame( 2, substr_count( $html, 'type="checkbox"' ) );
		$this->assertStringContainsString( 'Page', $html );
		$this->assertStringContainsString( 'Tag', $html );
		// Expect only the "Tag" checkbox to be checked.
		$this->assertMatchesRegularExpression( '/<input[^>]+type="checkbox"[^>]+value="post_tag"[^>]+checked/', $html );
		$this->assertDoesNotMatchRegularExpression( '/<input[^>]+type="checkbox"[^>]+value="page"[^>]+checked/', $html );
	}

	/**
	 * Test the default repeatable field output and the output with args.
	 */
	function test_render_repeatable() {
		$args = [
			'field'  => 'demo',
			'repeat' => [
				'first_name' => 'First name',
				'last_name'  => 'Last Name',
			],
		];

		$html = Utils::get_echo( [ WP_SEO_Settings(), 'render_repeatable_field' ], [
			$args, []
		] );

		// Expect a "name" attribute in with the counter for the template.
		$this->assertStringContainsString( 'name="wp-seo[demo][<%= i %>][first_name]"', $html );

		// No values: We repeat two fields, so expect four inputs, two each for input and the template.
		$this->assertSame( 4, substr_count( $html, '<input class="repeatable" type="text"' ) );
		// No values: The "name" attribute of our field should be "0", not "1."
		$this->assertStringContainsString( 'name="wp-seo[demo][0][first_name]"', $html );
		$this->assertStringNotContainsString( 'name="wp-seo[demo][1][first_name]"', $html );
		// No values: Expect that the template starts at "1."
		$this->assertStringContainsString( 'data-start="1"', $html );

		$args['size'] = '40';

		$html = Utils::get_echo( [ WP_SEO_Settings(), 'render_repeatable_field' ], [
			$args,
			[
				[
					'first_name' => 'Millard',
					'last_name'  => 'Fillmore',
				],
				[
					'first_name' => 'William Howard',
					'last_name'  => 'Taft'
				],
				[
					'first_name' => 'James',
					'last_name'  => 'Buchanan',
				],
			]
		] );

		// Three values: Expect eight inputs (there isn't a blank one).
		$this->assertSame( 8, substr_count( $html, '<input class="repeatable"' ) );
		// $args changed: Each input should be size 40
		$this->assertSame( 8, substr_count( $html, 'size="40"' ) );
		// The "name" attribute should go up to "2."
		$this->assertStringContainsString( 'name="wp-seo[demo][2][first_name]"', $html );
		$this->assertStringNotContainsString( 'name="wp-seo[demo][3][first_name]"', $html );
		// Expect that the template starts at "3."
		$this->assertStringContainsString( 'data-start="3"', $html );
		// Look for our field values.
		$this->assertMatchesRegularExpression( '/<input[^>]+class="repeatable"[^>]+\[1\]\[first_name\][^>]+value="William Howard"[^>]+>/', $html );
		$this->assertMatchesRegularExpression( '/<input[^>]+class="repeatable"[^>]+\[2\]\[last_name\][^>]+value="Buchanan"[^>]+>/', $html );
	}

	protected function tearDown(): void {
		parent::tearDown();
		// Clean up after ourselves.
		_wp_seo_reset_post_types();
		_wp_seo_reset_taxonomies();
	}

}
