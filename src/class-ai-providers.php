<?php
/**
 * AI_Providers class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

/**
 * The AI providers WP SEO features can send prompts to, and the one each
 * feature uses.
 *
 * AI providers are the connectors a site administrator configures under
 * Settings > Connectors (WordPress 7.0+). Before WordPress 7.0, there are none,
 * and a prompt cannot be started.
 *
 * Each feature's provider is chosen on the WP SEO AI settings page, or set in
 * code with the `wp_seo_ai_provider` filter.
 */
final class AI_Providers {
	/**
	 * Name of the option holding the provider chosen for each feature.
	 *
	 * @var string
	 */
	public const OPTION = 'wp_seo_ai_providers';

	/**
	 * The class is static; there is nothing to instantiate.
	 */
	private function __construct() {}

	/**
	 * Get the AI provider a WP SEO feature sends its prompts to.
	 *
	 * The provider set in code, if any, or else the one chosen on the WP SEO AI
	 * settings page. Whether the provider is still available is not checked; an
	 * unavailable one surfaces as an error when a prompt is sent.
	 *
	 * To send a prompt, use `get_prompt_builder()`, which checks that the
	 * feature has a provider.
	 *
	 * @since 2.0.0
	 *
	 * @param string $feature Handle of the feature sending prompts.
	 * @return string Connector ID of the provider, or an empty string if the
	 *                feature has none and should not send prompts.
	 */
	public static function get_feature_provider( string $feature ): string {
		$provider = self::get_feature_provider_from_code( $feature );

		if ( null !== $provider ) {
			return $provider;
		}

		$stored = get_option( self::OPTION );

		return is_array( $stored ) && isset( $stored[ $feature ] ) && is_string( $stored[ $feature ] ) ? $stored[ $feature ] : '';
	}

	/**
	 * Get the choice of AI provider for a WP SEO feature, as the WP SEO AI
	 * settings page shows it.
	 *
	 * The providers to choose from are the available ones, plus the feature's
	 * provider if it is not available, as one set in code can be, so that the
	 * page does not show "None" for a feature that still sends its prompts
	 * there.
	 *
	 * @since 2.0.0
	 *
	 * @param string $feature Handle of the feature sending prompts.
	 * @return array{provider: string, providers: array<string, string>, in_code: bool} The
	 *         feature's provider, or an empty string for none; the providers to
	 *         choose from, keyed by connector ID; and whether the provider is set
	 *         in code, which makes the choice read-only.
	 */
	public static function get_feature_choice( string $feature ): array {
		$in_code   = null !== self::get_feature_provider_from_code( $feature );
		$provider  = self::get_feature_provider( $feature );
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
	 * Sanitize the AI providers chosen for WP SEO features before they are
	 * saved.
	 *
	 * Keeps a provider only for one of the given features, and only if it is an
	 * available AI provider. A feature missing from the input keeps the provider
	 * already stored for it, so that a form can leave out features it does not
	 * let the user change. A feature given an empty string, which is what
	 * choosing "None" sends, has its provider cleared.
	 *
	 * @since 2.0.0
	 *
	 * @param mixed    $value    Connector IDs to save, keyed by feature handle.
	 * @param string[] $features Handles of the features whose providers can be saved.
	 * @return array<string, string> Connector IDs keyed by feature handle.
	 */
	public static function sanitize_feature_providers( mixed $value, array $features ): array {
		$value     = is_array( $value ) ? $value : [];
		$stored    = get_option( self::OPTION );
		$stored    = is_array( $stored ) ? $stored : [];
		$providers = self::get_available_providers();
		$sanitized = [];

		foreach ( $features as $feature ) {
			$provider = $value[ $feature ] ?? $stored[ $feature ] ?? '';

			if ( is_string( $provider ) && isset( $providers[ $provider ] ) ) {
				$sanitized[ $feature ] = $provider;
			}
		}

		return $sanitized;
	}

	/**
	 * Get the AI provider set in code for a WP SEO feature, if any.
	 *
	 * A provider set in code takes the place of the one chosen on the WP SEO AI
	 * settings page, which then shows that feature's setting as read-only.
	 *
	 * @param string $feature Handle of the feature sending prompts.
	 * @return string|null Connector ID of the provider, an empty string for no
	 *                     provider, or null to leave it to the settings page.
	 */
	private static function get_feature_provider_from_code( string $feature ): ?string {
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
	 * Check that a WP SEO feature can send prompts.
	 *
	 * A feature cannot send prompts before WordPress 7.0, or when it has no
	 * provider. Whether the provider is still available is not checked; an
	 * unavailable one surfaces as an error when a prompt is sent.
	 *
	 * @since 2.0.0
	 *
	 * @param string $feature Handle of the feature sending prompts.
	 * @return true|\WP_Error True if the feature can send prompts, or an error.
	 */
	public static function check_feature( string $feature ): true|\WP_Error {
		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return new \WP_Error( 'wp_seo_ai_unsupported', __( 'WP SEO requires WordPress 7.0 or later to use AI providers.', 'wp-seo' ) );
		}

		if ( '' === self::get_feature_provider( $feature ) ) {
			$registry = Registry::features();

			return new \WP_Error(
				'wp_seo_ai_no_provider',
				isset( $registry[ $feature ] )
					/* translators: %s: name of the feature. */
					? sprintf( __( 'No AI provider is selected for the %s.', 'wp-seo' ), $registry[ $feature ]->label() )
					: __( 'No AI provider is selected for this feature.', 'wp-seo' ),
				[ 'status' => 503 ]
			);
		}

		return true;
	}

	/**
	 * Start a prompt from a WP SEO feature to its AI provider.
	 *
	 * Returns the core prompt builder, so callers configure and send the prompt
	 * with its methods. Errors from sending it (e.g. AI disabled on the site, the
	 * prompt blocked by a filter, an unknown or unconfigured provider, a failed
	 * request) come back as a `WP_Error` from the builder's `generate_*()`
	 * methods.
	 *
	 *     $builder = AI_Providers::get_prompt_builder( 'description_generator', 'Summarize this post.' );
	 *
	 *     if ( ! is_wp_error( $builder ) ) {
	 *         $text = $builder->generate_text();
	 *     }
	 *
	 * @since 2.0.0
	 *
	 * @param string $feature Handle of the feature sending the prompt.
	 * @param string $prompt  Prompt text.
	 * @return \WP_AI_Client_Prompt_Builder|\WP_Error Prompt builder, or the error
	 *                                                from `check_feature()`.
	 */
	public static function get_prompt_builder( string $feature, string $prompt ): \WP_AI_Client_Prompt_Builder|\WP_Error {
		$check = self::check_feature( $feature );

		if ( is_wp_error( $check ) ) {
			return $check;
		}

		return wp_ai_client_prompt( $prompt )->using_provider( self::get_feature_provider( $feature ) );
	}
}
