<?php
/**
 * Tests WP SEO migration.
 *
 * @package WP SEO
 */
class WP_SEO_Taxonomy_Migration_Test extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();
		delete_option( WP_SEO_Settings::SLUG );
		WP_SEO_Settings()->set_options();
	}

	function tearDown() {
		parent::tearDown();
		delete_option( WP_SEO_Settings::SLUG );
	}

	function test_should_migration_run() {
		$this->assertFalse( WP_SEO_Settings()->should_taxonomy_migration_run() );
	}

	function test_should_migration_run_with_filter() {
		add_filter( 'wp_seo_should_taxonomy_migration_run', '__return_true' );
		$this->assertTrue( WP_SEO_Settings()->should_taxonomy_migration_run() );
	}

	function test_new_site_has_migrated() {
		$this->assertTrue( WP_SEO_Settings()->has_taxonomy_migration_run() );
	}

	function test_has_migrated() {
		update_option( WP_SEO_Settings::SLUG, WP_SEO_Settings()->sanitize_options( array(
			'post_types' => array( 'post' ),
			'taxonomies' => array( 'category' ),
		) ) );
		WP_SEO_Settings()->set_options();
		$this->assertTrue( WP_SEO_Settings()->has_taxonomy_migration_run() );
	}
}
