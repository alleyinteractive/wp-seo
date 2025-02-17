<?php
/**
 * Contains functions for working with meta.
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO;

/**
 * Register meta for posts or terms with sensible defaults and sanitization.
 *
 * @throws \InvalidArgumentException For unmet requirements.
 *
 * @see \register_post_meta
 * @see \register_term_meta
 *
 * @param string               $object_type  The type of meta to register, which must be one of 'post' or 'term'.
 * @param string|string[]      $object_slugs The post type or taxonomy slugs to register with.
 * @param string               $meta_key     The meta key to register.
 * @param array<string, mixed> $args         Optional. Additional arguments for register_post_meta or register_term_meta. Defaults to an empty array.
 * @return bool True if the meta key was successfully registered in the global array, false if not.
 */
function register_meta_helper(
	string $object_type,
	string|array $object_slugs,
	string $meta_key,
	array $args = []
): bool {

	// Object type must be either post or term.
	if ( ! in_array( $object_type, [ 'post', 'term' ], true ) ) {
		throw new \InvalidArgumentException(
			esc_html__(
				'Object type must be one of "post", "term".',
				'wp-seo'
			)
		);
	}

	/**
	 * Merge provided arguments with defaults and filter register_meta() args.
	 *
	 * @link https://developer.wordpress.org/reference/functions/register_meta/
	 *
	 * @param array           $args {
	 *     Array of args to be passed to register_meta().
	 *
	 *     @type string     $object_subtype    A subtype; e.g. if the object type is "post", the post type. If left empty,
	 *                                         the meta key will be registered on the entire object type. Default empty.
	 *     @type string     $type              The type of data associated with this meta key. Valid values are
	 *                                         'string', 'boolean', 'integer', 'number', 'array', and 'object'.
	 *     @type string     $description       A description of the data attached to this meta key.
	 *     @type bool       $single            Whether the meta key has one value per object, or an array of values per object.
	 *     @type mixed      $default           The default value returned from get_metadata() if no value has been set yet.
	 *                                         When using a non-single meta key, the default value is for the first entry. In other words,
	 *                                         when calling get_metadata() with $single set to false, the default value given here will be wrapped in an array.
	 *     @type callable   $sanitize_callback A function or method to call when sanitizing $meta_key data.
	 *     @type callable   $auth_callback     Optional. A function or method to call when performing edit_post_meta,
	 *                                         add_post_meta, and delete_post_meta capability checks.
	 *     @type bool|array $show_in_rest      Whether data associated with this meta key can be considered public and should be
	 *                                         accessible via the REST API. A custom post type must also declare support
	 *                                         for custom fields for registered meta to be accessible via REST. When registering
	 *                                         complex meta values this argument may optionally be an array with 'schema'
	 *                                         or 'prepare_callback' keys instead of a boolean.
	 * }
	 * @param string          $object_type  The type of meta to register, which must be one of 'post' or 'term'.
	 * @param string|string[] $object_slugs The post type or taxonomy slugs to register with.
	 * @param string          $meta_key     The meta key to register.
	 */
	$args = apply_filters(
		'wp_seo_register_meta_helper_args', // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		wp_parse_args(
			$args,
			[
				'show_in_rest' => true,
				'single'       => true,
				'type'         => 'string',
			]
		),
		$object_type,
		$object_slugs,
		$meta_key
	);

	// Allow setting meta for all of an object type.
	if (
		(
			is_array( $object_slugs ) &&
			1 === count( $object_slugs ) &&
			'all' === $object_slugs[0]
		) ||
		(
			is_string( $object_slugs ) &&
			'all' === $object_slugs
		)
	) {
		return register_meta( $object_type, $meta_key, $args ); // @phpstan-ignore-line array given
	}

	// Fix potential errors since we're allowing `$object_slugs` to be a string or array.
	if ( is_string( $object_slugs ) ) {
		$object_slugs = [ $object_slugs ];
	}

	// Fork for object type.
	switch ( $object_type ) {
		case 'post':
			foreach ( $object_slugs as $object_slug ) {
				if ( ! register_post_meta( $object_slug, $meta_key, $args ) ) { // @phpstan-ignore-line array given
					return false;
				}
			}
			break;
		case 'term':
			foreach ( $object_slugs as $object_slug ) {
				if ( ! register_term_meta( $object_slug, $meta_key, $args ) ) { // @phpstan-ignore-line array given
					return false;
				}
			}
			break;
		default:
			return false;
	}

	return true;
}

/**
 * Reads the post meta definitions from config and registers them.
 */
function register_post_meta_from_defs(): void {
	$filepath = dirname( __DIR__ ) . '/config/post-meta.json';

	// Ensure the config file exists and is valid.
	if ( ! file_exists( $filepath ) || ! in_array( validate_file( $filepath ), [ 0, 2 ], true ) ) {
		return;
	}

	// Try to read the file's contents. We can dismiss the "uncached" warning here because it is a local file.
	// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
	$definitions = json_decode( (string) file_get_contents( $filepath ), true );
	if ( empty( $definitions ) || ! is_array( $definitions ) ) {
		return;
	}

	// Loop through definitions and register each.
	foreach ( $definitions as $meta_key => $definition ) {
		if ( ! is_array( $definition ) ) {
			_doing_it_wrong( __FUNCTION__, 'Post meta definition items must be an array.', '1.0.0' );

			continue;
		}

		// Extract post types.
		$post_types = $definition['post_types'] ?? [];

		// Unset since $definition is passed as register_meta args.
		unset( $definition['post_types'] );

		// Relocate schema, if specified at the top level.
		if ( ! empty( $definition['schema'] ) ) {
			if ( ! isset( $definition['show_in_rest'] ) || ! is_array( $definition['show_in_rest'] ) ) {
				$definition['show_in_rest'] = [];
			}

			$definition['show_in_rest']['schema'] = $definition['schema'];
			// Unset since $definition is passed as register_meta args.
			unset( $definition['schema'] );
		}

		// Register the meta.
		register_meta_helper(
			'post',
			$post_types, // @phpstan-ignore-line array given
			$meta_key,
			$definition,  // @phpstan-ignore-line array given
		);
	}
}
