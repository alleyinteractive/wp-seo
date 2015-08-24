<?php
/**
 * Sets the page title and renders meta fields.
 *
 * @package WP_SEO
 */
class WP_SEO {

	/**
	 * Instance of this class.
	 *
	 * @var WP_SEO
	 */
	private static $instance = null;

	/**
	 * Associative array of {@see WP_SEO_Formatting_Tag} IDs and instances.
	 *
	 * @var array.
	 */
	public $formatting_tags = array();

	/**
	 * The regular expression used to find formatting tags in a string.
	 *
	 * @var string.
	 */
	public $formatting_tag_pattern = '';

	/**
	 * @codeCoverageIgnore
	 */
	private function __construct() {
		/* Don't do anything, needs to be initialized via instance() method */
	}

	/**
	 * Return an instance of this class.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return WP_SEO
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
			self::$instance->setup();
		}

		return self::$instance;
	}

	/**
	 * Initialize properties, add actions and filters.
	 *
	 * @codeCoverageIgnore
	 */
	protected function setup() {
		$this->init_formatting_tags();
		$this->init_formatting_tag_pattern();
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
	 * Replace formatting tags in a string with their value for the current page.
	 *
	 * @param  string $string  The string with formatting tags.
	 * @return string|WP_Error The formatted string, or WP_Error on error.
	 */
	public function format( $string ) {
		if ( ! is_string( $string ) ) {
			return new WP_Error( 'format_error', __( "Please don't try to format() a non-string.", 'wp-seo' ) );
		}

		$raw_string = $string;

		preg_match_all( $this->formatting_tag_pattern, $string, $matches );
		if ( empty( $matches[0] ) ) {
			return $string;
		}

		$replacements = array();
		$unique_matches = array_unique( $matches[0] );

		foreach ( $this->formatting_tags as $id => $tag ) {
			if ( ! empty( $tag->tag ) && in_array( $tag->tag, $unique_matches ) ) {
				/**
				 * Filter the value of a formatting tag for the current page.
				 *
				 * The dynamic portion of the hook name, `$id`, refers to the
				 * key used to register the formatting tag. For example, the
				 * hook for the default "#site_name#" formatting tag is
				 * 'wp_seo_format_site_name'.
				 *
				 * @see wp_seo_default_formatting_tags() for the defaults' keys.
				 *
				 * @param string The value returned by the formatting tag.
				 */
				$replacements[ $tag->tag ] = apply_filters( "wp_seo_format_{$id}", $tag->get_value() );
			}
		}

		if ( ! empty( $replacements ) ) {
			$string = str_replace( array_keys( $replacements ), array_values( $replacements ), $string );
		}

		/**
		 * Filter the formatted string.
		 *
		 * @param string $string The formatted string.
		 * @param string $raw_string The string as submitted.
		 */
		return apply_filters( 'wp_seo_after_format_string', $string, $raw_string );
	}

	/**
	 * Set the available formatting tags.
	 */
	private function init_formatting_tags() {
		/**
		 * Filter the available formatting tags.
		 *
		 * @see wp_seo_default_formatting_tags() for an example implementation.
		 *
		 * @param array $tags Associative array of WP_SEO_Formatting_Tag instances.
		 */
		$tags = apply_filters( 'wp_seo_formatting_tags', array() );

		foreach ( $tags as $id => $tag ) {
			if ( $tag instanceof WP_SEO_Formatting_Tag ) {
				$this->formatting_tags[ $id ] = $tag;
			}
		}
	}

	/**
	 * Set the regular expression used to find formatting tags in a string.
	 */
	private function init_formatting_tag_pattern() {
		/**
		 * Filter the regular expression used to find formatting tags in a string.
		 *
		 * You might filter this if you want to add unusual custom tags.
		 *
		 * @param string $formatting_tag_pattern The regex.
		 */
		$this->formatting_tag_pattern = apply_filters( 'wp_seo_formatting_tag_pattern', '/#[a-zA-Z\_]+#/' );
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
		WP_SEO_Term_Meta_Boxes::taxonomy_add_form_fields( $taxonomy );
	}

	/**
	 * @deprecated
	 */
	public function edit_term_meta_fields( $tag, $taxonomy ) {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Term_Meta_Boxes::taxonomy_edit_form()' );
		WP_SEO_Term_Meta_Boxes::taxonomy_edit_form( $tag, $taxonomy );
	}

	/**
	 * @deprecated
	 */
	public function save_term_fields( $term_id, $term_taxonomy_id, $taxonomy ) {
		_deprecated_function( __FUNCTION__, '1.0', 'WP_SEO_Term_Meta_Boxes::created_term() or WP_SEO_Term_Meta_Boxes::edited_term()' );
		WP_SEO_Term_Meta_Boxes::edited_term( $term_id, $term_taxonomy_id, $taxonomy );
	}

}

function wp_seo() {
	return wp_seo::instance();
}
add_action( 'get_header', 'wp_seo' );
