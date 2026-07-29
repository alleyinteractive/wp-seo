<?php
/**
 * Open_Graph class file
 *
 * @package wp-seo
 */

namespace Alley\WP\WP_SEO\Features;

use Alley\WP\Types\Feature;

use function Alley\WP\WP_SEO\register_meta_helper;

/**
 * Open Graph Feature
 */
final class Open_Graph implements Feature {

	/**
	 * WP SEO settings.
	 *
	 * @var \WP_SEO_Settings|null
	 */
	protected ?\WP_SEO_Settings $wp_seo_settings = null;

	/**
	 * Boot the feature.
	 */
	public function boot(): void {
		add_action( 'init', [ $this, 'add_post_type_support' ] );
		add_action( 'init', [ $this, 'add_meta_fields' ] );
		add_action( 'wp_head', [ $this, 'render_open_graph_tags' ] );
	}

	/**
	 * Get the WP SEO settings instance, initializing it if needed.
	 */
	protected function get_settings(): \WP_SEO_Settings {
		if ( null === $this->wp_seo_settings ) {
			$this->wp_seo_settings = \WP_SEO_Settings::instance();
		}

		return $this->wp_seo_settings;
	}

	/**
	 * Add post type support.
	 *
	 * @return void
	 */
	public function add_post_type_support() {
		$enabled_post_types = $this->get_settings()->get_option( 'open_graph_post_types' );

		if ( is_array( $enabled_post_types ) ) {
			foreach ( $enabled_post_types as $post_type ) {
				if ( is_string( $post_type ) ) {
					add_post_type_support( $post_type, 'open-graph' );
				}
			}
		}
	}

	/**
	 * Add meta fields.
	 */
	public function add_meta_fields(): void {
		register_meta_helper(
			'post',
			get_post_types_by_support( 'open-graph' ),
			'wp_seo_open_graph_title',
			[
				'sanitize_callback' => 'sanitize_text_field',
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => true,
			]
		);

		register_meta_helper(
			'post',
			get_post_types_by_support( 'open-graph' ),
			'wp_seo_open_graph_description',
			[
				'sanitize_callback' => 'sanitize_text_field',
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => true,
			]
		);

		register_meta_helper(
			'post',
			get_post_types_by_support( 'open-graph' ),
			'wp_seo_open_graph_image',
			[
				'sanitize_callback' => 'absint',
				'single'            => true,
				'type'              => 'integer',
				'show_in_rest'      => true,
			]
		);
	}

	/**
	 * Get the title with a fallback.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string The title.
	 */
	public static function get_title( $post_id ): string {
		$open_graph_title = get_post_meta( $post_id, 'wp_seo_open_graph_title', true );

		if ( ! empty( $open_graph_title ) && is_string( $open_graph_title ) ) {
			return $open_graph_title;
		}

		return get_the_title( $post_id );
	}

	/**
	 * Get the description with a fallback.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string The description.
	 */
	public static function get_description( $post_id ): string {
		$open_graph_description = get_post_meta( $post_id, 'wp_seo_open_graph_description', true );

		if ( ! empty( $open_graph_description ) && is_string( $open_graph_description ) ) {
			return $open_graph_description;
		}

		return get_the_excerpt( $post_id );
	}

	/**
	 * Get the image with a fallback.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return string|false The image URL or false if no assigned images.
	 */
	public static function get_image( $post_id ): string|bool {
		$open_graph_image_id  = get_post_meta( $post_id, 'wp_seo_open_graph_image', true );
		$open_graph_image_url = ( ! empty( $open_graph_image_id ) && is_string( $open_graph_image_id ) )
		? wp_get_attachment_image_url( (int) $open_graph_image_id, 'full' )
		: false;

		if ( empty( $open_graph_image_url ) ) {
			$open_graph_image_url = new \WP_Error( 'no_open_graph_image', 'No Open Graph image found' );
		}

		if ( is_wp_error( $open_graph_image_url ) ) {
			$open_graph_image_url = get_the_post_thumbnail_url( $post_id, 'full' );
		}

		if ( empty( $open_graph_image_url ) ) {
			$open_graph_image_url = self::get_default_image( $post_id );
		}

		return $open_graph_image_url;
	}

	/**
	 * Get the site-wide default Open Graph image, used as a fallback when a
	 * post has no explicit Open Graph image or featured image, or on a
	 * non-singular page with no post to pull an image from (pass 0 for
	 * $post_id in that case).
	 *
	 * @param int $post_id The post ID, or 0 if there isn't one (e.g. an archive).
	 *
	 * @return string|false The default image URL, or false if none is configured.
	 */
	protected static function get_default_image( $post_id ): string|bool {
		$default_image_id  = \WP_SEO_Settings::instance()->get_option( 'default_open_graph_image' );
		$default_image_url = ! empty( $default_image_id ) ? wp_get_attachment_image_url( (int) $default_image_id, 'full' ) : false;

		/**
		 * Filter the site-wide default Open Graph image.
		 *
		 * @param string|false $default_image_url The default image URL, or false if none is configured.
		 * @param int          $post_id            The post ID, or 0 if there isn't one (e.g. an archive).
		 */
		return apply_filters( 'wp_seo_default_open_graph_image', $default_image_url, $post_id );
	}

	/**
	 * Render Open Graph tags for the current page.
	 */
	public function render_open_graph_tags(): void {
		if ( is_404() ) {
			return;
		}

		// The homepage always gets basic Open Graph tags, even if it's a
		// static page whose post type hasn't been opted into Open Graph via
		// the post-type support setting -- unless that setting IS enabled,
		// in which case the richer per-post rendering below takes over.
		if ( is_front_page() && ! ( is_singular() && post_type_supports( (string) get_post_type(), 'open-graph' ) ) ) {
			$this->output_open_graph_tags(
				'website',
				get_bloginfo( 'name' ),
				get_bloginfo( 'description' ),
				home_url( '/' ),
				self::get_default_image( 0 )
			);

			return;
		}

		if ( is_singular() ) {
			$this->render_singular_open_graph_tags();

			return;
		}

		$context = $this->get_archive_open_graph_data();

		if ( null === $context ) {
			return;
		}

		$this->output_open_graph_tags(
			'website',
			$context['title'],
			$context['description'],
			$context['url'],
			self::get_default_image( 0 )
		);
	}

	/**
	 * Render Open Graph tags for a singular post or page.
	 */
	private function render_singular_open_graph_tags(): void {
		$post_id = get_the_ID();

		if ( ! is_int( $post_id ) ) {
			return;
		}

		$post_type = get_post_type( $post_id );

		if ( empty( $post_type ) || ! post_type_supports( $post_type, 'open-graph' ) ) {
			return;
		}

		$title       = $this->get_title( $post_id );
		$description = $this->get_description( $post_id );
		$image       = $this->get_image( $post_id );
		$permalink   = ! empty( get_permalink( $post_id ) ) ? get_permalink( $post_id ) : '';
		$additional  = '';

		$published_time = get_the_date( 'c', $post_id );
		$modified_time  = get_the_modified_date( 'c', $post_id );

		if ( ! empty( $published_time ) ) {
			$additional .= sprintf( "\n<meta property=\"article:published_time\" content=\"%s\" />", esc_attr( (string) $published_time ) );
		}

		if ( ! empty( $modified_time ) ) {
			$additional .= sprintf( "\n<meta property=\"article:modified_time\" content=\"%s\" />", esc_attr( (string) $modified_time ) );
		}

		$this->output_open_graph_tags( 'article', $title, $description, $permalink, $image, $additional );
	}

	/**
	 * Determine the title, description, and URL for the current archive,
	 * author, post type archive, date archive, or search results page.
	 *
	 * @return array{title: string, description: string, url: string}|null Null if the current page isn't a recognized archive context.
	 */
	private function get_archive_open_graph_data(): ?array {
		$description_fallback = get_bloginfo( 'description' );

		if ( is_search() ) {
			return [
				'title'       => sprintf(
					/* translators: %s: search query */
					__( 'Search results for "%s"', 'wp-seo' ),
					get_search_query()
				),
				'description' => $description_fallback,
				'url'         => get_search_link(),
			];
		}

		if ( is_author() ) {
			$author = get_queried_object();

			if ( ! $author instanceof \WP_User ) {
				return null;
			}

			return [
				'title'       => $author->display_name,
				'description' => $description_fallback,
				'url'         => get_author_posts_url( $author->ID ),
			];
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();

			if ( ! $term instanceof \WP_Term ) {
				return null;
			}

			$term_description = wp_strip_all_tags( term_description( $term->term_id ) );
			$term_link        = get_term_link( $term );

			return [
				'title'       => single_term_title( '', false ) ?? '',
				'description' => ! empty( $term_description ) ? $term_description : $description_fallback,
				'url'         => is_wp_error( $term_link ) ? '' : $term_link,
			];
		}

		if ( is_post_type_archive() ) {
			$post_type = get_queried_object();

			if ( ! $post_type instanceof \WP_Post_Type ) {
				return null;
			}

			$archive_link = get_post_type_archive_link( $post_type->name );

			return [
				'title'       => post_type_archive_title( '', false ) ?? '',
				'description' => $description_fallback,
				'url'         => ! empty( $archive_link ) ? $archive_link : '',
			];
		}

		if ( is_date() ) {
			return $this->get_date_archive_open_graph_data( $description_fallback );
		}

		return null;
	}

	/**
	 * Determine the title, description, and URL for a year, month, or day
	 * archive.
	 *
	 * Deliberately avoids get_the_date()/get_the_time(), which default to the
	 * global $post -- that global is only populated once the theme's Loop
	 * calls the_post(), which happens after wp_head has already fired in the
	 * standard template flow, so relying on it here would read stale or
	 * empty data.
	 *
	 * @param string $description_fallback The site tagline to use as the description.
	 * @return array{title: string, description: string, url: string}
	 */
	private function get_date_archive_open_graph_data( string $description_fallback ): array {
		$year_var  = get_query_var( 'year' );
		$month_var = get_query_var( 'monthnum' );
		$day_var   = get_query_var( 'day' );

		$year  = is_numeric( $year_var ) ? (int) $year_var : 0;
		$month = is_numeric( $month_var ) ? (int) $month_var : 0;
		$day   = is_numeric( $day_var ) ? (int) $day_var : 0;

		$date_format = get_option( 'date_format' );
		$date_format = is_string( $date_format ) && '' !== $date_format ? $date_format : 'F j, Y';

		if ( $year && $month && $day ) {
			$timestamp = mktime( 0, 0, 0, $month, $day, $year );
			$title     = wp_date( $date_format, false !== $timestamp ? $timestamp : null );
			$url       = get_day_link( $year, $month, $day );
		} elseif ( $year && $month ) {
			$timestamp = mktime( 0, 0, 0, $month, 1, $year );
			$title     = wp_date( 'F Y', false !== $timestamp ? $timestamp : null );
			$url       = get_month_link( $year, $month );
		} else {
			$title = (string) $year;
			$url   = get_year_link( $year );
		}

		return [
			'title'       => (string) $title,
			'description' => $description_fallback,
			'url'         => $url,
		];
	}

	/**
	 * Output the Open Graph tags markup.
	 *
	 * @param string       $type        The og:type value.
	 * @param string       $title       The og:title value.
	 * @param string       $description The og:description value.
	 * @param string       $url         The og:url value.
	 * @param string|false $image       The og:image value, or false/empty to omit it.
	 * @param string       $additional  Additional pre-escaped markup to include (e.g. article: tags).
	 */
	private function output_open_graph_tags( string $type, string $title, string $description, string $url, string|false $image, string $additional = '' ): void {
		if ( ! empty( $image ) ) {
			$additional .= sprintf( "\n<meta property=\"og:image\" content=\"%s\" />", esc_url( $image ) );
		}

		printf(
			<<<'HTML'
<!-- Start WP SEO Open Graph -->
<meta property="og:type" content="%1$s" />
<meta property="og:title" content="%2$s" />
<meta property="og:description" content="%3$s" />
<meta property="og:url" content="%4$s" />%5$s
<!-- End WP SEO Open Graph -->
HTML,
			esc_attr( $type ),
			esc_attr( $title ),
			esc_attr( $description ),
			esc_url( $url ),
			$additional // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped via esc_attr()/esc_url() when built above.
		);
	}
}
