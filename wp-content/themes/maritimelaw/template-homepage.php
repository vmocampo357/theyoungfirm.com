<?php
/**
 * Template Name: Maritime Law Homepage
 * Description: Homepage specific template, should be used as a Front Page under read settings
 */
/**
 * The template for displaying the homepage
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */


$context = Timber::get_context();

/**
 * This is the normal page we'll render, if we weren't searching for anything
 */

# This is the actual homepage content

/** @var Timber\Post $post */
$post = new TimberPost();
$context['post'] = $post;

/*
 * Only gets testimonials that are featured, and that have video available
 *
 */
$testimonials = [];
$context['testimonials_available'] = $testimonials = \Timber\Timber::get_posts([
    'post_type' => 'testimonials',
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key'     => 'tst_testimonial_video',
            'value'   => '',
            'compare' => '!=',
        ),
        array(
            'key'     => 'tst_featured_testimonial',
            'value'   => true,
            'compare' => '=',
        ),
    ),
]);

$testimonial_found = (count($testimonials) > 0) ? true : false;


/*
 * Get any available content snippets
 *
 */
$context['below_banner_snippets'] = \Timber\Timber::get_posts([
    'post_type' => 'content-snippet',
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key'       => 'cs_block_location',
            'value'     => 'banner',
            'compare'   => '='
        )
    )
]);

$context['default_snippets'] = \Timber\Timber::get_posts([
    'post_type' => 'content-snippet',
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key'       => 'cs_block_location',
            'value'     => 'default',
            'compare'   => '='
        )
    )
]);

$context['menu_snippets'] = \Timber\Timber::get_posts([
    'post_type' => 'content-snippet',
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key'       => 'cs_block_location',
            'value'     => 'menu',
            'compare'   => '='
        )
    )
]);

$context['cases_snippets'] = \Timber\Timber::get_posts([
    'post_type' => 'content-snippet',
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key'       => 'cs_block_location',
            'value'     => 'cases',
            'compare'   => '='
        )
    )
]);

/*
 * Final context compiler
 *
 */
$context['is_mobile'] = (!wp_is_mobile());
$context['testimonial_found'] = $testimonial_found;
$context['testimonials'] = ( $testimonial_found ) ? $testimonials : [];

Timber::render( array( 'page-template-homepage.twig' ), $context );

