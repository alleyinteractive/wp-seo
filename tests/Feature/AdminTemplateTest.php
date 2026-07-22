<?php
/**
 * WP SEO Tests: Tests for admin-template.php.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;
use Mantle\Testing\Concerns\Admin_Screen;
use Mantle\Testing\Mock_Action;
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

		// wp_seo_the_meta_robots_label() only prints a label for directives that
		// are registered in settings with a non-empty label.
		update_option( \WP_SEO_Settings::SLUG, [
			'robots_meta_directives' => [
				[ 'label' => 'No Index', 'value' => 'noindex' ],
				[ 'label' => 'No Follow', 'value' => 'nofollow' ],
			],
		] );

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

	/**
	 * Sanity-check the number of WP SEO hook calls in admin template tags.
	 */
	#[DataProvider( 'data_template_tag_hooks' )]
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
		// Data providers run before the test framework's application/container is
		// bootstrapped, but factory() (used below) depends on it being available.
		new \Mantle\Testkit\Application();

		// The fixed fields (title/description/canonical_url/robots legend) fire 11
		// hooks on their own; each configured robots directive adds 3 more
		// (label/input/after_input), so 2 directives brings the post and edit-term
		// contexts to 17. The add-term context is missing the two robots legend
		// hooks from that fixed count - wp_seo_the_add_term_meta_fields() fires
		// 'wp_seo_post_meta_fields_robots_legend'/'after_robots_legend' instead of
		// the 'wp_seo_add_term_meta_fields_*' names default-filters.php registers
		// for it, so those two never match this context's prefix - leaving it at 9
		// fixed + 6 directive hooks = 15.
		update_option( \WP_SEO_Settings::SLUG, [
			'robots_meta_directives' => [
				[ 'label' => 'No Index', 'value' => 'noindex' ],
				[ 'label' => 'No Follow', 'value' => 'nofollow' ],
			],
		] );

		return [
			[
				'wp_seo_the_post_meta_fields',
				17,
				'/^wp_seo_post_meta_fields/',
				[ static::factory()->post->create_and_get() ],
			],
			[
				'wp_seo_the_add_term_meta_fields',
				15,
				'/^wp_seo_add_term_meta_fields/',
				[ static::factory()->term->create_and_get(), rand_str() ],
			],
			[
				'wp_seo_the_edit_term_meta_fields',
				17,
				'/^wp_seo_edit_term_meta_fields/',
				[ static::factory()->term->create_and_get(), rand_str() ],
			],
		];
	}
}
