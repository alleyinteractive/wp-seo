<?php
/**
 * Custom block editor script registration and enqueue.
 *
 * This file will be copied to the assets output directory
 * with Webpack using wp-scripts build. The build command must
 * be run before this file will be available.
 *
 * This file must be included from the build output directory in a project.
 * and will be loaded from there.
 *
 * @package wp-seo
 */

/**
 * Registers all sidebar assets so that they can be enqueued in
 * the corresponding context.
 *
 * @return void
 */
function wp_seo_register_sidebar_scripts() {
	/**
	 * Asset file to automatically load dependencies and version.
	 *
	 * @var array{
	 *      dependencies: string[],
	 *      version: string,
	 * } $asset_file
	 * */
	$asset_file = include __DIR__ . '/index.asset.php';

	// Register the sidebar script.
	wp_register_script(
		'wp-seo-sidebar',
		plugins_url( 'index.js', __FILE__ ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);
	wp_set_script_translations( 'wp-seo-sidebar', 'wp-seo' );
}
add_action( 'init', 'wp_seo_register_sidebar_scripts' );

/**
 * Enqueue sidebar assets.
 */
function wp_seo_register_sidebar_assets() {
	wp_enqueue_script( 'wp-seo-sidebar' );
	wp_enqueue_style( 'wp-seo-sidebar', plugins_url( '../style-sidebar/index.css', __FILE__ ), [], '1.0.0' );
}
add_action( 'enqueue_block_editor_assets', 'wp_seo_register_sidebar_assets' );
