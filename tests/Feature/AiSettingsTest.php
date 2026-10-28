<?php
/**
 * AiSettingsTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\AI_Providers;
use Alley\WP\WP_SEO\AI_Settings;
use Alley\WP\WP_SEO\Feature;
use Alley\WP\WP_SEO\Features_Page;
use Alley\WP\WP_SEO\Registry;
use Alley\WP\WP_SEO\Sends_Prompts;
use Alley\WP\WP_SEO\Tests\TestCase;

use function Mantle\Support\Helpers\capture;

/**
 * Tests for the page that chooses the AI provider of each feature that sends
 * prompts.
 *
 * Each test puts the features it needs in the Registry. The setting is the one
 * the plugin registered on `init`, which is sanitized for the features in the
 * Registry when it is saved.
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

	/**
	 * Start each test with an empty Registry, an empty admin menu, and a user
	 * who can see the page.
	 */
	protected function setUp(): void {
		parent::setUp();

		Registry::reset_for_tests();

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
	 * Add a feature to the Registry, labeled "Test {$handle}".
	 *
	 * @param string $handle        Feature handle.
	 * @param bool   $active        Whether to enable and boot the feature.
	 * @param bool   $sends_prompts Whether the feature sends prompts.
	 */
	private function add_feature( string $handle, bool $active = true, bool $sends_prompts = true ): void {
		$origin = $sends_prompts
			? new class() implements Sends_Prompts {
				public function boot(): void {}
			}
			: new class() implements \Alley\WP\Types\Feature {
				public function boot(): void {}
			};

		$feature = Feature::top_level( $handle, "Test {$handle}", $origin );

		if ( $active ) {
			add_filter( "wp_seo_enable_{$handle}", '__return_true' );
			$feature->boot();
		}
	}

	/**
	 * Render the provider field for the description generator.
	 *
	 * @return string The rendered markup.
	 */
	private function render_field(): string {
		$page = new AI_Settings();

		return capture(
			fn () => $page->render_field(
				[
					'feature'   => 'description_generator',
					'label_for' => 'wp_seo_ai_provider_description_generator',
				]
			)
		);
	}

	/**
	 * The option is registered with WordPress.
	 */
	public function test_setting_is_registered() {
		$this->assertArrayHasKey( AI_Providers::OPTION, get_registered_settings() );
	}

	/**
	 * The option is registered only when a feature sends prompts.
	 */
	public function test_setting_is_registered_only_when_a_feature_sends_prompts() {
		unregister_setting( AI_Settings::SLUG, AI_Providers::OPTION );

		$this->add_feature( 'open_graph', sends_prompts: false );
		( new AI_Settings() )->register_setting();

		$this->assertArrayNotHasKey( AI_Providers::OPTION, get_registered_settings() );

		$this->add_feature( 'description_generator' );
		( new AI_Settings() )->register_setting();

		$this->assertArrayHasKey( AI_Providers::OPTION, get_registered_settings() );
	}

	/**
	 * Saving the option keeps providers only for features that send prompts,
	 * whether or not they are active.
	 */
	public function test_saving_keeps_providers_only_for_features_that_send_prompts() {
		$this->register_provider( 'wp_seo_active' );
		$this->add_feature( 'description_generator' );
		$this->add_feature( 'inactive_feature', active: false );
		$this->add_feature( 'open_graph', sends_prompts: false );

		update_option(
			AI_Providers::OPTION,
			[
				'description_generator' => 'wp_seo_active',
				'inactive_feature'      => 'wp_seo_active',
				'open_graph'            => 'wp_seo_active',
				'unknown_feature'       => 'wp_seo_active',
			]
		);

		$this->assertSame(
			[
				'description_generator' => 'wp_seo_active',
				'inactive_feature'      => 'wp_seo_active',
			],
			get_option( AI_Providers::OPTION )
		);
	}

	/**
	 * The page is added to the WP SEO menu only when a feature that sends
	 * prompts is active.
	 */
	public function test_page_is_added_only_when_a_feature_that_sends_prompts_is_active() {
		$this->add_feature( 'open_graph', sends_prompts: false );
		$this->add_feature( 'inactive_feature', active: false );

		$page = new AI_Settings();
		$page->add_page();

		$this->assertArrayNotHasKey( Features_Page::SLUG, $GLOBALS['submenu'] );

		$this->add_feature( 'description_generator' );
		$page->add_page();

		$this->assertSame( AI_Settings::SLUG, $GLOBALS['submenu'][ Features_Page::SLUG ][0][2] ?? null );
	}

	/**
	 * Loading the page adds a row, under its label, for each active feature
	 * that sends prompts.
	 */
	public function test_page_lists_a_row_per_active_feature_that_sends_prompts() {
		// Tests do not run in an admin request, which would have loaded this.
		require_once ABSPATH . 'wp-admin/includes/template.php';

		$this->add_feature( 'description_generator' );
		$this->add_feature( 'inactive_feature', active: false );
		$this->add_feature( 'open_graph', sends_prompts: false );

		$page = new AI_Settings();
		$page->add_page();

		do_action( 'load-' . get_plugin_page_hookname( AI_Settings::SLUG, Features_Page::SLUG ) );

		$html = capture( fn () => $page->render() );

		$this->assertStringContainsString( 'Test description_generator', $html );
		$this->assertStringContainsString( 'name="wp_seo_ai_providers[description_generator]"', $html );
		$this->assertStringNotContainsString( 'Test inactive_feature', $html );
		$this->assertStringNotContainsString( 'Test open_graph', $html );
	}

	/**
	 * A row is editable, and lists the available providers, unless its
	 * provider is set in code.
	 */
	public function test_field_is_editable_unless_set_in_code() {
		$this->register_provider( 'wp_seo_active' );

		$field = $this->render_field();

		$this->assertStringContainsString( 'value="wp_seo_active"', $field );
		$this->assertStringNotContainsString( 'disabled', $field );

		add_filter( 'wp_seo_ai_provider', fn () => 'wp_seo_active' );

		$field = $this->render_field();

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

		$field = $this->render_field();

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
