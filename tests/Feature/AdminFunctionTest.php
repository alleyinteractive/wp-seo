<?php
/**
 * WP SEO Tests: Tests for admin-functions.php.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;
use function Mantle\Support\Helpers\capture;

class AdminFunctionTest extends TestCase {
	/**
	 * Sanity-check that the post_id_to_* and term_data_to_* functions use saved values.
	 *
	 * @dataProvider data_post_id_to_and_term_data_to
	 */
	public function test_admin_functions_contain( callable $function, string $should, string $contain, callable $setup ) {
		$args = $setup();
		do_action( 'admin_init' );
		self::assertStringContainsString( $contain, capture( fn() => $function( ...$args ) ), $should );
	}

	/**
	 * Combines the post_id_to_* and term_data_to_* data providers.
	 */
	static function data_post_id_to_and_term_data_to() {
		return array_merge( self::data_post_id_to_functions(), self::data_term_data_to_functions() );
	}

	/**
	 * @return array {
	 *     @type string   $function Function name.
	 *     @type string   $should   Message to describe the expected behavior on failure.
	 *     @type string   $contain  Value the function output should contain, given $args.
	 *     @type callable $setup    Closure that creates the post and returns the args array.
	 * }
	 */
	static function data_post_id_to_functions() {
		$meta_title           = rand_str( rand( 32, 64 ) );
		$meta_description     = rand_str( rand( 32, 64 ) );
		$meta_canonical_url   = 'https://example.com/canonical-url';
		$meta_robots_noindex  = '1';
		$meta_robots_nofollow = '';

		$setup = static function() use ( $meta_title, $meta_description, $meta_canonical_url, $meta_robots_noindex, $meta_robots_nofollow ) {
			$post_id = static::factory()->post->create( [
				'meta_input' => [
					'_meta_title'           => $meta_title,
					'_meta_description'     => $meta_description,
					'_meta_canonical_url'   => $meta_canonical_url,
					'_meta_robots_noindex'  => $meta_robots_noindex,
					'_meta_robots_nofollow' => $meta_robots_nofollow,
				],
			] );
			return [ $post_id ];
		};

		return [
			[
				'wp_seo_post_id_to_the_meta_title_input',
				'Should print the title value in post meta',
				$meta_title,
				$setup,
			],
			[
				'wp_seo_post_id_to_the_title_character_count',
				'Should count the title value in post meta',
				(string) strlen( $meta_title ),
				$setup,
			],
			[
				'wp_seo_post_id_to_the_meta_description_input',
				'Should print the description value in post meta',
				$meta_description,
				$setup,
			],
			[
				'wp_seo_post_id_to_the_description_character_count',
				'Should count the description value in post meta',
				(string) strlen( $meta_description ),
				$setup,
			],
			[
				'wp_seo_post_id_to_the_meta_canonical_url_input',
				'Should print the canonical URL value in post meta',
				$meta_canonical_url,
				$setup,
			],
			[
				'wp_seo_post_id_to_the_meta_robots_noindex_input',
				'Should check the noindex checkbox when the noindex meta is set',
				$meta_robots_noindex,
				$setup,
			],
			[
				'wp_seo_post_id_to_the_meta_robots_nofollow_input',
				'Should not check the nofollow checkbox when the nofollow meta is not set',
				$meta_robots_nofollow,
				$setup,
			],
		];
	}

	/**
	 * @return array {
	 *     @type string   $function Function name.
	 *     @type string   $contain  Value the function output should contain, given $args.
	 *     @type callable $setup    Closure that creates the term and returns the args array.
	 * }
	 */
	static function data_term_data_to_functions() {
		$title           = rand_str( rand( 32, 64 ) );
		$description     = rand_str( rand( 32, 64 ) );
		$canonical_url   = 'https://example.com/canonical-url';
		$robots_noindex  = '1';
		$robots_nofollow = '';

		$setup = static function() use ( $title, $description, $canonical_url, $robots_noindex, $robots_nofollow ) {
			$term = static::create_and_get_term_with_option( [
				'title'         => $title,
				'description'   => $description,
				'canonical_url' => $canonical_url,
				'robots'        => [
					'noindex'  => $robots_noindex,
					'nofollow' => $robots_nofollow,
				],
			] );
			return [ $term->term_id, $term->taxonomy ];
		};

		return [
			[
				'wp_seo_term_data_to_the_meta_title_input',
				'Should print the title value in the term options',
				$title,
				$setup,
			],
			[
				'wp_seo_term_data_to_the_title_character_count',
				'Should count the title value in the term options',
				(string) strlen( $title ),
				$setup,
			],
			[
				'wp_seo_term_data_to_the_meta_description_input',
				'Should print the description value in the term options',
				$description,
				$setup,
			],
			[
				'wp_seo_term_data_to_the_description_character_count',
				'Should count the description value in the term options',
				(string) strlen( $description ),
				$setup,
			],
			[
				'wp_seo_term_data_to_the_meta_canonical_url_input',
				'Should print the canonical URL value in the term options',
				$canonical_url,
				$setup,
			],
			[
				'wp_seo_term_data_to_the_meta_robots_noindex_input',
				'Should check the noindex checkbox when the noindex option is set',
				$robots_noindex,
				$setup,
			],
			[
				'wp_seo_term_data_to_the_meta_robots_nofollow_input',
				'Should not check the nofollow checkbox when the nofollow option is not set',
				$robots_nofollow,
				$setup,
			],
		];
	}
}
