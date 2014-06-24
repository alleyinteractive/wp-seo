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
			add_action( 'save_post', array( $this, 'save_post' ) );
			add_action( 'edit_attachment', array( $this, 'save_post' ) );
			add_action( 'add_attachment', array( $this, 'save_post' ) );
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
		 * @param array $this->formatting_tags Associative array of WP_SEO_Formatting_Tag instances.
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
		 * @param string $this->formatting_tag_pattern The regex.
		 */
		$this->formatting_tag_pattern = apply_filters( 'wp_seo_formatting_tag_pattern', $this->formatting_tag_pattern );
	}

	/**
	 * Add the SEO fields to post types that support them.
	 *
	 * @param string $post_type The current post type.
	 */
	public function add_meta_boxes( $post_type ) {
		if ( WP_SEO_Settings()->has_individual_fields( $post_type ) ) {
			add_meta_box(
				'wp_seo',
				__( 'Search Engine Optimization', 'wp-seo' ),
				array( $this, 'meta_fields' ),
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
	 * Display the SEO fields.
	 *
	 * @param  WP_Post $post The post being edited.
	 */
	public function meta_fields( $post ) {
		wp_nonce_field( plugin_basename( __FILE__ ), 'wp-seo-nonce' );
		?>
		<table class="wp-seo-meta-fields">
			<tbody>
				<tr>
					<th scope="row"><label for="wp_seo_meta_title"><?php _e( 'Title Tag', 'wp-seo' ); ?></label></th>
					<td><input type="text" id="wp_seo_meta_title" name="seo_meta[title]" value="<?php echo esc_attr( get_post_meta( $post->ID, '_meta_title', true ) ); ?>" size="96" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="wp_seo_meta_description"><?php _e( 'Meta Description', 'wp-seo' ); ?></label></th>
					<td>
						<textarea id="wp_seo_meta_description" name="seo_meta[description]" rows="2" cols="96"><?php echo esc_html( $description = get_post_meta( $post->ID, '_meta_description', true ) ); ?></textarea>
						<div>
							<?php _e( 'Description character count: ', 'wp-seo' ); ?>
							<span class="description-character-count"></span>
							<noscript><?php _e( sprintf( '%d (save the post to update)', strlen( $description ) ), 'wp-seo' ); ?></noscript>
						</div>
					<td>
				</tr>
				<tr>
					<th scope="row"><label for="wp_seo_meta_keywords"><?php _e( 'Meta Keywords', 'wp-seo' ) ?></label></th>
					<td><textarea id="wp_seo_meta_keywords" name="seo_meta[keywords]" rows="2" cols="96"><?php echo esc_html( get_post_meta( $post->ID, '_meta_keywords', true ) ) ?></textarea></td>
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
	public function save_post( $post_id ) {
		if ( ! isset( $_POST['post_type'] ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! WP_SEO_Settings()->has_individual_fields( $_POST['post_type'] ) ) {
			return;
		}

		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_pages', $post_id ) ) {
				return;
			}
		} else {
			if ( ! current_user_can( 'edit_posts', $post_id ) ) {
				return;
			}
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
	 * Replace formatting tags in a string with their value for the current page.
	 *
	 * @param  string $string The string with formatting tags.
	 * @return string         The formatted string.
	 */
	public function format( $string ) {
		$raw_string = $string;

		preg_match_all( $this->formatting_tag_pattern, $string, $matches );
		if ( empty( $matches[0] ) ) {
			return $string;
		}

		$replacements = array();
		$unique_matches = array_unique( $matches[0] );

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

		if ( ! empty( $replacements ) ) {
			$string = str_replace( array_keys( $replacements ), array_values( $replacements ), $string );
		}

		/**
		 * Filter the formatted string.
		 *
		 * @param  string $string 		The formatted string.
		 * @param  string $raw_string 	The string as submitted.
		 */
		return apply_filters( 'wp_seo_after_format_string', $string, $raw_string );
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
			if ( WP_SEO_Settings()->has_individual_fields( $post_type = get_post_type() ) && $meta_title = get_post_meta( get_the_ID(), '_meta_title', true ) ) {
				return $meta_title;
			} else {
				$key = "single_{$post_type}_title";
			}
		} elseif ( is_front_page() ) {
			$key = 'home_title';
		} elseif ( is_author() ) {
			$key = 'archive_author_title';
		} elseif ( is_category() || is_tag() || is_tax() || is_post_type_archive() ) {
			$name = is_post_type_archive() ? get_queried_object()->name : get_queried_object()->taxonomy;
			$key = "archive_{$name}_title";
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
	 * @param  string $value The content of the "value" attribute.
	 */
	private function meta_field( $name, $value ) {
		echo "<meta name='" . esc_attr( $name ) . "' value='" . esc_attr( $value ) . "' />\n";
	}

	/**
	 * Determine the <meta> values for the current page.
	 *
	 * @uses $this->meta_field() To render the results.
	 */
	public function wp_head() {
		if ( is_singular() ) {
			if ( WP_SEO_Settings()->has_individual_fields( $post_type = get_post_type() ) ) {
				$meta_description = get_post_meta( get_the_ID(), '_meta_description', true );
				$meta_keywords = get_post_meta( get_the_ID(), '_meta_keywords', true );
			}
			$key = "single_{$post_type}";
		} elseif ( is_front_page() ) {
			$key = 'home';
		} elseif ( is_author() ) {
			$key = 'archive_author';
		} elseif ( is_category() || is_tag() || is_tax() || is_post_type_archive() ) {
			$name = is_post_type_archive() ? get_queried_object()->name : get_queried_object()->taxonomy;
			$key = "archive_{$name}";
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
				$description_string = apply_filters( 'wp_seo_meta_description_format', WP_SEO_Settings()->get_option( "{$key}_description" ), $key );
				$meta_description = $this->format( $description_string );
			}

			if ( $meta_description ) {
				$this->meta_field( 'description', $meta_description );
			}

			if ( empty( $meta_keywords ) ) {
				/**
				 * Filter the format string of the meta keywords for this page.
				 *
				 * @param  string 		The format string retrieved from the settings.
				 * @param  string $key	The key of the setting retrieved.
				 */
				$keywords_string = apply_filters( 'wp_seo_meta_keywords_format', WP_SEO_Settings()->get_option( "{$key}_keywords" ), $key );
				$meta_keywords = $this->format( $keywords_string );
			}

			if ( $meta_keywords ) {
				$this->meta_field( 'keywords', $meta_keywords );
			}
		}
	}

}

function WP_SEO() {
	return WP_SEO::instance();
}
add_action( 'after_setup_theme', 'WP_SEO' );

endif;