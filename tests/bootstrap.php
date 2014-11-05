<?php

/**
 * Mimic WP_UnitTestCase::reset_post_types() when testing older versions of WP.
 *
 * @see https://core.trac.wordpress.org/changeset/29860.
 */
function _wp_seo_reset_post_types() {
	if ( ! method_exists( 'WP_UnitTestCase', 'reset_post_types' ) ) {
		call_user_func( array( 'WP_UnitTestCase', 'reset_post_types' ) );
	} else {
		foreach ( get_post_types() as $pt ) {
			_unregister_post_type( $pt );
		}
		create_initial_post_types();
	}
}

/**
 * Mimic WP_UnitTestCase::reset_taxonomies() when testing older versions of WP.
 *
 * @see https://core.trac.wordpress.org/changeset/29860.
 */
function _wp_seo_reset_taxonomies() {
	if ( ! method_exists( 'WP_UnitTestCase', 'reset_taxonomies' ) ) {
		call_user_func( array( 'WP_UnitTestCase', 'reset_taxonomies' ) );
	} else {
		foreach ( get_taxonomies() as $tax ) {
			_unregister_taxonomy( $tax );
		}
		create_initial_taxonomies();
	}
}

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../wp-seo.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $_tests_dir . '/includes/bootstrap.php';
