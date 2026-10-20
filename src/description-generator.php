<?php
/**
 * Contains functions for generating meta descriptions with an AI provider.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

/**
 * Get the post types the description generator writes meta descriptions for.
 *
 * @since 2.0.0
 *
 * @return string[] Post type names.
 */
function get_description_generator_post_types(): array {
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
 * Generate a meta description for a post.
 *
 * Sends the post's title and rendered content to the AI provider chosen for
 * the description generator, and returns the description it writes. The
 * description is not saved.
 *
 *     $description = generate_description( get_post( 123 ) );
 *
 *     if ( ! is_wp_error( $description ) ) {
 *         // Show or save the description.
 *     }
 *
 * @since 2.0.0
 *
 * @param \WP_Post $post Post to describe.
 * @return string|\WP_Error The description, or an error. Errors from sending
 *                          the prompt are the ones core returns. A description
 *                          that is too long comes back as an error whose data
 *                          holds the description.
 */
function generate_description( \WP_Post $post ): string|\WP_Error {
	$provider = get_ai_provider( 'description_generator' );

	if ( '' === $provider ) {
		return new \WP_Error(
			'wp_seo_ai_no_provider',
			__( 'No AI provider is selected for the Meta Description Generator.', 'wp-seo' ),
			[ 'status' => 503 ]
		);
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

	/**
	 * Filters the maximum length of a generated meta description.
	 *
	 * The default instructions ask for a description no longer than this, and a
	 * longer one is returned as an error.
	 *
	 * @since 2.0.0
	 *
	 * @param int      $max_length Maximum length, in characters. Default 160.
	 * @param \WP_Post $post       Post being described.
	 */
	$max_length = absint( apply_filters( 'wp_seo_description_generator_max_length', 160, $post ) );

	$instructions = sprintf(
		"Write one meta description for the web page below. Summarize what the page is about in a neutral, factual tone. Don't use promotional or sales language, calls to action, questions, hashtags, or emoji. Use no more than %d characters, in the page's language. Your response will be used in code: reply with only the meta description, with no quotes, labels, or explanation.",
		$max_length
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

	$prompt = get_ai_prompt( "Title: {$title}\n\nContent: {$content}", $provider );

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

	if ( mb_strlen( $description ) > $max_length ) {
		return new \WP_Error(
			'wp_seo_description_too_long',
			sprintf(
				/* translators: %d: maximum length, in characters. */
				__( 'The AI provider returned a description longer than %d characters.', 'wp-seo' ),
				$max_length
			),
			[
				'status'      => 502,
				'description' => $description,
			]
		);
	}

	return $description;
}
