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
	 * Test image w/ no post thumbnail, Open Graph image, or site default configured.
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
	 * Test image falls back to the site-wide default when no post thumbnail
	 * or Open Graph image is set.
	 */
	public function test_get_image_fallback_default_image() {
		$attachment_id = $this->factory->attachment->with_image()->create();

		update_option( \WP_SEO_Settings::SLUG, [ 'default_open_graph_image' => $attachment_id ] );
		\WP_SEO_Settings::instance()->set_options();

		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_open_graph_image' => '',
			]
		)
		->create();

		$default_image_url = wp_get_attachment_image_url( $attachment_id, 'full' );

		$this->assertEquals( $default_image_url, Open_Graph::get_image( $post_id ) );
	}

	/**
	 * Test the post thumbnail still takes priority over the site-wide default.
	 */
	public function test_get_image_default_image_does_not_override_thumbnail() {
		$attachment_id = $this->factory->attachment->with_image()->create();

		update_option( \WP_SEO_Settings::SLUG, [ 'default_open_graph_image' => $attachment_id ] );
		\WP_SEO_Settings::instance()->set_options();

		$post_id = $this->factory->post
		->with_real_thumbnail()
		->create();

		$thumb_url = get_the_post_thumbnail_url( $post_id, 'full' );

		$this->assertNotFalse( $thumb_url );
		$this->assertEquals( $thumb_url, Open_Graph::get_image( $post_id ) );
	}

	/**
	 * Test the site-wide default falls back to false if the configured
	 * attachment no longer exists.
	 */
	public function test_get_image_fallback_default_image_deleted() {
		$attachment_id = $this->factory->attachment->with_image()->create();

		update_option( \WP_SEO_Settings::SLUG, [ 'default_open_graph_image' => $attachment_id ] );
		\WP_SEO_Settings::instance()->set_options();

		wp_delete_attachment( $attachment_id, true );

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
	 * Test the `wp_seo_default_open_graph_image` filter overrides the
	 * resolved site-wide default image.
	 */
	public function test_get_image_default_image_filter() {
		add_filter(
			'wp_seo_default_open_graph_image',
			function () {
				return 'https://example.com/filtered-image.jpg';
			}
		);

		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_open_graph_image' => '',
			]
		)
		->create();

		$this->assertEquals( 'https://example.com/filtered-image.jpg', Open_Graph::get_image( $post_id ) );
	}
}
