<?php
/**
 * Template Name: Sitemap
 * Description: Maritime Injury Law - Sitemap
 */
/**
 * The template for displaying the 'Sitemap' template
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */


$context = Timber::get_context();

/*
 * Start by getting anything directly from the Post itself. This can be useful if they want more things
 * here than what the auto-sitemap provides.
 *
 */
$post = new TimberPost();
$context['post'] = $post;


/*
 * Next, try and get all the pages and their subpages somehow, maybe just loop through pages and see if there
 * is an option for sub pages?
 *
 */
$pages = Timber::get_posts([
    'post_type' => 'page'
]);

$context['pages'] = wp_list_pages([
    'echo' => false,
    'title_li' => ''
]);

/*
 * Loop through all Post categories, show those posts
 *
 */

# Find all the Categories we have in the system
$terms = get_terms( array(
    'taxonomy' => 'category',
    'hide_empty' => false,
) );

$context['terms'] = $terms;

# if we have taxes, lets go through each and find their posts
$term_posts = [];
foreach($terms as $term)
{
    if($term->count > 0){
        $term_posts[$term->slug] =  Timber::get_posts([
            'post_type' => 'post',
            'tax_query' => array(
                array(
                    'taxonomy' => 'category',
                    'field'    => 'slug',
                    'terms'    => $term->slug,
                ),
            ),
        ]);
    }
}
$context['posts'] = $term_posts;

/*
 * Loop through all Custom Post Types, show those posts
 *
 */

# This stores custom posts per post type, which stores the label and the posts themselves
$custom_posts = [];


# A query for all the post types in the system that we should be displaying
$post_types = get_post_types([
    'public'   => true,
    '_builtin' => false,
    'exclude_from_search'  => false
]);


# Loop through the post types we found, add them to our overall custom_posts array
foreach($post_types as $post_type){

    # Get the post_type object for the label
    $post_type_object = get_post_type_object($post_type);

    # Set the label each time if it isn't set
    $custom_posts[$post_type]['label'] = $post_type_object->label;

    # Run an actual query for all these posts, and put them in the posts part
    $custom_posts[$post_type]['posts'] = Timber::get_posts([
        'post_type' => $post_type
    ]);
}


$context['custom_posts'] = $custom_posts;


Timber::render( array( 'sitemap.twig' ), $context );