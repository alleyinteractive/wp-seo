<?php
/**
 * Tests for internal-functions.php.
 *
 * @package WP_SEO
 */

class WP_SEO_Internal_Functions_Tests extends WP_SEO_Testcase {
	/**
	 * Test that enabling formatting-tag safe mode has the expected effects.
	 */
	function test_enable_formatting_tag_safe_mode() {
		$unsafe_string = 'Foo #bar#';

		// Before.
		$this->assertSame( $unsafe_string, WP_SEO()->format( $unsafe_string ) );

		wp_seo_enable_formatting_tag_safe_mode();

		// After.
		$this->assertWPError( WP_SEO()->format( $unsafe_string ) );
	}

	function setUp() {
		parent::setUp();
		update_option( WP_SEO_Settings::SLUG, array(
			'post_types' => array( 'post' ),
			'taxonomies' => array( 'category' ),
		) );
	}

	function tearDown() {
		parent::tearDown();
		delete_option( WP_SEO_Settings::SLUG );
	}

	/**
	 * Test that the plugin's settings page is always enqueueable.
	 */
	function test_is_admin_screen_enqueueable_on_settings_page() {
		$this->assertTrue( wp_seo_is_admin_screen_enqueueable( 'settings_page_' . WP_SEO_Settings::SLUG ) );
	}

	/**
	 * Test taxonomy add/edit screens, only for taxonomies with fields enabled.
	 */
	function test_is_admin_screen_enqueueable_on_taxonomy_screens() {
		set_current_screen( 'edit-tags.php' );
		get_current_screen()->taxonomy = 'category';
		$this->assertTrue( wp_seo_is_admin_screen_enqueueable( 'edit-tags.php' ) );

		set_current_screen( 'term.php' );
		get_current_screen()->taxonomy = 'category';
		$this->assertTrue( wp_seo_is_admin_screen_enqueueable( 'term.php' ) );

		set_current_screen( 'edit-tags.php' );
		get_current_screen()->taxonomy = 'post_tag';
		$this->assertFalse( wp_seo_is_admin_screen_enqueueable( 'edit-tags.php' ) );
	}

	/**
	 * Test post edit screens, only for post types with fields enabled and
	 * only in the classic editor.
	 */
	function test_is_admin_screen_enqueueable_on_post_screens() {
		set_current_screen( 'post.php' );
		get_current_screen()->post_type = 'page';
		$this->assertFalse( wp_seo_is_admin_screen_enqueueable( 'post.php' ) );

		set_current_screen( 'post-new.php' );
		get_current_screen()->post_type = 'post';

		if ( function_exists( 'use_block_editor_for_post_type' ) ) {
			add_filter( 'use_block_editor_for_post_type', '__return_true' );
			$this->assertFalse( wp_seo_is_admin_screen_enqueueable( 'post-new.php' ) );

			add_filter( 'use_block_editor_for_post_type', '__return_false' );
			$this->assertTrue( wp_seo_is_admin_screen_enqueueable( 'post-new.php' ) );
		} else {
			$this->assertTrue( wp_seo_is_admin_screen_enqueueable( 'post-new.php' ) );
		}
	}

	/**
	 * Test that unrelated admin screens never get the assets.
	 */
	function test_is_admin_screen_enqueueable_on_unrelated_screen() {
		set_current_screen( 'edit.php' );
		get_current_screen()->post_type = 'post';
		$this->assertFalse( wp_seo_is_admin_screen_enqueueable( 'edit.php' ) );

		set_current_screen( 'index.php' );
		$this->assertFalse( wp_seo_is_admin_screen_enqueueable( 'index.php' ) );
	}
}
