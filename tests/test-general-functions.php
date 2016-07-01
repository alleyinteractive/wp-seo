<?php
/**
 * Tests for general-functions.php.
 *
 * @package WP_SEO
 */

class WP_SEO_General_Functions_Tests extends WP_UnitTestCase {
	/**
	 * Test wp_seo_intersect_args() with args combinations.
	 *
	 * @dataProvider data_intersect_args
	 */
	function test_intersect_args( $args, $defaults, $expected, $message ) {
		$this->assertSame(
			$expected,
			wp_seo_intersect_args( $args, $defaults ),
			$message
		);
	}

	/**
	 * @return array {
	 *     @type array $args User args.
	 *     @type array $defaults Default args.
	 *     @type array $expected Expected intersect result.
	 *     @type array $message Failure message.
	 * }
	 */
	function data_intersect_args() {
		$key_1 = rand_str();
		$key_2 = rand_str();

		$val_1 = rand_str();
		$val_2 = rand_str();
		$val_3 = rand_str();
		$val_4 = rand_str();

		return array(
			array(
				array(),
				array( $key_1 => $val_1, $key_2 => $val_2 ),
				array( $key_1 => $val_1, $key_2 => $val_2 ),
				'Should return $defaults if no $args are passed'
			),
			array(
				array( $key_1 => $val_3 ),
				array( $key_1 => $val_1, $key_2 => $val_2 ),
				array( $key_1 => $val_3, $key_2 => $val_2 ),
				'Should return any passed $args values whose keys are in $defaults'
			),
			array(
				array( $key_1 => $val_3, $key_2 => $val_4 ),
				array( $key_1 => $val_1, $key_2 => $val_2 ),
				array( $key_1 => $val_3, $key_2 => $val_4 ),
				'Should return all passed $args values whose keys are in $defaults'
			),
			array(
				array( $key_1 => $val_1 ),
				array( $key_2 => $val_2 ),
				array( $key_2 => $val_2 ),
				'Should reject passed $args whose keys are not in defaults'
			),
		);
	}
}
