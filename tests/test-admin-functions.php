<?php
/**
 * Tests for admin-functions.php.
 *
 * @package WP_SEO
 */

class WP_SEO_Admin_Functions_Tests extends WP_SEO_Testcase {
	/**
	 * Sanity-check that the post_id_to_* and term_data_to_* functions use saved values.
	 *
	 * @dataProvider data_post_id_to_and_term_data_to
	 */
	function test_admin_functions_contain( $function, $should, $contain, $args ) {
		$this->assertContains( $contain, get_echo( $function, $args ), $should );
	}

	/**
	 * Combines the post_id_to_* and term_data_to_* data providers.
	 */
	function data_post_id_to_and_term_data_to() {
		return array_merge( $this->data_post_id_to_functions(), $this->data_term_data_to_functions() );
	}

	/**
	 * @return array {
	 *     @type string $function Function name.
	 *     @type string $should Message to describe the expected behavior on failure.
	 *     @type string $contain Value the function output should contain, given $args.
	 *     @type array $args Function arguments (for these functions, a post ID).
	 * }
	 */
	function data_post_id_to_functions() {
		$meta_title             = rand_str( rand( 32, 64 ) );
		$meta_description       = rand_str( rand( 32, 64 ) );
		$meta_keywords          = rand_str( rand( 32, 64 ) );
		$meta_og_title          = rand_str( rand( 32, 64 ) );
		$meta_og_description    = rand_str( rand( 32, 64 ) );
		$meta_og_type           = rand_str( rand( 32, 64 ) );
		$meta_og_image          = $this->factory->attachment->create();

		$post_id = $this->factory->post->create( array(
			'meta_input' => array(
				'_meta_title'          => $meta_title,
				'_meta_description'    => $meta_description,
				'_meta_keywords'       => $meta_keywords,
				'_meta_og_title'       => $meta_title,
				'_meta_og_description' => $meta_description,
				'_meta_og_type'        => $meta_og_type,
				'_meta_og_image'       => $meta_og_image,
			),
		) );

		return array(
			array(
				'wp_seo_post_id_to_the_meta_title_input',
				'Should print the title value in post meta',
				$meta_title,
				array( $post_id ),
			),
			array(
				'wp_seo_post_id_to_the_title_character_count',
				'Should count the title value in post meta',
				(string) strlen( $meta_title ),
				array( $post_id ),
			),
			array(
				'wp_seo_post_id_to_the_meta_description_input',
				'Should print the description value in post meta',
				$meta_description,
				array( $post_id ),
			),
			array(
				'wp_seo_post_id_to_the_description_character_count',
				'Should count the description value in post meta',
				(string) strlen( $meta_description ),
				array( $post_id ),
			),
			array(
				'wp_seo_post_id_to_the_meta_keywords_input',
				'Should print the keyword value in post meta',
				$meta_keywords,
				array( $post_id ),
			),
			array(
				'wp_seo_post_id_to_the_meta_og_title_input',
				'Should print the OG title value in post meta',
				$meta_og_title,
				array( $post_id ),
			),
			array(
				'wp_seo_post_id_to_the_og_title_character_count',
				'Should count the OG title value in post meta',
				(string) strlen( $meta_og_title ),
				array( $post_id ),
			),
			array(
				'wp_seo_post_id_to_the_meta_og_description_input',
				'Should print the OG description value in post meta',
				$meta_og_description,
				array( $post_id ),
			),
			array(
				'wp_seo_post_id_to_the_og_description_character_count',
				'Should count the OG description value in post meta',
				(string) strlen( $meta_og_description ),
				array( $post_id ),
			),
			array(
				'wp_seo_post_id_to_the_meta_og_type_input',
				'Should print the OG type value in post meta',
				$meta_og_type,
				array( $post_id ),
			),
			array(
				'wp_seo_post_id_to_the_meta_og_image_input',
				'Should print the OG image value in post meta',
				wp_get_attachment_image_src( $meta_og_image ),
				array( $post_id ),
			),
		);
	}

	/**
	 * @return array {
	 *     @type string $function Function name.
	 *     @type string $contain Value the function output should contain, given $args.
	 *     @type array $args Function arguments (for these functions, a term's ID and taxonomy).
	 * }
	 */
	function data_term_data_to_functions() {
		$meta_title             = rand_str( rand( 32, 64 ) );
		$meta_description       = rand_str( rand( 32, 64 ) );
		$meta_keywords          = rand_str( rand( 32, 64 ) );
		$meta_og_title          = rand_str( rand( 32, 64 ) );
		$meta_og_description    = rand_str( rand( 32, 64 ) );
		$meta_og_type           = rand_str( rand( 32, 64 ) );
		$meta_og_image          = $this->factory->attachment->create();

		$term = $this->create_and_get_term_with_option( array(
			'title' => $title,
			'description'    => $description,
			'keywords'       => $keywords,
			'og_title'       => $meta_title,
			'og_description' => $meta_description,
			'og_type'        => $meta_og_type,
			'og_image'       => $meta_og_image,
		) );

		return array(
			array(
				'wp_seo_term_data_to_the_meta_title_input',
				'Should print the title value in the term options',
				$title,
				array( $term->term_id, $term->taxonomy ),
			),
			array(
				'wp_seo_term_data_to_the_title_character_count',
				'Should count the title value in the term options',
				(string) strlen( $title ),
				array( $term->term_id, $term->taxonomy ),
			),
			array(
				'wp_seo_term_data_to_the_meta_description_input',
				'Should print the description value in the term options',
				$description,
				array( $term->term_id, $term->taxonomy ),
			),
			array(
				'wp_seo_term_data_to_the_description_character_count',
				'Should count the description value in the term options',
				(string) strlen( $description ),
				array( $term->term_id, $term->taxonomy ),
			),
			array(
				'wp_seo_term_data_to_the_meta_keywords_input',
				'Should print the keyword value in the term options',
				$keywords,
				array( $term->term_id, $term->taxonomy ),
			),
			array(
				'wp_seo_term_data_to_the_meta_og_title_input',
				'Should print the OG title value in post meta',
				$meta_og_title,
				array( $term_data ),
			),
			array(
				'wp_seo_term_data_to_the_og_title_character_count',
				'Should count the OG title value in post meta',
				(string) strlen( $meta_og_title ),
				array( $term_data ),
			),
			array(
				'wp_seo_term_data_to_the_meta_og_description_input',
				'Should print the OG description value in post meta',
				$meta_og_description,
				array( $term_data ),
			),
			array(
				'wp_seo_term_data_to_the_og_description_character_count',
				'Should count the OG description value in post meta',
				(string) strlen( $meta_og_description ),
				array( $term_data ),
			),
			array(
				'wp_seo_term_data_to_the_meta_og_type_input',
				'Should print the OG type value in post meta',
				$meta_og_type,
				array( $term_data ),
			),
			array(
				'wp_seo_term_data_to_the_meta_og_image_input',
				'Should print the OG image value in post meta',
				wp_get_attachment_image_src( $meta_og_image ),
				array( $term_data ),
			),
		);
	}
}
