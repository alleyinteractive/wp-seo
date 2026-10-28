<?php
/**
 * AI_Settings class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

/**
 * The admin page for choosing which AI provider WP SEO sends its prompts to.
 *
 * Providers are the AI connectors configured under Settings > Connectors.
 * `AI_Providers` gives the page its choices and sanitizes what is saved. When
 * the provider is set in code with the `wp_seo_ai_provider` filter, the choice
 * is shown read-only.
 *
 * This is infrastructure rather than a WP SEO feature: it is not wrapped in a
 * `Feature` and carries no handle, since it only configures features that are.
 * A feature that sends prompts registers it, so that the page is offered only
 * while such a feature is active.
 */
final class AI_Settings {
	/**
	 * Slug of the page, and the name of its settings group.
	 *
	 * @var string
	 */
	public const SLUG = 'wp-seo-ai';

	/**
	 * ID of the page's one settings section.
	 *
	 * @var string
	 */
	private const SECTION = 'wp_seo_ai_providers';

	/**
	 * Register the setting and put the page in the admin menu.
	 *
	 * The setting is registered on `init` rather than `admin_init`, so that it
	 * is known everywhere the option can be read or written, not only in the
	 * admin.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'register_setting' ] );
		add_action( 'admin_menu', [ $this, 'add_page' ] );
	}

	/**
	 * Register the option with WordPress.
	 */
	public function register_setting(): void {
		register_setting(
			self::SLUG,
			AI_Providers::OPTION,
			[
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => [ AI_Providers::class, 'sanitize_provider' ],
				'show_in_rest'      => false,
			]
		);
	}

	/**
	 * Add the page's section and its one field.
	 */
	public function add_fields(): void {
		add_settings_section( self::SECTION, '', [ $this, 'render_section' ], self::SLUG );

		add_settings_field(
			AI_Providers::OPTION,
			__( 'AI provider', 'wp-seo' ),
			[ $this, 'render_field' ],
			self::SLUG,
			self::SECTION,
			[ 'label_for' => AI_Providers::OPTION ]
		);
	}

	/**
	 * Add the page to the WP SEO menu.
	 *
	 * The fields are added only when the page loads, since only rendering the
	 * page needs them; saving through `options.php` needs only the setting.
	 */
	public function add_page(): void {
		$hook = add_submenu_page(
			Features_Page::SLUG,
			__( 'WP SEO AI Settings', 'wp-seo' ),
			__( 'AI', 'wp-seo' ),
			'manage_options',
			self::SLUG,
			[ $this, 'render' ]
		);

		if ( $hook ) {
			add_action( "load-{$hook}", [ $this, 'add_fields' ] );
		}
	}

	/**
	 * Render the page.
	 *
	 * WordPress prints the notice that settings were saved only on its own
	 * Settings screens, so the page prints it itself.
	 */
	public function render(): void {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP SEO AI Settings', 'wp-seo' ); ?></h1>

			<?php settings_errors(); ?>

			<form method="post" action="options.php">
				<?php
				settings_fields( self::SLUG );
				do_settings_sections( self::SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the section introduction: a pointer to where providers are set up,
	 * when there are none to choose from.
	 */
	public function render_section(): void {
		if ( AI_Providers::get_available_providers() ) {
			return;
		}
		?>
		<p>
			<?php
			printf(
				/* translators: %s: URL of the Connectors settings screen. */
				wp_kses( __( 'No AI providers are available. Set one up under <a href="%s">Settings &gt; Connectors</a>.', 'wp-seo' ), [ 'a' => [ 'href' => [] ] ] ),
				esc_url( admin_url( 'options-connectors.php' ) )
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the provider dropdown.
	 */
	public function render_field(): void {
		$choice = AI_Providers::get_choice();
		?>
		<select
			id="<?php echo esc_attr( AI_Providers::OPTION ); ?>"
			name="<?php echo esc_attr( AI_Providers::OPTION ); ?>"
			<?php disabled( $choice['in_code'] ); ?>
		>
			<option value=""><?php esc_html_e( 'None', 'wp-seo' ); ?></option>
			<?php foreach ( $choice['providers'] as $id => $name ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $choice['provider'], $id ); ?>>
					<?php echo esc_html( $name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php if ( $choice['in_code'] ) : ?>
			<p class="description">
				<?php
				printf(
					/* translators: %s: filter name. */
					esc_html__( 'Set in code with the %s filter.', 'wp-seo' ),
					'<code>wp_seo_ai_provider</code>'
				);
				?>
			</p>
		<?php endif; ?>
		<?php
	}
}
