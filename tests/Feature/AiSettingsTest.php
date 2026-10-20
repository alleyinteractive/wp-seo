<?php
/**
 * AiSettingsTest class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\AI_Settings;
use Alley\WP\WP_SEO\Feature;
use Alley\WP\WP_SEO\Features_Page;
use Alley\WP\WP_SEO\Registry;
use Alley\WP\WP_SEO\Tests\TestCase;

use function Alley\WP\WP_SEO\get_ai_provider;
use function Alley\WP\WP_SEO\get_ai_providers;
use function Mantle\Support\Helpers\capture;

/**
 * Tests for the page that chooses the AI provider of each feature that sends
 * prompts.
 *
 * The plugin boots the page with a `description_generator` row, so these tests
 * use that row and the setting the plugin registered for it.
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

		delete_option( AI_Settings::OPTION );

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
	 * Make a feature with the given handle active, in place of whatever the
	 * Registry held.
	 *
	 * @param string $handle Feature handle.
	 */
	private function activate_feature( string $handle ): void {
		Registry::reset_for_tests();

		$feature = Feature::top_level(
			$handle,
			"Test {$handle}",
			new class() implements \Alley\WP\Types\Feature {
				public function boot(): void {}
			}
		);

		add_filter( "wp_seo_enable_{$handle}", '__return_true' );

		$feature->boot();
	}

	/**
	 * Render the plugin's provider field for the description generator.
	 *
	 * @return string The rendered markup.
	 */
	private function render_field(): string {
		$page = new AI_Settings( [ 'description_generator' => 'Meta Description Generator' ] );

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
		$this->assertArrayHasKey( AI_Settings::OPTION, get_registered_settings() );
	}

	/**
	 * Saving keeps only the features the page lists, and only providers that
	 * are available.
	 */
	public function test_saving_drops_unknown_features_and_providers() {
		$this->register_provider( 'wp_seo_active' );

		update_option(
			AI_Settings::OPTION,
			[
				'description_generator' => 'wp_seo_active',
				'unknown_feature'       => 'wp_seo_active',
			]
		);

		$this->assertSame( [ 'description_generator' => 'wp_seo_active' ], get_option( AI_Settings::OPTION ) );

		update_option( AI_Settings::OPTION, [ 'description_generator' => 'not_a_provider' ] );

		$this->assertSame( [], get_option( AI_Settings::OPTION ) );
	}

	/**
	 * A feature missing from what is saved keeps its stored provider, since a
	 * row set in code renders a disabled field that the browser does not
	 * submit.
	 */
	public function test_saving_without_a_feature_keeps_its_stored_provider() {
		$this->register_provider( 'wp_seo_active' );

		update_option( AI_Settings::OPTION, [ 'description_generator' => 'wp_seo_active' ] );
		update_option( AI_Settings::OPTION, [] );

		$this->assertSame( [ 'description_generator' => 'wp_seo_active' ], get_option( AI_Settings::OPTION ) );
	}

	/**
	 * Saving "None" for a feature clears its stored provider, unlike leaving
	 * the feature out of what is saved.
	 */
	public function test_saving_none_clears_the_stored_provider() {
		$this->register_provider( 'wp_seo_active' );

		update_option( AI_Settings::OPTION, [ 'description_generator' => 'wp_seo_active' ] );
		update_option( AI_Settings::OPTION, [ 'description_generator' => '' ] );

		$this->assertSame( [], get_option( AI_Settings::OPTION ) );
	}

	/**
	 * A provider set in code wins over the stored one, the stored one is used
	 * otherwise, and a feature with neither has no provider.
	 */
	public function test_get_ai_provider_prefers_code_then_setting() {
		$this->assertSame( '', get_ai_provider( 'description_generator' ) );

		$this->register_provider( 'wp_seo_active' );
		update_option( AI_Settings::OPTION, [ 'description_generator' => 'wp_seo_active' ] );

		$this->assertSame( 'wp_seo_active', get_ai_provider( 'description_generator' ) );

		add_filter( 'wp_seo_ai_provider', fn () => '' );

		$this->assertSame( '', get_ai_provider( 'description_generator' ), 'An empty string set in code means no provider.' );
	}

	/**
	 * The page is added to the WP SEO menu only when it has features to list.
	 */
	public function test_page_is_added_only_when_it_has_features() {
		$this->activate_feature( 'description_generator' );

		$empty = new AI_Settings( [] );
		$empty->boot();

		$this->assertFalse( has_action( 'admin_menu', [ $empty, 'add_page' ] ) );

		$page = new AI_Settings( [ 'description_generator' => 'Meta Description Generator' ] );
		$page->boot();
		$page->add_page();

		$this->assertSame( AI_Settings::SLUG, $GLOBALS['submenu'][ Features_Page::SLUG ][0][2] ?? null );
	}

	/**
	 * The page is not added when none of its features is active, since none
	 * of them sends prompts.
	 */
	public function test_page_is_not_added_without_active_features() {
		$this->activate_feature( 'some_other_feature' );

		$page = new AI_Settings( [ 'description_generator' => 'Meta Description Generator' ] );
		$page->add_page();

		$this->assertArrayNotHasKey( Features_Page::SLUG, $GLOBALS['submenu'] );
	}

	/**
	 * Loading the page adds a row for each of its active features.
	 */
	public function test_page_lists_a_row_per_active_feature() {
		// Tests do not run in an admin request, which would have loaded this.
		require_once ABSPATH . 'wp-admin/includes/template.php';

		$this->activate_feature( 'description_generator' );

		$page = new AI_Settings(
			[
				'description_generator' => 'Meta Description Generator',
				'inactive_feature'      => 'Inactive Feature',
			]
		);
		$page->add_page();

		do_action( 'load-' . get_plugin_page_hookname( AI_Settings::SLUG, Features_Page::SLUG ) );

		$html = capture( fn () => $page->render() );

		$this->assertStringContainsString( 'Meta Description Generator', $html );
		$this->assertStringContainsString( 'name="wp_seo_ai_providers[description_generator]"', $html );
		$this->assertStringNotContainsString( 'Inactive Feature', $html );
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
		$page = new AI_Settings( [ 'description_generator' => 'Meta Description Generator' ] );

		$this->assertSame( [], get_ai_providers(), 'No AI provider plugin is active in the test environment.' );
		$this->assertStringContainsString( 'options-connectors.php', capture( fn () => $page->render_section() ) );

		$this->register_provider( 'wp_seo_active' );

		$this->assertSame( '', capture( fn () => $page->render_section() ) );
	}
}
