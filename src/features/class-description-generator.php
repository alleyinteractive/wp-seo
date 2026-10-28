<?php
/**
 * Description_Generator class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Features;

use Alley\WP\Types\Feature;
use Alley\WP\WP_SEO\AI_Providers;

/**
 * Meta Description Generator Feature
 *
 * Offers a REST route that writes a meta description for a post with the AI
 * provider chosen on the WP SEO AI settings page, for the editor to use. The
 * description is returned, not saved.
 */
final class Description_Generator implements Feature {
	/**
	 * Handle of the feature, which names its enablement filter and keys its AI
	 * provider.
	 *
	 * @var string
	 */
	public const HANDLE = 'description_generator';

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'rest_api_init', [ $this, 'register_route' ] );
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
	 * Get the AI provider the description generator sends its prompts to.
	 *
	 * @since 2.0.0
	 *
	 * @return string|\WP_Error Connector ID of the provider, or an error if none
	 *                          is chosen.
	 */
	public static function get_provider(): string|\WP_Error {
		$provider = AI_Providers::get_feature_provider( self::HANDLE );

		if ( '' === $provider ) {
			return new \WP_Error(
				'wp_seo_ai_no_provider',
				__( 'No AI provider is selected for the Meta Description Generator.', 'wp-seo' ),
				[ 'status' => 503 ]
			);
		}

		return $provider;
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
	 * Sends the post's title and rendered content to the AI provider chosen for
	 * the description generator, and returns the description it writes. The
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
		$provider = self::get_provider();

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

		$prompt = AI_Providers::get_prompt_builder( "Title: {$title}\n\nContent: {$content}", $provider );

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

		$description = self::generate_description( $post );

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
