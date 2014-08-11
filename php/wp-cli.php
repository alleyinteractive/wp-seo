<?php
/**
 * WP SEO commands.
 */
class WP_SEO_CLI_Command extends WP_CLI_Command {

	/**
	 * Converts meta tags and formatting tags from other libraries to WP SEO.
	 *
	 * ## OPTIONS
	 *
	 * --from
	 * : What library to convert values from. Accepted values: add-meta-tags, aiosp, yoast.
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
	 *     wp seo convert --from=aiosp --verbose --force
	 *     wp seo convert --from=yoast --dry-run
	 *
	 * @synopsis --from=<name> [--force] [--drop-if-exists] [--dry-run] [--verbose]
	 */
	public function convert( $args, $assoc_args ) {
		$converters = apply_filters( 'wp_seo_converters', array() );
		if ( isset( $converters[ $assoc_args['from'] ] ) ) {
			$converter = new $converters[ $assoc_args['from'] ];
		} else {
			WP_CLI::error( sprintf( __( 'No converter found for %s', 'wp-seo' ), $assoc_args['from'] ) );
		}

		$verify = $converter->can_convert();
		if ( ! is_wp_error( $verify ) ) {
			WP_CLI::log( sprintf( __( 'Converting settings from %s...', 'wp-seo' ), $converter->label ) );
		} else {
			WP_CLI::error( $verify->get_error_message( 'can_convert' ) );
		}

		$force             = ! empty( $assoc_args['force'] );
		$drop              = ! empty( $assoc_args['drop-if-exists'] );
		$dry_run           = ! empty( $assoc_args['dry-run'] );
		$verbose           = ! empty( $assoc_args['verbose'] );

		$current_settings  = WP_SEO_Settings()->get_all_options();
		$new_settings      = array();

		$single_post_types = WP_SEO_Settings()->get_single_post_types();
		$taxonomies        = WP_SEO_Settings()->get_taxonomies();
		$tag_map           = $converter->get_tag_map();
		$old_tags          = array_keys( $tag_map );
		$new_tags          = array_values( $tag_map );

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

		if ( $static_data = $converter->get_static_field_data() ) {
			foreach ( $converter->get_static_field_map() as $x => $the_spot ) {
				if ( ! empty( $static_data[ $x ] ) ) {
					$treasure = $static_data[ $x ];
					if ( ! $converter->has_tag( $treasure ) ) {
						$new_settings[ $the_spot ] = $treasure;
					} else {
						$treasure = str_replace( $old_tags, $new_tags, $treasure );
						$requires_force = $converter->has_tag( $treasure );
						if ( $force || ! $requires_force ) {
							if ( $requires_force ) {
								WP_CLI::warning( sprintf( __( 'Forcibly converting %s setting `%s` with unsupported formatting tags.', 'wp-seo' ), $converter->label, $x ) );
							}
							$new_settings[ $the_spot ] = $treasure;
						} else {
							WP_CLI::warning( sprintf( __( 'Could not convert the %s setting `%s` because it contains unsupported formatting tags.', 'wp-seo' ), $converter->label, $x ) );
						}
					}
				}
			}
		}

		$new_settings['post_types'] = $converter->get_enabled_post_types( $single_post_types );
		$new_settings['taxonomies'] = $converter->get_enabled_taxonomies( $taxonomies );

		if ( isset( $current_settings['arbitrary_tags'] ) ) {
			$new_settings['arbitrary_tags'] = $current_settings['arbitrary_tags'];
		}

		if ( $arbitrary_data = $converter->get_arbitrary_field_data() ) {
			$new_tag_names = wp_list_pluck( $new_settings['arbitrary_tags'], 'name' );
			foreach ( $converter->get_arbitrary_field_map() as $x => $the_spot ) {
				if ( ! in_array( $the_spot, $new_tag_names ) && ! empty( $arbitrary_data[ $x ] ) ) {
					$treasure = $arbitrary_data[ $x ];
					if ( ! $converter->has_tag( $treasure ) ) {
						$new_settings['arbitrary_tags'][] = array( 'name' => $the_spot, 'content' => $treasure );
					} else {
						$treasure = str_replace( $old_tags, $new_tags, $treasure );
						$requires_force = $converter->has_tag( $treasure );
						if ( $force || ! $requires_force ) {
							if ( $requires_force ) {
								WP_CLI::warning( sprintf( __( 'Forcibly converting %s setting `%s` with unsupported formatting tags.', 'wp-seo' ), $converter->label, $x ) );
							}
							$new_settings['arbitrary_tags'][] = array( 'name' => $the_spot, 'content' => $treasure );
						} else {
							WP_CLI::warning( sprintf( __( 'Could not convert the %s setting `%s` because it contains unsupported formatting tags.', 'wp-seo' ), $converter->label, $x ) );
						}
					}
				}
			}
		}

		if ( empty( $new_settings ) ) {
			WP_CLI::error( __( 'Nothing to convert: No settings that WP-SEO could use are filled in.', 'wp-seo' ) );
		} else {
			$result = WP_SEO_Settings()->sanitize_options( array_merge( $current_settings, $new_settings ) );
		}

		if ( $dry_run ) {
			WP_CLI::log( __( 'Simulating conversion...', 'wp-seo' ) );
		}

		if ( $verbose ) {
			// Loop through $new_settings to show only updates, not all WP SEO options.
			foreach ( array_keys( $new_settings ) as $key ) {
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

}
WP_CLI::add_command( 'seo', 'WP_SEO_CLI_Command' );