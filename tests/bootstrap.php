<?php
/**
 * WP SEO Tests: Bootstrap
 *
 * phpcs:disable Squiz.Commenting.InlineComment.InvalidEndChar
 *
 * @package wp-seo
 */

/**
 * Visit {@see https://mantle.alley.com/testing/test-framework.html} to learn more.
 */
\Mantle\Testing\manager()
	// Rsync the plugin to plugins/wp-seo when testing.
	->maybe_rsync_plugin()
	// Load the main file of the plugin.
	->loaded( fn () => require_once __DIR__ . '/../wp-seo.php' )
	->install();

/**
 * Mimic WP_UnitTestCase::reset_post_types() for supporting older versions of WP.
 *
 * @see https://core.trac.wordpress.org/changeset/29860.
 */
function _wp_seo_reset_post_types() {
	foreach ( get_post_types() as $pt ) {
		unregister_post_type( $pt );
	}
	create_initial_post_types();
}

/**
 * Mimic WP_UnitTestCase::reset_taxonomies() for supporting older versions of WP.
 *
 * @see https://core.trac.wordpress.org/changeset/29860.
 */
function _wp_seo_reset_taxonomies() {
	foreach ( get_taxonomies() as $tax ) {
		unregister_taxonomy( $tax );
	}
	create_initial_taxonomies();
}

/**
 * Load the admin files.
 */
function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../wp-seo.php';
	wp_seo_load_admin_files();
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );
