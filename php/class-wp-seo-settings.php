<?php
/**
 * Class file for WP_SEO_Settings.
 *
 * @package WP_SEO
 */

/**
 * Manages the plugin settings page, and provides helpers to some option values.
 */
class WP_SEO_Settings {
	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	private static $instance = null;

	/**
	 * The user capability required to access the options page.
	 *
	 * @var string.
	 */
	public $options_capability = '';

	/**
	 * The default options to save.
	 *
	 * @var array.
	 */
	public $default_options = array();

	/**
	 * Storage unit for the current option values of the plugin.
	 *
	 * @var array.
	 */
	public $options = array();

	/**
	 * Taxonomies with archive pages, which can have meta fields set for them.
	 *
	 * @see  WP_SEO_Settings::setup().
	 *
	 * @var array Term objects.
	 */
	private $taxonomies = array();

	/**
	 * Post types that can be viewed individually and have per-entry meta values.
	 *
	 * @see  WP_SEO_Settings::setup().
	 *
	 * @var array Post type objects.
	 */
	private $single_post_types = array();

	/**
	 * Post types with archives, which can have meta fields set for them.
	 *
	 * @see  WP_SEO_Settings::setup().
	 *
	 * @var array Post type objects.
	 */
	private $archived_post_types = array();

	const SLUG = 'wp-seo';

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
		wp_die( esc_html__( "Please don't __clone WP_SEO_Settings", 'wp-seo' ) );
	}

	/**
	 * Unused.
	 *
	 * @codeCoverageIgnore
	 */
	public function __wakeup() {
		wp_die( esc_html__( "Please don't __wakeup WP_SEO_Settings", 'wp-seo' ) );
	}

	/**
	 * Get the instance of this class.
	 *
	 * @codeCoverageIgnore
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WP_SEO_Settings;
			self::$instance->setup();
		}
		return self::$instance;
	}

	/**
	 * Add settings-related actions and filters.
	 *
	 * @codeCoverageIgnore
	 */
	protected function setup() {
		add_action( 'wp_loaded', array( $this, 'set_properties' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_options_page' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'load-settings_page_' . $this::SLUG, array( $this, 'add_help_tab' ) );
		}
	}

	/**
	 * Set class properties.
	 */
	public function set_properties() {
		/**
		 * Filter the capability required to access the settings page.
		 *
		 * @param string The default capability.
		 */
		$this->options_capability = apply_filters( 'wp_seo_options_capability', 'manage_options' );

		/**
		 * Filter the post types that support per-entry SEO fields.
		 *
		 * @param array Associative array of post type keys and objects.
		 */
		$this->single_post_types = apply_filters( 'wp_seo_single_post_types', wp_list_filter( get_post_types( array( 'public' => true ), 'objects' ), array( 'label' => false ), 'NOT' ) );

		/**
		 * Filter the post types that support SEO fields on their archive pages.
		 *
		 * @param array Associative array of post type keys and objects.
		 */
		$this->archived_post_types = apply_filters( 'wp_seo_archived_post_types', wp_list_filter( get_post_types( array( 'has_archive' => true ), 'objects' ), array( 'label' => false ), 'NOT' ) );

		/**
		 * Filter the taxonomies that support SEO fields on term archive pages.
		 *
		 * @param  array Associative array of taxonomy keys and objects.
		 */
		$this->taxonomies = apply_filters( 'wp_seo_taxonomies', wp_list_filter( get_taxonomies( array( 'public' => true ), 'objects' ), array( 'label' => false ), 'NOT' ) );

		/**
		 * Filter the options to save by default.
		 *
		 * These are also the settings shown when the option does not exist,
		 * such as when the the plugin is first activated.
		 *
		 * @param  array Associative array of setting names and values.
		 */
		$this->default_options = apply_filters( 'wp_seo_default_options', array( 'post_types' => array_keys( $this->single_post_types ), 'taxonomies' => array_keys( $this->taxonomies ) ) );
	}

	/**
	 * Set $options with the current database value.
	 */
	public function set_options() {
		$this->options = get_option( $this::SLUG, $this->default_options );
	}

	/**
	 * Get an option value.
	 *
	 * @param string $key     The option key sought.
	 * @param mixed  $default Optional default.
	 * @return mixed The value, or null on failure.
	 */
	public function get_option( $key, $default = null ) {
		if ( empty( $this->options ) ) {
			$this->set_options();
		}
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
	}

	/**
	 * Get the $taxonomies property.
	 *
	 * @return array @see WP_SEO_Settings::taxonomies.
	 */
	public function get_taxonomies() {
		return $this->taxonomies;
	}

	/**
	 * Get the $single_post_types property.
	 *
	 * @return array @see WP_SEO_Settings::single_post_types.
	 */
	public function get_single_post_types() {
		return $this->single_post_types;
	}

	/**
	 * Get the $archived_post_types property.
	 *
	 * @return array @see WP_SEO_Settings::archived_post_types.
	 */
	public function get_archived_post_types() {
		return $this->archived_post_types;
	}

	/**
	 * Get the taxonomies with per-term fields enabled.
	 *
	 * @return array With slugs of any enabled taxonomies.
	 */
	public function get_enabled_taxonomies() {
		return $this->get_option( 'taxonomies', array() );
	}

	/**
	 * Get the post types with per-entry fields enabled.
	 *
	 * @return array With names of any enabled post types.
	 */
	public function get_enabled_post_types() {
		return $this->get_option( 'post_types', array() );
	}

	/**
	 * Helper to check whether a post type is set in "Add fields to individual."
	 *
	 * @param string $post_type Post type name.
	 * @return bool
	 */
	public function has_post_fields( $post_type ) {
		return in_array( $post_type, $this->get_enabled_post_types() );
	}

	/**
	 * Helper to check whether a taxonomy is set in "Add fields to individual."
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return bool
	 */
	public function has_term_fields( $taxonomy ) {
		return in_array( $taxonomy, $this->get_enabled_taxonomies() );
	}

	/**
	 * Register the plugin options page.
	 */
	public function add_options_page() {
		add_options_page( __( 'WP SEO Settings', 'wp-seo' ), __( 'SEO', 'wp-seo' ), $this->options_capability, $this::SLUG, array( $this, 'view_settings_page' ) );
	}

	/**
	 * Add tabs to the help menu on the plugin options page.
	 */
	public function add_help_tab() {
		get_current_screen()->add_help_tab( array(
			'id'       => 'formatting-tags',
			'title'    => __( 'Formatting Tags', 'wp-seo' ),
			'callback' => array( $this, 'view_formatting_tags_help_tab' ),
		) );
	}

	/**
	 * Register the plugin settings.
	 */
	public function register_settings() {
		register_setting( $this::SLUG, $this::SLUG, array( $this, 'sanitize_options' ) );

		add_settings_section( 'home', __( 'Home Page', 'wp-seo' ), '__return_false', $this::SLUG );
		add_settings_field( 'home_title', __( 'Title Tag Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'home', array( 'field' => 'home_title' ) );
		add_settings_field( 'home_description', __( 'Meta Description Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'home', array( 'type' => 'textarea', 'field' => 'home_description' ) );
		add_settings_field( 'home_keywords', __( 'Meta Keywords Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'home', array( 'field' => 'home_keywords' ) );

		add_settings_section( 'post_types', __( 'Post Types', 'wp-seo' ), '__return_false', $this::SLUG );
		add_settings_field( 'post_types', __( 'Add SEO fields to individual:', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'post_types', array( 'field' => 'post_types', 'type' => 'checkboxes', 'boxes' => call_user_func_array( 'wp_list_pluck', array( $this->single_post_types, 'label' ) ) ) );

		foreach ( $this->single_post_types as $post_type ) {
			/* translators: %s: post type singular name */
			add_settings_section( 'single_' . $post_type->name, sprintf( __( 'Single %s Defaults', 'wp-seo' ), $post_type->labels->singular_name ), array( $this, 'example_permalink' ), $this::SLUG );
			add_settings_field( "single_{$post_type->name}_title", __( 'Title Tag Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'single_' . $post_type->name, array( 'field' => "single_{$post_type->name}_title" ) );
			add_settings_field( "single_{$post_type->name}_description", __( 'Meta Description Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'single_' . $post_type->name, array( 'type' => 'textarea', 'field' => "single_{$post_type->name}_description" ) );
			add_settings_field( "single_{$post_type->name}_keywords", __( 'Meta Keywords Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'single_' . $post_type->name, array( 'field' => "single_{$post_type->name}_keywords" ) );
		}

		foreach ( $this->archived_post_types as $post_type ) {
			/* translators: %s: post type singular name */
			add_settings_section( 'archive_' . $post_type->name, sprintf( __( '%s Archives', 'wp-seo' ), $post_type->labels->singular_name ), array( $this, 'example_post_type_archive' ), $this::SLUG );
			add_settings_field( "archive_{$post_type->name}_title", __( 'Title Tag Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'archive_' . $post_type->name, array( 'field' => "archive_{$post_type->name}_title" ) );
			add_settings_field( "archive_{$post_type->name}_description", __( 'Meta Description Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'archive_' . $post_type->name, array( 'type' => 'textarea', 'field' => "archive_{$post_type->name}_description" ) );
			add_settings_field( "archive_{$post_type->name}_keywords", __( 'Meta Keywords Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'archive_' . $post_type->name, array( 'field' => "archive_{$post_type->name}_keywords" ) );
		}

		// Post Formats have no UI, so they cannot have per-term fields.
		add_settings_section( 'taxonomies', __( 'Taxonomies', 'wp-seo' ), '__return_false', $this::SLUG );
		add_settings_field( 'taxonomies', __( 'Add SEO fields to individual:', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'taxonomies', array( 'field' => 'taxonomies', 'type' => 'checkboxes', 'boxes' => call_user_func_array( 'wp_list_pluck', array( array_diff_key( $this->taxonomies, array( 'post_format' => true ) ), 'label' ) ) ) );

		foreach ( $this->taxonomies as $taxonomy ) {
			/* translators: %s: taxonomy singular name */
			add_settings_section( 'archive_' . $taxonomy->name, sprintf( __( '%s Archives', 'wp-seo' ), $taxonomy->labels->singular_name ), array( $this, 'example_term_archive' ), $this::SLUG );
			add_settings_field( "archive_{$taxonomy->name}_title", __( 'Title Tag Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'archive_' . $taxonomy->name, array( 'field' => "archive_{$taxonomy->name}_title" ) );
			add_settings_field( "archive_{$taxonomy->name}_description", __( 'Meta Description Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'archive_' . $taxonomy->name, array( 'type' => 'textarea', 'field' => "archive_{$taxonomy->name}_description" ) );
			add_settings_field( "archive_{$taxonomy->name}_keywords", __( 'Meta Keywords Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'archive_' . $taxonomy->name, array( 'field' => "archive_{$taxonomy->name}_keywords" ) );
		}

		add_settings_section( 'archive_author', __( 'Author Archives', 'wp-seo' ), array( $this, 'example_author_archive' ), $this::SLUG );
		add_settings_field( 'archive_author_title', __( 'Title Tag Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'archive_author', array( 'field' => 'archive_author_title' ) );
		add_settings_field( 'archive_author_description', __( 'Meta Description Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'archive_author', array( 'type' => 'textarea', 'field' => 'archive_author_description' ) );
		add_settings_field( 'archive_author_keywords', __( 'Meta Keywords Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'archive_author', array( 'field' => 'archive_author_keywords' ) );

		add_settings_section( 'archive_date', __( 'Date Archives', 'wp-seo' ), array( $this, 'example_date_archive' ), $this::SLUG );
		add_settings_field( 'archive_date_title', __( 'Title Tag Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'archive_date', array( 'field' => 'archive_date_title' ) );
		add_settings_field( 'archive_date_description', __( 'Meta Description Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'archive_date', array( 'type' => 'textarea', 'field' => 'archive_date_description' ) );
		add_settings_field( 'archive_date_keywords', __( 'Meta Keywords Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'archive_date', array( 'field' => 'archive_date_keywords' ) );

		add_settings_section( 'search', __( 'Search Results', 'wp-seo' ), array( $this, 'example_search_page' ), $this::SLUG );
		add_settings_field( 'search_title', __( 'Title Tag Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'search', array( 'field' => 'search_title' ) );

		add_settings_section( '404', __( '404 Page', 'wp-seo' ), array( $this, 'example_404_page' ), $this::SLUG );
		add_settings_field( '404_title', __( 'Title Tag Format', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, '404', array( 'field' => '404_title' ) );

		add_settings_section( 'arbitrary', __( 'Other Meta Tags', 'wp-seo' ), false, $this::SLUG );
		add_settings_field( 'arbitrary_tags', __( 'Tags', 'wp-seo' ), array( $this, 'field' ), $this::SLUG, 'arbitrary', array( 'type' => 'repeatable', 'field' => 'arbitrary_tags', 'repeat' => array( 'name' => __( 'Name', 'lin' ), 'content' => __( 'Content', 'lin' ) ) ) );
	}

	/**
	 * Convenience wrapper for the translated "for example" prefix.
	 *
	 * Used often in settings descriptions.
	 *
	 * @return string.
	 */
	public function ex_text() {
		return __( 'ex.', 'wp-seo' );
	}

	/**
	 * Display a field description that includes a URL.
	 *
	 * For showing an example of a URL on which a group of SEO fields applies
	 * or indicating that the fields don't apply anywhere yet.
	 *
	 * @param string      $text The text to show before the URL.
	 * @param bool|string $url  The URL to show, or false if none exists.
	 */
	public function example_url( $text, $url = false ) {
		echo '<p class="description">' . esc_html( $text );
		if ( false !== $url ) {
			echo ' <code><a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $url ) . '</a></code>';
		}
		echo '</p>';
	}

	/**
	 * Display an example URL for individual posts.
	 *
	 * The URL is the permalink to the most recent post in the post type.
	 *
	 * @param  array $section An array of settings section data.
	 */
	public function example_permalink( $section ) {
		if ( $post = get_posts( array( 'numberposts' => 1, 'post_type' => str_replace( array( 'single_', 'archive_' ), '', $section['id'] ), 'fields' => 'ids', 'suppress_filters' => false ) ) ) {
			$this->example_url( $this->ex_text(), get_permalink( reset( $post ) ) );
		} else {
			$this->example_url( __( 'No posts yet.', 'wp-seo' ) );
		}
	}

	/**
	 * Display an example URL for term archives.
	 *
	 * The URL is the permalink to the most recent term in the taxonomy.
	 *
	 * @param  array $section An array of settings section data.
	 */
	public function example_term_archive( $section ) {
		if ( $term = get_terms( str_replace( 'archive_', '', $section['id'] ), array( 'number' => 1 ) ) ) {
			$this->example_url( $this->ex_text(), get_term_link( reset( $term ) ) );
		} else {
			$this->example_url( __( 'No terms yet.', 'wp-seo' ) );
		}
	}

	/**
	 * Display an example URL for a post type archive.
	 *
	 * @param  array $section An array of settings section data.
	 */
	public function example_post_type_archive( $section ) {
		if ( $url = get_post_type_archive_link( str_replace( 'archive_', '', $section['id'] ) ) ) {
			$this->example_url( __( 'at ', 'wp-seo' ), $url );
		}
	}

	/**
	 * Display an example URL for a date archive.
	 *
	 * The URL is the permalink to the current month's archives.
	 */
	public function example_date_archive() {
		$this->example_url( $this->ex_text(), get_month_link( false, false ) );
	}

	/**
	 * Display an example URL for an author archive.
	 *
	 * The URL is the permalink to the current user's archives.
	 */
	public function example_author_archive() {
		$this->example_url( $this->ex_text(), get_author_posts_url( get_current_user_id() ) );
	}

	/**
	 * Display an example URL for a search page.
	 */
	public function example_search_page() {
		$this->example_url( $this->ex_text(), get_search_link( 'wordpress' ) );
	}

	/**
	 * Display an example URL for a 404 page.
	 *
	 * We can only assume this page actually doesn't exist.
	 */
	public function example_404_page() {
		$this->example_url( $this->ex_text(), trailingslashit( get_bloginfo( 'url' ) ) . md5( get_bloginfo( 'url' ) ) );
	}

	/**
	 * Helper for gathering arguments to send to a field-rendering method.
	 *
	 * @param  array $args {
	 *     An array of arguments for the type and details of the field.
	 *
	 *     @type  string $field The field name, used as the key in the option.
	 *     @type  string $type  The field type. Use "textarea", "checkboxes", or
	 *                          "repeatable" to call the relevant method; all
	 *                          others become the "type" in render_text_field().
	 *     @type  mixed         Optional. Other args for the render methods.
	 *                          Refer to method called based on $type.
	 * }
	 */
	public function field( $args ) {
		if ( empty( $args['field'] ) ) {
			return;
		}

		if ( empty( $args['type'] ) ) {
			$args['type'] = 'text';
		}

		$value = ! empty( $this->options[ $args['field'] ] ) ? $this->options[ $args['field'] ] : '';

		switch ( $args['type'] ) {
			case 'textarea' :
				$this->render_textarea( $args, $value );
				break;

			case 'checkboxes' :
				$this->render_checkboxes( $args, $value );
				break;

			case 'repeatable' :
				$this->render_repeatable_field( $args, $value );
				break;

			default :
				$this->render_text_field( $args, $value );
				break;
		}
	}

	/**
	 * Render a settings text field.
	 *
	 * @param array  $args {
	 *     An array of arguments for the text field.
	 *
	 *     @type string $field  The field name.
	 *     @type string $type   The field type. Default 'text'.
	 *     @type string $size   The field size. Default 80.
	 * }
	 * @param string $value The current field value.
	 */
	public function render_text_field( $args, $value ) {
		$args = wp_parse_args( $args, array(
			'type' => 'text',
			'size' => 80,
		) );

		printf(
			'<input type="%s" name="%s[%s]" value="%s" size="%s" />',
			esc_attr( $args['type'] ),
			esc_attr( $this::SLUG ),
			esc_attr( $args['field'] ),
			esc_attr( $value ),
			esc_attr( $args['size'] )
		);
	}

	/**
	 * Render a settings textarea.
	 *
	 * @param array  $args {
	 *     An array of arguments for the textarea.
	 *
	 *     @type  string $field The field name.
	 *     @type  int    $rows  Rows in the textarea. Default 2.
	 *     @type  int    $cols  Columns in the textarea. Default 80.
	 * }
	 * @param string $value The current field value.
	 */
	public function render_textarea( $args, $value ) {
		$args = wp_parse_args( $args, array(
			'rows' => 2,
			'cols' => 80,
		) );

		printf(
			'<textarea name="%s[%s]" rows="%d" cols="%d">%s</textarea>',
			esc_attr( $this::SLUG ),
			esc_attr( $args['field'] ),
			esc_attr( $args['rows'] ),
			esc_attr( $args['cols'] ),
			esc_textarea( $value )
		);
	}

	/**
	 * Render settings checkboxes.
	 *
	 * @param  array $args {
	 *     An array of arguments for the checkboxes.
	 *
	 *     @type string $field The field name.
	 *     @type array  $boxes An associative array of the value and label
	 *                         of each checkbox.
	 * }
	 * @param  array $values Indexed array of current field values.
	 */
	public function render_checkboxes( $args, $values ) {
		foreach ( $args['boxes'] as $box_value => $box_label ) {
			printf( '
					<label for="%1$s_%2$s_%3$s">
						<input id="%1$s_%2$s_%3$s" type="checkbox" name="%1$s[%2$s][]" value="%3$s" %4$s>
						%5$s
					</label><br>',
				esc_attr( $this::SLUG ),
				esc_attr( $args['field'] ),
				esc_attr( $box_value ),
				is_array( $values ) ? checked( in_array( $box_value, $values ), true, false ) : '',
				esc_html( $box_label )
			);
		}
	}

	/**
	 * Render a repeatble text field.
	 *
	 * @param  array $args {
	 *     An array of arguments for setting up the repeatable fields.
	 *
	 *     @type string $field  The field name.
	 *     @type array  $repeat Associative array of field names and labels to
	 *                          include in each repeated instance of the field.
	 *     @type string $size   Optional. The field size. Default 70.
	 * }
	 * @param  array $values The current field values.
	 */
	public function render_repeatable_field( $args, $values ) {
		$args = wp_parse_args( $args, array(
			'size' => 70,
		) );
		$data_start = ( 0 === count( $values ) ) ? 1 : count( $values );
		?>
			<div class="wp-seo-repeatable">
				<div class="nodes">
					<?php if ( ! empty( $values ) ) : ?>
						<?php foreach ( (array) $values as $i => $group ) : ?>
							<div class="wp-seo-repeatable-group">
								<?php foreach ( $group as $name => $value ) : ?>
									<div class="wp-seo-repeatable-field">
										<?php
											printf( '
												<label for="%1$s_%2$s_%3$s_%4$s">
													%5$s
												</label>
												<input class="repeatable" type="text" id="%1$s_%2$s_%3$s_%4$s" name="%1$s[%2$s][%3$s][%4$s]" size="%6$s" value="%7$s" />',
												esc_attr( $this::SLUG ),
												esc_attr( $args['field'] ),
												intval( $i ),
												esc_attr( $name ),
												esc_attr( $args['repeat'][ $name ] ),
												esc_attr( $args['size'] ),
												esc_attr( $value )
											);
										?>
									</div><!-- .wp-seo-repeatable-field -->
								<?php endforeach; ?>
							</div><!-- .wp-seo-repeatable-group -->
						<?php endforeach; ?>
					<?php else : ?>
						<div class="wp-seo-repeatable-group">
							<?php foreach ( $args['repeat'] as $name => $label ) : ?>
								<div class="wp-seo-repeatable-field">
									<?php
										printf( '
											<label for="%1$s_%2$s_%3$s_%4$s">
												%5$s
											</label>
											<input class="repeatable" type="text" id="%1$s_%2$s_%3$s_%4$s" name="%1$s[%2$s][%3$s][%4$s]" size="%6$s" value="%7$s" />',
											esc_attr( $this::SLUG ),
											esc_attr( $args['field'] ),
											0,
											esc_attr( $name ),
											esc_attr( $label ),
											esc_attr( $args['size'] ),
											''
										);
									?>
								</div><!-- .wp-seo-repeatable-field -->
							<?php endforeach; ?>
						</div><!-- .wp-seo-repeatable-group -->
					<?php endif; ?>
				</div><!-- .nodes -->

				<script type="text/template" class="wp-seo-template" data-start="<?php echo absint( $data_start ); ?>">
					<div class="wp-seo-repeatable-group">
						<?php foreach ( $args['repeat'] as $name => $label ) : ?>
							<div class="wp-seo-repeatable-field">
								<?php
									printf( '
										<label for="%1$s_%2$s_%3$s_%4$s">
											%5$s
										</label>
										<input class="repeatable" type="text" id="%1$s_%2$s_%3$s_%4$s" name="%1$s[%2$s][%3$s][%4$s]" size="%6$s" value="%7$s" />',
										esc_attr( $this::SLUG ),
										esc_attr( $args['field'] ),
										'<%= i %>',
										esc_attr( $name ),
										esc_attr( $label ),
										esc_attr( $args['size'] ),
										''
									);
								?>
							</div><!-- .wp-seo-repeatable-field -->
						<?php endforeach; ?>
						<a href="#" class="wp-seo-delete"><%= wp_seo_admin.repeatable_remove_label %></a>
					</div><!-- .wp-seo-repeatable-group -->
				</script>
			</div><!-- .wp-seo-repeatable -->
		<?php
	}

	/**
	 * Render the settings page.
	 */
	public function view_settings_page() {
		?>
			<div class="wrap" id="wp_seo_settings">
				<h2><?php esc_html_e( 'WP SEO Settings', 'wp-seo' ); ?></h2>
				<form action="options.php" method="POST">
					<?php settings_fields( $this::SLUG ); ?>
					<?php
					/**
					 * Filter the type of UI to use with settings sections.
					 *
					 * @param  bool Whether to enhance the page with accordions.
					 */
					if ( apply_filters( 'wp_seo_use_settings_accordions', true ) ) {
						global $wp_settings_sections;
						foreach ( (array) $wp_settings_sections[ $this::SLUG ] as $section ) {
							add_meta_box( $section['id'], $section['title'], array( $this, 'settings_meta_box' ), 'wp-seo', 'advanced', 'default', $section );
						}
						do_accordion_sections( 'wp-seo', 'advanced', null );
					} else {
						do_settings_sections( $this::SLUG );
					}
					?>
					<?php submit_button(); ?>
				</form>
			</div>
		<?php
	}

	/**
	 * Render a section's fields as a meta box.
	 *
	 * @param  mixed $object Unused. Data passed from do_accordion_sections().
	 * @param  array $box {
	 *     An array of meta box arguments.
	 *
	 *     @type  string $id @see add_meta_box().
	 *     @type  string $title @see add_meta_box().
	 *     @type  callback $callback @see add_meta_box().
	 *     @type  array $args @see add_meta_box(), add_settings_section().
	 * }
	 */
	public function settings_meta_box( $object, $box ) {
		if ( is_callable( $box['args']['callback'] ) ) {
			call_user_func( $box['args']['callback'], $box['args'] );
		}

		echo '<table class="form-table">';
		do_settings_fields( $this::SLUG, $box['args']['id'] );
		echo '</table>';
	}

	/**
	 * Sanitize and validate the submitted options.
	 *
	 * @param  array $in The options as submitted.
	 * @return array     The options, sanitized.
	 */
	public function sanitize_options( $in ) {
		$out = $this->default_options;

		// Validate post types and taxonomies on which to show SEO fields.
		$out['post_types'] = isset( $in['post_types'] ) && is_array( $in['post_types'] ) ? array_filter( $in['post_types'], 'post_type_exists' ) : array();
		$out['taxonomies'] = isset( $in['taxonomies'] ) && is_array( $in['taxonomies'] ) ? array_filter( $in['taxonomies'], 'taxonomy_exists' ) : array();

		/**
		 * Sanitize these as text fields and in the following order:
		 */

		// Home description and keywords.
		$sanitize_as_text_field = array(
			'home_title',
			'home_description',
			'home_keywords',
		);
		// Single post default formats.
		foreach ( $this->single_post_types as $type ) {
			$sanitize_as_text_field[] = "single_{$type->name}_title";
			$sanitize_as_text_field[] = "single_{$type->name}_description";
			$sanitize_as_text_field[] = "single_{$type->name}_keywords";
		}
		// Post type, taxonomy, and other archives.
		foreach ( array_merge( $this->archived_post_types, $this->taxonomies, array( 'author', 'date' ) ) as $type ) {
			if ( is_object( $type ) ) {
				$type = $type->name;
			}
			$sanitize_as_text_field[] = "archive_{$type}_title";
			$sanitize_as_text_field[] = "archive_{$type}_description";
			$sanitize_as_text_field[] = "archive_{$type}_keywords";
		}
		// "Other Pages" titles.
		$sanitize_as_text_field[] = 'search_title';
		$sanitize_as_text_field[] = '404_title';

		foreach ( $sanitize_as_text_field as $field ) {
			$out[ $field ] = isset( $in[ $field ] ) && is_string( $in[ $field ] ) ? sanitize_text_field( $in[ $field ] ) : null;
		}

		/**
		 * Sanitize repeatable fields, also as text fields:
		 */

		$repeatables = array(
			'arbitrary_tags' => array( 'name', 'content' ),
		);

		foreach ( $repeatables as $repeatable => $fields ) {
			$out[ $repeatable ] = array();
			if ( isset( $in[ $repeatable ] ) ) {
				foreach ( array_values( $in[ $repeatable ] ) as $i => $group ) {
					foreach ( $fields as $field ) {
						$out[ $repeatable ][ $i ][ $field ] = isset( $group[ $field ] ) ? sanitize_text_field( $group[ $field ] ) : null;
					}
					// Remove empty values from this group of fields.
					$out[ $repeatable ][ $i ] = array_filter( $out[ $repeatable ][ $i ] );
				}
				// Remove groups with only empty fields.
				$out[ $repeatable ] = array_filter( $out[ $repeatable ] );
			}
		}

		return $out;
	}

	/**
	 * Render the content of the "Formatting Tags" help tab.
	 *
	 * The tab displays a table of each available formatting tab and any
	 * provided description.
	 */
	public function view_formatting_tags_help_tab() {
		$formatting_tags = WP_SEO()->formatting_tags;
		if ( ! empty( $formatting_tags ) ) :
			?>
			<aside>
				<h1><?php esc_html_e( 'These Formatting Tags are available', 'wp-seo' ); ?></h1>
				<dl class="formatting-tags">
					<?php foreach ( $formatting_tags as $tag ) : ?>
						<div class="formatting-tag-wrapper">
							<dt class="formatting-tag-name"><?php echo esc_html( $tag->tag ); ?></dt>
							<dd class="formatting-tag-description"><?php echo esc_html( $tag->get_description() ); ?></dd>
						</div><!-- .formatting-tag-wrapper -->
					<?php endforeach; ?>
				</dl>
			</aside>
			<?php
		endif;
	}
}

/**
 * Helper function to use the class instance.
 *
 * @return object
 */
function wp_seo_settings() {
	return WP_SEO_Settings::instance();
}
add_action( 'after_setup_theme', 'WP_SEO_Settings', 9 );
