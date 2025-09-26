<?php
/**
 * WP SEO Tests: Tests for admin-template.php.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;
use Mantle\Testing\Mock_Action;

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
				'Should print a checkbox input',
				'#<input[^>]+?type=(.)checkbox\1[^>]+? />#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_robots_noindex_input',
				'Should not check the checkbox when the passed value is empty',
				'#<input[^>]+?type=(.)checkbox\1(?![^>]+?checked)[^>]+? />#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_robots_noindex_input',
				'Should check the checkbox when the passed value is non-empty',
				'#<input[^>]+?type=(.)checkbox\1[^>]+?checked[^>]+? />#',
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
				'Should print a checkbox input',
				'#<input[^>]+?type=(.)checkbox\1[^>]+? />#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_robots_nofollow_input',
				'Should not check the checkbox when the passed value is empty',
				'#<input[^>]+?type=(.)checkbox\1(?![^>]+?checked)[^>]+? />#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_robots_nofollow_input',
				'Should check the checkbox when the passed value is non-empty',
				'#<input[^>]+?type=(.)checkbox\1[^>]+?checked[^>]+? />#',
				[ '1' ],
			],
			[
				'wp_seo_the_meta_robots_noarchive_label',
				'Should print a label',
				'#<label[^>]*?>.+?</label>#',
				[],
			],
			[
				'wp_seo_the_meta_robots_noarchive_input',
				'Should print a checkbox input',
				'#<input[^>]+?type=(.)checkbox\1[^>]+? />#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_robots_noarchive_input',
				'Should not check the checkbox when the passed value is empty',
				'#<input[^>]+?type=(.)checkbox\1(?![^>]+?checked)[^>]+? />#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_robots_noarchive_input',
				'Should check the checkbox when the passed value is non-empty',
				'#<input[^>]+?type=(.)checkbox\1[^>]+?checked[^>]+? />#',
				[ '1' ],
			],
			[
				'wp_seo_the_meta_robots_nosnippet_label',
				'Should print a label',
				'#<label[^>]*?>.+?</label>#',
				[],
			],
			[
				'wp_seo_the_meta_robots_nosnippet_input',
				'Should print a checkbox input',
				'#<input[^>]+?type=(.)checkbox\1[^>]+? />#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_robots_nosnippet_input',
				'Should not check the checkbox when the passed value is empty',
				'#<input[^>]+?type=(.)checkbox\1(?![^>]+?checked)[^>]+? />#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_robots_nosnippet_input',
				'Should check the checkbox when the passed value is non-empty',
				'#<input[^>]+?type=(.)checkbox\1[^>]+?checked[^>]+? />#',
				[ '1' ],
			],
			[
				'wp_seo_the_meta_robots_noimageindex_label',
				'Should print a label',
				'#<label[^>]*?>.+?</label>#',
				[],
			],
			[
				'wp_seo_the_meta_robots_noimageindex_input',
				'Should print a checkbox input',
				'#<input[^>]+?type=(.)checkbox\1[^>]+? />#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_robots_noimageindex_input',
				'Should not check the checkbox when the passed value is empty',
				'#<input[^>]+?type=(.)checkbox\1(?![^>]+?checked)[^>]+? />#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_robots_noimageindex_input',
				'Should check the checkbox when the passed value is non-empty',
				'#<input[^>]+?type=(.)checkbox\1[^>]+?checked[^>]+? />#',
				[ '1' ],
			],
			[
				'wp_seo_the_meta_robots_notranslate_label',
				'Should print a label',
				'#<label[^>]*?>.+?</label>#',
				[],
			],
			[
				'wp_seo_the_meta_robots_notranslate_input',
				'Should print a checkbox input',
				'#<input[^>]+?type=(.)checkbox\1[^>]+? />#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_robots_notranslate_input',
				'Should not check the checkbox when the passed value is empty',
				'#<input[^>]+?type=(.)checkbox\1(?![^>]+?checked)[^>]+? />#',
				[ '' ],
			],
			[
				'wp_seo_the_meta_robots_notranslate_input',
				'Should check the checkbox when the passed value is non-empty',
				'#<input[^>]+?type=(.)checkbox\1[^>]+?checked[^>]+? />#',
				[ '1' ],
			],
		];
	}

	/**
	 * Sanity-check the number of WP SEO hook calls in admin template tags.
	 *
	 * @dataProvider data_template_tag_hooks
	 */
	function test_template_tag_hooks( $function, $fires, $matching, $args ) {
		$ma = new Mock_Action();
		add_action( 'all', [ $ma, 'action' ] );

		$function( ...$args );

		$this->assertSame( $fires, count( preg_grep( $matching, $ma->get_tags() ) ) );
	}

	/**
	 * @return array {
	 *     @type string $function Function name.
	 *     @type int $fires Expected number of hook calls.
	 *     @type string $matching Regex of hook names to look for.
	 *     @type array $args Function arguments.
	 * }
	 */
	static function data_template_tag_hooks() {
		return [
			[
				'wp_seo_the_post_meta_fields',
				6,
				'/^wp_seo_post_meta_fields/',
				[ static::factory()->post->create_and_get() ],
			],
			[
				'wp_seo_the_add_term_meta_fields',
				6,
				'/^wp_seo_add_term_meta_fields/',
				[ static::factory()->term->create_and_get(), rand_str() ],
			],
			[
				'wp_seo_the_edit_term_meta_fields',
				6,
				'/^wp_seo_edit_term_meta_fields/',
				[ static::factory()->term->create_and_get(), rand_str() ],
			],
		];
	}
}
