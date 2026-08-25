<?php
/**
 * FeaturesPageTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\Features\Group;
use Alley\WP\Types\Feature as Origin;
use Alley\WP\WP_SEO\Feature;
use Alley\WP\WP_SEO\Features_Page;
use Alley\WP\WP_SEO\Registry;
use Alley\WP\WP_SEO\Tests\TestCase;
use Mantle\Testing\Exceptions\WP_Die_Exception;
use PHPUnit\Framework\Attributes\DataProvider;

use function Alley\WP\WP_SEO\main;
use function Mantle\Support\Helpers\capture;

/**
 * Tests for the readonly admin page that reports what the Registry contains.
 *
 * These cover what the page itself decides -- which features it shows, how it
 * arranges them, what it says about them, and who is allowed to see it. That
 * WordPress can hang a menu off `add_menu_page()` is WordPress's business, not
 * ours.
 *
 * @link https://mantle.alley.com/docs/testing
 */
class FeaturesPageTest extends TestCase {
	/**
	 * Start each test with an empty Registry, an empty admin menu, and a user
	 * who can see the page.
	 *
	 * Features register themselves into static state as they are constructed,
	 * and WordPress builds the admin menu into globals it expects to fill once
	 * per request, so both would otherwise leak from one test to the next.
	 */
	protected function setUp(): void {
		parent::setUp();

		Registry::reset_for_tests();

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['menu']    = [];
		$GLOBALS['submenu'] = [];
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Build a feature that does nothing when booted.
	 *
	 * @return Origin
	 */
	private function origin(): Origin {
		return new class() implements Origin {
			/**
			 * Boot the feature.
			 */
			public function boot(): void {}
		};
	}

	/**
	 * Render the page.
	 *
	 * @return string The rendered markup.
	 */
	private function render(): string {
		return capture( fn () => ( new Features_Page() )->render() );
	}

	/**
	 * Render the page and read its table back row by row, so that a test can
	 * say which row it means rather than searching the whole page for a word
	 * that appears in every row.
	 *
	 * Each em dash the page prefixes a label with is read back as one level of
	 * nesting, and dropped from the label. The status is read back with the
	 * color the page painted it, since the color is part of what the cell says.
	 *
	 * @return array<int, array{label: string, handle: string, depth: int, status: string, color: string}> Rows, in the order rendered.
	 */
	private function rendered_table(): array {
		$dom = new \DOMDocument();
		$dom->loadHTML( '<meta charset="utf-8">' . $this->render(), LIBXML_NOERROR | LIBXML_NOWARNING );

		$xpath = new \DOMXPath( $dom );
		$found = $xpath->query( '//tbody/tr' );
		$rows  = [];

		if ( false === $found ) {
			return $rows;
		}

		foreach ( $found as $row ) {
			$cells = $xpath->query( './td', $row );

			if ( false === $cells || 3 > $cells->count() ) {
				continue;
			}

			$label = trim( (string) $cells->item( 0 )?->textContent );
			$depth = 0;

			while ( str_starts_with( $label, "\u{2014}" ) ) {
				++$depth;
				$label = trim( substr( $label, \strlen( "\u{2014}" ) ) );
			}

			$rows[] = [
				'label'  => $label,
				'handle' => trim( (string) $cells->item( 1 )?->textContent ),
				'depth'  => $depth,
				'status' => trim( (string) $cells->item( 2 )?->textContent ),
				'color'  => $this->declared_color( $cells->item( 2 ) ),
			];
		}

		return $rows;
	}

	/**
	 * The color declared within a cell, if anything in it declares one.
	 *
	 * @param \DOMNode|null $cell Table cell.
	 * @return string Color, or an empty string if the cell paints nothing.
	 */
	private function declared_color( ?\DOMNode $cell ): string {
		if ( ! $cell instanceof \DOMElement ) {
			return '';
		}

		$found = ( new \DOMXPath( $cell->ownerDocument ) )->query( './/*[@style]', $cell );

		if ( false === $found || 0 === $found->count() ) {
			return '';
		}

		$style = $found->item( 0 ) instanceof \DOMElement ? $found->item( 0 )->getAttribute( 'style' ) : '';

		return preg_match( '/color:\s*([^;]+)/', $style, $matches ) ? trim( $matches[1] ) : '';
	}

	/**
	 * The features the page listed, without the colors it painted them.
	 *
	 * @return array<int, array{label: string, handle: string, depth: int, status: string}> Rows, in the order rendered.
	 */
	private function rendered_rows(): array {
		return array_map(
			static fn ( array $row ): array => [
				'label'  => $row['label'],
				'handle' => $row['handle'],
				'depth'  => $row['depth'],
				'status' => $row['status'],
			],
			$this->rendered_table()
		);
	}

	/**
	 * The status the page reported for each feature, keyed by handle.
	 *
	 * @return array<string, string> Reported status, keyed by feature handle.
	 */
	private function rendered_statuses(): array {
		return array_column( $this->rendered_table(), 'status', 'handle' );
	}

	/**
	 * The admin menu entry registered for the given slug, if any.
	 *
	 * @param string $slug Menu slug.
	 * @return array<int, mixed>|null Menu entry, or null if nothing registered it.
	 */
	private function menu_entry( string $slug ): ?array {
		foreach ( (array) ( $GLOBALS['menu'] ?? [] ) as $entry ) {
			if ( is_array( $entry ) && isset( $entry[2] ) && $slug === $entry[2] ) {
				return $entry;
			}
		}

		return null;
	}

	/**
	 * The submenu entry registered for the given slug within the WP SEO menu,
	 * if any.
	 *
	 * @param string $slug Submenu slug.
	 * @return array<int, mixed>|null Submenu entry, or null if nothing registered it.
	 */
	private function submenu_entry( string $slug ): ?array {
		foreach ( (array) ( $GLOBALS['submenu'][ Features_Page::SLUG ] ?? [] ) as $entry ) {
			if ( is_array( $entry ) && isset( $entry[2] ) && $slug === $entry[2] ) {
				return $entry;
			}
		}

		return null;
	}

	/**
	 * The page reports the whole Registry: the features that are running and
	 * the ones that are not, since "nothing is enabled" is a normal state for
	 * an opt-in plugin and an administrator is asking about both.
	 */
	public function test_reports_which_registered_features_are_active() {
		add_filter( 'wp_seo_enable_og', '__return_true' );

		( new Group(
			Feature::top_level( 'og', 'Open Graph', $this->origin() ),
			Feature::top_level( 'sitemaps', 'Sitemaps', $this->origin() ),
		) )->boot();

		$this->assertSame(
			[
				'og'       => 'Active',
				'sitemaps' => 'Inactive',
			],
			$this->rendered_statuses()
		);
	}

	/**
	 * A handle is an identifier the page still has to show, since it is what
	 * the enablement filters are named for, but it is not what a person came
	 * to the page reading for. The feature leads with the name it was given.
	 */
	public function test_features_are_listed_by_the_label_they_were_given() {
		Feature::top_level( 'open_graph', 'Open Graph', $this->origin() );

		$this->assertSame(
			[
				[
					'label'  => 'Open Graph',
					'handle' => 'open_graph',
					'depth'  => 0,
					'status' => 'Inactive',
				],
			],
			$this->rendered_rows(),
			'The page should name a feature as its author wrote it, alongside the handle that gates it.'
		);
	}

	/**
	 * Active and Inactive are the answer the page exists to give, so they are
	 * colored to be found at a glance -- in the admin's own success green and
	 * error red, rather than colors of this page's invention.
	 *
	 * The words stay: color reinforces the status and never carries it alone,
	 * so the page still answers for a reader who cannot tell the two apart.
	 */
	public function test_status_is_colored_without_the_color_being_the_only_thing_that_says_it() {
		add_filter( 'wp_seo_enable_og', '__return_true' );

		( new Group(
			Feature::top_level( 'og', 'Open Graph', $this->origin() ),
			Feature::top_level( 'sitemaps', 'Sitemaps', $this->origin() ),
		) )->boot();

		$this->assertSame(
			[
				[
					'status' => 'Active',
					'color'  => '#008a20',
				],
				[
					'status' => 'Inactive',
					'color'  => '#646970',
				],
			],
			array_map(
				static fn ( array $row ): array => [
					'status' => $row['status'],
					'color'  => $row['color'],
				],
				$this->rendered_table()
			)
		);
	}

	/**
	 * A group and the features it holds are one thing to the person reading the
	 * page, so the group comes first and its children follow it, indented --
	 * rather than in the order PHP happened to construct them, which puts
	 * children above the group they belong to.
	 */
	public function test_features_are_listed_under_the_group_that_holds_them() {
		( new Group(
			Feature::group(
				'titles',
				'Titles',
				Feature::nested( 'post_titles', 'Post Titles', $this->origin() ),
				Feature::nested( 'term_titles', 'Term Titles', $this->origin() ),
			),
			Feature::top_level( 'sitemaps', 'Sitemaps', $this->origin() ),
		) )->boot();

		$this->assertSame(
			[
				[
					'label'  => 'Titles',
					'handle' => 'titles',
					'depth'  => 0,
					'status' => 'Inactive',
				],
				[
					'label'  => 'Post Titles',
					'handle' => 'post_titles',
					'depth'  => 1,
					'status' => 'Inactive',
				],
				[
					'label'  => 'Term Titles',
					'handle' => 'term_titles',
					'depth'  => 1,
					'status' => 'Inactive',
				],
				[
					'label'  => 'Sitemaps',
					'handle' => 'sitemaps',
					'depth'  => 0,
					'status' => 'Inactive',
				],
			],
			$this->rendered_rows(),
			'A group should be listed before the features it holds, which should be indented beneath it.'
		);
	}

	/**
	 * A feature is Inactive when the group around it never ran, whatever its
	 * own filters answered, so the page has to say why: it explains what
	 * nesting means as soon as it shows any.
	 */
	public function test_explains_nesting_only_when_there_is_nesting_to_explain() {
		Feature::group( 'titles', 'Titles', Feature::nested( 'post_titles', 'Post Titles', $this->origin() ) );

		$this->assertStringContainsString( 'only when the group holding them is', $this->render() );

		Registry::reset_for_tests();
		Feature::top_level( 'sitemaps', 'Sitemaps', $this->origin() );

		$this->assertStringNotContainsString( 'only when the group holding them is', $this->render() );
	}

	/**
	 * Nothing being enabled is the normal state of an opt-in plugin, and is not
	 * the same thing as nothing being registered. A site with features it has
	 * not turned on must still see them listed.
	 */
	public function test_features_that_never_booted_are_not_reported_as_an_empty_registry() {
		Feature::top_level( 'og', 'Open Graph', $this->origin() );

		$this->assertStringNotContainsString(
			'No WP SEO features are registered',
			$this->render(),
			'A registry full of disabled features is not an empty registry.'
		);
	}

	/**
	 * An empty registry is the expected state until features are migrated to
	 * the wrapper, so it has to read as an answer rather than as a page that
	 * failed to load.
	 */
	public function test_empty_registry_says_so_instead_of_rendering_an_empty_table() {
		$rendered = $this->render();

		$this->assertStringContainsString( 'No WP SEO features are registered', $rendered );
		$this->assertStringNotContainsString( '<table', $rendered );
	}

	/**
	 * The page reports how the site is configured, so it is not rendered to
	 * someone who could not be trusted with the settings behind it.
	 */
	public function test_page_is_not_rendered_to_a_user_without_the_capability() {
		Feature::top_level( 'og', 'Open Graph', $this->origin() );

		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );

		$this->expectException( WP_Die_Exception::class );

		// Nothing is echoed before the capability check, so this needs no buffer.
		( new Features_Page() )->render();
	}

	/**
	 * A site that wants someone other than an administrator to read the page
	 * can say so, and the page it renders is the real one.
	 */
	public function test_capability_is_filterable() {
		Feature::top_level( 'og', 'Open Graph', $this->origin() );

		add_filter( 'wp_seo_features_page_capability', fn (): string => 'edit_posts' );

		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );

		$this->assertSame( [ 'og' => 'Inactive' ], $this->rendered_statuses() );
	}

	/**
	 * The menu is what actually keeps unauthorized users out, so it has to be
	 * built from the same filtered capability the page checks, rather than from
	 * the literal default.
	 */
	public function test_menu_and_submenu_are_registered_with_the_filtered_capability() {
		add_filter( 'wp_seo_features_page_capability', fn (): string => 'edit_posts' );

		( new Features_Page() )->add_pages();

		$this->assertSame( 'edit_posts', $this->menu_entry( Features_Page::SLUG )[1] ?? null );
		$this->assertSame(
			'edit_posts',
			$this->submenu_entry( Features_Page::SLUG )[1] ?? null,
			'The page is registered as a submenu of its own menu, so that a second page can be added later without WordPress naming this one after the menu.'
		);
	}

	/**
	 * The capability is answered once and reused, so that the menu and the page
	 * it leads to cannot disagree about who may see it -- `admin_menu` builds
	 * the menu, and a filter added afterwards would otherwise apply to only one
	 * of the two.
	 */
	public function test_capability_is_answered_once_per_request() {
		$page     = new Features_Page();
		$original = $page->capability();

		add_filter( 'wp_seo_features_page_capability', fn (): string => 'edit_posts' );

		$this->assertSame( $original, $page->capability() );
	}

	/**
	 * Capabilities a filter might return that cannot name one.
	 *
	 * @return array<string, array{mixed}>
	 */
	public static function data_unusable_capabilities(): array {
		return [
			'null'         => [ null ],
			'empty string' => [ '' ],
			'an array'     => [ [ 'manage_options' ] ],
			'a boolean'    => [ true ],
		];
	}

	/**
	 * A filter that answers with something that is not a capability leaves the
	 * default in place.
	 *
	 * The page registers itself on `admin_menu`, so an unusable answer that
	 * reached WordPress would take down every admin screen, including the one
	 * for deactivating the plugin that caused it.
	 *
	 * @param mixed $capability What the filter returns.
	 */
	#[DataProvider( 'data_unusable_capabilities' )]
	public function test_a_filter_that_does_not_answer_with_a_capability_is_ignored( mixed $capability ) {
		add_filter( 'wp_seo_features_page_capability', fn (): mixed => $capability );

		$this->assertSame( 'manage_options', ( new Features_Page() )->capability() );
	}

	/**
	 * The page reports what the plugin is doing, so it is registered whether or
	 * not the plugin is doing anything: a site that enabled no features is
	 * exactly the site whose administrator needs to see the list.
	 *
	 * @link docs/adr/0007-the-features-page-is-not-itself-a-feature.md
	 */
	public function test_page_is_registered_when_every_feature_is_disabled() {
		add_filter( 'wp_seo_enable_feature', '__return_false' );

		main();

		do_action( 'admin_menu' );

		$this->assertNotNull(
			$this->menu_entry( Features_Page::SLUG ),
			'The features page is not a feature, so no enablement filter can take it away.'
		);
	}
}
