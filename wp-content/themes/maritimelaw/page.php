<?php
/**
 * The template for displaying the 'Page' template
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */


$context = Timber::get_context();

# This is the actual Our Team content
$post = new TimberPost();
$context['post'] = $post;

# Final rendering
Timber::render( array( 'page.twig' ), $context );