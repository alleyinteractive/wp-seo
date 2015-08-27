<?php
/**
 * Class file for WP_SEO_Formatting_Tag_Collection.
 *
 * @package WP_SEO
 */

/**
 * Stores available instances of {@see WP_SEO_Formatting_Tag}.
 */
class WP_SEO_Formatting_Tag_Collection extends WP_SEO_Singleton {

	/**
	 * Associative array of WP_SEO_Formatting_Tag IDs and instances.
	 *
	 * @var array
	 */
	private $items = array();

	/**
	 * @codeCoverageIgnore
	 */
	protected function setup() {
		/**
		 * Filter the available formatting tags when instantiating the collection.
		 *
		 * @see wp_seo_default_formatting_tags() for an example implementation.
		 *
		 * @param array $tags Associative array of WP_SEO_Formatting_Tag instances.
		 */
		$tags = apply_filters( 'wp_seo_formatting_tags', array() );
		array_walk( $tags, array( $this, 'add' ) );
	}

	/**
	 * Add a formatting tag to the collection.
	 *
	 * @param WP_SEO_Formatting_Tag $tag Formatting tag instance.
	 * @param string $id Unique reference ID to register the tag under.
	 */
	public function add( $tag, $id ) {
		if ( $tag instanceof WP_SEO_Formatting_Tag ) {
			$this->items[ $id ] = $tag;
		}
	}

	/**
	 * Get all formatting tag instances in the collection.
	 *
	 * @return array Associative array of WP_SEO_Formatting_Tag IDs and instances.
	 */
	public function get_all() {
		return $this->items;
	}

}
