<?php
/**
 * Feature class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

use Alley\WP\Features\Group;

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
	 * enable or disable the feature. The leading letter is required for a second
	 * reason: PHP converts an all-digit array key to an integer, so a numeric
	 * handle would key the Registry by something that is not a handle.
	 *
	 * @var string
	 */
	private const HANDLE_PATTERN = '/^[a-z][a-z0-9_]*$/';

	/**
	 * Whether this feature claimed its handle and belongs to the Registry.
	 *
	 * @var bool
	 */
	private bool $registered = false;

	/**
	 * Handle of the group that holds this feature, if any.
	 *
	 * Written by `group()` as it receives its children, which is the only place
	 * the relationship is known: `Group` cannot be enumerated, and PHP has
	 * already constructed a child by the time its group exists.
	 *
	 * @var string|null
	 */
	private ?string $parent = null;

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
	 * @param string                  $label   Human-readable name of this feature.
	 * @param \Alley\WP\Types\Feature $origin  Feature to boot when the handle is enabled.
	 * @param bool                    $default Whether the handle is enabled before filtering.
	 */
	private function __construct(
		private readonly string $handle,
		private readonly string $label,
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
					'The feature handle "%s" is not usable. Handles are interpolated into the names of the filters that enable a feature and key the registry, so they must begin with a lowercase letter and may otherwise contain only lowercase letters, numbers, and underscores.',
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
	 * @param string                  $label  Human-readable name of this feature.
	 * @param \Alley\WP\Types\Feature $origin Feature to boot when the handle is enabled.
	 * @return self
	 */
	public static function top_level( string $handle, string $label, \Alley\WP\Types\Feature $origin ): self {
		return new self( $handle, $label, $origin, false );
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
	 * @param string                  $label  Human-readable name of this feature.
	 * @param \Alley\WP\Types\Feature $origin Feature to boot unless the handle is disabled.
	 * @return self
	 */
	public static function nested( string $handle, string $label, \Alley\WP\Types\Feature $origin ): self {
		return new self( $handle, $label, $origin, true );
	}

	/**
	 * A group of features under a handle of its own, off unless something
	 * enables it.
	 *
	 * The group's handle is the switch for everything it holds: enabling it runs
	 * the children, and leaving it off short-circuits them however their own
	 * filters answer. Children are built with `nested()`, so they are already
	 * constructed -- and already in the Registry -- by the time the group
	 * receives them; the group records that it holds them, since nothing else
	 * can.
	 *
	 * A group is a top-level feature, so a group given to another group still
	 * needs enabling on its own.
	 *
	 * The label comes before the children rather than after them, because the
	 * children are variadic and nothing can follow them. The other two
	 * constructors take it in the same position so that the three read alike.
	 *
	 * @link docs/adr/0008-groups-own-their-children.md
	 *
	 * @param string $handle      Handle identifying this group.
	 * @param string $label       Human-readable name of this group.
	 * @param self   ...$children Features the group holds.
	 * @return self
	 */
	public static function group( string $handle, string $label, self ...$children ): self {
		$group = new self( $handle, $label, new Group( ...$children ), false );

		/*
		 * A group that did not claim its handle is not in the Registry, so
		 * nothing would list its children underneath it. Leaving them
		 * parentless reports them as what they are: features on their own that
		 * no group in the Registry holds.
		 */
		if ( $group->registered ) {
			foreach ( $children as $child ) {
				$child->parent = $handle;
			}
		}

		return $group;
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
	 * The human-readable name of this feature.
	 *
	 * Written at the call site rather than derived from the handle. Deriving
	 * reads well enough for `open_graph` and then renders a later
	 * `title_description_tags` as "Title Description Tags" instead of the
	 * "Title & Description Tags" someone would have written. It is display
	 * text, so unlike the handle it is neither validated nor constrained: it
	 * names nothing and keys nothing.
	 *
	 * @return string Feature label.
	 */
	public function label(): string {
		return $this->label;
	}

	/**
	 * The handle of the group that holds this feature.
	 *
	 * A handle rather than the group itself, because that is what the answer is
	 * for: reporting the tree the Registry contains, where every feature is
	 * already known by handle.
	 *
	 * @return string|null Group handle, or null for a feature no group holds.
	 */
	public function parent(): ?string {
		return $this->parent;
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
