<?php
/**
 * The template for displaying the 'Case Results' library template
 *
 * @package  WordPress
 * @subpackage  Timber
 * @since    Timber 0.1
 */


$context = Timber::get_context();

# This is the actual Our Team content
$post = new TimberPost();
$context['post'] = $post;

# Then we'll also get all the different Claim Types
$terms = get_terms( array(
    'taxonomy' => 'claim-type',
    'hide_empty' => false,
) );
$context['terms'] = $terms;

# And finally, organize the content according to their Claim Types
# other posts
$context['case_results'] = \Timber\Timber::get_posts([
    'post_type' => 'case-results'
]);

# Final rendering
Timber::render( array( 'archives/case-results.twig' ), $context );