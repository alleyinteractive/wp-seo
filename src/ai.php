<?php
/**
 * Contains functions for working with the AI connectors configured in WordPress.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

/**
 * Get the AI providers WP SEO can send prompts to.
 *
 * Lists the connectors a site administrator can configure under Settings >
 * Connectors that are AI providers and whose provider plugin is active. Whether
 * the provider has working credentials is not checked, since that takes a
 * request to the provider; a missing or bad key surfaces as an error when a
 * prompt is sent.
 *
 * @since 2.0.0
 *
 * @return array<string, string> Provider names keyed by connector ID. Empty
 *                               before WordPress 7.0 or when AI is disabled.
 */
function get_ai_providers(): array {
	if ( ! function_exists( 'wp_get_connectors' ) ) {
		return [];
	}

	$connectors = wp_get_connectors();

	if ( empty( $connectors ) ) {
		return [];
	}

	$providers = [];

	foreach ( $connectors as $id => $connector ) {
		if ( 'ai_provider' === $connector['type'] && call_user_func( $connector['plugin']['is_active'] ) ) {
			$providers[ $id ] = $connector['name'];
		}
	}

	return $providers;
}

/**
 * Start a prompt to an AI provider configured in WordPress.
 *
 * Returns the core prompt builder, so callers configure and send the prompt
 * with its methods. Errors from sending it (e.g. AI disabled on the site, the
 * prompt blocked by a filter, an unknown or unconfigured provider, a failed
 * request) come back as a `WP_Error` from the builder's `generate_*()`
 * methods.
 *
 *     $builder = get_ai_prompt_builder( 'Summarize this post.', 'anthropic' );
 *
 *     if ( ! is_wp_error( $builder ) ) {
 *         $text = $builder->generate_text();
 *     }
 *
 * @since 2.0.0
 *
 * @param string      $prompt   Prompt text.
 * @param string|null $provider Optional. Connector ID of the provider to use, from
 *                              `get_ai_providers()`. Default null, which lets WordPress
 *                              choose a provider that supports the prompt.
 * @return \WP_AI_Client_Prompt_Builder|\WP_Error Prompt builder, or an error before WordPress 7.0.
 */
function get_ai_prompt_builder( string $prompt, ?string $provider = null ): \WP_AI_Client_Prompt_Builder|\WP_Error {
	if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
		return new \WP_Error( 'wp_seo_ai_unsupported', __( 'WP SEO requires WordPress 7.0 or later to use AI providers.', 'wp-seo' ) );
	}

	$builder = wp_ai_client_prompt( $prompt );

	if ( null !== $provider ) {
		$builder = $builder->using_provider( $provider );
	}

	return $builder;
}
