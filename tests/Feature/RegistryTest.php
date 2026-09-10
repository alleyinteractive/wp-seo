<?php
/**
 * RegistryTest class file
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

/**
 * Tests for the Registry, the enumerable collection of every WP SEO Feature.
 *
 * @link https://mantle.alley.com/docs/testing
 */
class RegistryTest extends TestCase {
	/**
	 * Start each test with an empty Registry, since Features register
	 * themselves into static state as they are constructed.
	 */
	protected function setUp(): void {
		parent::setUp();

		Registry::reset_for_tests();
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
	 * Features land in the registry as soon as they are constructed -- before
	 * anything boots -- however deeply they are nested.
	 */
	public function test_features_register_themselves_on_construction() {
		$child  = Feature::nested( 'child', $this->origin() );
		$parent = Feature::top_level( 'parent', new Group( $child ) );

		$this->assertSame(
			[
				'child'  => $child,
				'parent' => $parent,
			],
			Registry::features(),
			'Both the group and its child should be registered, keyed by handle.'
		);
	}

	/**
	 * The registry answers which of the features that exist actually booted.
	 */
	public function test_registry_reports_which_features_booted() {
		add_filter( 'wp_seo_enable_sitemaps', '__return_true' );

		$og       = Feature::top_level( 'og', $this->origin() );
		$sitemaps = Feature::top_level( 'sitemaps', $this->origin() );

		$og->boot();
		$sitemaps->boot();

		$this->assertSame(
			[
				'og'       => false,
				'sitemaps' => true,
			],
			array_map( static fn ( Feature $feature ): bool => $feature->booted(), Registry::features() )
		);
	}

	/**
	 * The feature that claimed the handle keeps it, so a stray duplicate cannot
	 * displace the real feature in the registry.
	 */
	#[Expected_Incorrect_Usage( 'Alley\WP\WP_SEO\Registry::register' )]
	public function test_the_first_feature_to_claim_a_handle_keeps_it() {
		$first = Feature::top_level( 'og', $this->origin() );
		Feature::top_level( 'og', $this->origin() );

		$this->assertSame( [ 'og' => $first ], Registry::features() );
	}
}
