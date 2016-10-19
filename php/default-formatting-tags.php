<?php
/**
 * Default formatting tags.
 *
 * @see WP_SEO_Formatting_Tag for property and method details.
 *
 * @package WP_SEO
 */

/**
 * Formatting tag for the site name.
 */
class WP_SEO_Format_Site_Name extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#site_name#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( "Replaced with this site's name.", 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return string Site name.
	 */
	public function get_value() {
		return get_bloginfo( 'name' );
	}
}

/**
 * Formatting tag for the site description.
 */
class WP_SEO_Format_Site_Description extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#site_description#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( "Replaced with this site's description.", 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return string Site description.
	 */
	public function get_value() {
		return get_bloginfo( 'description' );
	}
}

/**
 * Formatting tag for a post's title.
 */
class WP_SEO_Format_Title extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#title#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the title of the content being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Post title, or false.
	 */
	public function get_value() {
		if ( is_singular() && post_type_supports( get_post_type(), 'title' ) ) {
			return single_post_title( '', false );
		}

		return false;
	}
}

/**
 * Formatting tag for a post's excerpt.
 */
class WP_SEO_Format_Excerpt extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#excerpt#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( "Replaced with the excerpt of the content being viewed. An excerpt is generated if one isn't written.", 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * Uses the written excerpt if it exists.
	 *
	 * If there is no excerpt, then generates one similar to wp_trim_excerpt(),
	 * which fails to filter get_the_excerpt() itself at this point.
	 *
	 * @return mixed Post excerpt, or false.
	 */
	public function get_value() {
		if ( is_singular() && post_type_supports( get_post_type(), 'excerpt' ) ) {
			if ( $excerpt = get_the_excerpt() ) {
				return $excerpt;
			} else {
				$post = get_post();
				return wp_trim_words( $post->post_content, apply_filters( 'excerpt_length', 55 ), '' );
			}
		}

		return false;
	}
}

/**
 * Formatting tag for a post's publish date.
 */
class WP_SEO_Format_Date_Published extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#date_published#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the date that the content being viewed was published.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Post-publish date, or false.
	 */
	public function get_value() {
		if ( is_singular() ) {
			return get_the_date();
		}

		return false;
	}
}

/**
 * Formatting tag for a post's modified date.
 */
class WP_SEO_Format_Date_Modified extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#date_modified#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the date that the content being viewed was last modified.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Post-modified date, or false.
	 */
	public function get_value() {
		if ( is_singular() ) {
			return get_the_modified_date();
		}

		return false;
	}
}

/**
 * Formatting tag for a post's author.
 */
class WP_SEO_Format_Author extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#author#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the author name of the content or author archive being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * On singular, uses get_the_author() if it's available.
	 *
	 * If it isn't available yet, gets the author field from the post ID, and
	 * apply the 'the_author' filter for Co-Authors Plus support.
	 *
	 * On author archives, gets author data directly from the queried object,
	 * not get_the_author(), to prevent Co-Authors Plus from filtering it.
	 *
	 * @return mixed Author name, or false.
	 */
	public function get_value() {
		if ( is_singular() && post_type_supports( get_post_type(), 'author' ) ) {
			if ( $author = get_the_author() ) {
				return $author;
			} elseif ( $post_author = get_post_field( 'post_author', get_the_ID() ) ) {
				return apply_filters( 'the_author', get_the_author_meta( 'display_name', $post_author ) );
			}
		} elseif ( is_author() ) {
			return get_the_author_meta( 'display_name', get_queried_object_id() );
		}

		return false;
	}
}

/**
 * Formatting tag for a post's categories.
 */
class WP_SEO_Format_Categories extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#categories#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the Categories, comma-separated, of the content being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Category list, or false.
	 */
	public function get_value() {
		if ( is_singular() && is_object_in_taxonomy( get_post_type(), 'category' ) && $categories = get_the_category() ) {
			return implode( __( ', ', 'wp-seo' ), wp_list_pluck( $categories, 'name' ) );
		}

		return false;
	}
}

/**
 * Formatting tag for a post's tags.
 */
class WP_SEO_Format_Tags extends WP_SEO_Formatting_Tag {

	/**
	 * (Formatting) tag name.
	 *
	 * @var string
	 */
	public $tag = '#tags#';

	/**
	 * Get the (formatting) tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the Tags, comma-separated, of the content being viewed.', 'wp-seo' );
	}

	/**
	 * Get the (formatting) tag value for the current page.
	 *
	 * @return mixed Tag list, or false.
	 */
	public function get_value() {
		if ( is_singular() && is_object_in_taxonomy( get_post_type(), 'post_tag' ) && $tags = get_the_tags() ) {
			return implode( __( ', ', 'wp-seo' ), wp_list_pluck( $tags, 'name' ) );
		}

		return false;
	}
}

/**
 * Formatting tag for a term's name.
 */
class WP_SEO_Format_Term_Name extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#term_name#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the name of the term whose archive is being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Term name, or false.
	 */
	public function get_value() {
		if ( is_category() || is_tag() || is_tax() ) {
			return get_queried_object()->name;
		}

		return false;
	}
}

/**
 * Formatting tag for a term's description.
 */
class WP_SEO_Format_Term_Description extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#term_description#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the description of the term whose archive is being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Term description, or false.
	 */
	public function get_value() {
		if ( is_category() || is_tag() || is_tax() ) {
			return get_queried_object()->description;
		}

		return false;
	}
}

/**
 * Formatting tag for a post type's singular name.
 */
class WP_SEO_Format_Post_Type_Singular_Name extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#post_type_singular_name#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the singular form of the name of the post type being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Post type singular name, or false.
	 */
	public function get_value() {
		if ( is_singular() ) {
			return get_post_type_object( get_post_type() )->labels->singular_name;
		} elseif ( is_post_type_archive() ) {
			return get_queried_object()->labels->singular_name;
		}

		return false;
	}
}

/**
 * Formatting tag for a post type's plural name.
 */
class WP_SEO_Format_Post_Type_Plural_Name extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#post_type_plural_name#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the plural form of the name of the post type being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Post type plural name, or false.
	 */
	public function get_value() {
		if ( is_singular() ) {
			return get_post_type_object( get_post_type() )->labels->name;
		} elseif ( is_post_type_archive() ) {
			return get_queried_object()->labels->name;
		}

		return false;
	}
}

/**
 * Formatting tag for the date of a date archive.
 */
class WP_SEO_Format_Archive_Date extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#archive_date#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the date of the archive being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @see The "_s" theme for these date strings.
	 *
	 * @return mixed Date of the archive being viewed, or false.
	 */
	public function get_value() {
		if ( is_day() ) {
			return get_the_date();
		} elseif ( is_month() ) {
			return get_the_date( _x( 'F Y', 'monthly archives title tag format', 'wp-seo' ) );
		} elseif ( is_year() ) {
			return get_the_date( _x( 'Y', 'yearly archives title tag format', 'wp-seo' ) );
		}

		return false;
	}
}

/**
 * Formatting tag for a user's search term.
 */
class WP_SEO_Format_Search_Term extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#search_term#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( "Replaced with the user's search term.", 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Search term, or false.
	 */
	public function get_value() {
		return ( $term = get_search_query() ) ? $term : false;
	}
}

/**
 * Formatting tag for the post thumbnail URL.
 */
class WP_SEO_Format_Thumbnail_URL extends WP_SEO_Formatting_Tag {
	/**
	 * Tag name.
	 *
	 * @var string
	 */
	public $tag = '#thumbnail_url#';

	/**
	 * Get the tag description.
	 *
	 * @return string
	 */
	public function get_description() {
		return __( 'Replaced with the URL for the featured image of the content being viewed.', 'wp-seo' );
	}

	/**
	 * Get the tag value for the current page.
	 *
	 * @return mixed Thumbnail URL, or false.
	 */
	public function get_value() {
		/*
		 * The {@see "wp_seo_format_{$id}"} filter is available for returning a
		 * different image size. You also can subclass this formatting tag for
		 * your needs and use the {@see 'wp_seo_formatting_tags'} filter.
		 */
		return ( is_singular() ) ? get_the_post_thumbnail_url( get_the_ID(), 'full' ) : false;
	}
}

/**
 * Register the default formatting tags.
 *
 * @param  array $tags Associated array of formatting tags to load.
 * @return array       Tags to load, including these.
 */
function wp_seo_default_formatting_tags( $tags ) {
	$tags['site_name'] = new WP_SEO_Format_Site_Name;
	$tags['site_description'] = new WP_SEO_Format_Site_Description;
	$tags['title'] = new WP_SEO_Format_Title;
	$tags['excerpt'] = new WP_SEO_Format_Excerpt;
	$tags['date_published'] = new WP_SEO_Format_Date_Published;
	$tags['date_modified'] = new WP_SEO_Format_Date_Modified;
	$tags['author'] = new WP_SEO_Format_Author;
	$tags['categories'] = new WP_SEO_Format_Categories;
	$tags['tags'] = new WP_SEO_Format_Tags;
	$tags['term_name'] = new WP_SEO_Format_Term_Name;
	$tags['term_description'] = new WP_SEO_Format_Term_Description;
	$tags['post_type_singular_name'] = new WP_SEO_Format_Post_Type_Singular_Name;
	$tags['post_type_plural_name'] = new WP_SEO_Format_Post_Type_Plural_Name;
	$tags['archive_date'] = new WP_SEO_Format_Archive_Date;
	$tags['search_term'] = new WP_SEO_Format_Search_Term;
	$tags['thumbnail_url'] = new WP_SEO_Format_Thumbnail_URL;
	return $tags;
}
add_filter( 'wp_seo_formatting_tags', 'wp_seo_default_formatting_tags' );
