<?php
/**
 * Class file for WP_SEO_Singleton.
 *
 * @package WP_SEO
 */

/**
 * Base singleton class.
 */
abstract class WP_SEO_Singleton {

	/**
	 * Instances of child classes.
	 *
	 * @var array
	 */
	private static $instances = array();

	/**
	 * @codeCoverageIgnore
	 */
	protected function __construct() {
		/* Initialization typically happens via instance() method. */
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function __clone() {
		wp_die( sprintf( __( "Please don't __clone %s", "wp-seo" ), get_called_class() ) );
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function __wakeup() {
		wp_die( sprintf( __( "Please don't __wakeup %s", "wp-seo" ), get_called_class() ) );
	}

	/**
	 * Return an instance of a child class.
	 *
	 * @return WP_SEO_Singleton
	 */
	public static function instance() {
		$class = get_called_class();

		if ( ! isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new static;
			self::$instances[ $class ]->setup();
		}

		return self::$instances[ $class ];
	}

	/**
	 * Initialize properties, add actions and filters.
	 *
	 * @codeCoverageIgnore
	 */
	protected function setup() {}

}
