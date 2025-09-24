<?php
/**
 * Tests for class-wp-seo.php.
 *
 * @package WP_SEO
 */
class WP_SEO_WP_SEO_Tests extends WP_SEO_Testcase {
	function test_get_non_term_option() {
		$this->assertEmpty(
			WP_SEO::instance()->get_term_option( rand( -1, -100 ), rand_str() ),
			'Non-existent terms should not return term option data'
		);
	}

	function test_get_term_option() {
		$option_value = rand_str();
		$term = $this->create_and_get_term_with_option( $option_value );
		$this->assertSame(
			WP_SEO::instance()->get_term_option( $term->term_id, $term->taxonomy ),
			$option_value,
			'Valid terms with option data should be returned'
		);
	}

	function test_intersect_term_option() {
		$this->assertCount(
			3,
			WP_SEO::instance()->intersect_term_option( array() ),
			'Unexpected term option default key'
		);

		$this->assertArrayHasKey(
			'title',
			WP_SEO::instance()->intersect_term_option( array() ),
			'Unexpectedly missing default term option key'
		);

		$this->assertArrayHasKey(
			'description',
			WP_SEO::instance()->intersect_term_option( array() ),
			'Unexpectedly missing default term option key'
		);

		$this->assertArrayHasKey(
			'keywords',
			WP_SEO::instance()->intersect_term_option( array() ),
			'Unexpectedly missing default term option key'
		);

	}

	function test_get_canonical_url() {
		$post_ID = $this->factory->post->create();
		$post = get_post( $post_ID );
		$custom_url = 'https://example.com/' . rand_str();
		update_post_meta( $post_ID, '_meta_canonical_url', $custom_url );

		// Should return custom canonical URL if set.
		$this->assertSame(
			$custom_url,
			WP_SEO::instance()->get_canonical_url( 'https://default.com/', $post )
		);

		// Should return default if not set.
		delete_post_meta( $post_ID, '_meta_canonical_url' );
		$this->assertSame(
			'https://default.com/',
			WP_SEO::instance()->get_canonical_url( 'https://default.com/', $post )
		);
	}

	function test_wp_robots() {
		$post_ID = $this->factory->post->create();
		$post = get_post( $post_ID );

		// Simulate WP environment for is_singular().
		global $wp_query;
		$wp_query->post = $post;
		$wp_query->queried_object_id = $post_ID;
		$wp_query->is_singular = true;

		$directives = [
			'noindex',
			'nofollow',
			'noarchive',
			'nosnippet',
			'noimageindex',
			'notranslate',
		];

		// Verify that all directives are set when all are enabled.
		foreach ( $directives as $directive ) {
			update_post_meta( $post_ID, '_meta_robots_' . $directive, '1' );
		}

		$robots = WP_SEO::instance()->wp_robots( [] );

		foreach ( $directives as $directive ) {
			$this->assertArrayHasKey( $directive, $robots );
			$this->assertTrue( $robots[ $directive ] );
		}

		// Verify that no directives are set when none are enabled.
		foreach ( $directives as $directive ) {
			delete_post_meta( $post_ID, '_meta_robots_' . $directive );
		}

		$robots = WP_SEO::instance()->wp_robots( [] );

		foreach ( $directives as $directive ) {
			$this->assertArrayNotHasKey( $directive, $robots );
		}
	}
}
