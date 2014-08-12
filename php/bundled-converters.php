<?php

class WP_SEO_Add_Meta_Tags_Converter extends WP_SEO_Converter {

	public $label = 'Add Meta Tags';

	public function can_convert() {
		if ( ! get_option( 'add_meta_tags_opts' ) ) {
			return new WP_Error( 'can_convert', __( 'No data to convert from in the database', 'wp-seo' ) );
		}

		return true;
	}

	public function get_tag_map() {
		return array(
			'%cats%' => '#categories#',
			'%tags%' => '#tags',
		);
	}

	public function has_tag( $string ) {
		return false !== strpos( $string, '%' );
	}

	public function get_static_fields() {
		$data = get_option( 'add_meta_tags_opts' );
		$out = array();

		$map = array(
			'site_description' => 'home_description',
			'site_keywords'    => 'home_keywords',
		);

		foreach ( $map as $key => $value ) {
			if ( ! empty( $data[ $key ] ) ) {
				$out[ $map[ $key ] ] = $data[ $key ];
			}
		}

		return $out;
	}

	// No support for what we would call arbitrary tags.
	public function get_arbitrary_tags() {
		return null;
	}

	/**
	 * Add Meta Tags automatically adds meta boxes to Posts and Pages, but it
	 * allows users to disable each individual SEO field in the box. Assume both
	 * should be enabled in WP SEO unless all of their fields are disabled.
	 *
	 * Meta boxes are added to custom post types with a yes-or-no checkbox.
	 */
	public function get_enabled_post_types( $single_post_types ) {
		$enabled = array();

		$settings = get_option( 'add_meta_tags_opts' );

		if ( ! empty( $settings['custom_post_types'] ) ) {
			// Old values are saved as $post_type => $bool.
			$enabled = array_keys( $settings['custom_post_types'] );
		}

		if ( ! empty( $settings['post_options'] ) ) {
			$enabled[] = 'post';
		}

		if ( ! empty( $settings['page_options'] ) ) {
			$enabled[] = 'page';
		}

		return $enabled;
	}

	// No support for per-term fields.
	public function get_enabled_taxonomies( $taxonomies ) {
		return array();
	}

}

class WP_SEO_AIOSP_Converter extends WP_SEO_Converter {

	public $label = 'All In One SEO Pack';

	public function can_convert() {
		if ( ! get_option( 'aioseop_options' ) ) {
			return new WP_Error( 'can_convert', __( 'No data to convert from in the database', 'wp-seo' ) );
		}

		return true;
	}

	public function get_tag_map() {
		return array(
			'%blog_title%' => '#site_name#',
			'%blog_description%' => '#site_description#',
			'%page_title%' => '#page_title#',
			'%post_title%' => '#post_title#',
			'%page_author_nicename%' => '#author#',
			'%post_author_nicename%' => '#author#',
			'%author%' => '#author#',
			'%category_title%' => '#term_name#',
			'%tag%' => '#term_name#',
			'%category_description%' => '#term_description#',
			'%search%' => '#search_term#',
		);
	}

	public function has_tag( $string ) {
		return false !== strpos( $string, '%' );
	}

	public function get_static_fields() {
		$data = get_option( 'aioseop_options' );
		$out = array();

		$map = array(
			'aiosp_category_title_format' => 'archive_category_title',
			'aiosp_tag_title_format'      => 'archive_post_tag_title',
		);
		foreach ( WP_SEO_Settings()->get_single_post_types() as $name => $object ) {
			$map[ "aiosp_{$name}_title_format" ] = "single_{$name}_title";
		}
		$map = array_merge( $map, array(
			'aiosp_home_title'       => 'home_title',
			'aiosp_home_description' => 'home_description',
			'aiosp_home_keywords'    => 'home_keywords',
		) );

		foreach ( $map as $key => $value ) {
			if ( ! empty( $data[ $key ] ) ) {
				$out[ $map[ $key ] ] = $data[ $key ];
			}
		}

		return $out;
	}

	public function get_arbitrary_tags() {
		$data = get_option( 'aioseop_options' );
		$fields = array();

		foreach ( array(
			'aiosp_google_verify'    => 'google-site-verification',
			'aiosp_bing_verify'      => 'msvalidate.01',
			'aiosp_pinterest_verify' => 'p:domain_verify',
		) as $key => $name ) {
			if ( ! empty( $data[ $key ] ) ) {
				$fields[] = array( 'name' => $name, 'content' => $data[ $key ] );
			}
		}

		return $fields;
	}

	public function get_enabled_post_types( $single_post_types ) {
		$option = get_option( 'aioseop_options' );
		return $option['aiosp_cpostactive'];
	}

	public function get_enabled_taxonomies( $taxonomies ) {
		return array();
	}
}

class WP_SEO_Yoast_Converter extends WP_SEO_Converter {

	public $label = 'Yoast WordPress SEO';

	public function can_convert() {
		if ( ! get_option( 'wpseo_titles' ) || ! get_option( 'wpseo' ) ) {
			return new WP_Error( 'can_convert', __( 'No data to convert from in the database', 'wp-seo' ) );
		}

		return true;
	}

	public function get_tag_map() {
		return array(
			'%%sitename%%'             => '#site_name#',
			'%%sitedesc%%'             => '#site_description#',
			'%%title%%'                => '#title#',
			'%%excerpt%%'              => '#excerpt#',
			'%%date%%'                 => '#date_published#',
			'%%modified%%'             => '#date_modified#',
			'%%name%%'                 => '#author#',
			'%%category%%'             => '#categories#',
			'%%tags%%'                 => '#tags#',
			'%%term_title%%'           => '#term_name#',
			'%%category_description%%' => '#term_description#',
			'%%tag_description%%'      => '#term_description#',
			'%%term_description%%'     => '#term_description#',
			'%%pt_single%%'            => '#post_type_singular_name#',
			'%%pt_plural%%'            => '#post_type_plural_name#',
			'%%searchphrase%%'         => '#search_term#',
		);
	}

	public function has_tag( $string ) {
		return false !== strpos( $string, '%%' );
	}

	public function get_static_fields() {
		$data = get_option( 'wpseo_titles' );
		$out = array();

		$map = array();
		foreach ( WP_SEO_Settings()->get_taxonomies() as $name => $object ) {
			$map[ "title-tax-{$name}" ] = "archive_{$name}_title";
			$map[ "metadesc-tax-{$name}" ] = "archive_{$name}_description";
			$map[ "metakey-tax-{$name}" ] = "archive_{$name}_keywords";
		}
		foreach ( WP_SEO_Settings()->get_single_post_types() as $name => $object ) {
			$map[ "title-{$name}" ] = "single_{$name}_title";
			$map[ "metadesc-{$name}" ] = "single_{$name}_description";
			$map[ "metakey-{$name}" ] = "single_{$name}_keywords";
		}
		foreach ( WP_SEO_Settings()->get_archived_post_types() as $name => $object ) {
			$map[ "title-ptarchive-{$name}" ] = "archive_{$name}_title";
			$map[ "metadesc-ptarchive-{$name}" ] = "archive_{$name}_description";
			$map[ "metakey-ptarchive-{$name}" ] = "archive_{$name}_keywords";
		}
		$map = array_merge( $map, array(
			'title-home-wpseo'      => 'home_title',
			'metadesc-home-wpseo'   => 'home_description',
			'metakey-home-wpseo'    => 'home_keywords',
			'title-author-wpseo'    => 'archive_author_title',
			'metadesc-author-wpseo' => 'archive_author_description',
			'metakey-author-wpseo'  => 'archive_author_keywords',
			'title-archive-wpseo'   => 'archive_date_title',
			'metadesc-date-wpseo'   => 'archive_date_description',
			'title-search-wpseo'    => 'search_title',
			'title-404-wpseo'       => '404_title',
		) );

		foreach ( $map as $key => $value ) {
			if ( ! empty( $data[ $key ] ) ) {
				$out[ $map[ $key ] ] = $data[ $key ];
			}
		}

		return $out;
	}

	public function get_arbitrary_tags() {
		$data = get_option( 'wpseo' );
		$fields = array();

		foreach ( array(
			'googleverify'    => 'google-site-verification',
			'msverify'        => 'msvalidate.01',
			'pinterestverify' => 'p:domain_verify',
			'alexaverify'     => 'alexaVerifyID',
			'yandexverify'    => 'yandex-verification',
		) as $key => $name ) {
			if ( ! empty( $data[ $key ] ) ) {
				$fields[] = array( 'name' => $name, 'content' => $data[ $key ] );
			}
		}

		return $fields;
	}

	/**
	 * Yoast uses a blacklist of post types and taxonomies on which
	 * its meta box should not appear. WP SEO uses a whitelist. If
	 * any post types or taxonomies are in the blacklist, ensure
	 * they aren't in our whitelist.
	 */

	public function get_enabled_post_types( $single_post_types ) {
		$option = get_option( 'wpseo_titles' );
		foreach ( $single_post_types as $name => $object ) {
			if ( ! empty( $option[ "hideeditbox-{$name}" ] ) ) {
				unset( $single_post_types[ $name ] );
			}
		}
		return array_keys( $single_post_types );
	}

	public function get_enabled_taxonomies( $taxonomies ) {
		$option = get_option( 'wpseo_titles' );
		foreach ( $taxonomies as $name => $object ) {
			if ( ! empty( $option[ "hideeditbox-tax-{$name}" ] ) ) {
				unset( $taxonomies[ $name ] );
			}
		}
		return array_keys( $taxonomies );
	}

}

/**
 * Register the bundled converters.
 *
 * @param  array $converters Associative array of converter slugs and class names.
 * @return array             Available coverters, including these.
 */
function wp_seo_bundled_converters( $converters ) {
	$converters['add-meta-tags'] = 'WP_SEO_Add_Meta_Tags_Converter';
	$converters['aiosp']         = 'WP_SEO_AIOSP_Converter';
	$converters['yoast']         = 'WP_SEO_Yoast_Converter';
	return $converters;
}
add_filter( 'wp_seo_converters', 'wp_seo_bundled_converters' );