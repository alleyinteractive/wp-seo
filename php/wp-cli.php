<?php
/**
 * WP SEO commands.
 */
class WP_SEO_CLI_Command extends WP_CLI_Command {

	/**
	 * The plugin whose values are in the middle of being converted.
	 *
	 * @var string @see the --from parameter in WP_SEO_CLI_Command::convert().
	 */
	private $converting_from;

	/**
	 * Slugs of other SEO plugins that WP_SEO_CLI_Command::convert() supports.
	 *
	 * @var array {
	 *     Array of plugin slugs.
	 *
	 *     @type  array $slug {
	 *         Data about each plugin used during conversions.
	 *
	 *         @type  string $label The readable name for informational output.
	 *         @type  string $option The option name to convert values from.
	 *     }
	 * }
	 */
	private $can_convert_from = array(
		'add-meta-tags' => array(
			'label' => 'Add Meta Tags',
			'option' => 'add_meta_tags_opts',
		),
		'yoast' => array(
			'label' => 'Yoast WordPress SEO',
			'option' => 'wpseo_titles',
		),
		'aiosp' => array(
			'label' => 'All In One SEO Pack',
			'option' => 'aioseop_options',
		),
	);

	/**
	 * Converts meta tags and formatting tags from other plugins to WP SEO.
	 *
	 * @todo  Tell the user the supported fields with each converter.
	 *
	 * ## OPTIONS
	 *
	 * --from
	 * : What plugin to convert values from. Accepted values: add-meta-tags, yoast, aiosp.
	 *
	 * [--force]
	 * : Convert values even if they contain formatting tags without equivalents.
	 *
	 * [--drop-if-exists]
	 * : Remove all existing WP SEO settings, and replace only what is converted.
	 *
	 * [--dry-run]
	 * : Simulate the conversion.
	 *
	 * [--verbose]
	 * : Show each setting to be converted.
	 *
	 * ## EXAMPLES
	 *
	 *     wp seo convert --from=add-meta-tags
	 *     wp seo convert --from=yoast --dry-run
	 *     wp seo convert --from=aiosp --verbose --force
	 *
	 * @synopsis --from=<name> [--force] [--drop-if-exists] [--dry-run] [--verbose]
	 */
	public function convert( $args, $assoc_args ) {
		$this->converting_from = $assoc_args['from'];

		if ( ! isset( $this->can_convert_from[ $this->converting_from ] ) ) {
			WP_CLI::error( sprintf( __( 'No converter found for %s', 'wp-seo' ), $this->converting_from ) );
		} else if ( ! $old = get_option( $this->can_convert_from[ $this->converting_from ]['option'] ) ) {
			WP_CLI::error( sprintf( __( 'No settings found for %s.', 'wp-seo' ), $this->can_convert_from[ $this->converting_from ]['label'] ) );
		} else {
			WP_CLI::log( sprintf( __( 'Converting settings from %s...', 'wp-seo' ), $this->can_convert_from[ $this->converting_from ]['label'] ) );
		}

		$force = ! empty( $assoc_args['force'] );
		$drop = ! empty( $assoc_args['drop-if-exists'] );
		$dry_run = ! empty( $assoc_args['dry-run'] );
		$verbose = ! empty( $assoc_args['verbose'] );

		if ( $drop && false !== get_option( WP_SEO_Settings()->get_slug() ) ) {
			if ( $dry_run ) {
				WP_CLI::warning( __( 'Pretending to delete all existing WP SEO settings and starting fresh.', 'wp-seo' ) );
			} else {
				$deleted = delete_option( WP_SEO_Settings()->get_slug() );
				if ( ! $deleted ) {
					WP_CLI::error( __( 'Error deleting existing WP SEO settings. Exiting.', 'wp-seo' ) );
				} else {
					WP_CLI::warning( __( 'Deleting all existing WP SEO settings and starting fresh.', 'wp-seo' ) );
				}
			}
		}

		$current_values = WP_SEO_Settings()->get_all_options();
		$new_values = array();

		switch ( $this->converting_from ) {
			case 'add-meta-tags' :
				$new_values = $this->convert_static_fields_from_map( $old, $new_values, $this->build_static_fields_map(), $force );
				// Add Meta Tags doesn't keep what we would call arbitrary tags.
			break;

			case 'yoast' :
				$new_values = $this->convert_static_fields_from_map( $old, $new_values, $this->build_static_fields_map(), $force );

				/**
				 * Yoast uses a blacklist of post types and taxonomies on which
				 * its meta box should not appear. WP SEO uses a whitelist. If
				 * any post types or taxonomies are in the blacklist, ensure
				 * they aren't in our whitelist.
				 *
				 * If we dropped the existing settings, then all the eligible
				 * post types and taxonomies should be enabled except the
				 * backlisted ones.
				 */
				$hide_post_types = array();
				$hide_taxonomies = array();

				$single_post_types = WP_SEO_Settings()->get_single_post_types();
				foreach ( $single_post_types as $name => $object ) {
					if ( ! empty( $old[ "hideeditbox-{$name}" ] ) ) {
						$hide_post_types[] = $name;
					}
				}
				if ( ! empty( $hide_post_types ) ) {
					$remove_from = $drop ? array_keys( $single_post_types ) : $current_values['post_types'];
					$new_values['post_types'] = array_diff( $remove_from, $hide_post_types );
				}

				$taxonomies = WP_SEO_Settings()->get_taxonomies();
				foreach ( $taxonomies as $name => $object ) {
					if ( ! empty( $old[ "hideeditbox-tax-{$name}" ] ) ) {
						$hide_taxonomies[] = $name;
					}
				}
				if ( ! empty( $hide_taxonomies ) ) {
					$remove_from = $drop ? array_keys( $taxonomies ) : $current_values['post_types'];
					$new_values['taxonomies'] = array_diff( $remove_from, $hide_taxonomies );
				}

				/**
				 * Yoast uses a different option for its verification tags.
				 * Technically, but unrealistically, this second option could
				 * exist without the first, which would prevent the converter
				 * from reaching here.
				 */
				$old_verify_values = get_option( 'wpseo' );
				if ( ! empty( $old_verify_values ) ) {
					$new_values = $this->convert_arbitrary_fields_from_map( $old_verify_values, $new_values, $current_values, $this->build_arbitrary_fields_map() );
				}
			break;

			case 'aiosp' :
				$new_values = $this->convert_static_fields_from_map( $old, $new_values, $this->build_static_fields_map(), $force );
				$new_values = $this->convert_arbitrary_fields_from_map( $old, $new_values, $current_values, $this->build_arbitrary_fields_map() );
			break;
		}

		if ( empty( $new_values ) ) {
			WP_CLI::error( __( 'Nothing to convert: No settings that WP-SEO could use are filled in.', 'wp-seo' ) );
		} else {
			$result = WP_SEO_Settings()->sanitize_options( array_merge( $current_values, $new_values ) );
		}

		if ( $dry_run ) {
			WP_CLI::log( __( 'Simulating conversion...', 'wp-seo' ) );
		}

		if ( $verbose ) {
			// Loop through $new_values to show only updates, not all WP SEO options.
			foreach ( array_keys( $new_values ) as $key ) {
				if ( isset( $result[ $key ] ) ) {
					$value = $result[ $key ];
					if ( is_array( $value ) ) {
						$value = json_encode( $value );
					}
					WP_CLI::log( sprintf( __( 'Updating the `%s` setting to "%s"', 'wp-seo' ), $key, $value ) );
				}
			}
		}

		if ( $dry_run ) {
			WP_CLI::success( __( 'Pretended to convert settings.', 'wp-seo' ) );
		} else {
			if ( update_option( WP_SEO_Settings()->get_slug(), $result ) ) {
				WP_CLI::success( __( 'Converted settings.', 'wp-seo' ) );
			} else {
				WP_CLI::error( __( 'Error updating the database.', 'wp-seo' ) );
			}
		}
	}

	/**
	 * Create the map of old-to-new keys for converting static options.
	 *
	 * @uses  WP_SEO_CLI_Command::converting_from To get the right "from" map.
	 *
	 * @return array @see WP_SEO_CLI_Command::convert_static_fields_from_map().
	 */
	private function build_static_fields_map() {
		switch ( $this->converting_from ) {
			case 'add-meta-tags' :
				return array(
					'site_description' => 'home_description',
					'site_keywords'    => 'home_keywords',
				);
			break;

			case 'yoast' :
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
				return $map;
			break;

			case 'aiosp' :
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
				return $map;
			break;

			default :
				return array();
			break;
		}
	}

	/**
	 * Create the map of new-to-old keys for converting arbitrary tags.
	 *
	 * @uses  WP_SEO_CLI_Command::converting_from To get the right "from" map.
	 *
	 * @return array @see WP_SEO_CLI_Command::convert_arbitrary_fields_from_map().
	 */
	private function build_arbitrary_fields_map() {
		switch ( $this->converting_from ) {
			case 'yoast' :
				return array(
					'google-site-verification' => 'googleverify',
					'msvalidate.01'            => 'msverify',
					'p:domain_verify'          => 'pinterestverify',
					'alexaVerifyID'            => 'alexaverify',
					'yandex-verification'      => 'yandexverify',
				);
			break;

			case 'aiosp' :
				return array(
					'google-site-verification' => 'aiosp_google_verify',
					'msvalidate.01'            => 'aiosp_bing_verify',
					'p:domain_verify'          => 'aiosp_pinterest_verify',
				);
			break;

			default :
				return array();
			break;
		}
	}

	/**
	 * Guess whether a string has formatting tags from other plugins.
	 *
	 * @see  WP_SEO_CLI_Command::convert_formatting_tags().
	 * @see  wpseo_replace_vars() for how Yoast SEO tests for its formatting tags.
	 *
	 * @param  string $string Text that might have formatting tags.
	 * @return bool
	 */
	private function has_formatting_tags_to_convert( $string ) {
		switch ( $this->converting_from ) {
			case 'yoast' :
				return false !== strpos( $string, '%%' );
			break;

			case 'aiosp' :
				return false !== strpos( $string, '%' );
			break;

			default:
				return false;
			break;
		}
	}

	/**
	 * Replace formatting tags from other plugins with their WP SEO equivalents.
	 *
	 * @param  string $string Text with formatting tags to replace.
	 * @return string|WP_Error The updated text, or WP_Error if the text
	 *     contains formatting tags for which WP SEO has no equivalents.
	 */
	private function convert_formatting_tags( $string ) {
		$replace = array();

		foreach ( WP_SEO()->get_formatting_tags() as $tag ) {
			if ( isset( $tag->equivalents[ $this->converting_from ] ) ) {
				if ( is_array( $tag->equivalents[ $this->converting_from ] ) ) {
					$replace = array_merge( $replace, array_fill_keys( $tag->equivalents[ $this->converting_from ], $tag->tag ) );
				} else {
					$replace[ $tag->equivalents[ $this->converting_from ] ] = $tag->tag;
				}
			}
		}

		return str_replace( array_keys( $replace ), array_values( $replace ), $string );
	}

	/**
	 * Given settings from another plugin and a map, create settings for WP SEO.
	 *
	 * @param  array $source The settings to convert from.
	 * @param  array $target The under-construction WP SEO settings to merge
	 *     $source into. @see WP_SEO_CLI_Command::convert().
	 * @param  arrry $map Associative array, where $source[ $key ] = $target[ $value ].
	 * @param  bool $force @see WP_SEO_CLI_Command::convert().
	 * @return array Options for WP SEO with the updated values.
	 */
	private function convert_static_fields_from_map( $source, $target, $map, $force ) {
		foreach ( $map as $their_key => $our_key ) {
			if ( ! empty( $source[ $their_key ] ) ) {
				// Avoid parsing the Formatting Tags if we can.
				if ( ! $this->has_formatting_tags_to_convert( $source[ $their_key ] ) ) {
					$target[ $our_key ] = $source[ $their_key ];
				} else {
					$converted = $this->convert_formatting_tags( $source[ $their_key ] );
					$requires_force = $this->has_formatting_tags_to_convert( $converted );
					if ( $force || ! $requires_force ) {
						if ( $requires_force ) {
							WP_CLI::warning( sprintf( __( 'Forcibly converting %s setting `%s` with unsupported formatting tags.', 'wp-seo' ), $this->can_convert_from[ $this->converting_from ]['label'], $their_key ) );
						}
						$target[ $our_key ] = $converted;
					} else {
						WP_CLI::warning( sprintf( __( 'Could not convert the %s setting `%s` because it contains unsupported formatting tags.', 'wp-seo' ), $this->can_convert_from[ $this->converting_from ]['label'], $their_key, $source[ $their_key ] ) );
					}
				}
			}
		}

		return $target;
	}

	/**
	 * Given settings from another plugin and a map, create arbitrary tags for WP SEO.
	 *
	 * First updates the value of any arbitrary tags that already exist for the
	 * keys in the map. If the map still has keys after that, new arbitrary tags
	 * are appended for them.
	 *
	 * @see  WP_SEO_CLI_Command::convert(). Formatting tags are not accounted for
	 *     so long as we aren't trying to make arbitrary tags out of values that
	 *     would have any.
	 *
	 * @param  array $source @see WP_SEO_CLI_Command::convert_static_fields_from_map().
	 * @param  array $target @see WP_SEO_CLI_Command::convert_static_fields_from_map().
	 * @param  array $current The existing, pre-conversion WP SEO option.
	 * @param  array $map Associative array, where each key is the "Name" of a
	 *     WP SEO arbitrary tag and the value is the key in $source where the
	 *     "Content" for that tag would be found. @see WP_SEO_Settings::register_settings().
	 * @return array Options for WP SEO with the updated values.
	 */
	private function convert_arbitrary_fields_from_map( $source, $target, $current, $map ) {
		$out = array();

		if ( isset( $current['arbitrary_tags'] ) ) {
			$out = $current['arbitrary_tags'];
			foreach ( $out as $index => $tag ) {
				if ( isset( $map[ $tag['name'] ] ) && ! empty( $source[ $map[ $tag['name'] ] ] ) ) {
					$out[ $index ]['content'] = $source[ $map[ $tag['name'] ] ];
					unset( $map[ $tag['name'] ] );
				}
			}
		}

		if ( ! empty( $map ) ) {
			foreach ( $map as $our_key => $their_key ) {
				if ( ! empty( $source[ $their_key ] ) ) {
					$out[] = array( 'name' => $our_key, 'content' => $source[ $their_key ] );
				}
			}
		}

		$target['arbitrary_tags'] = $out;

		return $target;
	}

}
WP_CLI::add_command( 'seo', 'WP_SEO_CLI_Command' );