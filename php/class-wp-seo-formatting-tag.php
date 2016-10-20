<?php
/**
 * Class file for WP_SEO_Formatting_Tag.
 *
 * @package WP_SEO
 */

/**
 * A dynamically replaced placeholder for title or meta values.
 *
 * @see The tags added in wp_seo_default_formatting_tags() for usage examples.
 */
abstract class WP_SEO_Formatting_Tag {

	/**
	 * The tag name that users add to settings fields.
	 *
	 * @var string.
	 */
	public $tag = null;

	/**
	 * A description of the how the tag operates.
	 *
	 * @return string.
	 */
	abstract public function get_description();

	/**
	 * Generate the value of the tag for the current page.
	 *
	 * @return mixed Usually a string, or false if no value exists.
	 */
	abstract public function get_value();

}
