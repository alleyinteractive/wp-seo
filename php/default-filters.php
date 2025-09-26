<?php
/**
 * Sets up the default filters and actions for most WP SEO hooks.
 *
 * If you need to remove a default hook, this file will give you the priority.
 *
 * Not all of the default hooks are here (yet).
 *
 * @since 0.11.3-beta1 IMPORTANT! Some hooks in this file will change to support
 *    new features in an upcoming version of WP SEO. The release notes will
 *    include details about these changes.
 *
 * @package WP_SEO
 */

add_action( 'admin_init', 'wp_seo_load_admin_files', 0 );

$contexts = [
	'post',
	'add_term',
	'edit_term',
];

// Field and whether the field needs a character count after the input.
$fields = [
	'title'               => true,
	'description'         => true,
	'canonical_url'       => false,
	'robots_noindex'      => false,
	'robots_nofollow'     => false,
	'robots_noarchive'    => false,
	'robots_nosnippet'    => false,
	'robots_noimageindex' => false,
	'robots_notranslate'  => false,
];

foreach ( $contexts as $context ) {
	// All fields container.
	add_action(
		"wp_seo_{$context}_meta_fields",
		"wp_seo_the_{$context}_meta_fields",
		10,
		'edit_term' === $context ? 2 : 1
	);

	foreach ( $fields as $field => $show_char_count ) {
		// Label.
		add_action(
			"wp_seo_{$context}_meta_fields_{$field}_label",
			"wp_seo_the_meta_{$field}_label"
		);

		// Input.
		add_action(
			"wp_seo_{$context}_meta_fields_{$field}_input",
			match ( $context ) {
				'post'      => "wp_seo_post_id_to_the_meta_{$field}_input",
				'add_term'  => "wp_seo_the_meta_{$field}_input",
				'edit_term' => "wp_seo_term_data_to_the_meta_{$field}_input",
			},
			10,
			'edit_term' === $context ? 2 : 1
		);

		// After input.
		add_action(
			"wp_seo_{$context}_meta_fields_after_{$field}_input",
			match( $context ) {
				'post'      => $show_char_count
					? "wp_seo_post_id_to_the_{$field}_character_count"
					: "wp_seo_the_meta_{$field}_after_input",
				'add_term'  => $show_char_count
					? "wp_seo_the_{$field}_character_count"
					: "wp_seo_the_meta_{$field}_after_input",
				'edit_term' => $show_char_count
					? "wp_seo_term_data_to_the_{$field}_character_count"
					: "wp_seo_the_meta_{$field}_after_input",
			},
			10,
			'edit_term' === $context ? 2 : 1
		);
	}
}
