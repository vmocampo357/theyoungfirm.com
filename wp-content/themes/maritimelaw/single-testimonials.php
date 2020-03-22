<?php
/**
 * The template for displaying the 'Testimonial' single page template
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

# other posts
$context['other_testimonials'] = \Timber\Timber::get_posts([
    'post_type' => 'testimonials',
    'post__not_in' => [ $exclude_id ]
]);

# Final rendering
Timber::render( array( 'single-testimonials.twig'  ), $context );