<?php
/**
 * Class file for WP_SEO_Post_Meta_Boxes.
 *
 * @package WP_SEO
 */

/**
 * Adds meta boxes to edit-post screens and saves the submitted data.
 */
class WP_SEO_Post_Meta_Boxes extends WP_SEO_Meta_Boxes {

	/**
	 * Add actions and filters.
	 *
	 * @codeCoverageIgnore
	 */
	protected function setup() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
		add_action( 'edit_attachment', array( $this, 'edit_attachment' ) );
		add_action( 'add_attachment', array( $this, 'add_attachment' ) );
	}

	/**
	 * Fires after all built-in meta boxes have been added to an edit screen.
	 *
	 * @param string $post_type Post type.
	 * @param WP_Post $post Post object.
	 */
	public function add_meta_boxes( $post_type, $post ) {
		$this->register_meta_box( $post_type );
	}

	/**
	 * Post meta box callback.
	 *
	 * @param WP_Post $post The post being edited.
	 */
	public function do_meta_box( $post ) {
		$this->render_meta_box( $post );
	}

	/**
	 * Fires once a post has been saved.
	 *
	 * @param int $post_ID Post ID.
	 * @param WP_Post $post Post object.
	 * @param bool $update Whether this is an existing post being updated.
	 */
	public function save_post( $post_ID, $post, $update ) {
		$this->save_fields( $post_ID );
	}

	/**
	 * Fires once an attachment has been added.
	 *
	 * @param int $post_ID Attachment ID.
	 */
	public function add_attachment( $post_ID ) {
		$this->save_fields( $post_ID );
	}

	/**
	 * Fires once an existing attachment has been updated.
	 *
	 * @param int $post_ID Attachment ID.
	 */
	public function edit_attachment( $post_ID ) {
		$this->save_fields( $post_ID );
	}

	/**
	 * Register a WP SEO post meta box.
	 *
	 * @param string $post_type Post type to register the meta box with.
	 */
	private function register_meta_box( $post_type ) {
		if ( ! WP_SEO_Settings()->has_post_fields( $post_type ) ) {
			return;
		}

		add_meta_box(
			'wp_seo',
			$this->get_box_heading(),
			array( $this, 'do_meta_box' ),
			$post_type,
			/**
			 * Filter the screen context where the fields should display.
			 *
			 * @param string @see add_meta_box().
			 */
			apply_filters( 'wp_seo_meta_box_context', 'normal' ),
			/**
			 * Filter the display priority of the fields within the context.
			 *
			 * @param string @see add_meta_box().
			 */
			apply_filters( 'wp_seo_meta_box_priority', 'high' )
		);
	}

	/**
	 * Display the SEO fields for a post.
	 *
	 * @param WP_Post $post The post being edited.
	 */
	private function render_meta_box( $post ) {
		$title = get_post_meta( $post->ID, '_meta_title', true );
		$description = get_post_meta( $post->ID, '_meta_description', true );
		wp_nonce_field( plugin_basename( __FILE__ ), 'wp-seo-nonce' );
		?>
			<table class="wp-seo-post-meta-fields">
				<tbody>
					<tr>
						<th scope="row"><label for="wp_seo_meta_title"><?php esc_html_e( 'Title Tag', 'wp-seo' ); ?></label></th>
						<td>
							<input type="text" id="wp_seo_meta_title" name="seo_meta[title]" value="<?php echo esc_attr( $title ); ?>" size="96" />
							<div>
								<?php esc_html_e( 'Title character count:', 'wp-seo' ); ?>
								<span class="title-character-count"></span>
								<noscript><?php echo esc_html( $this->noscript_character_count( $title ) ); ?></noscript>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wp_seo_meta_description"><?php esc_html_e( 'Meta Description', 'wp-seo' ); ?></label></th>
						<td>
							<textarea id="wp_seo_meta_description" name="seo_meta[description]" rows="2" cols="96"><?php echo esc_textarea( $description ); ?></textarea>
							<div>
								<?php esc_html_e( 'Description character count:', 'wp-seo' ); ?>
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
	 * Save the $_POST'ed SEO values as post meta.
	 *
	 * @param int $post_ID The post ID being edited.
	 */
	private function save_fields( $post_ID ) {
		if ( ! isset( $_POST['post_type'] ) ) {
			return;
		}

		$post_type = get_post_type_object( sanitize_text_field( $_POST['post_type'] ) );
		if ( ! $post_type ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! WP_SEO_Settings()->has_post_fields( $post_type->name ) ) {
			return;
		}

		if ( empty( $post_type->cap->edit_post ) || ! current_user_can( $post_type->cap->edit_post, $post_ID ) ) {
			return;
		}

		if ( ! isset( $_POST['wp-seo-nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( $_POST['wp-seo-nonce'] ), plugin_basename( __FILE__ ) ) ) {
			return;
		}

		if ( ! isset( $_POST['post_ID'] ) ) {
			return;
		}

		$post_ID = absint( $_POST['post_ID'] );

		if ( ! isset( $_POST['seo_meta'] ) ) {
			$_POST['seo_meta'] = array();
		}

		foreach ( array( 'title', 'description', 'keywords' ) as $field ) {
			$value = isset( $_POST['seo_meta'][ $field ] ) ? sanitize_text_field( $_POST['seo_meta'][ $field ] ) : '';
			update_post_meta( $post_ID, "_meta_{$field}", $value );
		}
	}

}

/**
 * Helper function to use the class instance.
 *
 * @todo destroy
 *
 * @return WP_SEO_Post_Meta_Boxes
 */
function wp_seo_post_meta_boxes() {
	return WP_SEO_Post_Meta_Boxes::instance();
}
add_action( 'admin_init', 'wp_seo_post_meta_boxes' );
