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

		delete_option( AI_Providers::OPTION );

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
	 * Prompts can be sent once there is a provider.
	 */
	public function test_check_provider_passes_with_a_provider() {
		add_filter( 'wp_seo_ai_provider', fn () => 'wp_seo_missing' );

		$this->assertTrue( AI_Providers::check_provider() );
	}

	/**
	 * Without a provider, no prompts are sent.
	 */
	public function test_check_provider_fails_without_a_provider() {
		$result = AI_Providers::check_provider();

		$this->assertWPError( $result );
		$this->assertSame( 'wp_seo_ai_no_provider', $result->get_error_code() );
		$this->assertSame( [ 'status' => 503 ], $result->get_error_data() );
	}

	/**
	 * A prompt starts as the core prompt builder.
	 */
	public function test_get_prompt_builder_returns_core_prompt_builder() {
		add_filter( 'wp_seo_ai_provider', fn () => 'wp_seo_missing' );

		$this->assertInstanceOf( \WP_AI_Client_Prompt_Builder::class, AI_Providers::get_prompt_builder( 'Hello' ) );
	}

	/**
	 * A prompt goes to the provider. A provider that does not exist shows this
	 * without a request being made: generating fails, naming it.
	 */
	public function test_get_prompt_builder_uses_the_provider() {
		add_filter( 'wp_seo_ai_provider', fn () => 'wp_seo_missing' );

		$result = AI_Providers::get_prompt_builder( 'Hello' )->generate_text();

		$this->assertWPError( $result );
		$this->assertStringContainsString( 'wp_seo_missing', $result->get_error_message() );
	}

	/**
	 * Without a provider, a prompt cannot be started.
	 */
	public function test_get_prompt_builder_fails_without_a_provider() {
		$result = AI_Providers::get_prompt_builder( 'Hello' );

		$this->assertWPError( $result );
		$this->assertSame( 'wp_seo_ai_no_provider', $result->get_error_code() );
	}

	/**
	 * A provider set in code wins over the stored one, the stored one is used
	 * otherwise, and with neither there is no provider.
	 */
	public function test_get_provider_prefers_code_then_setting() {
		$this->assertSame( '', AI_Providers::get_provider() );

		$this->register_connector( 'wp_seo_active', 'ai_provider', true );
		update_option( AI_Providers::OPTION, 'wp_seo_active' );

		$this->assertSame( 'wp_seo_active', AI_Providers::get_provider() );

		add_filter( 'wp_seo_ai_provider', fn () => '' );

		$this->assertSame( '', AI_Providers::get_provider(), 'An empty string set in code means no provider.' );
	}

	/**
	 * A filter that answers with something other than a string leaves the
	 * choice to the settings page.
	 */
	public function test_get_provider_ignores_non_strings_set_in_code() {
		$this->register_connector( 'wp_seo_active', 'ai_provider', true );
		update_option( AI_Providers::OPTION, 'wp_seo_active' );

		add_filter( 'wp_seo_ai_provider', fn () => 123 );

		$this->assertSame( 'wp_seo_active', AI_Providers::get_provider() );
		$this->assertFalse( AI_Providers::get_choice()['in_code'] );
	}

	/**
	 * The choice lists the available providers, and is read-only when the
	 * provider is set in code.
	 */
	public function test_get_choice_is_read_only_when_set_in_code() {
		$this->register_connector( 'wp_seo_active', 'ai_provider', true );

		$this->assertSame(
			[
				'provider'  => '',
				'providers' => [ 'wp_seo_active' => 'Test wp_seo_active' ],
				'in_code'   => false,
			],
			AI_Providers::get_choice()
		);

		add_filter( 'wp_seo_ai_provider', fn () => 'wp_seo_active' );

		$this->assertSame(
			[
				'provider'  => 'wp_seo_active',
				'providers' => [ 'wp_seo_active' => 'Test wp_seo_active' ],
				'in_code'   => true,
			],
			AI_Providers::get_choice()
		);
	}

	/**
	 * A provider that is not available, as one set in code can be, is listed
	 * as unavailable rather than left out.
	 */
	public function test_get_choice_lists_an_unavailable_provider() {
		add_filter( 'wp_seo_ai_provider', fn () => 'wp_seo_missing' );

		$choice = AI_Providers::get_choice();

		$this->assertSame( 'wp_seo_missing', $choice['provider'] );
		$this->assertSame( [ 'wp_seo_missing' => 'wp_seo_missing (unavailable)' ], $choice['providers'] );
	}

	/**
	 * Sanitizing keeps only a provider that is available, and "None" clears it.
	 */
	public function test_sanitize_provider_keeps_only_available_providers() {
		$this->register_connector( 'wp_seo_active', 'ai_provider', true );

		$this->assertSame( 'wp_seo_active', AI_Providers::sanitize_provider( 'wp_seo_active' ) );
		$this->assertSame( '', AI_Providers::sanitize_provider( 'not_a_provider' ) );
		$this->assertSame( '', AI_Providers::sanitize_provider( [ 'wp_seo_active' ] ) );
		$this->assertSame( '', AI_Providers::sanitize_provider( '' ) );
	}

	/**
	 * When nothing is submitted, as from a disabled field, the stored provider
	 * is kept.
	 */
	public function test_sanitize_provider_keeps_the_stored_provider_when_nothing_is_submitted() {
		$this->register_connector( 'wp_seo_active', 'ai_provider', true );
		update_option( AI_Providers::OPTION, 'wp_seo_active' );

		$this->assertSame( 'wp_seo_active', AI_Providers::sanitize_provider( null ) );
	}
}
