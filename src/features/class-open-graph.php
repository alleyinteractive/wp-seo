<?php
/**
 * Open_Graph class file
 *
 * @package wp-seo
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
		add_action( 'init', [ $this, 'add_post_type_support' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
		add_action( 'init', [ $this, 'add_meta_fields' ] );
		add_action( 'wp_head', [ $this, 'render_open_graph_tags' ] );
	}

	/**
	 * Add post type support.
	 *
	 * @todo: Supported post types should be configurable from admin page.
	 *
	 * @return void
	 */
	public function add_post_type_support() {
		add_post_type_support( 'post', 'open-graph' );
		add_post_type_support( 'page', 'open-graph' );
	}


	/**
	 * Enqueue block editor assets.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		global $post;

		if ( ! is_admin()
			|| ! ( $post instanceof \WP_Post || is_int( $post ) || is_null( $post ) )
		) {
			return;
		}

		$post_type = get_post_type( $post );

		if ( empty( $post_type ) || ! post_type_supports( $post_type, 'open-graph' ) ) {
			return;
		}

		wp_enqueue_script( 'wp-seo-open-graph-js' );
	}

	/**
	 * Add meta fields.
	 */
	public function add_meta_fields(): void {
		register_meta_helper(
			'post',
			get_post_types_by_support( 'open-graph' ),
			'wp_seo_open_graph_title',
			[
				'sanitize_callback' => 'sanitize_text_field',
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => true,
			]
		);

		register_meta_helper(
			'post',
			get_post_types_by_support( 'open-graph' ),
			'wp_seo_open_graph_description',
			[
				'sanitize_callback' => 'sanitize_text_field',
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => true,
			]
		);

		register_meta_helper(
			'post',
			get_post_types_by_support( 'open-graph' ),
			'wp_seo_open_graph_image',
			[
				'sanitize_callback' => 'absint',
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
		$open_graph_title = get_post_meta( $post_id, 'wp_seo_open_graph_title', true );

		if ( ! empty( $open_graph_title ) && is_string( $open_graph_title ) ) {
			return $open_graph_title;
		}

		return get_the_title( $post_id );
	}

	/**
	 * Get the description with a fallback.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string The description.
	 */
	public static function get_description( $post_id ): string {
		$open_graph_description = get_post_meta( $post_id, 'wp_seo_open_graph_description', true );

		if ( ! empty( $open_graph_description ) && is_string( $open_graph_description ) ) {
			return $open_graph_description;
		}

		return get_the_excerpt( $post_id );
	}

	/**
	 * Get the image with a fallback.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string|false The image URL or false if no assigned images.
	 */
	public static function get_image( $post_id ): string|bool {
		$open_graph_image_id  = get_post_meta( $post_id, 'wp_seo_open_graph_image', true );
		$open_graph_image_url = wp_get_attachment_image_url( (int) $open_graph_image_id, 'full' );

		if ( ! empty( $open_graph_image_url ) && ! is_wp_error( $open_graph_image_url ) ) {
			return $open_graph_image_url;
		}

		return get_the_post_thumbnail_url( $post_id, 'full' );
	}

	/**
	 * Render Open Graph tags.
	 */
	public function render_open_graph_tags(): void {
		$post_id = get_the_ID();

		if ( ! is_int( $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );

		if ( empty( $post_type ) || ! post_type_supports( $post_type, 'open-graph' ) ) {
			return;
		}

		$title       = $this->get_title( $post_id );
		$description = $this->get_description( $post_id );
		$image       = $this->get_image( $post_id );
		$permalink   = ! empty( get_permalink( $post_id ) ) ? get_permalink( $post_id ) : '';

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
			esc_url( $permalink ),
			! empty( $image ) ? sprintf( '<meta property="og:image" content="%s" />', esc_url( $image ) ) : ''
		);
	}
}
