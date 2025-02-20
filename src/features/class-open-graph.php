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
		add_action( 'init', [ $this, 'add_meta_fields' ], 100 );
		add_action( 'wp_head', [ $this, 'render_open_graph_tags' ] );
	}

	/**
	 * Add meta fields.
	 */
	public function add_meta_fields(): void {
		register_meta_helper(
			'post',
			['post'], // @todo Suppprted post types only.
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
			['post'],
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
			['post'],
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
	 * Render Open Graph tags.
	 */
	public function render_open_graph_tags(): void {
		$title	     = get_post_meta( get_the_ID(), 'wp_seo_open_graph_title', true ) ?? get_the_title();
		$description = get_post_meta( get_the_ID(), 'wp_seo_open_graph_description', true ) ?? get_the_excerpt();
		$image	     = ! empty( get_post_meta( get_the_ID(), 'wp_seo_open_graph_image', true ) )
			? wp_get_attachment_image_url( get_post_meta( get_the_ID(), 'wp_seo_open_graph_image', true ), 'full' )
			: get_the_post_thumbnail_url( get_the_ID(), 'full' );

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
			! empty ( $image ) ? sprintf( '<meta property="og:image" content="%s" />', esc_url( $image ) ) : ''
		);
	}
}
