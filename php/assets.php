<?php
/**
 * Manage static assets.
 *
 * @package WP_SEO
 */

/**
 * Enqueue scripts for all admin pages.
 *
 * @param string $hook_suffix The current admin page.
 */
function wp_seo_admin_enqueue_scripts( $hook_suffix ) {
	wp_enqueue_script( 'wp-seo-admin', WP_SEO_URL . 'js/admin.js', array( 'jquery', 'underscore' ), '1.0.0', true );
	wp_localize_script( 'wp-seo-admin', 'wpSeo', array(
		'l10n' => array(
			'addAnother' => __( 'Add another', 'wp-seo' ),
			'remove' => __( 'Remove', 'wp-seo' ),
		),
	) );

	wp_enqueue_style( 'wp-seo-admin', WP_SEO_URL . 'css/admin.css', array(), '1.0.0' );
}
add_action( 'admin_enqueue_scripts', 'wp_seo_admin_enqueue_scripts' );
