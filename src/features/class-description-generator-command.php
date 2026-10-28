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

		$provider = Description_Generator::get_provider();

		if ( is_wp_error( $provider ) ) {
			WP_CLI::error( $provider );
		}

		$query_args = array_merge(
			[
				'post_type'    => Description_Generator::get_post_types(),
				'post_status'  => 'publish',
				'has_password' => false,
			],
			$query,
			[
				// Bulk_Task pages through posts by the ID of the last post object.
				'fields'                     => 'all',
				'wp_seo_missing_description' => true,
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
		$this->attempted  = 0;
		$this->generated  = 0;
		$this->failed     = 0;

		/*
		 * Bulk_Task has no way to stop partway, so reaching the limit throws
		 * this, which is caught below. Its cleanup after a run is skipped then,
		 * which matters only to a process that goes on to change terms.
		 */
		$limit_reached = new \RuntimeException();

		$where_missing_description = $this->where_missing_description( ... );
		add_filter( 'posts_where', $where_missing_description, 10, 2 );

		try {
			( new Bulk_Task( $key, null, $cursor ) )->run(
				$query_args,
				function ( \WP_Post $post ) use ( $cursor, $dry_run, $limit, $allowed_failures, $limit_reached ): void {
					if ( $this->attempted >= $limit ) {
						throw $limit_reached; // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
					}

					++$this->attempted;
					$this->describe_post( $post, $cursor, $dry_run, $allowed_failures );
				}
			);
		} catch ( \RuntimeException $e ) {
			if ( $e !== $limit_reached ) {
				throw $e;
			}

			/* translators: %d: limit, in posts. */
			WP_CLI::log( sprintf( _n( 'Stopped at the limit of %d post.', 'Stopped at the limit of %d posts.', $limit, 'wp-seo' ), $limit ) );
		} finally {
			remove_filter( 'posts_where', $where_missing_description, 10 );
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
	 * The place reached is saved after the post, except when the AI provider
	 * is limiting requests, so that the post is tried again next run.
	 *
	 * @param \WP_Post                    $post             Post to describe.
	 * @param Option_Cursor|Memory_Cursor $cursor           Place reached in the run.
	 * @param bool                        $dry_run          Whether to only list the post.
	 * @param int                         $allowed_failures Number of posts that may fail before the run stops.
	 */
	private function describe_post( \WP_Post $post, Option_Cursor|Memory_Cursor $cursor, bool $dry_run, int $allowed_failures ): void {
		if ( $dry_run ) {
			/* translators: 1: post ID, 2: post title. */
			WP_CLI::log( sprintf( __( 'Post %1$d: %2$s', 'wp-seo' ), $post->ID, $post->post_title ) );
			$cursor->set( $post->ID );
			return;
		}

		$description = Description_Generator::generate_description( $post );
		$max_length  = Description_Generator::get_max_length( $post );

		// The description is saved without an editor reviewing it, so one that is too long is refused.
		if ( is_string( $description ) && mb_strlen( $description ) > $max_length ) {
			$description = new \WP_Error(
				'wp_seo_description_too_long',
				sprintf(
					/* translators: %d: maximum length, in characters. */
					__( 'The AI provider returned a description longer than %d characters.', 'wp-seo' ),
					$max_length
				)
			);
		}

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
			 * Core reports a provider's rate limit or exhausted quota with a 429
			 * status. Every post after it would fail too, so the run stops, and
			 * the place is not saved so that the post is tried again next run.
			 */
			if ( is_array( $data ) && 429 === ( $data['status'] ?? null ) ) {
				WP_CLI::error( $message . "\n" . __( 'The AI provider is limiting requests or is out of quota, so the run stopped.', 'wp-seo' ) . ' ' . $this->summarize() );
			}

			++$this->failed;
			WP_CLI::warning( $message );
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

			return;
		}

		/*
		 * The post was queried with its batch, which can be minutes ago, so its
		 * description is read again, past the cache, in case an editor has
		 * saved one since.
		 */
		wp_cache_delete( $post->ID, 'post_meta' );

		if ( '' !== get_post_meta( $post->ID, Description_Generator::META_KEY, true ) ) {
			/* translators: %d: post ID. */
			WP_CLI::log( sprintf( __( 'Post %d: Kept the description saved while this one was being generated.', 'wp-seo' ), $post->ID ) );
			$cursor->set( $post->ID );
			return;
		}

		/*
		 * The description is saved without an editor reviewing it, and markup
		 * the provider escaped comes back decoded, so it is sanitized as the
		 * plain text it should be.
		 */
		$description = sanitize_text_field( $description );

		update_post_meta( $post->ID, Description_Generator::META_KEY, $description );
		++$this->generated;

		/* translators: 1: post ID, 2: description. */
		WP_CLI::log( sprintf( __( 'Post %1$d: %2$s', 'wp-seo' ), $post->ID, $description ) );
		$cursor->set( $post->ID );
	}

	/**
	 * Limit the command's query to posts without a description, or with an
	 * empty one.
	 *
	 * Other queries that run during the command, such as ones a post's
	 * content makes, are left alone.
	 *
	 * This is a correlated NOT EXISTS rather than a meta query, which would
	 * join the meta table twice to match both missing and empty descriptions.
	 * Either way, MySQL reads every meta row of each post it steps over, and
	 * a run steps over every post with a description between where it starts
	 * and the next post without one. On 500,000 posts with 10 meta rows each,
	 * all but the newest described, finding the first batch took 13 seconds
	 * this way, and 19 with the meta query.
	 *
	 * @param string    $where WHERE clause of the query.
	 * @param \WP_Query $query The query.
	 * @return string WHERE clause.
	 */
	private function where_missing_description( string $where, \WP_Query $query ): string {
		global $wpdb;

		if ( ! $query->get( 'wp_seo_missing_description' ) || ! $wpdb instanceof \wpdb ) {
			return $where;
		}

		return $where . $wpdb->prepare(
			' AND NOT EXISTS ( SELECT 1 FROM %i AS wp_seo_description WHERE wp_seo_description.post_id = %i.ID AND wp_seo_description.meta_key = %s AND wp_seo_description.meta_value <> %s )',
			$wpdb->postmeta,
			$wpdb->posts,
			Description_Generator::META_KEY,
			''
		);
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
