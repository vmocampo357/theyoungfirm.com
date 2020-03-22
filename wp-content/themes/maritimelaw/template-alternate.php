<?php
/**
 * Template Name: Alternative Page Template (see, Practice Areas Post Pages)
 * Description: Homepage specific template, should be used as a Front Page under read settings
 */
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
Timber::render( array( 'single-alt.twig' ), $context );