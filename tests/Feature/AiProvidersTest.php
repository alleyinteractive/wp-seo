<?php
/**
 * AiProvidersTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\AI_Providers;
use Alley\WP\WP_SEO\Feature;
use Alley\WP\WP_SEO\Registry;
use Alley\WP\WP_SEO\Tests\TestCase;

/**
 * Tests for the AI providers WP SEO features can send prompts to, and the one
 * each feature uses.
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
	 * A feature with a provider can send prompts.
	 */
	public function test_check_feature_passes_with_a_provider() {
		add_filter( 'wp_seo_ai_provider', fn () => 'wp_seo_missing' );

		$this->assertTrue( AI_Providers::check_feature( 'description_generator' ) );
	}

	/**
	 * A feature without a provider sends no prompts.
	 */
	public function test_check_feature_fails_without_a_provider() {
		$result = AI_Providers::check_feature( 'description_generator' );

		$this->assertWPError( $result );
		$this->assertSame( 'wp_seo_ai_no_provider', $result->get_error_code() );
		$this->assertSame( [ 'status' => 503 ], $result->get_error_data() );
	}

	/**
	 * The error for a feature without a provider names the feature by its label.
	 */
	public function test_check_feature_names_the_feature_without_a_provider() {
		Registry::reset_for_tests();

		Feature::top_level(
			'wp_seo_test',
			'Test Feature',
			new class() implements \Alley\WP\Types\Feature {
				/**
				 * Boot the feature.
				 */
				public function boot(): void {}
			}
		);

		$this->assertSame( 'No AI provider is selected for the Test Feature.', AI_Providers::check_feature( 'wp_seo_test' )->get_error_message() );
		$this->assertSame( 'No AI provider is selected for this feature.', AI_Providers::check_feature( 'wp_seo_unregistered' )->get_error_message() );
	}

	/**
	 * A prompt starts as the core prompt builder.
	 */
	public function test_get_prompt_builder_returns_core_prompt_builder() {
		add_filter( 'wp_seo_ai_provider', fn () => 'wp_seo_missing' );

		$this->assertInstanceOf( \WP_AI_Client_Prompt_Builder::class, AI_Providers::get_prompt_builder( 'description_generator', 'Hello' ) );
	}

	/**
	 * A prompt goes to the feature's provider. A provider that does not exist
	 * shows this without a request being made: generating fails, naming it.
	 */
	public function test_get_prompt_builder_uses_the_features_provider() {
		add_filter( 'wp_seo_ai_provider', fn () => 'wp_seo_missing' );

		$result = AI_Providers::get_prompt_builder( 'description_generator', 'Hello' )->generate_text();

		$this->assertWPError( $result );
		$this->assertStringContainsString( 'wp_seo_missing', $result->get_error_message() );
	}

	/**
	 * A feature without a provider cannot start a prompt.
	 */
	public function test_get_prompt_builder_fails_without_a_provider() {
		$result = AI_Providers::get_prompt_builder( 'description_generator', 'Hello' );

		$this->assertWPError( $result );
		$this->assertSame( 'wp_seo_ai_no_provider', $result->get_error_code() );
	}

	/**
	 * A provider set in code wins over the stored one, the stored one is used
	 * otherwise, and a feature with neither has no provider.
	 */
	public function test_get_feature_provider_prefers_code_then_setting() {
		$this->assertSame( '', AI_Providers::get_feature_provider( 'description_generator' ) );

		$this->register_connector( 'wp_seo_active', 'ai_provider', true );
		update_option( AI_Providers::OPTION, [ 'description_generator' => 'wp_seo_active' ] );

		$this->assertSame( 'wp_seo_active', AI_Providers::get_feature_provider( 'description_generator' ) );

		add_filter( 'wp_seo_ai_provider', fn () => '' );

		$this->assertSame( '', AI_Providers::get_feature_provider( 'description_generator' ), 'An empty string set in code means no provider.' );
	}

	/**
	 * A filter that answers with something other than a string leaves the
	 * choice to the settings page.
	 */
	public function test_get_feature_provider_from_code_ignores_non_strings() {
		add_filter( 'wp_seo_ai_provider', fn () => 123 );

		$this->assertNull( AI_Providers::get_feature_provider_from_code( 'description_generator' ) );
	}
}
