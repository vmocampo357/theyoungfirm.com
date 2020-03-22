<?php
/**
 * Template Name: Calculator (Chained Quiz)
 * Description: Maritime Injury Law - Chained Quiz
 */
/**
 * The template for displaying the 'Calculator' template
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
Timber::render( array( 'page-calculator.twig' ), $context );