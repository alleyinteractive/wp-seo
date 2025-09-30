<?php
/**
 * WP SEO Tests: Test that properties in WP_SEO_Settings include only allowed values after set_properties().
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;
use WP_SEO;

class SettingsPropertiesTest extends TestCase {

	function tearDown(): void {
		parent::tearDown();
		// Leave the place as we found it.
		_wp_seo_reset_post_types();
		_wp_seo_reset_taxonomies();
		WP_SEO_Settings()->set_properties();
	}

	function test_allow_public_objects() {
		register_post_type( 'demo_public', [ 'rewrite' => true, 'has_archive' => true, 'public' => true ] );
		register_taxonomy( 'demo_public', 'post', [ 'public' => true ] );

		WP_SEO_Settings()->set_properties();

		$this->assertArrayHasKey( 'demo_public', WP_SEO_Settings()->get_single_post_types() );
		$this->assertArrayHasKey( 'demo_public', WP_SEO_Settings()->get_archived_post_types() );
		$this->assertArrayHasKey( 'demo_public', WP_SEO_Settings()->get_taxonomies() );
	}

	function test_disallow_private_objects() {
		register_post_type( 'demo_private', [ 'public' => false ] );
		register_taxonomy( 'demo_private', 'post', [ 'public' => false ] );

		WP_SEO_Settings()->set_properties();

		$this->assertArrayNotHasKey( 'demo_private', WP_SEO_Settings()->get_single_post_types() );
		$this->assertArrayNotHasKey( 'demo_private', WP_SEO_Settings()->get_archived_post_types() );
		$this->assertArrayNotHasKey( 'demo_private', WP_SEO_Settings()->get_taxonomies() );
	}

	function test_handle_post_types_without_archives() {
		register_post_type( 'demo_no_archive', [ 'rewrite' => true, 'has_archive' => false, 'public' => true ] );

		WP_SEO_Settings()->set_properties();

		$this->assertArrayHasKey( 'demo_no_archive', WP_SEO_Settings()->get_single_post_types() );
		$this->assertArrayNotHasKey( 'demo_no_archive', WP_SEO_Settings()->get_archived_post_types() );
	}

	function test_disallow_unlabeled_objects() {
		register_post_type( 'demo_no_label', [
			'label'       => false,
			'rewrite'     => true,
			'has_archive' => true,
			'public'      => true
		] );
		register_taxonomy( 'demo_no_label', 'post', [
			'label'  => false,
			'public' => true
		] );

		WP_SEO_Settings()->set_properties();

		$this->assertArrayNotHasKey( 'demo_no_label', WP_SEO_Settings()->get_single_post_types() );
		$this->assertArrayNotHasKey( 'demo_no_label', WP_SEO_Settings()->get_archived_post_types() );
		$this->assertArrayNotHasKey( 'demo_no_label', WP_SEO_Settings()->get_taxonomies() );
	}

}
