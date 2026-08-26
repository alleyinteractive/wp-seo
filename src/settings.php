<?php
/**
 * Contains functions for working with WP SEO settings.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

/**
 * Get the WP SEO settings instance.
 *
 * Centralizes access to the settings singleton so individual features don't
 * each need their own copy of the same lazily-initialized property/getter.
 *
 * Named get_wp_seo_settings() rather than get_settings() to avoid colliding
 * (at least by name, if not by namespace) with WordPress core's own
 * long-deprecated get_settings() function.
 *
 * @return \WP_SEO_Settings
 */
function get_wp_seo_settings(): \WP_SEO_Settings {
	return \WP_SEO_Settings::instance();
}
