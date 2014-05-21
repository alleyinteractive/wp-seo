<?php

/**
 * WP-SEO Settings
 */

class WP_SEO_Settings {

	public $options_capability = 'manage_options';

	public $default_options = array( 'post_types' => array() );

	public $options = array();

	private $taxonomies = array();

	private $archived_post_types = array();

	const SLUG = 'wp-seo';

	protected static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WP_SEO_Settings;
			self::$instance->setup_actions();
		}
		return self::$instance;
	}

	protected function __construct() {
		/** Don't do anything **/
	}

	protected function setup_actions() {
		// add_action( 'wp_head', array( self::$instance, 'action_wp_head' ) );

		add_action( 'after_setup_theme', array( self::$instance, 'load_options' ), 5 );
		add_action( 'admin_init', array( self::$instance, 'action_admin_init' ) );

		add_action( 'admin_menu', array( self::$instance, 'action_admin_menu' ) );
	}

	/**
	 * Load the options on demand
	 *
	 * @return void
	 */
	public function load_options() {
		if ( !$this->options )
			$this->options = get_option( self::SLUG, $this->default_options );
	}

	public function action_admin_init() {
		register_setting( self::SLUG, self::SLUG, array( self::$instance, 'sanitize_options' ) );

		add_settings_section( 'general', false, '__return_false', self::SLUG );
		// add_settings_field( 'post_types', __( 'Add meta fields to:', 'wp-seo' ), array( self::$instance, 'field' ), self::SLUG, 'general' );

		add_settings_section( 'content_types', false, '__return_false', self::SLUG );
		add_settings_field( 'post_types', __( 'Add meta fields to:', 'wp-seo' ), array( self::$instance, 'post_types' ), self::SLUG, 'content_types' );

		// add_settings_section( 'archives', false, '__return_false', self::SLUG );

		$this->taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		foreach ( $this->taxonomies as $taxonomy ) {
			add_settings_section( 'archive_' . $taxonomy->name, false, '__return_false', self::SLUG );
			add_settings_field( "archive_{$taxonomy->name}_title", __( 'Meta Title Format', 'wp-seo' ), array( self::$instance, 'text_field' ), self::SLUG, 'archive_' . $taxonomy->name, array( 'field' => "archive_{$taxonomy->name}_title" ) );
			add_settings_field( "archive_{$taxonomy->name}_description", __( 'Meta Description Format', 'wp-seo' ), array( self::$instance, 'text_field' ), self::SLUG, 'archive_' . $taxonomy->name, array( 'field' => "archive_{$taxonomy->name}_description" ) );
		}

		$this->archived_post_types = get_post_types( array( 'has_archive' => true ), 'objects' );

		foreach ( $this->archived_post_types as $post_type ) {
			add_settings_section( 'archive_' . $post_type->name, false, '__return_false', self::SLUG );
			add_settings_field( "archive_{$post_type->name}_title", __( 'Meta Title Format', 'wp-seo' ), array( self::$instance, 'text_field' ), self::SLUG, 'archive_' . $post_type->name, array( 'field' => "archive_{$post_type->name}_title" ) );
			add_settings_field( "archive_{$post_type->name}_description", __( 'Meta Description Format', 'wp-seo' ), array( self::$instance, 'text_field' ), self::SLUG, 'archive_' . $post_type->name, array( 'field' => "archive_{$post_type->name}_description" ) );
		}
	}

	public function do_section( $section_id ) {
		?>
		<div class="wp-seo-tab" id="wp_seo_<?php echo esc_attr( $section_id ) ?>">
			<table class="form-table">
				<?php do_settings_fields( self::SLUG, $section_id ); ?>
			</table>
		</div>
		<?php
	}

	public function action_admin_menu() {
		add_options_page( __( 'WP SEO Settings', 'wp-seo' ), __( 'SEO', 'wp-seo' ), $this->options_capability, self::SLUG, array( self::$instance, 'view_settings_page' ) );
	}

	public function post_types() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_types as $slug => $post_type ) :
			?>
			<label><input type="checkbox" name="<?php echo self::SLUG ?>[post_types][]" value="<?php echo $slug ?>"<?php checked( in_array( $slug, $this->options['post_types'] ) ) ?> /> <?php echo $post_type->label ?></label><br />
			<?php
		endforeach;
	}

	public function text_field( $args ) {
		$args = wp_parse_args( $args, array(
			'type' => 'text'
		) );

		if ( empty( $args['field'] ) ) {
			return;
		}

		$value = ! empty( $this->options[ $args['field'] ] ) ? $this->options[ $args['field'] ] : '';
		printf(
			'<input type="%s" name="%s[%s]" value="%s" size="50" />',
			esc_attr( $args['type'] ),
			esc_attr( self::SLUG ),
			esc_attr( $args['field'] ),
			esc_attr( $value )
		);
	}

	public function sanitize_options( $in ) {

		$out = $this->default_options;

		// Validate post_types
		$out['post_types'] = $in['post_types'];

		return $out;
	}

	public function view_settings_page() {
	?>
	<div class="wrap" id="wp_seo_settings">
		<h2><?php _e( 'WP SEO', 'wp-seo' ); ?></h2>

		<form action="options.php" method="POST">
			<?php settings_fields( self::SLUG ); ?>

			<h3 class="nav-tab-wrapper">
				<a class="nav-tab nav-tab-active" href="#wp_seo_content_types"><?php _e( 'Post Types', 'wp-seo' ); ?></a>
				<a class="nav-tab" href="#wp_seo_taxonomies"><?php _e( 'Taxonomies', 'wp-seo' ); ?></a>
				<a class="nav-tab" href="#wp_seo_archives"><?php _e( 'Archives', 'wp-seo' ); ?></a>
			</h3>

			<div class="wp-seo-tab" id="wp_seo_content_types">
				<table class="form-table">
					<?php do_settings_fields( self::SLUG, 'content_types' ); ?>
				</table>
				<?php if ( ! empty( $this->archived_post_types ) ) : ?>
					<?php foreach ( $this->archived_post_types as $post_type ) : ?>

						<h3><?php printf( esc_html__( '%s Archives', 'wp-seo' ), esc_html( $post_type->labels->singular_name ) ) ?></h3>
						<table class="form-table">
							<?php do_settings_fields( self::SLUG, 'archive_' . $post_type->name ); ?>
						</table>

					<?php endforeach ?>
				<?php endif ?>
			</div>

			<div class="wp-seo-tab" id="wp_seo_taxonomies">
				<?php foreach ( $this->taxonomies as $taxonomy ) : ?>

					<h3><?php echo esc_html( $taxonomy->label ) ?></h3>
					<table class="form-table">
						<?php do_settings_fields( self::SLUG, 'archive_' . $taxonomy->name ); ?>
					</table>

				<?php endforeach ?>
			</div>

			<div class="wp-seo-tab" id="wp_seo_archives">
				General archives go here: Date, Author, Search
			</div>

			<?php # $this->do_section( 'archives' ); ?>

			<?php submit_button(); ?>
		</form>

	</div>
	<?php
	}

}

function WP_SEO_Settings() {
	return WP_SEO_Settings::instance();
}
add_action( 'plugins_loaded', 'WP_SEO_Settings' );