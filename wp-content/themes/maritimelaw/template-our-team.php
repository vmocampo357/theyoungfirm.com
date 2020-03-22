<?php
/**
 * Template Name: Our Team
 * Description: Maritime Injury Law - Team Page
 */
/**
 * The template for displaying the 'Our Team' template
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */


$context = Timber::get_context();

# This is the actual Our Team content
$post = new TimberPost();
$context['post'] = $post;

# These are the Attorneys
$args = array(
    'post_type' => 'attorney',
);
$context['attorneys'] = Timber::get_posts( $args );

# These are the Staff Members
$args = array(
    'post_type' => 'staff'
);
$context['staff'] = Timber::get_posts( $args );

# Final rendering
Timber::render( array( 'page-our-team.twig' ), $context );