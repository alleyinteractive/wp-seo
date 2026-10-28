<?php
/**
 * Sends_Prompts interface file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

/**
 * A WP SEO feature that sends prompts to an AI provider.
 *
 * A feature in the Registry that implements this gets a row on the WP SEO AI
 * settings page, under its label, for choosing its provider.
 */
interface Sends_Prompts extends \Alley\WP\Types\Feature {}
