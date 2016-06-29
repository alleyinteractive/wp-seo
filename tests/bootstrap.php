<?php

/**
 * Mimic WP_UnitTestCase::reset_post_types() for supporting older versions of WP.
 *
 * @see https://core.trac.wordpress.org/changeset/29860.
 */
function _wp_seo_reset_post_types() {
	foreach ( get_post_types() as $pt ) {
		_unregister_post_type( $pt );
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
		_unregister_taxonomy( $tax );
	}
	create_initial_taxonomies();
}

$_tests_dir = getenv('WP_TESTS_DIR');
if ( !$_tests_dir ) $_tests_dir = '/tmp/wordpress-tests-lib';

require_once $_tests_dir . '/includes/functions.php';

function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../wp-seo.php';
	wp_seo_load_admin_files();
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require_once $_tests_dir . '/includes/bootstrap.php';
require_once dirname( __FILE__ ) . '/includes/class-wp-seo-testcase.php';
