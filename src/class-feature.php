<?php
/**
 * Feature class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

/**
 * A WP SEO feature: some plugin behavior paired with the handle that gates it.
 *
 * Wrapping a feature in this class gives it a stable handle, adds it to the
 * Registry, and decides from the filters named for that handle whether to boot
 * it. Features are opt-in: a top-level feature stays off unless something
 * enables it.
 *
 * Gating is synchronous, so a feature must not be composed before themes and
 * other plugins have had a chance to add their filters. The plugin waits once,
 * for all of its features together, by composing them on `after_setup_theme`.
 */
final class Feature implements \Alley\WP\Types\Feature {
	/**
	 * The handle that the global enablement filter has already taken.
	 *
	 * A feature claiming it would name its per-handle filter
	 * `wp_seo_enable_feature`, the same hook as the global gate. The global gate
	 * passes its callbacks the handle as a second argument and the per-handle
	 * filter does not, so any consumer using the documented global signature
	 * would fatal.
	 *
	 * @var string
	 */
	private const RESERVED_HANDLE = 'feature';

	/**
	 * The characters a handle may be made of.
	 *
	 * Anything else could not be written as a filter name, so nothing could
	 * enable or disable the feature.
	 *
	 * @var string
	 */
	private const HANDLE_PATTERN = '/^[a-z0-9_]+$/';

	/**
	 * Whether this feature claimed its handle and belongs to the Registry.
	 *
	 * @var bool
	 */
	private bool $registered = false;

	/**
	 * Whether the wrapped feature was booted.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Set up.
	 *
	 * Private so that a feature is built through one of the named constructors,
	 * which say at the call site whether it is top-level or nested.
	 *
	 * @param string                  $handle  Handle identifying this feature.
	 * @param \Alley\WP\Types\Feature $origin  Feature to boot when the handle is enabled.
	 * @param bool                    $default Whether the handle is enabled before filtering.
	 */
	private function __construct(
		private readonly string $handle,
		private readonly \Alley\WP\Types\Feature $origin,
		private readonly bool $default,
	) {
		if ( self::RESERVED_HANDLE === $handle ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					'The feature handle "%s" is reserved. It would name the same filter as the global wp_seo_enable_feature gate, which passes its callbacks an argument that a per-handle filter does not.',
					esc_html( $handle )
				),
				'2.0.0'
			);

			return;
		}

		if ( ! preg_match( self::HANDLE_PATTERN, $handle ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					'The feature handle "%s" is not usable. Handles are interpolated into the names of the filters that enable a feature, so they may contain only lowercase letters, numbers, and underscores.',
					esc_html( $handle )
				),
				'2.0.0'
			);

			return;
		}

		$this->registered = Registry::register( $this );
	}

	/**
	 * A feature the site has not asked for, off unless something enables it.
	 *
	 * @param string                  $handle Handle identifying this feature.
	 * @param \Alley\WP\Types\Feature $origin Feature to boot when the handle is enabled.
	 * @return self
	 */
	public static function top_level( string $handle, \Alley\WP\Types\Feature $origin ): self {
		return new self( $handle, $origin, false );
	}

	/**
	 * A feature inside a group that carries its own handle, on unless something
	 * disables it individually.
	 *
	 * Enabling the group is the request to run what is inside it, so a nested
	 * feature starts enabled. Its own handle exists so that it can be turned off
	 * on its own, and is only ever consulted when the group around it was
	 * enabled.
	 *
	 * @param string                  $handle Handle identifying this feature.
	 * @param \Alley\WP\Types\Feature $origin Feature to boot unless the handle is disabled.
	 * @return self
	 */
	public static function nested( string $handle, \Alley\WP\Types\Feature $origin ): self {
		return new self( $handle, $origin, true );
	}

	/**
	 * Boot the wrapped feature, if this feature's handle is enabled.
	 *
	 * The filters are read here and now: whoever composed this feature is
	 * responsible for not doing so before themes and plugins could add them.
	 */
	public function boot(): void {
		/*
		 * A feature that never claimed its handle is not in the Registry, so
		 * nothing reports that it exists and no filter can reach it. Booting it
		 * would run behavior the site cannot see or turn off.
		 */
		if ( ! $this->registered ) {
			return;
		}

		// One instance can be reachable from more than one place in a tree.
		if ( $this->booted ) {
			return;
		}

		$enabled = $this->default;

		/**
		 * Filters whether to enable a WP SEO feature.
		 *
		 * @since 2.0.0
		 *
		 * @param bool   $enabled Whether to enable the feature. Default false for a top-level
		 *                        feature, true for a feature nested within an enabled group.
		 * @param string $handle  Feature handle.
		 */
		$enabled = apply_filters( 'wp_seo_enable_feature', $enabled, $this->handle );

		/**
		 * Filters whether to enable the given WP SEO feature.
		 *
		 * The dynamic portion of the hook name, `$handle`, refers to the feature's handle.
		 *
		 * @since 2.0.0
		 *
		 * @param bool $enabled Whether to enable the feature, as left by `wp_seo_enable_feature`.
		 */
		$enabled = apply_filters( "wp_seo_enable_{$this->handle}", $enabled );

		if ( ! $enabled ) {
			return;
		}

		$this->booted = true;

		$this->origin->boot();
	}

	/**
	 * The handle identifying this feature.
	 *
	 * @return string Feature handle.
	 */
	public function handle(): string {
		return $this->handle;
	}

	/**
	 * Whether the wrapped feature booted.
	 *
	 * This reports what happened, not what the filters asked for: a feature
	 * nested within a group that was never enabled is never reached, so it
	 * reports false however its own filters would have answered.
	 *
	 * @return bool Whether the feature booted.
	 */
	public function booted(): bool {
		return $this->booted;
	}
}
