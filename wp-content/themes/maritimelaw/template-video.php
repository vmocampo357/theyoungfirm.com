<?php
/**
 * Template Name: Video Library
 * Description: Maritime Injury Law - Video Page
 */
/**
 * The template for displaying the 'Video' template
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */


$context = Timber::get_context();

# This is the actual Our Team content
$post = new TimberPost();
$context['post'] = $post;

# the types of taxes here
$terms = get_terms( array(
    'taxonomy' => 'video-category',
    'hide_empty' => false,
) );
$context['terms'] = $terms;

# if we have taxes, lets go through each and find their posts
$term_posts = [];
foreach($terms as $term)
{
    if($term->count > 0){

        $posts_split_array = [];

        $first_post_ids = [];

        $first_posts = Timber::get_posts([
            'posts_per_page' => -1,
            'post_type' => 'video',
            'tax_query' => array(
                array(
                    'taxonomy' => 'video-category',
                    'field'    => 'slug',
                    'terms'    => $term->slug,
                ),
            ),
            'meta_query' => array(
                array(
                    'key'     => 'vv_is_featured_video',
                    'value'   => array( 1 ),
                    'compare' => 'IN',
                ),
            ),
        ]);

        if( !empty($first_posts) )
        {
            foreach($first_posts as $first_post)
            {
                $first_post_ids[] = $first_post->ID;
            }
            $posts_split_array[] = $first_posts;
        }

        $second_posts = Timber::get_posts([
            'posts_per_page' => -1,
            'post_type' => 'video',
            'post__not_in' => $first_post_ids,
            'tax_query' => array(
                array(
                    'taxonomy' => 'video-category',
                    'field'    => 'slug',
                    'terms'    => $term->slug,
                ),
            )
        ]);

        $posts_split_array[] = $second_posts;

        $term_posts[$term->slug] =  $first_posts + $second_posts;
    }
}
$context['posts'] = $term_posts;

# Final rendering
Timber::render( array( 'archives/videos.twig' ), $context );