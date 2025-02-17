<?php
/**
 * WP SEO Tests: Tests for general-functions.php.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Feature;

use Alley\WP\WP_SEO\Tests\TestCase;

class GeneralFunctionsTest extends TestCase {
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
	static function data_intersect_args() {
		$key_1 = rand_str();
		$key_2 = rand_str();

		$val_1 = rand_str();
		$val_2 = rand_str();
		$val_3 = rand_str();
		$val_4 = rand_str();

		return [
			[
				[],
				[ $key_1 => $val_1, $key_2 => $val_2 ],
				[ $key_1 => $val_1, $key_2 => $val_2 ],
				'Should return $defaults if no $args are passed'
			],
			[
				[ $key_1 => $val_3 ],
				[ $key_1 => $val_1, $key_2 => $val_2 ],
				[ $key_1 => $val_3, $key_2 => $val_2 ],
				'Should return any passed $args values whose keys are in $defaults'
			],
			[
				[ $key_1 => $val_3, $key_2 => $val_4 ],
				[ $key_1 => $val_1, $key_2 => $val_2 ],
				[ $key_1 => $val_3, $key_2 => $val_4 ],
				'Should return all passed $args values whose keys are in $defaults'
			],
			[
				[ $key_1 => $val_1 ],
				[ $key_2 => $val_2 ],
				[ $key_2 => $val_2 ],
				'Should reject passed $args whose keys are not in defaults'
			],
		];
	}
}
