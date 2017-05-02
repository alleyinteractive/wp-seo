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

	public function test_register_meta_registers_callbacks() {
		$meta_key = rand_str();
		$object_type = 'foo';
		$auth_callback = 'is_super_admin';
		$sanitize_callback = 'wp_kses_post';

		wp_seo()->register_meta( $meta_key, array(
			'auth_callback' => $auth_callback,
			'object_type' => array( $object_type ),
			'sanitize_callback' => $sanitize_callback,
		) );

		$this->assertNotFalse( has_filter(
			"auth_{$object_type}_meta_{$meta_key}",
			$auth_callback
		) );

		$this->assertNotFalse( has_filter(
			"sanitize_{$object_type}_meta_{$meta_key}",
			$sanitize_callback
		) );
	}

	public function test_register_meta_must_register_sanitize_callback() {
		$meta_key = rand_str();
		$object_type = 'foo';

		wp_seo()->register_meta( $meta_key, array(
			'object_type' => $object_type,
			'sanitize_callback' => false,
		) );

		$this->assertNotFalse( has_filter(
			"sanitize_{$object_type}_meta_{$meta_key}",
			'sanitize_text_field'
		) );
	}
}
