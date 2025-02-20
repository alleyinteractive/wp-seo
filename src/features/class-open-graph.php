<?php
/**
 * Open_Graph class file
 *
 * @package WP_SEO
 */

namespace Alley\WP\WP_SEO\Features;

use Alley\WP\Types\Feature;

use function Alley\WP\WP_SEO\register_meta_helper;

/**
 * Open Graph Feature
 */
final class Open_Graph implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
		add_action( 'init', [ $this, 'add_meta_fields' ], 100 );
		add_action( 'wp_head', [ $this, 'render_open_graph_tags' ] );
	}

	/**
	 * Enqueue block editor assets.
	 */
	public function enqueue_block_editor_assets() {
		wp_enqueue_script( 'wp-seo-open-graph-js' );
	}

	/**
	 * Add meta fields.
	 */
	public function add_meta_fields(): void {
		register_meta_helper(
			'post',
			[ 'post', 'page' ],
			'wp_seo_open_graph_title',
			[
				'sanitize_callback' => 'wp_kses_post',
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => true,
			]
		);

		register_meta_helper(
			'post',
			[ 'post', 'page' ],
			'wp_seo_open_graph_description',
			[
				'sanitize_callback' => 'wp_kses_post',
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => true,
			]
		);

		register_meta_helper(
			'post',
			[ 'post', 'page' ],
			'wp_seo_open_graph_image',
			[
				'sanitize_callback' => 'wp_kses_post',
				'single'            => true,
				'type'              => 'integer',
				'show_in_rest'      => true,
			]
		);
	}

	/**
	 * Get the title with a fallback.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string The title.
	 */
	public static function get_title( $post_id ): string {
		return get_post_meta( $post_id, 'wp_seo_open_graph_title', true ) ?? get_the_title( $post_id );
	}

	/**
	 * Get the description with a fallback.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string The description.
	 */
	public static function get_description( $post_id ): string {
		return get_post_meta( $post_id, 'wp_seo_open_graph_description', true ) ?? get_the_excerpt( $post_id );
	}

	/**
	 * Get the image with a fallback.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string|false The image URL or false if no assigned images.
	 */
	public static function get_image( $post_id ): string|bool {
		return ! empty( get_post_meta( $post_id, 'wp_seo_open_graph_image', true ) )
			? wp_get_attachment_image_url( get_post_meta( $post_id, 'wp_seo_open_graph_image', true ), 'full' )
			: get_the_post_thumbnail_url( $post_id, 'full' );
	}

	/**
	 * Render Open Graph tags.
	 */
	public function render_open_graph_tags(): void {
		$post_id     = get_the_ID();
		$title       = $this->get_title( $post_id );
		$description = $this->get_description( $post_id );
		$image       = $this->get_image( $post_id );

		printf(
			<<<'HTML'
<!-- Start Open Graph -->
<meta property="og:type" content="website" />
<meta property="og:title" content="%1$s" />
<meta property="og:description" content="%2$s" />
<meta property="og:url" content="%3$s" />
%4$s
<!-- End Open Graph -->
HTML,
			esc_attr( $title ),
			esc_attr( $description ),
			esc_url( get_permalink() ),
			! empty( $image ) ? sprintf( '<meta property="og:image" content="%s" />', esc_url( $image ) ) : ''
		);
	}
}
