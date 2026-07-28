<?php
/**
 * OpenGraphTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Features;

use Alley\WP\WP_SEO\Tests\TestCase;
use Alley\WP\WP_SEO\Features\Open_Graph;

/**
 * OpenGraph Test for the Open_Graph class.
 *
 * @link https://mantle.alley.com/docs/testing
 */
class OpenGraphTest extends TestCase {
	/**
	 * Test title.
	 */
	public function test_get_title() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_open_graph_title' => 'Open Graph Title',
			]
		)
		->create();
		$this->assertEquals( 'Open Graph Title', Open_Graph::get_title( $post_id ) );
	}

	/**
	 * Test title w/ fallback.
	 */
	public function test_get_title_fallback() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_open_graph_title' => '',
			]
		)
		->create(
			[
				'post_title' => 'Post Title',
			]
		);
		$this->assertEquals( 'Post Title', Open_Graph::get_title( $post_id ) );
	}

	/**
	 * Test description.
	 */
	public function test_get_description() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_open_graph_description' => 'Open Graph Description',
			]
		)
		->create();
		$this->assertEquals( 'Open Graph Description', Open_Graph::get_description( $post_id ) );
	}

	/**
	 * Test description w/ fallback.
	 */
	public function test_get_description_fallback() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_open_graph_description' => '',
			]
		)
		->create(
			[
				'post_excerpt' => 'Post Content',
			]
		);
		$this->assertEquals( 'Post Content', Open_Graph::get_description( $post_id ) );
	}

	/**
	 * Test image.
	 */
	public function test_get_image() {
		$post_id = $this->factory->post
		->with_thumbnail()
		->create();

		$thumb_id = get_post_meta( $post_id, '_thumbnail_id', true );
		$thumb_url = wp_get_attachment_image_url( $thumb_id, 'full' );

		$this->assertEquals($thumb_url, Open_Graph::get_image( $post_id ) );
	}

	/**
	 * Test image w/ fallback.
	 */
	public function test_get_image_fallback() {
		$post = $this->factory->post
		->with_thumbnail()
		->with_meta(
			[
				'wp_seo_open_graph_image' => '',
			]
		)
		->create_and_get();

		$post_thumbnail_url = get_the_post_thumbnail_url( $post->ID );

		$this->assertEquals( $post_thumbnail_url, Open_Graph::get_image( $post->ID ) );
	}

	/**
	 * Test image w/ no post thumbnail or Open Graph image.
	 */
	public function test_get_image_fallback_no_thumbnail() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_open_graph_image' => '',
			]
		)
		->create();
		$this->assertFalse( Open_Graph::get_image( $post_id ) );
	}

	/**
	 * Test that enabling Open Graph support for a post type via settings
	 * results in that post type actually being recognized as supporting it.
	 *
	 * This guards against a regression: the post type support slug was
	 * renamed from 'open-graph' to 'wp-seo-open-graph' (to avoid colliding
	 * with another plugin's post type support flag of the same generic
	 * name - post_type_supports() flags share one global namespace across
	 * every plugin, with no built-in prefixing convention). A later merge
	 * accidentally reverted just the add_post_type_support() call back to
	 * the old, unprefixed name, while get_post_types_by_support(),
	 * post_type_supports(), and the block editor sidebar's
	 * postType.supports check all still used the new one. That silently
	 * broke Open Graph for every post type - meta fields never registered,
	 * front-end tags never rendered, the sidebar panel never appeared -
	 * and nothing in this file caught it, since the rest of these tests
	 * only exercise the static getter methods directly rather than the
	 * post-type-support wiring itself.
	 */
	public function test_post_type_support_is_granted_under_the_expected_slug() {
		update_option( \WP_SEO_Settings::SLUG, [
			'open_graph_post_types' => [ 'post' ],
		] );
		WP_SEO_Settings()->set_options();

		( new Open_Graph() )->add_post_type_support();

		$this->assertTrue( post_type_supports( 'post', 'wp-seo-open-graph' ) );

		remove_post_type_support( 'post', 'wp-seo-open-graph' );
	}
}
