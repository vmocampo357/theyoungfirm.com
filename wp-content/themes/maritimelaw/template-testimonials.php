<?php
/**
 * Template Name: Testimonials Library
 * Description: Maritime Injury Law - Testimonials Page
 */
/**
 * The template for displaying the 'Testimonials' template
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */


$context = Timber::get_context();

# This is the actual Our Team content
$post = new TimberPost();
$context['post'] = $post;

$context['posts'] = Timber::get_posts([
    'post_type' => 'testimonials'
]);

# Final rendering
Timber::render( array( 'archives/testimonials.twig' ), $context );