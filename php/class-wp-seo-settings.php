<?php
/**
 * Class file for WP_SEO_Settings.
 *
 * @package WP_SEO
 */

/**
 * Gateway to plugin option values.
 */
class WP_SEO_Settings extends WP_SEO_Singleton {
	/**
	 * Key for the option name and other IDs.
	 */
	const SLUG = 'wp-seo';

	/**
	 * Get an option value from the top level of the array.
	 *
	 * @param string $key The option key sought.
	 * @param mixed $default Optional default.
	 * @return mixed The value, or $default if the requested key is missing.
	 */
	public function get_option( $key, $default = null ) {
		$option = get_option( $this::SLUG, $this->get_default_options() );
		return isset( $option[ $key ] ) ? $option[ $key ] : $default;
	}

	/**
	 * Sanitize and validate submitted options.
	 *
	 * @param array $in The options as submitted.
	 * @return array The options, sanitized.
	 */
	public function sanitize_options( $in ) {
		$out = $this->default_options;

		// Validate post types on which to show SEO fields.
		if ( ! isset( $in['post_types'] ) || ! is_array( $in['post_types'] ) ) {
			$in['post_types'] = array();
		}
		$out['post_types'] = array_filter( $in['post_types'], 'post_type_exists' );

		// Validate taxonomies on which to show SEO fields.
		if ( ! isset( $in['taxonomies'] ) || ! is_array( $in['taxonomies'] ) ) {
			$in['taxonomies'] = array();
		}
		$out['taxonomies'] = array_filter( $in['taxonomies'], 'taxonomy_exists' );

		/**
		 * Keys to sanitize as text fields.
		 *
		 * @var array
		 */
		$sanitize_as_text_field = array();

		// Home description and keywords.
		$sanitize_as_text_field[] = 'home_title';
		$sanitize_as_text_field[] = 'home_description';
		$sanitize_as_text_field[] = 'home_keywords';

		// Single post types.
		foreach ( $this->get_available_single_post_types() as $post_type ) {
			$sanitize_as_text_field[] = "single_{$post_type}_title";
			$sanitize_as_text_field[] = "single_{$post_type}_description";
			$sanitize_as_text_field[] = "single_{$post_type}_keywords";
		}

		// Archived post types.
		foreach ( $this->get_available_archive_post_types() as $post_type ) {
			$sanitize_as_text_field[] = "archive_{$post_type}_title";
			$sanitize_as_text_field[] = "archive_{$post_type}_description";
			$sanitize_as_text_field[] = "archive_{$post_type}_keywords";
		}

		// Taxonomy archives.
		foreach ( $this->get_available_taxonomies() as $taxonomy ) {
			$sanitize_as_text_field[] = "archive_{$taxonomy}_title";
			$sanitize_as_text_field[] = "archive_{$taxonomy}_description";
			$sanitize_as_text_field[] = "archive_{$taxonomy}_keywords";
		}

		// Author and date archives.
		foreach ( array( 'author', 'date' ) as $type ) {
			$sanitize_as_text_field[] = "archive_{$type}_title";
			$sanitize_as_text_field[] = "archive_{$type}_description";
			$sanitize_as_text_field[] = "archive_{$type}_keywords";
		}

		// Search and 404 titles.
		$sanitize_as_text_field[] = 'search_title';
		$sanitize_as_text_field[] = '404_title';

		// Finally, sanitize.
		foreach ( $sanitize_as_text_field as $field ) {
			if ( ! isset( $in[ $field ] ) || ! is_string( $in[ $field ] ) ) {
				$in[ $field ] = '';
			}

			$out[ $field ] = sanitize_text_field( $in[ $field ] );
		}

		// Sanitize arbitrary tags, also as text fields.

		if ( ! isset( $in['arbitrary_tags'] ) || ! is_array( $in['arbitrary_tags'] ) ) {
			$in['arbitrary_tags'] = array();
		}

		$out['arbitrary_tags'] = array();

		foreach ( $in['arbitrary_tags'] as $group ) {
			// Skip if a field is missing.
			if ( ! isset( $group['name'] ) || ! isset( $group['content'] ) ) {
				continue;
			}

			$name = sanitize_text_field( $group['name'] );
			$content = sanitize_text_field( $group['content'] );

			// Skip if both fields are, or were sanitized into being, empty.
			if ( empty( $name ) && empty( $content ) ) {
				continue;
			}

			$out['arbitrary_tags'][] = array(
				'name' => $name,
				'content' => $content,
			);
		}

		return $out;
	}

	/**
	 * Get the post types with per-post fields enabled.
	 *
	 * @return array Array of names of any enabled post types.
	 */
	public function get_enabled_post_types() {
		$post_types = $this->get_option( 'post_types', $this->get_available_single_post_types() );

		// Remove post types that are saved but not enabled.
		return array_filter( $post_types, array( $this, 'is_available_single_post_type' ) );
	}

	/**
	 * Get the taxonomies with per-term fields enabled.
	 *
	 * @return array Array of names of any enabled taxonomies.
	 */
	public function get_enabled_taxonomies() {
		$taxonomies = $this->get_option( 'taxonomies', $this->get_available_taxonomies() );

		// Remove taxonomies that are saved but not enabled.
		return array_filter( $taxonomies, array( $this, 'is_available_taxonomy' ) );
	}

	/**
	 * Helper to check whether a post type is set in "Add fields to individual."
	 *
	 * @param string $post_type Post type name.
	 * @return bool
	 */
	public function has_post_fields( $post_type ) {
		return in_array( $post_type, $this->get_enabled_post_types(), true );
	}

	/**
	 * Helper to check whether a taxonomy is set in "Add fields to individual."
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return bool
	 */
	public function has_term_fields( $taxonomy ) {
		return in_array( $taxonomy, $this->get_enabled_taxonomies(), true );
	}

	/**
	 * Get the post types for which SEO values can be set on single posts.
	 *
	 * @return array Array of post type names. Default is public post types with labels.
	 */
	public function get_available_single_post_types() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		// Remove post types without labels.
		wp_list_filter( $post_types, array( 'label' => false ), 'NOT' );

		if ( has_filter( 'wp_seo_single_post_types' ) ) {
			/**
			 * Filter the post type objects.
			 *
			 * @deprecated Use 'wp_seo_available_single_post_types'.
			 *
			 * @param array $post_types Post type objects.
			 */
			$post_types = apply_filters( 'wp_seo_single_post_types', $post_types );
		}

		$post_types = array_keys( $post_types );

		/**
		 * Filter the post types that support setting SEO values on single posts.
		 *
		 * @param array Post type names.
		 */
		return apply_filters( 'wp_seo_available_single_post_types', $post_types );
	}

	/**
	 * Helper to check whether a post type supports setting SEO values on single posts.
	 *
	 * @param string $post_type Post type name.
	 * @return bool
	 */
	public function is_available_single_post_type( $post_type ) {
		return in_array( $post_type, $this->get_available_single_post_types(), true );
	}

	/**
	 * Get the post types for which SEO values can be set on post type archives.
	 *
	 * @return array Array of post type names. Default is post types with archives.
	 */
	public function get_available_archive_post_types() {
		$post_types = get_post_types( array( 'has_archive' => true ), 'objects' );

		// Remove post types without labels.
		wp_list_filter( $post_types, array( 'label' => false ), 'NOT' );

		if ( has_filter( 'wp_seo_archived_post_types' ) ) {
			/**
			 * Filter the post type objects.
			 *
			 * @deprecated Use 'wp_seo_available_archive_post_types'.
			 *
			 * @param array $post_types Post type objects.
			 */
			$post_types = apply_filters( 'wp_seo_archived_post_types', $post_types );
		}

		$post_types = array_keys( $post_types );

		/**
		 * Filter the post types that support setting SEO values on archives.
		 *
		 * @param array Post type names.
		 */
		return apply_filters( 'wp_seo_available_archive_post_types', $post_types );
	}

	/**
	 * Get the taxonomies for which SEO values can be set on term archives.
	 *
	 * @return array Array of taxonomy names. Default is public taxonomies.
	 */
	public function get_available_taxonomies() {
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );

		// Remove taxonomies without labels.
		wp_list_filter( $taxonomies, array( 'label' => false ), 'NOT' );

		if ( has_filter( 'wp_seo_taxonomies' ) ) {
			/**
			 * Filter the taxonomy objects.
			 *
			 * @deprecated Use 'wp_seo_available_taxonomies'.
			 *
			 * @param array $taxonomies Taxonomy objects.
			 */
			$taxonomies = apply_filters( 'wp_seo_taxonomies', $taxonomies );
		}

		// Remove Post Formats, which have labels but no UI (by default).
		if ( isset( $taxonomies['post_format'] ) ) {
			unset( $taxonomies['post_format'] );
		}

		$taxonomies = array_keys( $taxonomies );

		/**
		 * Filter the taxonomies that support setting SEO values on archives.
		 *
		 * @param array Taxonomy names.
		 */
		return apply_filters( 'wp_seo_available_taxonomies', $taxonomies );
	}

	/**
	 * Helper to check whether a taxonomy supports setting SEO values on term archives.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return bool
	 */
	public function is_available_taxonomy( $taxonomy ) {
		return in_array( $taxonomy, $this->get_available_taxonomies(), true );
	}

	/**
	 * Get the default plugin options.
	 *
	 * Used when, for example, no option value exists in the database.
	 *
	 * @return array Array of options.
	 */
	private function get_default_options() {
		$defaults = array(
			'post_types' => $this->get_single_post_types(),
			'taxonomies' => $this->get_taxonomies(),
		);

		/**
		 * Filter the default plugin options.
		 *
		 * @param array $default_options Associative array of setting names and values.
		 */
		return apply_filters( 'wp_seo_default_options', $defaults );
	}

	/**
	 * @return mixed
	 */
	public function __get( $name ) {
		if ( 'options_capability' === $name ) {
			return WP_SEO_Administration::instance()->get_options_capability();
		}

		if ( 'options' === $name ) {
			return get_option( this::SLUG, $this->get_default_options() );
		}

		if ( 'default_options' === $name ) {
			return $this->get_default_options();
		}

		return null;
	}

	/**
	 * @deprecated
	 */
	public function get_single_post_types() {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Settings::get_available_single_post_types()' );
		return array_map( 'get_post_type_object', $this->get_available_single_post_types() );
	}

	/**
	 * @deprecated
	 */
	public function get_archived_post_types() {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Settings::get_available_archive_post_types()' );
		return array_map( 'get_post_type_object', $this->get_available_archive_post_types() );
	}

	/**
	 * @deprecated
	 */
	public function get_taxonomies() {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Settings::get_available_taxonomies()' );
		return array_map( 'get_taxonomy', $this->get_available_taxonomies() );
	}

	/**
	 * @deprecated
	 *
	 * These are now set dynamically or have __get() compatibility.
	 */
	public function set_properties() {
		_deprecated_function( __FUNCTION__, '1.0' );
	}

	/**
	 * @deprecated
	 *
	 * @see __get().
	 */
	public function set_options() {
		_deprecated_function( __FUNCTION__, '1.0' );
	}

	/**
	 * @deprecated
	 */
	public function add_options_page() {
		_deprecated_function( __FUNCTION__, '1.0' );
	}

	/**
	 * @deprecated
	 */
	public function add_help_tab() {
		_deprecated_function( __FUNCTION__, '1.0' );
	}

	/**
	 * @deprecated
	 */
	public function register_settings() {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Settings_Page::load_settings_page()' );
		WP_SEO_Settings_Page::instance()->load_settings_page();
	}

	/**
	 * @deprecated
	 */
	public function field( $args ) {
		_deprecated_function( __FUNCTION__, '1.0' );
	}

	/**
	 * @deprecated
	 */
	public function render_text_field( $args, $value ) {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Settings_Page::do_text_field()' );
		WP_SEO_Settings::instance()->do_text_field( '[' . $args['field'] . ']', $args['field'], $value );
	}

	/**
	 * @deprecated
	 */
	public function render_textarea( $args, $value ) {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Settings_Page::do_textarea()' );
		WP_SEO_Settings::instance()->do_textarea( '[' . $args['field'] . ']', $args['field'], $value );
	}

	/**
	 * @deprecated
	 */
	public function render_checkboxes( $args, $values ) {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Settings_Page::do_checkboxes_for_objects()' );
		WP_SEO_Settings::instance()->do_checkboxes_for_objects(
			'[' . $args['field'] . ']',
			$values,
			$args['boxes']
		);
	}

	/**
	 * @deprecated
	 */
	public function render_repeatable_field( $args, $values ) {
		if ( 'arbitrary_tags' !== $args['field'] ) {
			_deprecated_function( __FUNCTION__, '1.0' );
			return;
		}

		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Settings_Page::do_arbitrary_tags_field()' );
		WP_SEO_Settings_Page::instance()->do_arbitrary_tags_field( array(
			'value' => $values,
		) );
	}

	/**
	 * @deprecated
	 */
	public function view_settings_page() {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Settings_Page::do_settings_page()' );
		WP_SEO_Settings_Page::instance()->do_settings_page();
	}

	/**
	 * @deprecated
	 */
	public function settings_meta_box( $object, $box ) {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Settings_Page::do_meta_box()' );
		WP_SEO_Settings_Page::instance()->do_meta_box( $object, $box );
	}

	/**
	 * @deprecated
	 */
	public function view_formatting_tags_help_tab() {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Settings_Page::do_help_tab()' );
		WP_SEO_Settings_Page::instance()->do_help_tab();
	}

	/**
	 * @deprecated
	 */
	public function ex_text() {
		_deprecated_function( __FUNCTION__, '1.0' );
	}

	/**
	 * @deprecated
	 */
	public function example_url( $text, $url = false ) {
		_deprecated_function( __FUNCTION__, '1.0' );
	}

	/**
	 * @deprecated
	 */
	public function example_permalink( $section ) {
		_deprecated_function( __FUNCTION__, '1.0' );
	}

	/**
	 * @deprecated
	 */
	public function example_term_archive( $section ) {
		_deprecated_function( __FUNCTION__, '1.0' );
	}

	/**
	 * @deprecated
	 */
	public function example_post_type_archive( $section ) {
		_deprecated_function( __FUNCTION__, '1.0' );
	}

	/**
	 * @deprecated
	 */
	public function example_date_archive() {
		_deprecated_function( __FUNCTION__, '1.0' );
	}

	/**
	 * @deprecated
	 */
	public function example_author_archive() {
		_deprecated_function( __FUNCTION__, '1.0' );
	}

	/**
	 * @deprecated
	 */
	public function example_search_page() {
		_deprecated_function( __FUNCTION__, '1.0' );
	}

	/**
	 * @deprecated
	 */
	public function example_404_page() {
		_deprecated_function( __FUNCTION__, '1.0' );
	}
}
