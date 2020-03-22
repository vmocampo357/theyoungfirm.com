<?php
use Timber\Timber;

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
$templates = array( 'archives/faqs.twig', 'index.twig' );

# goes into twig
$context = Timber::get_context();

# says yes, this is an archive (might be redundant)
$context['is_archive'] = true;

# posts found for faqs (not really.. needed..)
# $context['posts'] = new Timber\PostQuery();

# the types of taxes here
$terms = get_terms( array(
    'taxonomy' => 'faq-type',
    'hide_empty' => false,
) );
$context['terms'] = $terms;

# if we have taxes, lets go through each and find their posts
$term_posts = [];
foreach($terms as $term)
{
    if($term->count > 0){
        $term_posts[$term->slug] =  Timber::get_posts([
            'post_type' => 'faqs',
            'tax_query' => array(
                array(
                    'taxonomy' => 'faq-type',
                    'field'    => 'slug',
                    'terms'    => $term->slug,
                ),
            ),
        ]);
    }
}
$context['posts'] = $term_posts;

Timber::render( $templates, $context );
