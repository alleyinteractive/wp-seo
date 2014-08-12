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
	 * [--force-formatting-tags]
	 * : Convert values even if they contain formatting tags without equivalents.
	 *
	 * [--force-arbitrary-tags]
	 * : Replace existing arbitrary tags if one with the same "name" is converted.
	 *
	 * [--force-all]
	 * : Same as --force-formatting-tags --force-arbitrary-tags
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
	 * @synopsis --from=<name> [--force-formatting-tags] [--force-arbitrary-tags] [--force-all] [--drop-if-exists] [--dry-run] [--verbose]
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

		$force_formatting_tags = ! empty( $assoc_args['force-all'] ) || ! empty( $assoc_args['force-formatting-tags'] );
		$force_arbitrary_tags  = ! empty( $assoc_args['force-all'] ) || ! empty( $assoc_args['force-arbitrary-tags'] );
		$drop                  = ! empty( $assoc_args['drop-if-exists'] );
		$dry_run               = ! empty( $assoc_args['dry-run'] );
		$verbose               = ! empty( $assoc_args['verbose'] );

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

		$current_settings  = $drop ? array() : WP_SEO_Settings()->get_all_options();
		$new_settings      = array();

		$single_post_types = WP_SEO_Settings()->get_single_post_types();
		$taxonomies        = WP_SEO_Settings()->get_taxonomies();
		$tag_map           = $converter->get_tag_map();
		$old_tags          = array_keys( $tag_map );
		$new_tags          = array_values( $tag_map );


		if ( $converter_static_fields = $converter->get_static_fields() ) {
			foreach ( $converter_static_fields as $key => $value ) {
				if ( ! $converter->has_tag( $value ) ) {
					continue;
				} else {
					$value = str_replace( $old_tags, $new_tags, $value );
					$requires_force = $converter->has_tag( $value );
					if ( $force_formatting_tags || ! $requires_force ) {
						if ( $requires_force ) {
							WP_CLI::warning( sprintf( __( 'Forcibly converting the `%s` setting with unsupported formatting tags.', 'wp-seo' ), $key ) );
						}
						$converter_static_fields[ $key ] = $value;
					} else {
						unset( $converter_static_fields[ $key ] );
						WP_CLI::warning( sprintf( __( 'Could not convert the `%s` setting because it contains unsupported formatting tags.', 'wp-seo' ), $key ) );
					}
				}
			}
			$new_settings = $converter_static_fields;
		}

		$new_settings['post_types'] = $converter->get_enabled_post_types( $single_post_types );
		$new_settings['taxonomies'] = $converter->get_enabled_taxonomies( $taxonomies );

		if ( $converter_arbitrary_tags = $converter->get_arbitrary_tags() ) {
			$new_settings['arbitrary_tags'] = array();
			if ( isset( $current_settings['arbitrary_tags'] ) ) {
				$new_settings['arbitrary_tags'] = $current_settings['arbitrary_tags'];
			}
			if ( $force_arbitrary_tags ) {
				foreach ( wp_list_pluck( $converter_arbitrary_tags, 'name' ) as $name ) {
					$new_settings['arbitrary_tags'] = wp_list_filter( $new_settings['arbitrary_tags'], array( 'name' => $name ), 'NOT' );
				}
			} else {
				foreach ( wp_list_pluck( $new_settings['arbitrary_tags'], 'name' ) as $name ) {
					$converter_arbitrary_tags = wp_list_filter( $converter_arbitrary_tags, array( 'name' => $name ), 'NOT' );
				}
			}
			foreach ( $converter_arbitrary_tags as $index => $tag ) {
				if ( ! $converter->has_tag( $tag['content'] ) ) {
					continue;
				} else {
					$tag['content'] = str_replace( $old_tags, $new_tags, $tag['content'] );
					$requires_force = $converter->has_tag( $tag['content'] );
					if ( $force_formatting_tags || ! $requires_force ) {
						if ( $requires_force ) {
							WP_CLI::warning( sprintf( __( 'Forcibly converting %s arbitrary tag `%s` with unsupported formatting tags.', 'wp-seo' ), $converter->label, $tag['name'] ) );
						}
						$converter_arbitrary_tags[ $index ]['content'] = $tag['content'];
					} else {
						unset( $converter_arbitrary_tags[ $index ] );
						WP_CLI::warning( sprintf( __( 'Could not convert the %s arbitrary tag `%s` because it contains unsupported formatting tags.', 'wp-seo' ), $converter->label, $tag['name'] ) );
					}
				}
			}
			$new_settings['arbitrary_tags'] = array_merge( $new_settings['arbitrary_tags'], $converter_arbitrary_tags );
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