<?php
/**
 * Class file for WP_SEO_Settings_Page.
 *
 * @package WP_SEO
 */

/**
 * Creates the Dashboard settings page.
 */
class WP_SEO_Settings_Page extends WP_SEO_Administration {
	/**
	 * Set up.
	 */
	protected function setup() {
		parent::setup();

		$this->register_setting();
		$this->register_settings_page();

		add_action( 'load-settings_page_wp-seo', array( $this, 'load_settings_page' ) );
	}

	/**
	 * Fires before the plugin screen callback loads.
	 */
	public function load_settings_page() {
		$this->register_help_tab();
		$this->register_home_settings();
		$this->register_single_post_type_settings();
		$this->register_post_type_archive_settings();
		$this->register_add_fields_to_individual_post_type_settings();
		$this->register_taxonomy_archive_settings();
		$this->register_add_fields_to_individual_term_settings();
		$this->register_author_archive_settings();
		$this->register_date_archive_settings();
		$this->register_search_settings();
		$this->register_404_settings();
		$this->register_arbitrary_tag_settings();
	}

	/**
	 * Settings page content display callback.
	 */
	public function do_settings_page() {
		$this->render_settings_page();
	}

	/**
	 * Settings page help tab callback.
	 */
	public function do_help_tab() {
		$this->render_help_tab();
	}

	/**
	 * Render a settings section's fields as a meta box.
	 *
	 * @param mixed $object Unused. Data passed from do_accordion_sections().
	 * @param array $box {
	 *     An array of meta box arguments.
	 *
	 *     @type string $id @see add_meta_box().
	 *     @type string $title @see add_meta_box().
	 *     @type callback $callback @see add_meta_box().
	 *     @type array $args @see add_meta_box(), add_settings_section().
	 * }
	 */
	public function do_meta_box( $object, $box ) {
		if ( is_callable( $box['args']['callback'] ) ) {
			call_user_func( $box['args']['callback'], $box['args'] );
		}

		echo '<table class="form-table">';
			do_settings_fields( 'wp-seo', $box['args']['id'] );
		echo '</table>';
	}

	/**
	 * Default single-post settings section display callback.
	 *
	 * @param array $section Settings section arguments. @see add_settings_section().
	 */
	public function do_single_post_type_settings_section( $section ) {
		$link = $this->get_example_post_permalink( str_replace( 'single_', '', $section['id'] ) );

		if ( ! $link ) {
			$this->render_no_example_link();
			return;
		}

		$this->render_example_link( $link );
	}

	/**
	 * Post type archive settings section display callback.
	 *
	 * @param array $section Settings section arguments. @see add_settings_section().
	 */
	public function do_post_type_archive_settings_section( $section ) {
		$link = $this->get_example_post_type_archive_link( str_replace( 'archive_', '', $section['id'] ) );

		if ( ! $link ) {
			$this->render_no_example_link();
			return;
		}

		$this->render_example_link( $link );
	}

	/**
	 * Taxonomy archive settings section display callback.
	 *
	 * @param array $section Settings section arguments. @see add_settings_section().
	 */
	public function do_taxonomy_archive_settings_section( $section ) {
		$link = $this->get_example_taxonomy_archive_link( str_replace( 'archive_', '', $section['id'] ) );

		if ( ! $link ) {
			$this->render_no_example_link();
			return;
		}

		$this->render_example_link( $link );
	}

	/**
	 * Author archive settings section display callback.
	 *
	 * @param array $section Settings section arguments. @see add_settings_section().
	 */
	public function do_author_archive_settings_section( $section ) {
		$this->render_example_link( $this->get_example_author_archive_link() );
	}

	/**
	 * Date archive settings section display callback.
	 *
	 * @param array $section Settings section arguments. @see add_settings_section().
	 */
	public function do_date_archive_settings_section( $section ) {
		$this->render_example_link( $this->get_example_date_archive_link() );
	}

	/**
	 * Search page settings section display callback.
	 *
	 * @param array $section Settings section arguments. @see add_settings_section().
	 */
	public function do_search_settings_section( $section ) {
		$this->render_example_link( $this->get_example_search_page_link() );
	}

	/**
	 * 404 page settings section display callback.
	 *
	 * @param array $section Settings section arguments. @see add_settings_section().
	 */
	public function do_404_settings_section( $section ) {
		$this->render_example_link( $this->get_example_404_page_link() );
	}

	/**
	 * Text settings field display callback.
	 *
	 * @param array $args Rendering arguments. @see WP_SEO_Settings_Page::render_text_field().
	 */
	public function do_text_field( $args ) {
		$this->render_text_field( $args['name'], $args['label_for'], $args['value'] );
	}

	/**
	 * Textarea settings field display callback.
	 *
	 * @param array $args Rendering arguments. @see WP_SEO_Settings_Page::render_textarea().
	 */
	public function do_textarea( $args ) {
		$this->render_textarea( $args['name'], $args['label_for'], $args['value'] );
	}

	/**
	 * One-checkbox-per-object settings field display callback.
	 *
	 * @param array $args Rendering arguments. @see WP_SEO_Settings::render_checkboxes_for_objects().
	 */
	public function do_checkboxes_for_objects( $args ) {
		$this->render_checkboxes_for_objects( $args['name'], $args['value'], $args['choices'] );
	}

	/**
	 * Arbitrary-tags settings field display callback.
	 *
	 * The `name` attributes used here don't match their structure in the option
	 * value, but they're way nicer to look at. The values are converted to the
	 * correct structure later. @see WP_SEO_Settings_Page::sanitize_option().
	 *
	 * @param array $args {
	 *     Rendering arguments.
	 *
	 *     @param array $value Arrays of arbitrary-tag 'name' and 'content' values.
	 * }
	 */
	public function do_arbitrary_tags_field( $args ) {
		$args['value'] = (array) $args['value'];
		// One more for the next empty value.
		$args['value'][] = array( 'name' => '', 'content' => '' );
		?>
			<div class="wp-seo-arbitrary-tags wp-seo-repeatable">
				<?php // Template inside .wp-seo-repeatable. ?>
				<script type="text/template" class="wp-seo-template" id="wp-seo-arbitrary-tags-template">
					<div class="wp-seo-arbitrary-tag wp-seo-repeated">
						<div>
							<label><?php esc_html_e( 'Name', 'wp-seo' ); ?></label>
							<?php $this->render_text_field( '[arbitrary_tags][name][]', '', '' ); ?>
						</div>

						<div>
							<label><?php esc_html_e( 'Content', 'wp-seo' ); ?></label>
							<?php $this->render_text_field( '[arbitrary_tags][content][]', '', '' ); ?>
						</div>
					</div>
				</script>

				<?php foreach ( $args['value'] as $i => $group ) : ?>
					<div class="wp-seo-arbitrary-tag wp-seo-repeated">
						<div>
							<label for="<?php echo esc_attr( "arbitrary-{$i}-name" ); ?>"><?php esc_html_e( 'Name', 'wp-seo' ); ?></label>
							<?php $this->render_text_field( '[arbitrary_tags][name][]', "arbitrary-{$i}-name", $group['name'] ); ?>
						</div>

						<div>
							<label for="<?php echo esc_attr( "arbitrary-{$i}-content" ); ?>"><?php esc_html_e( 'Content', 'wp-seo' ); ?></label>
							<?php $this->render_text_field( '[arbitrary_tags][content][]', "arbitrary-{$i}-content", $group['content'] ); ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php
	}

	/**
	 * Callback for sanitizing values from the submitted settings page form.
	 *
	 * @param mixed $in The settings as submitted.
	 * @return array The sanitized options.
	 */
	public function sanitize_option( $in ) {
		if ( isset( $in['arbitrary_tags'] ) ) {
			$in['arbitrary_tags'] = $this->convert_submitted_arbitrary_tags( $in['arbitrary_tags'] );
		}

		return wp_seo_settings()->sanitize_options( $in );
	}

	/**
	 * Register the Settings submenu page.
	 */
	private function register_settings_page() {
		add_options_page(
			__( 'SEO Settings', 'wp-seo' ),
			__( 'SEO', 'wp-seo' ),
			$this->options_capability,
			'wp-seo',
			array( $this, 'do_settings_page' )
		);
	}

	/**
	 * Register the plugin setting and sanitization callback.
	 */
	private function register_setting() {
		register_setting( 'wp-seo', 'wp-seo', array( $this, 'sanitize_option' ) );
	}

	/**
	 * Register the formatting-tags help tab.
	 */
	private function register_help_tab() {
		get_current_screen()->add_help_tab( array(
			'id'       => 'formatting-tags',
			'title'    => __( 'Formatting Tags', 'wp-seo' ),
			'callback' => array( $this, 'do_help_tab' ),
		) );
	}

	/**
	 * Render the content of the "Formatting Tags" help tab.
	 *
	 * The tab displays a table of each available formatting tab and any
	 * provided description.
	 */
	private function render_help_tab() {
		$formatting_tags = WP_SEO_Formatting_Tag_Collection::instance()->get_all();
		if ( ! empty( $formatting_tags ) ) :
			?>
				<aside>
					<h3><?php esc_html_e( 'These Formatting Tags are available', 'wp-seo' ); ?></h3>
					<dl class="formatting-tags">
						<?php foreach( $formatting_tags as $tag ) : ?>
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

	/**
	 * Register the home page settings section and fields.
	 */
	private function register_home_settings() {
		$section = 'home';

		add_settings_section( $section, __( 'Home Page', 'wp-seo' ), '__return_empty_string', 'wp-seo' );

		add_settings_field(
			'home_title',
			__( 'Title Tag Format', 'wp-seo' ),
			array( $this, 'do_text_field' ),
			'wp-seo',
			'home',
			array(
				'name' => '[home_title]',
				'value' => wp_seo_settings()->get_option( 'home_title' ),
				'label_for' => 'home_title',
			)
		);

		add_settings_field(
			'home_description',
			__( 'Meta Description Format', 'wp-seo' ),
			array( $this, 'do_textarea' ),
			'wp-seo',
			'home',
			array(
				'name' => '[home_description]',
				'value' => wp_seo_settings()->get_option( 'home_description' ),
				'label_for' => 'home_description',
			)
		);

		add_settings_field(
			'home_keywords',
			__( 'Meta Keywords Format', 'wp-seo' ),
			array( $this, 'do_text_field' ),
			'wp-seo',
			'home',
			array(
				'name' => '[home_keywords]',
				'value' => wp_seo_settings()->get_option( 'home_keywords' ),
				'label_for' => 'home_keywords',
			)
		);
	}

	/**
	 * Register the single-post settings section and fields for all post types.
	 */
	private function register_single_post_type_settings() {
		$post_types = array_map(
			'get_post_type_object',
			wp_seo_settings()->get_available_single_post_types()
		);

		foreach ( $post_types as $post_type ) {
			$section = "single_{$post_type->name}";

			add_settings_section(
				$section,
				sprintf( __( 'Single %s Defaults', 'wp-seo' ), $post_type->labels->singular_name ),
				array( $this, 'do_single_post_type_settings_section' ),
				'wp-seo'
			);

			add_settings_field(
				"single_{$post_type->name}_title",
				__( 'Title Tag Format', 'wp-seo' ),
				array( $this, 'do_text_field' ),
				'wp-seo',
				"single_{$post_type->name}",
				array(
					'name' => "[single_{$post_type->name}_title]",
					'value' => wp_seo_settings()->get_option( "single_{$post_type->name}_title" ),
					'label_for' => "single_{$post_type->name}_title",
				)
			);

			add_settings_field(
				"single_{$post_type->name}_description",
				__( 'Meta Description Format', 'wp-seo' ),
				array( $this, 'do_textarea' ),
				'wp-seo',
				"single_{$post_type->name}",
				array(
					'name' => "[single_{$post_type->name}_description]",
					'value' => wp_seo_settings()->get_option( "single_{$post_type->name}_description" ),
					'label_for' => "single_{$post_type->name}_description",
				)
			);

			add_settings_field(
				"single_{$post_type->name}_keywords",
				__( 'Meta Keywords Format', 'wp-seo' ),
				array( $this, 'do_text_field' ),
				'wp-seo',
				"single_{$post_type->name}",
				array(
					'name' => "[single_{$post_type->name}_keywords]",
					'value' => wp_seo_settings()->get_option( "single_{$post_type->name}_keywords" ),
					'label_for' => "single_{$post_type->name}_keywords",
				)
			);
		}
	}

	/**
	 * Register the archive settings section and fields for all post types.
	 */
	private function register_post_type_archive_settings() {
		$post_types = array_map(
			'get_post_type_object',
			wp_seo_settings()->get_available_archive_post_types()
		);

		foreach ( $post_types as $post_type ) {
			$section = "archive_{$post_type->name}";

			add_settings_section(
				$section,
				sprintf( __( 'Archive %s Defaults', 'wp-seo' ), $post_type->labels->singular_name ),
				array( $this, 'do_post_type_archive_settings_section' ),
				'wp-seo'
			);

			add_settings_field(
				"archive_{$post_type->name}_title",
				__( 'Title Tag Format', 'wp-seo' ),
				array( $this, 'do_text_field' ),
				'wp-seo',
				"archive_{$post_type->name}",
				array(
					'name' => "[archive_{$post_type->name}_title]",
					'value' => wp_seo_settings()->get_option( "archive_{$post_type->name}_title" ),
					'label_for' => "archive_{$post_type->name}_title",
				)
			);

			add_settings_field(
				"archive_{$post_type->name}_description",
				__( 'Meta Description Format', 'wp-seo' ),
				array( $this, 'do_textarea' ),
				'wp-seo',
				"archive_{$post_type->name}",
				array(
					'name' => "[archive_{$post_type->name}_description]",
					'value' => wp_seo_settings()->get_option( "archive_{$post_type->name}_description" ),
					'label_for' => "archive_{$post_type->name}_description",
				)
			);

			add_settings_field(
				"archive_{$post_type->name}_keywords",
				__( 'Meta Keywords Format', 'wp-seo' ),
				array( $this, 'do_text_field' ),
				'wp-seo',
				"archive_{$post_type->name}",
				array(
					'name' => "[archive_{$post_type->name}_keywords]",
					'value' => wp_seo_settings()->get_option( "archive_{$post_type->name}_keywords" ),
					'label_for' => "archive_{$post_type->name}_keywords",
				)
			);
		}
	}

	/**
	 * Register the settings section and fields for allowing per-post SEO fields.
	 */
	private function register_add_fields_to_individual_post_type_settings() {
		add_settings_section( 'post_types', __( 'Individual Posts', 'wp-seo' ), '__return_empty_string', 'wp-seo' );

		add_settings_field(
			'post_types',
			__( 'Add SEO fields to individual:', 'wp-seo' ),
			array( $this, 'do_checkboxes_for_objects' ),
			'wp-seo',
			'post_types',
			array(
				'name' => '[post_types]',
				'value' => wp_seo_settings()->get_option( 'post_types' ),
				'choices' => wp_list_pluck(
					array_map(
						'get_post_type_object',
						wp_seo_settings()->get_available_single_post_types()
					),
					'label',
					'name'
				),
			)
		);
	}

	/**
	 * Register the archive settings section and fields for all taxonomies.
	 */
	private function register_taxonomy_archive_settings() {
		$taxonomies = array_map(
			'get_taxonomy',
			wp_seo_settings()->get_available_taxonomies()
		);

		foreach ( $taxonomies as $taxonomy ) {
			$section = "archive_{$taxonomy->name}";

			add_settings_section(
				$section,
				sprintf( __( '%s Archive Defaults', 'wp-seo' ), $taxonomy->labels->singular_name ),
				array( $this, 'do_taxonomy_archive_settings_section' ),
				'wp-seo'
			);

			add_settings_field(
				"archive_{$taxonomy->name}_title",
				__( 'Title Tag Format', 'wp-seo' ),
				array( $this, 'do_text_field' ),
				'wp-seo',
				"archive_{$taxonomy->name}",
				array(
					'name' => "[archive_{$taxonomy->name}_title]",
					'value' => wp_seo_settings()->get_option( "archive_{$taxonomy->name}_title" ),
					'label_for' => "archive_{$taxonomy->name}_title",
				)
			);

			add_settings_field(
				"archive_{$taxonomy->name}_description",
				__( 'Meta Description Format', 'wp-seo' ),
				array( $this, 'do_textarea' ),
				'wp-seo',
				"archive_{$taxonomy->name}",
				array(
					'name' => "[archive_{$taxonomy->name}_description]",
					'value' => wp_seo_settings()->get_option( "archive_{$taxonomy->name}_description" ),
					'label_for' => "archive_{$taxonomy->name}_description",
				)
			);

			add_settings_field(
				"archive_{$taxonomy->name}_keywords",
				__( 'Meta Keywords Format', 'wp-seo' ),
				array( $this, 'do_text_field' ),
				'wp-seo',
				"archive_{$taxonomy->name}",
				array(
					'name' => "[archive_{$taxonomy->name}_keywords]",
					'value' => wp_seo_settings()->get_option( "archive_{$taxonomy->name}_keywords" ),
					'label_for' => "archive_{$taxonomy->name}_keywords",
				)
			);
		}
	}

	/**
	 * Register the settings section and fields for allowing per-term SEO fields.
	 */
	private function register_add_fields_to_individual_term_settings() {
		add_settings_section( 'taxonomies', __( 'Individual Terms', 'wp-seo' ), '__return_empty_string', 'wp-seo' );

		add_settings_field(
			'taxonomies',
			__( 'Add SEO fields to individual:', 'wp-seo' ),
			array( $this, 'do_checkboxes_for_objects' ),
			'wp-seo',
			'taxonomies',
			array(
				'name' => '[taxonomies]',
				'value' => wp_seo_settings()->get_option( 'taxonomies' ),
				'choices' => wp_list_pluck(
					array_map(
						'get_taxonomy',
						wp_seo_settings()->get_available_taxonomies()
					),
					'label',
					'name'
				),
			)
		);
	}

	/**
	 * Register the author archive settings section and fields.
	 */
	private function register_author_archive_settings() {
		add_settings_section(
			'archive_author',
			__( 'Author Archives', 'wp-seo' ),
			array( $this, 'do_author_archive_settings_section' ),
			'wp-seo'
		);

		add_settings_field(
			'archive_author_title',
			__( 'Title Tag Format', 'wp-seo' ),
			array( $this, 'do_text_field' ),
			'wp-seo',
			'archive_author',
			array(
				'name' => '[archive_author_title]',
				'value' => wp_seo_settings()->get_option( 'archive_author_title' ),
				'label_for' => 'archive_author_title',
			)
		);

		add_settings_field(
			'archive_author_description',
			__( 'Meta Description Format', 'wp-seo' ),
			array( $this, 'do_textarea' ),
			'wp-seo',
			'archive_author',
			array(
				'name' => '[archive_author_description]',
				'value' => wp_seo_settings()->get_option( 'archive_author_description' ),
				'label_for' => 'archive_author_description',
			)
		);

		add_settings_field(
			'archive_author_keywords',
			__( 'Meta Keywords Format', 'wp-seo' ),
			array( $this, 'do_text_field' ),
			'wp-seo',
			'archive_author',
			array(
				'name' => '[archive_author_keywords]',
				'value' => wp_seo_settings()->get_option( 'archive_author_keywords' ),
				'label_for' => 'archive_author_keywords',
			)
		);
	}

	/**
	 * Register the date archive settings section and fields.
	 */
	private function register_date_archive_settings() {
		add_settings_section(
			'archive_date',
			__( 'Date Archives', 'wp-seo' ),
			array( $this, 'do_date_archive_settings_section' ),
			'wp-seo'
		);

		add_settings_field(
			'archive_date_title',
			__( 'Title Tag Format', 'wp-seo' ),
			array( $this, 'do_text_field' ),
			'wp-seo',
			'archive_date',
			array(
				'name' => '[archive_date_title]',
				'value' => wp_seo_settings()->get_option( 'archive_date_title' ),
				'label_for' => 'archive_date_title',
			)
		);

		add_settings_field(
			'archive_date_description',
			__( 'Meta Description Format', 'wp-seo' ),
			array( $this, 'do_textarea' ),
			'wp-seo',
			'archive_date',
			array(
				'name' => '[archive_date_description]',
				'value' => wp_seo_settings()->get_option( 'archive_date_description' ),
				'label_for' => 'archive_date_description',
			)
		);

		add_settings_field(
			'archive_date_keywords',
			__( 'Meta Keywords Format', 'wp-seo' ),
			array( $this, 'do_text_field' ),
			'wp-seo',
			'archive_date',
			array(
				'name' => '[archive_date_keywords]',
				'value' => wp_seo_settings()->get_option( 'archive_date_keywords' ),
				'label_for' => 'archive_date_keywords',
			)
		);
	}

	/**
	 * Register the search results settings section and field.
	 */
	private function register_search_settings() {
		add_settings_section(
			'search',
			__( 'Search Results', 'wp-seo' ),
			array( $this, 'do_search_settings_section' ),
			'wp-seo'
		);

		add_settings_field(
			'search_title',
			__( 'Title Tag Format', 'wp-seo' ),
			array( $this, 'do_text_field' ),
			'wp-seo',
			'search',
			array(
				'name' => '[search_title]',
				'value' => wp_seo_settings()->get_option( 'search_title' ),
				'label_for' => 'search_title',
			)
		);
	}

	/**
	 * Register the 404 page settings section and field.
	 */
	private function register_404_settings() {
		add_settings_section(
			'404',
			__( '404 Page', 'wp-seo' ),
			array( $this, 'do_404_settings_section' ),
			'wp-seo'
		);

		add_settings_field(
			'404_title',
			__( 'Title Tag Format', 'wp-seo' ),
			array( $this, 'do_text_field' ),
			'wp-seo',
			'404',
			array(
				'name' => '[404_title]',
				'value' => wp_seo_settings()->get_option( '404_title' ),
				'label_for' => '404_title',
			)
		);
	}

	/**
	 * Register the arbitrary tags settings section and field.
	 */
	private function register_arbitrary_tag_settings() {
		add_settings_section( 'arbitrary', __( 'Other Meta Tags', 'wp-seo' ), '__return_empty_string', 'wp-seo' );

		add_settings_field(
			'arbitrary_tags',
			__( 'Tags', 'wp-seo' ),
			array( $this, 'do_arbitrary_tags_field' ),
			'wp-seo',
			'arbitrary',
			array(
				'value' => wp_seo_settings()->get_option( 'arbitrary_tags' )
			)
		);
	}

	/**
	 * Render the settings page content.
	 */
	private function render_settings_page() {
		global $wp_settings_sections;
		?>
			<div class="wrap" id="wp_seo_settings">
				<h1><?php esc_html_e( 'SEO Settings', 'wp-seo' ); ?></h1>
				<form action="options.php" method="POST">
					<?php settings_fields( 'wp-seo' ); ?>
					<?php
						/**
						 * Filter whether to display settings sections with accordions.
						 *
						 * @param bool Default true.
						 */
						$use_accordions = apply_filters( 'wp_seo_use_settings_accordions', true );
						if ( $use_accordions && ! empty( $wp_settings_sections['wp-seo'] ) ) {
							foreach ( (array) $wp_settings_sections['wp-seo'] as $section ) {
								add_meta_box( $section['id'], $section['title'], array( $this, 'do_meta_box' ), 'wp-seo', 'advanced', 'default', $section );
							}
							do_accordion_sections( 'wp-seo', 'advanced', null );
						} else {
							do_settings_sections( 'wp-seo' );
						}
					?>
					<?php submit_button(); ?>
				</form>
			</div>
		<?php
	}

	/**
	 * Render an example link that the settings in a section affect.
	 *
	 * @param string $url The link.
	 */
	private function render_example_link( $url ) {
		printf(
			'<p class="description">%s</p>',
			sprintf(
				esc_html__( 'ex. %s', 'wp-seo' ),
				sprintf(
					'<code><a href="%1$s" target="_blank">%1$s</a></code>',
					esc_url( $url )
				)
			)
		);
	}

	/**
	 * Render a message when no example links for a settings section exist.
	 */
	private function render_no_example_link() {
		printf(
			'<p class="description">%s</p>',
			esc_html__( 'None on this site yet.', 'wp-seo' )
		);
	}

	/**
	 * Render a settings page text field.
	 *
	 * @param string $name The input name attribute to use after 'wp-seo'.
	 * @param string $value The input value attribute.
	 */
	private function render_text_field( $name, $id, $value ) {
		printf(
			'<input name="wp-seo%s" id="%s" value="%s" type="text" size="80" />',
			esc_attr( $name ),
			esc_attr( $id ),
			esc_attr( $value )
		);
	}

	/**
	 * Render a settings page textarea.
	 *
	 * @param string $name The input name attribute to use after 'wp-seo'.
	 * @param string $value The textarea content.
	 */
	private function render_textarea( $name, $id, $value ) {
		printf(
			'<textarea name="wp-seo%s" id="%s" rows="2" cols="80">%s</textarea>',
			esc_attr( $name ),
			esc_attr( $id ),
			esc_textarea( $value )
		);
	}

	/**
	 * Render checkboxes for selecting several objects, such as posts or taxonomies.
	 *
	 * @param string $name The input name attribute to use after 'wp-seo'.
	 * @param array $values Array of values that are already selected.
	 * @param array $choices Associative array of checkbox values and labels.
	 */
	private function render_checkboxes_for_objects( $name, $values, $choices ) {
		foreach ( $choices as $choice => $label ) {
			printf(
				'<p><label for="%2$s"><input type="checkbox" name="wp-seo%1$s[]" id="%2$s" value="%3$s" %4$s>%5$s</label></p>',
				esc_attr( $name ),
				esc_attr( "$name-$choice" ),
				esc_attr( $choice ),
				// Outputs both the attribute name and value.
				wp_strip_all_tags( checked( in_array( $choice, $values ), true, false ) ),
				esc_html( $label )
			);
		}
	}

	/**
	 * Convert submitted arbitrary tag values into the actual option structure.
	 *
	 * Rather than try to match the structure of arbitrary tags actually saved
	 * in the option value, we have the settings page submit separate bundles of
	 * 'name' and 'content' values. Here we check that equal numbers of each
	 * were submitted, then convert them into one indexed array.
	 *
	 * @param array $in {
	 *     Arrays of arbitrary tag names and contents.
	 *
	 *     @type array $name Array of all the submitted arbitrary tag names.
	 *     @type array $content Array of all the submitted arbitrary tag contents.
	 * }
	 * @return array Indexed array of arrays with 'name' and 'content' fields.
	 */
	private function convert_submitted_arbitrary_tags( $in ) {
		$out = array();

		if ( empty( $in['name'] ) ) {
			$in['name'] = array();
		}

		if ( empty( $in['content'] ) ) {
			$in['content'] = array();
		}

		if ( count( $in['name'] ) === count( $in['content'] ) ) {
			foreach ( $in['name'] as $i => $value ) {
				$out[ $i ]['name'] = $value;
				$out[ $i ]['content'] = $in['content'][ $i ];
			}
		}

		return $out;
	}
}
