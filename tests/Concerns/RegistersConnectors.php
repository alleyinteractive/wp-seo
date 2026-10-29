<?php
/**
 * RegistersConnectors trait file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Tests\Concerns;

/**
 * Registers connectors for the length of a test.
 *
 * A test case that uses this calls `unregister_connectors()` in `tearDown()`.
 */
trait RegistersConnectors {
	/**
	 * Connectors registered by a test, removed again after it.
	 *
	 * @var string[]
	 */
	private array $connectors = [];

	/**
	 * Register a connector for the length of a test.
	 *
	 * @param string $id        Connector ID.
	 * @param string $type      Connector type. Default `ai_provider`.
	 * @param bool   $is_active Whether the connector's plugin is active. Default true.
	 */
	private function register_connector( string $id, string $type = 'ai_provider', bool $is_active = true ): void {
		\WP_Connector_Registry::get_instance()->register(
			$id,
			[
				'name'           => "Test {$id}",
				'type'           => $type,
				'authentication' => [ 'method' => 'none' ],
				'plugin'         => [ 'is_active' => $is_active ? '__return_true' : '__return_false' ],
			]
		);

		$this->connectors[] = $id;
	}

	/**
	 * Remove the connectors the test registered.
	 */
	private function unregister_connectors(): void {
		foreach ( $this->connectors as $id ) {
			\WP_Connector_Registry::get_instance()->unregister( $id );
		}

		$this->connectors = [];
	}
}
