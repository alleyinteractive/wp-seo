<?php
/**
 * Description_Generator_Command class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

use Alley\WP\WP_SEO\Features\Description_Generator;
use Alley\WP_Bulk_Task\Bulk_Task;
use Alley\WP_Bulk_Task\Cursor\Memory_Cursor;
use Alley\WP_Bulk_Task\Cursor\Option_Cursor;
use WP_CLI;

/**
 * The `wp wp-seo generate-descriptions` command, which generates and saves
 * meta descriptions in bulk with the Meta Description Generator.
 *
 * The Meta Description Generator feature registers it while the feature is
 * active.
 */
final class Description_Generator_Command {
	/**
	 * Generate meta descriptions for posts that don't have one.
	 *
	 * Sends each post without a description to the AI provider chosen for the
	 * Meta Description Generator, and saves the description it writes. A post
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
	 * replace the defaults: published posts without a password, of the post
	 * types the Meta Description Generator works on. Posts that already have a
	 * description are always left out, and posts are always ordered by ID.
	 *
	 * [--limit=<number>]
	 * : Most posts to send to the AI provider in this run. Default 100.
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

		if ( '' === get_ai_provider( 'description_generator' ) ) {
			WP_CLI::error( __( 'No AI provider is selected for the Meta Description Generator.', 'wp-seo' ) );
		}

		$query_args = array_merge(
			[
				'post_type'    => get_description_generator_post_types(),
				'post_status'  => 'publish',
				'has_password' => false,
			],
			$query,
			[
				// Bulk_Task pages through posts by the ID of the last post object.
				'fields'     => 'all',
				'meta_query' => $this->get_missing_description_meta_query( $query['meta_query'] ?? null ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			]
		);

		// WP_Query reads no post types as `post`, which the filter may have removed.
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

		$allowed_failures = (int) floor( $limit * $fault_tolerance / 100 );
		$attempted        = 0;
		$generated        = 0;
		$failed           = 0;

		/*
		 * Bulk_Task has no way to stop partway, so reaching the limit throws
		 * this, which is caught below. Its cleanup after a run is skipped then,
		 * which matters only to a process that goes on to change terms.
		 */
		$limit_reached = new \RuntimeException();

		try {
			( new Bulk_Task( $key, null, $cursor ) )->run(
				$query_args,
				function ( \WP_Post $post ) use ( $cursor, $dry_run, $limit, $allowed_failures, $limit_reached, &$attempted, &$generated, &$failed ): void {
					if ( $attempted >= $limit ) {
						throw $limit_reached; // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					}

					++$attempted;

					if ( $dry_run ) {
						/* translators: 1: post ID, 2: post title. */
						WP_CLI::log( sprintf( __( 'Post %1$d: %2$s', 'wp-seo' ), $post->ID, $post->post_title ) );
						$cursor->set( $post->ID );
						return;
					}

					$description = generate_description( $post );

					if ( is_wp_error( $description ) ) {
						$message = sprintf(
							/* translators: 1: post ID, 2: error message, 3: error code. */
							__( 'Post %1$d: %2$s (%3$s)', 'wp-seo' ),
							$post->ID,
							$description->get_error_message(),
							$description->get_error_code()
						);
						$data = $description->get_error_data();

						/*
						 * Core reports a provider's rate limit or exhausted quota
						 * with a 429 status. Every post after it would fail too,
						 * so the run stops, and the place is not saved so that
						 * the post is tried again next run.
						 */
						if ( is_array( $data ) && 429 === ( $data['status'] ?? null ) ) {
							WP_CLI::error( $message . "\n" . __( 'The AI provider is limiting requests or is out of quota, so the run stopped.', 'wp-seo' ) . ' ' . $this->summarize( $generated, $failed ) );
						}

						++$failed;
						WP_CLI::warning( $message );
						$cursor->set( $post->ID );

						if ( $failed > $allowed_failures ) {
							WP_CLI::error(
								sprintf(
									/* translators: %d: number of posts allowed to fail. */
									_n( 'More than %d post failed, so the run stopped.', 'More than %d posts failed, so the run stopped.', $allowed_failures, 'wp-seo' ),
									$allowed_failures
								) . ' ' . $this->summarize( $generated, $failed )
							);
						}

						return;
					}

					update_post_meta( $post->ID, Description_Generator::META_KEY, $description );
					++$generated;

					/* translators: 1: post ID, 2: description. */
					WP_CLI::log( sprintf( __( 'Post %1$d: %2$s', 'wp-seo' ), $post->ID, $description ) );
					$cursor->set( $post->ID );
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
			WP_CLI::success( sprintf( _n( '%d post would get a description.', '%d posts would get a description.', $attempted, 'wp-seo' ), $attempted ) );
			return;
		}

		WP_CLI::success( $this->summarize( $generated, $failed ) );
	}

	/**
	 * Get the meta query for posts without a description, combined with the
	 * one passed in --query, if any.
	 *
	 * @param mixed $meta_query Meta query passed in --query.
	 * @return array<int|string, mixed> Meta query.
	 */
	private function get_missing_description_meta_query( mixed $meta_query ): array {
		$missing = [
			'relation' => 'OR',
			[
				'key'     => Description_Generator::META_KEY,
				'compare' => 'NOT EXISTS',
			],
			[
				'key'   => Description_Generator::META_KEY,
				'value' => '',
			],
		];

		if ( null === $meta_query ) {
			return [ $missing ];
		}

		return [
			'relation' => 'AND',
			$missing,
			$meta_query,
		];
	}

	/**
	 * Summarize what a run did.
	 *
	 * @param int $generated Number of descriptions generated.
	 * @param int $failed    Number of posts that failed.
	 * @return string Summary.
	 */
	private function summarize( int $generated, int $failed ): string {
		return sprintf(
			/* translators: %d: number of descriptions. */
			_n( 'Generated %d description.', 'Generated %d descriptions.', $generated, 'wp-seo' ),
			$generated
		) . ' ' . sprintf(
			/* translators: %d: number of posts. */
			_n( '%d post failed.', '%d posts failed.', $failed, 'wp-seo' ),
			$failed
		);
	}
}
