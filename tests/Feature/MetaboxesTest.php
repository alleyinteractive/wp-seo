<?php
/**
 * WP SEO Tests: Tests for registering metaboxes, the metabox markup, and saving data.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;
use WP_SEO_Settings;
use WP_SEO;

class MetaboxesTest extends TestCase {
	function setUp(): void {
		parent::setUp();
		update_option( WP_SEO_Settings::SLUG, [
			'post_types' => [ 'post' ],
			'taxonomies' => [ 'category' ],
		] );
		require_once ABSPATH . 'wp-admin/includes/template.php';
	}

	function tearDown(): void {
		parent::tearDown();
		// Clean up after ourselves.
		delete_option( WP_SEO_Settings::SLUG );
		wp_set_current_user( 1 );
	}

	/**
	 * These tests can be reenabled once we have a way to mock being in wp-admin context.
	 */
	// function test_add_meta_boxes() {
	// 	WP_SEO()->add_meta_boxes( 'post' );

	// 	global $wp_meta_boxes;
	// 	$this->assertTrue( isset( $wp_meta_boxes['post']['normal']['high']['wp_seo'] ) );
	// }

	// function test_add_meta_boxes_no_post_field() {
	// 	WP_SEO()->add_meta_boxes( 'page' );

	// 	global $wp_meta_boxes;
	// 	$this->assertFalse( isset( $wp_meta_boxes['page']['normal']['high']['wp_seo'] ) );
	// }

	// function test_add_meta_boxes_filters() {
	// 	add_filter( 'wp_seo_meta_box_context', function() {
	// 		return 'advanced';
	// 	} );

	// 	add_filter( 'wp_seo_meta_box_priority', function() {
	// 		return 'low';
	// 	} );

	// 	WP_SEO()->add_meta_boxes( 'post' );

	// 	global $wp_meta_boxes;
	// 	$this->assertTrue( isset( $wp_meta_boxes['post']['advanced']['low']['wp_seo'] ) );
	// }

	/**
	 * Test that markup for post fields has our expected fields and values.
	 */
	function test_post_meta_fields() {
		$post_ID = $this->factory->post->create();
		$title = rand_str();
		$description = rand_str();
		add_post_meta( $post_ID, '_meta_title', $title );
		add_post_meta( $post_ID, '_meta_description', $description );

		$post = get_post( $post_ID );
		// Capture the output of the function.
		ob_start();
		WP_SEO()->post_meta_fields( $post );
		$html = ob_get_clean();

		self::assertStringContainsString( 'name="seo_meta[title]" value="' . $title . '" size="96"', $html );
		self::assertMatchesRegularExpression( '/<input[^>]+type="hidden"[^>]+name="wp-seo-nonce"/', $html );
		self::assertMatchesRegularExpression( "/<textarea.*?>{$description}<\/textarea>/", $html );
		self::assertStringContainsString( sprintf( '<noscript>%d (save changes to update)</noscript>', strlen( $title ) ), $html );
		self::assertStringContainsString( sprintf( '<noscript>%d (save changes to update)</noscript>', strlen( $description ) ), $html );
	}

	/**
	 * Test most ways saving post fields can fail and the one way they succeed.
	 */
	function test_save_post_fields() {
		wp_set_current_user( 1 );
		$post_ID = $this->factory->post->create();
		$post = get_post( $post_ID );

		// Capture the output of the function.
		ob_start();
		WP_SEO()->post_meta_fields( $post );
		$html = ob_get_clean();

		// No $_POST.
		$this->assertNull( WP_SEO()->save_post_fields( $post_ID ) );

		// Wrong post type.
		$_POST = [
			'post_type' => 'page',
		];
		$this->assertNull( WP_SEO()->save_post_fields( $post_ID ) );

		$_POST = [
			'post_type' => 'post',
		];

		// Incapable user.
		$user_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );
		$this->assertNull( WP_SEO()->save_post_fields( $post_ID ) );

		wp_set_current_user( 1 );

		// No nonce.
		$this->assertNull( WP_SEO()->save_post_fields( $post_ID ) );

		$_POST['wp-seo-nonce'] = rand_str();

		// Wrong nonce.
		$this->assertNull( WP_SEO()->save_post_fields( $post_ID ) );

		preg_match( "/name=\"wp-seo-nonce\" value=\"(.*?)\"/m", $html, $nonce_matches );
		$_POST['wp-seo-nonce'] = $nonce_matches[1];

		// No POST'ed ID.
		$this->assertNull( WP_SEO()->save_post_fields( $post_ID ) );

		$_POST['post_ID'] = $post_ID;

		// No SEO data.
		WP_SEO()->save_post_fields( $post_ID );
		$this->assertEmpty( get_post_meta( $post_ID, '_meta_title', true ) );
		$this->assertEmpty( get_post_meta( $post_ID, '_meta_description', true ) );

		$title = rand_str();
		$description = rand_str();

		// add_magic_quotes() to simulate wp_magic_quotes().
		$_POST['seo_meta'] = add_magic_quotes( [
			'title' => $title,
			'description' => $description . '<script>meta</script>',
		] );

		// Successful save.
		WP_SEO()->save_post_fields( $post_ID );
		$this->assertEquals( $title, get_post_meta( $post_ID, '_meta_title', true ) );
		$this->assertEquals( $description, get_post_meta( $post_ID, '_meta_description', true ) );

		$title = "Is your name O'Reilly?";
		$description = 'What is Folder\SubFolder\File.txt?';

		// Successfully save data with slashes. add_magic_quotes() to simulate wp_magic_quotes().
		$_POST['seo_meta'] = add_magic_quotes( [
			'title'       => $title,
			'description' => $description,
		] );

		WP_SEO()->save_post_fields( $post_ID );
		$this->assertEquals( $title, get_post_meta( $post_ID, '_meta_title', true ) );
		$this->assertEquals( $description, get_post_meta( $post_ID, '_meta_description', true ) );
	}

	/**
	 * Test that actions are added for term fields to the correct taxonomies.
	 */
	function test_add_term_boxes() {
		WP_SEO()->add_term_boxes();
		$this->assertNotFalse( has_action( 'category_add_form_fields', [ WP_SEO(), 'add_term_meta_fields' ] ) );
		$this->assertNotFalse( has_action( 'category_edit_form', [ WP_SEO(), 'edit_term_meta_fields' ] ) );
		$this->assertFalse( has_action( 'post_tag_add_form_fields', [ WP_SEO(), 'add_term_meta_fields' ] ) );
		$this->assertFalse( has_action( 'post_tag_edit_form', [ WP_SEO(), 'edit_term_meta_fields' ] ) );
	}

	/**
	 * On a "New Term" form, check that our nonce and field names are present.
	 */
	function test_add_term_meta_fields() {
		// Capture the output of the function.
		ob_start();
		WP_SEO()->add_term_meta_fields( 'category' );
		$html = ob_get_clean();

		self::assertMatchesRegularExpression( '/<input[^>]+type="hidden"[^>]+name="wp-seo-nonce"/', $html );
		self::assertStringContainsString( 'name="seo_meta[title]"', $html );
		self::assertStringContainsString( 'name="seo_meta[description]"', $html );
	}

	/**
	 * Test that markup for term fields has our expected fields and values.
	 */
	function test_edit_term_meta_fields() {
		$category_ID = $this->factory->term->create( [ 'taxonomy' => 'category' ] );
		$category = get_term( $category_ID, 'category' );

		$title = rand_str();
		$description = rand_str();

		update_option(
			WP_SEO()->get_term_option_name( $category ),
			[
				'title' => $title,
				'description' => $description,
			]
		);

		// Capture the output of the function.
		ob_start();
		WP_SEO()->edit_term_meta_fields( $category, 'category' );
		$html = ob_get_clean();

		self::assertMatchesRegularExpression( '/<input[^>]+type="hidden"[^>]+name="wp-seo-nonce"/', $html );
		self::assertMatchesRegularExpression( "/<textarea.*?>{$description}<\/textarea>/", $html );
		self::assertStringContainsString( 'name="seo_meta[title]" value="' . $title . '" size="96"', $html );
		self::assertStringContainsString( sprintf( '<noscript>%d (save changes to update)</noscript>', strlen( $title ) ), $html );
		self::assertStringContainsString( sprintf( '<noscript>%d (save changes to update)</noscript>', strlen( $description ) ), $html );
	}

	function test_save_term_fields() {
		wp_set_current_user( 1 );
		$category_ID = $this->factory->term->create( [ 'taxonomy' => 'category' ] );
		$category = get_term( $category_ID, 'category' );

		// Capture the output of the function.
		ob_start();
		WP_SEO()->edit_term_meta_fields( $category, 'category' );
		$html = ob_get_clean();

		// No $_POST.
		$this->assertNull( WP_SEO()->save_term_fields( $category_ID, $category->term_taxonomy_id, 'category' ) );

		$_POST['taxonomy'] = 'category';

		// Wrong taxonomy.
		$this->assertNull( WP_SEO()->save_term_fields( $category_ID, $category->term_taxonomy_id, 'post_tag' ) );

		// Incapable user.
		$user_id = $this->factory->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );
		$this->assertNull( WP_SEO()->save_term_fields( $category_ID, $category->term_taxonomy_id, 'category' ) );

		wp_set_current_user( 1 );

		// No nonce.
		$this->assertNull( WP_SEO()->save_term_fields( $category_ID, $category->term_taxonomy_id, 'category' ) );

		$_POST['wp-seo-nonce'] = rand_str();

		// Wrong nonce.
		$this->assertNull( WP_SEO()->save_term_fields( $category_ID, $category->term_taxonomy_id, 'category' ) );

		preg_match( "/name=\"wp-seo-nonce\" value=\"(.*?)\"/m", $html, $nonce_matches );
		$_POST['wp-seo-nonce'] = $nonce_matches[1];

		// No SEO data? No option.
		WP_SEO()->save_term_fields( $category_ID, $category->term_taxonomy_id, 'category' );
		$this->assertFalse( get_option( WP_SEO()->get_term_option_name( $category ) ) );

		$title = rand_str();
		$description = rand_str();

		$_POST['seo_meta'] = [
			'title' => $title,
			'description' => $description . '<script>meta</script>',
		];

		// Successful add_option().
		WP_SEO()->save_term_fields( $category_ID, $category->term_taxonomy_id, 'category' );
		$this->assertSame(
			[
				'title' => $title,
				'description' => $description,
			],
			get_option( WP_SEO()->get_term_option_name( $category ) )
		);

		$updated_title = rand_str();

		$_POST['seo_meta'] = [
			'title' => $updated_title,
		];

		// Successful update_option().
		WP_SEO()->save_term_fields( $category_ID, $category->term_taxonomy_id, 'category' );
		$this->assertSame(
			[
				'title' => $updated_title,
				'description' => '',
			],
			get_option( WP_SEO()->get_term_option_name( $category ) )
		);
	}

}
