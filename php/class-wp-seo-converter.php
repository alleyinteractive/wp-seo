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

	abstract public function can_convert();

	abstract public function get_tag_map();

	abstract public function has_tag( $string );

	abstract public function get_static_field_data();

	abstract public function get_static_field_map();

	abstract public function get_arbitrary_field_data();

	abstract public function get_arbitrary_field_map();

	abstract public function get_enabled_post_types( $single_post_types );

	abstract public function get_enabled_taxonomies( $taxonomies );

}