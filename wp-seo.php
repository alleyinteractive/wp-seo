<?php
/**
 * Plugin Name: WP SEO
 * Plugin URI: https://github.com/alleyinteractive/wp-seo
 * Description: Enterprise SEO for large, performant sites
 * Version: 2.0.0
 * Author: Alley Interactive
 * Author URI: https://github.com/alleyinteractive/wp-seo
 * Requires at least: 5.9
 * Requires PHP: 8.2
 * Tested up to: 6.7
 *
 * Text Domain: wp-seo
 * Domain Path: /languages/
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Root directory to this plugin.
 */
define( 'WP_SEO_DIR', __DIR__ );

// Check if Composer is installed (remove if Composer is not required for your plugin).
if ( ! file_exists( __DIR__ . '/vendor/wordpress-autoload.php' ) ) {
	// Will also check for the presence of an already loaded Composer autoloader
	// to see if the Composer dependencies have been installed in a parent
	// folder. This is useful for when the plugin is loaded as a Composer
	// dependency in a larger project.
	if ( ! class_exists( \Composer\InstalledVersions::class ) ) {
		\add_action(
			'admin_notices',
			function () {
				?>
				<div class="notice notice-error">
					<p><?php esc_html_e( 'Composer is not installed and wp-seo cannot load. Try using a `*-built` branch if the plugin is being loaded as a submodule.', 'wp-seo' ); ?></p>
				</div>
				<?php
			}
		);

		return;
	}
} else {
	// Load Composer dependencies.
	require_once __DIR__ . '/vendor/wordpress-autoload.php';
}

// Load the plugin's main files.
require_once __DIR__ . '/src/assets.php';
require_once __DIR__ . '/src/meta.php';
require_once __DIR__ . '/src/main.php';

load_scripts();
register_post_meta_from_defs();
main();

/**
 * Start Legacy Code
 *
 * This is legacy code that should be modified to use the new plugin structure.
 * It is included here to ensure that the plugin continues to work as expected.
 */
define( 'WP_SEO_PATH', __DIR__ );
define( 'WP_SEO_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );

// Behind-the-scenes functions.
require_once WP_SEO_PATH . '/php/internal-functions.php';

// Core filters for the page title and meta tags, and post and term metaboxes.
require_once WP_SEO_PATH . '/php/class-wp-seo.php';

// Settings page and option management.
require_once WP_SEO_PATH . '/php/class-wp-seo-settings.php';

// Extendable formatting-tag class.
require_once WP_SEO_PATH . '/php/class-wp-seo-formatting-tag.php';

// Included formatting tags.
require_once WP_SEO_PATH . '/php/default-formatting-tags.php';

// General functions.
require_once WP_SEO_PATH . '/php/general-functions.php';

// Formatting functions.
require_once WP_SEO_PATH . '/php/formatting-functions.php';

// The plugin's default filters.
require_once WP_SEO_PATH . '/php/default-filters.php';

/**
 * Enqueues scripts and styles for administration pages.
 */
function wp_seo_admin_scripts(): void {
	wp_enqueue_script( 'wp-seo-admin', WP_SEO_URL . 'js/wp-seo.js', [ 'jquery', 'underscore' ], '0.11.1', true );
	wp_localize_script( 'wp-seo-admin', 'wp_seo_admin', [
		'repeatable_add_more_label' => __( 'Add another', 'wp-seo' ),
		'repeatable_remove_label'   => __( 'Remove group', 'wp-seo' ),
		/**
		 * Filter the fields that support character counts.
		 *
		 * @param array $fields Fields that support character counters.
		 */
		'character_count_fields'    => (array) apply_filters( 'wp_seo_character_count_fields', [ 'title', 'description' ] ),
	] );

	wp_enqueue_style( 'wp-seo-admin', WP_SEO_URL . 'css/wp-seo.css', [], '2.0.0' );
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\wp_seo_admin_scripts' );
/* End Legacy Code */
