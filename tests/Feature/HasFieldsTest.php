<?php
/**
 * WP SEO Tests: Tests for whether post types and taxonomies have per-object fields enabled.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;
use WP_SEO_Settings;

class HasFieldsTest extends TestCase {
	function setUp(): void {
		parent::setUp();
		update_option( WP_SEO_Settings::SLUG, [
			'post_types' => [ 'post' ],
			'taxonomies' => [ 'category' ],
		] );
		WP_SEO_Settings()->set_options();
	}

	function tearDown(): void {
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