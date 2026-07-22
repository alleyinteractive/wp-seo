<?php
/**
 * WP SEO Tests: Tests for admin-functions.php.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;
use Mantle\Testing\Concerns\Admin_Screen;
use Mantle\Testing\Utils;
use PHPUnit\Framework\Attributes\DataProvider;

class AdminFunctionTest extends TestCase {
	// Loads the WP_Screen/set_current_screen() dependencies and, importantly,
	// backs up and restores $GLOBALS['current_screen'] around each test so the
	// set_current_screen() calls below don't leak admin context into other
	// tests that happen to run afterward in the same process.
	use Admin_Screen;

	/**
	 * Sanity-check that the post_id_to_* and term_data_to_* functions use saved values.
	 */
	#[DataProvider( 'data_post_id_to_and_term_data_to' )]
	function test_admin_functions_contain( $function, $should, $contain, $args, $screen = '' ) {
		// The robots directive functions only render once is_admin() is true and
		// get_current_screen() returns a matching post type or taxonomy screen.
		if ( '' !== $screen ) {
			set_current_screen( $screen );
		}

		// Capture the output of the function.
		self::assertStringContainsString( $contain, Utils::get_echo( $function, $args ), $should );
	}

	/**
	 * Combines the post_id_to_* and term_data_to_* data providers.
	 */
	static function data_post_id_to_and_term_data_to() {
		// Data providers run before the test framework's application/container is
		// bootstrapped, but factory() (used below) depends on it being available.
		new \Mantle\Testkit\Application();

		return array_merge( self::data_post_id_to_functions(), self::data_term_data_to_functions() );
	}

	/**
	 * @return array {
	 *     @type string $function Function name.
	 *     @type string $should Message to describe the expected behavior on failure.
	 *     @type string $contain Value the function output should contain, given $args.
	 *     @type array $args Function arguments (for these functions, a post ID).
	 * }
	 */
	static function data_post_id_to_functions() {
		$meta_title               = rand_str( rand( 32, 64 ) );
		$meta_description         = rand_str( rand( 32, 64 ) );
		$meta_canonical_url       = 'https://example.com/canonical-url';
		$meta_robots_noindex      = 'enable';
		$meta_robots_nofollow     = 'disable';

		$post_id = static::factory()->post->create( [
			'meta_input' => [
				'_meta_title'               => $meta_title,
				'_meta_description'         => $meta_description,
				'_meta_canonical_url'       => $meta_canonical_url,
				'_meta_robots_noindex'      => $meta_robots_noindex,
				'_meta_robots_nofollow'     => $meta_robots_nofollow,
			],
		] );
		do_action( 'admin_init' );

		return [
			[
				'wp_seo_post_id_to_the_meta_title_input',
				'Should print the title value in post meta',
				$meta_title,
				[ $post_id ],
			],
			[
				'wp_seo_post_id_to_the_title_character_count',
				'Should count the title value in post meta',
				(string) strlen( $meta_title ),
				[ $post_id ],
			],
			[
				'wp_seo_post_id_to_the_meta_description_input',
				'Should print the description value in post meta',
				$meta_description,
				[ $post_id ],
			],
			[
				'wp_seo_post_id_to_the_description_character_count',
				'Should count the description value in post meta',
				(string) strlen( $meta_description ),
				[ $post_id ],
			],
			[
				'wp_seo_post_id_to_the_meta_canonical_url_input',
				'Should print the canonical URL value in post meta',
				$meta_canonical_url,
				[ $post_id ],
			],
			[
				'wp_seo_post_id_to_the_meta_robots_input',
				'Should select the enable option when the noindex meta is set to enable',
				sprintf( 'value="%s"  selected=', $meta_robots_noindex ),
				[ $post_id, 'noindex' ],
				'post',
			],
			[
				'wp_seo_post_id_to_the_meta_robots_input',
				'Should select the disable option when the nofollow meta is set to disable',
				sprintf( 'value="%s"  selected=', $meta_robots_nofollow ),
				[ $post_id, 'nofollow' ],
				'post',
			],
		];
	}

	/**
	 * @return array {
	 *     @type string $function Function name.
	 *     @type string $contain Value the function output should contain, given $args.
	 *     @type array $args Function arguments (for these functions, a term's ID and taxonomy).
	 * }
	 */
	static function data_term_data_to_functions() {
		$title               = rand_str( rand( 32, 64 ) );
		$description         = rand_str( rand( 32, 64 ) );
		$canonical_url       = 'https://example.com/canonical-url';
		$robots_noindex      = 'enable';
		$robots_nofollow     = 'disable';

		// intersect_term_option() only keeps a 'robots_{directive}' key for
		// directives configured in settings, so noindex/nofollow must be registered
		// for the term option's flat robots_noindex/robots_nofollow keys to survive.
		update_option( \WP_SEO_Settings::SLUG, [
			'robots_meta_directives' => [
				[ 'value' => 'noindex' ],
				[ 'value' => 'nofollow' ],
			],
		] );

		$term = self::create_and_get_term_with_option( [
			'title'           => $title,
			'description'     => $description,
			'canonical_url'   => $canonical_url,
			'robots_noindex'  => $robots_noindex,
			'robots_nofollow' => $robots_nofollow,
		] );

		return [
			[
				'wp_seo_term_data_to_the_meta_title_input',
				'Should print the title value in the term options',
				$title,
				[ $term->term_id, $term->taxonomy ],
			],
			[
				'wp_seo_term_data_to_the_title_character_count',
				'Should count the title value in the term options',
				(string) strlen( $title ),
				[ $term->term_id, $term->taxonomy ],
			],
			[
				'wp_seo_term_data_to_the_meta_description_input',
				'Should print the description value in the term options',
				$description,
				[ $term->term_id, $term->taxonomy ],
			],
			[
				'wp_seo_term_data_to_the_description_character_count',
				'Should count the description value in the term options',
				(string) strlen( $description ),
				[ $term->term_id, $term->taxonomy ],
			],
			[
				'wp_seo_term_data_to_the_meta_canonical_url_input',
				'Should print the canonical URL value in the term options',
				$canonical_url,
				[ $term->term_id, $term->taxonomy ],
			],
			[
				'wp_seo_term_data_to_the_meta_robots_input',
				'Should select the enable option when the noindex option is set to enable',
				sprintf( 'value="%s"  selected=', $robots_noindex ),
				[ $term->term_id, $term->taxonomy, 'noindex' ],
				'edit-' . $term->taxonomy,
			],
			[
				'wp_seo_term_data_to_the_meta_robots_input',
				'Should select the disable option when the nofollow option is set to disable',
				sprintf( 'value="%s"  selected=', $robots_nofollow ),
				[ $term->term_id, $term->taxonomy, 'nofollow' ],
				'edit-' . $term->taxonomy,
			],
		];
	}
}
