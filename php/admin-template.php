<?php
/**
 * Administration template tags.
 *
 * @package WP_SEO
 */

/**
 * Prints markup and fires actions to construct the default WP SEO post metabox.
 *
 * @param WP_Post $post Post object of the post being edited.
 */
function wp_seo_the_post_meta_fields( $post ) {
	?>
	<table class="wp-seo-post-meta-fields">
		<tbody>
			<tr>
				<th scope="row">
					<?php
					/**
					 * Fires to print the meta title input label in the post metabox.
					 */
					do_action( 'wp_seo_post_meta_fields_title_label' );
					?>
				</th>
				<td>
					<?php
					/**
					 * Fires to print the meta title input in the post metabox.
					 *
					 * @param int $post_id The ID of the post being edited.
					 */
					do_action( 'wp_seo_post_meta_fields_title_input', $post->ID );

					/**
					 * Fires after the meta title input in the post metabox.
					 *
					 * @param int $post_id The ID of the post being edited.
					 */
					do_action( 'wp_seo_post_meta_fields_after_title_input', $post->ID );
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php
					/**
					 * Fires to print the meta description label in the post metabox.
					 */
					do_action( 'wp_seo_post_meta_fields_description_label' );
					?>
				</th>
				<td>
					<?php
					/**
					 * Fires to print the meta description input in the post metabox.
					 *
					 * @param int $post_id The ID of the post being edited.
					 */
					do_action( 'wp_seo_post_meta_fields_description_input', $post->ID );

					/**
					 * Fires after the meta description input in the post metabox.
					 *
					 * @param int $post_id The ID of the post being edited.
					 */
					do_action( 'wp_seo_post_meta_fields_after_description_input', $post->ID );
					?>
				<td>
			</tr>
			<tr>
				<th scope="row">
					<?php
					/**
					 * Fires to print the meta keywords label in the post metabox.
					 */
					do_action( 'wp_seo_post_meta_fields_keywords_label' );
					?>
				</th>
				<td>
					<?php
					/**
					 * Fires to print the meta keywords input in the post metabox.
					 *
					 * @param int $post_id The ID of the post being edited.
					 */
					do_action( 'wp_seo_post_meta_fields_keywords_input', $post->ID );
					?>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}

/**
 * Prints markup and fires actions to place the default WP SEO fields on the add-term screen.
 *
 * @param string $taxonomy The taxonomy to which a term is being added.
 */
function wp_seo_the_add_term_meta_fields( $taxonomy ) {
	?>
	<h3><?php echo esc_html( wp_seo_get_box_title() ); ?></h3>

	<div class="wp-seo-term-meta-fields">
		<div class="form-field">
			<?php
			/**
			 * Fires to print the meta title input label with the add-term meta fields.
			 */
			do_action( 'wp_seo_add_term_meta_fields_title_label' );

			/**
			 * Fires to print the meta title input label with the add-term meta fields.
			 */
			do_action( 'wp_seo_add_term_meta_fields_title_input' );

			/**
			 * Fires after the meta title input with the add-term meta fields.
			 */
			do_action( 'wp_seo_add_term_meta_fields_after_title_input' );
			?>
		</div>
		<div class="form-field">
			<?php
			/**
			 * Fires to print the meta description label with the add-term meta fields.
			 */
			do_action( 'wp_seo_add_term_meta_fields_description_label' );

			/**
			 * Fires to print the meta description input with the add-term meta fields.
			 */
			do_action( 'wp_seo_add_term_meta_fields_description_input' );

			/**
			 * Fires after the meta description input with the add-term meta fields.
			 */
			do_action( 'wp_seo_add_term_meta_fields_after_description_input' );
			?>
		</div>
		<div class="form-field">
			<?php
			/**
			 * Fires to print the meta keywords label with the add-term meta fields.
			 */
			do_action( 'wp_seo_add_term_meta_fields_keywords_label' );

			/**
			 * Fires to print the meta keywords input with the add-term meta fields.
			 */
			do_action( 'wp_seo_add_term_meta_fields_keywords_input' );
			?>
		</div>
	</div>
	<?php
}

/**
 * Prints markup and fires actions to place the default WP SEO fields on the edit-term screen.
 *
 * @param WP_Term $tag      The term object.
 * @param string  $taxonomy The taxonomy slug.
 */
function wp_seo_the_edit_term_meta_fields( $tag, $taxonomy ) {
	?>
	<h2><?php echo esc_html( wp_seo_get_box_title() ); ?></h2>

	<table class="form-table wp-seo-term-meta-fields">
		<tbody>
			<tr class="form-field">
				<th scope="row">
					<?php
					/**
					 * Fires to print the meta title input label with the edit-term meta fields.
					 */
					do_action( 'wp_seo_edit_term_meta_fields_title_label' );
					?>
				</th>
				<td>
					<?php
					/**
					 * Fires to print the meta title input with the edit-term meta fields.
					 *
					 * @param int $term_id The term ID of the term being edited.
					 * @param string $taxonomy The taxonomy slug.
					 */
					do_action( 'wp_seo_edit_term_meta_fields_title_input', $tag->term_id, $taxonomy );

					/**
					 * Fires after the meta title input with the edit-term meta fields.
					 *
					 * @param int $term_id The term ID of the term being edited.
					 * @param string $taxonomy The taxonomy slug.
					 */
					do_action( 'wp_seo_edit_term_meta_fields_after_title_input', $tag->term_id, $taxonomy );
					?>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row">
					<?php
					/**
					 * Fires to print the meta description label with the edit-term meta fields.
					 */
					do_action( 'wp_seo_edit_term_meta_fields_description_label' );
					?>
				</th>
				<td>
					<?php
					/**
					 * Fires to print the meta description input with the edit-term meta fields.
					 *
					 * @param int $term_id The term ID of the term being edited.
					 * @param string $taxonomy The taxonomy slug.
					 */
					do_action( 'wp_seo_edit_term_meta_fields_description_input', $tag->term_id, $taxonomy );

					/**
					 * Fires after the meta description input with the edit-term meta fields.
					 *
					 * @param int $term_id The term ID of the term being edited.
					 * @param string $taxonomy The taxonomy slug.
					 */
					do_action( 'wp_seo_edit_term_meta_fields_after_description_input', $tag->term_id, $taxonomy );
					?>
				<td>
			</tr>
			<tr class="form-field">
				<th scope="row">
					<?php
					/**
					 * Fires to print the meta keywords label with the edit-term meta fields.
					 */
					do_action( 'wp_seo_edit_term_meta_fields_keywords_label' );
					?>
				</th>
				<td>
					<?php
					/**
					 * Fires to print the meta keywords input with the edit-term meta fields.
					 *
					 * @param int $term_id The term ID of the term being edited.
					 * @param string $taxonomy The taxonomy slug.
					 */
					do_action( 'wp_seo_edit_term_meta_fields_keywords_input', $tag->term_id, $taxonomy );
					?>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}

/**
 * Prints a form label for a meta title input.
 */
function wp_seo_the_meta_title_label() {
	?>
	<label for="wp_seo_meta_title"><?php esc_html_e( 'Title Tag', 'wp-seo' ); ?></label>
	<?php
}

/**
 * Prints a form input for a meta title.
 *
 * @param string $value The input's current value.
 */
function wp_seo_the_meta_title_input( $value ) {
	?>
	<input type="text" id="wp_seo_meta_title" name="seo_meta[title]" value="<?php echo esc_attr( $value ); ?>" size="96" />
	<?php
}

/**
 * Prints markup for displaying a meta title input's character count.
 *
 * @param string $count The starting character count.
 */
function wp_seo_the_title_character_count( $count ) {
	?>
	<p>
		<?php esc_html_e( 'Title character count: ', 'wp-seo' ); ?>
		<span class="title-character-count"></span>
		<?php /* translators: %d: title character count */ ?>
		<noscript><?php echo esc_html( sprintf( __( '%d (save changes to update)', 'wp-seo' ), $count ) ); ?></noscript>
	</p>
	<?php
}

/**
 * Prints a form label for a meta description input.
 */
function wp_seo_the_meta_description_label() {
	?>
	<label for="wp_seo_meta_description"><?php esc_html_e( 'Meta Description', 'wp-seo' ); ?></label>
	<?php
}

/**
 * Prints a form input for a meta description.
 *
 * @param string $value The input's current value.
 */
function wp_seo_the_meta_description_input( $value ) {
	?>
	<textarea id="wp_seo_meta_description" name="seo_meta[description]" rows="2" cols="96"><?php echo esc_textarea( $value ); ?></textarea>
	<?php
}

/**
 * Prints markup for displaying a meta description input's character count.
 *
 * @param string $count The starting character count.
 */
function wp_seo_the_description_character_count( $count ) {
	?>
	<p>
		<?php esc_html_e( 'Description character count: ', 'wp-seo' ); ?>
		<span class="description-character-count"></span>
		<?php /* translators: %d: description character count */ ?>
		<noscript><?php echo esc_html( sprintf( __( '%d (save changes to update)', 'wp-seo' ), $count ) ); ?></noscript>
	</p>
	<?php
}

/**
 * Prints a form label for a meta keywords input.
 */
function wp_seo_the_meta_keywords_label() {
	?>
	<label for="wp_seo_meta_keywords"><?php esc_html_e( 'Meta Keywords', 'wp-seo' ); ?></label>
	<?php
}

/**
 * Prints a form input for meta keywords.
 *
 * @param string $value The input's current value.
 */
function wp_seo_the_meta_keywords_input( $value ) {
	?>
	<textarea id="wp_seo_meta_keywords" name="seo_meta[keywords]" rows="2" cols="96"><?php echo esc_textarea( $value ); ?></textarea>
	<?php
}
