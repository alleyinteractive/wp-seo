<?php
/**
 * Description_Generator class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Features;

use Alley\WP\Types\Feature;
use Alley\WP\WP_SEO\Description_Generator_Command;

use function Alley\WP\WP_SEO\generate_description;
use function Alley\WP\WP_SEO\get_ai_provider;
use function Alley\WP\WP_SEO\get_description_generator_post_types;

/**
 * Meta Description Generator Feature
 *
 * Offers a REST route that writes a meta description for a post with the AI
 * provider chosen on the WP SEO AI settings page, for the editor to use. The
 * description is returned, not saved.
 *
 * Every request costs a prompt, so each user may make a limited number of
 * requests to the route an hour. Calling generate_description() directly, as
 * bulk runs do, is not limited.
 *
 * The block editor offers a button to generate the description on post types
 * with the `wp-seo-description-generator` support, and the
 * `wp wp-seo generate-descriptions` command generates and saves them in bulk.
 */
final class Description_Generator implements Feature {
	/**
	 * Meta key the description is saved to, which the block editor's Search
	 * Engine panel edits.
	 *
	 * @var string
	 */
	public const META_KEY = 'search_engine_description';

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		// Late, so that post types registered on `init` are included.
		add_action( 'init', [ $this, 'add_post_type_support' ], 99 );
		add_action( 'rest_api_init', [ $this, 'register_route' ] );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'wp-seo generate-descriptions', Description_Generator_Command::class );
		}
	}

	/**
	 * Add support for the block editor's button to the post types it can
	 * be used on.
	 *
	 * The button fills the description the Search Engine panel edits, so it is
	 * offered only on post types that store that description. It is not
	 * offered until an AI provider is chosen, since an editor could not fix
	 * the error every click would get.
	 */
	public function add_post_type_support(): void {
		if ( '' === get_ai_provider( 'description_generator' ) ) {
			return;
		}

		foreach ( get_description_generator_post_types() as $post_type ) {
			if ( registered_meta_key_exists( 'post', self::META_KEY, $post_type ) ) {
				add_post_type_support( $post_type, 'wp-seo-description-generator' );
			}
		}
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
	 * A description longer than the maximum length is returned rather than
	 * refused, since the editor reviews it before saving, and asking again
	 * would count toward the limit and could be just as long.
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

		$retry_after = $this->count_request( get_current_user_id() );

		if ( $retry_after > 0 ) {
			// A WP_Error can't carry headers, so the error is sent as a response.
			$response = rest_convert_error_to_response(
				new \WP_Error(
					'wp_seo_description_rate_limited',
					__( 'Too many descriptions have been generated. Please try again later.', 'wp-seo' ),
					[
						'status'      => 429,
						'retry_after' => $retry_after,
					]
				)
			);
			$response->header( 'Retry-After', (string) $retry_after );

			return $response;
		}

		$description = generate_description( $post );

		if ( is_wp_error( $description ) && 'wp_seo_description_too_long' === $description->get_error_code() ) {
			$data        = $description->get_error_data();
			$description = is_array( $data ) && is_string( $data['description'] ?? null ) ? $data['description'] : $description;
		}

		if ( is_wp_error( $description ) ) {
			return $description;
		}

		return new \WP_REST_Response( [ 'description' => $description ] );
	}

	/**
	 * Count a request toward the user's hourly limit.
	 *
	 * Requests are counted in a transient for each user, in a fixed window
	 * that starts with the user's first request and lasts an hour. A request
	 * is counted before its prompt is sent, so one that fails still counts.
	 * Two requests made at the same time at the limit may both be allowed.
	 *
	 * @param int $user_id User ID.
	 * @return int Seconds until the user may try again, or 0 if the request is
	 *             allowed.
	 */
	private function count_request( int $user_id ): int {
		/**
		 * Filters the number of descriptions a user may generate an hour
		 * through the REST route.
		 *
		 * Calling generate_description() directly is not limited.
		 *
		 * @since 2.0.0
		 *
		 * @param int $limit   Requests allowed an hour. 0 or less turns the
		 *                     limit off. Default 30.
		 * @param int $user_id ID of the user making the request.
		 */
		$limit = (int) apply_filters( 'wp_seo_description_generator_rate_limit', 30, $user_id );

		if ( $limit <= 0 ) {
			return 0;
		}

		$key    = 'wp_seo_description_rate_limit_' . $user_id;
		$now    = time();
		$stored = get_transient( $key );
		$count  = is_array( $stored ) && is_int( $stored['count'] ?? null ) ? $stored['count'] : 0;
		$reset  = is_array( $stored ) && is_int( $stored['reset'] ?? null ) ? $stored['reset'] : 0;

		if ( $reset <= $now ) {
			$count = 0;
			$reset = $now + HOUR_IN_SECONDS;
		}

		if ( $count >= $limit ) {
			return $reset - $now;
		}

		set_transient(
			$key,
			[
				'count' => $count + 1,
				'reset' => $reset,
			],
			$reset - $now
		);

		return 0;
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
