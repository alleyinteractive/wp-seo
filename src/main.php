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
	// Add features here.
	$plugin = new Group(
		new Features\Open_Graph(),
	);

	$plugin->boot();
}
