<?php
/**
 * Class file for WP_SEO_Administration.
 *
 * @package WP_SEO
 */

/**
 * Base class for creating plugin management interfaces.
 */
class WP_SEO_Administration extends WP_SEO_Singleton {
	/**
	 * The capability required to access settings.
	 *
	 * @var string
	 */
	protected $options_capability = 'manage_options';

	/**
	 * Set up.
	 */
	protected function setup() {
		/**
		 * Filter the capability required to access the settings manager.
		 *
		 * @param string The default capability.
		 */
		$this->options_capability = apply_filters( 'wp_seo_options_capability', $this->options_capability );
	}

	/**
	 * Get the capability for accessing the settings manager.
	 *
	 * @return string The capability.
	 */
	public function get_options_capability() {
		return $this->options_capability;
	}

	/**
	 * Get an example URL for a post permalink.
	 *
	 * @param string $post_type Name of the post type for which to get a permalink.
	 * @return string The post permalink.
	 */
	protected function get_example_post_permalink( $post_type ) {
		$query = new WP_Query( array(
			'fields' => 'ids',
			'no_found_rows' => true,
			'post_type' => $post_type,
			'posts_per_page' => 1,
		) );

		if ( ! $query->have_posts() ) {
			return false;
		}

		return get_permalink( $query->posts[0] );
	}

	/**
	 * Get an example URL for a term archive.
	 *
	 * @param string $taxonomy Name of the taxonomy whose archive link is retrieved.
	 * @return string The link to the archive.
	 */
	protected function get_example_taxonomy_archive_link( $taxonomy ) {
		$terms = get_terms( $taxonomy, array(
			'hierarchical' => false,
			'number' => 1,
			'update_term_meta_cache' => false,
		) );

		if ( ! $terms ) {
			return false;
		}

		return get_term_link( $terms[0] );
	}

	/**
	 * Get an example URL for a post type archive.
	 *
	 * @param string $post_type Name of a post type whose archive link is retrieved.
	 * @return string The link to the archive.
	 */
	protected function get_example_post_type_archive_link( $post_type ) {
		return get_post_type_archive_link( $post_type );
	}


	/**
	 * Get an example URL for a date archive.
	 *
	 * @return string The permalink to the current month's archives.
	 */
	protected function get_example_date_archive_link() {
		return get_month_link( false, false );
	}

	/**
	 * Get an example URL for an author archive.
	 *
	 * @return string The permalink to the current user's archives.
	 */
	protected function get_example_author_archive_link() {
		return get_author_posts_url( get_current_user_id() );
	}

	/**
	 * Get an example URL for a search page.
	 *
	 * @return string A link to search results.
	 */
	protected function get_example_search_page_link() {
		return get_search_link( 'Alley Interactive' );
	}

	/**
	 * Get an example URL for a 404 page.
	 *
	 * @return string A URL we can only assume actually doesn't exist.
	 */
	protected function get_example_404_page_link() {
		return untrailingslashit( get_bloginfo( 'url' ) ) . '/404/';
	}
}
