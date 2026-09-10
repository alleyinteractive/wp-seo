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
	 * A feature nobody enabled stays off.
	 */
	public function test_feature_is_disabled_by_default() {
		$spy     = $this->boot_spy();
		$feature = Feature::top_level( 'og', $spy );

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
		$feature = Feature::nested( 'og', $spy );

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

		Feature::top_level( 'og', $enabled )->boot();
		Feature::top_level( 'sitemaps', $other )->boot();

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
		$feature = Feature::top_level( 'og', $spy );

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
		$feature = Feature::top_level( 'og', $spy );

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
		$child = Feature::nested( 'child', $spy );
		$group = Feature::top_level( 'group', new Group( $child ) );

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
		$child = Feature::nested( 'child', $spy );
		$group = Feature::top_level( 'group', new Group( $child ) );

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
		$child = Feature::nested( 'child', $spy );
		$group = Feature::top_level( 'group', new Group( $child ) );

		$group->boot();

		$this->assertSame( 0, $spy->boots );
		$this->assertTrue( $group->booted() );
		$this->assertFalse( $child->booted() );
	}

	/**
	 * Handles that cannot name a usable filter.
	 *
	 * @return array<string, array{string}>
	 */
	public static function data_invalid_handles(): array {
		return [
			'the global gate'    => [ 'feature' ],
			'empty'              => [ '' ],
			'uppercase letters'  => [ 'Open_Graph' ],
			'dashes'             => [ 'open-graph' ],
			'spaces'             => [ 'open graph' ],
			'namespace brackets' => [ 'og[]' ],
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
	 * argument that the per-handle call does not.
	 *
	 * @param string $handle Handle to refuse.
	 */
	#[DataProvider( 'data_invalid_handles' )]
	#[Expected_Incorrect_Usage( 'Alley\WP\WP_SEO\Feature::__construct' )]
	public function test_a_feature_with_an_invalid_handle_is_refused( string $handle ) {
		// Would enable every feature, whatever its handle.
		add_filter( 'wp_seo_enable_feature', '__return_true' );

		$spy     = $this->boot_spy();
		$feature = Feature::top_level( $handle, $spy );

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

		$first  = Feature::top_level( 'og', $claimed );
		$second = Feature::top_level( 'og', $rejected );

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
		$feature = Feature::top_level( 'og', $spy );

		( new Group( $feature, $feature ) )->boot();

		$this->assertSame( 1, $spy->boots );
		$this->assertTrue( $feature->booted() );
	}
}
