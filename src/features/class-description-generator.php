<?php
/**
 * Description_Generator class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Features;

use Alley\WP\Types\Feature;

use function Alley\WP\WP_SEO\generate_description;
use function Alley\WP\WP_SEO\get_description_generator_post_types;

/**
 * Meta Description Generator Feature
 *
 * Offers a REST route that writes a meta description for a post with the AI
 * provider chosen on the WP SEO AI settings page, for the editor to use. The
 * description is returned, not saved.
 */
final class Description_Generator implements Feature {
	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'rest_api_init', [ $this, 'register_route' ] );
	}

	/**
	 * Register the route that generates a description.
	 *
	 * The route takes POST rather than GET, since every request costs a prompt
	 * to the AI provider and must not be cached or prefetched.
	 */
	public function register_route(): void {
		register_rest_route(
			'wp-seo/v1',
			'/generate-description',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'generate' ],
				'permission_callback' => [ $this, 'can_generate' ],
				'args'                => [
					'post_id' => [
						'description' => __( 'ID of the post to describe.', 'wp-seo' ),
						'type'        => 'integer',
						'minimum'     => 1,
						'required'    => true,
					],
				],
			]
		);
	}

	/**
	 * Check that the current user can generate a description for the post.
	 *
	 * The post must be one the description generator works on, and one the
	 * user can edit.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array{post_id: int}> $request
	 * @return true|\WP_Error True if allowed, or an error.
	 */
	public function can_generate( \WP_REST_Request $request ): true|\WP_Error {
		$post = $this->get_requested_post( $request );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		if (
			! current_user_can( 'edit_post', $post->ID )
			|| ! in_array( $post->post_type, get_description_generator_post_types(), true )
		) {
			return new \WP_Error(
				'rest_forbidden',
				__( 'Sorry, you are not allowed to generate a description for this post.', 'wp-seo' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Generate a description for the post.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array{post_id: int}> $request
	 * @return \WP_REST_Response|\WP_Error The description, or an error.
	 */
	public function generate( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$post = $this->get_requested_post( $request );

		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$description = generate_description( $post );

		if ( is_wp_error( $description ) ) {
			return $description;
		}

		return new \WP_REST_Response( [ 'description' => $description ] );
	}

	/**
	 * Get the post a request is for.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @phpstan-param \WP_REST_Request<array{post_id: int}> $request
	 * @return \WP_Post|\WP_Error The post, or an error if it does not exist.
	 */
	private function get_requested_post( \WP_REST_Request $request ): \WP_Post|\WP_Error {
		$post = get_post( $request['post_id'] );

		if ( ! $post instanceof \WP_Post ) {
			return new \WP_Error( 'rest_post_invalid_id', __( 'Invalid post ID.', 'wp-seo' ), [ 'status' => 404 ] );
		}

		return $post;
	}
}
