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

	$providers = [];

	foreach ( wp_get_connectors() as $id => $connector ) {
		if ( 'ai_provider' === $connector['type'] && call_user_func( $connector['plugin']['is_active'] ) ) {
			$providers[ $id ] = $connector['name'];
		}
	}

	return $providers;
}

/**
 * Get the AI provider a WP SEO feature sends its prompts to.
 *
 * The provider set in code with the `wp_seo_ai_provider` filter, if any, or
 * else the one chosen on the WP SEO AI settings page. Whether the provider is
 * still available is not checked; an unavailable one surfaces as an error when
 * a prompt is sent.
 *
 *     $provider = get_ai_provider( 'description_generator' );
 *
 *     if ( '' !== $provider ) {
 *         $text = get_ai_prompt( 'Summarize this post.', $provider )->generate_text();
 *     }
 *
 * @since 2.0.0
 *
 * @param string $feature Handle of the feature sending prompts.
 * @return string Connector ID of the provider, or an empty string if the
 *                feature has none and should not send prompts.
 */
function get_ai_provider( string $feature ): string {
	$provider = get_ai_provider_from_code( $feature );

	if ( null !== $provider ) {
		return $provider;
	}

	$stored = get_option( AI_Settings::OPTION );

	return is_array( $stored ) && isset( $stored[ $feature ] ) && is_string( $stored[ $feature ] ) ? $stored[ $feature ] : '';
}

/**
 * Get the AI provider set in code for a WP SEO feature, if any.
 *
 * A provider set in code takes the place of the one chosen on the WP SEO AI
 * settings page, which then shows that feature's setting as read-only.
 *
 * @since 2.0.0
 *
 * @param string $feature Handle of the feature sending prompts.
 * @return string|null Connector ID of the provider, an empty string for no
 *                     provider, or null to leave it to the settings page.
 */
function get_ai_provider_from_code( string $feature ): ?string {
	/**
	 * Filters the AI provider a WP SEO feature sends its prompts to.
	 *
	 * Return a connector ID to use that provider, or an empty string for none,
	 * and the feature's setting on the WP SEO AI settings page becomes
	 * read-only. Return null to leave the choice to the settings page.
	 *
	 * @since 2.0.0
	 *
	 * @param string|null $provider Connector ID of the provider. Default null.
	 * @param string      $feature  Handle of the feature sending prompts.
	 */
	$provider = apply_filters( 'wp_seo_ai_provider', null, $feature );

	return is_string( $provider ) ? $provider : null;
}

/**
 * Start a prompt to an AI provider configured in WordPress.
 *
 * Returns the core prompt builder, so callers configure and send the prompt
 * with its methods. Errors from sending it -- AI disabled on the site, the
 * prompt blocked by a filter, an unknown or unconfigured provider, a failed
 * request -- come back as a `WP_Error` from the builder's `generate_*()`
 * methods.
 *
 *     $text = get_ai_prompt( 'Summarize this post.', 'anthropic' )->generate_text();
 *
 * @since 2.0.0
 *
 * @param string      $prompt   Prompt text.
 * @param string|null $provider Optional. Connector ID of the provider to use, from
 *                              `get_ai_providers()`. Default null, which lets WordPress
 *                              choose a provider that supports the prompt.
 * @return \WP_AI_Client_Prompt_Builder|\WP_Error Prompt builder, or an error before WordPress 7.0.
 */
function get_ai_prompt( string $prompt, ?string $provider = null ): \WP_AI_Client_Prompt_Builder|\WP_Error {
	if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
		return new \WP_Error( 'wp_seo_ai_unsupported', __( 'WP SEO requires WordPress 7.0 or later to use AI providers.', 'wp-seo' ) );
	}

	$builder = wp_ai_client_prompt( $prompt );

	if ( null !== $provider ) {
		$builder = $builder->using_provider( $provider );
	}

	return $builder;
}
