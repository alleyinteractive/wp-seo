<?php
/**
 * A dynamically replaced placeholder for title or meta values.
 *
 * @see  The tags added in wp_seo_default_formatting_tags() for usage examples.
 *
 * @package WP SEO
 */
abstract class WP_SEO_Formatting_Tag {

	/**
	 * The tag name that users add to settings fields.
	 *
	 * @var string.
	 */
	public $tag = null;

	/**
	 * Formatting tags in other SEO libraries that are equivalent to this one.
	 *
	 * @var null|array {
	 *     Optional. Array of any library slugs and equivalent tags.
	 *
	 *     @see WP_SEO_CLI_Command::convert().
	 *
	 *     @type  string|array $from The equivalent tag, or an array of tags,
	 *                               in the other library.
	 * }
	 */
	public $equivalents = null;

	/**
	 * A description of the how the tag operates.
	 *
	 * @return string.
	 */
	abstract public function get_description();

	/**
	 * Generate the value of the tag for the current page.
	 *
	 * @return string.
	 */
	abstract public function get_value();

}