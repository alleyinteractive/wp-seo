<?php
if ( ! class_exists( 'WP_SEO' ) ) :
/**
 * WP SEO Core Functionality
 *
 * @package WP SEO
 */
class WP_SEO {

	private static $instance;

	/**
	 * Associative array of WP_SEO_Formatting_Tag instances.
	 *
	 * @var array.
	 */
	public $formatting_tags = array();

	/**
	 * The regular expression used to find formatting tags.
	 *
	 * @var string.
	 */
	public $formatting_tag_pattern = '/#[a-zA-Z\_]+#/';

	/**
	 * The heading text above the SEO fields on posts and terms.
	 *
	 * @var string
	 */
	private $box_heading = '';

	private function __construct() {
		/* Don't do anything, needs to be initialized via instance() method */
	}

	public function __clone() { wp_die( "Please don't __clone WP_SEO" ); }

	public function __wakeup() { wp_die( "Please don't __wakeup WP_SEO" ); }

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WP_SEO;
			self::$instance->setup();
		}
		return self::$instance;
	}

	/**
	 * Add actions and filters.
	 */
	protected function setup() {
		add_action( 'init', array( $this, 'set_properties' ) );

		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			add_action( 'save_post', array( $this, 'save_post_fields' ) );
			add_action( 'edit_attachment', array( $this, 'save_post_fields' ) );
			add_action( 'add_attachment', array( $this, 'save_post_fields' ) );
			add_action( 'admin_init', array( $this, 'add_term_boxes' ) );
		}

		add_filter( 'wp_title', array( $this, 'wp_title' ), 20, 2 );
		add_filter( 'wp_head', array( $this, 'wp_head' ), 5 );
	}

	/**
	 * Set class properties.
	 *
	 * The filter for adding custom formatting tags is applied here.
	 */
	public function set_properties() {
		/**
		 * Filter the available formatting tags.
		 *
		 * @see  wp_seo_default_formatting_tags() for an example implementation.
		 *
		 * @param array WP_SEO::formatting_tags Associative array of WP_SEO_Formatting_Tag instances.
		 */
		foreach ( apply_filters( 'wp_seo_formatting_tags', $this->formatting_tags ) as $id => $tag ) {
			if ( is_a( $tag, 'WP_SEO_Formatting_Tag' ) ) {
				$this->formatting_tags[ $id ] = $tag;
			}
		}

		/**
		 * Filter the regular expression used to find formatting tags.
		 *
		 * You might need this if you want to add unusual custom tags.
		 *
		 * @see  wp_seo_admin_scripts(), wp-seo.js. If you change the pattern,
		 *     you should also overwrite the formatting_tag_pattern property in
		 *     the localized wp_seo_admin object so that your tags are properly
		 *     detected when counting characters.
		 *
		 * @param string WP_SEO::formatting_tag_pattern The regex.
		 */
		$this->formatting_tag_pattern = apply_filters( 'wp_seo_formatting_tag_pattern', $this->formatting_tag_pattern );

		/**
		 * Filter the heading above SEO fields on post- and term-edit screens.
		 *
		 * @param string The text.
		 */
		$this->box_heading = apply_filters( 'wp_seo_box_heading', __( 'Search Engine Optimization', 'wp-seo' ) );
	}

	/**
	 * Add the SEO fields to post types that support them.
	 *
	 * @param string $post_type The current post type.
	 */
	public function add_meta_boxes( $post_type ) {
		if ( WP_SEO_Settings()->has_post_fields( $post_type ) ) {
			add_meta_box(
				'wp_seo',
				$this->box_heading,
				array( $this, 'post_meta_fields' ),
				$post_type,
				/**
				 * Filter the screen context where the fields should display.
				 *
				 * @param  string @see add_meta_box().
				 */
				apply_filters( 'wp_seo_meta_box_context', 'normal' ),
				/**
				 * Filter the display priority of the fields within the context.
				 *
				 * @param  string @see add_meta_box().
				 */
				apply_filters( 'wp_seo_meta_box_priority', 'high' )
			);
		}
	}

	/**
	 * Helper to get the translated <noscript> text for the character count.
	 *
	 * @see  wp_seo_admin_scripts(), wpseo_update_character_counts() for the
	 *     message and logic behind JS-enabled character count estimates when
	 *     formatting tags are detected.
	 *
	 * @param  string $text The text to count.
	 * @return string The text to go between the <noscript> tags.
	 */
	private function noscript_character_count( $text ) {
		if ( false !== $matches = $this->get_formatting_tags( $text ) ) {
			$message = sprintf( 'At least %d, depending on formatting tags', ( strlen( $text ) - strlen( implode( '', $matches ) ) ) );
		} else {
			$message = strlen( $text );
		}
		return sprintf( __( '%s (save changes to update)', 'wp-seo' ), esc_html( $message ) );
	}

	/**
	 * Display the SEO fields for a post.
	 *
	 * @param  WP_Post $post The post being edited.
	 */
	public function post_meta_fields( $post ) {
		wp_nonce_field( plugin_basename( __FILE__ ), 'wp-seo-nonce' );
		?>
		<table class="wp-seo-post-meta-fields">
			<tbody>
				<tr>
					<th scope="row"><label for="wp_seo_meta_title"><?php esc_html_e( 'Title Tag', 'wp-seo' ); ?></label></th>
					<td>
						<input type="text" id="wp_seo_meta_title" name="seo_meta[title]" value="<?php echo esc_attr( $title = get_post_meta( $post->ID, '_meta_title', true ) ); ?>" size="96" />
						<div>
							<?php esc_html_e( 'Title character count: ', 'wp-seo' ); ?>
							<span class="title-character-count"></span>
							<noscript><?php echo esc_html( $this->noscript_character_count( $title ) ); ?></noscript>
						</div>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wp_seo_meta_description"><?php esc_html_e( 'Meta Description', 'wp-seo' ); ?></label></th>
					<td>
						<textarea id="wp_seo_meta_description" name="seo_meta[description]" rows="2" cols="96"><?php echo esc_textarea( $description = get_post_meta( $post->ID, '_meta_description', true ) ); ?></textarea>
						<div>
							<?php esc_html_e( 'Description character count: ', 'wp-seo' ); ?>
							<span class="description-character-count"></span>
							<noscript><?php echo esc_html( $this->noscript_character_count( $description ) ); ?></noscript>
						</div>
					<td>
				</tr>
				<tr>
					<th scope="row"><label for="wp_seo_meta_keywords"><?php esc_html_e( 'Meta Keywords', 'wp-seo' ) ?></label></th>
					<td><textarea id="wp_seo_meta_keywords" name="seo_meta[keywords]" rows="2" cols="96"><?php echo esc_textarea( get_post_meta( $post->ID, '_meta_keywords', true ) ) ?></textarea></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save the SEO values as post meta.
	 *
	 * @param  int $post_id The post ID being edited.
	 */
	public function save_post_fields( $post_id ) {
		if ( ! isset( $_POST['post_type'] ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! WP_SEO_Settings()->has_post_fields( $_POST['post_type'] ) ) {
			return;
		}

		$post_type = get_post_type_object( $_POST['post_type'] );
		if ( empty( $post_type->cap->edit_post ) || ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['wp-seo-nonce'] ) || ! wp_verify_nonce( $_POST['wp-seo-nonce'], plugin_basename( __FILE__ ) ) ) {
			return;
		}

		$post_id = absint( $_POST['post_ID'] );
		if ( ! isset( $_POST['seo_meta'] ) ) {
			$_POST['seo_meta'] = array();
		}

		foreach ( array( 'title', 'description', 'keywords' ) as $field ) {
			$data = isset( $_POST['seo_meta'][ $field ] ) ? sanitize_text_field( $_POST['seo_meta'][ $field ] ) : '';
			update_post_meta( $post_id, '_meta_' . $field, $data );
		}
	}

	/**
	 * Add the meta box to taxonomies with per-term fields enabled.
	 */
	public function add_term_boxes() {
		foreach ( WP_SEO_Settings()->get_taxonomies() as $slug ) {
			add_action( $slug . '_edit_form', array( $this, 'term_meta_fields' ), 10, 2 );
		}
		add_action( 'edited_term', array( $this, 'save_term_fields' ), 10, 3 );
	}

	/**
	 * Helper to construct an option name for per-term SEO fields.
	 *
	 * @param  object $term The term object
	 * @return string The option name
	 */
	private function get_term_fields_option_name( $term ) {
		return "wp-seo-term-{$term->term_taxonomy_id}";
	}

	/**
	 * Display the SEO fields for a term.
	 *
	 * @param  object $tag The term object
	 * @param  string $taxonomy The taxonomy slug
	 */
	public function term_meta_fields( $tag, $taxonomy ) {
		$values = get_option( $this->get_term_fields_option_name( $tag ), array( 'title' => '', 'description' => '', 'keywords' => '' ) );
		wp_nonce_field( plugin_basename( __FILE__ ), 'wp-seo-nonce' );
		?>
		<h2><?php echo esc_html( $this->box_heading ); ?></h2>
		<table class="form-table wp-seo-term-meta-fields">
			<tbody>
				<tr class="form-field">
					<th scope="row"><label for="wp_seo_meta_title"><?php esc_html_e( 'Title Tag', 'wp-seo' ); ?></label></th>
					<td>
						<input type="text" id="wp_seo_meta_title" name="seo_meta[title]" value="<?php echo esc_attr( $title = $values['title'] ); ?>" size="96" />
						<div>
							<?php esc_html_e( 'Title character count: ', 'wp-seo' ); ?>
							<span class="title-character-count"></span>
							<noscript><?php echo esc_html( $this->noscript_character_count( $title ) ); ?></noscript>
						</div>
					</td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="wp_seo_meta_description"><?php esc_html_e( 'Meta Description', 'wp-seo' ); ?></label></th>
					<td>
						<textarea id="wp_seo_meta_description" name="seo_meta[description]" rows="2" cols="96"><?php echo esc_textarea( $description = $values['description'] ); ?></textarea>
						<div>
							<?php esc_html_e( 'Description character count: ', 'wp-seo' ); ?>
							<span class="description-character-count"></span>
							<noscript><?php echo esc_html( $this->noscript_character_count( $description ) ); ?></noscript>
						</div>
					<td>
				</tr>
				<tr class="form-field">
					<th scope="row"><label for="wp_seo_meta_keywords"><?php esc_html_e( 'Meta Keywords', 'wp-seo' ) ?></label></th>
					<td><textarea id="wp_seo_meta_keywords" name="seo_meta[keywords]" rows="2" cols="96"><?php echo esc_textarea( $values['keywords'] ); ?></textarea></td>
				</tr>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save the SEO term values as an option.
	 *
	 * @param  int $term_id Term ID.
	 * @param  int $tt_id Term taxonomy ID.
	 * @param  string $taxonomy Taxonomy slug.
	 */
	public function save_term_fields( $term_id, $tt_id, $taxonomy ) {
		if ( ! isset( $_POST['taxonomy'] ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! WP_SEO_Settings()->has_term_fields( $taxonomy ) ) {
			return;
		}

		$object = get_taxonomy( $taxonomy );
		if ( empty( $object->cap->edit_terms ) || ! current_user_can( $object->cap->edit_terms, $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['wp-seo-nonce'] ) || ! wp_verify_nonce( $_POST['wp-seo-nonce'], plugin_basename( __FILE__ ) ) ) {
			return;
		}

		if ( ! isset( $_POST['seo_meta'] ) ) {
			$_POST['seo_meta'] = array();
		}

		foreach ( array( 'title', 'description', 'keywords' ) as $field ) {
			$data[ $field ] = isset( $_POST['seo_meta'][ $field ] ) ? sanitize_text_field( $_POST['seo_meta'][ $field ] ) : '';
		}

		$name = $this->get_term_fields_option_name( get_term( $term_id, $taxonomy ) );
		if ( false === get_option( $name ) ) {
			// Don't create an option unless at least one field exists.
			$filtered_data = array_filter( $data );
			if ( ! empty( $filtered_data ) ) {
				add_option( $name, $data, null, false );
			}
		} else {
			update_option( $name, $data );
		}
	}

	/**
	 * Get the formatting tags in a string.
	 *
	 * @param  string $string The string to search.
	 * @return array|bool An array of found tags, or false.
	 */
	public function get_formatting_tags( $string ) {
		preg_match_all( $this->formatting_tag_pattern, $string, $matches );
		return ! empty( $matches[0] ) ? $matches[0] : false;
	}

	/**
	 * Replace formatting tags in a string with their value for the current page.
	 *
	 * @param  string $string The string with formatting tags.
	 * @return string         The formatted string.
	 */
	public function format( $string ) {
		$raw_string = $string;

		$matches = $this->get_formatting_tags( $string );
		if ( ! $matches ) {
			return $string;
		}

		$unique_matches = array_unique( $matches );
		$replacements = array();
		$unregistered = array();

		// Loop through all tags here; wp_list_pluck() or similar would anyway.
		foreach( $this->formatting_tags as $id => $tag ) {
			if ( ! empty( $tag->tag ) && in_array( $tag->tag, $unique_matches ) ) {
				/**
				 * Filter the value of a formatting tag for the current page.
				 *
				 * The dynamic portion of the hook name, $id, refers to the
				 * array key used to register the formatting tag in
				 * $this->setup(). For example, the hook for the default
				 * "#site_name#" formatting tag is 'wp_seo_format_site_name'.
				 *
				 * @see wp_seo_default_formatting_tags() for the defaults' keys.
				 *
				 * @param  string The value returned by the formatting tag.
				 */
				$replacements[ $tag->tag ] = apply_filters( "wp_seo_format_{$id}", $tag->get_value() );
			}
		}

		if ( count( $unique_matches ) !== count( $replacements ) ) {
			foreach ( $unique_matches as $match ) {
				if ( ! isset( $replacements[ $match ] ) ) {
					/**
					 * Filter the fallback value of formatting tags.
					 *
					 * This value is used when a formatting tag is encountered
					 * in a string but no class for it was registered. For
					 * example, it would be used if a tag is misspelled or if a
					 * third-party plugin that provided the tag is deactivated.
					 *
					 * @param  string The fallback value. Defaults to empty string.
					 * @param  string $match The unregistered formatting tag.
					 */
					$replacements[ $match ] = apply_filters( 'wp_seo_format_fallback', '', $match );
					$unregistered[] = $match;
				}
			}
		}

		if ( ! empty( $replacements ) ) {
			$string = str_replace( array_keys( $replacements ), array_values( $replacements ), $string );
		}

		/**
		 * Filter the formatted string.
		 *
		 * @param  string $string       The formatted string.
		 * @param  string $raw_string 	The string as submitted.
		 * @param  array  $unregistered Array of any found, unregistered formatting tags.
		 */
		return apply_filters( 'wp_seo_after_format_string', $string, $raw_string, $unregistered );
	}

	/**
	 * Filter the page title with the custom title or formatted title.
	 *
	 * @param  string $title The page title from wp_title().
	 * @param  string $sep   The title separator.
	 * @return string        The custom title of the current entry, if one
	 *                       exists, or the formatted title from the settings
	 *                       for the current post type. The original title if no
	 *                       custom or formatted title exists.
	 */
	public function wp_title( $title, $sep ) {
		if ( is_singular() ) {
			if ( WP_SEO_Settings()->has_post_fields( $post_type = get_post_type() ) && $meta_title = get_post_meta( get_the_ID(), '_meta_title', true ) ) {
				return $this->format( $meta_title );
			} else {
				$key = "single_{$post_type}_title";
			}
		} elseif ( is_front_page() ) {
			$key = 'home_title';
		} elseif ( is_author() ) {
			$key = 'archive_author_title';
		} elseif ( is_category() || is_tag() || is_tax() ) {
			if ( ( WP_SEO_Settings()->has_term_fields( $taxonomy = get_queried_object()->taxonomy ) ) && ( $option = get_option( $this->get_term_fields_option_name( get_queried_object() ) ) ) && ( ! empty( $option['title'] ) ) ) {
				return $this->format( $option['title'] );
			} else {
				$key = "archive_{$taxonomy}_title";
			}
		} elseif ( is_post_type_archive() ) {
			$key = 'archive_' . get_queried_object()->name . '_title';
		} elseif ( is_date() ) {
			$key = 'archive_date_title';
		} elseif ( is_search() ) {
			$key = 'search_title';
		} elseif ( is_404() ) {
			$key = '404_title';
		} else {
			$key = false;
		}

		if ( $key ) {
			/**
			 * Filter the format string of the title tag for the current page.
			 *
			 * @param  string		The format string retrieved from the settings.
			 * @param  string $key 	The key of the setting retrieved.
			 */
			$title_string = apply_filters( 'wp_seo_title_tag_format', WP_SEO_Settings()->get_option( $key ), $key );

			if ( $title_string ) {
				return $this->format( $title_string );
			} else {
				return $title;
			}
		}

		return $title;
	}

	/**
	 * Render a <meta /> field.
	 *
	 * @access private.
	 *
	 * @param  string $name  The content of the "name" attribute.
	 * @param  string $content The content of the "content" attribute.
	 */
	private function meta_field( $name, $content ) {
		echo "<meta name='" . esc_attr( $name ) . "' content='" . esc_attr( $content ) . "' />\n";
	}

	/**
	 * Determine the <meta> values for the current page.
	 *
	 * Unlike WP_SEO::wp_title(), custom per-entry and per-term values are not
	 * returned immediately but rendered at the end of the method. They should
	 * not be filtered, regardless.
	 *
	 * @uses WP_SEO::meta_field() To render the results.
	 */
	public function wp_head() {
		if ( is_singular() ) {
			if ( WP_SEO_Settings()->has_post_fields( $post_type = get_post_type() ) ) {
				$meta_description = get_post_meta( get_the_ID(), '_meta_description', true );
				$meta_keywords = get_post_meta( get_the_ID(), '_meta_keywords', true );
			}
			$key = "single_{$post_type}";
		} elseif ( is_front_page() ) {
			$key = 'home';
		} elseif ( is_author() ) {
			$key = 'archive_author';
		} elseif ( is_category() || is_tag() || is_tax() ) {
			if ( WP_SEO_Settings()->has_term_fields( $taxonomy = get_queried_object()->taxonomy ) && $option = get_option( $this->get_term_fields_option_name( get_queried_object() ) ) ) {
				$meta_description = $option['description'];
				$meta_keywords = $option['keywords'];
			}
			$key = "archive_{$taxonomy}";
		} elseif ( is_post_type_archive() ) {
			$key = 'archive_' . get_queried_object()->name;
		} elseif ( is_date() ) {
			$key = 'archive_date';
		} else {
			$key = false;
		}

		if ( $key ) {
			if ( empty( $meta_description ) ) {
				/**
				 * Filter the format string of the meta description for this page.
				 *
				 * @param  string 		The format string retrieved from the settings.
				 * @param  string $key	The key of the setting retrieved.
				 */
				$meta_description = apply_filters( 'wp_seo_meta_description_format', WP_SEO_Settings()->get_option( "{$key}_description" ), $key );
			}

			if ( $meta_description ) {
				$this->meta_field( 'description', $this->format( $meta_description ) );
			}

			if ( empty( $meta_keywords ) ) {
				/**
				 * Filter the format string of the meta keywords for this page.
				 *
				 * @param  string 		The format string retrieved from the settings.
				 * @param  string $key	The key of the setting retrieved.
				 */
				$meta_keywords = apply_filters( 'wp_seo_meta_keywords_format', WP_SEO_Settings()->get_option( "{$key}_keywords" ), $key );
			}

			if ( $meta_keywords ) {
				$this->meta_field( 'keywords', $this->format( $meta_keywords ) );
			}
		}

		/**
		 * Filter the artibrary meta tags that display on this page.
		 *
		 * @param  array {
		 *     Meta tag data.
		 *
		 *     @type  string $name The field "name" attribute.
		 *     @type  string $content The field "content" attribute.
		 * }
		 */
		foreach( apply_filters( 'wp_seo_arbitrary_tags', WP_SEO_Settings()->get_option( 'arbitrary_tags' ) ) as $tag ) {
			$this->meta_field( $tag['name'], $this->format( $tag['content'] ) );
		}

	}

}

function WP_SEO() {
	return WP_SEO::instance();
}
add_action( 'after_setup_theme', 'WP_SEO' );

endif;