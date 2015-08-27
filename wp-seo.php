<?php
/*
	Plugin Name: WP SEO
	Plugin URI: https://github.com/alleyinteractive/wp-seo
	Description: An SEO plugin that stays out of your way. Just the facts, Jack.
	Version: 0.9.0
	Author: Alley Interactive, Matthew Boynes, David Herrera
	Author URI: http://www.alleyinteractive.com/
*/
/*  This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if ( ! class_exists( 'WP_SEO' ) ) {

	define( 'WP_SEO_PATH', dirname( __FILE__ ) );
	define( 'WP_SEO_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );

	// Base singleton class.
	require_once ( WP_SEO_PATH . '/php/class-wp-seo-singleton.php' );

	// Extendable formatting-tag class.
	require_once ( WP_SEO_PATH . '/php/class-wp-seo-formatting-tag.php' );

	// Stores available formatting tag instances.
	require_once ( WP_SEO_PATH . '/php/class-wp-seo-formatting-tag-collection.php' );

	// Bundled formatting tags.
	require_once ( WP_SEO_PATH . '/php/default-formatting-tags.php' );

	// Formatting tag formatter.
	require_once ( WP_SEO_PATH . '/php/class-wp-seo-formatter.php' );

	// Settings page and option management.
	require_once ( WP_SEO_PATH . '/php/class-wp-seo-settings.php' );

	// Post meta boxes.
	require_once ( WP_SEO_PATH . '/php/class-wp-seo-post-meta-boxes.php' );

	// Term meta boxes.
	require_once ( WP_SEO_PATH . '/php/class-wp-seo-term-meta-boxes.php' );

	// Get WP SEO values for a query.
	require_once ( WP_SEO_PATH . '/php/class-wp-seo-query.php' );

	// Filter the page title and render meta tags.
	require_once ( WP_SEO_PATH . '/php/class-wp-seo.php' );

	// Common helpers and miscellaneous functions.
	require_once ( WP_SEO_PATH . '/php/functions.php' );

}
