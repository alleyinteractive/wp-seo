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

require_once __DIR__ . '/class-wp-seo.php';

class WP_SEO_Settings {

	public $options_capability = 'manage_options';
	public $default_options = array( 'post_types' => array() );
	public $options = array();

	const SLUG = 'wp-seo';

	protected static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new WP_SEO_Settings;
			self::$instance->setup_actions();
		}
		return self::$instance;
	}

	protected function __construct() {
		/** Don't do anything **/
	}

	protected function setup_actions() {
		// add_action( 'wp_head', array( self::$instance, 'action_wp_head' ) );

		add_action( 'after_setup_theme', array( self::$instance, 'load_options' ), 5 );
		add_action( 'admin_init', array( self::$instance, 'action_admin_init' ) );

		add_action( 'admin_menu', array( self::$instance, 'action_admin_menu' ) );
	}

	/**
	 * Load the options on demand
	 *
	 * @return void
	 */
	public function load_options() {
		if ( !$this->options )
			$this->options = get_option( self::SLUG, $this->default_options );
	}

	public function action_admin_init() {
		register_setting( self::SLUG, self::SLUG, array( self::$instance, 'sanitize_options' ) );
		add_settings_section( 'general', false, '__return_false', self::SLUG );
		add_settings_field( 'post_types', __( 'Add meta fields to:', self::SLUG ), array( self::$instance, 'field' ), self::SLUG, 'general' );
	}

	public function action_admin_menu() {
		add_options_page( __( 'WP SEO Settings', self::SLUG ), __( 'SEO', self::SLUG ), $this->options_capability, self::SLUG, array( self::$instance, 'view_settings_page' ) );
	}

	public function field() {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_types as $slug => $post_type ) :
			?>
			<label><input type="checkbox" name="<?php echo self::SLUG ?>[post_types][]" value="<?php echo $slug ?>"<?php checked( in_array( $slug, $this->options['post_types'] ) ) ?> /> <?php echo $post_type->label ?></label><br />
			<?php
		endforeach;
	}

	public function sanitize_options( $in ) {

		$out = $this->default_options;

		// Validate post_types
		$out['post_types'] = $in['post_types'];

		return $out;
	}

	public function view_settings_page() {
	?>
	<div class="wrap">
		<h2><?php _e( 'WP SEO', self::SLUG ); ?></h2>
		<p><?php _e( 'SEO Settings for SEO Professionals', self::SLUG ); ?></p>
		<form action="options.php" method="POST">
			<?php settings_fields( self::SLUG ); ?>
			<?php do_settings_sections( self::SLUG ); ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
	}

	/**
	 * Basic styling will go here
	 */
	public function action_wp_head() {
		?>
		<style type="text/css">
		</style>
		<?php
	}

}

function WP_SEO_Settings() {
	return WP_SEO_Settings::instance();
}
add_action( 'plugins_loaded', 'WP_SEO_Settings' );


?>