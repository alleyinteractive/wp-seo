<?php
/**
 * Default formatting tags.
 *
 * @see  WP_SEO_Formatting_Tag for property and method details.
 */

class WP_SEO_Format_Site_Name extends WP_SEO_Formatting_Tag {

	public $tag = '#site_name#';

	public function get_description() {
		return __( "Replaced with this site's name.", 'wp-seo' );
	}

	public function get_value() {
		return get_bloginfo( 'name' );
	}

}

class WP_SEO_Format_Site_Description extends WP_SEO_Formatting_Tag {

	public $tag = '#site_description#';

	public function get_description() {
		return __( "Replaced with this site's description.", 'wp-seo' );
	}

	public function get_value() {
		return get_bloginfo( 'description' );
	}

}

class WP_SEO_Format_Title extends WP_SEO_Formatting_Tag {

	public $tag = '#title#';

	public function get_description() {
		return __( 'Replaced with the title of the content being viewed.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_singular() && post_type_supports( get_post_type(), 'title' ) ) {
			return single_post_title( '', false );
		}

		return false;
	}

}

class WP_SEO_Format_Excerpt extends WP_SEO_Formatting_Tag {

	public $tag = '#excerpt#';

	public function get_description() {
		return __( "Replaced with the excerpt of the content being viewed. An excerpt is generated if one isn't written.", 'wp-seo' );
	}

	/**
	 * Use the written excerpt if it exists.
	 *
	 * If there is no excerpt, then generate one similar to wp_trim_excerpt(),
	 * which fails to filter get_the_excerpt() itself at this point.
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

class WP_SEO_Format_Date_Published extends WP_SEO_Formatting_Tag {

	public $tag = '#date_published#';

	public function get_description() {
		return __( 'Replaced with the date that the content being viewed was published.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_singular() ) {
			return get_the_date();
		}

		return false;
	}

}

class WP_SEO_Format_Date_Modified extends WP_SEO_Formatting_Tag {

	public $tag = '#date_modified#';

	public function get_description() {
		return __( 'Replaced with the date that the content being viewed was last modified.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_singular() ) {
			return get_the_modified_date();
		}

		return false;
	}

}

class WP_SEO_Format_Author extends WP_SEO_Formatting_Tag {

	public $tag = '#author#';

	public function get_description() {
		return __( 'Replaced with the author name of the content or author archive being viewed.', 'wp-seo' );
	}

	/**
	 * On singular, use get_the_author() if it's available.
	 *
	 * If it isn't available yet, get the author field from the post ID, and
	 * apply the 'the_author' filter for Co-Authors Plus support.
	 *
	 * On author archives, get author data directly from the queried object, not
	 * get_the_author(), to prevent Co-Authors Plus from filtering it.
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

class WP_SEO_Format_Categories extends WP_SEO_Formatting_Tag {

	public $tag = '#categories#';

	public function get_description() {
		return __( 'Replaced with the Categories, comma-separated, of the content being viewed.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_singular() && is_object_in_taxonomy( get_post_type(), 'category' ) && $categories = get_the_category() ) {
			return implode( __( ', ', 'wp-seo' ), wp_list_pluck( $categories, 'name' ) );
		}

		return false;
	}

}

class WP_SEO_Format_Tags extends WP_SEO_Formatting_Tag {

	public $tag = '#tags#';

	public function get_description() {
		return __( 'Replaced with the Tags, comma-separated, of the content being viewed.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_singular() && is_object_in_taxonomy( get_post_type(), 'post_tag' ) && $tags = get_the_tags() ) {
			return implode( __( ', ', 'wp-seo' ), wp_list_pluck( $tags, 'name' ) );
		}

		return false;
	}

}

class WP_SEO_Format_Term_Name extends WP_SEO_Formatting_Tag {

	public $tag = '#term_name#';

	public function get_description() {
		return __( 'Replaced with the name of the term whose archive is being viewed.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_category() || is_tag() || is_tax() ) {
			return get_queried_object()->name;
		}

		return false;
	}

}

class WP_SEO_Format_Term_Description extends WP_SEO_Formatting_Tag {

	public $tag = '#term_description#';

	public function get_description() {
		return __( 'Replaced with the description of the term whose archive is being viewed.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_category() || is_tag() || is_tax() ) {
			return get_queried_object()->description;
		}

		return false;
	}

}

class WP_SEO_Format_Post_Type_Singular_Name extends WP_SEO_Formatting_Tag {

	public $tag = '#post_type_singular_name#';

	public function get_description() {
		return __( 'Replaced with the singular form of the name of the post type being viewed.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_singular() ) {
			return get_post_type_object( get_post_type() )->labels->singular_name;
		} elseif ( is_post_type_archive() ) {
			return get_queried_object()->labels->singular_name;
		}

		return false;
	}

}

class WP_SEO_Format_Post_Type_Plural_Name extends WP_SEO_Formatting_Tag {

	public $tag = '#post_type_plural_name#';

	public function get_description() {
		return __( 'Replaced with the plural form of the name of the post type being viewed.', 'wp-seo' );
	}

	public function get_value() {
		if ( is_singular() ) {
			return get_post_type_object( get_post_type() )->labels->name;
		} elseif ( is_post_type_archive() ) {
			return get_queried_object()->labels->name;
		}

		return false;
	}

}

class WP_SEO_Format_Archive_Date extends WP_SEO_Formatting_Tag {

	public $tag = '#archive_date#';

	public function get_description() {
		return __( 'Replaced with the date of the archive being viewed.', 'wp-seo' );
	}

	// @see the "_s" theme for these date strings.
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

class WP_SEO_Format_Search_Term extends WP_SEO_Formatting_Tag {

	public $tag = '#search_term#';

	public function get_description() {
		return __( "Replaced with the user's search term.", 'wp-seo' );
	}

	public function get_value() {
		return ( $term = get_search_query() ) ? $term : false;
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
	return $tags;
}
add_filter( 'wp_seo_formatting_tags', 'wp_seo_default_formatting_tags' );