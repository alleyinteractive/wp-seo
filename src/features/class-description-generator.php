<?php
/**
 * Description_Generator class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Features;

use Alley\WP\WP_SEO\AI_Providers;
use Alley\WP\WP_SEO\AI_Settings;

/**
 * Meta Description Generator Feature
 *
 * Offers a REST route that writes a meta description for a post with the AI
 * provider chosen on the WP SEO AI settings page, for the editor to use. The
 * description is returned, not saved.
 *
 * Every request costs a prompt, so each user may make a limited number of
 * requests to the route an hour. Calling `generate_description()` directly, as
 * bulk runs do, is not limited.
 */
final class Description_Generator implements \Alley\WP\Types\Feature {
	/**
	 * Handle of the feature, which names its enablement filter.
	 *
	 * @var string
	 */
	public const HANDLE = 'description_generator';

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'rest_api_init', [ $this, 'register_route' ] );

		( new AI_Settings() )->boot();
	}

	/**
	 * Get the post types the description generator writes meta descriptions for.
	 *
	 * @since 2.0.0
	 *
	 * @return string[] Post type names.
	 */
	public static function get_post_types(): array {
		$post_types = array_diff(
			get_post_types(
				[
					'public'  => true,
					'show_ui' => true,
				]
			),
			[ 'attachment' ]
		);

		/**
		 * Filters the post types the description generator writes meta descriptions for.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $post_types Post type names. Default every public post type
		 *                             shown in the admin, except attachments.
		 */
		return apply_filters( 'wp_seo_description_generator_post_types', array_values( $post_types ) );
	}

	/**
	 * Get the maximum length of a generated meta description.
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Post $post Post being described.
	 * @return int Maximum length, in characters.
	 */
	public static function get_max_length( \WP_Post $post ): int {
		/**
		 * Filters the maximum length of a generated meta description.
		 *
		 * The default instructions ask for a description no longer than this. A
		 * longer one is still returned, so a caller that saves descriptions
		 * without review should check it.
		 *
		 * @since 2.0.0
		 *
		 * @param int      $max_length Maximum length, in characters. Default 160.
		 * @param \WP_Post $post       Post being described.
		 */
		return absint( apply_filters( 'wp_seo_description_generator_max_length', 160, $post ) );
	}

	/**
	 * Generate a meta description for a post.
	 *
	 * Sends the post's title and rendered content to the AI provider chosen on
	 * the WP SEO AI settings page, and returns the description it writes. The
	 * description is not saved, and may be longer than `get_max_length()`.
	 *
	 *     $description = Description_Generator::generate_description( get_post( 123 ) );
	 *
	 *     if ( ! is_wp_error( $description ) ) {
	 *         // Show or save the description.
	 *     }
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Post $post Post to describe.
	 * @return string|\WP_Error The description, or an error. Errors from sending
	 *                          the prompt are the ones core returns.
	 */
	public static function generate_description( \WP_Post $post ): string|\WP_Error {
		// Checked first, so that the content is not rendered for a prompt that cannot be sent.
		$check = AI_Providers::check_provider();

		if ( is_wp_error( $check ) ) {
			return $check;
		}

		/*
		 * A description is shown to anyone who views the post, so it must not
		 * summarize content that only people with the password can read.
		 */
		if ( '' !== $post->post_password ) {
			return new \WP_Error(
				'wp_seo_description_password_protected',
				__( 'A meta description cannot be generated for a password-protected post.', 'wp-seo' ),
				[ 'status' => 400 ]
			);
		}

		/*
		 * The provider reads and writes plain text, so markup is removed and
		 * entities such as the curly quotes the_content adds become characters.
		 */
		$to_text = fn ( string $html ): string => html_entity_decode( wp_strip_all_tags( $html, true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		/** This filter is documented in wp-includes/post-template.php */
		$content = apply_filters( 'the_content', $post->post_content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound

		/**
		 * Filters the content of the post sent to the AI provider.
		 *
		 * The content is rendered through `the_content`, which other plugins can
		 * add to every post, with share buttons or related posts for example.
		 * Removing such additions here keeps them out of every prompt. Tags are
		 * stripped and entities decoded after filtering.
		 *
		 * @since 2.0.0
		 *
		 * @param string   $content Rendered content, as HTML.
		 * @param \WP_Post $post    Post being described.
		 */
		$content = apply_filters( 'wp_seo_description_generator_content', is_string( $content ) ? $content : '', $post );

		/*
		 * The title is read as stored, since get_the_title() prepends "Private: "
		 * to a private post's title outside the admin, and the description would
		 * repeat it.
		 */
		$title   = $to_text( $post->post_title );
		$content = $to_text( $content );

		if ( '' === $title && '' === $content ) {
			return new \WP_Error(
				'wp_seo_description_no_content',
				__( 'The post has no title or content to describe.', 'wp-seo' ),
				[ 'status' => 400 ]
			);
		}

		$instructions = sprintf(
			"Write one meta description for the web page below. Summarize what the page is about in a neutral, factual tone. Don't use promotional or sales language, calls to action, questions, hashtags, or emoji. Use no more than %d characters, in the page's language. Your response will be used in code: reply with only the meta description, with no quotes, labels, or explanation.",
			self::get_max_length( $post )
		);

		/**
		 * Filters the instructions the description generator sends to the AI provider.
		 *
		 * The post's title and content are sent separately, as the prompt the
		 * instructions apply to.
		 *
		 * @since 2.0.0
		 *
		 * @param string   $instructions Instructions for writing the description.
		 * @param \WP_Post $post         Post being described.
		 */
		$instructions = apply_filters( 'wp_seo_description_generator_prompt', $instructions, $post );

		$prompt = AI_Providers::get_prompt_builder( "Title: {$title}\n\nContent: {$content}" );

		if ( is_wp_error( $prompt ) ) {
			return $prompt;
		}

		$text = $prompt->using_system_instruction( $instructions )->generate_text();

		if ( is_wp_error( $text ) ) {
			return $text;
		}

		/*
		 * Models sometimes wrap their answer in quotes despite being told not to.
		 * The quotes are removed only when the opening one appears nowhere else in
		 * the reply, so that a reply such as `"A" vs "B"` keeps its quotes.
		 */
		$description = trim( $to_text( $text ) );
		$description = (string) preg_replace( '/^(?|"([^"]*)"|\'([^\']*)\'|“([^“]*)”|‘([^‘]*)’)$/u', '$1', $description );

		if ( '' === $description ) {
			return new \WP_Error(
				'wp_seo_description_empty',
				__( 'The AI provider returned an empty description.', 'wp-seo' ),
				[ 'status' => 502 ]
			);
		}

		return $description;
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
			|| ! in_array( $post->post_type, self::get_post_types(), true )
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
	 * A description longer than the maximum length is returned all the same,
	 * since the editor reviews it before saving.
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

		$description = self::generate_description( $post );

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
		 * Calling `generate_description()` directly is not limited.
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
