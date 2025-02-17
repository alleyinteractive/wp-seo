<?php
/**
 * WP SEO Tests: Tests for the default formatting tags.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;
use Mantle\Testing\Mock_Action;

class DefaultFormattingTagsTest extends TestCase {
	/**
	 * Store category data.
	 */
	private $category = [];

	/**
	 * Store category ID.
	 */
	private $category_ID = 0;

	/**
	 * Store tag data.
	 */
	private $tag = [];

	/**
	 * Store tag ID.
	 */
	private $tag_ID = 0;

	/**
	 * Store term data.
	 */
	private $term = [];

	/**
	 * Store term ID.
	 */
	private $term_ID = 0;

	/**
	 * Store author data.
	 */
	private $author = [];

	/**
	 * Store author ID.
	 */
	private $author_ID = 0;

	/**
	 * Store post data.
	 */
	private $post = [];

	/**
	 * Store post ID.
	 */
	private $post_ID = 0;

	/**
	 * Store attachment ID.
	 */
	private $attachment_id = 0;

	/**
	 * Store post type data.
	 */
	private $post_type = [];

	/**
	 * Store demo data.
	 */
	private $demo = [];

	/**
	 * Store demo ID.
	 */
	private $demo_ID = 0;

	/**
	 * The formatting tag undergoing tests.
	 *
	 * @var WP_SEO_Formatting_Tag.
	 */
	var $current_tag;

	protected function setUp(): void {
		parent::setUp();
		$this->_create_objects();
		global $wp_rewrite;
		$wp_rewrite->init();
		$wp_rewrite->flush_rules();
	}

	protected function tearDown(): void {
		parent::tearDown();
		// Clean up after ourselves.
		_wp_seo_reset_post_types();
		_wp_seo_reset_taxonomies();
	}

	/**
	 * Create post types, posts, taxonomies, terms, and users for these tests.
	 *
	 * @see especially WP_SEO_Default_Formatting_Tags_Tests::_truthy_on_only(),
	 *     which uses most of these objects as part of the tests for each
	 *     formatting tag.
	 */
	function _create_objects() {
		$this->category = [ 'name' => 'Cat A', 'taxonomy' => 'category', 'description' => 'Cat A description' ];
		$this->category_ID = static::factory()->term->create( $this->category );

		$this->tag = [ 'name' => 'Tag A', 'taxonomy' => 'post_tag', 'description' => 'Tag A description' ];
		$this->tag_ID = static::factory()->term->create( $this->tag );

		register_taxonomy( 'demo_taxonomy', 'post' );

		$this->term = [ 'name' => 'Term A', 'taxonomy' => 'demo_taxonomy', 'description' => 'Term A description' ];
		$this->term_ID = static::factory()->term->create( $this->term );

		$this->author = [ 'display_name' => 'User A', 'user_login' => 'user-a' ];
		$this->author_ID = static::factory()->user->create( $this->author );

		$this->post = [
			'post_title'    => 'hello-world',
			'post_excerpt'  => rand_str(),
			'post_date'     => '2007-09-04 12:34',
			'post_author'   => $this->author_ID,
			'post_category' => [ $this->category_ID ],
			'tags_input'    => $this->tag['name'],
		];
		$this->post_ID = static::factory()->post->create( $this->post );
		// Trigger an update to "post_modified".
		wp_update_post( [ 'ID' => $this->post_ID, ] );

		$this->attachment_id = self::factory()->attachment->create_object( 'image.jpg', 0, [
			'post_mime_type' => 'image/jpeg',
		] );
		set_post_thumbnail( $this->post_ID, $this->attachment_id );

		$this->post_type = [ 'labels' => [ 'name' => 'Demo Post Types', 'singular_name' => 'Demo Post Type' ], 'rewrite' => true, 'has_archive' => true, 'public' => true, 'supports' => [ 'editor' ] ];
		register_post_type( 'demo_post_type', $this->post_type );

		$this->demo = [ 'post_type' => 'demo_post_type' ];
		$this->demo_ID = static::factory()->post->create( $this->demo );
	}

	/**
	 * Set the $current_tag property.
	 *
	 * @see wp_seo_default_formatting_tags() for the array keys used to register
	 *     individual formatting tags.
	 *
	 * @param string $key Formatting tag to set.
	 */
	function _set_current_tag( $key ) {
		$this->current_tag = WP_SEO()->formatting_tags[ $key ];
	}

	/**
	 * Test that the current tag has a description.
	 */
	function _has_description() {
		// $this->assertInternalType( 'string', $this->current_tag->get_description() );
		$this->assertNotSame( '', $this->current_tag->get_description() );
	}

	/**
	 * Test that the current tag returns a truthy value only on expected requests.
	 *
	 * These tests provide some security that formatting tags return false
	 * everywhere they should. More pages to test against can be added to
	 * $destinations as needed.
	 *
	 * The tests for individual tags separately check whether the tag returns
	 * the correct value on the pages where it should return anything at all.
	 *
	 * @param  array|string $expected An array of destinations on which the
	 *     current tag should be truthy, or 'all' to test all destinations.
	 */
	function _truthy_on_only( $expected ) {
		$destinations = [
			'home'              => '/',
			'404'               => '/' . rand_str( 5 ),
			'single'            => get_permalink( $this->post_ID ),
			'date'              => get_day_link( '2007', '09', '04' ),
			'author'            => get_author_posts_url( $this->author_ID ),
			'category'          => get_term_link( $this->category_ID, 'category' ),
			'tag'               => get_term_link( $this->tag_ID, 'post_tag' ),
			'tax'               => get_term_link( $this->term_ID, 'demo_taxonomy' ),
			'post_type_archive' => get_post_type_archive_link( 'demo_post_type' ),
			'search'            => get_search_link( 'wp-seo' ),
		];

		foreach ( $destinations as $destination => $url ) {
			$this->go_to( $url );
			if ( 'all' == $expected || in_array( $destination, $expected ) ) {
				$this->assertNotEmpty( $this->current_tag->get_value(), sprintf( 'Should have been truthy at %s', $url ) );
			} else {
				$this->assertFalse( $this->current_tag->get_value(), sprintf( 'Should not have been truthy at %s', $url ) );
			}
		}
	}

	/**
	 * Test that the current tag returns an expected value at a URL.
	 *
	 * @param  string $go_to The URL to go to and call get_value() on.
	 * @param  mixed $expected The expected value from the tag.
	 */
	function _go_to_and_expect( $go_to, $expected ) {
		$this->go_to( $go_to );
		$this->assertSame( $expected, $this->current_tag->get_value() );
	}

	/**
	 * Tests for each default formatting tag.
	 *
	 * Most tags should be subject to _truthy_on_only() and _go_to_and_expect(), at least.
	 */

	function test_site_name() {
		$this->_set_current_tag( 'site_name' );

		$this->_has_description();

		$this->_truthy_on_only( 'all' );

		$this->assertSame( 'Test Site', $this->current_tag->get_value() );
	}

	function test_title() {
		$this->_set_current_tag( 'title' );

		$this->_has_description();
		$this->_truthy_on_only( ['single'] );

		$this->_go_to_and_expect( get_permalink( $this->post_ID ), $this->post['post_title'] );
		$this->_truthy_on_only( [ 'single' ] );

		$this->_go_to_and_expect( get_permalink( $this->post_ID ), $this->post['post_title'] );
	}

	// WP_SEO_Format_Title should be false if the post type doesn't support titles.
	function test_title_no_post_type_support() {
		$this->_set_current_tag( 'title' );
		$this->_go_to_and_expect( get_permalink( $this->demo_ID ), false );
	}

	function test_excerpt() {
		$this->_set_current_tag( 'excerpt' );

		$this->_has_description();

		$this->_truthy_on_only( [ 'single' ] );

		$this->_go_to_and_expect( get_permalink( $this->post_ID ), $this->post['post_excerpt'] );
	}

	function test_generated_excerpt() {
		$this->_set_current_tag( 'excerpt' );

		add_filter( 'excerpt_length', function() {
			return '5';
		} );

		$post_ID = static::factory()->post->create( [
			'post_content' => 'Lorem ipsum dolor sit amet consectetur.',
			'post_excerpt' => '',
		] );

		$this->_go_to_and_expect( get_permalink( $post_ID ), 'Lorem ipsum dolor sit amet [&hellip;]' );
	}

	// WP_SEO_Format_Excerpt should be false if the post type doesn't support excerpts.
	function test_excerpt_no_post_type_support() {
		$this->_set_current_tag( 'excerpt' );
		$this->_go_to_and_expect( get_permalink( $this->demo_ID ), false );
	}

	function test_date_published() {
		$this->_set_current_tag( 'date_published' );

		$this->_has_description();

		$this->_truthy_on_only( [ 'single' ] );

		$this->_go_to_and_expect( get_permalink( $this->post_ID ), 'September 4, 2007' );
	}

	function test_date_modified() {
		$this->_set_current_tag( 'date_modified' );

		$this->_has_description();

		$this->_truthy_on_only( [ 'single' ] );

		// The modified date should be today, when we called wp_update_post().
		$this->_go_to_and_expect( get_permalink( $this->post_ID ), date( 'F j, Y' ) );
	}

	function test_author() {
		$this->_set_current_tag( 'author' );

		$this->_has_description();

		$this->_truthy_on_only( [ 'single', 'author' ] );

		$this->_go_to_and_expect( get_permalink( $this->post_ID ), $this->author['display_name'] );
		$this->_go_to_and_expect( get_author_posts_url( $this->author_ID ), $this->author['display_name'] );
	}

	/**
	 * Tests the author formatting tag in a way that uses get_the_author().
	 *
	 * _go_to_and_expect() skips setup_postdata(), so get_the_author() isn't available.
	 */
	function test_author_with_get_the_author() {
		$this->_set_current_tag( 'author' );

		$author_ID = static::factory()->user->create( [
			'display_name' => 'test_author',
		] );

		$post_ID = static::factory()->post->create( [
			'post_author' => $author_ID,
		] );

		$this->go_to( get_permalink( $post_ID ) );
		setup_postdata( get_post( $post_ID ) );

		$this->assertEquals( 'test_author', $this->current_tag->get_value() );
	}

	function test_categories() {
		$this->_set_current_tag( 'categories' );

		$this->_has_description();

		$this->_truthy_on_only( [ 'single' ] );

		$this->_go_to_and_expect( get_permalink( $this->post_ID ), $this->category['name'] );
	}

	// WP_SEO_Format_Categories should return false if the post type doesn't support categories.
	function test_categories_no_post_type_support() {
		$this->_set_current_tag( 'categories' );
		$this->_go_to_and_expect( get_permalink( $this->demo_ID ), false );
	}

	function test_tags() {
		$this->_set_current_tag( 'tags' );

		$this->_has_description();

		$this->_truthy_on_only( [ 'single' ] );

		$this->_go_to_and_expect( get_permalink( $this->post_ID ), $this->tag['name'] );
	}

	// WP_SEO_Format_Tags should return false if the post type doesn't support tags.
	function test_tags_no_post_type_support() {
		$this->_set_current_tag( 'tags' );
		$this->_go_to_and_expect( get_permalink( $this->demo_ID ), false );
	}

	function test_term_name() {
		$this->_set_current_tag( 'term_name' );

		$this->_has_description();

		$this->_truthy_on_only( [ 'category', 'tag', 'tax' ] );

		$this->_go_to_and_expect( get_term_link( $this->category_ID, 'category' ), $this->category['name'] );
		$this->_go_to_and_expect( get_term_link( $this->tag_ID, 'post_tag' ), $this->tag['name'] );
		$this->_go_to_and_expect( get_term_link( $this->term_ID, 'demo_taxonomy' ), $this->term['name'] );
	}

	function test_term_description() {
		$this->_set_current_tag( 'term_description' );

		$this->_has_description();

		$this->_truthy_on_only( [ 'category', 'tag', 'tax' ] );

		$this->_go_to_and_expect( get_term_link( $this->category_ID, 'category' ), $this->category['description'] );
		$this->_go_to_and_expect( get_term_link( $this->tag_ID, 'post_tag' ), $this->tag['description'] );
		$this->_go_to_and_expect( get_term_link( $this->term_ID, 'demo_taxonomy' ), $this->term['description'] );
	}

	function test_post_type_singular_name() {
		$this->_set_current_tag( 'post_type_singular_name' );

		$this->_has_description();

		$this->_truthy_on_only( [ 'single', 'post_type_archive' ] );

		$this->_go_to_and_expect( get_permalink( $this->demo_ID ), $this->post_type['labels']['singular_name'] );
		$this->_go_to_and_expect( get_post_type_archive_link( 'demo_post_type' ), $this->post_type['labels']['singular_name'] );
	}

	function test_post_type_plural_name() {
		$this->_set_current_tag( 'post_type_plural_name' );

		$this->_has_description();

		$this->_truthy_on_only( [ 'single', 'post_type_archive' ] );

		$this->_go_to_and_expect( get_permalink( $this->demo_ID ), 'Demo Post Types' );
		$this->_go_to_and_expect( get_post_type_archive_link( 'demo_post_type' ), 'Demo Post Types' );
	}

	function test_archive_date() {
		$this->_set_current_tag( 'archive_date' );

		$this->_has_description();

		$this->_truthy_on_only( [ 'date' ] );

		$this->_go_to_and_expect( get_day_link( '2007', '09', '04' ), 'September 4, 2007' );
		$this->_go_to_and_expect( get_month_link( '2007', '09' ), 'September 2007' );
		$this->_go_to_and_expect( get_year_link( '2007' ), '2007' );
	}

	function test_search() {
		$this->_set_current_tag( 'search_term' );

		$this->_has_description();

		$this->_truthy_on_only( [ 'search' ] );

		$this->_go_to_and_expect( get_search_link( 'wp-seo' ), 'wp-seo' );
	}

	function test_thumbnail_url() {
		$this->_set_current_tag( 'thumbnail_url' );

		$this->_has_description();

		$this->_truthy_on_only( [ 'single' ] );

		$this->_go_to_and_expect( get_permalink( $this->post_ID ), wp_get_attachment_image_url( $this->attachment_id, 'full' ) );
	}
}
