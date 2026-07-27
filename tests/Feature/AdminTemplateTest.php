<?php
/**
 * WP SEO Tests: Tests for admin-template.php.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;
use Mantle\Testing\Concerns\Admin_Screen;
use PHPUnit\Framework\Attributes\DataProvider;

class AdminTemplateTest extends TestCase {
	// Loads the WP_Screen/set_current_screen() dependencies and, importantly,
	// backs up and restores $GLOBALS['current_screen'] around each test so the
	// set_current_screen() calls below don't leak admin context into other
	// tests that happen to run afterward in the same process.
	use Admin_Screen;

	/**
	 * Sanity-check admin template tag output.
	 */
	#[DataProvider( 'data_template_tag_output' )]
	function test_template_tag_output( $function, $should, $match, $args, $screen = '' ) {
		// The robots directive input only renders once is_admin() is true and
		// get_current_screen() returns a real WP_Screen.
		if ( '' !== $screen ) {
			set_current_screen( $screen );
		}

		self::expectOutputRegex( $match );
		$function( ...$args );
	}

	/**
	 * @return array {
	 *    @type string $function Function name.
	 *    @type string $should Message to describe the expected behavior on failure.
	 *    @type string $match Regex to test against $function output.
	 *    @type array $args Function arguments.
	 * }
	 */
	static function data_template_tag_output() {
		// Data providers run before the test framework's application/container is
		// bootstrapped, but factory() (used below) depends on it being available.
		new \Mantle\Testkit\Application();

		$str = rand_str();
		$num = rand( 1, 10 );

		return [
			[
				'wp_seo_the_post_meta_fields',
				'Should print a table',
				'#<table[^>]*?>.+?</table>#s',
				[ static::factory()->post->create_and_get() ],
			],
			[
				'wp_seo_the_add_term_meta_fields',
				'Should print a heading',
				'#<h(\d)[^>]*?>.+?</h\1>#',
				[ static::factory()->term->create_and_get(), $str ],
			],
			[
				'wp_seo_the_edit_term_meta_fields',
				'Should print a heading',
				'#<h(\d)[^>]*?>.+?</h\1>#',
				[ static::factory()->term->create_and_get(), $str ],
			],
			[
				'wp_seo_the_edit_term_meta_fields',
				'Should print a table',
				'#<table[^>]*?>.+?</table>#s',
				[ static::factory()->term->create_and_get(), $str ],
			],
			[
				'wp_seo_the_meta_title_label',
				'Should print a label',
				'#<label[^>]*?>.+?</label>#',
				[],
			],
			[
				'wp_seo_the_meta_title_input',
				'Should print an input',
				'#<input[^>]+? />#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_title_input',
				'Should print the passed value',
				'#value=(.)\1#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_title_input',
				'Should print the passed value',
				'#value=(.)' . $str . '\1#',
				[ $str ],
			],
			[
				'wp_seo_the_title_character_count',
				'Should print the passed number',
				"#{$num} \(save changes to update\)#s",
				[ $num ],
			],
			[
				'wp_seo_the_meta_description_label',
				'Should print a label',
				'#<label[^>]*?>.+?</label>#',
				[],
			],
			[
				'wp_seo_the_meta_description_input',
				'Should print an input',
				'#<textarea[^>]*?></textarea>#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_description_input',
				'Should print the passed value',
				"#<textarea[^>]*?>{$str}</textarea>#",
				[ $str ],
			],
			[
				'wp_seo_the_description_character_count',
				'Should print the passed number',
				"#{$num} \(save changes to update\)#s",
				[ $num ],
			],
			[
				'wp_seo_the_meta_canonical_url_label',
				'Should print a label',
				'#<label[^>]*?>.+?</label>#',
				[],
			],
			[
				'wp_seo_the_meta_canonical_url_input',
				'Should print an input',
				'#<input[^>]+? />#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_canonical_url_input',
				'Should print the passed value',
				'#value=(.)\1#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_canonical_url_input',
				'Should print the passed value',
				'#value=(.)' . $str . '\1#',
				[ $str ],
			],
			[
				'wp_seo_the_meta_robots_label',
				'Should print a label',
				'#<label[^>]*?>.+?</label>#s',
				[ 'noindex' ],
			],
			[
				'wp_seo_the_meta_robots_input',
				'Should print a select input',
				'#<select[^>]+?name="seo_meta\[robots_noindex\]"[^>]*?>.*?</select>#s',
				[ '', 'noindex' ],
				'post',
			],
			[
				'wp_seo_the_meta_robots_input',
				'Should mark the enable option as selected',
				'#<option value="enable"\s+selected=#',
				[ 'enable', 'noindex' ],
				'post',
			],
			[
				'wp_seo_the_meta_robots_label',
				'Should print a label',
				'#<label[^>]*?>.+?</label>#s',
				[ 'nofollow' ],
			],
			[
				'wp_seo_the_meta_robots_input',
				'Should print a select input',
				'#<select[^>]+?name="seo_meta\[robots_nofollow\]"[^>]*?>.*?</select>#s',
				[ '', 'nofollow' ],
				'post',
			],
			[
				'wp_seo_the_meta_robots_input',
				'Should mark the disable option as selected',
				'#<option value="disable"\s+selected=#',
				[ 'disable', 'nofollow' ],
				'post',
			],
		];
	}
}
