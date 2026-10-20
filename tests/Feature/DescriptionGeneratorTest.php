<?php
/**
 * DescriptionGeneratorTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Features\Description_Generator;
use Alley\WP\WP_SEO\Tests\Doubles\FakeAiProvider;
use Alley\WP\WP_SEO\Tests\TestCase;

use function Alley\WP\WP_SEO\generate_description;
use function Alley\WP\WP_SEO\get_description_generator_post_types;

/**
 * Tests for the Meta Description Generator feature.
 *
 * Prompts go through core to a fake provider, which replies with text a test
 * sets and makes no request.
 *
 * @link https://mantle.alley.com/docs/testing
 */
class DescriptionGeneratorTest extends TestCase {
	/**
	 * A post with a title and content to describe.
	 *
	 * @var \WP_Post
	 */
	private \WP_Post $post;

	protected function setUp(): void {
		parent::setUp();

		FakeAiProvider::register( 'A neutral summary of the post.' );

		add_filter( 'wp_seo_ai_provider', fn () => FakeAiProvider::ID );

		$this->post = static::factory()->post->create_and_get(
			[
				'post_title'   => 'Hello <em>World</em>',
				'post_content' => '<p>Some <strong>post</strong> content.</p>',
			]
		);
	}

	protected function tearDown(): void {
		// The next request for the REST server builds a new one, without the route.
		$GLOBALS['wp_rest_server'] = null;

		parent::tearDown();
	}

	/**
	 * Send a request to generate a description, with the feature booted.
	 *
	 * @param array<string, mixed> $params Request parameters.
	 * @return \WP_REST_Response
	 */
	private function request( array $params ): \WP_REST_Response {
		( new Description_Generator() )->boot();

		// Build the REST server again, so that it registers the route.
		$GLOBALS['wp_rest_server'] = null;

		$request = new \WP_REST_Request( 'POST', '/wp-seo/v1/generate-description' );
		$request->set_body_params( $params );

		return rest_do_request( $request );
	}

	/**
	 * The description is what the provider replies, and the prompt carries the
	 * post's title and rendered content as text.
	 */
	public function test_generates_a_description_from_the_post() {
		$this->assertSame( 'A neutral summary of the post.', generate_description( $this->post ) );
		$this->assertStringContainsString( 'Title: Hello World', FakeAiProvider::$prompt );
		$this->assertStringContainsString( 'Content: Some post content.', FakeAiProvider::$prompt );
		$this->assertStringContainsString( 'no more than 160 characters', FakeAiProvider::$system_instruction );
	}

	/**
	 * The instructions can be changed, and are given the post.
	 */
	public function test_instructions_are_filterable() {
		add_filter(
			'wp_seo_description_generator_prompt',
			fn ( string $instructions, \WP_Post $post ) => "Describe post {$post->ID}.",
			10,
			2
		);

		generate_description( $this->post );

		$this->assertSame( "Describe post {$this->post->ID}.", FakeAiProvider::$system_instruction );
	}

	/**
	 * The maximum length is asked for in the instructions, and a longer reply
	 * is an error that still holds the description.
	 */
	public function test_a_description_over_the_max_length_is_an_error() {
		add_filter( 'wp_seo_description_generator_max_length', fn () => 20 );

		$result = generate_description( $this->post );

		$this->assertStringContainsString( 'no more than 20 characters', FakeAiProvider::$system_instruction );
		$this->assertWPError( $result );
		$this->assertSame( 'wp_seo_description_too_long', $result->get_error_code() );
		$this->assertSame(
			[
				'status'      => 502,
				'description' => 'A neutral summary of the post.',
			],
			$result->get_error_data()
		);
	}

	/**
	 * Whitespace, tags and wrapping quotes are removed from the reply.
	 */
	public function test_the_reply_is_cleaned_up() {
		FakeAiProvider::$response = "  \"A <b>bold</b>\n summary.\"  ";

		$this->assertSame( 'A bold summary.', generate_description( $this->post ) );
	}

	/**
	 * A reply with nothing left once cleaned up is an error.
	 */
	public function test_an_empty_reply_is_an_error() {
		FakeAiProvider::$response = ' "" ';

		$result = generate_description( $this->post );

		$this->assertWPError( $result );
		$this->assertSame( 'wp_seo_description_empty', $result->get_error_code() );
	}

	/**
	 * Without a provider, no prompt is sent.
	 */
	public function test_no_provider_is_an_error() {
		add_filter( 'wp_seo_ai_provider', fn () => '', 11 );

		$result = generate_description( $this->post );

		$this->assertWPError( $result );
		$this->assertSame( 'wp_seo_ai_no_provider', $result->get_error_code() );
		$this->assertNull( FakeAiProvider::$prompt );
	}

	/**
	 * A password-protected post's content is not sent, since the description
	 * would show it to people without the password.
	 */
	public function test_a_password_protected_post_is_an_error() {
		$post = static::factory()->post->create_and_get( [ 'post_password' => 'secret' ] );

		$result = generate_description( $post );

		$this->assertWPError( $result );
		$this->assertSame( 'wp_seo_description_password_protected', $result->get_error_code() );
		$this->assertNull( FakeAiProvider::$prompt );
	}

	/**
	 * A post with no title or content has nothing to describe.
	 */
	public function test_a_post_without_title_or_content_is_an_error() {
		$post = static::factory()->post->create_and_get(
			[
				'post_title'   => '',
				'post_content' => '',
			]
		);

		$result = generate_description( $post );

		$this->assertWPError( $result );
		$this->assertSame( 'wp_seo_description_no_content', $result->get_error_code() );
		$this->assertNull( FakeAiProvider::$prompt );
	}

	/**
	 * Errors from sending the prompt are returned as core reports them.
	 */
	public function test_errors_from_core_are_returned() {
		add_filter( 'wp_ai_client_prevent_prompt', '__return_true' );

		$result = generate_description( $this->post );

		$this->assertWPError( $result );
		$this->assertSame( 'prompt_prevented', $result->get_error_code() );
	}

	/**
	 * Public post types shown in the admin are included by default, except
	 * attachments, and the list is filterable.
	 */
	public function test_post_types() {
		$post_types = get_description_generator_post_types();

		$this->assertContains( 'post', $post_types );
		$this->assertContains( 'page', $post_types );
		$this->assertNotContains( 'attachment', $post_types );

		add_filter( 'wp_seo_description_generator_post_types', fn () => [ 'page' ] );

		$this->assertSame( [ 'page' ], get_description_generator_post_types() );
	}

	/**
	 * Someone who can edit the post gets a description from the REST route.
	 */
	public function test_rest_route_returns_a_description() {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );

		$response = $this->request( [ 'post_id' => $this->post->ID ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [ 'description' => 'A neutral summary of the post.' ], $response->get_data() );
	}

	/**
	 * Someone who cannot edit the post is turned away before a prompt is sent.
	 */
	public function test_rest_route_requires_editing_the_post() {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'contributor' ] ) );

		$response = $this->request( [ 'post_id' => $this->post->ID ] );

		$this->assertSame( 403, $response->get_status() );
		$this->assertNull( FakeAiProvider::$prompt );
	}

	/**
	 * A post whose type the generator does not work on is turned away.
	 */
	public function test_rest_route_requires_a_supported_post_type() {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );
		add_filter( 'wp_seo_description_generator_post_types', fn () => [ 'page' ] );

		$response = $this->request( [ 'post_id' => $this->post->ID ] );

		$this->assertSame( 403, $response->get_status() );
		$this->assertNull( FakeAiProvider::$prompt );
	}

	/**
	 * A post that does not exist is not found.
	 */
	public function test_rest_route_requires_an_existing_post() {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );

		$response = $this->request( [ 'post_id' => PHP_INT_MAX ] );

		$this->assertSame( 404, $response->get_status() );
	}

	/**
	 * An error generating the description is returned with its status.
	 */
	public function test_rest_route_returns_errors_with_their_status() {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );
		add_filter( 'wp_seo_ai_provider', fn () => '', 11 );

		$response = $this->request( [ 'post_id' => $this->post->ID ] );

		$this->assertSame( 503, $response->get_status() );
		$this->assertSame( 'wp_seo_ai_no_provider', $response->get_data()['code'] );
	}
}
