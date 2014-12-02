<?php
/**
 * Tests for whether post types and taxonomies have per-object fields enabled.
 *
 * @package WP SEO
 */
class WP_SEO_Has_Fields_Tests extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();
		update_option( WP_SEO_Settings::SLUG, array(
			'post_types' => array( 'post' ),
			'taxonomies' => array( 'category' ),
		) );
	}

	function tearDown() {
		parent::tearDown();
		// Clean up after ourselves.
		delete_option( WP_SEO_Settings::SLUG );
	}

	function test_has_post_fields() {
		$this->assertTrue( WP_SEO_Settings()->has_post_fields( 'post' ) );
		$this->assertFalse( WP_SEO_Settings()->has_post_fields( 'page' ) );
	}

	function test_has_term_fields() {
		$this->assertTrue( WP_SEO_Settings()->has_term_fields( 'category' ) );
		$this->assertFalse( WP_SEO_Settings()->has_term_fields( 'post_tag' ) );
	}

}