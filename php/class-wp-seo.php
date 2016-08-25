<?php
/**
 * Class file for WP_SEO.
 *
 * @package WP_SEO
 */

if ( ! class_exists( 'WP_SEO' ) ) :
	/**
	 * WP SEO core functionality.
	 */
	class WP_SEO {
		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		private static $instance = null;

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
		public $formatting_tag_pattern = '';

		/**
		 * Unused.
		 *
		 * @codeCoverageIgnore
		 */
		private function __construct() {
			// Don't do anything, needs to be initialized via instance() method.
		}

		/**
		 * Unused.
		 *
		 * @codeCoverageIgnore
		 */
		public function __clone() {
			wp_die( esc_html__( "Please don't __clone WP_SEO", 'wp-seo' ) );
		}

		/**
		 * Unused.
		 *
		 * @codeCoverageIgnore
		 */
		public function __wakeup() {
			wp_die( esc_html__( "Please don't __wakeup WP_SEO", 'wp-seo' ) );
		}

		/**
		 * Get the instance of this class.
		 *
		 * @codeCoverageIgnore
		 */
		public static function instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new WP_SEO;
				self::$instance->setup();
			}
			return self::$instance;
		}

		/**
		 * Add actions and filters.
		 *
		 * @codeCoverageIgnore
		 */
		protected function setup() {
			add_action( 'wp_loaded', array( $this, 'set_properties' ) );

			if ( is_admin() ) {
				add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
				add_action( 'save_post', array( $this, 'save_post_fields' ) );
				add_action( 'edit_attachment', array( $this, 'save_post_fields' ) );
				add_action( 'add_attachment', array( $this, 'save_post_fields' ) );
				add_action( 'admin_init', array( $this, 'add_term_boxes' ) );
			}

			add_filter( 'pre_get_document_title', array( $this, 'pre_get_document_title' ), 20 );
			add_filter( 'wp_title', array( $this, 'wp_title' ), 20, 2 );
			add_filter( 'wp_head', array( $this, 'wp_head' ), 5 );
		}

		/**
		 * Set class properties.
		 *
		 * The filter for adding custom formatting tags is applied here.
		 */
		public function set_properties() {
			$tags = array();
			/**
			 * Filter the available formatting tags.
			 *
			 * @see wp_seo_default_formatting_tags() for an example implementation.
			 *
			 * @param array $tags Associative array of WP_SEO_Formatting_Tag instances.
			 */
			foreach ( apply_filters( 'wp_seo_formatting_tags', $tags ) as $id => $tag ) {
				if ( is_a( $tag, 'WP_SEO_Formatting_Tag' ) ) {
					$tags[ $id ] = $tag;
				}
			}
			$this->formatting_tags = $tags;

			/**
			 * Filter the regular expression used to find formatting tags.
			 *
			 * You might need this if you want to add unusual custom tags.
			 *
			 * @param string WP_SEO::formatting_tag_pattern The regex.
			 */
			$this->formatting_tag_pattern = apply_filters( 'wp_seo_formatting_tag_pattern', '/#[a-zA-Z\_]+#/' );
		}

		/**
		 * Get the WP SEO term option value for a given term.
		 *
		 * @param int    $term_id  Term ID.
		 * @param string $taxonomy Term taxonomy.
		 * @return mixed The get_option() return value for the given term data.
		 */
		public function get_term_option( $term_id, $taxonomy ) {
			$option_name = '';

			$term = get_term( $term_id, $taxonomy );

			if ( $term instanceof \WP_Term ) {
				$option_name = $this->get_term_option_name( $term );
			}

			return get_option( $option_name );
		}

		/**
		 * Intersect data with the default array of WP SEO term option values.
		 *
		 * @param array $option_value Array of term SEO option values, if any.
		 * @return array Option values with default keys and values.
		 */
		public function intersect_term_option( $option_value ) {
			return wp_seo_intersect_args( $option_value, array(
				'title'       => '',
				'description' => '',
				'keywords'    => '',
			) );
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
					wp_seo_get_box_title(),
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
		 * Display the SEO fields for a post.
		 *
		 * @param  WP_Post $post The post being edited.
		 */
		public function post_meta_fields( $post ) {
			wp_nonce_field( plugin_basename( __FILE__ ), 'wp-seo-nonce' );

			/**
			 * Fires during the WP SEO post meta box display callback.
			 *
			 * @param WP_Post $post The post passed to the meta box display callback.
			 */
			do_action( 'wp_seo_post_meta_fields', $post );
		}

		/**
		 * Save the SEO values as post meta.
		 *
		 * @param  int $post_id The post ID being edited.
		 */
		public function save_post_fields( $post_id ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}

			if ( ! isset( $_POST['wp-seo-nonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp-seo-nonce'] ) ), plugin_basename( __FILE__ ) ) ) {
				return;
			}

			if ( ! isset( $_POST['post_type'] ) ) {
				return;
			}

			$post_type = sanitize_text_field( wp_unslash( $_POST['post_type'] ) );

			if ( ! WP_SEO_Settings()->has_post_fields( $post_type ) ) {
				return;
			}

			$post_type_obj = get_post_type_object( $post_type );
			if ( empty( $post_type_obj->cap->edit_post ) || ! current_user_can( $post_type_obj->cap->edit_post, $post_id ) ) {
				return;
			}

			if ( ! isset( $_POST['post_ID'] ) ) {
				return;
			}

			$post_id = absint( $_POST['post_ID'] );

			if ( ! isset( $_POST['seo_meta'] ) ) {
				$_POST['seo_meta'] = array();
			}

			foreach ( array( 'title', 'description', 'keywords' ) as $field ) {
				$data = isset( $_POST['seo_meta'][ $field ] ) ? sanitize_text_field( wp_unslash( $_POST['seo_meta'][ $field ] ) ) : '';
				update_post_meta( $post_id, wp_slash( '_meta_' . $field ), wp_slash( $data ) );
			}
		}

		/**
		 * Add the meta box to taxonomies with per-term fields enabled.
		 */
		public function add_term_boxes() {
			foreach ( WP_SEO_Settings()->get_enabled_taxonomies() as $slug ) {
				add_action( $slug . '_add_form_fields', array( $this, 'add_term_meta_fields' ) );
				add_action( $slug . '_edit_form', array( $this, 'edit_term_meta_fields' ), 10, 2 );
			}
			add_action( 'created_term', array( $this, 'save_term_fields' ), 10, 3 );
			add_action( 'edited_term', array( $this, 'save_term_fields' ), 10, 3 );
		}

		/**
		 * Helper to construct an option name for per-term SEO fields.
		 *
		 * @param WP_Term $term The term object.
		 * @return string The option name
		 */
		public function get_term_option_name( $term ) {
			return "wp-seo-term-{$term->term_taxonomy_id}";
		}

		/**
		 * Display the SEO fields for adding a term.
		 *
		 * @param string $taxonomy The taxonomy slug.
		 */
		public function add_term_meta_fields( $taxonomy ) {
			wp_nonce_field( plugin_basename( __FILE__ ), 'wp-seo-nonce' );

			/**
			 * Fires during the WP SEO add-term fields display callback.
			 *
			 * @param string $taxonomy The taxonomy slug.
			 */
			do_action( 'wp_seo_add_term_meta_fields', $taxonomy );
		}

		/**
		 * Display the SEO fields for editing a term.
		 *
		 * @param WP_Term $tag      The term object.
		 * @param string  $taxonomy The taxonomy slug.
		 */
		public function edit_term_meta_fields( $tag, $taxonomy ) {
			wp_nonce_field( plugin_basename( __FILE__ ), 'wp-seo-nonce' );

			/**
			 * Fires during the WP SEO edit-term fields display callback.
			 *
			 * @param WP_Term $tag The term object.
			 * @param string $taxonomy The taxonomy slug.
			 */
			do_action( 'wp_seo_edit_term_meta_fields', $tag, $taxonomy );
		}

		/**
		 * Save the SEO term values as an option.
		 *
		 * @see wp_unslash(), which the Settings API and update_post_meta()
		 *     otherwise handle.
		 *
		 * @param int    $term_id  Term ID.
		 * @param int    $tt_id    Term taxonomy ID.
		 * @param string $taxonomy Taxonomy slug.
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
			if ( empty( $object->cap->edit_terms ) || ! current_user_can( $object->cap->edit_terms ) ) {
				return;
			}

			if ( ! isset( $_POST['wp-seo-nonce'] ) ) {
				return;
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp-seo-nonce'] ) ), plugin_basename( __FILE__ ) ) ) {
				return;
			}

			if ( ! isset( $_POST['seo_meta'] ) ) {
				$_POST['seo_meta'] = array();
			}

			foreach ( array( 'title', 'description', 'keywords' ) as $field ) {
				$data[ $field ] = isset( $_POST['seo_meta'][ $field ] ) ? sanitize_text_field( wp_unslash( $_POST['seo_meta'][ $field ] ) ) : '';
			}

			$name = $this->get_term_option_name( get_term( $term_id, $taxonomy ) );
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

			$matches = wp_seo_match_all_formatting_tags( $string );
			if ( empty( $matches ) ) {
				return $string;
			}

			$replacements = array();
			$unique_matches = array_unique( $matches );

			foreach ( $this->formatting_tags as $id => $tag ) {
				if ( ! empty( $tag->tag ) && in_array( $tag->tag, $unique_matches ) ) {
					/**
					 * Filter the value of a formatting tag for the current page.
					 *
					 * The dynamic portion of the hook name, $id, refers to the key
					 * used to register the tag in WP_SEO::set_properties(). For
					 * example, the hook for the default "#site_name#" formatting
					 * tag is 'wp_seo_format_site_name'.
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
				if ( WP_SEO_Settings()->has_post_fields( $post_type = get_post_type() ) && $meta_title = get_post_meta( get_the_ID(), '_meta_title', true ) ) {
					return $meta_title;
				} else {
					$key = "single_{$post_type}_title";
				}
			} elseif ( is_front_page() ) {
				$key = 'home_title';
			} elseif ( is_author() ) {
				$key = 'archive_author_title';
			} elseif ( is_category() || is_tag() || is_tax() ) {
				if ( ( WP_SEO_Settings()->has_term_fields( $taxonomy = get_queried_object()->taxonomy ) ) && ( $option = get_option( $this->get_term_option_name( get_queried_object() ) ) ) && ( ! empty( $option['title'] ) ) ) {
					return $option['title'];
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
				$title_tag = $this->format( $title_string );
				if ( $title_tag && ! is_wp_error( $title_tag ) ) {
					$title = $title_tag;
				}
			}

			return $title;
		}

		/**
		 * Filter the document title before it is generated.
		 *
		 * @param string $title The document title. Default empty string.
		 * @return string The custom title, if any, or the received $title if none exists.
		 */
		public function pre_get_document_title( $title ) {
			// We can lean on the logic already in WP_SEO::wp_title().
			$custom = $this->wp_title( $title, '' );

			if ( $custom ) {
				$title = $custom;
			}

			return $title;
		}

		/**
		 * Render a <meta /> field.
		 *
		 * @access private.
		 *
		 * @since 0.12.0 An HTML comment was added to the output.
		 *
		 * @param  string $name  The content of the "name" attribute.
		 * @param  string $content The content of the "content" attribute.
		 */
		private function meta_field( $name, $content ) {
			if ( ! is_string( $name ) || ! is_string( $content ) ) {
				return;
			}

			echo "<meta name='" . esc_attr( $name ) . "' content='" . esc_attr( $content ) . "' /><!-- WP SEO -->\n";
		}

		/**
		 * Determine the <meta> values for the current page.
		 *
		 * Unlike WP_SEO::wp_title(), custom per-entry and per-term values are not
		 * returned immediately but rendered at the end of the method.
		 *
		 * @see WP_SEO::meta_field() for detail on how the values are rendered.
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
				if ( WP_SEO_Settings()->has_term_fields( $taxonomy = get_queried_object()->taxonomy ) && $option = get_option( $this->get_term_option_name( get_queried_object() ) ) ) {
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
					$description_string = apply_filters( 'wp_seo_meta_description_format', WP_SEO_Settings()->get_option( "{$key}_description" ), $key );
					$meta_description = $this->format( $description_string );
				}

				if ( $meta_description && ! is_wp_error( $meta_description ) ) {
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

				if ( $meta_keywords && ! is_wp_error( $meta_keywords ) ) {
					$this->meta_field( 'keywords', $meta_keywords );
				}
			}

			/**
			 * Filter the artibrary meta tags that display on this page.
			 *
			 * @param  array {
			 *     Meta tag data.
			 *
			 *     @type  string $name    The field "name" attribute.
			 *     @type  string $content The field "content" attribute.
			 * }
			 */
			$arbitrary_tags = apply_filters( 'wp_seo_arbitrary_tags', WP_SEO_Settings()->get_option( 'arbitrary_tags' ) );
			if ( is_array( $arbitrary_tags ) ) {
				foreach ( $arbitrary_tags as $tag ) {
					$this->meta_field( $tag['name'], $this->format( $tag['content'] ) );
				}
			}

		}
	}

	/**
	 * Helper function to use the class instance.
	 *
	 * @return WP_SEO
	 */
	function wp_seo() {
		return WP_SEO::instance();
	}
	add_action( 'after_setup_theme', 'wp_seo' );
endif;
