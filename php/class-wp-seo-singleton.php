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
