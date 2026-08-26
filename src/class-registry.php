<?php
/**
 * Registry class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

/**
 * The enumerable collection of every WP SEO feature.
 *
 * Features add themselves here as they are constructed, so the registry knows
 * about every feature that exists whether or not it was enabled. It backs the
 * readonly admin display, the toggle UI, and the generated recommended-features
 * documentation.
 */
final class Registry {
	/**
	 * Registered features, keyed by handle.
	 *
	 * @var array<string, Feature>
	 */
	private static array $features = [];

	/**
	 * The registry is static; there is nothing to instantiate.
	 */
	private function __construct() {}

	/**
	 * Add a feature to the registry.
	 *
	 * Called by the feature itself as it is constructed.
	 *
	 * A handle names the filters that enable a feature, so two features sharing
	 * one cannot be enabled independently. The feature that claimed the handle
	 * keeps it, so that a stray duplicate cannot displace the real feature. The
	 * duplicate is told that it was turned away, so that it does not boot
	 * behavior the registry would then fail to report.
	 *
	 * @param Feature $feature Feature to register.
	 * @return bool Whether the feature was registered.
	 */
	public static function register( Feature $feature ): bool {
		$handle = $feature->handle();

		if ( isset( self::$features[ $handle ] ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					'A feature is already registered with the handle "%s". Handles name the filters that enable a feature and must be unique.',
					esc_html( $handle )
				),
				'2.0.0'
			);

			return false;
		}

		self::$features[ $handle ] = $feature;

		return true;
	}

	/**
	 * Every registered feature, in the order the features were constructed.
	 *
	 * @return array<string, Feature> Features keyed by handle.
	 */
	public static function features(): array {
		return self::$features;
	}

	/**
	 * Forget every registered feature.
	 *
	 * Test support only. The registry is static state that would otherwise leak
	 * from one test to the next; production code has no reason to call this.
	 */
	public static function reset_for_tests(): void {
		self::$features = [];
	}
}
