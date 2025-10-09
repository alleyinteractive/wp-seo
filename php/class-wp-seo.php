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
			remove_action( 'wp_head', 'rel_canonical' ); // We handle canonical URLs ourselves.
			add_filter( 'wp_head', array( $this, 'wp_head' ), 5 );
			add_filter( 'wp_robots', array( $this, 'wp_robots' ) );
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
			return wp_seo_intersect_args(
				$option_value,
				array_merge(
					array_fill_keys( $this->get_base_fields(), '' ),
					array_fill_keys( $this->get_robots_directive_meta_keys(), '' ),
				),
			);
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

			/**
			 * Filter the fields that can be saved.
			 *
			 * @param array $fields Array of field names that can be saved to the post meta.
			 */
			$fields = (array) apply_filters(
				'wp_seo_saveable_fields',
				array_merge(
					$this->get_base_fields(),
					$this->get_robots_directive_meta_keys(),
				)
			);
			foreach ( $fields as $field ) {
				$meta_key = wp_slash( '_meta_' . $field );

				// If this is the canonical URL field, validate it as a URL.
				if ( 'canonical_url' === $field ) {
					$value = isset( $_POST['seo_meta'][ $field ] )
						? wp_unslash( $_POST['seo_meta'][ $field ] )
						: '';

					// Only save if empty or valid URL.
					if ( empty( $value ) ) {
						update_post_meta( $post_id, $meta_key, '' );
					} else {
						$valid_url = filter_var( $value, FILTER_VALIDATE_URL );
						if ( $valid_url ) {
							update_post_meta( $post_id, $meta_key, wp_slash( $valid_url ) );
						}
					}
				} else {
					$value = isset( $_POST['seo_meta'][ $field ] )
						? sanitize_text_field( wp_unslash( $_POST['seo_meta'][ $field ] ) )
						: '';
					update_post_meta( $post_id, $meta_key, wp_slash( $value ) );
				}
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

			$fields = array_merge(
				$this->get_base_fields(),
				$this->get_robots_directive_meta_keys(),
			);

			foreach ( $fields as $field ) {
				$value = isset( $_POST['seo_meta'][ $field ] ) ? sanitize_text_field( wp_unslash( $_POST['seo_meta'][ $field ] ) ) : '';

				if ( 'canonical_url' === $field ) {
					// Only save if empty or valid URL.
					if ( empty( $value ) ) {
						$data[ $field ] = '';
					} else {
						$valid_url = filter_var( $value, FILTER_VALIDATE_URL );
						if ( $valid_url ) {
							$data[ $field ] = $valid_url;
						}
					}
				} else {
					$data[ $field ] = $value;
				}
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

			$title_string = null;
			if ( $key ) {
				$title_string = WP_SEO_Settings()->get_option( $key );
			}

			/**
			 * Filter the format string of the title tag for the current page.
			 *
			 * @param  string $title_string The format string retrieved from the settings.
			 * @param  string $key 	The key of the setting retrieved.
			 */
			$title_string = apply_filters( 'wp_seo_title_tag_format', $title_string, $key );

			// Format the title string if set.
			if ( ! empty( $title_string ) ) {
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
		 * @param string $name    The value of the "name" attribute.
		 * @param string $content The value of the "content" attribute.
		 */
		private function meta_field( $name, $content ) {
			/**
			 * Filters the "content" attribute value of the <meta /> field.
			 *
			 * @since 0.13.0
			 *
			 * @param string $content The "content" attribute value.
			 * @param string $name    The "name" attribute value.
			 */
			$content = apply_filters( 'wp_seo_meta_field_content', $content, $name );

			if ( ! is_string( $name ) || ! is_string( $content ) ) {
				return;
			}

			echo "<meta name='" . esc_attr( $name ) . "' content='" . esc_attr( $content ) . "' /><!-- WP SEO -->\n";
		}

		/**
		 * Render a <link /> field.
		 *
		 * @access private.
		 *
		 * @param string $rel  The value of the "rel" attribute.
		 * @param string $href The value of the "href" attribute.
		 */
		private function link_field( $rel, $href ) {
			/**
			 * Filters the "content" attribute value of the <meta /> field.
			 *
			 * @since 2.0.0
			 *
			 * @param string $href The "href" attribute value.
			 * @param string $rel  The "rel" attribute value.
			 */
			$href = apply_filters( 'wp_seo_link_field_href', $href, $rel );

			if ( ! is_string( $rel ) || ! is_string( $href ) ) {
				return;
			}

			echo "<link rel='" . esc_attr( $rel ) . "' href='" . esc_url( $href ) . "' /><!-- WP SEO -->\n";
		}

		/**
		 * Determine the <meta> values for the current page.
		 *
		 * Unlike WP_SEO::wp_title(), custom per-entry and per-term values are not
		 * returned immediately but rendered at the end of the method.
		 *
		 * @see WP_SEO::meta_field() and WP_SEO::link_field() for detail
		 * on how the values are rendered.
		 */
		public function wp_head() {
			if ( is_singular() ) {
				if ( WP_SEO_Settings()->has_post_fields( $post_type = get_post_type() ) ) {
					$post_id = get_the_ID();
					$meta_description = get_post_meta( $post_id, '_meta_description', true );
					$canonical_url = get_post_meta( $post_id, '_meta_canonical_url', true );
				}
				$key = "single_{$post_type}";
			} elseif ( is_front_page() ) {
				$key = 'home';
			} elseif ( is_author() ) {
				$key = 'archive_author';
			} elseif ( is_category() || is_tag() || is_tax() ) {
				if ( WP_SEO_Settings()->has_term_fields( $taxonomy = get_queried_object()->taxonomy ) && $option = get_option( $this->get_term_option_name( get_queried_object() ) ) ) {
					$meta_description = $option['description'];
					$canonical_url = $option['canonical_url'];
				}
				$key = "archive_{$taxonomy}";
			} elseif ( is_post_type_archive() ) {
				$key = 'archive_' . get_queried_object()->name;
			} elseif ( is_date() ) {
				$key = 'archive_date';
			} else {
				$key = false;
			}

			if ( empty( $meta_description ) ) {
				/**
				 * Filter the format string of the meta description for this page.
				 *
				 * @param  string      The format string retrieved from the settings.
				 * @param  string $key The key of the setting retrieved.
				 */
				$description_string = apply_filters(
					'wp_seo_meta_description_format',
					! empty( $key ) ? WP_SEO_Settings()->get_option( "{$key}_description" ) : '',
					$key
				);
				$meta_description = $this->format( $description_string );
			}

			if ( ! empty( $meta_description ) && ! is_wp_error( $meta_description ) ) {
				$this->meta_field( 'description', $meta_description );
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

			// Output the canonical URL if set.
			if ( empty( $canonical_url ) ) {
				/**
				 * Filter the format string of the canonical URL for this page.
				 * @param  string      The format string retrieved from the settings.
				 * @param  string $key The key of the setting retrieved.
				 */
				$canonical_string = apply_filters(
					'wp_seo_canonical_url_format',
					! empty( $key ) ? WP_SEO_Settings()->get_option( "{$key}_canonical_url" ) : '',
					$key
				);
				$canonical_url = $this->format( $canonical_string );
			}

			if ( ! empty( $canonical_url ) && ! is_wp_error( $canonical_url ) ) {
				$this->link_field( 'canonical', $canonical_url );
			}
		}

		/**
		 * Filters the directives to be included in the 'robots' meta tag.
		 * 
		 * @param array $robots Associative array of directives.
		 */
		public function wp_robots( $robots ) {
			$key = '';

			// Singular posts.
			if ( is_singular() ) {
				$post_type = get_post_type( get_queried_object_id() );

				if ( empty( $post_type )
					|| ! WP_SEO_Settings()->has_post_fields( $post_type )
				) {
					return $robots;
				}

				$key = "single_{$post_type}";
			}

			// Term archives.
			if ( is_category() || is_tag() || is_tax() ) {
				$term = get_queried_object();

				if ( ! $term instanceof WP_Term
					|| empty( $term->taxonomy )
					|| ! WP_SEO_Settings()->has_term_fields( $term->taxonomy )
				) {
					return $robots;
				}

				$key = "archive_{$term->taxonomy}";
			}

			// Homepage.
			if ( is_front_page() ) {
				$key = 'home';
			}

			// Author archives.
			if ( is_author() ) {
				$key = 'archive_author';
			}

			// Post type archives.
			if ( is_post_type_archive() ) {
				$key = 'archive_' . get_queried_object()->name;
			}

			// Date archives.
			if ( is_date() ) {
				$key = 'archive_date';
			}

			if ( empty( $key ) ) {
				return $robots;
			}

			$robots = $this->filter_robots_meta( $robots, $key );

			return $robots;
		}

		/**
		 * Get the post meta or term option for the current object.
		 * 
		 * @return array Associative array of meta/option values, or empty array if none exist.
		 */
		public function get_current_object_options(): array {
			// Singular posts.
			if ( is_singular() ) {
				$post_id = get_queried_object_id();
				if ( empty( $post_id ) ) {
					return [];
				}

				$post_meta = get_post_meta( $post_id );

				return ! empty( $post_meta ) && is_array( $post_meta )
					? $post_meta
					: [];

			}

			// Term archives.
			if ( is_category() || is_tag() || is_tax() ) {
				$term = get_queried_object();
				if ( empty( $term ) || ! $term instanceof WP_Term ) {
					return [];
				}

				$term_options = get_option( $this->get_term_option_name( $term ) );

				return ! empty( $term_options ) && is_array( $term_options )
					? $term_options
					: [];
			}

			return [];
		}

		/**
		 * Set the robots meta directives based on current object
		 * options (post meta or term options), or settings.
		 * 
		 * @param array  $robots Associative array of directives.
		 * @param string $key    The settings key for the current page type.
		 * @return array Updated associative array of directives.
		 */
		public function filter_robots_meta( array $robots, string $key ): array {
			if ( empty( $key ) ) {
				return $robots;
			}

			// List of possible robots directives.
			$robots_directives = $this->get_robots_directive_values();

			if ( empty( $robots_directives ) || ! is_array( $robots_directives ) ) {
				return $robots;
			}

			// Get the data for the current object (post meta or term options), if any.
			$object_data = $this->get_current_object_options();

			// Get the settings from the settings page.
			$settings = WP_SEO_Settings()->get_option( "{$key}_robots" );

			// Check the meta value for each directive and set it to true if present.
			foreach ( $robots_directives as $directive ) {
				if ( empty( $directive ) ) {
					continue;
				}

				$robots_meta = '';

				if ( is_singular() && ! empty( $object_data ) ) {
					$robots_meta = $object_data[ "_meta_robots_{$directive}" ][0] ?? '';
				} elseif ( ( is_category() || is_tag() || is_tax() ) && ! empty( $object_data ) ) {
					$robots_meta = $object_data[ "robots_{$directive}" ] ?? '';
				}

				if ( empty( $robots_meta ) && ! empty( $settings ) && is_array( $settings ) ) {
					/**
					 * Filter the robots meta directive for this page.
					 * @param  string      The value retrieved from the settings.
					 * @param  string $key The key of the setting retrieved.
					 */
					$robots_meta = apply_filters(
						"wp_seo_robots_{$directive}_value",
						in_array( $directive, $settings ) ? $directive : '',
						$key
					);
				}

				if ( ! empty( $robots_meta ) && 'disable' !== $robots_meta ) {
					$robots[ $directive ] = true;
				}
			}

			return $robots;
		}

    /**
     * Get the base WP SEO fields.
     *
     * @return array Array of base WP SEO fields.
     */
    public function get_base_fields(): array {
      return [
        'title',
        'description',
        'canonical_url',
      ];
    }

    /**
     * Get robots directives.
     *
     * @return array Array of robots directives from settings.
     */
    public function get_robots_directives(): array {
      return WP_SEO_Settings()->get_option( 'robots_meta_directives', [] );
    }

    /**
     * Get robots directive values.
     *
     * @return array Array of robots directive values from settings.
     */
    public function get_robots_directive_values(): array {
      return wp_list_pluck( $this->get_robots_directives(), 'value' );
		}

    /**
     * Get robots directive meta keys.
     *
     * @return array Array of robots directive meta keys from settings.
		 * The meta keys are the directive values prefixed with 'robots_'.
     */
    public function get_robots_directive_meta_keys(): array {
      return array_map(
        fn( $value ) => 'robots_' . $value,
        $this->get_robots_directive_values(),
      );
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
