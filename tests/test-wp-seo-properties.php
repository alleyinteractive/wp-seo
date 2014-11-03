<?php
/**
 * Test that properties in WP_SEO include only allowed values after set_properties().
 *
 * @package WP SEO
 */
class WP_SEO_Properties_Tests extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();
		add_filter( 'wp_seo_formatting_tags', array( $this, '_add_mock' ) );
		add_filter( 'wp_seo_formatting_tags', array( $this, '_add_illegals' ) );
		WP_SEO()->set_properties();
	}

	function tearDown() {
		parent::tearDown();
		// Leave the place as we found it.
		remove_filter( 'wp_seo_formatting_tags', array( $this, '_add_mock' ) );
		remove_filter( 'wp_seo_formatting_tags', array( $this, '_add_illegals' ) );
		WP_SEO()->set_properties();
	}

	function _add_mock( $tags ) {
		$tags['is_a_tag'] = $this->getMockForAbstractClass( 'WP_SEO_Formatting_Tag' );
		return $tags;
	}

	function _add_illegals( $tags ) {
		$tags['is_non_object'] = true;
		$tags['is_wrong_object'] = new stdClass;
		return $tags;
	}

	function test_legal_tag() {
		$this->assertArrayHasKey( 'is_a_tag', WP_SEO()->formatting_tags );
	}

	function test_illegal_tags() {
		$this->assertArrayNotHasKey( 'is_non_object', WP_SEO()->formatting_tags );
		$this->assertArrayNotHasKey( 'is_wrong_object', WP_SEO()->formatting_tags );
	}

}