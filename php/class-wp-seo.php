<?php

/**
 * WP-SEO Core Functionality
 */

if ( !class_exists( 'WP_SEO' ) ) :

class WP_SEO {

	private static $instance;

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

	public function setup() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_post' ) );
		add_action( 'edit_attachment', array( $this, 'save_post' ) );
		add_action( 'add_attachment', array( $this, 'save_post' ) );
		add_action( 'admin_head', array( $this, 'styles' ) );

		add_filter( 'wp_title', array( $this, 'wp_title' ), 20, 2 );
		add_filter( 'wp_head', array( $this, 'wp_head' ), 5 );
	}

	public function add_meta_boxes( $post_type ) {
		if ( in_array( $post_type, WP_SEO_Settings()->options['post_types'] ) ) {
			add_meta_box(
				'wp_seo',
				__( 'Search Engine Optimization', 'wp-seo' ),
				array( $this, 'meta_fields' ),
				$post_type,
				apply_filters( 'wp_seo_meta_box_context', 'normal' ),
				apply_filters( 'wp_seo_meta_box_priority', 'high' )
			);
		}
	}

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

	public function save_post( $post_id ) {
		if ( !isset( $_POST['post_type'] ) )
			return;

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) )
				return;
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return;
		}

		if ( ! isset( $_POST['wp-seo-nonce'] ) || ! wp_verify_nonce( $_POST['wp-seo-nonce'], plugin_basename( __FILE__ ) ) )
			return;

		$post_id = $_POST['post_ID'];
		if ( !isset( $_POST['seo_meta'] ) )
			$_POST['seo_meta'] = array();

		foreach ( array( 'title', 'description', 'keywords' ) as $field ) {
			$data = isset( $_POST['seo_meta'][ $field ] ) ? sanitize_text_field( $_POST['seo_meta'][ $field ] ) : '';
			update_post_meta( $post_id, '_meta_' . $field, $data );
		}
	}

	public function styles() {
		?>
		<style type="text/css">
		table.wp-seo-meta-fields { width: 100%; }
		.wp-seo-meta-fields th { padding: 3px; vertical-align: top; text-align: right; }
		.wp-seo-meta-fields textarea { height: 4em; width: 98%; }
		.wp-seo-meta-fields input { width: 98%; }
		</style>
		<?php
	}

	public function wp_title( $title, $sep ) {
		if ( ! in_array( get_post_type(), WP_SEO_Settings()->options['post_types'] ) )
			return $title;

		if ( is_single() || is_page() ) {
			if ( '' != ( $meta_title = get_post_meta( get_the_ID(), '_meta_title', true ) ) )
				return $meta_title;
		}
		return $title;
	}

	public function wp_head() {
		if ( ! in_array( get_post_type(), WP_SEO_Settings()->options['post_types'] ) )
			return;

		if ( is_single() || is_page() ) {
			if ( '' != ( $meta_description = get_post_meta( get_the_ID(), '_meta_description', true ) ) )
				echo "<meta name='description' value='" . esc_attr( $meta_description ) . "' />\n";
			if ( '' != ( $meta_keywords = get_post_meta( get_the_ID(), '_meta_keywords', true ) ) )
				echo "<meta name='keywords' value='" . esc_attr( $meta_keywords ) . "' />\n";
		}

	}

}

function WP_SEO() {
	return WP_SEO::instance();
}
add_action( 'after_setup_theme', 'WP_SEO' );

endif;