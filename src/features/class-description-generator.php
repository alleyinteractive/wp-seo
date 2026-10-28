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
 * requests to the route an hour. Calling `generate_description()` or
 * `generate_and_save_description()` directly, as bulk runs do, is not limited.
 *
 * The block editor offers a button to generate the description on post types
 * with the `wp-seo-description-generator` support, and the
 * `wp wp-seo generate-descriptions` command generates and saves them in bulk.
 */
final class Description_Generator implements \Alley\WP\Types\Feature {
	/**
	 * Handle of the feature, which names its enablement filter.
	 *
	 * @var string
	 */
	public const HANDLE = 'description_generator';

	/**
	 * Meta key the description is saved to, which the block editor's Search
	 * Engine panel edits.
	 *
	 * @var string
	 */
	public const META_KEY = 'search_engine_description';

	/**
	 * Query variable that limits a `WP_Query` to posts without a description,
	 * or with an empty one, while the feature is active.
	 *
	 * @var string
	 */
	public const MISSING_DESCRIPTION_QUERY_VAR = 'wp_seo_missing_description';

	/**
	 * Code of the error returned when the AI provider is limiting requests or
	 * is out of quota, so that every prompt sent soon after would fail too.
	 *
	 * @var string
	 */
	public const ERROR_PROVIDER_LIMITED = 'wp_seo_description_provider_limited';

	/**
	 * Code of the error `generate_and_save_description()` returns when it kept
	 * a description an editor saved while this one was being generated.
	 *
	 * @var string
	 */
	public const ERROR_DESCRIPTION_EXISTS = 'wp_seo_description_exists';

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		// Late, so that post types registered on `init` are included.
		add_action( 'init', [ $this, 'add_post_type_support' ], 99 );
		add_action( 'rest_api_init', [ $this, 'register_route' ] );
		add_filter( 'posts_where', [ $this, 'limit_query_to_missing_descriptions' ], 10, 2 );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'wp-seo generate-descriptions', Description_Generator_Command::class );
		}

		( new AI_Settings() )->boot();
	}

	/**
	 * Add support for the block editor's button to the post types it can
	 * be used on.
	 *
	 * The button is not offered until the feature can send prompts, since an
	 * editor could not fix the error every click would get.
	 */
	public function add_post_type_support(): void {
		if ( is_wp_error( AI_Providers::check_provider() ) ) {
			return;
		}

		foreach ( self::get_post_types() as $post_type ) {
			add_post_type_support( $post_type, 'wp-seo-description-generator' );
		}
	}

	/**
	 * Get the post types the description generator writes meta descriptions for.
	 *
	 * Only post types that store the description the Search Engine panel edits
	 * are included, including any the filter adds.
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
		 * Post types that do not store the description are removed afterward.
		 *
		 * @since 2.0.0
		 *
		 * @param string[] $post_types Post type names. Default every public post type
		 *                             shown in the admin, except attachments.
		 */
		$post_types = apply_filters( 'wp_seo_description_generator_post_types', array_values( $post_types ) );

		return array_values(
			array_filter(
				$post_types,
				fn ( string $post_type ): bool => registered_meta_key_exists( 'post', self::META_KEY, $post_type ),
			)
		);
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
		 * longer one is still returned for an editor to review, but
		 * `generate_and_save_description()` refuses it.
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
	 * @return string|\WP_Error The description, or an error. The error code is
	 *                          `ERROR_PROVIDER_LIMITED` when the provider is
	 *                          limiting requests or is out of quota. Other
	 *                          errors from sending the prompt are the ones
	 *                          core returns.
	 */
	public static function generate_description( \WP_Post $post ): string|\WP_Error {
		// Checked first, so that the content is not rendered for a prompt that cannot be sent.
		$provider = AI_Providers::check_provider();

		if ( is_wp_error( $provider ) ) {
			return $provider;
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

		$text = wp_ai_client_prompt( "Title: {$title}\n\nContent: {$content}" )
			->using_provider( $provider )
			->using_system_instruction( $instructions )
			->generate_text();

		if ( is_wp_error( $text ) ) {
			$data = $text->get_error_data();

			// Core reports a provider's rate limit or exhausted quota with a 429 status.
			if ( is_array( $data ) && 429 === ( $data['status'] ?? null ) ) {
				return new \WP_Error( self::ERROR_PROVIDER_LIMITED, $text->get_error_message(), $data );
			}

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
	 * Generate a meta description for a post and save it, without an editor
	 * reviewing it.
	 *
	 * Since no editor reviews it, a description longer than `get_max_length()`
	 * is refused, and markup is removed before it is saved. A description an
	 * editor saved while this one was being generated is kept.
	 *
	 *     $description = Description_Generator::generate_and_save_description( get_post( 123 ) );
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_Post $post Post to describe.
	 * @return string|\WP_Error The saved description, or an error. The error
	 *                          code is `ERROR_DESCRIPTION_EXISTS` when an
	 *                          editor's description was kept, and
	 *                          `ERROR_PROVIDER_LIMITED` when the provider is
	 *                          limiting requests or is out of quota.
	 */
	public static function generate_and_save_description( \WP_Post $post ): string|\WP_Error {
		$description = self::generate_description( $post );

		if ( is_wp_error( $description ) ) {
			return $description;
		}

		$max_length = self::get_max_length( $post );

		if ( mb_strlen( $description ) > $max_length ) {
			return new \WP_Error(
				'wp_seo_description_too_long',
				sprintf(
					/* translators: %d: maximum length, in characters. */
					__( 'The AI provider returned a description longer than %d characters.', 'wp-seo' ),
					$max_length
				)
			);
		}

		/*
		 * The post can have been read minutes ago, as a bulk run reads it with
		 * its batch, so its description is read again, past the cache, in case
		 * an editor has saved one since.
		 */
		wp_cache_delete( $post->ID, 'post_meta' );

		if ( '' !== get_post_meta( $post->ID, self::META_KEY, true ) ) {
			return new \WP_Error(
				self::ERROR_DESCRIPTION_EXISTS,
				__( 'Kept the description saved while this one was being generated.', 'wp-seo' )
			);
		}

		// Markup the provider escaped comes back decoded, so it is removed as plain text.
		$description = sanitize_text_field( $description );

		update_post_meta( $post->ID, self::META_KEY, $description );

		return $description;
	}

	/**
	 * Limit a query to posts without a description, or with an empty one, when
	 * it sets `MISSING_DESCRIPTION_QUERY_VAR`.
	 *
	 * A post has a description when its meta is a non-empty string, the same
	 * test `generate_and_save_description()` makes before saving one. Queries
	 * that do not set the variable are left alone.
	 *
	 * This is a correlated NOT EXISTS rather than a meta query, which would
	 * join the meta table twice to match both missing and empty descriptions.
	 * Either way, MySQL reads every meta row of each post it steps over, and
	 * a run steps over every post with a description between where it starts
	 * and the next post without one. On 500,000 posts with 10 meta rows each,
	 * all but the newest described, finding the first batch took 13 seconds
	 * this way, and 19 with the meta query.
	 *
	 * @param string    $where WHERE clause of the query.
	 * @param \WP_Query $query The query.
	 * @return string WHERE clause.
	 */
	public function limit_query_to_missing_descriptions( string $where, \WP_Query $query ): string {
		global $wpdb;

		if ( ! $query->get( self::MISSING_DESCRIPTION_QUERY_VAR ) || ! $wpdb instanceof \wpdb ) {
			return $where;
		}

		return $where . $wpdb->prepare(
			' AND NOT EXISTS ( SELECT 1 FROM %i AS wp_seo_description WHERE wp_seo_description.post_id = %i.ID AND wp_seo_description.meta_key = %s AND wp_seo_description.meta_value <> %s )',
			$wpdb->postmeta,
			$wpdb->posts,
			self::META_KEY,
			''
		);
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
	 * since the editor reviews it before saving, and asking again would count
	 * toward the limit and could be just as long.
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
		 * Calling `generate_description()` or `generate_and_save_description()`
		 * directly is not limited.
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
