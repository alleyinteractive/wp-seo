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
 * The provider is chosen on the WP SEO AI settings page, or set in code with
 * the `wp_seo_ai_provider` filter.
 */
final class AI_Providers {
	/**
	 * Name of the option holding the chosen provider.
	 *
	 * @var string
	 */
	public const OPTION = 'wp_seo_ai_provider';

	/**
	 * The class is static; there is nothing to instantiate.
	 */
	private function __construct() {}

	/**
	 * Get the AI provider WP SEO sends its prompts to.
	 *
	 * The provider set in code, if any, or else the one chosen on the WP SEO AI
	 * settings page. Whether the provider is still available is not checked; an
	 * unavailable one surfaces as an error when a prompt is sent.
	 *
	 * To send a prompt, use `check_provider()`, which checks that one can be
	 * sent.
	 *
	 * @since 2.0.0
	 *
	 * @return string Connector ID of the provider, or an empty string if there
	 *                is none and no prompts should be sent.
	 */
	public static function get_provider(): string {
		$provider = self::get_provider_from_code();

		if ( null !== $provider ) {
			return $provider;
		}

		$stored = get_option( self::OPTION );

		return is_string( $stored ) ? $stored : '';
	}

	/**
	 * Get the choice of AI provider, as the WP SEO AI settings page shows it.
	 *
	 * The providers to choose from are the available ones, plus the current
	 * provider if it is not available, as one set in code can be, so that the
	 * page does not show "None" while prompts are still sent there.
	 *
	 * @since 2.0.0
	 *
	 * @return array{provider: string, providers: array<string, string>, in_code: bool} The
	 *         provider, or an empty string for none; the providers to choose
	 *         from, keyed by connector ID; and whether the provider is set in
	 *         code, which makes the choice read-only.
	 */
	public static function get_choice(): array {
		$in_code   = null !== self::get_provider_from_code();
		$provider  = self::get_provider();
		$providers = self::get_available_providers();

		if ( '' !== $provider && ! isset( $providers[ $provider ] ) ) {
			$providers[ $provider ] = sprintf(
				/* translators: %s: connector ID of the provider. */
				__( '%s (unavailable)', 'wp-seo' ),
				$provider
			);
		}

		return [
			'provider'  => $provider,
			'providers' => $providers,
			'in_code'   => $in_code,
		];
	}

	/**
	 * Sanitize the chosen AI provider before it is saved.
	 *
	 * Keeps the provider only if it is an available AI provider. When nothing
	 * is submitted, as when the field is disabled because the provider is set
	 * in code, the stored provider is kept. An empty string, which is what
	 * choosing "None" sends, clears it.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed $value Connector ID to save.
	 * @return string Connector ID, or an empty string for none.
	 */
	public static function sanitize_provider( mixed $value ): string {
		if ( null === $value ) {
			$stored = get_option( self::OPTION );

			return is_string( $stored ) ? $stored : '';
		}

		return is_string( $value ) && isset( self::get_available_providers()[ $value ] ) ? $value : '';
	}

	/**
	 * Get the AI provider set in code, if any.
	 *
	 * A provider set in code takes the place of the one chosen on the WP SEO AI
	 * settings page, which then shows the setting as read-only.
	 *
	 * @return string|null Connector ID of the provider, an empty string for no
	 *                     provider, or null to leave it to the settings page.
	 */
	private static function get_provider_from_code(): ?string {
		/**
		 * Filters the AI provider WP SEO sends its prompts to.
		 *
		 * Return a connector ID to use that provider, or an empty string for none,
		 * and the setting on the WP SEO AI settings page becomes read-only.
		 * Return null to leave the choice to the settings page.
		 *
		 * @since 2.0.0
		 *
		 * @param string|null $provider Connector ID of the provider. Default null.
		 */
		$provider = apply_filters( 'wp_seo_ai_provider', null );

		return is_string( $provider ) ? $provider : null;
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
	 * Get the AI provider to send a prompt to, checking that one can be sent.
	 *
	 * Prompts cannot be sent before WordPress 7.0, when AI is turned off on the
	 * site, or when there is no provider. Whether the provider is still
	 * available is not checked; an unavailable one surfaces as an error when a
	 * prompt is sent.
	 *
	 *     $provider = AI_Providers::check_provider();
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
	public static function check_provider(): string|\WP_Error {
		if ( ! function_exists( 'wp_supports_ai' ) || ! wp_supports_ai() ) {
			return new \WP_Error( 'wp_seo_ai_unsupported', __( 'AI is not available on this site. WP SEO needs WordPress 7.0 or later, with AI turned on.', 'wp-seo' ) );
		}

		$provider = self::get_provider();

		if ( '' === $provider ) {
			return new \WP_Error(
				'wp_seo_ai_no_provider',
				__( 'No AI provider is selected on the WP SEO AI settings page.', 'wp-seo' ),
				[ 'status' => 503 ]
			);
		}

		return $provider;
	}
}
