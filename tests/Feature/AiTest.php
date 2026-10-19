<?php
/**
 * AiTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;

use function Alley\WP\WP_SEO\ai_prompt;
use function Alley\WP\WP_SEO\ai_providers;

/**
 * Tests for the helpers that use the AI connectors configured in WordPress.
 *
 * No test sends a prompt: that would need a provider plugin and credentials,
 * and would make a request to the provider.
 *
 * @link https://mantle.alley.com/docs/testing
 */
class AiTest extends TestCase {
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
	public function test_ai_providers_lists_active_ai_providers() {
		$this->register_connector( 'wp_seo_active', 'ai_provider', true );

		$providers = ai_providers();

		$this->assertArrayHasKey( 'wp_seo_active', $providers );
		$this->assertSame( 'Test wp_seo_active', $providers['wp_seo_active'] );
	}

	/**
	 * Connectors that are not AI providers, and AI providers whose plugin is not
	 * active, are left out.
	 */
	public function test_ai_providers_omits_inactive_and_non_ai_connectors() {
		$this->register_connector( 'wp_seo_inactive', 'ai_provider', false );
		$this->register_connector( 'wp_seo_other', 'spam_filtering', true );

		$providers = ai_providers();

		$this->assertArrayNotHasKey( 'wp_seo_inactive', $providers );
		$this->assertArrayNotHasKey( 'wp_seo_other', $providers );
		$this->assertArrayNotHasKey( 'akismet', $providers );
	}

	/**
	 * A prompt starts as the core prompt builder, with or without a provider.
	 */
	public function test_ai_prompt_returns_core_prompt_builder() {
		$this->assertInstanceOf( \WP_AI_Client_Prompt_Builder::class, ai_prompt( 'Hello' ) );
		$this->assertInstanceOf( \WP_AI_Client_Prompt_Builder::class, ai_prompt( 'Hello', 'anthropic' ) );
	}
}
