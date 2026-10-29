<?php
/**
 * AI_Providers class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

/**
 * The AI providers WP SEO can send prompts to, and the one it uses.
 *
 * AI providers are the connectors a site administrator configures under
 * Settings > Connectors (WordPress 7.0+). Before WordPress 7.0, there are none,
 * and a prompt cannot be started.
 *
 * The provider is set in code with the `wp_seo_ai_provider` filter.
 */
final class AI_Providers {
	/**
	 * The class is static; there is nothing to instantiate.
	 */
	private function __construct() {}

	/**
	 * Get the AI provider WP SEO sends its prompts to.
	 *
	 * Returns the provider set with the `wp_seo_ai_provider` filter. To send a
	 * prompt, use `get_prompt_provider()` instead, which also checks that
	 * prompts can be sent.
	 *
	 * @since 2.0.0
	 *
	 * @return string Connector ID of the provider, or an empty string if there
	 *                is none and no prompts should be sent.
	 */
	public static function get_provider(): string {
		/**
		 * Filters the AI provider WP SEO sends its prompts to.
		 *
		 * Return a connector ID to use that provider, or an empty string or null
		 * for none.
		 *
		 * @since 2.0.0
		 *
		 * @param string|null $provider Connector ID of the provider. Default null.
		 */
		$provider = apply_filters( 'wp_seo_ai_provider', null );

		return is_string( $provider ) ? $provider : '';
	}

	/**
	 * Get the AI providers WP SEO can send prompts to.
	 *
	 * Lists the connectors that are AI providers and whose provider plugin is
	 * active. Whether the provider has working credentials is not checked, since
	 * that takes a request to the provider; a missing or bad key surfaces as an
	 * error when a prompt is sent.
	 *
	 * @since 2.0.0
	 *
	 * @return array<string, string> Provider names keyed by connector ID. Empty
	 *                               before WordPress 7.0 or when AI is disabled.
	 */
	public static function get_available_providers(): array {
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
	 * Get the AI provider to send a prompt to, or an error if prompts can't be sent.
	 *
	 * Prompts can't be sent before WordPress 7.0, when AI is turned off on the
	 * site, or when there is no provider. Whether the provider is still
	 * available is not checked; an unavailable one surfaces as an error when a
	 * prompt is sent.
	 *
	 *     $provider = AI_Providers::get_prompt_provider();
	 *
	 *     if ( ! is_wp_error( $provider ) ) {
	 *         $text = wp_ai_client_prompt( 'Summarize this post.' )->using_provider( $provider )->generate_text();
	 *     }
	 *
	 * @since 2.0.0
	 *
	 * @return string|\WP_Error Connector ID of the provider, or an error if
	 *                          prompts cannot be sent.
	 */
	public static function get_prompt_provider(): string|\WP_Error {
		if ( ! function_exists( 'wp_supports_ai' ) || ! wp_supports_ai() ) {
			return new \WP_Error( 'wp_seo_ai_unsupported', __( 'AI is not available on this site. WP SEO needs WordPress 7.0 or later, with AI turned on.', 'wp-seo' ) );
		}

		$provider = self::get_provider();

		if ( '' === $provider ) {
			return new \WP_Error(
				'wp_seo_ai_no_provider',
				__( 'No AI provider is set for WP SEO.', 'wp-seo' ),
				[ 'status' => 503 ]
			);
		}

		return $provider;
	}
}
