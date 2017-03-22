<?php
/**
 * Tests WP SEO migration.
 *
 * @package WP SEO
 */
class WP_SEO_Taxonomy_Migration_Test extends WP_UnitTestCase {

	function test_has_not_migrated() {
		$this->assertFalse( WP_SEO_Settings()->has_taxonomy_migration_run() );
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
