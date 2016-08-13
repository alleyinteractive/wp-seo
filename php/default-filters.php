<?php
/**
 * Sets up the default filters and actions for most WP SEO hooks.
 *
 * If you need to remove a default hook, this file will give you the priority.
 *
 * Not all of the default hooks are here (yet).
 *
 * @since 0.11.3-beta1 IMPORTANT! Some hooks in this file will change to support
 *    new features in an upcoming version of WP SEO. The release notes will
 *    include details about these changes.
 *
 * @package WP_SEO
 */

add_action( 'admin_init',                                                       'wp_seo_load_admin_files', 0 );

add_action( 'wp_seo_post_meta_fields',                                          'wp_seo_the_post_meta_fields' );
add_action( 'wp_seo_post_meta_fields_title_label',                              'wp_seo_the_meta_title_label' );
add_action( 'wp_seo_post_meta_fields_title_input',                              'wp_seo_post_id_to_the_meta_title_input' );
add_action( 'wp_seo_post_meta_fields_after_title_input',                        'wp_seo_post_id_to_the_title_character_count' );
add_action( 'wp_seo_post_meta_fields_description_label',                        'wp_seo_the_meta_description_label' );
add_action( 'wp_seo_post_meta_fields_description_input',                        'wp_seo_post_id_to_the_meta_description_input' );
add_action( 'wp_seo_post_meta_fields_after_description_input',                  'wp_seo_post_id_to_the_description_character_count' );
add_action( 'wp_seo_post_meta_fields_keywords_label',                           'wp_seo_the_meta_keywords_label' );
add_action( 'wp_seo_post_meta_fields_keywords_input',                           'wp_seo_post_id_to_the_meta_keywords_input' );

add_action( 'wp_seo_add_term_meta_fields',                                      'wp_seo_the_add_term_meta_fields' );
add_action( 'wp_seo_add_term_meta_fields_title_label',                          'wp_seo_the_meta_title_label' );
add_action( 'wp_seo_add_term_meta_fields_title_input',                          'wp_seo_the_meta_title_input' );
add_action( 'wp_seo_add_term_meta_fields_after_title_input',                    'wp_seo_the_title_character_count' );
add_action( 'wp_seo_add_term_meta_fields_description_label',                    'wp_seo_the_meta_description_label' );
add_action( 'wp_seo_add_term_meta_fields_description_input',                    'wp_seo_the_meta_description_input' );
add_action( 'wp_seo_add_term_meta_fields_after_description_input',              'wp_seo_the_description_character_count' );
add_action( 'wp_seo_add_term_meta_fields_keywords_label',                       'wp_seo_the_meta_keywords_label' );
add_action( 'wp_seo_add_term_meta_fields_keywords_input',                       'wp_seo_the_meta_keywords_input' );

add_action( 'wp_seo_edit_term_meta_fields',                                     'wp_seo_the_edit_term_meta_fields', 10, 2 );
add_action( 'wp_seo_edit_term_meta_fields_title_label',                         'wp_seo_the_meta_title_label' );
add_action( 'wp_seo_edit_term_meta_fields_title_input',                         'wp_seo_term_data_to_the_meta_title_input', 10, 2 );
add_action( 'wp_seo_edit_term_meta_fields_after_title_input',                   'wp_seo_term_data_to_the_title_character_count', 10, 2 );
add_action( 'wp_seo_edit_term_meta_fields_description_label',                   'wp_seo_the_meta_description_label' );
add_action( 'wp_seo_edit_term_meta_fields_description_input',                   'wp_seo_term_data_to_the_meta_description_input', 10, 2 );
add_action( 'wp_seo_edit_term_meta_fields_after_description_input',             'wp_seo_term_data_to_the_description_character_count', 10, 2 );
add_action( 'wp_seo_edit_term_meta_fields_keywords_label',                      'wp_seo_the_meta_keywords_label' );
add_action( 'wp_seo_edit_term_meta_fields_keywords_input',                      'wp_seo_term_data_to_the_meta_keywords_input', 10, 2 );
