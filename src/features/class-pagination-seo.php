<?php
/**
 * Pagination_SEO class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Features;

use Alley\WP\Types\Feature;

/**
 * Handles SEO directives for paginated archive pages.
 *
 * - Archives page 2+: forced noindex, follow.
 * - Pagination links pointing past the depth limit: rel="nofollow".
 * - robots.txt: Allow pages 1–N, Disallow everything deeper.
 */
final class Pagination_SEO implements Feature {

	/**
	 * Default page depth after which deep-pagination rules kick in.
	 */
	const DEFAULT_PAGINATION_LIMIT = 20;

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		// Runs after WP_SEO's priority-10 wp_robots filter so it can override settings.
		add_filter( 'wp_robots', array( $this, 'noindex_paginated_archives' ), 20 );
		add_filter( 'paginate_links', array( $this, 'nofollow_deep_pagination_links' ) );
		add_filter( 'next_posts_link_attributes', array( $this, 'nofollow_next_posts_link' ) );
		add_filter( 'robots_txt', array( $this, 'add_robots_txt_rules' ), 10, 2 );
	}

	/**
	 * Return the page depth limit.
	 *
	 * @return int
	 */
	public function get_pagination_limit(): int {
		return (int) apply_filters( 'wp_seo_pagination_limit', self::DEFAULT_PAGINATION_LIMIT );
	}

	/**
	 * Force noindex + follow on every paginated archive page (page 2 and beyond).
	 *
	 * Runs at priority 20 so it can override anything set by WP_SEO settings.
	 *
	 * @param array $robots Associative array of robots directives.
	 * @return array
	 */
	public function noindex_paginated_archives( array $robots ): array {
		if ( ! is_paged() ) {
			return $robots;
		}

		if ( ! is_archive() && ! is_home() ) {
			return $robots;
		}

		$robots['noindex'] = true;
		unset( $robots['index'] );

		// Ensure follow; remove any nofollow that settings may have added.
		$robots['follow'] = true;
		unset( $robots['nofollow'] );

		return $robots;
	}

	/**
	 * Add rel="nofollow" to any paginate_links() anchor that targets a page past the limit.
	 *
	 * @param string $link HTML link string produced by paginate_links().
	 * @return string
	 */
	public function nofollow_deep_pagination_links( string $link ): string {
		$page_num = $this->get_page_number_from_link( $link );

		if ( $page_num > $this->get_pagination_limit() ) {
			$link = $this->add_nofollow_to_anchor( $link );
		}

		return $link;
	}

	/**
	 * Add rel="nofollow" to the next-posts link when the next page exceeds the limit.
	 *
	 * @param string $attributes Existing extra attributes for the anchor tag.
	 * @return string
	 */
	public function nofollow_next_posts_link( string $attributes ): string {
		$current_page = max( 1, (int) get_query_var( 'paged' ) );
		$next_page    = $current_page + 1;

		if ( $next_page > $this->get_pagination_limit() ) {
			$attributes .= ' rel="nofollow"';
		}

		return $attributes;
	}

	/**
	 * Appends robots.txt Disallow rules that block pagination deeper than the limit.
	 *
	 * Generates explicit Allow rules for pages 1–N and a catch-all Disallow for
	 * anything deeper. Uses the `/page / N / ` pattern to cover both root-level
	 * (/page/5/) and nested (/category/news/page/5/) WordPress pagination URLs.
	 *
	 * @param string $output Current robots.txt content.
	 * @param bool   $is_public Whether the site is publicly accessible.
	 * @return string Modified robots.txt content.
	 */
	public function add_robots_txt_rules( string $output, bool $is_public ): string {
		if ( ! $is_public ) {
			return $output;
		}

		$limit = $this->get_pagination_limit();

		$lines = array( sprintf( "\n# WP SEO: Allow first %d pages of archives; block deeper pagination.", $limit ) );

		for ( $page = 1; $page <= $limit; $page++ ) {
			$lines[] = "Allow: */page/{$page}/";
		}

		$lines[] = 'Disallow: */page/';

		$output .= implode( "\n", $lines ) . "\n";

		return $output;
	}

	/**
	 * Extract the target page number from a paginate_links() HTML string.
	 *
	 * Supports pretty-permalink (/page/N/) and query-string (?paged=N) formats.
	 *
	 * @param string $link HTML link string.
	 * @return int Page number, or 0 if none found.
	 */
	public function get_page_number_from_link( string $link ): int {
		if ( preg_match( '|/page/(\d+)/|', $link, $matches ) ) {
			return (int) $matches[1];
		}

		if ( preg_match( '/[?&]paged=(\d+)/', $link, $matches ) ) {
			return (int) $matches[1];
		}

		return 0;
	}

	/**
	 * Add rel="nofollow" to an anchor tag, merging with any existing rel value.
	 *
	 * @param string $link HTML anchor tag string.
	 * @return string
	 */
	public function add_nofollow_to_anchor( string $link ): string {
		if ( preg_match( '/\srel="([^"]*)"/', $link, $matches ) ) {
			$existing = $matches[1];

			if ( ! str_contains( $existing, 'nofollow' ) ) {
				$merged = trim( $existing . ' nofollow' );
				$link   = str_replace( 'rel="' . $existing . '"', 'rel="' . $merged . '"', $link );
			}
		} else {
			$replaced = preg_replace( '/<a\b/', '<a rel="nofollow"', $link, 1 );
			$link     = null !== $replaced ? $replaced : $link;
		}

		return $link;
	}
}
