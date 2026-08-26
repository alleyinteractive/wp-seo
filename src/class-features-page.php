<?php
/**
 * Features_Page class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

/**
 * The admin page that reports what the feature Registry contains.
 *
 * The page answers "what is WP SEO doing on this site" by listing every feature
 * that exists and whether it is running. It is readonly: features are enabled
 * with filters, and a toggle UI is deferred to a later release.
 *
 * This is deliberately not a `Feature` and carries no handle. A readonly admin
 * screen emits nothing to the front end, so gating it protects nothing, and
 * making it opt-in would hide it from exactly the person most likely to need
 * it -- someone looking at a site where nothing appears to be enabled.
 *
 * @link docs/adr/0004-readonly-settings-page-in-v2-0.md
 * @link docs/adr/0007-the-features-page-is-not-itself-a-feature.md
 */
final class Features_Page {
	/**
	 * Slug of the top-level menu, and of the features page within it.
	 *
	 * @var string
	 */
	public const SLUG = 'wp-seo-features';

	/**
	 * The capability required to view the page before filtering.
	 *
	 * @var string
	 */
	private const DEFAULT_CAPABILITY = 'manage_options';

	/**
	 * The color the page paints an Active status.
	 *
	 * WordPress's own admin success green, so that the page reads as part of
	 * the admin around it rather than as a screen with a palette of its own.
	 *
	 * @var string
	 */
	private const ACTIVE_COLOR = '#008a20';

	/**
	 * The color the page paints an Inactive status.
	 *
	 * WordPress's own muted admin gray, not its error red. Every feature is
	 * off until a site asks for it, so Inactive is the designed default rather
	 * than a fault, and a site that has enabled nothing would otherwise render
	 * a column of red that reads as broken. Green marks what is running;
	 * absence of it says the rest is not.
	 *
	 * @var string
	 */
	private const INACTIVE_COLOR = '#646970';

	/**
	 * The capability this request settled on, once it has been asked for.
	 *
	 * @var string|null
	 */
	private ?string $capability = null;

	/**
	 * Put the page in the admin menu.
	 *
	 * Safe to call any time before `admin_menu`, which is long after the
	 * `after_setup_theme` hook where WP SEO composes itself.
	 */
	public function register(): void {
		add_action( 'admin_menu', [ $this, 'add_pages' ] );
	}

	/**
	 * Register the top-level WP SEO menu and the features page inside it.
	 *
	 * The plugin's existing content-configuration settings page stays where it
	 * is, under `Settings > SEO`; moving it is tracked separately.
	 */
	public function add_pages(): void {
		$capability = $this->capability();

		add_menu_page(
			__( 'WP SEO Features', 'wp-seo' ),
			__( 'WP SEO', 'wp-seo' ),
			$capability,
			self::SLUG,
			[ $this, 'render' ],
			'dashicons-search'
		);

		/*
		 * Name the page within its own menu, so that the menu can hold a second
		 * page later without WordPress naming this one after the menu itself.
		 *
		 * The label does not render today: while this is the only page under the
		 * menu, WordPress removes a lone submenu item whose slug matches its
		 * parent, on the grounds that it only repeats the menu. Registering it
		 * anyway is what makes "Features" the name of the first item on the day a
		 * second one appears, instead of a second "WP SEO".
		 */
		add_submenu_page(
			self::SLUG,
			__( 'WP SEO Features', 'wp-seo' ),
			__( 'Features', 'wp-seo' ),
			$capability,
			self::SLUG,
			[ $this, 'render' ]
		);
	}

	/**
	 * The capability a user needs to view the features page.
	 *
	 * Asked once and reused: the menu is built during `admin_menu` and the page
	 * renders later, so a filter added in between would otherwise let the two
	 * disagree about who may see the page.
	 *
	 * @return string Capability name.
	 */
	public function capability(): string {
		if ( null !== $this->capability ) {
			return $this->capability;
		}

		/**
		 * Filters the capability required to view the WP SEO features page.
		 *
		 * @since 2.0.0
		 *
		 * @param string $capability Capability required to view the features page. Default 'manage_options'.
		 */
		$filtered = apply_filters( 'wp_seo_features_page_capability', self::DEFAULT_CAPABILITY );

		$this->capability = $this->usable_capability( $filtered );

		return $this->capability;
	}

	/**
	 * The given capability, or the default if it is not one.
	 *
	 * A filter is a callback on somebody else's site, and a branch that forgets
	 * to return leaves nothing where a capability should be. The answer is read
	 * while WordPress builds the admin menu, so passing on something it cannot
	 * use would take down every admin screen at once -- including the one for
	 * deactivating whatever caused it.
	 *
	 * @param mixed $capability Filtered capability.
	 * @return string Capability name.
	 */
	private function usable_capability( mixed $capability ): string {
		return is_string( $capability ) && '' !== $capability ? $capability : self::DEFAULT_CAPABILITY;
	}

	/**
	 * The registered features in the order the page lists them: each group
	 * ahead of the features it holds, and how deep each one sits.
	 *
	 * Features register themselves as they are constructed, and PHP constructs
	 * the children of a group before the group itself, so the registry's own
	 * order puts children above the group they belong to.
	 *
	 * @return array<int, array{feature: Feature, depth: int}> Features to list, in order.
	 */
	private function rows(): array {
		$features = Registry::features();
		$children = [];

		foreach ( $features as $handle => $feature ) {
			$parent = $feature->parent();

			if ( null !== $parent ) {
				$children[ $parent ][ $handle ] = $feature;
			}
		}

		$listed = [];

		foreach ( $features as $feature ) {
			// Anything inside a group is listed by the group that holds it.
			if ( null === $feature->parent() ) {
				$listed = array_merge( $listed, $this->list_feature( $feature, 0, $children ) );
			}
		}

		return $listed;
	}

	/**
	 * A feature, followed by everything it holds.
	 *
	 * @param Feature                               $feature  Feature to list.
	 * @param int                                   $depth    How many groups the feature sits inside.
	 * @param array<string, array<string, Feature>> $children Features held by each group, keyed by group handle.
	 * @return array<int, array{feature: Feature, depth: int}> Rows.
	 */
	private function list_feature( Feature $feature, int $depth, array $children ): array {
		$listed = [
			[
				'feature' => $feature,
				'depth'   => $depth,
			],
		];

		foreach ( $children[ $feature->handle() ] ?? [] as $child ) {
			$listed = array_merge( $listed, $this->list_feature( $child, $depth + 1, $children ) );
		}

		return $listed;
	}

	/**
	 * Render the features page.
	 *
	 * The menu already restricts who can reach this, but the page reports how a
	 * site is configured, so it checks for itself rather than trusting whatever
	 * called it.
	 */
	public function render(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'wp-seo' ), 403 );
		}

		$rows = $this->rows();

		// Nesting needs explaining, but only to someone who is looking at some.
		$nested = (bool) array_filter( $rows, static fn ( array $row ): bool => $row['depth'] > 0 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP SEO Features', 'wp-seo' ); ?></h1>

			<?php if ( ! $rows ) : ?>
				<p>
					<?php esc_html_e( 'No WP SEO features are registered on this site. Features add themselves to this list as the plugin loads them, so there is nothing to report yet.', 'wp-seo' ); ?>
				</p>
			<?php else : ?>
				<p>
					<?php esc_html_e( 'Every WP SEO feature registered on this site, and whether it is running. This page is read-only: features are turned on and off in code.', 'wp-seo' ); ?>
					<?php if ( $nested ) : ?>
						<?php esc_html_e( 'Indented features belong to the group above them, and run only when the group holding them is running.', 'wp-seo' ); ?>
					<?php endif; ?>
				</p>

				<table class="widefat striped">
					<thead>
						<tr>
							<th scope="col"><?php echo esc_html_x( 'Feature', 'feature list column heading', 'wp-seo' ); ?></th>
							<th scope="col"><?php echo esc_html_x( 'Handle', 'feature list column heading', 'wp-seo' ); ?></th>
							<th scope="col"><?php echo esc_html_x( 'Status', 'feature list column heading', 'wp-seo' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rows as $row ) : ?>
							<?php $active = $row['feature']->booted(); ?>
							<tr>
								<td>
									<?php
									/*
									 * An em dash per level of nesting, as WordPress's own
									 * hierarchical list tables indent with, so that the page
									 * needs no stylesheet to read as a tree. It marks the
									 * feature's name, which is the column a reader scans.
									 */
									echo esc_html( str_repeat( "\u{2014} ", $row['depth'] ) );
									echo esc_html( $row['feature']->label() );
									?>
								</td>
								<td>
									<code><?php echo esc_html( $row['feature']->handle() ); ?></code>
								</td>
								<td>
									<?php
									/*
									 * The color is supplementary: the words say which status
									 * this is, so the column still answers for a reader who
									 * cannot tell the two colors apart. Two colors are not
									 * worth a stylesheet and the enqueue logic to keep it off
									 * every other admin screen.
									 */
									?>
									<span style="color: <?php echo esc_attr( $active ? self::ACTIVE_COLOR : self::INACTIVE_COLOR ); ?>;">
										<?php
										echo $active
											? esc_html_x( 'Active', 'feature status', 'wp-seo' )
											: esc_html_x( 'Inactive', 'feature status', 'wp-seo' );
										?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}
