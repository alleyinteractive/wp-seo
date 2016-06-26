<?php
/**
 * Tests for admin-template.php.
 *
 * @package WP_SEO
 */

class WP_SEO_Admin_Template_Tests extends WP_UnitTestCase {
	/**
	 * Sanity-check admin template tag output.
	 *
	 * @dataProvider data_template_tag_output
	 */
	function test_template_tag_output( $function, $should, $match, $args ) {
		$this->assertRegExp( $match, get_echo( $function, $args ), $should );
	}

	/**
	 * @return array {
	 *    @type string $function Function name.
	 *    @type string $should Message to describe the expected behavior on failure.
	 *    @type string $match Regex to test against $function output.
	 *    @type array $args Function arguments.
	 * }
	 */
	function data_template_tag_output() {
		$str = rand_str();
		$num = rand( 1, 10 );

		return array(
			array(
				'wp_seo_the_post_meta_fields',
				'Should print a table',
				'#<table[^>]*?>.+?</table>#s',
				array( $this->factory->post->create_and_get() ),
			),
			array(
				'wp_seo_the_add_term_meta_fields',
				'Should print a heading',
				'#<h(\d)[^>]*?>.+?</h\1>#',
				array( $this->factory->term->create_and_get(), $str ),
			),
			array(
				'wp_seo_the_edit_term_meta_fields',
				'Should print a heading',
				'#<h(\d)[^>]*?>.+?</h\1>#',
				array( $this->factory->term->create_and_get(), $str ),
			),
			array(
				'wp_seo_the_edit_term_meta_fields',
				'Should print a table',
				'#<table[^>]*?>.+?</table>#s',
				array( $this->factory->term->create_and_get(), $str ),
			),
			array(
				'wp_seo_the_meta_title_label',
				'Should print a label',
				'#<label[^>]*?>.+?</label>#',
				array(),
			),
			array(
				'wp_seo_the_meta_title_input',
				'Should print an input',
				'#<input[^>]+? />#',
				array( '' ),
			),
			array(
				'wp_seo_the_meta_title_input',
				'Should print the passed value',
				'#value=(.)\1#',
				array( '' ),
			),
			array(
				'wp_seo_the_meta_title_input',
				'Should print the passed value',
				'#value=(.)' . $str . '\1#',
				array( $str ),
			),
			array(
				'wp_seo_the_title_character_count',
				'Should print the passed number',
				"#{$num} \(save changes to update\)#s",
				array( $num ),
			),
			array(
				'wp_seo_the_meta_description_label',
				'Should print a label',
				'#<label[^>]*?>.+?</label>#',
				array(),
			),
			array(
				'wp_seo_the_meta_description_input',
				'Should print an input',
				'#<textarea[^>]*?></textarea>#',
				array( '' ),
			),
			array(
				'wp_seo_the_meta_description_input',
				'Should print the passed value',
				"#<textarea[^>]*?>{$str}</textarea>#",
				array( $str ),
			),
			array(
				'wp_seo_the_description_character_count',
				'Should print the passed number',
				"#{$num} \(save changes to update\)#s",
				array( $num ),
			),
			array(
				'wp_seo_the_meta_keywords_label',
				'Should print a label',
				'#<label[^>]*?>.+?</label>#',
				array(),
			),
			array(
				'wp_seo_the_meta_keywords_input',
				'Should print an input',
				'#<textarea[^>]*?></textarea>#',
				array( '' ),
			),
			array(
				'wp_seo_the_meta_keywords_input',
				'Should print the passed value',
				"#<textarea[^>]*?>{$str}</textarea>#",
				array( $str ),
			),
		);
	}

	/**
	 * Sanity-check the number of WP SEO hook calls in admin template tags.
	 *
	 * @dataProvider data_template_tag_hooks
	 */
	function test_template_tag_hooks( $function, $fires, $matching, $args ) {
		$ma = new MockAction();
		add_action( 'all', array( $ma, 'action' ) );
		get_echo( $function, $args );
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
	function data_template_tag_hooks() {
		return array(
			array(
				'wp_seo_the_post_meta_fields',
				8,
				'/^wp_seo_post_meta_fields/',
				array( $this->factory->post->create_and_get() ),
			),
			array(
				'wp_seo_the_add_term_meta_fields',
				8,
				'/^wp_seo_add_term_meta_fields/',
				array( $this->factory->term->create_and_get(), rand_str() ),
			),
			array(
				'wp_seo_the_edit_term_meta_fields',
				8,
				'/^wp_seo_edit_term_meta_fields/',
				array( $this->factory->term->create_and_get(), rand_str() ),
			),
		);
	}
}
