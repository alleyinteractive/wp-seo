<?php
/**
 * OpenGraphRenderTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Feature;
use Alley\WP\WP_SEO\Features\Open_Graph;
use Alley\WP\WP_SEO\Registry;
use Alley\WP\WP_SEO\Tests\TestCase;

/**
 * Tests for Open_Graph::render_open_graph_tags() across every page context
 * it supports: singular posts, the homepage (both as a static page and as
 * the default posts index), taxonomy archives, author/post-type/date
 * archives, search results, and 404s.
 *
 * These test the actual rendered <head> markup via the real wp_head action
 * (through the base TestCase::get_rendered_head() helper), since these
 * branches only behave correctly against a real, parsed WP query set up by
 * go_to().
 *
 * @link https://mantle.alley.com/docs/testing
 */
class OpenGraphRenderTest extends TestCase {

	/**
	 * A custom taxonomy registered for these tests.
	 *
	 * @var string
	 */
	private string $taxonomy = 'og_demo_taxonomy';

	/**
	 * A custom post type registered for these tests.
	 *
	 * @var string
	 */
	private string $post_type = 'og_demo_post_type';

	protected function setUp(): void {
		parent::setUp();

		/*
		 * Open Graph is opt-in, so the plugin composed it without booting it.
		 * These tests are about what the feature renders on a site that has
		 * turned it on, so they turn it on the way a site would -- through the
		 * filter named for its handle -- and boot it from here. The registry is
		 * emptied first because the plugin already claimed the handle when it
		 * composed itself.
		 */
		Registry::reset_for_tests();
		add_filter( 'wp_seo_enable_open_graph', '__return_true' );
		Feature::top_level( 'open_graph', 'Open Graph', new Open_Graph() )->boot();

		add_post_type_support( 'post', 'wp-seo-open-graph' );

		register_taxonomy( $this->taxonomy, 'post' );
		register_post_type(
			$this->post_type,
			[
				'rewrite'     => true,
				'has_archive' => true,
				'public'      => true,
				'label'       => 'Widgets',
			]
		);

		global $wp_rewrite;
		$wp_rewrite->init();
		$wp_rewrite->flush_rules();
	}

	protected function tearDown(): void {
		remove_post_type_support( 'post', 'wp-seo-open-graph' );
		remove_post_type_support( 'page', 'wp-seo-open-graph' );

		parent::tearDown();

		// Leave the place as we found it.
		_wp_seo_reset_post_types();
		_wp_seo_reset_taxonomies();
	}

	/**
	 * Regression guard: singular post rendering must be unchanged after the
	 * dispatcher refactor.
	 */
	public function test_singular_post_baseline() {
		$post_id = $this->factory->post
			->with_meta(
				[
					'wp_seo_open_graph_title'       => 'OG Title',
					'wp_seo_open_graph_description' => 'OG Description',
				]
			)
			->create();

		$this->go_to( get_permalink( $post_id ) );
		$output = $this->get_rendered_head();

		$this->assertStringContainsString( 'property="og:type" content="article"', $output );
		$this->assertStringContainsString( 'property="og:title" content="OG Title"', $output );
		$this->assertStringContainsString( 'property="og:description" content="OG Description"', $output );
		$this->assertStringContainsString( 'property="article:published_time"', $output );
	}

	/**
	 * Test that the default posts-index homepage gets basic site-level tags.
	 */
	public function test_homepage_posts_index() {
		$this->go_to( home_url( '/' ) );
		$output = $this->get_rendered_head();

		$this->assertStringContainsString( 'property="og:type" content="website"', $output );
		$this->assertStringContainsString( 'property="og:title" content="' . esc_attr( get_bloginfo( 'name' ) ) . '"', $output );
		$this->assertStringContainsString( 'property="og:url" content="' . esc_url( home_url( '/' ) ) . '"', $output );
	}

	/**
	 * Test that a static front page whose post type has Open Graph support
	 * enabled uses the richer per-post rendering.
	 */
	public function test_homepage_static_page_with_open_graph_support() {
		add_post_type_support( 'page', 'wp-seo-open-graph' );

		$page_id = $this->factory->post->create(
			[
				'post_type'  => 'page',
				'post_title' => 'Homepage Page Title',
			]
		);

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );

		$this->go_to( home_url( '/' ) );
		$output = $this->get_rendered_head();

		$this->assertStringContainsString( 'property="og:type" content="article"', $output );
		$this->assertStringContainsString( 'property="og:title" content="Homepage Page Title"', $output );
	}

	/**
	 * Test that a static front page whose post type does NOT have Open
	 * Graph support still gets basic site-level tags rather than nothing.
	 */
	public function test_homepage_static_page_without_open_graph_support() {
		$page_id = $this->factory->post->create(
			[
				'post_type'  => 'page',
				'post_title' => 'Homepage Page Title',
			]
		);

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );

		$this->go_to( home_url( '/' ) );
		$output = $this->get_rendered_head();

		$this->assertStringContainsString( 'property="og:type" content="website"', $output );
		$this->assertStringContainsString( 'property="og:title" content="' . esc_attr( get_bloginfo( 'name' ) ) . '"', $output );
		$this->assertStringNotContainsString( 'Homepage Page Title', $output );
	}

	/**
	 * Test a category archive.
	 */
	public function test_category_archive() {
		$term = $this->factory->term->create_and_get(
			[
				'taxonomy' => 'category',
				'name'     => 'News',
			]
		);

		$this->go_to( get_term_link( $term ) );
		$output = $this->get_rendered_head();

		$this->assertStringContainsString( 'property="og:type" content="website"', $output );
		$this->assertStringContainsString( 'property="og:title" content="News"', $output );
		$this->assertStringContainsString( 'property="og:url" content="' . esc_url( get_term_link( $term ) ) . '"', $output );
	}

	/**
	 * Test a tag archive.
	 */
	public function test_tag_archive() {
		$term = $this->factory->term->create_and_get(
			[
				'taxonomy' => 'post_tag',
				'name'     => 'Announcements',
			]
		);

		$this->go_to( get_term_link( $term ) );
		$output = $this->get_rendered_head();

		$this->assertStringContainsString( 'property="og:title" content="Announcements"', $output );
	}

	/**
	 * Test a custom taxonomy archive.
	 */
	public function test_custom_taxonomy_archive() {
		$term = $this->factory->term->create_and_get(
			[
				'taxonomy' => $this->taxonomy,
				'name'     => 'Gadgets',
			]
		);

		$this->go_to( get_term_link( $term ) );
		$output = $this->get_rendered_head();

		$this->assertStringContainsString( 'property="og:title" content="Gadgets"', $output );
	}

	/**
	 * Test an author archive.
	 */
	public function test_author_archive() {
		$author_id = $this->factory->user->create( [ 'display_name' => 'Jane Doe' ] );
		$this->factory->post->create( [ 'post_author' => $author_id ] );

		$this->go_to( get_author_posts_url( $author_id ) );
		$output = $this->get_rendered_head();

		$this->assertStringContainsString( 'property="og:type" content="website"', $output );
		$this->assertStringContainsString( 'property="og:title" content="Jane Doe"', $output );
	}

	/**
	 * Test a post type archive.
	 */
	public function test_post_type_archive() {
		$this->factory->post->create( [ 'post_type' => $this->post_type ] );

		$this->go_to( get_post_type_archive_link( $this->post_type ) );
		$output = $this->get_rendered_head();

		$this->assertStringContainsString( 'property="og:type" content="website"', $output );
		$this->assertStringContainsString( 'property="og:title" content="Widgets"', $output );
		$this->assertStringContainsString( 'property="og:url" content="' . esc_url( get_post_type_archive_link( $this->post_type ) ) . '"', $output );
	}

	/**
	 * Test a day archive, confirming the title/URL are derived from query
	 * vars rather than the (unset, at wp_head time) global $post.
	 */
	public function test_date_archive_day() {
		$this->factory->post->create( [ 'post_date' => '2007-09-04 12:34:00' ] );

		$this->go_to( get_day_link( 2007, 9, 4 ) );
		$output = $this->get_rendered_head();

		$expected_title = wp_date( get_option( 'date_format' ), mktime( 0, 0, 0, 9, 4, 2007 ) );

		$this->assertStringContainsString( 'property="og:type" content="website"', $output );
		$this->assertStringContainsString( 'property="og:title" content="' . esc_attr( $expected_title ) . '"', $output );
	}

	/**
	 * Test search results.
	 */
	public function test_search_results() {
		$this->go_to( get_search_link( 'wp-seo' ) );
		$output = $this->get_rendered_head();

		$this->assertStringContainsString( 'property="og:type" content="website"', $output );
		$this->assertStringContainsString( 'wp-seo', $output );
	}

	/**
	 * Test that a 404 renders no Open Graph tags at all.
	 */
	public function test_404_emits_nothing() {
		// An invalid month (13) reliably triggers a 404.
		$this->go_to( get_day_link( '2014', '13', '13' ) );
		$output = $this->get_rendered_head();

		$this->assertStringNotContainsString( 'Start WP SEO Open Graph', $output );
	}

	/**
	 * Test that the site-wide default Open Graph image appears on a
	 * non-singular page when configured.
	 */
	public function test_default_image_appears_on_archive() {
		$attachment_id = $this->factory->attachment->with_image()->create();

		update_option(
			\WP_SEO_Settings::SLUG,
			[ 'default_open_graph_image' => $attachment_id ]
		);
		WP_SEO_Settings()->set_options();

		$term = $this->factory->term->create_and_get(
			[
				'taxonomy' => 'category',
				'name'     => 'Gadgets',
			]
		);

		$this->go_to( get_term_link( $term ) );

		$image_url = wp_get_attachment_image_url( $attachment_id, 'full' );

		$this->assertStringContainsString( "property=\"og:image\" content=\"{$image_url}\"", $this->get_rendered_head() );
	}

	/**
	 * Test that no og:image tag renders on a non-singular page when no
	 * default Open Graph image is configured.
	 */
	public function test_no_image_when_default_unset() {
		$term = $this->factory->term->create_and_get(
			[
				'taxonomy' => 'category',
				'name'     => 'Gadgets',
			]
		);

		$this->go_to( get_term_link( $term ) );

		$this->assertStringNotContainsString( 'og:image', $this->get_rendered_head() );
	}
}
