<?php
/**
 * AI_Settings class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

/**
 * The admin page for choosing which AI provider each WP SEO feature sends its
 * prompts to.
 *
 * Providers are the AI connectors configured under Settings > Connectors. The
 * page lists one row per feature it was given, and stores the choices in one
 * option keyed by feature handle. A row whose provider is set in code with the
 * `wp_seo_ai_provider` filter is shown read-only.
 *
 * This is not a `Feature` and carries no handle: it only configures features
 * that are, and does nothing when it was given none.
 */
final class AI_Settings implements \Alley\WP\Types\Feature {
	/**
	 * Slug of the page, and the name of its settings group.
	 *
	 * @var string
	 */
	public const SLUG = 'wp-seo-ai';

	/**
	 * Name of the option holding each feature's provider.
	 *
	 * @var string
	 */
	public const OPTION = 'wp_seo_ai_providers';

	/**
	 * The capability required to view the page and save its settings.
	 *
	 * @var string
	 */
	private const CAPABILITY = 'manage_options';

	/**
	 * ID of the page's one settings section.
	 *
	 * @var string
	 */
	private const SECTION = 'wp_seo_ai_providers';

	/**
	 * Set up.
	 *
	 * @param array<string, string> $features Labels of the features that send prompts, keyed by handle.
	 */
	public function __construct(
		private readonly array $features,
	) {}

	/**
	 * Boot the feature.
	 *
	 * The setting is registered on `init` rather than `admin_init`, so that it
	 * is known everywhere the option can be read or written, not only in the
	 * admin.
	 */
	public function boot(): void {
		if ( ! $this->features ) {
			return;
		}

		add_action( 'init', [ $this, 'register_setting' ] );
		add_action( 'admin_menu', [ $this, 'add_page' ] );
	}

	/**
	 * Register the option with WordPress.
	 */
	public function register_setting(): void {
		register_setting(
			self::SLUG,
			self::OPTION,
			[
				'type'              => 'object',
				'default'           => [],
				'sanitize_callback' => [ $this, 'sanitize' ],
				'show_in_rest'      => false,
			]
		);
	}

	/**
	 * Add the page's section and one field per feature.
	 */
	public function add_fields(): void {
		add_settings_section( self::SECTION, '', [ $this, 'render_section' ], self::SLUG );

		foreach ( $this->features as $feature => $label ) {
			add_settings_field(
				"wp_seo_ai_provider_{$feature}",
				$label,
				[ $this, 'render_field' ],
				self::SLUG,
				self::SECTION,
				[
					'feature'   => $feature,
					'label_for' => "wp_seo_ai_provider_{$feature}",
				]
			);
		}
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
			self::CAPABILITY,
			self::SLUG,
			[ $this, 'render' ]
		);

		if ( $hook ) {
			add_action( "load-{$hook}", [ $this, 'add_fields' ] );
		}
	}

	/**
	 * Sanitize the option before it is saved.
	 *
	 * Keeps a provider only for a feature the page lists and only if it is an
	 * available AI provider. A feature missing from the input keeps the provider
	 * already stored for it: a row set in code renders a disabled field, and
	 * browsers do not submit disabled fields.
	 *
	 * @param mixed $value Option value to save.
	 * @return array<string, string> Connector IDs keyed by feature handle.
	 */
	public function sanitize( mixed $value ): array {
		$value     = is_array( $value ) ? $value : [];
		$stored    = get_option( self::OPTION );
		$stored    = is_array( $stored ) ? $stored : [];
		$providers = ai_providers();
		$sanitized = [];

		foreach ( array_keys( $this->features ) as $feature ) {
			$provider = $value[ $feature ] ?? $stored[ $feature ] ?? '';

			if ( is_string( $provider ) && isset( $providers[ $provider ] ) ) {
				$sanitized[ $feature ] = $provider;
			}
		}

		return $sanitized;
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
		if ( ai_providers() ) {
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
	 * Render the provider dropdown for one feature.
	 *
	 * @param array{feature: string, label_for: string} $args Field arguments.
	 */
	public function render_field( array $args ): void {
		$in_code  = null !== get_ai_provider_from_code( $args['feature'] );
		$selected = get_ai_provider( $args['feature'] );
		?>
		<select
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			name="<?php echo esc_attr( self::OPTION . '[' . $args['feature'] . ']' ); ?>"
			<?php disabled( $in_code ); ?>
		>
			<option value=""><?php esc_html_e( 'None', 'wp-seo' ); ?></option>
			<?php foreach ( ai_providers() as $id => $name ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $selected, $id ); ?>>
					<?php echo esc_html( $name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php if ( $in_code ) : ?>
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
