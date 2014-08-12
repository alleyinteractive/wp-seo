<?php
/**
 *
 *
 * @see  The converters added in wp_seo_bundled_converters() for usage examples.
 *
 * @package WP SEO
 */
abstract class WP_SEO_Converter {

	/**
	 * A display label for this converter.
	 *
	 * @var string
	 */
	public $label = null;

	/**
	 * A self-test for whether this converter has enough data to proceed.
	 *
	 * For example, you could use it to check whether an option you need exists.
	 *
	 * @return bool|WP_Error True to proceed, or a WP_Error. Use the
	 *     "can_convert" code to provide an error message.
	 */
	abstract public function can_convert();

	/**
	 * Get a map of the formatting tags the library uses and their WP SEO equivalents.
	 *
	 * @return array With keys for each of the library's formatting tags and
	 *     values for the equivalents.
	 */
	abstract public function get_tag_map();

	/**
	 * Does a string contain any of this library's formatting tags?
	 *
	 * @param  string $string The string to test.
	 * @return bool
	 */
	abstract public function has_tag( $string );

	/**
	 * Get data from the library as WP SEO "static" settings.
	 *
	 * @return array Associative array of WP SEO setting keys and values.
	 */
	abstract public function get_static_fields();

	/**
	 * Get data from the library as WP SEO arbitrary tags.
	 *
	 * @return array {
	 *     Numeric array of tags.
	 *
	 *     @type  array {
	 *         Data about an arbitrary tag.
	 *
	 *         @type  string $name    The "name" attribute of the <meta> tag.
	 *         @type  string $content The "content" attribute of the <meta> tag.
	 *     }
	 * }
	 */
	abstract public function get_arbitrary_tags();

	/**
	 * Get the post types that should have per-entry SEO fields enabled.
	 *
	 * @param  array $single_post_types Names of post types that can have
	 *     per-entry fields in WP SEO. @see WP_SEO_Settings::single_post_types.
	 * @return array Names of enabled post types.
	 */
	abstract public function get_enabled_post_types( $single_post_types );

	/**
	 * Get the taxonomies that should have per-term SEO fields enabled.
	 *
	 * @param  array $taxonomies Names of taxonomies that can have per-term
	 *     fields in WP SEO. @see WP_SEO_Settings::taxonomies.
	 * @return array Names of enabled taxonomies.
	 */
	abstract public function get_enabled_taxonomies( $taxonomies );

}