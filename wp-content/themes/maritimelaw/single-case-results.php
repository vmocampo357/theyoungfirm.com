<?php
/**
 * The template for displaying the 'Case Result' single page template
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */


$context = Timber::get_context();

# This is the actual Our Team content
$post = new TimberPost();
$context['post'] = $post;

$exclude_id = $post->id;

# the types of tags here
$terms = get_terms( array(
    'taxonomy' => 'case-result-tag',
    'hide_empty' => false,
) );
$context['terms'] = $terms;

# other posts
$context['case_results'] = \Timber\Timber::get_posts([
    'post_type' => 'case-results',
    'post__not_in' => [ $exclude_id ],
    'posts_per_page' => -1
]);

# Final rendering
Timber::render( array( 'single-case-result.twig'  ), $context );