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
	 * Test image w/ fallback all the way to the site-wide default Open Graph image.
	 */
	public function test_get_image_fallback_to_default_image() {
		$attachment_id = $this->factory->attachment->with_image()->create();

		update_option( \WP_SEO_Settings::SLUG, [
			'default_open_graph_image' => $attachment_id,
		] );
		WP_SEO_Settings()->set_options();

		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_open_graph_image' => '',
			]
		)
		->create();

		$image_url = wp_get_attachment_image_url( $attachment_id, 'full' );

		$this->assertEquals( $image_url, Open_Graph::get_image( $post_id ) );
	}

	/**
	 * Test that the site-wide default Open Graph image is not used when a
	 * post thumbnail is already available.
	 */
	public function test_get_image_prefers_thumbnail_over_default_image() {
		$attachment_id = $this->factory->attachment->with_image()->create();

		update_option( \WP_SEO_Settings::SLUG, [
			'default_open_graph_image' => $attachment_id,
		] );
		WP_SEO_Settings()->set_options();

		$post_id = $this->factory->post
		->with_real_thumbnail()
		->with_meta(
			[
				'wp_seo_open_graph_image' => '',
			]
		)
		->create();

		$thumb_id  = get_post_meta( $post_id, '_thumbnail_id', true );
		$thumb_url = wp_get_attachment_image_url( $thumb_id, 'full' );

		$this->assertEquals( $thumb_url, Open_Graph::get_image( $post_id ) );
	}

	/**
	 * Test that Open_Graph::get_default_image() returns false when no
	 * default Open Graph image is configured.
	 */
	public function test_get_default_image_returns_false_when_unset() {
		$this->assertFalse( Open_Graph::get_default_image() );
	}
}
