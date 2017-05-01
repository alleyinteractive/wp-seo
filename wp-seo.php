<?php
/**
 * Plugin Name: WP SEO
 * Plugin URI: https://github.com/alleyinteractive/wp-seo
 * Description: An SEO plugin that stays out of your way. Just the facts, Jack.
 * Version: 0.13.0
 * Author: Alley Interactive, Matthew Boynes, David Herrera
 * Author URI: https://www.alleyinteractive.com/
 *
 * @package WP_SEO
 */

/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

define( 'WP_SEO_PATH', dirname( __FILE__ ) );
define( 'WP_SEO_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );

/**
 * Current plugin version.
 *
 * @since 0.13.0
 *
 * @var string $version
 */
define( 'WP_SEO_VERSION', '0.13.0' );

// Behind-the-scenes functions.
require_once WP_SEO_PATH . '/php/internal-functions.php';

// Core filters for the page title and meta tags, and post and term metaboxes.
require_once WP_SEO_PATH . '/php/class-wp-seo.php';

// WP SEO fields.
require_once WP_SEO_PATH . '/php/class-wp-seo-fields.php';

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

add_action( 'init', function() {
	wp_seo()->register_meta( '_meta_title', array(
		'sanitize_callback' => 'sanitize_text_field',
	) );

	wp_seo()->register_meta( '_meta_description', array(
		'sanitize_callback' => 'sanitize_text_field',
	) );

	wp_seo()->register_meta( '_meta_keywords', array(
		'sanitize_callback' => 'sanitize_text_field',
	) );

	wp_seo()->register_meta( '_meta_og_title', array(
		'sanitize_callback' => 'sanitize_text_field',
	) );

} );
