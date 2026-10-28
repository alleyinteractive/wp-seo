<?php
/**
 * Contains functions for choosing the AI provider a WP SEO feature sends its prompts to.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

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
 *         $builder = AI_Providers::get_prompt_builder( 'Summarize this post.', $provider );
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
