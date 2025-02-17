<?php
/**
 * WP SEO Tests: Tests for admin-functions.php.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;

class AdminFunctionTest extends TestCase {
	/**
	 * Sanity-check that the post_id_to_* and term_data_to_* functions use saved values.
	 *
	 * @dataProvider data_post_id_to_and_term_data_to
	 */
	function test_admin_functions_contain( $function, $should, $contain, $args ) {
		// Capture the output of the function.
		ob_start();
		$function( ...$args );
		$output = ob_get_clean();

		self::assertStringContainsString( $contain, $output, $should );
	}

	/**
	 * Combines the post_id_to_* and term_data_to_* data providers.
	 */
	static function data_post_id_to_and_term_data_to() {
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
		$meta_title       = rand_str( rand( 32, 64 ) );
		$meta_description = rand_str( rand( 32, 64 ) );

		$post_id = static::factory()->post->create( [
			'meta_input' => [
				'_meta_title'       => $meta_title,
				'_meta_description' => $meta_description,
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
		$title       = rand_str( rand( 32, 64 ) );
		$description = rand_str( rand( 32, 64 ) );

		$term = self::create_and_get_term_with_option( [
			'title' => $title,
			'description' => $description,
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
		];
	}
}
