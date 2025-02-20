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
}
