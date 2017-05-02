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
		<span class="title-character-count"><?php echo esc_html( $count ); ?></span>
	</p>

	<noscript>
		<p><?php esc_html_e( 'Save changes to update.', 'wp-seo' ); ?></p>
	</noscript>
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
		<span class="description-character-count"><?php echo esc_html( $count ); ?></span>
	</p>

	<noscript>
		<p><?php esc_html_e( 'Save changes to update.', 'wp-seo' ); ?></p>
	</noscript>
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

/**
 * Render a settings text field.
 *
 * @param array  $args {
 *     An array of arguments for the text field.
 *
 *     @type string $field The field name.
 *     @type string $type  The field type. Default 'text'.
 *     @type string $size  The field size. Default 80.
 *     @type string $slug  Optional. Form field name prefix. Default WP_SEO_Settings::SLUG.
 * }
 * @param string $value The current field value.
 */
function wp_seo_render_text_field( $args, $value ) {
	$args = wp_parse_args( $args, array(
		'type' => 'text',
		'size' => 80,
		'slug' => WP_SEO_Settings::SLUG,
	) );

	printf(
		'<input type="%s" name="%s[%s]" value="%s" size="%s" />',
		esc_attr( $args['type'] ),
		esc_attr( $args['slug'] ),
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
 *     @type string $field The field name.
 *     @type int    $rows  Rows in the textarea. Default 2.
 *     @type int    $cols  Columns in the textarea. Default 80.
 *     @type string $slug  Optional. Form field name prefix. Default WP_SEO_Settings::SLUG.
 * }
 * @param string $value The current field value.
 */
function wp_seo_render_textarea( $args, $value ) {
	$args = wp_parse_args( $args, array(
		'rows' => 2,
		'cols' => 80,
		'slug' => WP_SEO_Settings::SLUG,
	) );

	printf(
		'<textarea name="%s[%s]" rows="%d" cols="%d">%s</textarea>',
		esc_attr( $args['slug'] ),
		esc_attr( $args['field'] ),
		esc_attr( $args['rows'] ),
		esc_attr( $args['cols'] ),
		esc_textarea( $value )
	);
}

/**
 * Render settings checkboxes.
 *
 * @param array $args {
 *     An array of arguments for the checkboxes.
 *
 *     @type string $field The field name.
 *     @type array  $boxes An associative array of the value and label
 *                         of each checkbox.
 *     @type string $slug  Optional. Form field name prefix. Default WP_SEO_Settings::SLUG.
 * }
 * @param array $values Indexed array of current field values.
 */
function wp_seo_render_checkboxes( $args, $values ) {
	$args = wp_parse_args( $args, array(
		'slug' => WP_SEO_Settings::SLUG,
	) );

	foreach ( $args['boxes'] as $box_value => $box_label ) {
		printf( '
				<label for="%1$s_%2$s_%3$s">
					<input id="%1$s_%2$s_%3$s" type="checkbox" name="%1$s[%2$s][]" value="%3$s" %4$s>
					%5$s
				</label><br>',
			esc_attr( $args['slug'] ),
			esc_attr( $args['field'] ),
			esc_attr( $box_value ),
			is_array( $values ) ? checked( in_array( $box_value, $values ), true, false ) : '',
			esc_html( $box_label )
		);
	}
}

/**
 * Render settings dropdown.
 *
 * @param array $args {
 *     An array of arguments for the dropdown.
 *
 *     @type string $field The field name.
 *     @type array  $boxes An associative array of the value and label
 *                         of each dropdown option.
 *     @type string $slug  Optional. Form field name prefix. Default WP_SEO_Settings::SLUG.
 * }
 * @param array $values Indexed array of current field values.
 */
function wp_seo_render_dropdown( $args, $values ) {
	$args = wp_parse_args( $args, array(
		'slug' => WP_SEO_Settings::SLUG,
	) );

	printf( '<select id="%1$s_%2$s" name="%1$s[%2$s]">',
		esc_attr( $args['slug'] ),
		esc_attr( $args['field'] )
	);

	$count = 0;

	printf(
		'<option value="" %1$s>%2$s</option>',
		esc_attr( $values ? '' : 'selected' ),
		esc_html__( 'Select', 'wp-seo' )
	);
	foreach ( $args['boxes'] as $box_value => $box_label ) {
		printf(
			'<option id="%1$s_%2$s_%3$s" value="%4$s" %5$s>%6$s</option>',
			esc_attr( $args['slug'] ),
			esc_attr( $args['field'] ),
			esc_attr( $count ),
			esc_attr( $box_value ),
			selected( $values, $box_value, true ),
			esc_html( $box_label )
		);
		$count++;
	}
	echo '</select>';
}

/**
 * Render image field.
 *
 * @param array $args {
 *     An array of arguments for the image field.
 *
 *     @type string $field The field name.
 *     @type string $size  Optional. Preview image size. Default 'thumbnail'.
 *     @type string $slug  Optional. Form field name prefix. Default WP_SEO_Settings::SLUG.
 * }
 * @param int   $value The current field value.
 */
function wp_seo_render_image_field( $args, $value ) {
	$args = wp_parse_args( $args, array(
		'size' => 'thumbnail',
		'slug' => WP_SEO_Settings::SLUG,
	) );

	wp_enqueue_media();

	$img_src = '';
	if ( $value ) {
		$img_src = wp_get_attachment_image_url( $value, $args['size'] );
	}

	echo '<div class="wp-seo-image-container">';

	// If we have an image, output it.
	echo '<div class="custom-img-container">';
	if ( $img_src ) {
		printf(
			'<img src="%1$s" alt="%2$s" />',
			esc_url( $img_src ),
			esc_attr( get_post_meta( $value, '_wp_attachment_image_alt', true ) )
		);
	}
	echo '</div>';

	// If we have an image, hide the add button, and vice versa.
	echo '<p class="hide-if-no-js">';
	printf(
		'<a class="upload-custom-img %1$s" href="%2$s">%3$s</a>',
		esc_attr( $img_src ? 'hidden' : '' ),
		esc_url( get_upload_iframe_src( 'image' ) ),
		esc_html__( 'Set image', 'wp-seo' )
	);
	printf(
		'<a class="delete-custom-img %1$s" href="#">%2$s</a>',
		esc_attr( $img_src ? '' : 'hidden' ),
		esc_html__( 'Remove this image', 'wp-seo' )
	);
	echo '</p>';

	printf(
		'<input id="%1$s_%2$s" class="custom-img-id" name="%1$s[%2$s]" type="hidden" value="%3$s" />',
		esc_attr( $args['slug'] ),
		esc_attr( $args['field'] ),
		esc_attr( $value )
	);

	echo '</div>';
}

/**
 * Render a repeatable text field.
 *
 * @param array $args {
 *     An array of arguments for setting up the repeatable fields.
 *
 *     @type string $field  The field name.
 *     @type array  $repeat Associative array of field names and labels to
 *                          include in each repeated instance of the field.
 *     @type string $size   Optional. The field size. Default 70.
 *     @type string $slug   Optional. Form field name prefix. Default WP_SEO_Settings::SLUG.
 * }
 * @param array $values The current field values.
 */
function wp_seo_render_repeatable_field( $args, $values ) {
	$args = wp_parse_args( $args, array(
		'size' => 70,
		'slug' => WP_SEO_Settings::SLUG,
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
											esc_attr( $args['slug'] ),
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
										esc_attr( $args['slug'] ),
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
									esc_attr( $args['slug'] ),
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
