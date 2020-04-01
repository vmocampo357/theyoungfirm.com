<?php
/**
 * Search results page
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since   Timber 0.1
 */

$templates = array( 'search.twig', 'index.twig' );

$context          = Timber::get_context();
$context['title'] = 'Search results for ' . get_search_query();
$context['term'] = get_search_query();
$context['results'] = new Timber\PostQuery();
$context['paginations'] = get_the_posts_pagination();

Timber::render( $templates, $context );