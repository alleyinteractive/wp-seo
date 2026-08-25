<?php
/**
 * The main plugin function
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

use Alley\WP\Features\Group;

/**
 * Instantiate the plugin.
 */
function main(): void {
	/*
	 * The page that reports on the features is not one of them: it is registered
	 * here rather than in the group below, so that no enablement filter can take
	 * away the site's only view of what the plugin is doing. `admin_menu` fires
	 * long after this, so registering it from here is early enough.
	 */
	( new Features_Page() )->register();

	// Add features here.
	$plugin = new Group(
		Feature::top_level( 'open_graph', __( 'Open Graph', 'wp-seo' ), new Features\Open_Graph() ),
		new Features\Twitter_Card(),
	);

	/**
	 * Fires while WP SEO composes its features, before any of them boot.
	 *
	 * Include a feature in the given group to have WP SEO boot it alongside its
	 * own, without editing the plugin. Wrap it in a `Feature` first to give it a
	 * handle and put it in the Registry.
	 *
	 * WP SEO composes on `after_setup_theme`, so the enablement filters named
	 * for a handle can still be added from a theme.
	 *
	 * @since 2.0.0
	 *
	 * @param \Alley\WP\Types\Features $plugin The features WP SEO is about to boot.
	 */
	do_action( 'wp_seo_register_features', $plugin );

	$plugin->boot();
}
