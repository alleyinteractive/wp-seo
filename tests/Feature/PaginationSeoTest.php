<?php
/**
 * PaginationSeoTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;
use Alley\WP\WP_SEO\Features\Pagination_SEO;

/**
 * Tests for the Pagination_SEO feature.
 */
class PaginationSeoTest extends TestCase {

	protected Pagination_SEO $feature;

	public function set_up(): void {
		parent::set_up();
		$this->feature = new Pagination_SEO();
	}

	// ------------------------------------------------------------------
	// get_page_number_from_link
	// ------------------------------------------------------------------

	public function test_get_page_number_from_pretty_permalink_link(): void {
		$link = '<a href="https://example.com/page/5/">5</a>';
		$this->assertSame( 5, $this->feature->get_page_number_from_link( $link ) );
	}

	public function test_get_page_number_from_nested_pretty_permalink_link(): void {
		$link = '<a href="https://example.com/category/news/page/21/">21</a>';
		$this->assertSame( 21, $this->feature->get_page_number_from_link( $link ) );
	}

	public function test_get_page_number_from_query_string_link(): void {
		$link = '<a href="https://example.com/?paged=15">15</a>';
		$this->assertSame( 15, $this->feature->get_page_number_from_link( $link ) );
	}

	public function test_get_page_number_from_query_string_with_amp(): void {
		$link = '<a href="https://example.com/?cat=1&paged=22">22</a>';
		$this->assertSame( 22, $this->feature->get_page_number_from_link( $link ) );
	}

	public function test_get_page_number_returns_zero_for_non_pagination_link(): void {
		$link = '<a href="https://example.com/about/">About</a>';
		$this->assertSame( 0, $this->feature->get_page_number_from_link( $link ) );
	}

	public function test_get_page_number_returns_zero_for_span(): void {
		$link = '<span class="page-numbers current">1</span>';
		$this->assertSame( 0, $this->feature->get_page_number_from_link( $link ) );
	}

	// ------------------------------------------------------------------
	// add_nofollow_to_anchor
	// ------------------------------------------------------------------

	public function test_add_nofollow_to_plain_anchor(): void {
		$link   = '<a href="https://example.com/page/21/">21</a>';
		$result = $this->feature->add_nofollow_to_anchor( $link );
		$this->assertStringContainsString( 'rel="nofollow"', $result );
	}

	public function test_add_nofollow_merges_with_existing_rel(): void {
		$link   = '<a class="page-numbers" rel="prev" href="https://example.com/page/21/">21</a>';
		$result = $this->feature->add_nofollow_to_anchor( $link );
		$this->assertStringContainsString( 'rel="prev nofollow"', $result );
		$this->assertStringNotContainsString( 'rel="prev"', $result );
	}

	public function test_add_nofollow_is_idempotent_when_already_present(): void {
		$link   = '<a rel="nofollow" href="https://example.com/page/21/">21</a>';
		$result = $this->feature->add_nofollow_to_anchor( $link );
		// Should not duplicate nofollow.
		$this->assertSame( 1, substr_count( $result, 'nofollow' ) );
	}

	// ------------------------------------------------------------------
	// nofollow_deep_pagination_links
	// ------------------------------------------------------------------

	public function test_nofollow_added_for_link_past_limit(): void {
		$link   = '<a href="https://example.com/page/21/">21</a>';
		$result = $this->feature->nofollow_deep_pagination_links( $link );
		$this->assertStringContainsString( 'rel="nofollow"', $result );
	}

	public function test_no_nofollow_for_link_at_limit(): void {
		$link   = '<a href="https://example.com/page/20/">20</a>';
		$result = $this->feature->nofollow_deep_pagination_links( $link );
		$this->assertStringNotContainsString( 'nofollow', $result );
	}

	public function test_no_nofollow_for_link_before_limit(): void {
		$link   = '<a href="https://example.com/page/1/">1</a>';
		$result = $this->feature->nofollow_deep_pagination_links( $link );
		$this->assertStringNotContainsString( 'nofollow', $result );
	}

	public function test_nofollow_added_for_query_string_link_past_limit(): void {
		$link   = '<a href="https://example.com/?paged=25">25</a>';
		$result = $this->feature->nofollow_deep_pagination_links( $link );
		$this->assertStringContainsString( 'rel="nofollow"', $result );
	}

	// ------------------------------------------------------------------
	// add_robots_txt_rules
	// ------------------------------------------------------------------

	public function test_robots_txt_rules_added_for_public_site(): void {
		$output = $this->feature->add_robots_txt_rules( '', true );

		// Allow rules for every page from 1 to the default limit.
		$limit = Pagination_SEO::DEFAULT_PAGINATION_LIMIT;
		for ( $page = 1; $page <= $limit; $page++ ) {
			$this->assertStringContainsString( "Allow: */page/{$page}/", $output );
		}

		// Catch-all Disallow.
		$this->assertStringContainsString( 'Disallow: */page/', $output );

		// Page just past the limit must NOT have an Allow rule.
		$this->assertStringNotContainsString( 'Allow: */page/' . ( $limit + 1 ) . '/', $output );
	}

	public function test_robots_txt_rules_not_added_for_private_site(): void {
		$output = $this->feature->add_robots_txt_rules( '', false );
		$this->assertEmpty( $output );
	}

	public function test_robots_txt_preserves_existing_content(): void {
		$existing = "User-agent: *\nDisallow:\n";
		$output   = $this->feature->add_robots_txt_rules( $existing, true );
		$this->assertStringStartsWith( $existing, $output );
	}

	public function test_robots_txt_respects_custom_limit(): void {
		add_filter( 'wp_seo_pagination_limit', fn() => 5 );

		$output = $this->feature->add_robots_txt_rules( '', true );

		$this->assertStringContainsString( 'Allow: */page/5/', $output );
		$this->assertStringNotContainsString( 'Allow: */page/6/', $output );

		remove_all_filters( 'wp_seo_pagination_limit' );
	}

	// ------------------------------------------------------------------
	// noindex_paginated_archives
	// ------------------------------------------------------------------

	public function test_noindex_not_applied_when_not_paged(): void {
		// Without setting up a real paged query, is_paged() returns false.
		$robots = $this->feature->noindex_paginated_archives( [] );
		$this->assertArrayNotHasKey( 'noindex', $robots );
	}

	// ------------------------------------------------------------------
	// nofollow_next_posts_link
	// ------------------------------------------------------------------

	public function test_nofollow_next_posts_link_not_added_on_page_1(): void {
		// On page 1 the next page is 2, which is within the default limit.
		$result = $this->feature->nofollow_next_posts_link( '' );
		$this->assertEmpty( $result );
	}
}
