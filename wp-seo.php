<?php

/*
	Plugin Name: Alley SEO
	Plugin URI: http://www.alleyinteractive.com/
	Description: A simple, straightforward SEO plugin. Just the facts, Jack.
	Version: 0.1
	Author: Matthew Boynes, Alley Interactive
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

require_once WP_SEO_PATH . '/php/class-wp-seo.php';
require_once WP_SEO_PATH . '/php/class-wp-seo-settings.php';

function wp_seo_scripts() {
	wp_enqueue_script( 'wp-seo-admin', WP_SEO_URL . 'js/wp-seo.js', array( 'jquery' ), '1.0' );
}
add_action( 'admin_enqueue_scripts', 'wp_seo_scripts' );