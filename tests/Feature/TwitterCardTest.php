<?php
/**
 * TwitterCardTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Features;

use Alley\WP\WP_SEO\Tests\TestCase;
use Alley\WP\WP_SEO\Features\Twitter_Card;
use Mantle\Testing\Utils;

/**
 * TwitterCard Test for the Twitter_Card class.
 *
 * @link https://mantle.alley.com/docs/testing
 */
class TwitterCardTest extends TestCase {
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
		$this->assertEquals( 'Twitter Card Title', Twitter_Card::get_title( $post_id ) );
	}

	/**
	 * Test title w/ fallback to Open Graph title.
	 */
	public function test_get_title_fallback_to_open_graph() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_title' => '',
				'wp_seo_open_graph_title'   => 'Open Graph Title',
			]
		)
		->create();
		$this->assertEquals( 'Open Graph Title', Twitter_Card::get_title( $post_id ) );
	}

	/**
	 * Test title w/ fallback all the way to the post title.
	 */
	public function test_get_title_fallback_to_post_title() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_title' => '',
				'wp_seo_open_graph_title'   => '',
			]
		)
		->create(
			[
				'post_title' => 'Post Title',
			]
		);
		$this->assertEquals( 'Post Title', Twitter_Card::get_title( $post_id ) );
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
		$this->assertEquals( 'Twitter Card Description', Twitter_Card::get_description( $post_id ) );
	}

	/**
	 * Test description w/ fallback to Open Graph description.
	 */
	public function test_get_description_fallback_to_open_graph() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_description' => '',
				'wp_seo_open_graph_description'   => 'Open Graph Description',
			]
		)
		->create();
		$this->assertEquals( 'Open Graph Description', Twitter_Card::get_description( $post_id ) );
	}

	/**
	 * Test description w/ fallback all the way to the post excerpt.
	 */
	public function test_get_description_fallback_to_post_excerpt() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_description' => '',
				'wp_seo_open_graph_description'   => '',
			]
		)
		->create(
			[
				'post_excerpt' => 'Post Content',
			]
		);
		$this->assertEquals( 'Post Content', Twitter_Card::get_description( $post_id ) );
	}

	/**
	 * Test image.
	 */
	public function test_get_image() {
		$post_id = $this->factory->post
		->with_thumbnail()
		->create();

		$thumb_id  = get_post_meta( $post_id, '_thumbnail_id', true );
		$thumb_url = wp_get_attachment_image_url( $thumb_id, 'full' );

		$this->assertEquals( $thumb_url, Twitter_Card::get_image( $post_id ) );
	}

	/**
	 * Test image w/ fallback to Open Graph image.
	 */
	public function test_get_image_fallback_to_open_graph() {
		$attachment_id = $this->factory->attachment->create();

		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_image' => '',
				'wp_seo_open_graph_image'   => $attachment_id,
			]
		)
		->create();

		$image_url = wp_get_attachment_image_url( $attachment_id, 'full' );

		$this->assertEquals( $image_url, Twitter_Card::get_image( $post_id ) );
	}

	/**
	 * Test image w/ fallback all the way to the featured image.
	 */
	public function test_get_image_fallback_to_featured_image() {
		$post = $this->factory->post
		->with_thumbnail()
		->with_meta(
			[
				'wp_seo_twitter_card_image' => '',
				'wp_seo_open_graph_image'   => '',
			]
		)
		->create_and_get();

		$post_thumbnail_url = get_the_post_thumbnail_url( $post->ID );

		$this->assertEquals( $post_thumbnail_url, Twitter_Card::get_image( $post->ID ) );
	}

	/**
	 * Test image w/ no Twitter Card image, Open Graph image, or post thumbnail.
	 */
	public function test_get_image_fallback_no_thumbnail() {
		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_image' => '',
				'wp_seo_open_graph_image'   => '',
			]
		)
		->create();
		$this->assertFalse( Twitter_Card::get_image( $post_id ) );
	}

	/**
	 * Test that no tags render when the post type doesn't support 'wp-seo-twitter-card'.
	 */
	public function test_render_twitter_card_tags_not_supported() {
		remove_post_type_support( 'post', 'wp-seo-twitter-card' );

		$post_id = $this->factory->post->create();
		$this->go_to( get_permalink( $post_id ) );

		$twitter_card = new Twitter_Card();

		$this->assertEmpty( Utils::get_echo( [ $twitter_card, 'render_twitter_card_tags' ] ) );
	}

	/**
	 * Test that tags render with a 'summary_large_image' card type when an image resolves.
	 */
	public function test_render_twitter_card_tags_with_image() {
		add_post_type_support( 'post', 'wp-seo-twitter-card' );

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

		$twitter_card = new Twitter_Card();
		$output       = Utils::get_echo( [ $twitter_card, 'render_twitter_card_tags' ] );

		$this->assertStringContainsString( 'name="twitter:card" content="summary_large_image"', $output );
		$this->assertStringContainsString( 'name="twitter:title" content="Twitter Title"', $output );
		$this->assertStringContainsString( 'name="twitter:description" content="Twitter Description"', $output );
		$this->assertStringContainsString( 'name="twitter:image"', $output );

		remove_post_type_support( 'post', 'wp-seo-twitter-card' );
	}

	/**
	 * Test that tags render with a 'summary' card type when no image resolves.
	 */
	public function test_render_twitter_card_tags_without_image() {
		add_post_type_support( 'post', 'wp-seo-twitter-card' );

		$post_id = $this->factory->post
		->with_meta(
			[
				'wp_seo_twitter_card_title'       => 'Twitter Title',
				'wp_seo_twitter_card_description' => 'Twitter Description',
			]
		)
		->create();

		$this->go_to( get_permalink( $post_id ) );

		$twitter_card = new Twitter_Card();
		$output       = Utils::get_echo( [ $twitter_card, 'render_twitter_card_tags' ] );

		$this->assertStringContainsString( 'name="twitter:card" content="summary"', $output );
		$this->assertStringNotContainsString( 'name="twitter:image"', $output );

		remove_post_type_support( 'post', 'wp-seo-twitter-card' );
	}
}
