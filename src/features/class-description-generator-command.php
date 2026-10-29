<?php
/**
 * Description_Generator_Command class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Features;

use Alley\WP_Bulk_Task\Bulk_Task;
use Alley\WP_Bulk_Task\Cursor\Memory_Cursor;
use Alley\WP_Bulk_Task\Cursor\Option_Cursor;
use Alley\WP\WP_SEO\AI_Providers;
use WP_CLI;

/**
 * The `wp wp-seo generate-descriptions` command, which generates and saves
 * meta descriptions in bulk with the Meta Description Generator.
 *
 * The Meta Description Generator feature registers it while active, along with
 * the query filter it uses to find posts without a description.
 */
final class Description_Generator_Command {
	/**
	 * Number of posts a run has worked on, counting failures.
	 *
	 * @var int
	 */
	private int $attempted = 0;

	/**
	 * Number of descriptions a run has saved.
	 *
	 * @var int
	 */
	private int $generated = 0;

	/**
	 * Number of posts a run has failed to describe.
	 *
	 * @var int
	 */
	private int $failed = 0;

	/**
	 * Generate meta descriptions for posts that don't have one.
	 *
	 * Sends each post without a description to the AI provider chosen on the
	 * WP SEO AI settings page, and saves the description it writes. A post
	 * whose description can't be generated, or comes back too long, is logged
	 * and left alone.
	 *
	 * Posts are worked through in order of ID. The place reached is saved after
	 * each post, so the next run with the same --query continues where this one
	 * stopped, and posts that failed are passed over until --rewind.
	 *
	 * The run stops early if the AI provider is limiting requests or is out of
	 * quota, or if more posts fail than the fault tolerance allows.
	 *
	 * ## OPTIONS
	 *
	 * [--query=<json>]
	 * : WP_Query arguments for the posts to describe, as a JSON object. They
	 * override the matching defaults: published posts without a password, of
	 * the post types the Meta Description Generator works on. Posts that
	 * already have a description are always left out, and posts are always
	 * ordered by ID.
	 *
	 * [--limit=<number>]
	 * : Maximum number of posts to send to the AI provider in this run.
	 * Default 100.
	 *
	 * [--fault-tolerance=<percent>]
	 * : Percentage of the limit that may fail before the run stops. Default 5.
	 *
	 * [--dry-run]
	 * : List the posts a run would describe, without sending prompts or saving.
	 *
	 * [--rewind]
	 * : Start from the first post the next time the command runs with this
	 * --query, so that posts that failed are tried again.
	 *
	 * ## EXAMPLES
	 *
	 *     # Describe up to 100 posts.
	 *     $ wp wp-seo generate-descriptions
	 *
	 *     # Describe up to 10 pages by one author.
	 *     $ wp wp-seo generate-descriptions --query='{"post_type":"page","author":3}' --limit=10
	 *
	 *     # Try the posts that failed again.
	 *     $ wp wp-seo generate-descriptions --rewind
	 *     $ wp wp-seo generate-descriptions
	 *
	 * @throws \RuntimeException If the bulk task throws one.
	 *
	 * @param string[]             $args       Positional arguments. Unused.
	 * @param array<string, mixed> $assoc_args Flags.
	 */
	public function __invoke( array $args, array $assoc_args ): void {
		$query = json_decode( is_string( $assoc_args['query'] ?? null ) ? $assoc_args['query'] : '{}', true );

		if ( ! is_array( $query ) || ( [] !== $query && array_is_list( $query ) ) ) {
			WP_CLI::error( __( 'The --query value must be a JSON object of WP_Query arguments.', 'wp-seo' ) );
		}

		/**
		 * Filters the number of posts `wp wp-seo generate-descriptions` sends to
		 * the AI provider in a run, when the --limit flag is not given.
		 *
		 * @since 2.0.0
		 *
		 * @param int $limit Most posts to send. Default 100.
		 */
		$limit = filter_var(
			$assoc_args['limit'] ?? apply_filters( 'wp_seo_description_generator_cli_limit', 100 ),
			FILTER_VALIDATE_INT,
			[ 'options' => [ 'min_range' => 1 ] ]
		);

		if ( false === $limit ) {
			WP_CLI::error( __( 'The limit must be a whole number of at least 1.', 'wp-seo' ) );
		}

		/**
		 * Filters the percentage of the limit that may fail before
		 * `wp wp-seo generate-descriptions` stops, when the --fault-tolerance
		 * flag is not given.
		 *
		 * @since 2.0.0
		 *
		 * @param int|float $fault_tolerance Percentage, from 0 to 100. Default 5.
		 */
		$fault_tolerance = filter_var(
			$assoc_args['fault-tolerance'] ?? apply_filters( 'wp_seo_description_generator_cli_fault_tolerance', 5 ),
			FILTER_VALIDATE_FLOAT,
			[
				'options' => [
					'min_range' => 0,
					'max_range' => 100,
				],
			]
		);

		if ( false === $fault_tolerance ) {
			WP_CLI::error( __( 'The fault tolerance must be a percentage from 0 to 100.', 'wp-seo' ) );
		}

		// Each --query keeps its own place, so that running one doesn't skip the posts of another.
		$key    = 'wp_seo_generate_descriptions_' . md5( (string) wp_json_encode( $query ) );
		$cursor = new Option_Cursor( $key );

		if ( ! empty( $assoc_args['rewind'] ) ) {
			$cursor->reset();
			WP_CLI::success( __( 'Rewound. Run the command again without --rewind to start from the first post.', 'wp-seo' ) );
			return;
		}

		$check = AI_Providers::check_provider();

		if ( is_wp_error( $check ) ) {
			WP_CLI::error( $check );
		}

		$query_args = array_merge(
			[
				'post_type'      => Description_Generator::get_post_types(),
				'post_status'    => 'publish',
				'has_password'   => false,
				'posts_per_page' => min( $limit, 100 ),
			],
			$query,
			[
				// Bulk_Task pages through posts by the ID of the last post object.
				'fields' => 'all',
				Description_Generator::MISSING_DESCRIPTION_QUERY_VAR => true,
			]
		);

		// An empty `post_type` makes WP_Query fall back to `post`, which the filter may have removed.
		if ( [] === $query_args['post_type'] ) {
			WP_CLI::error( __( 'There are no post types to generate descriptions for.', 'wp-seo' ) );
		}

		$dry_run = ! empty( $assoc_args['dry-run'] );

		if ( $dry_run ) {
			// A dry run starts where a real run would, without moving the saved place.
			$saved  = $cursor->get();
			$cursor = new Memory_Cursor();
			$cursor->set( $saved );
		}

		// Rounded down, so with the default 5%, a limit under 20 allows no failures and the first one stops the run.
		$allowed_failures = (int) floor( $limit * $fault_tolerance / 100 );

		/*
		 * Bulk_Task has no way to stop partway, so reaching the limit throws
		 * this, which is caught below. Skipping its cleanup leaves term counting
		 * deferred, which matters only if the process goes on to change terms.
		 */
		$limit_reached = new \RuntimeException();

		try {
			( new Bulk_Task( $key, null, $cursor ) )->run(
				$query_args,
				function ( \WP_Post $post ) use ( $cursor, $dry_run, $limit, $allowed_failures, $limit_reached ): void {
					++$this->attempted;

					if ( $dry_run ) {
						/* translators: 1: post ID, 2: post title. */
						WP_CLI::log( sprintf( __( 'Post %1$d: %2$s', 'wp-seo' ), $post->ID, $post->post_title ) );
					} else {
						$this->describe_post( $post );
					}

					$cursor->set( $post->ID );

					if ( $this->failed > $allowed_failures ) {
						WP_CLI::error(
							sprintf(
								/* translators: %d: number of posts allowed to fail. */
								_n( 'More than %d post failed, so the run stopped.', 'More than %d posts failed, so the run stopped.', $allowed_failures, 'wp-seo' ),
								$allowed_failures
							) . ' ' . $this->summarize()
						);
					}

					if ( $this->attempted >= $limit ) {
						throw $limit_reached; // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					}
				}
			);
		} catch ( \RuntimeException $e ) {
			if ( $e !== $limit_reached ) {
				throw $e;
			}

			/* translators: %d: limit, in posts. */
			WP_CLI::log( sprintf( _n( 'Stopped at the limit of %d post.', 'Stopped at the limit of %d posts.', $limit, 'wp-seo' ), $limit ) );
		}

		if ( $dry_run ) {
			/* translators: %d: number of posts. */
			WP_CLI::success( sprintf( _n( '%d post would get a description.', '%d posts would get a description.', $this->attempted, 'wp-seo' ), $this->attempted ) );
			return;
		}

		WP_CLI::success( $this->summarize() );
	}

	/**
	 * Generate and save the description of one post, or log why it was not.
	 *
	 * @param \WP_Post $post Post to describe.
	 */
	private function describe_post( \WP_Post $post ): void {
		$description = Description_Generator::generate_and_save_description( $post );

		if ( ! is_wp_error( $description ) ) {
			++$this->generated;

			/* translators: 1: post ID, 2: description. */
			WP_CLI::log( sprintf( __( 'Post %1$d: %2$s', 'wp-seo' ), $post->ID, $description ) );
			return;
		}

		// An editor's description was kept, which is not a failure.
		if ( Description_Generator::ERROR_DESCRIPTION_EXISTS === $description->get_error_code() ) {
			/* translators: 1: post ID, 2: message. */
			WP_CLI::log( sprintf( __( 'Post %1$d: %2$s', 'wp-seo' ), $post->ID, $description->get_error_message() ) );
			return;
		}

		$message = sprintf(
			/* translators: 1: post ID, 2: error message, 3: error code. */
			__( 'Post %1$d: %2$s (%3$s)', 'wp-seo' ),
			$post->ID,
			$description->get_error_message(),
			$description->get_error_code()
		);

		/*
		 * Every post after this one would fail too, so the run stops before the
		 * place is saved, and the post is tried again next run.
		 */
		if ( Description_Generator::ERROR_PROVIDER_LIMITED === $description->get_error_code() ) {
			WP_CLI::error( $message . "\n" . __( 'The AI provider is limiting requests or is out of quota, so the run stopped.', 'wp-seo' ) . ' ' . $this->summarize() );
		}

		++$this->failed;
		WP_CLI::warning( $message );
	}

	/**
	 * Summarize what the run has done so far.
	 *
	 * @return string Summary.
	 */
	private function summarize(): string {
		return sprintf(
			/* translators: %d: number of descriptions. */
			_n( 'Generated %d description.', 'Generated %d descriptions.', $this->generated, 'wp-seo' ),
			$this->generated
		) . ' ' . sprintf(
			/* translators: %d: number of posts. */
			_n( '%d post failed.', '%d posts failed.', $this->failed, 'wp-seo' ),
			$this->failed
		);
	}
}
