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

define( 'WP_SEO_PATH', dirname( __FILE__ ) );
define( 'WP_SEO_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );

// Core filters for the page title and meta tags, and post and term metaboxes.
require_once WP_SEO_PATH . '/php/class-wp-seo.php';

// Settings page and option management.
require_once WP_SEO_PATH . '/php/class-wp-seo-settings.php';

// Extendable formatting-tag class.
require_once WP_SEO_PATH . '/php/class-wp-seo-formatting-tag.php';

// Included formatting tags.
require_once WP_SEO_PATH . '/php/default-formatting-tags.php';

function wp_seo_admin_scripts() {
	wp_enqueue_script( 'wp-seo-admin', WP_SEO_URL . 'js/wp-seo.js', array( 'jquery', 'underscore' ), '0.9.0', true );
	wp_localize_script( 'wp-seo-admin', 'wp_seo_admin', array(
		'repeatable_add_more_label' => __( 'Add another', 'wp-seo' ),
		'repeatable_remove_label' => __( 'Remove group', 'wp-seo' ),
	) );

	wp_enqueue_style( 'wp-seo-admin', WP_SEO_URL . 'css/wp-seo.css', array(), '0.9.0' );
}
add_action( 'admin_enqueue_scripts', 'wp_seo_admin_scripts' );