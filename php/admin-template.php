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
					 * Fires to print the meta canonical URL input label in the post metabox.
					 */
					do_action( 'wp_seo_post_meta_fields_canonical_url_label' );
					?>
				</th>
				<td>
					<?php
					/**
					 * Fires to print the meta canonical URL input in the post metabox.
					 *
					 * @param int $post_id The ID of the post being edited.
					 */
					do_action( 'wp_seo_post_meta_fields_canonical_url_input', $post->ID );

					/**
					 * Fires after the meta canonical URL input in the post metabox.
					 *
					 * @param int $post_id The ID of the post being edited.
					 */
					do_action( 'wp_seo_post_meta_fields_after_canonical_url_input', $post->ID );
					?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php
					/**
					 * Fires to print the meta robots legend in the post metabox.
					 */
					do_action( 'wp_seo_post_meta_fields_robots_legend' );
					?>
				</th>
				<td>
					<?php
					/**
					 * Fires after the meta robots legend in the post metabox.
					 */
					do_action( 'wp_seo_post_meta_fields_after_robots_legend' );
					?>
				</td>
			</tr>
			<?php foreach ( WP_SEO()->get_robots_directive_values() as $directive ) : ?>
				<tr>
					<th scope="row">
						<?php
						/**
						 * Fires to print the meta robots {directive} label in the post metabox.
						 *
						 * @param string $directive The robots directive key.
						 */
						do_action( "wp_seo_post_meta_fields_robots_{$directive}_label", $directive );
						?>
					</th>
					<td>
						<?php
						/**
						 * Fires to print the meta robots {directive} input in the post metabox.
						 *
						 * @param int    $post_id   The ID of the post being edited.
						 * @param string $directive The robots directive key.
						 */
						do_action( "wp_seo_post_meta_fields_robots_{$directive}_input", $post->ID, $directive );

						/**
						 * Fires after the meta robots {directive} input in the post metabox.
						 *
						 * @param int    $post_id   The ID of the post being edited.
						 * @param string $directive The robots directive key.
						 */
						do_action( "wp_seo_post_meta_fields_after_robots_{$directive}_input", $post->ID, $directive );
						?>
					</td>
				</tr>
			<?php endforeach; ?>
			<?php
			/**
			 * Fires after the other SEO fields are rendered.
			 *
			 * @param int $post_id The ID of the post being edited.
			 */
			do_action( 'wp_seo_after_post_meta_fields', $post->ID );
			?>
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
			 * Fires to print the meta canonical URL input label with the add-term meta fields.
			 */
			do_action( 'wp_seo_add_term_meta_fields_canonical_url_label' );

			/**
			 * Fires to print the meta canonical URL input with the add-term meta fields.
			 */
			do_action( 'wp_seo_add_term_meta_fields_canonical_url_input' );

			/**
			 * Fires after the meta canonical URL input with the add-term meta fields.
			 */
			do_action( 'wp_seo_add_term_meta_fields_after_canonical_url_input' );
			?>
		</div>
		<div class="form-field">
			<?php
			/**
			 * Fires to print the meta robots legend with the add-term meta fields.
			 */
			do_action( 'wp_seo_post_meta_fields_robots_legend' );

			/**
			 * Fires after the meta robots legend with the add-term meta fields.
			 */
			do_action( 'wp_seo_post_meta_fields_after_robots_legend' );
			?>
		</div>
		<?php foreach ( WP_SEO()->get_robots_directive_values() as $directive ) : ?>
			<div class="form-field">
				<?php
				/**
				 * Fires to print the meta robots {directive} label with the add-term meta fields.
				 *
				 * @param string $directive The robots directive key.
				 */
				do_action( "wp_seo_add_term_meta_fields_robots_{$directive}_label", $directive );

				/**
				 * Fires to print the meta robots {directive} input with the add-term meta fields.
				 *
				 * @param string $directive The robots directive key.
				 */
				do_action( "wp_seo_add_term_meta_fields_robots_{$directive}_input", $directive );

				/**
				 * Fires after the meta robots {directive} input with the add-term meta fields.
				 *
				 * @param string $directive The robots directive key.
				 */
				do_action( "wp_seo_add_term_meta_fields_after_robots_{$directive}_input", $directive );
				?>
			</div>
		<?php endforeach; ?>
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
					 * Fires to print the meta canonical URL input label with the edit-term meta fields.
					 */
					do_action( 'wp_seo_edit_term_meta_fields_canonical_url_label' );
					?>
				</th>
				<td>
					<?php
					/**
					 * Fires to print the meta canonical URL input with the edit-term meta fields.
					 *
					 * @param int $term_id The term ID of the term being edited.
					 * @param string $taxonomy The taxonomy slug.
					 */
					do_action( 'wp_seo_edit_term_meta_fields_canonical_url_input', $tag->term_id, $taxonomy );

					/**
					 * Fires after the meta canonical URL input with the edit-term meta fields.
					 *
					 * @param int $term_id The term ID of the term being edited.
					 * @param string $taxonomy The taxonomy slug.
					 */
					do_action( 'wp_seo_edit_term_meta_fields_after_canonical_url_input', $tag->term_id, $taxonomy );
					?>
				</td>
			</tr>
			<tr class="form-field">
				<th scope="row">
					<?php
					/**
					 * Fires to print the meta robots legend with the edit-term meta fields.
					 */
					do_action( 'wp_seo_edit_term_meta_fields_robots_legend' );
					?>
				</th>
				<td>
					<?php
					/**
					 * Fires after the meta robots legend with the edit-term meta fields.
					 */
					do_action( 'wp_seo_edit_term_meta_fields_after_robots_legend' );
					?>
				</td>
			</tr>
			<?php foreach( WP_SEO()->get_robots_directive_values() as $directive ) : ?>
				<tr class="form-field">
					<th scope="row">
						<?php
						/**
						 * Fires to print the meta robots {directive} label with the edit-term meta fields.
						 *
						 * @param string $directive The robots directive key.
						 */
						do_action( "wp_seo_edit_term_meta_fields_robots_{$directive}_label", $directive );
						?>
					</th>
					<td>
						<?php
						/**
						 * Fires to print the meta robots {directive} input with the edit-term meta fields.
						 *
						 * @param int    $term_id   The term ID of the term being edited.
						 * @param string $taxonomy  The taxonomy slug.
						 * @param string $directive The robots directive key.
						 */
						do_action( "wp_seo_edit_term_meta_fields_robots_{$directive}_input", $tag->term_id, $taxonomy, $directive );

						/**
						 * Fires after the meta robots {directive} input with the edit-term meta fields.
						 *
						 * @param int    $term_id   The term ID of the term being edited.
						 * @param string $taxonomy  The taxonomy slug.
						 * @param string $directive The robots directive key.
						 */
						do_action( "wp_seo_edit_term_meta_fields_after_robots_{$directive}_input", $tag->term_id, $taxonomy, $directive );
						?>
					</td>
				</tr>
			<?php endforeach; ?>
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
 * Prints a form label for a meta canonical URL input.
 */
function wp_seo_the_meta_canonical_url_label() {
	?>
	<label for="wp_seo_meta_canonical_url"><?php esc_html_e( 'Canonical URL', 'wp-seo' ); ?></label>
	<?php
}

/**
 * Prints a form input for a meta canonical URL.
 *
 * @param string $value The input's current value.
 */
function wp_seo_the_meta_canonical_url_input( $value ) {
	?>
	<input type="url" id="wp_seo_meta_canonical_url" name="seo_meta[canonical_url]" value="<?php echo esc_attr( $value ); ?>" size="96" placeholder="https://" pattern="https?://.+" />
	<?php
}

/**
 * Prints a description for a meta canonical URL input.
 */
function wp_seo_the_meta_canonical_url_after_input() {
	?>
	<p>
		<?php esc_html_e( 'Canonical URL must be a valid URL', 'wp-seo' ); ?>
	</p>
	<?php
}

/**
 * Prints a legend for the meta robots options.
 */
function wp_seo_the_meta_robots_legend() {
	?>
	<legend>
		<?php esc_html_e( 'Meta Robots', 'wp-seo' ); ?>
	</legend>
	<?php
}

/**
 * Prints a description for the meta robots options legend.
 */
function wp_seo_the_meta_robots_after_legend() {
	?>
	<p>
		<?php esc_html_e( 'Override the default meta robots settings for this content.', 'wp-seo' ); ?>
	</p>
	<?php
}

/**
 * Prints a form label for a meta robots directive input.
 *
 * @param string $directive The robots directive key (e.g., 'noindex').
 */
function wp_seo_the_meta_robots_label( $directive ) {
	$robots_directives = WP_SEO()->get_robots_directives();
	$robots_directive_index = array_search( $directive, array_column( $robots_directives, 'value' ), true );

	if ( false === $robots_directive_index ) {
		return;
	}

	$robots_directive_label = $robots_directives[ $robots_directive_index ]['label'] ?? '';

	if ( empty( $robots_directive_label ) ) {
		return;
	}

	?>
	<label for="wp_seo_meta_robots_<?php echo esc_attr( $directive ); ?>">
		<?php echo esc_html( $robots_directive_label ); ?>
	</label>
	<?php
}

/**
 * Prints a form input for a meta robots directive.
 *
 * @param string $value     The input's current value.
 * @param string $directive The robots directive key (e.g., 'noindex').
 */
function wp_seo_the_meta_robots_input( $value, $directive ) {
	if ( ! is_admin() ) {
		return;
	}

	// Get the default value of the directive from the settings.
	$current_screen = get_current_screen();

	if ( empty( $current_screen ) || ! $current_screen instanceof WP_Screen ) {
		return;
	}

	if ( ! empty( $current_screen->taxonomy ) ) {
		$robots = WP_SEO_Settings::instance()->get_option( "archive_{$current_screen->taxonomy}_robots", [] );
	} elseif ( ! empty( $current_screen->post_type ) ) {
		$robots = WP_SEO_Settings::instance()->get_option( "single_{$current_screen->post_type}_robots", [] );
	}

	$inherited_value = '';

	if ( ! empty( $robots ) && is_array( $robots ) ) {
		$inherited_value = in_array( $directive, $robots, true )
			? __( 'Enable', 'wp-seo' )
			: __( 'Disable', 'wp-seo' );
	}

	?>
	<select id="wp_seo_meta_robots_<?php echo esc_attr( $directive ); ?>" name="seo_meta[robots_<?php echo esc_attr( $directive ); ?>]">
		<option value=""><?php echo esc_html(
			sprintf(
				/* translators: inherited value (Enable/Disable) */
				__( 'Inherit (%s)', 'wp-seo' ),
				$inherited_value
			)
		); ?></option>
		<option value="enable" <?php selected( $value, 'enable' ); ?>><?php esc_html_e( 'Enable', 'wp-seo' ); ?></option>
		<option value="disable" <?php selected( $value, 'disable' ); ?>><?php esc_html_e( 'Disable', 'wp-seo' ); ?></option>
	</select>
	<?php
}

/**
 * Prints a description for a meta robots directive input.
 *
 * @param string $directive The robots directive key (e.g., 'noindex').
 */
function wp_seo_the_meta_robots_after_input( $directive ) {
	$robots_directives = WP_SEO()->get_robots_directives();
	$robots_directive_index = array_search( $directive, array_column( $robots_directives, 'value' ), true );

	if ( false === $robots_directive_index ) {
		return;
	}

	$robots_directive_description = $robots_directives[ $robots_directive_index ]['description'] ?? '';

	if ( empty( $robots_directive_description ) ) {
		return;
	}

	echo esc_html( $robots_directive_description );
}