<?php
/**
 * Class file for WP_SEO.
 *
 * @package WP_SEO
 */

/**
 * Sets the page title and renders meta fields.
 */
class WP_SEO extends WP_SEO_Singleton {

	/**
	 * A {@see WP_SEO_Query} instance for the main query on this page.
	 *
	 * @var WP_SEO_Query
	 */
	private $seo_query;

	/**
	 * Initialize properties, add actions and filters.
	 *
	 * @codeCoverageIgnore
	 */
	protected function setup() {
		$this->init_seo_query();
		add_filter( 'wp_title', array( $this, 'filter_wp_title' ), 10, 3 );
		add_action( 'wp_head', array( $this, 'wp_head' ) );
	}

	/**
	 * Filter the text of the page title.
	 *
	 * @param string $title Page title.
	 * @param string $sep Title separator.
	 * @param string $seplocation Location of the separator (left or right).
	 * @return string Page title, possibly updated.
	 */
	public function filter_wp_title( $title, $sep, $seplocation ) {
		$custom = $this->get_page_title();

		if ( $custom ) {
			$title = $custom;
		}

		return $title;
	}

	/**
	 * Fires to print scripts or data in the head tag.
	 */
	public function wp_head() {
		$this->render_meta_description();
		$this->render_meta_keywords();
		$this->render_arbitrary_tags();
	}

	/**
	 * Set the {@see WP_SEO_Query} instance to use for this page.
	 */
	private function init_seo_query() {
		global $wp_the_query;
		$this->seo_query = new WP_SEO_Query( $wp_the_query );
	}

	/**
	 * Get the custom page title for the current page.
	 *
	 * @return string The page title, if any.
	 */
	private function get_page_title() {
		$title = $this->seo_query->get_title();

		/**
		 * Filter the WP SEO page title before replacing formatting tags.
		 *
		 * @todo Restore $key parameter?
		 *
		 * @param string $title The unformatted WP SEO page title.
		 */
		$title = apply_filters( 'wp_seo_title_tag_format', $title );

		$title = $this->format( $title );

		if ( is_wp_error( $title ) ) {
			$title = '';
		}

		return $title;
	}

	/**
	 * Render the meta description for the current page.
	 */
	private function render_meta_description() {
		$description = $this->seo_query->get_description();

		/**
		 * Filter the WP SEO meta description before replacing formatting tags.
		 *
		 * @todo Restore $key?
		 *
		 * @param string $description The unformatted WP SEO meta description.
		 */
		$description = apply_filters( 'wp_seo_meta_description_format', $description );

		$description = $this->format( $description );

		if ( ! $description || is_wp_error( $description ) ) {
			return;
		}

		$this->meta_field( 'description', $description );
	}

	/**
	 * Render the meta keywords for the current page.
	 */
	private function render_meta_keywords() {
		$keywords = $this->seo_query->get_keywords();

		/**
		 * Filter the WP SEO meta keywords before replacing formatting tags.
		 *
		 * @todo Restore $key?
		 *
		 * @param string $keywords The unformatted WP SEO meta keywords.
		 */
		$keywords = apply_filters( 'wp_seo_meta_keywords_format', $keywords );

		$keywords = $this->format( $keywords );

		if ( ! $keywords || is_wp_error( $keywords ) ) {
			return;
		}

		$this->meta_field( 'keywords', $keywords );
	}

	/**
	 * Render the arbitrary meta tags for the current page.
	 */
	private function render_arbitrary_tags() {
		/**
		 * @todo Safe get_option(), no type casting.
		 */
		$arbitrary_tags = (array) WP_SEO_Settings()->get_option( 'arbitrary_tags' );

		/**
		 * Filter the arbitrary meta tags for the current page.
		 *
		 * @param array $arbitrary_tags {
		 *     Arrays of meta tag data.
		 *
		 *     @type array {
		 *         @type string $name Tag "name" attribute.
		 *         @type string $content Unformatted tag "content" attribute.
		 *     }
		 * }
		 */
		$arbitrary_tags = apply_filters( 'wp_seo_arbitrary_tags', $arbitrary_tags );

		foreach ( $arbitrary_tags as $tag ) {
			if ( empty( $tag['name'] ) || empty( $tag['content'] ) ) {
				continue;
			}

			$content = $this->format( $tag['content'] );

			if ( ! $content || is_wp_error( $content ) ) {
				continue;
			}

			$this->meta_field( $tag['name'], $content );
		}
	}

	/**
	 * Render a <meta /> field.
	 *
	 * @param string $name The "name" attribute.
	 * @param string $content The "content" attribute.
	 */
	private function meta_field( $name, $content ) {
		echo "<meta name='" . esc_attr( $name ) . "' content='" . esc_attr( $content ) . "' />\n";
	}

	/**
	 * @return mixed
	 */
	public function __get( $name ) {
		if ( 'formatting_tag_pattern' === $name ) {
			return WP_SEO_Formatter::instance()->get_formatting_tag_pattern();
		}

		if ( 'formatting_tags' === $name ) {
			return WP_SEO_Formatting_Tag_Collection::instance()->get_all();
		}

		return null;
	}

	/**
	 * @deprecated
	 */
	public function set_properties() {
		_deprecated_function( __FUNCTION__, '1.0' );
	}

	/**
	 * @deprecated
	 */
	public function add_meta_boxes( $post_type ) {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Post_Meta_Boxes::add_meta_boxes()' );
		WP_SEO_Post_Meta_Boxes::instance()->add_meta_boxes( $post_type, get_post() );
	}

	/**
	 * @deprecated
	 */
	public function post_meta_fields( $post ) {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Post_Meta_Boxes::do_meta_box()' );
		WP_SEO_Post_Meta_Boxes::instance()->do_meta_box( $post );
	}

	/**
	 * @deprecated
	 */
	public function save_post_fields( $post_id ) {
		_deprecated_function( __FUNCTION__, '1.0' );
	}

	/**
	 * @deprecated
	 */
	public function add_term_boxes() {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Term_Meta_Boxes::instance()' );
		WP_SEO_Term_Meta_Boxes::instance();
	}

	/**
	 * @deprecated
	 */
	public function get_term_option_name( $term ) {
		_deprecated_function( __FUNCTION__, '1.0', 'wp_seo_get_term_option_name()' );
		return wp_seo_get_term_option_name( $term );
	}

	/**
	 * @deprecated
	 */
	public function add_term_meta_fields( $taxonomy ) {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Term_Meta_Boxes::taxonomy_add_form_fields()' );
		WP_SEO_Term_Meta_Boxes::instance()->taxonomy_add_form_fields( $taxonomy );
	}

	/**
	 * @deprecated
	 */
	public function edit_term_meta_fields( $tag, $taxonomy ) {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Term_Meta_Boxes::taxonomy_edit_form()' );
		WP_SEO_Term_Meta_Boxes::instance()->taxonomy_edit_form( $tag, $taxonomy );
	}

	/**
	 * @deprecated
	 */
	public function save_term_fields( $term_id, $term_taxonomy_id, $taxonomy ) {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Term_Meta_Boxes::created_term() or WP_SEO_Term_Meta_Boxes::edited_term()' );
		WP_SEO_Term_Meta_Boxes::instance()->edited_term( $term_id, $term_taxonomy_id, $taxonomy );
	}

	/**
	 * @deprecated
	 */
	public function format( $string ) {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Formatter::format()' );
		WP_SEO_Formatter::instance()->format( $string );
	}

}

function wp_seo() {
	return WP_SEO::instance();
}
add_action( 'get_header', 'wp_seo' );
