<?php
/**
 * TwitterCardTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Features;

use Alley\WP\WP_SEO\Tests\TestCase;
use Alley\WP\WP_SEO\Features\Twitter_Card;

/**
 * TwitterCard Test for the Twitter_Card class.
 *
 * These test the actual rendered <head> markup for a post (via the real
 * wp_head action, per the base TestCase's get_rendered_head()) rather than
 * calling Twitter_Card's methods directly, so they verify the feature is
 * genuinely wired into WordPress's own rendering, not just callable in
 * isolation.
 *
 * @link https://mantle.alley.com/docs/testing
 */
class TwitterCardTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
		add_post_type_support( 'post', 'wp-seo-twitter-card' );
	}

	protected function tearDown(): void {
		remove_post_type_support( 'post', 'wp-seo-twitter-card' );
		parent::tearDown();
	}

	/**
	 * Test title.
	 */
	public function test_get_title() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_title' => 'Twitter Card Title',
			]
		)
		->create();
		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringContainsString( 'name="twitter:title" content="Twitter Card Title"', $this->get_rendered_head() );
	}

	/**
	 * Test title w/ fallback to Open Graph title.
	 */
	public function test_get_title_fallback_to_open_graph() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_title' => '',
				'alley_seo_open_graph_title'   => 'Open Graph Title',
			]
		)
		->create();
		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringContainsString( 'name="twitter:title" content="Open Graph Title"', $this->get_rendered_head() );
	}

	/**
	 * Test title w/ fallback all the way to the post title.
	 */
	public function test_get_title_fallback_to_post_title() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_title' => '',
				'alley_seo_open_graph_title'   => '',
			]
		)
		->create(
			[
				'post_title' => 'Post Title',
			]
		);
		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringContainsString( 'name="twitter:title" content="Post Title"', $this->get_rendered_head() );
	}

	/**
	 * Test description.
	 */
	public function test_get_description() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_description' => 'Twitter Card Description',
			]
		)
		->create();
		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringContainsString( 'name="twitter:description" content="Twitter Card Description"', $this->get_rendered_head() );
	}

	/**
	 * Test description w/ fallback to Open Graph description.
	 */
	public function test_get_description_fallback_to_open_graph() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_description' => '',
				'alley_seo_open_graph_description'   => 'Open Graph Description',
			]
		)
		->create();
		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringContainsString( 'name="twitter:description" content="Open Graph Description"', $this->get_rendered_head() );
	}

	/**
	 * Test description w/ fallback all the way to the post excerpt.
	 */
	public function test_get_description_fallback_to_post_excerpt() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_description' => '',
				'alley_seo_open_graph_description'   => '',
			]
		)
		->create(
			[
				'post_excerpt' => 'Post Content',
			]
		);
		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringContainsString( 'name="twitter:description" content="Post Content"', $this->get_rendered_head() );
	}

	/**
	 * Test image.
	 */
	public function test_get_image() {
		$post_id = $this->factory->post
		->with_real_thumbnail()
		->create();

		$thumb_id  = get_post_meta( $post_id, '_thumbnail_id', true );
		$thumb_url = wp_get_attachment_image_url( $thumb_id, 'full' );

		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringContainsString( "name=\"twitter:image\" content=\"{$thumb_url}\"", $this->get_rendered_head() );
	}

	/**
	 * Test image w/ fallback to Open Graph image.
	 */
	public function test_get_image_fallback_to_open_graph() {
		$attachment_id = $this->factory->attachment->with_image()->create();

		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_image' => '',
				'alley_seo_open_graph_image'   => $attachment_id,
			]
		)
		->create();

		$image_url = wp_get_attachment_image_url( $attachment_id, 'full' );

		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringContainsString( "name=\"twitter:image\" content=\"{$image_url}\"", $this->get_rendered_head() );
	}

	/**
	 * Test image w/ fallback all the way to the featured image.
	 */
	public function test_get_image_fallback_to_featured_image() {
		$post = $this->factory->post
		->with_real_thumbnail()
		->with_meta(
			[
				'wp_seo_twitter_card_image' => '',
				'alley_seo_open_graph_image'   => '',
			]
		)
		->create_and_get();

		$post_thumbnail_url = get_the_post_thumbnail_url( $post->ID );

		$this->go_to( get_permalink( $post->ID ) );

		$this->assertStringContainsString( "name=\"twitter:image\" content=\"{$post_thumbnail_url}\"", $this->get_rendered_head() );
	}

	/**
	 * Test image w/ no Twitter Card image, Open Graph image, or post thumbnail.
	 */
	public function test_get_image_fallback_no_thumbnail() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_image' => '',
				'alley_seo_open_graph_image'   => '',
			]
		)
		->create();
		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringNotContainsString( 'name="twitter:image"', $this->get_rendered_head() );
	}

	/**
	 * Test that no tags render when the post type doesn't support 'wp-seo-twitter-card'.
	 */
	public function test_render_twitter_card_tags_not_supported() {
		remove_post_type_support( 'post', 'wp-seo-twitter-card' );

		$post_id = $this->factory->post->create();
		$this->go_to( get_permalink( $post_id ) );

		$this->assertStringNotContainsString( 'name="twitter:card"', $this->get_rendered_head() );
	}

	/**
	 * Test that tags render with a 'summary_large_image' card type when an image resolves.
	 */
	public function test_render_twitter_card_tags_with_image() {
		$post_id = $this->factory->post
		->with_real_thumbnail()
		->with_meta(
			[
				'wp_seo_twitter_card_title'       => 'Twitter Title',
				'wp_seo_twitter_card_description' => 'Twitter Description',
			]
		)
		->create();

		$this->go_to( get_permalink( $post_id ) );
		$output = $this->get_rendered_head();

		$this->assertStringContainsString( 'name="twitter:card" content="summary_large_image"', $output );
		$this->assertStringContainsString( 'name="twitter:title" content="Twitter Title"', $output );
		$this->assertStringContainsString( 'name="twitter:description" content="Twitter Description"', $output );
		$this->assertStringContainsString( 'name="twitter:image"', $output );
	}

	/**
	 * Test that tags render with a 'summary' card type when no image resolves.
	 */
	public function test_render_twitter_card_tags_without_image() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_title'       => 'Twitter Title',
				'wp_seo_twitter_card_description' => 'Twitter Description',
			]
		)
		->create();

		$this->go_to( get_permalink( $post_id ) );
		$output = $this->get_rendered_head();

		$this->assertStringContainsString( 'name="twitter:card" content="summary"', $output );
		$this->assertStringNotContainsString( 'name="twitter:image"', $output );
	}

	/**
	 * Test that enabling Twitter Card support for a post type via settings
	 * results in that post type actually being recognized as supporting it.
	 *
	 * Added alongside an equivalent test in OpenGraphTest to guard against
	 * the same class of regression found there: a merge accidentally
	 * reverted Open_Graph::add_post_type_support() to grant the old,
	 * unprefixed 'open-graph' support flag while everywhere else
	 * (get_post_types_by_support(), post_type_supports(), and the block
	 * editor sidebar's postType.supports check) still checked the prefixed
	 * 'wp-seo-open-graph' one - post type support flags share one global
	 * namespace across every plugin, with no built-in prefixing
	 * convention, which is exactly why these were prefixed in the first
	 * place. Twitter_Card doesn't have that specific history, but this
	 * confirms the same settings -> add_post_type_support() ->
	 * post_type_supports() wiring stays in sync here too.
	 */
	public function test_post_type_support_is_granted_under_the_expected_slug() {
		// setUp() already grants this support directly for the rest of the
		// tests in this class; remove it first so this test genuinely
		// exercises the settings-driven wiring instead of passing because of
		// that unrelated setup.
		remove_post_type_support( 'post', 'wp-seo-twitter-card' );

		update_option( \WP_SEO_Settings::SLUG, [
			'twitter_card_post_types' => [ 'post' ],
		] );
		WP_SEO_Settings()->set_options();

		( new Twitter_Card() )->add_post_type_support();

		$this->assertTrue( post_type_supports( 'post', 'wp-seo-twitter-card' ) );
	}
}
