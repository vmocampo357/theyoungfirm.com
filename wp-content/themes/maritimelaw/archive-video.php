<?php
/**
 * The template for displaying Archive pages.
 *
 * Used to display archive-type pages if nothing more specific matches a query.
 * For example, puts together date-based pages if no date.php file exists.
 *
 * Learn more: http://codex.wordpress.org/Template_Hierarchy
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.2
 */

# the templates to follow
$templates = array( 'archives/videos.twig', 'index.twig' );

# goes into twig
$context = Timber::get_context();

# says yes, this is an archive (might be redundant)
$context['is_archive'] = true;

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

Timber::render( $templates, $context );
