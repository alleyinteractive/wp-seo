<?php
/**
 * Tests for registering metaboxes, the metabox markup, and saving data.
 *
 * @package WP SEO
 */
class WP_SEO_Metaboxes_Tests extends WP_UnitTestCase {

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
		wp_set_current_user( 1 );
	}

	function test_add_meta_boxes() {
		WP_SEO()->add_meta_boxes( 'post' );

		global $wp_meta_boxes;
		$this->assertTrue( isset( $wp_meta_boxes['post']['normal']['high']['wp_seo'] ) );
	}

	function test_add_meta_boxes_no_post_field() {
		WP_SEO()->add_meta_boxes( 'page' );

		global $wp_meta_boxes;
		$this->assertFalse( isset( $wp_meta_boxes['page']['normal']['high']['wp_seo'] ) );
	}

	function test_add_meta_boxes_filters() {
		add_filter( 'wp_seo_meta_box_context', function() {
			return 'advanced';
		} );

		add_filter( 'wp_seo_meta_box_priority', function() {
			return 'low';
		} );

		WP_SEO()->add_meta_boxes( 'post' );

		global $wp_meta_boxes;
		$this->assertTrue( isset( $wp_meta_boxes['post']['advanced']['low']['wp_seo'] ) );
	}

	/**
	 * Test that markup for post fields has our expected fields and values.
	 */
	function test_post_meta_fields() {
		$post_ID = $this->factory->post->create();
		$title = rand_str();
		$description = rand_str();
		$keywords = rand_str();
		add_post_meta( $post_ID, '_meta_title', $title );
		add_post_meta( $post_ID, '_meta_description', $description );
		add_post_meta( $post_ID, '_meta_keywords', $keywords );

		$post = get_post( $post_ID );
		$html = get_echo( array( WP_SEO(), 'post_meta_fields' ), array( $post ) );

		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="wp-seo-nonce"/', $html );
		$this->assertContains( 'name="seo_meta[title]" value="' . $title . '" size="96"', $html );
		$this->assertContains( sprintf( '<noscript>%d (save changes to update)</noscript>', strlen( $title ) ), $html );
		$this->assertRegExp( "/<textarea.*?>{$description}<\/textarea>/", $html );
		$this->assertContains( sprintf( '<noscript>%d (save changes to update)</noscript>', strlen( $description ) ), $html );
		$this->assertRegExp( "/<textarea.*?>{$keywords}<\/textarea>/", $html );
	}

	/**
	 * Test most ways saving post fields can fail and the one way they succeed.
	 */
	function test_save_post_fields() {
		wp_set_current_user( 1 );
		$post_ID = $this->factory->post->create();
		$post = get_post( $post_ID );
		$html = get_echo( array( WP_SEO(), 'post_meta_fields' ), array( $post ) );

		// No $_POST.
		$this->assertNull( WP_SEO()->save_post_fields( $post_ID ) );

		// Wrong post type.
		$_POST = array(
			'post_type' => 'page',
		);
		$this->assertNull( WP_SEO()->save_post_fields( $post_ID ) );

		$_POST = array(
			'post_type' => 'post',
		);

		// Incapable user.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
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
		$this->assertEmpty( get_post_meta( $post_ID, '_meta_keywords', true ) );

		$title = rand_str();
		$description = rand_str();
		$keywords = rand_str();

		// add_magic_quotes() to simulate wp_magic_quotes().
		$_POST['seo_meta'] = add_magic_quotes( array(
			'title' => $title,
			'description' => $description . '<script>meta</script>',
			'keywords' => $keywords,
		) );

		// Successful save.
		WP_SEO()->save_post_fields( $post_ID );
		$this->assertEquals( $title, get_post_meta( $post_ID, '_meta_title', true ) );
		$this->assertEquals( $description, get_post_meta( $post_ID, '_meta_description', true ) );
		$this->assertEquals( $keywords, get_post_meta( $post_ID, '_meta_keywords', true ) );

		$title = "Is your name O'Reilly?";
		$description = 'What is Folder\SubFolder\File.txt?';
		$keywords = '';

		// Successfully save data with slashes. add_magic_quotes() to simulate wp_magic_quotes().
		$_POST['seo_meta'] = add_magic_quotes( array(
			'title'       => $title,
			'description' => $description,
			'keywords'    => $keywords,
		) );

		WP_SEO()->save_post_fields( $post_ID );
		$this->assertEquals( $title, get_post_meta( $post_ID, '_meta_title', true ) );
		$this->assertEquals( $description, get_post_meta( $post_ID, '_meta_description', true ) );
		$this->assertEquals( $keywords, get_post_meta( $post_ID, '_meta_keywords', true ) );
	}

	/**
	 * Test that actions are added for term fields to the correct taxonomies.
	 */
	function test_add_term_boxes() {
		WP_SEO()->add_term_boxes();
		$this->assertNotFalse( has_action( 'category_add_form_fields', array( WP_SEO(), 'add_term_meta_fields' ) ) );
		$this->assertNotFalse( has_action( 'category_edit_form', array( WP_SEO(), 'edit_term_meta_fields' ) ) );
		$this->assertFalse( has_action( 'post_tag_add_form_fields', array( WP_SEO(), 'add_term_meta_fields' ) ) );
		$this->assertFalse( has_action( 'post_tag_edit_form', array( WP_SEO(), 'edit_term_meta_fields' ) ) );
	}

	/**
	 * On a "New Term" form, check that our nonce and field names are present.
	 */
	function test_add_term_meta_fields() {
		$html = get_echo( array( WP_SEO(), 'add_term_meta_fields' ), array( 'category' ) );

		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="wp-seo-nonce"/', $html );
		$this->assertContains( 'name="seo_meta[title]"', $html );
		$this->assertContains( 'name="seo_meta[description]"', $html );
		$this->assertContains( 'name="seo_meta[keywords]"', $html );
	}

	/**
	 * Test that markup for term fields has our expected fields and values.
	 */
	function test_edit_term_meta_fields() {
		$category_ID = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		$category = get_term( $category_ID, 'category' );

		$title = rand_str();
		$description = rand_str();
		$keywords = rand_str();

		update_option(
			WP_SEO()->get_term_option_name( $category ),
			array(
				'title' => $title,
				'description' => $description,
				'keywords' => $keywords,
			)
		);

		$html = get_echo( array( WP_SEO(), 'edit_term_meta_fields' ), array( $category, 'category' ) );

		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="wp-seo-nonce"/', $html );
		$this->assertContains( 'name="seo_meta[title]" value="' . $title . '" size="96"', $html );
		$this->assertContains( sprintf( '<noscript>%d (save changes to update)</noscript>', strlen( $title ) ), $html );
		$this->assertRegExp( "/<textarea.*?>{$description}<\/textarea>/", $html );
		$this->assertContains( sprintf( '<noscript>%d (save changes to update)</noscript>', strlen( $description ) ), $html );
		$this->assertRegExp( "/<textarea.*?>{$keywords}<\/textarea>/", $html );
	}

	function test_save_term_fields() {
		wp_set_current_user( 1 );
		$category_ID = $this->factory->term->create( array( 'taxonomy' => 'category' ) );
		$category = get_term( $category_ID, 'category' );
		$html = get_echo( array( WP_SEO(), 'edit_term_meta_fields' ), array( $category, 'category' ) );

		// No $_POST.
		$this->assertNull( WP_SEO()->save_term_fields( $category_ID, $category->term_taxonomy_id, 'category' ) );

		$_POST['taxonomy'] = 'category';

		// Wrong taxonomy.
		$this->assertNull( WP_SEO()->save_term_fields( $category_ID, $category->term_taxonomy_id, 'post_tag' ) );

		// Incapable user.
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
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
		$keywords = rand_str();

		$_POST['seo_meta'] = array(
			'title' => $title,
			'description' => $description . '<script>meta</script>',
			'keywords' => $keywords,
		);

		// Successful add_option().
		WP_SEO()->save_term_fields( $category_ID, $category->term_taxonomy_id, 'category' );
		$this->assertSame(
			array(
				'title' => $title,
				'description' => $description,
				'keywords' => $keywords,
			),
			get_option( WP_SEO()->get_term_option_name( $category ) )
		);

		$updated_title = rand_str();
		$updated_keywords = rand_str();

		$_POST['seo_meta'] = array(
			'title' => $updated_title,
			'keywords' => $updated_keywords,
		);

		// Successful update_option().
		WP_SEO()->save_term_fields( $category_ID, $category->term_taxonomy_id, 'category' );
		$this->assertSame(
			array(
				'title' => $updated_title,
				'description' => '',
				'keywords' => $updated_keywords,
			),
			get_option( WP_SEO()->get_term_option_name( $category ) )
		);
	}

}
