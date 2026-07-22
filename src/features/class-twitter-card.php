<?php
/**
 * Twitter_Card class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Features;

use Alley\WP\Types\Feature;

use function Alley\WP\WP_SEO\register_meta_helper;

/**
 * Twitter Card Feature
 */
final class Twitter_Card implements Feature {

	/**
	 * WP SEO settings.
	 *
	 * @var object WP_SEO_Settings::instance
	 */
	protected $wp_seo_settings;

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'init', [ $this, 'add_post_type_support' ] );
		add_action( 'init', [ $this, 'add_meta_fields' ] );
		add_action( 'wp_head', [ $this, 'render_twitter_card_tags' ] );

		if ( ! isset( $this->wp_seo_settings ) ) {
			$this->wp_seo_settings = \WP_SEO_Settings::instance();
		}
	}

	/**
	 * Add post type support.
	 *
	 * @return void
	 */
	public function add_post_type_support() {
		$enabled_post_types = $this->wp_seo_settings->get_option( 'twitter_card_post_types' );

		if ( is_array( $enabled_post_types ) ) {
			foreach ( $enabled_post_types as $post_type ) {
				add_post_type_support( $post_type, 'wp-seo-twitter-card' );
			}
		}
	}

	/**
	 * Add meta fields.
	 */
	public function add_meta_fields(): void {
		register_meta_helper(
			'post',
			get_post_types_by_support( 'wp-seo-twitter-card' ),
			'wp_seo_twitter_card_title',
			[
				'sanitize_callback' => 'sanitize_text_field',
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => true,
			]
		);

		register_meta_helper(
			'post',
			get_post_types_by_support( 'wp-seo-twitter-card' ),
			'wp_seo_twitter_card_description',
			[
				'sanitize_callback' => 'sanitize_text_field',
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => true,
			]
		);

		register_meta_helper(
			'post',
			get_post_types_by_support( 'wp-seo-twitter-card' ),
			'wp_seo_twitter_card_image',
			[
				'sanitize_callback' => 'absint',
				'single'            => true,
				'type'              => 'integer',
				'show_in_rest'      => true,
			]
		);
	}

	/**
	 * Get the title with a fallback to the Open Graph title.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string The title.
	 */
	public static function get_title( $post_id ): string {
		$twitter_card_title = get_post_meta( $post_id, 'wp_seo_twitter_card_title', true );

		if ( ! empty( $twitter_card_title ) && is_string( $twitter_card_title ) ) {
			return $twitter_card_title;
		}

		return Open_Graph::get_title( $post_id );
	}

	/**
	 * Get the description with a fallback to the Open Graph description.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string The description.
	 */
	public static function get_description( $post_id ): string {
		$twitter_card_description = get_post_meta( $post_id, 'wp_seo_twitter_card_description', true );

		if ( ! empty( $twitter_card_description ) && is_string( $twitter_card_description ) ) {
			return $twitter_card_description;
		}

		return Open_Graph::get_description( $post_id );
	}

	/**
	 * Get the image with a fallback to the Open Graph image.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string|false The image URL or false if no assigned images.
	 */
	public static function get_image( $post_id ): string|bool {
		$twitter_card_image_url = wp_seo_get_image_url_from_meta( $post_id, 'wp_seo_twitter_card_image' );

		if ( ! empty( $twitter_card_image_url ) ) {
			return $twitter_card_image_url;
		}

		return Open_Graph::get_image( $post_id );
	}

	/**
	 * Render Twitter Card tags.
	 */
	public function render_twitter_card_tags(): void {
		$post_id = get_the_ID();

		if ( ! is_int( $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );

		if ( empty( $post_type ) || ! post_type_supports( $post_type, 'wp-seo-twitter-card' ) ) {
			return;
		}

		$title       = $this->get_title( $post_id );
		$description = $this->get_description( $post_id );
		$image       = $this->get_image( $post_id );
		$card_type   = ! empty( $image ) ? 'summary_large_image' : 'summary';
		$image_tag   = ! empty( $image ) ? sprintf( "\n<meta name=\"twitter:image\" content=\"%s\" />", esc_url( $image ) ) : '';

		printf(
			<<<'HTML'
<!-- Start WP SEO Twitter Card -->
<meta name="twitter:card" content="%1$s" />
<meta name="twitter:title" content="%2$s" />
<meta name="twitter:description" content="%3$s" />%4$s
<!-- End WP SEO Twitter Card -->
HTML,
			esc_attr( $card_type ),
			esc_attr( $title ),
			esc_attr( $description ),
			$image_tag // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped above with esc_url().
		);
	}
}
