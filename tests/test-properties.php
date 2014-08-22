<?php

class WP_SEO_Properties_Tests extends WP_UnitTestCase {

	function setUp() {
		parent::setUp();
	}

	function _add_mock( $tags ) {
		$tags['is_a_tag'] = $this->getMockForAbstractClass( 'WP_SEO_Formatting_Tag' );
		return $tags;
	}

	function _add_illegal( $tags ) {
		$tags['is_non_object'] = true;
		$tags['is_wrong_object'] = new stdClass;
		return $tags;
	}

	function test_legal_tag() {
		add_filter( 'wp_seo_formatting_tags', array( $this, '_add_mock' ) );

		WP_SEO()->reset_properties();

		$this->assertArrayHasKey( 'is_a_tag', WP_SEO()->formatting_tags );
	}

	function test_illegal_tags() {
		add_filter( 'wp_seo_formatting_tags', array( $this, '_add_illegal' ) );

		WP_SEO()->reset_properties();

		$this->assertArrayNotHasKey( 'is_non_object', WP_SEO()->formatting_tags );
		$this->assertArrayNotHasKey( 'is_wrong_object', WP_SEO()->formatting_tags );
	}

}