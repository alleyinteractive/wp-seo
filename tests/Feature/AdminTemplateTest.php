<?php
/**
 * WP SEO Tests: Tests for admin-template.php.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;

class AdminTemplateTest extends TestCase {

	/**
	 * Sanity-check admin template tag output.
	 *
	 * @dataProvider data_template_tag_output
	 */
	function test_template_tag_output( $function, $should, $match, $args ) {
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
				'wp_seo_the_meta_robots_noindex_label',
				'Should print a label',
				'#<label[^>]*?>.+?</label>#',
				[],
			],
			[
				'wp_seo_the_meta_robots_noindex_input',
				'Should print a select input',
				'#<select[^>]+?name="seo_meta\[robots_noindex\]"[^>]*?>.*?</select>#s',
				[ '' ],
			],
			[
				'wp_seo_the_meta_robots_noindex_input',
				'Should print the passed value as selected',
				'#<option[^>]+?value=(.)\1[^>]*? selected>#',
				[ '1' ],
			],
			[
				'wp_seo_the_meta_robots_nofollow_label',
				'Should print a label',
				'#<label[^>]*?>.+?</label>#',
				[],
			],
			[
				'wp_seo_the_meta_robots_nofollow_input',
				'Should print a select input',
				'#<select[^>]+?name="seo_meta\[robots_nofollow\]"[^>]*?>.*?</select>#s',
				[ '' ],
			],
			[
				'wp_seo_the_meta_robots_nofollow_input',
				'Should print the passed value as selected',
				'#<option[^>]+?value=(.)\1[^>]*? selected>#',
				[ '1' ],
			],
		];
	}

	/**
	 * Note: there used to be a test_template_tag_hooks() here asserting a raw
	 * count of how many hooks fire per admin template tag context. Removed per
	 * PR #179 review feedback - it's a maintenance burden that has to be
	 * recalculated by hand (and re-derived from a test failure) every time a
	 * hook is added or removed, and the behavior it protects (that hooked
	 * callbacks fire and produce the right output) is already covered more
	 * meaningfully by test_template_tag_output() above, which asserts on the
	 * actual rendered markup those callbacks produce.
	 */
}
