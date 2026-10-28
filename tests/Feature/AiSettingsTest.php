<?php
/**
 * AiSettingsTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\AI_Providers;
use Alley\WP\WP_SEO\AI_Settings;
use Alley\WP\WP_SEO\Features_Page;
use Alley\WP\WP_SEO\Tests\TestCase;

use function Mantle\Support\Helpers\capture;

/**
 * Tests for the page that chooses the AI provider WP SEO sends prompts to.
 *
 * These tests use the setting the plugin registered when it booted the page.
 *
 * @link https://mantle.alley.com/docs/testing
 */
class AiSettingsTest extends TestCase {
	/**
	 * Connectors registered by a test, removed again after it.
	 *
	 * @var string[]
	 */
	private array $connectors = [];

	protected function setUp(): void {
		parent::setUp();

		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
		$GLOBALS['submenu'] = [];
		// phpcs:enable WordPress.WP.GlobalVariablesOverride.Prohibited

		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	protected function tearDown(): void {
		foreach ( $this->connectors as $id ) {
			\WP_Connector_Registry::get_instance()->unregister( $id );
		}

		delete_option( AI_Providers::OPTION );

		parent::tearDown();
	}

	/**
	 * Register an active AI provider for the length of a test.
	 *
	 * @param string $id Connector ID.
	 */
	private function register_provider( string $id ): void {
		\WP_Connector_Registry::get_instance()->register(
			$id,
			[
				'name'           => "Test {$id}",
				'type'           => 'ai_provider',
				'authentication' => [ 'method' => 'none' ],
				'plugin'         => [ 'is_active' => '__return_true' ],
			]
		);

		$this->connectors[] = $id;
	}

	/**
	 * The option is registered with WordPress.
	 */
	public function test_setting_is_registered() {
		$this->assertArrayHasKey( AI_Providers::OPTION, get_registered_settings() );
	}

	/**
	 * Saving the option keeps only a provider that is available.
	 */
	public function test_saving_is_sanitized() {
		$this->register_provider( 'wp_seo_active' );

		update_option( AI_Providers::OPTION, 'wp_seo_active' );

		$this->assertSame( 'wp_seo_active', get_option( AI_Providers::OPTION ) );

		update_option( AI_Providers::OPTION, 'not_a_provider' );

		$this->assertSame( '', get_option( AI_Providers::OPTION ) );
	}

	/**
	 * The page is added to the WP SEO menu.
	 */
	public function test_page_is_added_to_the_menu() {
		( new AI_Settings() )->add_page();

		$this->assertSame( AI_Settings::SLUG, $GLOBALS['submenu'][ Features_Page::SLUG ][0][2] ?? null );
	}

	/**
	 * Loading the page adds the provider field.
	 */
	public function test_page_lists_the_provider_field() {
		// Tests do not run in an admin request, which would have loaded this.
		require_once ABSPATH . 'wp-admin/includes/template.php';

		$page = new AI_Settings();
		$page->add_page();

		do_action( 'load-' . get_plugin_page_hookname( AI_Settings::SLUG, Features_Page::SLUG ) );

		$html = capture( fn () => $page->render() );

		$this->assertStringContainsString( 'AI provider', $html );
		$this->assertStringContainsString( 'name="wp_seo_ai_provider"', $html );
	}

	/**
	 * The field is editable, and lists the available providers, unless the
	 * provider is set in code.
	 */
	public function test_field_is_editable_unless_set_in_code() {
		$this->register_provider( 'wp_seo_active' );

		$field = capture( fn () => ( new AI_Settings() )->render_field() );

		$this->assertStringContainsString( 'value="wp_seo_active"', $field );
		$this->assertStringNotContainsString( 'disabled', $field );

		add_filter( 'wp_seo_ai_provider', fn () => 'wp_seo_active' );

		$field = capture( fn () => ( new AI_Settings() )->render_field() );

		$this->assertStringContainsString( "disabled='disabled'", $field );
		$this->assertMatchesRegularExpression( "/value=\"wp_seo_active\"\\s+selected='selected'/", $field );
		$this->assertStringContainsString( 'Set in code', $field );
	}

	/**
	 * A provider set in code that is not available is listed as unavailable,
	 * rather than the field showing "None".
	 */
	public function test_field_lists_an_unavailable_provider_set_in_code() {
		add_filter( 'wp_seo_ai_provider', fn () => 'wp_seo_missing' );

		$field = capture( fn () => ( new AI_Settings() )->render_field() );

		$this->assertMatchesRegularExpression( "/value=\"wp_seo_missing\"\\s+selected='selected'>\\s*wp_seo_missing \\(unavailable\\)/", $field );
	}

	/**
	 * With no providers to choose from, the page points to where they are set
	 * up.
	 */
	public function test_section_links_to_connectors_when_there_are_no_providers() {
		$page = new AI_Settings();

		$this->assertSame( [], AI_Providers::get_available_providers(), 'No AI provider plugin is active in the test environment.' );
		$this->assertStringContainsString( 'options-connectors.php', capture( fn () => $page->render_section() ) );

		$this->register_provider( 'wp_seo_active' );

		$this->assertSame( '', capture( fn () => $page->render_section() ) );
	}
}
