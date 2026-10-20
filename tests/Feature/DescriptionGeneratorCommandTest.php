<?php
/**
 * DescriptionGeneratorCommandTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Description_Generator_Command;
use Alley\WP\WP_SEO\Tests\Doubles\FakeAiProvider;
use Alley\WP\WP_SEO\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use WordPress\AiClient\Providers\Http\Exception\ClientException;
use WP_CLI;
use WP_CLI\ExitException;
use WP_CLI\Loggers\Execution;

/**
 * Tests for the `wp wp-seo generate-descriptions` command.
 *
 * The command is run directly, with WP-CLI's output captured and its exits
 * thrown as exceptions. Prompts go to a fake provider, which replies with
 * text a test sets and makes no request.
 */
class DescriptionGeneratorCommandTest extends TestCase {
	/**
	 * What the provider replies.
	 *
	 * @var string
	 */
	private const DESCRIPTION = 'A neutral summary of the post.';

	protected function setUp(): void {
		parent::setUp();

		FakeAiProvider::register( self::DESCRIPTION );

		add_filter( 'wp_seo_ai_provider', fn () => FakeAiProvider::ID );

		( new \ReflectionProperty( WP_CLI::class, 'capture_exit' ) )->setValue( null, true );

		/*
		 * The command works on every post in the database, and the data
		 * providers of other test classes create posts before any test runs,
		 * outside the transaction each test is rolled back in. They are deleted
		 * within this test's transaction, so that only its own posts are there.
		 */
		foreach ( get_posts( [ 'post_type' => 'any', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ] ) as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	protected function tearDown(): void {
		( new \ReflectionProperty( WP_CLI::class, 'capture_exit' ) )->setValue( null, false );
		WP_CLI::set_logger( null );

		parent::tearDown();
	}

	/**
	 * Run the command.
	 *
	 * @param array<string, mixed> $assoc_args Flags.
	 * @return array{stdout: string, stderr: string, exit: int} What the command
	 *                                                          wrote, and its exit code.
	 */
	private function run_command( array $assoc_args = [] ): array {
		$logger = new Execution();
		WP_CLI::set_logger( $logger );

		$exit = 0;

		try {
			( new Description_Generator_Command() )( [], $assoc_args );
		} catch ( ExitException $e ) {
			$exit = $e->getCode();
		}

		return [
			'stdout' => $logger->stdout,
			'stderr' => $logger->stderr,
			'exit'   => $exit,
		];
	}

	/**
	 * Get a post's saved description.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private function description( int $post_id ): string {
		return (string) get_post_meta( $post_id, 'search_engine_description', true );
	}

	/**
	 * Posts without a description, or with an empty one, get the one the
	 * provider writes, and posts with one are left alone.
	 */
	public function test_generates_descriptions_for_posts_without_one() {
		$missing  = static::factory()->post->create();
		$empty    = static::factory()->post->create( [ 'meta_input' => [ 'search_engine_description' => '' ] ] );
		$existing = static::factory()->post->create( [ 'meta_input' => [ 'search_engine_description' => 'Written by an editor.' ] ] );
		$page     = static::factory()->post->create( [ 'post_type' => 'page' ] );

		$result = $this->run_command();

		$this->assertSame( 0, $result['exit'] );
		$this->assertSame( self::DESCRIPTION, $this->description( $missing ) );
		$this->assertSame( self::DESCRIPTION, $this->description( $empty ) );
		$this->assertSame( 'Written by an editor.', $this->description( $existing ) );
		$this->assertSame( self::DESCRIPTION, $this->description( $page ) );
		$this->assertStringContainsString( "Post {$missing}: " . self::DESCRIPTION, $result['stdout'] );
		$this->assertStringContainsString( 'Generated 3 descriptions. 0 posts failed.', $result['stdout'] );
		$this->assertSame( '', $result['stderr'] );
	}

	/**
	 * Markup in the description, which the provider can return escaped, is
	 * removed before it is saved.
	 */
	public function test_descriptions_are_saved_as_plain_text() {
		FakeAiProvider::$response = '&lt;img src=x onerror=alert(1)&gt;A &lt;b&gt;bold&lt;/b&gt; summary.';

		$post = static::factory()->post->create();

		$result = $this->run_command();

		$this->assertSame( 'A bold summary.', $this->description( $post ) );
		$this->assertStringContainsString( "Post {$post}: A bold summary.", $result['stdout'] );
	}

	/**
	 * A description an editor saves while one is being generated is kept,
	 * though the post was queried, and its meta cached, before it was saved.
	 */
	public function test_a_description_saved_during_generation_is_kept() {
		$post = static::factory()->post->create();

		add_filter(
			'wp_seo_description_generator_content',
			function ( string $content ) use ( $post ) {
				global $wpdb;

				// An editor saving in another request, which this request's cache doesn't know about.
				$wpdb->insert(
					$wpdb->postmeta,
					[
						'post_id'    => $post,
						'meta_key'   => 'search_engine_description', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_value' => 'Written by an editor.', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					]
				);

				return $content;
			}
		);

		$result = $this->run_command();

		$this->assertSame( 'Written by an editor.', $this->description( $post ) );
		$this->assertStringContainsString( "Post {$post}: Kept the description saved while this one was being generated.", $result['stdout'] );
		$this->assertStringContainsString( 'Generated 0 descriptions. 0 posts failed.', $result['stdout'] );
	}

	/**
	 * Other queries that run during the command, such as ones a post's content
	 * makes, still find posts that have a description.
	 */
	public function test_other_queries_are_not_limited_to_posts_without_a_description() {
		$described = static::factory()->post->create( [ 'meta_input' => [ 'search_engine_description' => 'Written by an editor.' ] ] );
		static::factory()->post->create();

		$found = [];

		add_filter(
			'wp_seo_description_generator_content',
			function ( string $content ) use ( $described, &$found ) {
				$found = wp_list_pluck( ( new \WP_Query( [ 'post__in' => [ $described ] ] ) )->posts, 'ID' );

				return $content;
			}
		);

		$this->run_command();

		$this->assertSame( [ $described ], $found );
	}

	/**
	 * By default, drafts, password-protected posts, and post types the filter
	 * leaves out are not described.
	 */
	public function test_default_query() {
		add_filter( 'wp_seo_description_generator_post_types', fn () => [ 'post' ] );

		$draft    = static::factory()->post->create( [ 'post_status' => 'draft' ] );
		$password = static::factory()->post->create( [ 'post_password' => 'secret' ] );
		$page     = static::factory()->post->create( [ 'post_type' => 'page' ] );

		$result = $this->run_command();

		$this->assertSame( 0, $result['exit'] );
		$this->assertNull( FakeAiProvider::$prompt );
		$this->assertSame( '', $this->description( $draft ) );
		$this->assertSame( '', $this->description( $password ) );
		$this->assertSame( '', $this->description( $page ) );
		$this->assertStringContainsString( 'Generated 0 descriptions.', $result['stdout'] );
	}

	/**
	 * Arguments passed in --query replace the defaults.
	 */
	public function test_query_replaces_the_defaults() {
		$draft     = static::factory()->post->create( [ 'post_status' => 'draft' ] );
		$published = static::factory()->post->create();

		$this->run_command( [ 'query' => '{"post_status":"draft"}' ] );

		$this->assertSame( self::DESCRIPTION, $this->description( $draft ) );
		$this->assertSame( '', $this->description( $published ) );
	}

	/**
	 * A meta query passed in --query narrows the posts, and still leaves out
	 * posts that have a description.
	 */
	public function test_query_meta_query_is_combined_with_the_missing_description() {
		$featured  = static::factory()->post->create( [ 'meta_input' => [ 'featured' => '1' ] ] );
		$described = static::factory()->post->create(
			[
				'meta_input' => [
					'featured'                  => '1',
					'search_engine_description' => 'Written by an editor.',
				],
			]
		);
		$other     = static::factory()->post->create();

		$this->run_command( [ 'query' => '{"meta_query":[{"key":"featured","value":"1"}]}' ] );

		$this->assertSame( self::DESCRIPTION, $this->description( $featured ) );
		$this->assertSame( 'Written by an editor.', $this->description( $described ) );
		$this->assertSame( '', $this->description( $other ) );
	}

	/**
	 * A --query that isn't a JSON object is an error.
	 *
	 * @param string $query Value of --query.
	 */
	#[DataProvider( 'data_invalid_queries' )]
	public function test_invalid_query_is_an_error( string $query ) {
		$post = static::factory()->post->create();

		$result = $this->run_command( [ 'query' => $query ] );

		$this->assertSame( 1, $result['exit'] );
		$this->assertStringContainsString( 'must be a JSON object', $result['stderr'] );
		$this->assertSame( '', $this->description( $post ) );
	}

	/**
	 * Values of --query that aren't a JSON object.
	 *
	 * @return array<string, array{string}>
	 */
	public static function data_invalid_queries(): array {
		return [
			'not JSON' => [ 'post_type=page' ],
			'list'     => [ '["page"]' ],
			'string'   => [ '"page"' ],
		];
	}

	/**
	 * A run stops at the limit, and the next run continues after the last post
	 * it reached.
	 */
	public function test_limit() {
		[ $first, $second, $third ] = static::factory()->post->create_many( 3 );

		$result = $this->run_command( [ 'limit' => '2' ] );

		$this->assertSame( 0, $result['exit'] );
		$this->assertSame( self::DESCRIPTION, $this->description( $first ) );
		$this->assertSame( self::DESCRIPTION, $this->description( $second ) );
		$this->assertSame( '', $this->description( $third ) );
		$this->assertStringContainsString( 'Stopped at the limit of 2 posts.', $result['stdout'] );

		$this->run_command( [ 'limit' => '2' ] );

		$this->assertSame( self::DESCRIPTION, $this->description( $third ) );
	}

	/**
	 * The default limit is filterable, and the flag takes its place.
	 */
	public function test_limit_is_filterable() {
		add_filter( 'wp_seo_description_generator_cli_limit', fn () => 1 );

		[ $first, $second, $third ] = static::factory()->post->create_many( 3 );

		$this->run_command();

		$this->assertSame( self::DESCRIPTION, $this->description( $first ) );
		$this->assertSame( '', $this->description( $second ) );

		$this->run_command( [ 'limit' => '2' ] );

		$this->assertSame( self::DESCRIPTION, $this->description( $second ) );
		$this->assertSame( self::DESCRIPTION, $this->description( $third ) );
	}

	/**
	 * A limit that isn't a whole number of at least 1 is an error.
	 *
	 * @param string $limit Value of --limit.
	 */
	#[DataProvider( 'data_invalid_limits' )]
	public function test_invalid_limit_is_an_error( string $limit ) {
		$result = $this->run_command( [ 'limit' => $limit ] );

		$this->assertSame( 1, $result['exit'] );
		$this->assertStringContainsString( 'The limit must be a whole number of at least 1.', $result['stderr'] );
		$this->assertNull( FakeAiProvider::$prompt );
	}

	/**
	 * Values of --limit that aren't allowed.
	 *
	 * @return array<string, array{string}>
	 */
	public static function data_invalid_limits(): array {
		return [
			'zero'     => [ '0' ],
			'negative' => [ '-1' ],
			'decimal'  => [ '1.5' ],
			'all'      => [ 'all' ],
		];
	}

	/**
	 * A post whose description fails is logged and not saved, the run goes
	 * on, and the next run passes over it until the command is rewound.
	 */
	public function test_a_failed_post_is_skipped_until_rewound() {
		[ $failing, $passing ] = static::factory()->post->create_many( 2 );

		$max_length = fn ( int $max_length, \WP_Post $post ) => $post->ID === $failing ? 5 : $max_length;
		add_filter( 'wp_seo_description_generator_max_length', $max_length, 10, 2 );

		$result = $this->run_command();

		$this->assertSame( 0, $result['exit'] );
		$this->assertSame( '', $this->description( $failing ) );
		$this->assertSame( self::DESCRIPTION, $this->description( $passing ) );
		$this->assertStringContainsString( "Post {$failing}: The AI provider returned a description longer than 5 characters. (wp_seo_description_too_long)", $result['stderr'] );
		$this->assertStringContainsString( 'Generated 1 description. 1 post failed.', $result['stdout'] );

		remove_filter( 'wp_seo_description_generator_max_length', $max_length );
		FakeAiProvider::$prompt = null;

		$this->run_command();

		$this->assertNull( FakeAiProvider::$prompt );
		$this->assertSame( '', $this->description( $failing ) );

		$result = $this->run_command( [ 'rewind' => true ] );

		$this->assertSame( 0, $result['exit'] );
		$this->assertStringContainsString( 'Rewound.', $result['stdout'] );
		$this->assertNull( FakeAiProvider::$prompt );

		$this->run_command();

		$this->assertSame( self::DESCRIPTION, $this->description( $failing ) );
	}

	/**
	 * A run stops once more posts fail than the fault tolerance allows of the
	 * limit, and the next run continues after the posts that failed.
	 */
	public function test_too_many_failures_stop_the_run() {
		[ $first, $second, $third ] = static::factory()->post->create_many( 3 );

		// 10% of 10 posts allows one to fail.
		add_filter( 'wp_seo_description_generator_max_length', fn ( int $max_length, \WP_Post $post ) => $post->ID === $third ? $max_length : 5, 10, 2 );

		$result = $this->run_command(
			[
				'limit'           => '10',
				'fault-tolerance' => '10',
			]
		);

		$this->assertSame( 1, $result['exit'] );
		$this->assertStringContainsString( "Post {$first}:", $result['stderr'] );
		$this->assertStringContainsString( "Post {$second}:", $result['stderr'] );
		$this->assertStringContainsString( 'More than 1 post failed, so the run stopped. Generated 0 descriptions. 2 posts failed.', $result['stderr'] );
		$this->assertSame( '', $this->description( $third ) );

		$result = $this->run_command();

		$this->assertSame( 0, $result['exit'] );
		$this->assertSame( self::DESCRIPTION, $this->description( $third ) );
		$this->assertStringContainsString( 'Generated 1 description. 0 posts failed.', $result['stdout'] );
	}

	/**
	 * A fault tolerance of 0 stops the run at the first failure, and the
	 * default is filterable.
	 */
	public function test_fault_tolerance_is_filterable() {
		add_filter( 'wp_seo_description_generator_cli_fault_tolerance', fn () => 0 );
		add_filter( 'wp_seo_description_generator_max_length', fn () => 5 );

		static::factory()->post->create_many( 2 );

		$result = $this->run_command();

		$this->assertSame( 1, $result['exit'] );
		$this->assertStringContainsString( 'More than 0 posts failed', $result['stderr'] );
		$this->assertStringContainsString( '1 post failed.', $result['stderr'] );
	}

	/**
	 * A fault tolerance that isn't a percentage is an error.
	 *
	 * @param string $fault_tolerance Value of --fault-tolerance.
	 */
	#[DataProvider( 'data_invalid_fault_tolerances' )]
	public function test_invalid_fault_tolerance_is_an_error( string $fault_tolerance ) {
		$result = $this->run_command( [ 'fault-tolerance' => $fault_tolerance ] );

		$this->assertSame( 1, $result['exit'] );
		$this->assertStringContainsString( 'The fault tolerance must be a percentage from 0 to 100.', $result['stderr'] );
	}

	/**
	 * Values of --fault-tolerance that aren't allowed.
	 *
	 * @return array<string, array{string}>
	 */
	public static function data_invalid_fault_tolerances(): array {
		return [
			'negative'      => [ '-1' ],
			'over 100'      => [ '101' ],
			'not a number'  => [ 'five' ],
			'with % sign'   => [ '5%' ],
		];
	}

	/**
	 * A rate limit or exhausted quota stops the run at once, and the post it
	 * happened on is tried again next run.
	 */
	public function test_a_rate_limit_stops_the_run() {
		[ $first, $second ] = static::factory()->post->create_many( 2 );

		FakeAiProvider::$exception = new ClientException( 'Too Many Requests (429)', 429 );

		$result = $this->run_command();

		$this->assertSame( 1, $result['exit'] );
		$this->assertStringContainsString( "Post {$first}: Too Many Requests (429) (prompt_client_error)", $result['stderr'] );
		$this->assertStringContainsString( 'The AI provider is limiting requests or is out of quota, so the run stopped.', $result['stderr'] );
		$this->assertStringNotContainsString( "Post {$second}:", $result['stderr'] );

		FakeAiProvider::$exception = null;

		$this->run_command();

		$this->assertSame( self::DESCRIPTION, $this->description( $first ) );
		$this->assertSame( self::DESCRIPTION, $this->description( $second ) );
	}

	/**
	 * A dry run lists the posts a run would describe, up to the limit, without
	 * sending prompts, saving descriptions, or moving the saved place.
	 */
	public function test_dry_run() {
		[ $first, $second ] = static::factory()->post->create_many( 2, [ 'post_title' => 'Hello World' ] );

		$result = $this->run_command(
			[
				'dry-run' => true,
				'limit'   => '1',
			]
		);

		$this->assertSame( 0, $result['exit'] );
		$this->assertNull( FakeAiProvider::$prompt );
		$this->assertSame( '', $this->description( $first ) );
		$this->assertStringContainsString( "Post {$first}: Hello World", $result['stdout'] );
		$this->assertStringNotContainsString( "Post {$second}:", $result['stdout'] );
		$this->assertStringContainsString( '1 post would get a description.', $result['stdout'] );

		$this->run_command();

		$this->assertSame( self::DESCRIPTION, $this->description( $first ) );
		$this->assertSame( self::DESCRIPTION, $this->description( $second ) );
	}

	/**
	 * Each --query keeps its own place, so running one does not skip the
	 * posts of another.
	 */
	public function test_each_query_keeps_its_own_place() {
		$post = static::factory()->post->create();
		$page = static::factory()->post->create( [ 'post_type' => 'page' ] );

		$this->run_command( [ 'query' => '{"post_type":"page"}' ] );

		$this->assertSame( self::DESCRIPTION, $this->description( $page ) );
		$this->assertSame( '', $this->description( $post ) );

		$this->run_command();

		$this->assertSame( self::DESCRIPTION, $this->description( $post ) );
	}

	/**
	 * Without a provider, the command stops before describing anything.
	 */
	public function test_no_provider_is_an_error() {
		add_filter( 'wp_seo_ai_provider', fn () => '', 11 );

		$post = static::factory()->post->create();

		$result = $this->run_command();

		$this->assertSame( 1, $result['exit'] );
		$this->assertStringContainsString( 'No AI provider is selected for the Meta Description Generator.', $result['stderr'] );
		$this->assertSame( '', $this->description( $post ) );
	}

	/**
	 * When the filter leaves no post types, the command stops rather than
	 * describing posts, which WP_Query would query for no post types.
	 */
	public function test_no_post_types_is_an_error() {
		add_filter( 'wp_seo_description_generator_post_types', fn () => [] );

		$post = static::factory()->post->create();

		$result = $this->run_command();

		$this->assertSame( 1, $result['exit'] );
		$this->assertStringContainsString( 'There are no post types to generate descriptions for.', $result['stderr'] );
		$this->assertSame( '', $this->description( $post ) );
	}
}
