<?php
/**
 * DescriptionGeneratorTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\AI_Settings;
use Alley\WP\WP_SEO\Features\Description_Generator;
use Alley\WP\WP_SEO\Features_Page;
use Alley\WP\WP_SEO\Tests\Doubles\FakeAiProvider;
use Alley\WP\WP_SEO\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

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
	 * Booting the feature offers the WP SEO AI settings page.
	 */
	public function test_boot_adds_the_ai_settings_page() {
		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['submenu'] = [];
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		( new Description_Generator() )->boot();

		do_action( 'admin_menu' );

		$this->assertContains( AI_Settings::SLUG, wp_list_pluck( $GLOBALS['submenu'][ Features_Page::SLUG ] ?? [], 2 ) );
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
		$this->assertSame( 'A neutral summary of the post.', Description_Generator::generate_description( $this->post ) );
		$this->assertStringContainsString( 'Title: Hello World', FakeAiProvider::$prompt );
		$this->assertStringContainsString( 'Content: Some post content.', FakeAiProvider::$prompt );
		$this->assertStringContainsString( 'no more than 160 characters', FakeAiProvider::$system_instruction );
	}

	/**
	 * A private post's title is sent as stored, without the "Private: " that
	 * is prepended to it for display.
	 */
	public function test_a_private_post_title_is_sent_without_a_prefix() {
		$post = static::factory()->post->create_and_get(
			[
				'post_title'  => 'Hello World',
				'post_status' => 'private',
			]
		);

		Description_Generator::generate_description( $post );

		$this->assertStringContainsString( "Title: Hello World\n", FakeAiProvider::$prompt );
		$this->assertStringNotContainsString( 'Private', FakeAiProvider::$prompt );
	}

	/**
	 * Characters that are encoded as HTML entities for display, such as the
	 * curly quotes the_content adds, are sent as characters.
	 */
	public function test_html_entities_are_sent_as_characters() {
		$post = static::factory()->post->create_and_get(
			[
				'post_title'   => 'Salt &amp; Pepper',
				'post_content' => '<p>It\'s "quoted" &amp; &lt;escaped&gt;.</p>',
			]
		);

		Description_Generator::generate_description( $post );

		$this->assertStringContainsString( 'Title: Salt & Pepper', FakeAiProvider::$prompt );
		$this->assertStringContainsString( 'Content: It’s “quoted” & <escaped>.', FakeAiProvider::$prompt );
	}

	/**
	 * The rendered content can be changed before it is sent, for example to
	 * remove what another plugin adds to every post, and the filter is given
	 * the post.
	 */
	public function test_content_is_filterable() {
		// A plugin adding share buttons to every post.
		add_filter( 'the_content', fn ( string $content ) => $content . '<div class="share">Share this: Facebook</div>' );

		add_filter(
			'wp_seo_description_generator_content',
			fn ( string $content, \WP_Post $post ) => $post->ID === $this->post->ID
				? (string) preg_replace( '#<div class="share">.*?</div>#s', '', $content )
				: $content,
			10,
			2
		);

		Description_Generator::generate_description( $this->post );

		$this->assertStringContainsString( 'Content: Some post content.', FakeAiProvider::$prompt );
		$this->assertStringNotContainsString( 'Share this', FakeAiProvider::$prompt );
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

		Description_Generator::generate_description( $this->post );

		$this->assertSame( "Describe post {$this->post->ID}.", FakeAiProvider::$system_instruction );
	}

	/**
	 * The maximum length is asked for in the instructions, is given the post,
	 * and a longer reply is still returned.
	 */
	public function test_a_description_over_the_max_length_is_returned() {
		add_filter(
			'wp_seo_description_generator_max_length',
			fn ( int $max_length, \WP_Post $post ) => $post->ID === $this->post->ID ? 20 : $max_length,
			10,
			2
		);

		$this->assertSame( 20, Description_Generator::get_max_length( $this->post ) );
		$this->assertSame( 'A neutral summary of the post.', Description_Generator::generate_description( $this->post ) );
		$this->assertStringContainsString( 'no more than 20 characters', FakeAiProvider::$system_instruction );
	}

	/**
	 * Whitespace, tags and wrapping quotes are removed from the reply.
	 */
	public function test_the_reply_is_cleaned_up() {
		FakeAiProvider::$response = "  \"A <b>bold</b>\n summary.\"  ";

		$this->assertSame( 'A bold summary.', Description_Generator::generate_description( $this->post ) );
	}

	/**
	 * HTML entities in the reply are returned as characters.
	 */
	public function test_html_entities_in_the_reply_are_decoded() {
		FakeAiProvider::$response = '&quot;Salt &amp; pepper&#8217;s summary.&quot;';

		$this->assertSame( 'Salt & pepper’s summary.', Description_Generator::generate_description( $this->post ) );
	}

	/**
	 * Quotes are removed only when they wrap the whole reply.
	 *
	 * @param string $reply    Reply from the provider.
	 * @param string $expected Description returned.
	 */
	#[DataProvider( 'quoted_replies' )]
	public function test_wrapping_quotes_are_removed( string $reply, string $expected ) {
		FakeAiProvider::$response = $reply;

		$this->assertSame( $expected, Description_Generator::generate_description( $this->post ) );
	}

	/**
	 * Replies with quotes, and the descriptions returned for them.
	 *
	 * @return array<string, array{string, string}>
	 */
	public static function quoted_replies(): array {
		return [
			'straight double quotes' => [ '"A summary."', 'A summary.' ],
			'curly double quotes'    => [ '“A summary.”', 'A summary.' ],
			'curly single quotes'    => [ '‘It’s a summary.’', 'It’s a summary.' ],
			'quoted phrases'         => [ '"A" vs "B" compared.', '"A" vs "B" compared.' ],
			'quoted phrases at ends' => [ '"A" vs "B"', '"A" vs "B"' ],
			'curly quoted phrases'   => [ '“A” vs “B”', '“A” vs “B”' ],
			'apostrophe inside'      => [ "'It's a summary.'", "'It's a summary.'" ],
		];
	}

	/**
	 * A reply with nothing left once cleaned up is an error.
	 */
	public function test_an_empty_reply_is_an_error() {
		FakeAiProvider::$response = ' "" ';

		$result = Description_Generator::generate_description( $this->post );

		$this->assertWPError( $result );
		$this->assertSame( 'wp_seo_description_empty', $result->get_error_code() );
	}

	/**
	 * Without a provider, no prompt is sent.
	 */
	public function test_no_provider_is_an_error() {
		add_filter( 'wp_seo_ai_provider', fn () => '', 11 );

		$result = Description_Generator::generate_description( $this->post );

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

		$result = Description_Generator::generate_description( $post );

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

		$result = Description_Generator::generate_description( $post );

		$this->assertWPError( $result );
		$this->assertSame( 'wp_seo_description_no_content', $result->get_error_code() );
		$this->assertNull( FakeAiProvider::$prompt );
	}

	/**
	 * Errors from sending the prompt are returned as core reports them.
	 */
	public function test_errors_from_core_are_returned() {
		add_filter( 'wp_ai_client_prevent_prompt', '__return_true' );

		$result = Description_Generator::generate_description( $this->post );

		$this->assertWPError( $result );
		$this->assertSame( 'prompt_prevented', $result->get_error_code() );
	}

	/**
	 * Public post types shown in the admin are included by default, except
	 * attachments, and the list is filterable.
	 */
	public function test_post_types() {
		$post_types = Description_Generator::get_post_types();

		$this->assertContains( 'post', $post_types );
		$this->assertContains( 'page', $post_types );
		$this->assertNotContains( 'attachment', $post_types );

		add_filter( 'wp_seo_description_generator_post_types', fn () => [ 'page' ] );

		$this->assertSame( [ 'page' ], Description_Generator::get_post_types() );
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

	/**
	 * A description over the maximum length is returned, since the editor
	 * reviews it before saving.
	 */
	public function test_rest_route_returns_a_description_over_the_max_length() {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );
		add_filter( 'wp_seo_description_generator_max_length', fn () => 20 );

		$response = $this->request( [ 'post_id' => $this->post->ID ] );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( [ 'description' => 'A neutral summary of the post.' ], $response->get_data() );
	}

	/**
	 * Requests under the rate limit get a description, and the limit is
	 * filterable for each user.
	 */
	public function test_rest_route_allows_requests_under_the_rate_limit() {
		$user_id = static::factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		add_filter(
			'wp_seo_description_generator_rate_limit',
			fn ( int $limit, int $id ) => $id === $user_id ? 3 : $limit,
			10,
			2
		);

		for ( $i = 0; $i < 3; $i++ ) {
			$this->assertSame( 200, $this->request( [ 'post_id' => $this->post->ID ] )->get_status() );
		}

		$this->assertSame( 429, $this->request( [ 'post_id' => $this->post->ID ] )->get_status() );
	}

	/**
	 * A request over the default limit of 30 an hour is refused before a
	 * prompt is sent, and says when to try again.
	 */
	public function test_rest_route_refuses_requests_over_the_rate_limit() {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );

		for ( $i = 0; $i < 30; $i++ ) {
			$this->assertSame( 200, $this->request( [ 'post_id' => $this->post->ID ] )->get_status() );
		}

		FakeAiProvider::$prompt = null;

		$response = $this->request( [ 'post_id' => $this->post->ID ] );
		$headers  = $response->get_headers();
		$data     = $response->get_data();

		$this->assertSame( 429, $response->get_status() );
		$this->assertSame( 'wp_seo_description_rate_limited', $data['code'] );
		$this->assertArrayHasKey( 'Retry-After', $headers );
		$this->assertGreaterThan( 0, (int) $headers['Retry-After'] );
		$this->assertLessThanOrEqual( HOUR_IN_SECONDS, (int) $headers['Retry-After'] );
		$this->assertSame( (int) $headers['Retry-After'], $data['data']['retry_after'] );
		$this->assertNull( FakeAiProvider::$prompt );
	}

	/**
	 * A request that fails to generate a description still counts toward the
	 * limit.
	 */
	public function test_rest_route_counts_failed_requests() {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );
		add_filter( 'wp_seo_description_generator_rate_limit', fn () => 1 );
		add_filter( 'wp_seo_ai_provider', fn () => '', 11 );

		$this->assertSame( 503, $this->request( [ 'post_id' => $this->post->ID ] )->get_status() );

		remove_all_filters( 'wp_seo_ai_provider', 11 );

		$this->assertSame( 429, $this->request( [ 'post_id' => $this->post->ID ] )->get_status() );
	}

	/**
	 * Requests are allowed again once the window resets.
	 */
	public function test_rest_route_rate_limit_resets() {
		$user_id = static::factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );
		add_filter( 'wp_seo_description_generator_rate_limit', fn () => 1 );

		$this->assertSame( 200, $this->request( [ 'post_id' => $this->post->ID ] )->get_status() );
		$this->assertSame( 429, $this->request( [ 'post_id' => $this->post->ID ] )->get_status() );

		// Move the end of the window into the past.
		$key    = 'wp_seo_description_rate_limit_' . $user_id;
		$stored = get_transient( $key );
		$this->assertIsArray( $stored );
		set_transient( $key, [ 'count' => $stored['count'], 'reset' => time() - 1 ], HOUR_IN_SECONDS );

		$this->assertSame( 200, $this->request( [ 'post_id' => $this->post->ID ] )->get_status() );
		$this->assertSame( 429, $this->request( [ 'post_id' => $this->post->ID ] )->get_status() );
	}

	/**
	 * Each user's requests are counted separately.
	 */
	public function test_rest_route_rate_limit_is_per_user() {
		$first  = static::factory()->user->create( [ 'role' => 'editor' ] );
		$second = static::factory()->user->create( [ 'role' => 'editor' ] );
		add_filter( 'wp_seo_description_generator_rate_limit', fn () => 1 );

		wp_set_current_user( $first );
		$this->assertSame( 200, $this->request( [ 'post_id' => $this->post->ID ] )->get_status() );

		wp_set_current_user( $second );
		$this->assertSame( 200, $this->request( [ 'post_id' => $this->post->ID ] )->get_status() );

		wp_set_current_user( $first );
		$this->assertSame( 429, $this->request( [ 'post_id' => $this->post->ID ] )->get_status() );
	}

	/**
	 * A limit of 0 or less turns the rate limit off.
	 *
	 * @param int $limit Requests allowed per hour.
	 */
	#[DataProvider( 'unlimited_rate_limits' )]
	public function test_rest_route_rate_limit_can_be_turned_off( int $limit ) {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );
		add_filter( 'wp_seo_description_generator_rate_limit', fn () => $limit );

		for ( $i = 0; $i < 3; $i++ ) {
			$this->assertSame( 200, $this->request( [ 'post_id' => $this->post->ID ] )->get_status() );
		}
	}

	/**
	 * Limits that turn the rate limit off.
	 *
	 * @return array<string, array{int}>
	 */
	public static function unlimited_rate_limits(): array {
		return [
			'zero'     => [ 0 ],
			'negative' => [ -1 ],
		];
	}

	/**
	 * Calling `generate_description()` directly, as bulk runs do, is not
	 * limited and does not count toward the REST route's limit.
	 */
	public function test_generate_description_is_not_rate_limited() {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );
		add_filter( 'wp_seo_description_generator_rate_limit', fn () => 1 );

		for ( $i = 0; $i < 3; $i++ ) {
			$this->assertSame( 'A neutral summary of the post.', Description_Generator::generate_description( $this->post ) );
		}

		$this->assertSame( 200, $this->request( [ 'post_id' => $this->post->ID ] )->get_status() );
	}
}
