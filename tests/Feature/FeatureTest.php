<?php
/**
 * FeatureTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\Features\Group;
use Alley\WP\Types\Feature as Origin;
use Alley\WP\WP_SEO\Feature;
use Alley\WP\WP_SEO\Registry;
use Alley\WP\WP_SEO\Tests\TestCase;
use Mantle\Testing\Attributes\Expected_Incorrect_Usage;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the Feature wrapper, which gates a Feature behind the enablement
 * filters named for its handle.
 *
 * @link https://mantle.alley.com/docs/testing
 */
class FeatureTest extends TestCase {
	/**
	 * Start each test with an empty Registry, since Features register
	 * themselves into static state as they are constructed.
	 */
	protected function setUp(): void {
		parent::setUp();

		Registry::reset_for_tests();
	}

	/**
	 * Build a Feature that records how many times it was booted.
	 *
	 * @return Origin
	 */
	private function boot_spy(): Origin {
		return new class() implements Origin {
			/**
			 * Times the feature was booted.
			 *
			 * @var int
			 */
			public int $boots = 0;

			/**
			 * Boot the feature.
			 */
			public function boot(): void {
				++$this->boots;
			}
		};
	}

	/**
	 * A feature carries the name a person reads it by, alongside the handle a
	 * filter addresses it by.
	 *
	 * The name is written rather than derived from the handle: deriving reads
	 * well enough for `open_graph`, and then renders a later
	 * `title_description_tags` as "Title Description Tags" instead of the
	 * "Title & Description Tags" someone would have written. Labels are
	 * user-facing and translated, so they are authored at the call site.
	 */
	public function test_a_feature_reports_the_label_it_was_given() {
		$this->assertSame( 'Open Graph', Feature::top_level( 'open_graph', 'Open Graph', $this->boot_spy() )->label() );
		$this->assertSame( 'Post Titles', Feature::nested( 'post_titles', 'Post Titles', $this->boot_spy() )->label() );
		$this->assertSame(
			'Title & Description Tags',
			Feature::group( 'titles', 'Title & Description Tags', Feature::nested( 'term_titles', 'Term Titles', $this->boot_spy() ) )->label()
		);
	}

	/**
	 * A feature nobody enabled stays off.
	 */
	public function test_feature_is_disabled_by_default() {
		$spy     = $this->boot_spy();
		$feature = Feature::top_level( 'og', 'Open Graph', $spy );

		$feature->boot();

		$this->assertSame( 0, $spy->boots );
		$this->assertFalse( $feature->booted() );
	}

	/**
	 * A nested feature boots without any filter at all, since enabling the
	 * group around it is the request to run it.
	 */
	public function test_nested_feature_boots_without_filters() {
		$spy     = $this->boot_spy();
		$feature = Feature::nested( 'og', 'Open Graph', $spy );

		$feature->boot();

		$this->assertSame( 1, $spy->boots );
		$this->assertTrue( $feature->booted() );
	}

	/**
	 * The global filter receives the handle, so it can enable one feature
	 * without enabling every feature.
	 */
	public function test_global_filter_enables_feature_by_handle() {
		$enabled = $this->boot_spy();
		$other   = $this->boot_spy();

		add_filter(
			'wp_seo_enable_feature',
			fn ( $value, $handle ) => 'og' === $handle ? true : $value,
			10,
			2
		);

		Feature::top_level( 'og', 'Open Graph', $enabled )->boot();
		Feature::top_level( 'sitemaps', 'Sitemaps', $other )->boot();

		$this->assertSame( 1, $enabled->boots );
		$this->assertSame( 0, $other->boots );
	}

	/**
	 * The per-handle filter alone is enough to enable a feature.
	 *
	 * The filter is added after the feature is constructed, since gating reads
	 * the filters when the feature boots rather than when it is built.
	 */
	public function test_per_handle_filter_enables_feature() {
		$spy     = $this->boot_spy();
		$feature = Feature::top_level( 'og', 'Open Graph', $spy );

		add_filter( 'wp_seo_enable_og', '__return_true' );

		$feature->boot();

		$this->assertSame( 1, $spy->boots, 'The feature should boot as soon as it is told to, without waiting for a hook.' );
		$this->assertTrue( $feature->booted() );
	}

	/**
	 * The per-handle filter runs last: it is passed whatever the global filter
	 * decided, and it has the final word.
	 */
	public function test_per_handle_filter_runs_after_and_overrides_global_filter() {
		$received = null;

		add_filter( 'wp_seo_enable_feature', '__return_true' );
		add_filter(
			'wp_seo_enable_og',
			function ( $value ) use ( &$received ) {
				$received = $value;

				return false;
			}
		);

		$spy     = $this->boot_spy();
		$feature = Feature::top_level( 'og', 'Open Graph', $spy );

		$feature->boot();

		$this->assertTrue( $received, 'The per-handle filter should be passed the global filter\'s result.' );
		$this->assertSame( 0, $spy->boots );
		$this->assertFalse( $feature->booted() );
	}

	/**
	 * Disabling a group's handle short-circuits the whole subtree, whatever the
	 * children's own filters say, and the children report that they did not boot.
	 */
	public function test_disabled_group_prevents_children_from_booting() {
		add_filter( 'wp_seo_enable_child', '__return_true' );

		$spy   = $this->boot_spy();
		$child = Feature::nested( 'child', 'Child', $spy );
		$group = Feature::top_level( 'group', 'Group', new Group( $child ) );

		$group->boot();

		$this->assertSame( 0, $spy->boots );
		$this->assertFalse( $group->booted() );
		$this->assertFalse( $child->booted(), 'A child of a disabled group did not boot, whatever its own filter returns.' );
	}

	/**
	 * An enabled group boots the children nested inside it.
	 */
	public function test_enabled_group_boots_its_children() {
		add_filter( 'wp_seo_enable_group', '__return_true' );

		$spy   = $this->boot_spy();
		$child = Feature::nested( 'child', 'Child', $spy );
		$group = Feature::top_level( 'group', 'Group', new Group( $child ) );

		$group->boot();

		$this->assertSame( 1, $spy->boots );
		$this->assertTrue( $group->booted() );
		$this->assertTrue( $child->booted() );
	}

	/**
	 * A child inside an enabled group can still be turned off on its own.
	 */
	public function test_child_can_be_disabled_within_an_enabled_group() {
		add_filter( 'wp_seo_enable_group', '__return_true' );
		add_filter( 'wp_seo_enable_child', '__return_false' );

		$spy   = $this->boot_spy();
		$child = Feature::nested( 'child', 'Child', $spy );
		$group = Feature::top_level( 'group', 'Group', new Group( $child ) );

		$group->boot();

		$this->assertSame( 0, $spy->boots );
		$this->assertTrue( $group->booted() );
		$this->assertFalse( $child->booted() );
	}

	/**
	 * A group is the switch for everything inside it: its own handle decides
	 * whether the subtree runs, exactly as when the group was composed by hand.
	 */
	public function test_group_gates_the_subtree_it_wraps() {
		add_filter( 'wp_seo_enable_on_group', '__return_true' );

		$off_spy = $this->boot_spy();
		$on_spy  = $this->boot_spy();

		$off = Feature::group( 'off_group', 'Off Group', Feature::nested( 'off_child', 'Off Child', $off_spy ) );
		$on  = Feature::group( 'on_group', 'On Group', Feature::nested( 'on_child', 'On Child', $on_spy ) );

		$off->boot();
		$on->boot();

		$this->assertSame( 0, $off_spy->boots, 'A group nobody enabled runs nothing inside it.' );
		$this->assertSame( 1, $on_spy->boots, 'Enabling a group is the request to run what it holds.' );
	}

	/**
	 * The group records what it was handed, so that the registry can report the
	 * tree that the call site describes.
	 */
	public function test_a_group_owns_the_children_it_is_given() {
		$child   = Feature::nested( 'child', 'Child', $this->boot_spy() );
		$sibling = Feature::nested( 'sibling', 'Sibling', $this->boot_spy() );
		$group   = Feature::group( 'group', 'Group', $child, $sibling );

		$this->assertSame( 'group', $child->parent() );
		$this->assertSame( 'group', $sibling->parent() );
		$this->assertNull( $group->parent(), 'A group that no other group holds belongs to nothing.' );
	}

	/**
	 * A group that lost its handle is not in the registry, so nothing could list
	 * its children underneath it. They report themselves as belonging to no
	 * group, which is what they are.
	 */
	#[Expected_Incorrect_Usage( 'Alley\WP\WP_SEO\Registry::register' )]
	public function test_a_group_that_did_not_claim_its_handle_claims_no_children() {
		Feature::top_level( 'taken', 'Taken', $this->boot_spy() );

		$child = Feature::nested( 'child', 'Child', $this->boot_spy() );

		Feature::group( 'taken', 'Taken', $child );

		$this->assertNull( $child->parent() );
	}

	/**
	 * Handles that cannot name a usable filter.
	 *
	 * @return array<string, array{string}>
	 */
	public static function data_invalid_handles(): array {
		return [
			'the global gate'      => [ 'feature' ],
			'empty'                => [ '' ],
			'uppercase letters'    => [ 'Open_Graph' ],
			'dashes'               => [ 'open-graph' ],
			'spaces'               => [ 'open graph' ],
			'namespace brackets'   => [ 'og[]' ],
			'digits only'          => [ '123' ],
			'a leading digit'      => [ '2nd_feature' ],
			'a leading underscore' => [ '_og' ],
		];
	}

	/**
	 * A handle is interpolated into the name of the filters that enable a
	 * feature, so one that cannot make a usable filter name is refused
	 * outright: the feature is neither registered nor booted, rather than
	 * running behavior nothing can address.
	 *
	 * `feature` is reserved because it would name the same filter as the global
	 * `wp_seo_enable_feature` gate, which passes its callbacks a second
	 * argument that the per-handle call does not. A handle must also begin with
	 * a letter: PHP turns an all-digit array key into an integer, so a numeric
	 * handle would key the registry by something that is not a handle.
	 *
	 * @param string $handle Handle to refuse.
	 */
	#[DataProvider( 'data_invalid_handles' )]
	#[Expected_Incorrect_Usage( 'Alley\WP\WP_SEO\Feature::__construct' )]
	public function test_a_feature_with_an_invalid_handle_is_refused( string $handle ) {
		// Would enable every feature, whatever its handle.
		add_filter( 'wp_seo_enable_feature', '__return_true' );

		$spy     = $this->boot_spy();
		$feature = Feature::top_level( $handle, 'A Feature', $spy );

		$feature->boot();

		$this->assertSame( [], Registry::features(), 'A feature with an unusable handle should not reach the registry.' );
		$this->assertSame( 0, $spy->boots );
		$this->assertFalse( $feature->booted() );
	}

	/**
	 * A feature that lost its handle to one registered before it does not boot.
	 *
	 * The registry would report only the feature that claimed the handle, so
	 * booting the loser anyway would run behavior that nothing reports on and
	 * no filter can turn off.
	 */
	#[Expected_Incorrect_Usage( 'Alley\WP\WP_SEO\Registry::register' )]
	public function test_a_feature_that_did_not_claim_its_handle_does_not_boot() {
		add_filter( 'wp_seo_enable_og', '__return_true' );

		$claimed  = $this->boot_spy();
		$rejected = $this->boot_spy();

		$first  = Feature::top_level( 'og', 'Open Graph', $claimed );
		$second = Feature::top_level( 'og', 'Open Graph', $rejected );

		$first->boot();
		$second->boot();

		$this->assertSame( 1, $claimed->boots );
		$this->assertSame( 0, $rejected->boots );
		$this->assertTrue( $first->booted() );
		$this->assertFalse( $second->booted() );
	}

	/**
	 * One instance reachable from more than one place in a composed tree boots
	 * the feature it wraps once.
	 */
	public function test_booting_a_feature_twice_boots_its_origin_once() {
		add_filter( 'wp_seo_enable_og', '__return_true' );

		$spy     = $this->boot_spy();
		$feature = Feature::top_level( 'og', 'Open Graph', $spy );

		( new Group( $feature, $feature ) )->boot();

		$this->assertSame( 1, $spy->boots );
		$this->assertTrue( $feature->booted() );
	}
}
