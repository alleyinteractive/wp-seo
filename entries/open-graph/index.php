<?php
/**
 * open-graph slotfill script registration and enqueue.
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
 * Registers all open-graph slotfill assets so that they can be enqueued in
 * the corresponding context.
 */
function wp_seo_register_open_graph_scripts() {
	// Automatically load dependencies and version.
	$asset_file = include __DIR__ . '/index.asset.php';

	// Register the open-graph script.
	wp_register_script(
		'wp-seo-open-graph-js',
		plugins_url( 'index.js', __FILE__ ),
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);
	wp_set_script_translations( 'wp-seo-open-graph-js', 'wp-seo' );
}
add_action( 'init', 'wp_seo_register_open_graph_scripts' );

/**
 * Enqueue block editor assets for the open-graph slotfill.
 *
 * This function is called by the enqueue_block_editor_assets hook. Use it to
 * enqueue assets that are loaded in the block editor.
 */
function wp_seo_enqueue_open_graph_assets() {
	wp_enqueue_script( 'wp-seo-open-graph-js' );
}
add_action( 'enqueue_block_editor_assets', 'wp_seo_enqueue_open_graph_assets' );
