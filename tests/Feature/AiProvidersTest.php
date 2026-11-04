<?php
/**
 * AiProvidersTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\AI_Providers;
use Alley\WP\WP_SEO\Tests\TestCase;

/**
 * Tests for the AI providers WP SEO can send prompts to, and the one it uses.
 *
 * No test sends a prompt: that would need a provider plugin and credentials,
 * and would make a request to the provider.
 *
 * @link https://mantle.alley.com/docs/testing
 */
class AiProvidersTest extends TestCase {
	/**
	 * Connectors registered by a test, removed again after it.
	 *
	 * @var string[]
	 */
	private array $connectors = [];

	protected function tearDown(): void {
		foreach ( $this->connectors as $id ) {
			\WP_Connector_Registry::get_instance()->unregister( $id );
		}

		parent::tearDown();
	}

	/**
	 * Register a connector for the length of a test.
	 *
	 * @param string $id        Connector ID.
	 * @param string $type      Connector type.
	 * @param bool   $is_active Whether the connector's plugin is active.
	 */
	private function register_connector( string $id, string $type, bool $is_active ): void {
		\WP_Connector_Registry::get_instance()->register(
			$id,
			[
				'name'           => "Test {$id}",
				'type'           => $type,
				'authentication' => [ 'method' => 'none' ],
				'plugin'         => [ 'is_active' => $is_active ? '__return_true' : '__return_false' ],
			]
		);

		$this->connectors[] = $id;
	}

	/**
	 * An AI provider whose plugin is active is listed by name.
	 */
	public function test_get_available_providers_lists_active_ai_providers() {
		$this->register_connector( 'wp_seo_active', 'ai_provider', true );

		$providers = AI_Providers::get_available_providers();

		$this->assertArrayHasKey( 'wp_seo_active', $providers );
		$this->assertSame( 'Test wp_seo_active', $providers['wp_seo_active'] );
	}

	/**
	 * Connectors that are not AI providers, and AI providers whose plugin is not
	 * active, are left out.
	 */
	public function test_get_available_providers_omits_inactive_and_non_ai_connectors() {
		$this->register_connector( 'wp_seo_inactive', 'ai_provider', false );
		$this->register_connector( 'wp_seo_other', 'spam_filtering', true );

		$providers = AI_Providers::get_available_providers();

		$this->assertArrayNotHasKey( 'wp_seo_inactive', $providers );
		$this->assertArrayNotHasKey( 'wp_seo_other', $providers );
		$this->assertArrayNotHasKey( 'akismet', $providers );
	}

	/**
	 * Once there is a provider, it is the one prompts are sent to.
	 */
	public function test_get_prompt_provider_returns_the_provider() {
		add_filter( 'wp_seo_ai_provider', fn () => 'wp_seo_missing' );

		$this->assertSame( 'wp_seo_missing', AI_Providers::get_prompt_provider() );
	}

	/**
	 * With AI turned off on the site, no prompts are sent, whatever the provider.
	 */
	public function test_get_prompt_provider_fails_when_ai_is_turned_off() {
		add_filter( 'wp_seo_ai_provider', fn () => 'wp_seo_missing' );
		add_filter( 'wp_supports_ai', '__return_false' );

		$result = AI_Providers::get_prompt_provider();

		$this->assertWPError( $result );
		$this->assertSame( 'wp_seo_ai_unsupported', $result->get_error_code() );
	}

	/**
	 * Without a provider, no prompts are sent.
	 */
	public function test_get_prompt_provider_fails_without_a_provider() {
		$result = AI_Providers::get_prompt_provider();

		$this->assertWPError( $result );
		$this->assertSame( 'wp_seo_ai_no_provider', $result->get_error_code() );
		$this->assertSame( [ 'status' => 503 ], $result->get_error_data() );
	}

	/**
	 * The provider is the one set in code, and a filter that answers with
	 * something other than a string means no provider.
	 */
	public function test_get_provider_uses_the_provider_set_in_code() {
		$this->assertSame( '', AI_Providers::get_provider() );

		add_filter( 'wp_seo_ai_provider', fn () => 'wp_seo_active' );

		$this->assertSame( 'wp_seo_active', AI_Providers::get_provider() );

		add_filter( 'wp_seo_ai_provider', fn () => 123, 20 );

		$this->assertSame( '', AI_Providers::get_provider() );
	}
}
