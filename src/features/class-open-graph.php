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
		add_action( 'wp_head', [ $this, 'render_open_graph_tags' ] );

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
		$enabled_post_types = $this->wp_seo_settings->get_option( 'open_graph_post_types' );

		if ( is_array( $enabled_post_types ) ) {
			foreach ( $enabled_post_types as $post_type ) {
				add_post_type_support( $post_type, 'open-graph' );
			}
		}
	}

	/**
	 * Add meta fields.
	 */
	public function add_meta_fields(): void {
		register_meta_helper(
			'post',
			get_post_types_by_support( 'open-graph' ),
			'alley_seo_open_graph_title',
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
			'alley_seo_open_graph_description',
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
			'alley_seo_open_graph_image',
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
		$open_graph_title = get_post_meta( $post_id, 'alley_seo_open_graph_title', true );

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
		$open_graph_description = get_post_meta( $post_id, 'alley_seo_open_graph_description', true );

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
		$open_graph_image_id  = get_post_meta( $post_id, 'alley_seo_open_graph_image', true );
		$open_graph_image_url = ( ! empty( $open_graph_image_id ) && is_string( $open_graph_image_id ) )
		? wp_get_attachment_image_url( (int) $open_graph_image_id, 'full' )
		: false;

		if ( empty( $open_graph_image_url ) ) {
			$open_graph_image_url = new \WP_Error( 'no_open_graph_image', 'No Open Graph image found' );
		}

		if ( is_wp_error( $open_graph_image_url ) ) {
			return get_the_post_thumbnail_url( $post_id, 'full' );
		}

		return $open_graph_image_url;
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
		$additional  = '';

		// Add article related tags.
		if ( is_singular() ) {
			$published_time = get_the_date( 'c', $post_id );
			$modified_time  = get_the_modified_date( 'c', $post_id );

			if ( ! empty( $published_time ) ) {
				$additional .= sprintf( "\n<meta property=\"article:published_time\" content=\"%s\" />", esc_attr( $published_time ) );
			}

			if ( ! empty( $modified_time ) ) {
				$additional .= sprintf( "\n<meta property=\"article:modified_time\" content=\"%s\" />", esc_attr( $modified_time ) );
			}
		}

		// Add image related tags.
		if ( ! empty( $image ) ) {
			$additional .= sprintf( "\n<meta property=\"og:image\" content=\"%s\" />", esc_url( $image ) );
		}

		printf(
			<<<'HTML'
<!-- Start WP SEO Open Graph -->
<meta property="og:type" content="%1$s" />
<meta property="og:title" content="%2$s" />
<meta property="og:description" content="%3$s" />
<meta property="og:url" content="%4$s" />%5$s
<!-- End WP SEO Open Graph -->
HTML,
			is_singular() ? 'article' : 'website',
			esc_attr( $title ),
			esc_attr( $description ),
			esc_url( $permalink ),
			$additional // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $additional is pre-built HTML with all dynamic values already escaped via esc_attr/esc_url.
		);
	}
}
