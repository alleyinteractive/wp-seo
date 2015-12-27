<?php
/**
 * Class file for WP_SEO_Term_Meta_Boxes.
 *
 * @package WP_SEO
 */

/**
 * Adds meta boxes to edit-term screens and saves the submitted data.
 */
class WP_SEO_Term_Meta_Boxes extends WP_SEO_Meta_Boxes {
	/**
	 * Add actions and filters.
	 *
	 * @codeCoverageIgnore
	 */
	protected function setup() {
		$taxonomies = wp_seo_settings()->get_enabled_taxonomies();

		if ( ! empty( $taxonomies ) ) {
			foreach ( $taxonomies as $taxonomy ) {
				add_action( "{$taxonomy}_add_form_fields", array( $this, 'taxonomy_add_form_fields' ) );
				add_action( "{$taxonomy}_edit_form", array( $this, 'taxonomy_edit_form' ), 10, 2 );
			}

			add_action( 'created_term', array( $this, 'created_term' ), 10, 3 );
			add_action( 'edited_term', array( $this, 'edited_term' ), 10, 3 );
		}
	}

	/**
	 * Fires after the Add Term form fields.
	 *
	 * @param string $taxonomy The taxonomy slug.
	 */
	public function taxonomy_add_form_fields( $taxonomy ) {
		$this->render_add_term_fields();
	}

	/**
	 * Fires at the end of the Edit Term form for all taxonomies.
	 *
	 * @param object $tag The term object
	 * @param string $taxonomy The taxonomy slug
	 */
	public function taxonomy_edit_form( $tag, $taxonomy ) {
		$this->render_edit_term_fields( $tag );
	}

	/**
	 * Fires after a term is created, and the term cache has been cleaned.
	 *
	 * @param int $term_id Term ID.
	 * @param int $term_taxonomy_id Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function created_term( $term_id, $term_taxonomy_id, $taxonomy ) {
		$this->save_fields( $term_id, $taxonomy );
	}

	/**
	 * Fires after a term is updated, and the term cache has been cleaned.
	 *
	 * @param int $term_id Term ID.
	 * @param int $term_taxonomy_id Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function edited_term( $term_id, $term_taxonomy_id, $taxonomy ) {
		$this->save_fields( $term_id, $taxonomy );
	}

	/**
	 * Display the SEO fields, formatted for the Add Term screen.
	 */
	private function render_add_term_fields() {
		wp_nonce_field( plugin_basename( __FILE__ ), 'wp-seo-nonce' );
		?>
			<h2><?php echo esc_html( $this->get_box_heading() ); ?></h2>

			<div class="wp-seo-term-meta-fields">
				<div class="form-field">
					<label for="wp-seo-meta-title"><?php esc_html_e( 'Title Tag', 'wp-seo' ); ?></label>
					<input type="text" id="wp-seo-meta-title" class="wp-seo-has-character-count" name="seo_meta[title]" value="" size="96" />
					<p>
						<?php esc_html_e( 'Title character count:', 'wp-seo' ); ?>
						<span data-character-count-for="wp-seo-meta-title" class="wp-seo-character-count"></span>
						<noscript><?php echo esc_html( $this->noscript_character_count( '' ) ); ?></noscript>
					</p>
				</div>

				<div class="form-field">
					<label for="wp-seo-meta-description"><?php esc_html_e( 'Meta Description', 'wp-seo' ); ?></label>
					<textarea id="wp-seo-meta-description" class="wp-seo-has-character-count" name="seo_meta[description]" rows="2" cols="96"></textarea>
					<p>
						<?php esc_html_e( 'Description character count:', 'wp-seo' ); ?>
						<span data-character-count-for="wp-seo-meta-description" class="wp-seo-character-count"></span>
						<noscript><?php echo esc_html( $this->noscript_character_count( '' ) ); ?></noscript>
					</p>
				</div>

				<div class="form-field">
					<label for="wp-seo-meta-keywords"><?php esc_html_e( 'Meta Keywords', 'wp-seo' ) ?></label>
					<textarea id="wp-seo-meta-keywords" name="seo_meta[keywords]" rows="2" cols="96"></textarea>
				</div>
			</div>
		<?php
	}

	/**
	 * Display the SEO fields, formatted for the Edit Term screen.
	 *
	 * @param object $term Term object.
	 */
	private function render_edit_term_fields( $term ) {
		wp_nonce_field( plugin_basename( __FILE__ ), 'wp-seo-nonce' );

		$values = get_option( wp_seo_get_term_option_name( $term ), array( 'title' => '', 'description' => '', 'keywords' => '' ) );
		?>
			<h2><?php echo esc_html( $this->get_box_heading() ); ?></h2>

			<table class="form-table wp-seo-term-meta-fields">
				<tbody>
					<tr class="form-field">
						<th scope="row"><label for="wp-seo-meta-title"><?php esc_html_e( 'Title Tag', 'wp-seo' ); ?></label></th>
						<td>
							<input type="text" id="wp-seo-meta-title" class="wp-seo-has-character-count" name="seo_meta[title]" value="<?php echo esc_attr( $values['title'] ); ?>" size="96" />
							<p class="description">
								<?php esc_html_e( 'Title character count:', 'wp-seo' ); ?>
								<span data-character-count-for="wp-seo-meta-title" class="wp-seo-character-count"></span>
								<noscript><?php echo esc_html( $this->noscript_character_count( $values['title'] ) ); ?></noscript>
							</p>
						</td>
					</tr>

					<tr class="form-field">
						<th scope="row"><label for="wp-seo-meta-description"><?php esc_html_e( 'Meta Description', 'wp-seo' ); ?></label></th>
						<td>
							<textarea id="wp-seo-meta-description" class="wp-seo-has-character-count" name="seo_meta[description]" rows="2" cols="96"><?php echo esc_textarea( $values['description'] ); ?></textarea>
							<p class="description">
								<?php esc_html_e( 'Description character count:', 'wp-seo' ); ?>
								<span data-character-count-for="wp-seo-meta-description" class="wp-seo-character-count"></span>
								<noscript><?php echo esc_html( $this->noscript_character_count( $values['description'] ) ); ?></noscript>
							</p>
						<td>
					</tr>

					<tr class="form-field">
						<th scope="row"><label for="wp-seo-meta-keywords"><?php esc_html_e( 'Meta Keywords', 'wp-seo' ) ?></label></th>
						<td><textarea id="wp-seo-meta-keywords" name="seo_meta[keywords]" rows="2" cols="96"><?php echo esc_textarea( $values['keywords'] ); ?></textarea></td>
					</tr>
				</tbody>
			</table>
		<?php
	}

	/**
	 * Save the SEO term values as an option.
	 *
	 * @see wp_unslash(), which the Settings API and update_post_meta() otherwise handle.
	 *
	 * @param int $term_id Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	private function save_fields( $term_id, $taxonomy ) {
		if ( ! isset( $_POST['taxonomy'] ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! wp_seo_settings()->has_term_fields( $taxonomy ) ) {
			return;
		}

		$taxonomy_object = get_taxonomy( $taxonomy );
		if ( empty( $taxonomy_object->cap->edit_terms ) || ! current_user_can( $taxonomy_object->cap->edit_terms ) ) {
			return;
		}

		if ( ! isset( $_POST['wp-seo-nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( $_POST['wp-seo-nonce'] ), plugin_basename( __FILE__ ) ) ) {
			return;
		}

		if ( ! isset( $_POST['seo_meta'] ) ) {
			$_POST['seo_meta'] = array();
		}

		foreach ( array( 'title', 'description', 'keywords' ) as $field ) {
			$data[ $field ] = isset( $_POST['seo_meta'][ $field ] ) ? sanitize_text_field( wp_unslash( $_POST['seo_meta'][ $field ] ) ) : '';
		}

		/**
		 * @todo Check get_term(), add test.
		 */
		$name = wp_seo_get_term_option_name( get_term( $term_id, $taxonomy ) );

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
}
