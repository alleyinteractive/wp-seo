<?php
/**
 * Gets the WP SEO values for a WP_Query.
 *
 * @package WP_SEO
 */
class WP_SEO_Query {

	/**
	 * The {@see WP_Query} to get WP SEO values for.
	 *
	 * @var WP_Query
	 */
	private $wp_query;

	/**
	 * The prefix of the options array key with WP SEO values for the $wp_query.
	 *
	 * Includes the trailing underscore. e.g., `home_`.
	 *
	 * @var string
	 */
	private $key_prefix = '';

	/**
	 * The unformatted WP SEO title for the $wp_query.
	 *
	 * @var string
	 */
	private $title = '';

	/**
	 * The unformatted WP SEO description for the $wp_query.
	 *
	 * @var string
	 */
	private $description = '';

	/**
	 * The unformatted WP SEO keywords for the $wp_query.
	 *
	 * @var string
	 */
	private $keywords = '';

	/**
	 * Initialize the class.
	 *
	 * @param WP_Query $wp_query @see WP_SEO_Query::wp_query.
	 */
	function __construct( $wp_query ) {
		if ( ! ( $wp_query instanceof WP_Query ) ) {
			return;
		}

		$this->wp_query = $wp_query;
		$this->init_key_prefix();
		$this->init_values();
	}

	/**
	 * Set $key_prefix based on this instance's $wp_query.
	 */
	private function init_key_prefix() {
		switch ( true ) {
			case $this->wp_query->is_singular() :
				$post_type = get_post_type( $this->wp_query->get_queried_object() );
				$this->key_prefix = "single_{$post_type}_";
			break;

			case $this->wp_query->is_front_page() :
				$this->key_prefix = 'home_';
			break;

			case $this->wp_query->is_author() :
				$this->key_prefix = 'archive_author_';
			break;

			case $this->wp_query->is_category() || $this->wp_query->is_tag() || $this->wp_query->is_tax() :
				$taxonomy = $this->wp_query->get_queried_object()->taxonomy;
				$this->key_prefix = "archive_{$taxonomy}_";
			break;

			case $this->wp_query->is_post_type_archive() :
				$post_type = $this->wp_query->get_queried_object()->name;
				$this->key_prefix = "archive_{$post_type}_";
			break;

			case $this->wp_query->is_date() :
				$this->key_prefix = 'archive_date_';
			break;

			case $this->wp_query->is_search() :
				$this->key_prefix = 'search_';
			break;

			case $this->wp_query->is_404() :
				$this->key_prefix = '404_';
			break;
		}
	}

	/**
	 * Set $title, $keywords, and $description based on the $wp_query and $key_prefix.
	 */
	private function init_values() {
		if ( $this->wp_query->is_singular() ) {
			$post_type = get_post_type( $this->wp_query->get_queried_object() );

			if ( wp_seo_settings()->has_post_fields( $post_type ) ) {
				$post_id = $this->wp_query->get_queried_object_id();

				$meta_title = get_post_meta( $post_id, '_meta_title', true );
				if ( $meta_title ) {
					$this->title = $meta_title;
				}

				$meta_description = get_post_meta( $post_id, '_meta_description', true );
				if ( $meta_description ) {
					$this->description = $meta_description;
				}

				$meta_keywords = get_post_meta( $post_id, '_meta_keywords', true );
				if ( $meta_keywords ) {
					$this->keywords = $meta_keywords;
				}
			}
		} elseif ( $this->wp_query->is_category() || $this->wp_query->is_tag() || $this->wp_query->is_tax() ) {
			$option = get_option( wp_seo_get_term_option_name( $this->wp_query->get_queried_object() ) );

			if ( ! empty( $option['title'] ) ) {
				$this->title = $option['title'];
			}

			if ( ! empty( $option['description'] ) ) {
				$this->description = $option['description'];
			}

			if ( ! empty( $option['keywords'] ) ) {
				$this->keywords = $option['keywords'];
			}
		}

		if ( $this->key_prefix ) {
			if ( ! $this->title ) {
				$this->title = wp_seo_settings()->get_option( $this->key_prefix . 'title' );
			}

			if ( ! $this->description ) {
				$this->description = wp_seo_settings()->get_option( $this->key_prefix . 'description' );
			}

			if ( ! $this->keywords ) {
				$this->keywords = wp_seo_settings()->get_option( $this->key_prefix . 'keywords' );
			}
		}
	}

	/**
	 * @return bool
	 */
	public function has_title() {
		return ! empty( $this->title );
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return $this->title;
	}

	/**
	 * @return bool
	 */
	public function has_description() {
		return ! empty( $this->description );
	}

	/**
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * @return bool
	 */
	public function has_keywords() {
		return ! empty( $this->keywords );
	}

	/**
	 * @return string
	 */
	public function get_keywords() {
		return $this->keywords;
	}

}
