<?php
/**
 * Default formatting tags.
 *
 * @see WP_SEO_Formatting_Tag for property and method details.
 *
 * @package WP_SEO
 */

require_once( WP_SEO_PATH . '/php/class-wp-seo-format-site-name.php' );
require_once( WP_SEO_PATH . '/php/class-wp-seo-format-site-description.php' );
require_once( WP_SEO_PATH . '/php/class-wp-seo-format-title.php' );
require_once( WP_SEO_PATH . '/php/class-wp-seo-format-excerpt.php' );
require_once( WP_SEO_PATH . '/php/class-wp-seo-format-date-published.php' );
require_once( WP_SEO_PATH . '/php/class-wp-seo-format-date-modified.php' );
require_once( WP_SEO_PATH . '/php/class-wp-seo-format-author.php' );
require_once( WP_SEO_PATH . '/php/class-wp-seo-format-categories.php' );
require_once( WP_SEO_PATH . '/php/class-wp-seo-format-tags.php' );
require_once( WP_SEO_PATH . '/php/class-wp-seo-format-term-name.php' );
require_once( WP_SEO_PATH . '/php/class-wp-seo-format-term-description.php' );
require_once( WP_SEO_PATH . '/php/class-wp-seo-format-post-type-singular-name.php' );
require_once( WP_SEO_PATH . '/php/class-wp-seo-format-post-type-plural-name.php' );
require_once( WP_SEO_PATH . '/php/class-wp-seo-format-archive-date.php' );
require_once( WP_SEO_PATH . '/php/class-wp-seo-format-search-term.php' );

/**
 * Register the default formatting tags.
 *
 * @param array $tags Associated array of formatting tags to load.
 * @return array Tags to load, including these.
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
