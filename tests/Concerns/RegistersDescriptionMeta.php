<?php
/**
 * RegistersDescriptionMeta trait file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Concerns;

use Alley\WP\WP_SEO\Features\Description_Generator;

/**
 * Makes sure posts and pages store the meta description, as the plugin
 * registers them to when it loads.
 *
 * Earlier tests can reset post types and their meta, so a test that needs the
 * meta registers it rather than relying on the registration from when the
 * plugin loaded. A test case that uses this calls
 * `unregister_description_meta()` in `tearDown()`.
 */
trait RegistersDescriptionMeta {
	/**
	 * Post types a test registered the description meta for, unregistered
	 * again after it.
	 *
	 * @var string[]
	 */
	private array $registered_meta = [];

	/**
	 * Register the description meta for posts and pages, where it is missing.
	 */
	private function register_description_meta(): void {
		foreach ( [ 'post', 'page' ] as $post_type ) {
			if ( ! registered_meta_key_exists( 'post', Description_Generator::META_KEY, $post_type ) ) {
				register_post_meta(
					$post_type,
					Description_Generator::META_KEY,
					[
						'type'   => 'string',
						'single' => true,
					]
				);

				$this->registered_meta[] = $post_type;
			}
		}
	}

	/**
	 * Remove the description meta the test registered.
	 */
	private function unregister_description_meta(): void {
		foreach ( $this->registered_meta as $post_type ) {
			unregister_post_meta( $post_type, Description_Generator::META_KEY );
		}

		$this->registered_meta = [];
	}
}
